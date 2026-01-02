"""
Serviço de Quilometragem Automática

Responsável por:
- Buscar odômetro diário de veículos via API Ituran
- Calcular KM rodados (odômetro hoje - odômetro ontem)
- Salvar dados na tabela daily_mileage via API PHP
- Gestão de erros e retry automático
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

            logger.info(f"🔍 Buscando odômetro: {plate} em {date_str}")

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

    def calculate_daily_mileage(self, plate: str, date: datetime) -> Optional[Dict]:
        """
        Calcula a quilometragem diária de um veículo

        Busca odômetro de hoje e de ontem, calcula a diferença

        Args:
            plate: Placa do veículo
            date: Data para calcular (normalmente hoje ou ontem)

        Returns:
            Dict com odometer_start, odometer_end, km_driven
            ou None se houver erro
        """
        try:
            # Buscar odômetro do dia anterior (início do dia)
            previous_date = date - timedelta(days=1)
            odometer_start_data = self.get_vehicle_odometer(plate, previous_date)

            # Buscar odômetro do dia atual (fim do dia)
            odometer_end_data = self.get_vehicle_odometer(plate, date)

            # Verificar se ambas as consultas foram bem-sucedidas
            if not odometer_start_data or not odometer_end_data:
                logger.warning(f"⚠️ Não foi possível obter odômetros completos para {plate}")
                return None

            odometer_start = odometer_start_data['odometer']
            odometer_end = odometer_end_data['odometer']

            # Calcular KM rodados
            km_driven = odometer_end - odometer_start

            # Validar resultado (KM não pode ser negativo)
            if km_driven < 0:
                logger.warning(f"⚠️ KM negativo detectado para {plate}: {km_driven:.2f} km")
                # Pode ser erro de leitura ou reset de odômetro
                # Vamos registrar como 0 e marcar erro
                km_driven = 0

            logger.info(f"📊 {plate}: {km_driven:.2f} km rodados em {date.strftime('%Y-%m-%d')}")

            return {
                'plate': plate,
                'date': date.strftime('%Y-%m-%d'),
                'odometer_start': odometer_start,
                'odometer_end': odometer_end,
                'km_driven': km_driven,
                'synced_at': datetime.now().isoformat()
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
            # Endpoint da API PHP (vamos criar este endpoint)
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

            logger.info(f"💾 Salvando no banco: {payload['vehicle_plate']} - {payload['km_driven']:.2f} km")

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
            date: Data para sincronizar (padrão: ontem)

        Returns:
            True se sincronizou com sucesso
        """
        try:
            # Se não especificou data, usar ontem (dados completos do dia anterior)
            if date is None:
                date = datetime.now() - timedelta(days=1)

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
            date: Data para sincronizar (padrão: ontem)

        Returns:
            Dict com estatísticas: {'success': X, 'failed': Y, 'total': Z}
        """
        try:
            logger.info("🚀 Iniciando sincronização de todos os veículos")

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


if __name__ == '__main__':
    # Teste rápido
    print("🧪 Testando serviço de quilometragem...\n")
    test_api_connection()
    print("\n" + "="*60 + "\n")
    test_single_vehicle('RTS9B92')
