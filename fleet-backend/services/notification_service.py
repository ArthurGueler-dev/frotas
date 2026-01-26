"""
Serviço de Notificações para Alertas de Manutenção

Este serviço é responsável por:
1. Enviar notificações via WhatsApp usando Evolution API
2. Gerenciar destinatários de alertas
3. Formatar mensagens de alerta
4. Registrar histórico de notificações

@author Claude
@version 1.0
@date 2026-01-21
"""

import logging
import requests
from datetime import datetime
from typing import Dict, List, Optional, Any

logger = logging.getLogger(__name__)


class NotificationService:
    """Serviço para envio de notificações de alertas de manutenção"""

    def __init__(self):
        # URL base das APIs PHP no cPanel
        self.base_url = "https://floripa.in9automacao.com.br"
        self.whatsapp_api_url = f"{self.base_url}/enviar-alertas-whatsapp.php"
        self.recipients_api_url = f"{self.base_url}/alert-recipients-api.php"
        self.alerts_api_url = f"{self.base_url}/avisos-manutencao-api.php"
        self.timeout = 30

    def send_whatsapp_message(self, telefone: str, mensagem: str, tipo: str = "custom") -> Dict[str, Any]:
        """
        Envia mensagem via WhatsApp usando API PHP

        Args:
            telefone: Número de telefone (com DDD)
            mensagem: Texto da mensagem
            tipo: Tipo de mensagem (custom, maintenance_alert)

        Returns:
            Dict com resultado do envio
        """
        logger.info(f"📤 Enviando WhatsApp para {telefone[:4]}***")

        try:
            response = requests.post(
                self.whatsapp_api_url,
                json={
                    "telefone": telefone,
                    "mensagem": mensagem,
                    "tipo": tipo
                },
                timeout=self.timeout
            )

            if response.status_code == 200:
                result = response.json()
                if result.get("success"):
                    logger.info(f"✅ Mensagem enviada com sucesso")
                    return {
                        "success": True,
                        "telefone": telefone,
                        "timestamp": datetime.utcnow().isoformat()
                    }
                else:
                    logger.error(f"❌ Erro no envio: {result.get('error')}")
                    return {
                        "success": False,
                        "error": result.get("error", "Erro desconhecido"),
                        "telefone": telefone
                    }
            else:
                logger.error(f"❌ HTTP {response.status_code}")
                return {
                    "success": False,
                    "error": f"HTTP {response.status_code}",
                    "telefone": telefone
                }

        except requests.Timeout:
            logger.error("❌ Timeout no envio")
            return {"success": False, "error": "Timeout", "telefone": telefone}
        except Exception as e:
            logger.error(f"❌ Exceção no envio: {e}")
            return {"success": False, "error": str(e), "telefone": telefone}

    def send_critical_alerts_summary(self) -> Dict[str, Any]:
        """
        Envia resumo de alertas críticos para todos os destinatários configurados

        Este método:
        1. Chama a API PHP com action=critical
        2. A API busca alertas críticos/altos
        3. A API busca destinatários configurados
        4. A API envia mensagem formatada para cada destinatário
        5. A API marca alertas como notificados

        Returns:
            Dict com estatísticas de envio
        """
        logger.info("🚨 Enviando resumo de alertas críticos...")

        try:
            response = requests.post(
                self.whatsapp_api_url,
                params={"action": "critical"},
                json={},  # Body vazio, a API busca tudo internamente
                timeout=60  # Timeout maior para processamento
            )

            if response.status_code == 200:
                result = response.json()
                if result.get("success"):
                    data = result.get("data", {})
                    logger.info(
                        f"✅ Resumo enviado: {data.get('enviados', 0)}/{data.get('destinatarios', 0)} destinatários, "
                        f"{data.get('total_alertas', 0)} alertas"
                    )
                    return {
                        "success": True,
                        "total_alertas": data.get("total_alertas", 0),
                        "destinatarios": data.get("destinatarios", 0),
                        "enviados": data.get("enviados", 0),
                        "erros": data.get("erros", []),
                        "timestamp": datetime.utcnow().isoformat()
                    }
                else:
                    logger.warning(f"⚠️ API retornou: {result.get('error')}")
                    return {
                        "success": False,
                        "error": result.get("error", "Erro desconhecido")
                    }
            else:
                logger.error(f"❌ HTTP {response.status_code}")
                return {
                    "success": False,
                    "error": f"HTTP {response.status_code}"
                }

        except requests.Timeout:
            logger.error("❌ Timeout no envio de resumo")
            return {"success": False, "error": "Timeout"}
        except Exception as e:
            logger.error(f"❌ Exceção no envio de resumo: {e}")
            return {"success": False, "error": str(e)}

    def send_individual_alert(self, alerta_id: int, telefone: str) -> Dict[str, Any]:
        """
        Envia alerta individual para um telefone específico

        Args:
            alerta_id: ID do alerta em avisos_manutencao
            telefone: Número de telefone do destinatário

        Returns:
            Dict com resultado do envio
        """
        logger.info(f"📤 Enviando alerta #{alerta_id} para {telefone[:4]}***")

        try:
            response = requests.post(
                self.whatsapp_api_url,
                json={
                    "telefone": telefone,
                    "mensagem": "",  # Será preenchida pela API
                    "tipo": "maintenance_alert",
                    "alerta_id": alerta_id
                },
                timeout=self.timeout
            )

            if response.status_code == 200:
                result = response.json()
                return {
                    "success": result.get("success", False),
                    "error": result.get("error"),
                    "alerta_id": alerta_id,
                    "telefone": telefone
                }
            else:
                return {
                    "success": False,
                    "error": f"HTTP {response.status_code}",
                    "alerta_id": alerta_id,
                    "telefone": telefone
                }

        except Exception as e:
            logger.error(f"❌ Erro ao enviar alerta individual: {e}")
            return {
                "success": False,
                "error": str(e),
                "alerta_id": alerta_id,
                "telefone": telefone
            }

    def get_recipients(self, alert_type: str = "maintenance") -> List[Dict[str, Any]]:
        """
        Busca destinatários configurados para um tipo de alerta

        Args:
            alert_type: Tipo de alerta (maintenance, all, route, etc.)

        Returns:
            Lista de destinatários
        """
        try:
            response = requests.get(
                self.recipients_api_url,
                params={"alert_type": alert_type, "is_active": 1},
                timeout=self.timeout
            )

            if response.status_code == 200:
                result = response.json()
                if result.get("success"):
                    return result.get("data", [])

            return []

        except Exception as e:
            logger.error(f"Erro ao buscar destinatários: {e}")
            return []

    def mark_alerts_as_notified(self, alert_ids: List[int]) -> bool:
        """
        Marca múltiplos alertas como notificados usando endpoint batch

        Args:
            alert_ids: Lista de IDs de alertas

        Returns:
            True se sucesso, False caso contrário
        """
        if not alert_ids:
            return True

        logger.info(f"📝 Marcando {len(alert_ids)} alertas como notificados")

        try:
            response = requests.put(
                self.alerts_api_url,
                params={"action": "mark-notified-batch"},
                json={"ids": alert_ids},
                timeout=self.timeout
            )

            if response.status_code == 200:
                result = response.json()
                if result.get("success"):
                    data = result.get("data", {})
                    logger.info(f"✅ {data.get('atualizados', 0)} alertas marcados como notificados")
                    return True
                else:
                    logger.error(f"❌ Erro ao marcar: {result.get('error')}")
                    return False
            else:
                logger.error(f"❌ HTTP {response.status_code}")
                return False

        except Exception as e:
            logger.error(f"❌ Exceção ao marcar alertas: {e}")
            return False

    def format_alert_message(self, alerta: Dict[str, Any]) -> str:
        """
        Formata mensagem de alerta individual para WhatsApp

        Args:
            alerta: Dados do alerta

        Returns:
            Mensagem formatada
        """
        placa = alerta.get("placa", alerta.get("placa_veiculo", "N/A"))
        modelo = alerta.get("modelo", "")
        descricao = alerta.get("plano_nome", alerta.get("mensagem", "Manutenção"))
        nivel = alerta.get("prioridade", alerta.get("nivel_alerta", ""))
        km_restantes = alerta.get("km_restantes", 0)
        km_atual = alerta.get("km_atual", alerta.get("km_atual_veiculo", 0))

        # Emoji baseado no nível
        emoji = {
            "Critico": "🔴",
            "Alto": "🟠",
            "Medio": "🟡",
            "Baixo": "🔵"
        }.get(nivel, "⚪")

        # Status KM
        if km_restantes <= 0:
            status_km = f"*VENCIDO* há {abs(km_restantes):,} km"
        else:
            status_km = f"Faltam {km_restantes:,} km"

        message = f"""
{emoji} *ALERTA DE MANUTENÇÃO*

*Veículo:* {placa} - {modelo}
*Serviço:* {descricao}
*Status:* {status_km}
*KM Atual:* {km_atual:,} km
*Nível:* {nivel}

_FleetFlow - Gestão de Frotas_
        """.strip()

        return message

    def format_summary_message(self, alertas: List[Dict[str, Any]]) -> str:
        """
        Formata mensagem resumida com múltiplos alertas

        Args:
            alertas: Lista de alertas

        Returns:
            Mensagem resumida formatada
        """
        if not alertas:
            return "✅ *Nenhum alerta crítico no momento.*\n\n_FleetFlow - Gestão de Frotas_"

        # Agrupar por veículo
        grupos = {}
        for alerta in alertas:
            placa = alerta.get("placa", alerta.get("placa_veiculo", "UNKNOWN"))
            if placa not in grupos:
                grupos[placa] = []
            grupos[placa].append(alerta)

        # Contar totais
        total_criticos = sum(
            1 for a in alertas
            if a.get("nivel_alerta") == "Critico" or a.get("prioridade") == "Critico"
        )
        total_vencidos = sum(
            1 for a in alertas
            if a.get("km_restantes", 0) <= 0
        )

        lines = [
            "🚨 *RESUMO DE ALERTAS DE MANUTENÇÃO*",
            f"📅 Data: {datetime.now().strftime('%d/%m/%Y %H:%M')}",
            "",
            f"⚠️ Total de alertas: {len(alertas)}",
            f"🔴 Críticos: {total_criticos}",
            f"❌ Vencidos: {total_vencidos}",
            "",
            "*Por Veículo:*"
        ]

        for placa, alertas_veiculo in sorted(grupos.items()):
            modelo = alertas_veiculo[0].get("modelo", "")
            lines.append(f"\n📋 *{placa}* - {modelo}")

            for alerta in alertas_veiculo[:3]:  # Máximo 3 por veículo
                descricao = alerta.get("plano_nome", "Manutenção")[:30]
                km_rest = alerta.get("km_restantes", 0)

                if km_rest <= 0:
                    lines.append(f"   🔴 {descricao}: VENCIDO ({abs(km_rest):,}km)")
                else:
                    lines.append(f"   🟠 {descricao}: {km_rest:,}km restantes")

            if len(alertas_veiculo) > 3:
                lines.append(f"   ... e mais {len(alertas_veiculo) - 3} alerta(s)")

        lines.extend([
            "",
            "_Acesse o sistema para mais detalhes._",
            "_FleetFlow - Gestão de Frotas_"
        ])

        return "\n".join(lines)

    def test_connection(self) -> Dict[str, Any]:
        """
        Testa conexão com as APIs

        Returns:
            Dict com status de cada API
        """
        results = {}

        # Testar API de WhatsApp
        try:
            response = requests.get(
                self.whatsapp_api_url,
                timeout=10
            )
            results["whatsapp_api"] = {
                "status": "OK" if response.status_code in [200, 405] else "ERROR",
                "http_code": response.status_code
            }
        except Exception as e:
            results["whatsapp_api"] = {"status": "ERROR", "error": str(e)}

        # Testar API de destinatários
        try:
            response = requests.get(
                self.recipients_api_url,
                timeout=10
            )
            results["recipients_api"] = {
                "status": "OK" if response.status_code == 200 else "ERROR",
                "http_code": response.status_code
            }
        except Exception as e:
            results["recipients_api"] = {"status": "ERROR", "error": str(e)}

        # Testar API de alertas
        try:
            response = requests.get(
                self.alerts_api_url,
                params={"limit": 1},
                timeout=10
            )
            results["alerts_api"] = {
                "status": "OK" if response.status_code == 200 else "ERROR",
                "http_code": response.status_code
            }
        except Exception as e:
            results["alerts_api"] = {"status": "ERROR", "error": str(e)}

        return results


# Instância singleton do serviço
notification_service = NotificationService()
