"""
Monitoring Service - Sistema de Monitoramento de Syncs

Detecta falhas, atrasos e anomalias nos syncs de quilometragem
"""
import logging
import requests
from datetime import datetime, timedelta
from typing import Dict, List, Optional

logger = logging.getLogger(__name__)


class MonitoringService:
    """Serviço de monitoramento de syncs de quilometragem"""

    def __init__(self):
        from config import Config
        self.php_api_url = 'https://floripa.in9automacao.com.br/daily-mileage-api.php'
        self.alert_webhook = Config.ALERT_WEBHOOK_URL  # Webhook do Discord/Slack

    def check_sync_health(self, date: Optional[datetime] = None) -> Dict:
        """
        Verifica saúde do sync de um dia específico

        Args:
            date: Data para verificar (default: ontem)

        Returns:
            Dict com diagnóstico completo
        """
        if date is None:
            date = datetime.now() - timedelta(days=1)

        date_str = date.strftime('%Y-%m-%d')

        logger.info(f"🔍 Verificando saúde do sync para {date_str}")

        try:
            # Buscar dados do dia via PHP API
            response = requests.get(
                self.php_api_url,
                params={'date': date_str},
                timeout=30
            )

            if response.status_code != 200:
                return {
                    'healthy': False,
                    'date': date_str,
                    'error': f'API retornou status {response.status_code}'
                }

            data = response.json()
            records = data.get('records', [])

            if not records:
                return {
                    'healthy': False,
                    'date': date_str,
                    'error': 'Nenhum registro encontrado',
                    'action_required': 'REPROCESS'
                }

            # Análise dos dados
            total_vehicles = len(records)
            zero_km_count = sum(1 for r in records if r.get('km_driven', 0) == 0)
            with_km_count = total_vehicles - zero_km_count
            total_km = sum(r.get('km_driven', 0) for r in records)

            zero_km_percent = (zero_km_count / total_vehicles * 100) if total_vehicles > 0 else 0

            # Determinar se é dia de semana ou fim de semana
            is_weekend = date.weekday() >= 5  # 5=Sábado, 6=Domingo

            # Thresholds de alerta
            max_zero_percent_weekday = 50  # 50% em dia de semana é suspeito
            max_zero_percent_weekend = 70  # 70% em fim de semana é aceitável

            max_allowed_zero = max_zero_percent_weekend if is_weekend else max_zero_percent_weekday

            # Verificar sync atrasado (synced_at > 6 horas após meia-noite)
            sync_times = [r.get('synced_at') for r in records if r.get('synced_at')]
            late_sync = False
            earliest_sync = None

            if sync_times:
                earliest_sync = min(sync_times)
                sync_datetime = datetime.fromisoformat(earliest_sync.replace('Z', '+00:00'))
                expected_time = date.replace(hour=6, minute=0, second=0) + timedelta(days=1)

                # Se sync rodou mais de 6 horas depois do esperado
                if sync_datetime > expected_time + timedelta(hours=6):
                    late_sync = True

            # Determinar saúde
            is_healthy = (
                zero_km_percent <= max_allowed_zero and
                not late_sync and
                total_km > 0
            )

            issues = []
            if zero_km_percent > max_allowed_zero:
                issues.append(f'{zero_km_percent:.1f}% veículos com 0km (limite: {max_allowed_zero}%)')
            if late_sync:
                issues.append(f'Sync atrasado (rodou em {earliest_sync})')
            if total_km == 0:
                issues.append('Total de KM é zero')

            result = {
                'healthy': is_healthy,
                'date': date_str,
                'day_type': 'weekend' if is_weekend else 'weekday',
                'statistics': {
                    'total_vehicles': total_vehicles,
                    'with_km': with_km_count,
                    'zero_km': zero_km_count,
                    'zero_km_percent': round(zero_km_percent, 2),
                    'total_km': round(total_km, 2)
                },
                'sync_info': {
                    'late': late_sync,
                    'earliest_sync_at': earliest_sync
                },
                'issues': issues,
                'action_required': 'REPROCESS' if not is_healthy else None
            }

            if not is_healthy:
                logger.warning(f"⚠️ Sync não está saudável para {date_str}: {', '.join(issues)}")
            else:
                logger.info(f"✅ Sync saudável para {date_str}")

            return result

        except Exception as e:
            logger.error(f"❌ Erro ao verificar saúde do sync: {e}")
            return {
                'healthy': False,
                'date': date_str,
                'error': str(e),
                'action_required': 'CHECK_CELERY'
            }

    def check_last_7_days(self) -> List[Dict]:
        """
        Verifica saúde dos últimos 7 dias

        Returns:
            Lista com diagnóstico de cada dia
        """
        results = []
        for i in range(7):
            date = datetime.now() - timedelta(days=i+1)
            result = self.check_sync_health(date)
            results.append(result)

        unhealthy_count = sum(1 for r in results if not r['healthy'])

        logger.info(f"📊 Últimos 7 dias: {7 - unhealthy_count} saudáveis, {unhealthy_count} com problemas")

        return results

    def send_alert(self, alert_type: str, message: str, details: Dict = None):
        """
        Envia alerta para webhook configurado (Discord/Slack)

        Args:
            alert_type: Tipo de alerta (ERROR, WARNING, INFO, CRITICAL)
            message: Mensagem do alerta
            details: Detalhes adicionais
        """
        if not self.alert_webhook:
            logger.warning("⚠️ Webhook de alertas não configurado")
            return

        # Determinar cor do embed baseado no tipo
        colors = {
            'CRITICAL': 15158332,  # Vermelho forte
            'ERROR': 15105570,     # Vermelho
            'WARNING': 16776960,   # Amarelo
            'INFO': 3447003        # Azul
        }

        color = colors.get(alert_type, 8421504)  # Cinza padrão

        # Ícones para cada tipo
        icons = {
            'CRITICAL': '🚨',
            'ERROR': '❌',
            'WARNING': '⚠️',
            'INFO': 'ℹ️'
        }

        icon = icons.get(alert_type, '📢')

        # Formatar detalhes
        details_text = ""
        if details:
            for key, value in details.items():
                if isinstance(value, dict):
                    details_text += f"\n**{key}:**\n```json\n{str(value)[:500]}\n```"
                else:
                    details_text += f"\n**{key}:** `{value}`"

        # Payload formato Discord
        payload = {
            'embeds': [{
                'title': f'{icon} {alert_type}: Sistema de Quilometragem',
                'description': message,
                'color': color,
                'fields': [
                    {
                        'name': '🕒 Timestamp',
                        'value': datetime.utcnow().strftime('%Y-%m-%d %H:%M:%S UTC'),
                        'inline': True
                    },
                    {
                        'name': '🔧 Serviço',
                        'value': 'fleet-mileage-sync',
                        'inline': True
                    }
                ],
                'footer': {
                    'text': 'Sistema de Monitoramento i9 Engenharia'
                }
            }]
        }

        # Adicionar detalhes se existirem
        if details_text:
            payload['embeds'][0]['fields'].append({
                'name': '📋 Detalhes',
                'value': details_text[:1024],  # Discord limit
                'inline': False
            })

        try:
            response = requests.post(
                self.alert_webhook,
                json=payload,
                timeout=10
            )

            if response.status_code in [200, 204]:
                logger.info(f"✅ Alerta Discord enviado: {message}")
            else:
                logger.error(f"❌ Falha ao enviar alerta Discord: HTTP {response.status_code}")
                logger.error(f"   Response: {response.text}")

        except Exception as e:
            logger.error(f"❌ Erro ao enviar alerta Discord: {e}")

    def detect_and_report_issues(self) -> Dict:
        """
        Detecta e reporta problemas nos syncs

        Returns:
            Relatório completo de issues
        """
        logger.info("🔍 Detectando problemas nos syncs...")

        results = self.check_last_7_days()

        issues = [r for r in results if not r['healthy']]
        critical_issues = [r for r in issues if r.get('action_required') == 'REPROCESS']

        report = {
            'total_days_checked': len(results),
            'healthy_days': len(results) - len(issues),
            'unhealthy_days': len(issues),
            'critical_issues': len(critical_issues),
            'issues': issues
        }

        # Enviar alertas se houver problemas críticos
        if critical_issues:
            message = f"⚠️ {len(critical_issues)} dia(s) com problemas críticos detectados"
            self.send_alert('ERROR', message, {'critical_issues': critical_issues})

        return report


# Instância global
monitoring_service = MonitoringService()
