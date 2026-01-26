"""
Serviço de Quilometragem Automática

Responsável por:
- Buscar odômetro diário de veículos via API Ituran
- Calcular KM rodados (odômetro atual - odômetro início do dia)
- Salvar dados na tabela daily_mileage via API PHP
- Gestão de erros e retry automático

IMPORTANTE - Lógica de cálculo:
- DIA ATUAL (hoje): odômetro_atual (tempo real) - odômetro_meia_noite_de_hoje
- DIAS PASSADOS: odômetro_meia_noite_próximo_dia - odômetro_meia_noite_do_dia
"""

import requests
import logging
from datetime import datetime, timedelta
from typing import Dict, Optional, List
from xml.etree import ElementTree as ET
import json

# Configurar logger
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class MileageService:
    """Serviço para cálculo e sincronização de quilometragem diária"""

    # Configurações da API Ituran
    ITURAN_BASE_URL = 'https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx'
    ITURAN_USERNAME = 'api@i9tecnologia'
    ITURAN_PASSWORD = 'Api@In9Eng'

    # Configurações da API PHP
    PHP_API_BASE_URL = 'https://floripa.in9automacao.com.br'

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/x-www-form-urlencoded',
            'User-Agent': 'FleetBackend/1.0'
        })

    def get_vehicle_odometer(self, plate: str, date: datetime) -> Optional[Dict]:
        """
        Busca o odômetro do veículo em uma data específica via API Ituran
        NOTA: Retorna o odômetro de MEIA-NOITE da data especificada

        Args:
            plate: Placa do veículo (ex: 'RTS9B92')
            date: Data para consulta

        Returns:
            Dict com 'odometer' (float), 'timestamp' (datetime), 'result_code' (str)
            ou None se houver erro
        """
        try:
            # Formatar data para API Ituran (YYYY-MM-DD)
            date_str = date.strftime('%Y-%m-%d')

            # Montar URL da API
            url = f"{self.ITURAN_BASE_URL}/GetVehicleMileage_JSON"
            params = {
                'Plate': plate,
                'LocTime': date_str,
                'UserName': self.ITURAN_USERNAME,
                'Password': self.ITURAN_PASSWORD
            }

            logger.info(f"🔍 Buscando odômetro (meia-noite): {plate} em {date_str}")

            # Fazer requisição
            response = self.session.get(url, params=params, timeout=30)
            response.raise_for_status()

            # Parse XML response (API retorna JSON dentro de XML)
            root = ET.fromstring(response.content)
            json_data = root.text

            if not json_data:
                logger.error(f"❌ Resposta vazia da API Ituran para {plate}")
                return None

            # Parse JSON
            data = json.loads(json_data)

            # Verificar resultado
            if data.get('ResultCode') != 'OK':
                logger.error(f"❌ API Ituran retornou erro: {data.get('ResultCode')} para {plate}")
                return None

            # Extrair dados
            odometer = float(data.get('resMileage', 0))
            timestamp = data.get('resLocTime', date_str)

            logger.info(f"✅ Odômetro de {plate}: {odometer:,.2f} km em {timestamp}")

            return {
                'odometer': odometer,
                'timestamp': timestamp,
                'result_code': data.get('ResultCode'),
                'plate': plate,
                'date': date
            }

        except requests.exceptions.RequestException as e:
            logger.error(f"❌ Erro de rede ao buscar {plate}: {e}")
            return None
        except ET.ParseError as e:
            logger.error(f"❌ Erro ao parsear XML da API Ituran para {plate}: {e}")
            return None
        except json.JSONDecodeError as e:
            logger.error(f"❌ Erro ao parsear JSON da API Ituran para {plate}: {e}")
            return None
        except Exception as e:
            logger.error(f"❌ Erro inesperado ao buscar {plate}: {e}")
            return None

    def get_current_odometer(self, plate: str) -> Optional[Dict]:
        """
        Busca o odômetro ATUAL (tempo real) do veículo via GetFullReport
        Usa o período de hoje 00:00 até agora para pegar o registro mais recente

        Args:
            plate: Placa do veículo

        Returns:
            Dict com 'odometer' (float), 'timestamp' (str)
            ou None se houver erro
        """
        try:
            # Período: hoje 00:00 até agora
            now = datetime.now()
            start = now.replace(hour=0, minute=0, second=0).strftime('%Y-%m-%d %H:%M:%S')
            end = now.strftime('%Y-%m-%d %H:%M:%S')

            url = f"{self.ITURAN_BASE_URL}/GetFullReport"
            params = {
                'UserName': self.ITURAN_USERNAME,
                'Password': self.ITURAN_PASSWORD,
                'Plate': plate,
                'Start': start,
                'End': end,
                'UAID': '0',
                'MaxNumberOfRecords': '1000'
            }

            logger.info(f"🔍 Buscando odômetro ATUAL: {plate} (período: {start} até {end})")

            response = self.session.get(url, params=params, timeout=30)
            response.raise_for_status()

            # Parse XML - remover namespace para facilitar parse
            xml_content = response.content.decode('utf-8')
            # Remover namespace do XML
            xml_content = xml_content.replace('xmlns="http://www.ituran.com/ituranWebService3"', '')
            root = ET.fromstring(xml_content)

            # Buscar registros - estrutura: Records -> RecordWithPlate
            records = root.findall('.//RecordWithPlate')

            if not records:
                # Tentar estrutura alternativa
                records = root.findall('.//Record')

            if not records:
                logger.warning(f"⚠️ Nenhum registro encontrado para {plate} hoje (total elementos: {len(list(root.iter()))})")
                return None

            # Encontrar o registro mais recente (maior Mileage ou mais recente Date)
            latest_odometer = 0
            latest_timestamp = None

            for record in records:
                mileage_elem = record.find('Mileage')
                date_elem = record.find('Date')

                if mileage_elem is not None:
                    mileage = float(mileage_elem.text or 0)
                    # Mileage já vem em KM (não em metros)
                    mileage_km = mileage

                    if mileage_km > latest_odometer:
                        latest_odometer = mileage_km
                        if date_elem is not None:
                            latest_timestamp = date_elem.text

            if latest_odometer > 0:
                logger.info(f"✅ Odômetro ATUAL de {plate}: {latest_odometer:,.2f} km")
                return {
                    'odometer': latest_odometer,
                    'timestamp': latest_timestamp or now.isoformat(),
                    'plate': plate
                }

            logger.warning(f"⚠️ Não foi possível obter odômetro atual para {plate}")
            return None

        except Exception as e:
            logger.error(f"❌ Erro ao buscar odômetro atual de {plate}: {e}")
            return None

    def calculate_daily_mileage(self, plate: str, target_date: datetime) -> Optional[Dict]:
        """
        Calcula a quilometragem diária de um veículo

        LÓGICA:
        - Se target_date = HOJE: usa odômetro ATUAL (tempo real) - odômetro meia-noite de hoje
        - Se target_date = dia PASSADO: usa odômetro meia-noite(dia+1) - odômetro meia-noite(dia)

        Args:
            plate: Placa do veículo
            target_date: Data para calcular

        Returns:
            Dict com odometer_start, odometer_end, km_driven, date
            ou None se houver erro
        """
        try:
            today = datetime.now().date()
            target_day = target_date.date() if isinstance(target_date, datetime) else target_date

            # Verificar se é o dia atual
            is_today = (target_day == today)

            if is_today:
                # ============================================
                # CÁLCULO PARA O DIA ATUAL (tempo real)
                # ============================================
                logger.info(f"📊 Calculando KM de HOJE para {plate} (tempo real)")

                # Odômetro de meia-noite de hoje (início do dia)
                odometer_start_data = self.get_vehicle_odometer(plate, target_date)
                if not odometer_start_data:
                    logger.warning(f"⚠️ Não foi possível obter odômetro inicial para {plate}")
                    return None

                # Odômetro ATUAL (tempo real via GetFullReport)
                odometer_end_data = self.get_current_odometer(plate)
                if not odometer_end_data:
                    # Fallback: usar odômetro de meia-noite (KM = 0 se não rodou ainda hoje)
                    logger.warning(f"⚠️ Usando fallback para {plate} - sem dados de hoje ainda")
                    odometer_end_data = {'odometer': odometer_start_data['odometer']}

                odometer_start = odometer_start_data['odometer']
                odometer_end = odometer_end_data['odometer']

                # KM rodados HOJE (parcial, até o momento atual)
                km_driven = odometer_end - odometer_start

                # Data do registro = HOJE
                result_date = target_day

            else:
                # ============================================
                # CÁLCULO PARA DIAS PASSADOS
                # ============================================
                logger.info(f"📊 Calculando KM de {target_day} para {plate} (dia passado)")

                # Odômetro de meia-noite do dia alvo (início do dia)
                odometer_start_data = self.get_vehicle_odometer(plate, target_date)

                # Odômetro de meia-noite do dia SEGUINTE (fim do dia alvo)
                next_day = target_date + timedelta(days=1)
                odometer_end_data = self.get_vehicle_odometer(plate, next_day)

                if not odometer_start_data or not odometer_end_data:
                    logger.warning(f"⚠️ Não foi possível obter odômetros completos para {plate}")
                    return None

                odometer_start = odometer_start_data['odometer']
                odometer_end = odometer_end_data['odometer']

                # KM rodados no dia alvo
                km_driven = odometer_end - odometer_start

                # Data do registro = dia alvo
                result_date = target_day

            # Validar resultado (KM não pode ser negativo)
            if km_driven < 0:
                logger.warning(f"⚠️ KM negativo detectado para {plate}: {km_driven:.2f} km")
                km_driven = 0

            logger.info(f"📊 {plate}: {km_driven:.2f} km rodados em {result_date}")

            return {
                'plate': plate,
                'date': result_date.strftime('%Y-%m-%d') if hasattr(result_date, 'strftime') else str(result_date),
                'odometer_start': odometer_start,
                'odometer_end': odometer_end,
                'km_driven': km_driven,
                'synced_at': datetime.now().isoformat(),
                'is_partial': is_today  # Marca se é cálculo parcial (dia atual)
            }

        except Exception as e:
            logger.error(f"❌ Erro ao calcular quilometragem diária para {plate}: {e}")
            return None

    def save_mileage_to_database(self, mileage_data: Dict) -> bool:
        """
        Salva dados de quilometragem no banco via API PHP

        Usa UPSERT (INSERT ... ON DUPLICATE KEY UPDATE)

        Args:
            mileage_data: Dict com plate, date, odometer_start, odometer_end, km_driven

        Returns:
            True se salvou com sucesso, False caso contrário
        """
        try:
            # Endpoint da API PHP
            url = f"{self.PHP_API_BASE_URL}/daily-mileage-api.php"

            # Preparar payload
            payload = {
                'vehicle_plate': mileage_data['plate'],
                'date': mileage_data['date'],
                'odometer_start': mileage_data.get('odometer_start'),
                'odometer_end': mileage_data.get('odometer_end'),
                'km_driven': mileage_data['km_driven'],
                'source': 'API',
                'sync_status': 'success' if mileage_data['km_driven'] >= 0 else 'failed',
                'synced_at': mileage_data.get('synced_at', datetime.now().isoformat())
            }

            logger.info(f"💾 Salvando no banco: {payload['vehicle_plate']} - {payload['km_driven']:.2f} km em {payload['date']}")

            # Fazer requisição POST
            response = self.session.post(url, json=payload, timeout=10)
            response.raise_for_status()

            result = response.json()

            if result.get('success'):
                logger.info(f"✅ Salvo com sucesso: {payload['vehicle_plate']} em {payload['date']}")
                return True
            else:
                logger.error(f"❌ Erro ao salvar: {result.get('error', 'Erro desconhecido')}")
                return False

        except requests.exceptions.RequestException as e:
            logger.error(f"❌ Erro de rede ao salvar dados: {e}")
            return False
        except Exception as e:
            logger.error(f"❌ Erro inesperado ao salvar dados: {e}")
            return False

    def sync_vehicle_mileage(self, plate: str, date: Optional[datetime] = None) -> bool:
        """
        Sincroniza quilometragem de um veículo (busca API + salva DB)

        Args:
            plate: Placa do veículo
            date: Data para sincronizar (padrão: HOJE para KM em tempo real)

        Returns:
            True se sincronizou com sucesso
        """
        try:
            # Se não especificou data, usar HOJE (KM parcial em tempo real)
            if date is None:
                date = datetime.now()

            logger.info(f"🔄 Sincronizando {plate} para {date.strftime('%Y-%m-%d')}")

            # Calcular quilometragem diária
            mileage_data = self.calculate_daily_mileage(plate, date)

            if not mileage_data:
                logger.error(f"❌ Falha ao calcular quilometragem para {plate}")
                return False

            # Salvar no banco
            success = self.save_mileage_to_database(mileage_data)

            return success

        except Exception as e:
            logger.error(f"❌ Erro ao sincronizar {plate}: {e}")
            return False

    def sync_all_vehicles(self, date: Optional[datetime] = None) -> Dict[str, int]:
        """
        Sincroniza quilometragem de TODOS os veículos ativos

        Args:
            date: Data para sincronizar (padrão: HOJE para KM em tempo real)

        Returns:
            Dict com estatísticas: {'success': X, 'failed': Y, 'total': Z}
        """
        try:
            # Se não especificou data, usar HOJE
            if date is None:
                date = datetime.now()

            logger.info(f"🚀 Iniciando sincronização de todos os veículos para {date.strftime('%Y-%m-%d')}")

            # Buscar lista de veículos via API PHP
            vehicles_url = f"{self.PHP_API_BASE_URL}/veiculos-api.php?action=list"
            response = self.session.get(vehicles_url, timeout=10)
            response.raise_for_status()

            vehicles_data = response.json()

            if not vehicles_data.get('success'):
                logger.error("❌ Erro ao buscar lista de veículos")
                return {'success': 0, 'failed': 0, 'total': 0}

            vehicles = vehicles_data.get('vehicles', [])
            logger.info(f"📋 Encontrados {len(vehicles)} veículos para sincronizar")

            # Estatísticas
            stats = {'success': 0, 'failed': 0, 'total': len(vehicles)}

            # Sincronizar cada veículo
            for vehicle in vehicles:
                plate = vehicle.get('LicensePlate')

                if not plate:
                    logger.warning(f"⚠️ Veículo sem placa: {vehicle}")
                    stats['failed'] += 1
                    continue

                # Sincronizar
                success = self.sync_vehicle_mileage(plate, date)

                if success:
                    stats['success'] += 1
                else:
                    stats['failed'] += 1

            logger.info(f"✅ Sincronização concluída: {stats['success']} sucesso, {stats['failed']} falhas")

            return stats

        except Exception as e:
            logger.error(f"❌ Erro ao sincronizar todos os veículos: {e}")
            return {'success': 0, 'failed': 0, 'total': 0}


