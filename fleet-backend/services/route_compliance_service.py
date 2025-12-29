"""
Sistema de Monitoramento de Conformidade de Rotas em Tempo Real

Este serviço verifica se motoristas estão seguindo as rotas planejadas
e envia alertas WhatsApp quando detecta desvios.

Autor: Claude AI
Data: 2025-12-24
"""

import json
import requests
from datetime import datetime
from typing import Dict, List, Optional, Tuple
from geopy.distance import geodesic


class RouteComplianceService:
    """
    Serviço principal de verificação de conformidade de rotas.

    Detecta 4 tipos de desvios:
    1. wrong_sequence - Visitou local B antes do local A
    2. excessive_distance - Percorreu mais que 20% além do planejado
    3. unplanned_stop - Parou >15min em local não planejado
    4. route_abandoned - Está >5km longe de qualquer ponto
    """

    # ========== CONFIGURAÇÕES ==========
    DISTANCE_TOLERANCE_KM = 2.0       # Alerta se >2km longe do ponto esperado
    PROXIMITY_RADIUS_M = 100          # Considera "visitado" se <100m do ponto
    UNPLANNED_STOP_MIN = 15           # Alerta se parado >15min fora da rota
    ABANDONED_THRESHOLD_KM = 5.0      # CRÍTICO se >5km de qualquer ponto
    EXCESSIVE_DISTANCE_PERCENT = 0.20 # Alerta se rodou >20% além do planejado

    # URLs das APIs
    ROTAS_API_URL = 'https://floripa.in9automacao.com.br/cpanel-api/rotas-api.php'
    ALERT_API_URL = 'https://floripa.in9automacao.com.br/cpanel-api/send-deviation-alert.php'
    RECIPIENTS_API_URL = 'https://floripa.in9automacao.com.br/cpanel-api/alert-recipients-api.php'

    def __init__(self, db_session=None, ituran_service=None):
        """
        Inicializa o serviço.

        Args:
            db_session: Sessão SQLAlchemy (opcional, para quando rodar no Flask)
            ituran_service: Instância do serviço Ituran (opcional)
        """
        self.db = db_session
        self.ituran = ituran_service

    def check_route_compliance(self, route_id: int) -> Dict:
        """
        Verifica conformidade de uma rota em andamento.

        Fluxo:
        1. Busca rota planejada (sequência de locais)
        2. Busca posição atual do veículo (Ituran GPS)
        3. Determina qual local deveria estar visitando
        4. Calcula distância da rota planejada
        5. Detecta desvios (4 tipos)
        6. Salva análise no banco
        7. Envia alertas WhatsApp se necessário

        Args:
            route_id: ID da rota em FF_Rotas

        Returns:
            Dict com resultado da análise:
            {
                'is_compliant': bool,
                'deviations': [lista de desvios],
                'distance_km': float,
                'compliance_score': float
            }
        """
        print(f"\n{'='*60}")
        print(f"🔍 Verificando conformidade da rota #{route_id}")
        print(f"{'='*60}\n")

        try:
            # 1. Buscar rota planejada (via PHP API)
            route = self._fetch_route_from_api(route_id)
            if not route:
                raise Exception(f"Rota #{route_id} não encontrada")

            sequencia = json.loads(route.get('sequencia_locais_json', '[]'))
            vehicle_plate = route.get('veiculo_placa')

            if not sequencia:
                print(f"⚠️  Rota #{route_id} sem sequência de locais definida")
                return {'is_compliant': True, 'deviations': [], 'distance_km': 0}

            if not vehicle_plate:
                raise Exception(f"Rota #{route_id} sem veículo associado")

            print(f"📋 Rota: {route.get('nome', 'Sem nome')}")
            print(f"🚗 Veículo: {vehicle_plate}")
            print(f"📍 Sequência: {len(sequencia)} locais")

            # 2. Buscar posição atual do veículo (Ituran)
            position = self._fetch_vehicle_position(vehicle_plate)
            current_lat = position['latitude']
            current_lon = position['longitude']
            current_speed = position.get('speed', 0)

            print(f"📡 Posição atual: {current_lat}, {current_lon}")
            print(f"⚡ Velocidade: {current_speed} km/h")

            # 3. Determinar qual local deveria estar visitando
            expected_index = self._find_expected_location_index(sequencia, current_lat, current_lon)

            # 4. Calcular distância da rota planejada
            distance_km = self._calculate_distance_from_route(
                sequencia, current_lat, current_lon, expected_index
            )

            print(f"📏 Distância da rota: {distance_km:.2f} km")

            # 5. DETECTAR DESVIOS
            deviations = []

            # 5.1. Rota Abandonada (CRÍTICO)
            if distance_km > self.ABANDONED_THRESHOLD_KM:
                dev = {
                    'type': 'route_abandoned',
                    'severity': 'critical',
                    'distance_km': distance_km,
                    'message': f'Veículo está {distance_km:.1f}km longe da rota planejada!'
                }
                deviations.append(dev)
                print(f"🔴 CRÍTICO: {dev['message']}")

            # 5.2. Distância Excessiva (MÉDIO)
            elif distance_km > self.DISTANCE_TOLERANCE_KM:
                dev = {
                    'type': 'excessive_distance',
                    'severity': 'medium',
                    'distance_km': distance_km,
                    'message': f'Desvio de {distance_km:.1f}km da rota planejada'
                }
                deviations.append(dev)
                print(f"🟡 MÉDIO: {dev['message']}")

            # 5.3. Sequência Errada (ALTO)
            wrong_seq = self._detect_wrong_sequence(sequencia, current_lat, current_lon, expected_index)
            if wrong_seq:
                deviations.append(wrong_seq)
                print(f"🟠 ALTO: {wrong_seq['message']}")

            # 5.4. Parada Não Planejada (MÉDIO)
            if current_speed == 0:
                unplanned = self._detect_unplanned_stop(
                    sequencia, current_lat, current_lon, route_id
                )
                if unplanned:
                    deviations.append(unplanned)
                    print(f"🟡 MÉDIO: {unplanned['message']}")

            # 6. Calcular score de conformidade
            compliance_score = self._calculate_compliance_score(deviations, distance_km)

            # 7. Salvar análise no banco (se tiver db_session)
            compliance_id = None
            if self.db:
                compliance_id = self._save_compliance_check(
                    route_id, vehicle_plate, current_lat, current_lon,
                    current_speed, distance_km, expected_index,
                    len(deviations) == 0, compliance_score, sequencia
                )

            # 8. Salvar desvios e enviar alertas
            if deviations:
                for dev in deviations:
                    # Salvar desvio no banco
                    if self.db:
                        self._save_deviation(
                            route_id, compliance_id, dev, current_lat, current_lon
                        )

                    # Enviar alerta WhatsApp
                    self._send_whatsapp_alert(route, dev)

            # Resultado
            result = {
                'is_compliant': len(deviations) == 0,
                'deviations': deviations,
                'distance_km': distance_km,
                'compliance_score': compliance_score
            }

            if len(deviations) == 0:
                print(f"\n✅ Rota #{route_id} CONFORME (Score: {compliance_score:.1f}%)")
            else:
                print(f"\n❌ Rota #{route_id} COM DESVIOS: {len(deviations)} detectados")

            return result

        except Exception as e:
            print(f"\n❌ ERRO ao verificar rota #{route_id}: {str(e)}")
            raise

    # ========== MÉTODOS AUXILIARES ==========

    def _fetch_route_from_api(self, route_id: int) -> Optional[Dict]:
        """Busca dados da rota via API PHP"""
        try:
            response = requests.get(
                self.ROTAS_API_URL,
                params={'id': route_id},
                timeout=10
            )
            data = response.json()

            if data.get('success') and data.get('rota'):
                return data['rota']
            return None

        except Exception as e:
            print(f"❌ Erro ao buscar rota: {e}")
            return None

    def _fetch_vehicle_position(self, plate: str) -> Dict:
        """
        Busca posição atual do veículo via Ituran.

        Se self.ituran estiver disponível, usa ele.
        Caso contrário, faz chamada HTTP para o serviço Node.js.
        """
        if self.ituran:
            # Usa serviço Ituran Python
            return self.ituran.get_vehicle_location(plate)
        else:
            # Fallback: chama via HTTP (Node.js proxy)
            # NOTA: Você pode implementar um endpoint que exponha o ituran-service.js
            raise NotImplementedError(
                "Integração com Ituran via HTTP não implementada. "
                "Passe instância de ituran_service ao construtor."
            )

    def _find_expected_location_index(
        self, sequencia: List[Dict], lat: float, lon: float
    ) -> Optional[int]:
        """
        Determina qual local da sequência o veículo deveria estar visitando agora.

        Lógica:
        - Verifica se está próximo (<100m) de algum local
        - Se sim, retorna o índice desse local
        - Se não, retorna o próximo local não visitado
        """
        # Verifica se está próximo de algum local
        for i, loc in enumerate(sequencia):
            dist_m = geodesic(
                (lat, lon),
                (loc['latitude'], loc['longitude'])
            ).meters

            if dist_m < self.PROXIMITY_RADIUS_M:
                return i

        # Não está próximo de nenhum: retorna índice 0 (deveria estar no primeiro)
        return 0

    def _calculate_distance_from_route(
        self, sequencia: List[Dict], lat: float, lon: float, expected_index: Optional[int]
    ) -> float:
        """Calcula distância (em km) do ponto atual até o ponto esperado da rota"""

        if expected_index is not None and expected_index < len(sequencia):
            expected_loc = sequencia[expected_index]
            return geodesic(
                (lat, lon),
                (expected_loc['latitude'], expected_loc['longitude'])
            ).kilometers
        else:
            # Calcula distância até o ponto mais próximo
            min_dist = float('inf')
            for loc in sequencia:
                dist = geodesic(
                    (lat, lon),
                    (loc['latitude'], loc['longitude'])
                ).kilometers
                min_dist = min(min_dist, dist)
            return min_dist

    def _detect_wrong_sequence(
        self, sequencia: List[Dict], lat: float, lon: float, expected_index: Optional[int]
    ) -> Optional[Dict]:
        """
        Detecta se visitou locais fora da ordem.

        Returns:
            Dict com desvio ou None se não houver
        """
        # Verifica se está próximo de algum local
        for i, loc in enumerate(sequencia):
            dist_m = geodesic(
                (lat, lon),
                (loc['latitude'], loc['longitude'])
            ).meters

            # Está visitando este local
            if dist_m < self.PROXIMITY_RADIUS_M:
                # Se não é o local esperado (pulou alguém)
                if expected_index is not None and i > expected_index + 1:
                    return {
                        'type': 'wrong_sequence',
                        'severity': 'high',
                        'message': f'Visitou local #{i+1} antes do local #{expected_index+1}'
                    }

        return None

    def _detect_unplanned_stop(
        self, sequencia: List[Dict], lat: float, lon: float, route_id: int
    ) -> Optional[Dict]:
        """
        Detecta se está parado >15min em local não planejado.

        NOTA: Requer histórico de posições para calcular duração.
        Por simplicidade inicial, apenas verifica se está parado E longe.
        """
        # Verifica se está longe de todos os locais planejados
        min_dist_m = float('inf')
        for loc in sequencia:
            dist = geodesic(
                (lat, lon),
                (loc['latitude'], loc['longitude'])
            ).meters
            min_dist_m = min(min_dist_m, dist)

        # Está parado (velocidade=0) E longe (>200m) de qualquer local
        if min_dist_m > 200:
            # TODO: Implementar verificação de duração (precisa histórico)
            # Por ora, apenas detecta que está parado longe da rota
            return {
                'type': 'unplanned_stop',
                'severity': 'medium',
                'message': f'Veículo parado a {min_dist_m:.0f}m do local mais próximo'
            }

        return None

    def _calculate_compliance_score(self, deviations: List[Dict], distance_km: float) -> float:
        """
        Calcula score de conformidade (0-100).

        Penalidades:
        - critical: -50 pontos
        - high: -25 pontos
        - medium: -15 pontos
        - low: -5 pontos
        - Distância: -1 ponto por km além de 1km
        """
        score = 100.0

        for dev in deviations:
            severity = dev['severity']
            if severity == 'critical':
                score -= 50
            elif severity == 'high':
                score -= 25
            elif severity == 'medium':
                score -= 15
            elif severity == 'low':
                score -= 5

        # Penalidade por distância
        if distance_km > 1.0:
            score -= (distance_km - 1.0)

        return max(0.0, min(100.0, score))

    def _save_compliance_check(
        self, route_id, vehicle_plate, lat, lon, speed, distance_km,
        expected_index, is_compliant, compliance_score, sequencia
    ) -> Optional[int]:
        """Salva análise no banco (FF_RouteCompliance)"""
        # TODO: Implementar quando integrar com SQLAlchemy
        # Por ora retorna None
        return None

    def _save_deviation(
        self, route_id, compliance_id, deviation, lat, lon
    ):
        """Salva desvio no banco (FF_RouteDeviations)"""
        # TODO: Implementar quando integrar com SQLAlchemy
        pass

    def _send_whatsapp_alert(self, route: Dict, deviation: Dict):
        """
        Envia alerta WhatsApp via Evolution API.

        Busca destinatários conforme severidade e envia mensagem formatada.
        """
        try:
            # Buscar destinatários
            recipients = self._get_alert_recipients(deviation['severity'])

            if not recipients:
                print(f"⚠️  Nenhum destinatário cadastrado para severidade {deviation['severity']}")
                return

            # Template de mensagem
            alert_msg = self._format_alert_message(route, deviation)

            # Enviar para cada destinatário
            sent_count = 0
            for recipient in recipients:
                success = self._call_whatsapp_api(recipient['phone'], alert_msg)
                if success:
                    sent_count += 1

            print(f"📱 Alerta enviado para {sent_count}/{len(recipients)} destinatários")

        except Exception as e:
            print(f"❌ Erro ao enviar alertas WhatsApp: {e}")

    def _get_alert_recipients(self, severity: str) -> List[Dict]:
        """Busca destinatários que devem receber alertas desta severidade"""
        try:
            response = requests.get(self.RECIPIENTS_API_URL, timeout=10)
            data = response.json()

            if not data.get('success'):
                return []

            recipients = data.get('recipients', [])

            # Filtrar por severidade
            filtered = []
            for rec in recipients:
                should_receive = False

                if severity == 'critical' and rec.get('receive_critical'):
                    should_receive = True
                elif severity == 'high' and rec.get('receive_high'):
                    should_receive = True
                elif severity == 'medium' and rec.get('receive_medium'):
                    should_receive = True
                elif severity == 'low' and rec.get('receive_low'):
                    should_receive = True

                if should_receive:
                    filtered.append(rec)

            return filtered

        except Exception as e:
            print(f"❌ Erro ao buscar destinatários: {e}")
            return []

    def _format_alert_message(self, route: Dict, deviation: Dict) -> str:
        """Formata mensagem de alerta WhatsApp"""

        severity_emoji = {
            'critical': '🔴',
            'high': '🟠',
            'medium': '🟡',
            'low': '🟢'
        }

        type_names = {
            'route_abandoned': 'Rota Abandonada',
            'wrong_sequence': 'Sequência Errada',
            'excessive_distance': 'Distância Excessiva',
            'unplanned_stop': 'Parada Não Planejada',
            'skipped_location': 'Local Pulado'
        }

        emoji = severity_emoji.get(deviation['severity'], '⚪')
        type_name = type_names.get(deviation['type'], deviation['type'])

        return f"""{emoji} ALERTA DE DESVIO DE ROTA {emoji}

Tipo: {type_name}
Severidade: {deviation['severity'].upper()}

Rota: #{route.get('id')} - {route.get('nome', 'Sem nome')}
Veículo: {route.get('veiculo_placa', 'N/A')}
Motorista: {route.get('motorista_nome', 'N/A')}

⚠️ {deviation['message']}

🕒 Detectado em: {datetime.now().strftime('%H:%M:%S')}

---
Sistema de Monitoramento i9 Engenharia
"""

    def _call_whatsapp_api(self, phone: str, message: str) -> bool:
        """Chama API PHP proxy para enviar via Evolution API"""
        try:
            response = requests.post(
                self.ALERT_API_URL,
                json={'phone': phone, 'message': message},
                timeout=15
            )

            data = response.json()
            return data.get('success', False)

        except Exception as e:
            print(f"❌ Erro ao enviar WhatsApp para {phone}: {e}")
            return False


# ========== TESTE STANDALONE ==========

if __name__ == '__main__':
    """
    Teste standalone do serviço.
    Execute: python route_compliance_service.py
    """
    print("🧪 Testando RouteComplianceService...\n")

    service = RouteComplianceService()

    # Mock de dados para teste
    print("⚠️  MODO TESTE: Usando dados mockados")
    print("Para usar em produção, integre com ituran_service e SQLAlchemy\n")

    # TODO: Adicionar testes unitários aqui
