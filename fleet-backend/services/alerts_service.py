"""
Serviço de Alertas de Manutenção Preventiva

Este serviço é responsável por:
1. Chamar a API PHP para gerar alertas de manutenção
2. Buscar alertas pendentes de notificação
3. Integrar com o serviço de notificações

@author Claude
@version 1.0
@date 2026-01-21
"""

import logging
import requests
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Any

logger = logging.getLogger(__name__)


class AlertsService:
    """Serviço para gerenciamento de alertas de manutenção preventiva"""

    def __init__(self):
        # URL base das APIs PHP no cPanel
        self.base_url = "https://floripa.in9automacao.com.br"
        self.timeout = 120  # 2 minutos de timeout para geração de alertas

    def generate_all_alerts(self, placa: Optional[str] = None) -> Dict[str, Any]:
        """
        Gera alertas de manutenção para todos os veículos ou um específico

        Args:
            placa: Placa do veículo (opcional). Se None, processa todos.

        Returns:
            Dict com estatísticas de geração
        """
        logger.info(f"🔔 Iniciando geração de alertas" + (f" para {placa}" if placa else " para todos os veículos"))

        try:
            url = f"{self.base_url}/gerar-alertas-manutencao.php"
            params = {"placa": placa} if placa else {}

            response = requests.post(
                url,
                params=params,
                timeout=self.timeout,
                headers={"Content-Type": "application/json"}
            )

            if response.status_code != 200:
                logger.error(f"Erro na API de alertas: HTTP {response.status_code}")
                return {
                    "success": False,
                    "error": f"HTTP {response.status_code}",
                    "alertas_gerados": 0,
                    "alertas_atualizados": 0
                }

            data = response.json()

            if data.get("success"):
                result = data.get("data", {})
                logger.info(
                    f"✅ Alertas gerados: {result.get('alertas_gerados', 0)}, "
                    f"Atualizados: {result.get('alertas_atualizados', 0)}, "
                    f"Veículos: {result.get('total_veiculos', 0)}"
                )
                return {
                    "success": True,
                    "alertas_gerados": result.get("alertas_gerados", 0),
                    "alertas_atualizados": result.get("alertas_atualizados", 0),
                    "total_veiculos": result.get("total_veiculos", 0),
                    "tempo_execucao": result.get("tempo_execucao", "N/A"),
                    "erros": result.get("erros", [])
                }
            else:
                logger.error(f"Erro na geração de alertas: {data.get('error')}")
                return {
                    "success": False,
                    "error": data.get("error", "Erro desconhecido"),
                    "alertas_gerados": 0,
                    "alertas_atualizados": 0
                }

        except requests.Timeout:
            logger.error("Timeout na geração de alertas")
            return {
                "success": False,
                "error": "Timeout na requisição",
                "alertas_gerados": 0,
                "alertas_atualizados": 0
            }
        except Exception as e:
            logger.error(f"Exceção na geração de alertas: {e}")
            return {
                "success": False,
                "error": str(e),
                "alertas_gerados": 0,
                "alertas_atualizados": 0
            }

    def get_alert_stats(self) -> Dict[str, Any]:
        """
        Busca estatísticas de alertas

        Returns:
            Dict com contadores de alertas por nível/status
        """
        try:
            url = f"{self.base_url}/gerar-alertas-manutencao.php"
            response = requests.get(
                url,
                params={"action": "stats"},
                timeout=30
            )

            if response.status_code == 200:
                data = response.json()
                if data.get("success"):
                    return data.get("data", {})

            return {
                "total": 0,
                "vencidos": 0,
                "criticos": 0,
                "altos": 0,
                "medios": 0,
                "baixos": 0
            }

        except Exception as e:
            logger.error(f"Erro ao buscar estatísticas de alertas: {e}")
            return {
                "total": 0,
                "vencidos": 0,
                "criticos": 0,
                "altos": 0,
                "medios": 0,
                "baixos": 0
            }

    def get_pending_notifications(self, nivel_minimo: str = "Alto") -> List[Dict[str, Any]]:
        """
        Busca alertas pendentes de notificação

        Args:
            nivel_minimo: Nível mínimo de alerta para notificar ("Critico", "Alto", "Medio", "Baixo")

        Returns:
            Lista de alertas para notificação
        """
        try:
            url = f"{self.base_url}/avisos-manutencao-api.php"

            # Mapear níveis para filtro
            niveis_filtro = []
            niveis_ordem = ["Critico", "Alto", "Medio", "Baixo"]
            idx = niveis_ordem.index(nivel_minimo) if nivel_minimo in niveis_ordem else 1

            for i in range(idx + 1):
                niveis_filtro.append(niveis_ordem[i])

            alertas_pendentes = []

            for nivel in niveis_filtro:
                response = requests.get(
                    url,
                    params={
                        "status": "Ativo",
                        "nivel_alerta": nivel,
                        "limit": 100
                    },
                    timeout=30
                )

                if response.status_code == 200:
                    data = response.json()
                    if data.get("success"):
                        alertas = data.get("data", {}).get("alertas", [])
                        # Filtrar apenas os não notificados
                        for alerta in alertas:
                            if not alerta.get("notificado"):
                                alertas_pendentes.append(alerta)

            logger.info(f"📋 Encontrados {len(alertas_pendentes)} alertas pendentes de notificação")
            return alertas_pendentes

        except Exception as e:
            logger.error(f"Erro ao buscar alertas pendentes: {e}")
            return []

    def get_critical_alerts(self) -> List[Dict[str, Any]]:
        """
        Busca apenas alertas críticos e vencidos

        Returns:
            Lista de alertas críticos
        """
        try:
            url = f"{self.base_url}/avisos-manutencao-api.php"

            response = requests.get(
                url,
                params={
                    "status": "Vencido",
                    "limit": 100
                },
                timeout=30
            )

            alertas = []

            if response.status_code == 200:
                data = response.json()
                if data.get("success"):
                    alertas = data.get("data", {}).get("alertas", [])

            # Buscar também os críticos não vencidos
            response2 = requests.get(
                url,
                params={
                    "nivel_alerta": "Critico",
                    "limit": 100
                },
                timeout=30
            )

            if response2.status_code == 200:
                data2 = response2.json()
                if data2.get("success"):
                    # Adicionar sem duplicatas
                    ids_existentes = {a.get("id") for a in alertas}
                    for alerta in data2.get("data", {}).get("alertas", []):
                        if alerta.get("id") not in ids_existentes:
                            alertas.append(alerta)

            logger.info(f"🚨 Encontrados {len(alertas)} alertas críticos/vencidos")
            return alertas

        except Exception as e:
            logger.error(f"Erro ao buscar alertas críticos: {e}")
            return []

    def mark_as_notified(self, alert_ids: List[int]) -> bool:
        """
        Marca alertas como notificados usando endpoint batch

        Args:
            alert_ids: Lista de IDs de alertas

        Returns:
            True se sucesso, False caso contrário
        """
        if not alert_ids:
            return True

        logger.info(f"📝 Marcando {len(alert_ids)} alertas como notificados...")

        try:
            url = f"{self.base_url}/avisos-manutencao-api.php"

            # Usar endpoint batch para marcar todos de uma vez
            response = requests.put(
                url,
                params={"action": "mark-notified-batch"},
                json={"ids": alert_ids},
                headers={"Content-Type": "application/json"},
                timeout=30
            )

            if response.status_code == 200:
                result = response.json()
                if result.get("success"):
                    data = result.get("data", {})
                    logger.info(f"✅ {data.get('atualizados', len(alert_ids))} alertas marcados como notificados")
                    return True
                else:
                    logger.error(f"❌ Erro ao marcar alertas: {result.get('error')}")
                    return False
            else:
                logger.error(f"❌ HTTP {response.status_code} ao marcar alertas")
                return False

        except Exception as e:
            logger.error(f"❌ Exceção ao marcar alertas como notificados: {e}")
            return False

    def format_alert_message(self, alerta: Dict[str, Any]) -> str:
        """
        Formata mensagem de alerta para envio via WhatsApp

        Args:
            alerta: Dados do alerta

        Returns:
            Mensagem formatada
        """
        placa = alerta.get("placa", "N/A")
        modelo = alerta.get("modelo", "")
        descricao = alerta.get("plano_nome", alerta.get("mensagem", "Manutenção"))
        nivel = alerta.get("prioridade", alerta.get("nivel_alerta", ""))
        km_restantes = alerta.get("km_restantes", 0)
        km_atual = alerta.get("km_atual", 0)

        # Emoji baseado no nível
        emoji = {
            "Critico": "🔴",
            "Alto": "🟠",
            "Medio": "🟡",
            "Baixo": "🔵"
        }.get(nivel, "⚪")

        # Formatar mensagem
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

    def group_alerts_by_vehicle(self, alertas: List[Dict[str, Any]]) -> Dict[str, List[Dict[str, Any]]]:
        """
        Agrupa alertas por veículo

        Args:
            alertas: Lista de alertas

        Returns:
            Dict com placa como chave e lista de alertas como valor
        """
        grupos = {}

        for alerta in alertas:
            placa = alerta.get("placa", "UNKNOWN")
            if placa not in grupos:
                grupos[placa] = []
            grupos[placa].append(alerta)

        return grupos

    def format_summary_message(self, alertas: List[Dict[str, Any]]) -> str:
        """
        Formata mensagem resumida com todos os alertas críticos

        Args:
            alertas: Lista de alertas

        Returns:
            Mensagem resumida formatada
        """
        if not alertas:
            return "✅ *Nenhum alerta crítico no momento.*"

        # Agrupar por veículo
        grupos = self.group_alerts_by_vehicle(alertas)

        # Contar totais
        total_criticos = sum(1 for a in alertas if a.get("nivel_alerta") == "Critico" or a.get("prioridade") == "Critico")
        total_vencidos = sum(1 for a in alertas if a.get("km_restantes", 0) <= 0)

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

            for alerta in alertas_veiculo[:3]:  # Máximo 3 alertas por veículo no resumo
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


# Instância singleton do serviço
alerts_service = AlertsService()