# ============================================================
# FUNÇÕES DE UTILIDADE
# ============================================================

def test_single_vehicle(plate: str):
    """Testa sincronização de um veículo específico"""
    service = MileageService()
    success = service.sync_vehicle_mileage(plate)
    if success:
        print(f"✅ Teste bem-sucedido para {plate}")
    else:
        print(f"❌ Teste falhou para {plate}")


def test_api_connection():
    """Testa conexão com API Ituran"""
    service = MileageService()
    # Testar com placa conhecida
    result = service.get_vehicle_odometer('RTS9B92', datetime.now())
    if result:
        print(f"✅ API Ituran funcionando: {result}")
    else:
        print("❌ Erro ao conectar com API Ituran")


def test_current_odometer(plate: str):
    """Testa busca de odômetro atual em tempo real"""
    service = MileageService()
    result = service.get_current_odometer(plate)
    if result:
        print(f"✅ Odômetro atual de {plate}: {result}")
    else:
        print(f"❌ Não foi possível obter odômetro atual de {plate}")


if __name__ == '__main__':
    # Teste rápido
    print("🧪 Testando serviço de quilometragem...\n")
    test_api_connection()
    print("\n" + "="*60 + "\n")
    test_current_odometer('RBA2F98')
    print("\n" + "="*60 + "\n")
    test_single_vehicle('RBA2F98')
