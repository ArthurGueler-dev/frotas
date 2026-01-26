"""
Celery Tasks for Fleet Management
- Daily mileage calculation
- Data retention/cleanup
- Scheduled synchronizations
- Route compliance monitoring
"""
import logging
import time
import requests
from datetime import datetime, timedelta, date
from celery import Celery, Task
from celery.schedules import crontab
from flask import Flask
from config import Config
from models import db, Vehicle, DailyMileage, SyncLog, RouteCompliance, RouteDeviation, AlertRecipient
from services.ituran_service import ituran_service
from services.route_compliance_service import RouteComplianceService
from services.mileage_service import MileageService
from services.monitoring_service import monitoring_service
from services.alerts_service import alerts_service

logger = logging.getLogger(__name__)
route_compliance_service = RouteComplianceService()
mileage_service = MileageService()


def make_celery(app: Flask) -> Celery:
    """Create and configure Celery instance"""
    celery = Celery(
        app.import_name,
        **app.config['CELERY_CONFIG']
    )

    class ContextTask(Task):
        """Task with Flask app context"""
        def __call__(self, *args, **kwargs):
            with app.app_context():
                return self.run(*args, **kwargs)

    celery.Task = ContextTask
    return celery


# Create Celery instance (will be reconfigured in app.py with Flask context)
celery = Celery('fleet-backend')


@celery.task(
    bind=True,
    autoretry_for=(Exception,),
    retry_kwargs={'max_retries': 3, 'countdown': 60},
    name='tasks.calculate_vehicle_mileage'
)
def calculate_vehicle_mileage(self, vehicle_id: int, target_date: str = None):
    """
    Calculate mileage for a single vehicle

    Args:
        vehicle_id: Vehicle ID
        target_date: Date to calculate (ISO format), defaults to yesterday

    Returns:
        Dict with result
    """
    try:
        # Get vehicle
        vehicle = db.session.get(Vehicle, vehicle_id)
        if not vehicle:
            logger.error(f"Vehicle {vehicle_id} not found")
            return {'success': False, 'error': 'Vehicle not found'}

        if not vehicle.is_active:
            logger.info(f"Vehicle {vehicle.plate} is inactive, skipping")
            return {'success': False, 'error': 'Vehicle inactive'}

        # Parse target date (default: yesterday)
        if target_date:
            calc_date = datetime.fromisoformat(target_date).date()
        else:
            calc_date = (datetime.now() - timedelta(days=1)).date()

        logger.info(f"🚗 Calculating KM for {vehicle.plate} on {calc_date}")

        # Check if already calculated
        existing = DailyMileage.query.filter_by(
            vehicle_id=vehicle_id,
            date=calc_date
        ).first()

        if existing and existing.calculation_status == 'success':
            logger.info(f"Already calculated for {vehicle.plate} on {calc_date}")
            return {
                'success': True,
                'km_driven': existing.km_driven,
                'cached': True
            }

        # Get KM from Ituran API
        result = ituran_service.get_daily_km(
            plate=vehicle.plate,
            date=datetime.combine(calc_date, datetime.min.time()),
            area_id=vehicle.area_id
        )

        # Create or update record
        if existing:
            mileage_record = existing
            mileage_record.retry_count += 1
        else:
            mileage_record = DailyMileage(
                vehicle_id=vehicle_id,
                date=calc_date
            )

        # Update with results
        mileage_record.km_driven = result.get('km_driven', 0)
        mileage_record.start_odometer = result.get('start_odometer')
        mileage_record.end_odometer = result.get('end_odometer')
        mileage_record.calculation_method = result.get('method', 'unknown')
        mileage_record.data_source = result.get('data_source')
        mileage_record.record_count = result.get('record_count', 0)
        mileage_record.calculation_status = 'success' if result['success'] else 'error'
        mileage_record.error_message = result.get('error')
        mileage_record.updated_at = datetime.utcnow()

        if not existing:
            db.session.add(mileage_record)

        db.session.commit()

        logger.info(
            f"✅ {vehicle.plate}: {mileage_record.km_driven} km "
            f"(method: {mileage_record.calculation_method})"
        )

        return {
            'success': result['success'],
            'vehicle_plate': vehicle.plate,
            'date': calc_date.isoformat(),
            'km_driven': mileage_record.km_driven,
            'method': mileage_record.calculation_method,
            'cached': False
        }

    except Exception as e:
        logger.error(f"Error calculating mileage for vehicle {vehicle_id}: {e}")

        # Update record with error
        try:
            mileage_record = DailyMileage.query.filter_by(
                vehicle_id=vehicle_id,
                date=calc_date
            ).first()

            if mileage_record:
                mileage_record.calculation_status = 'error'
                mileage_record.error_message = str(e)
                mileage_record.retry_count += 1
                db.session.commit()
        except Exception:
            pass

        # Reraise for Celery retry
        raise


@celery.task(
    bind=True,
    name='tasks.calculate_daily_mileage_all'
)
def calculate_daily_mileage_all(self, target_date: str = None):
    """
    Calculate daily mileage for ALL active vehicles

    Args:
        target_date: Date to calculate (ISO format), defaults to yesterday

    Returns:
        Dict with statistics
    """
    task_id = self.request.id
    logger.info(f"🚀 Starting daily mileage calculation (task: {task_id})")

    # Create sync log
    sync_log = SyncLog(
        task_id=task_id,
        task_name='calculate_daily_mileage_all',
        started_at=datetime.utcnow(),
        status='running'
    )
    db.session.add(sync_log)
    db.session.commit()

    try:
        # Get all active vehicles
        vehicles = Vehicle.query.filter_by(is_active=True).all()
        total_vehicles = len(vehicles)

        logger.info(f"📋 Found {total_vehicles} active vehicles")

        if total_vehicles == 0:
            sync_log.status = 'success'
            sync_log.finished_at = datetime.utcnow()
            db.session.commit()
            return {
                'success': True,
                'message': 'No active vehicles to process'
            }

        # Process each vehicle
        success_count = 0
        failed_count = 0
        results = []

        for i, vehicle in enumerate(vehicles, 1):
            try:
                logger.info(f"  [{i}/{total_vehicles}] Processing {vehicle.plate}")

                # Call single vehicle task
                result = calculate_vehicle_mileage(
                    vehicle.id,
                    target_date
                )

                if result.get('success'):
                    success_count += 1
                else:
                    failed_count += 1

                results.append({
                    'plate': vehicle.plate,
                    'success': result.get('success'),
                    'km_driven': result.get('km_driven', 0),
                    'error': result.get('error')
                })

                # Update progress
                self.update_state(
                    state='PROGRESS',
                    meta={
                        'current': i,
                        'total': total_vehicles,
                        'vehicle': vehicle.plate,
                        'success': success_count,
                        'failed': failed_count
                    }
                )

            except Exception as e:
                logger.error(f"  ❌ Failed to process {vehicle.plate}: {e}")
                failed_count += 1
                results.append({
                    'plate': vehicle.plate,
                    'success': False,
                    'error': str(e)
                })

        # Update sync log
        sync_log.status = 'success' if failed_count == 0 else 'partial'
        sync_log.finished_at = datetime.utcnow()
        sync_log.vehicles_processed = total_vehicles
        sync_log.vehicles_success = success_count
        sync_log.vehicles_failed = failed_count
        db.session.commit()

        logger.info(
            f"✅ Daily mileage calculation completed: "
            f"{success_count} success, {failed_count} failed"
        )

        return {
            'success': True,
            'total': total_vehicles,
            'success_count': success_count,
            'failed_count': failed_count,
            'results': results
        }

    except Exception as e:
        logger.error(f"❌ Fatal error in daily mileage calculation: {e}")

        # Update sync log
        sync_log.status = 'failed'
        sync_log.finished_at = datetime.utcnow()
        sync_log.error_message = str(e)
        db.session.commit()

        raise


@celery.task(name='tasks.cleanup_old_data')
def cleanup_old_data():
    """
    Clean up data older than DATA_RETENTION_YEARS
    Runs monthly
    """
    logger.info("🗑️ Starting data cleanup job")

    try:
        retention_years = Config.DATA_RETENTION_YEARS
        cutoff_date = datetime.now() - timedelta(days=retention_years * 365)

        logger.info(f"Deleting mileage records older than {cutoff_date.date()}")

        # Delete old daily mileage records
        deleted = DailyMileage.query.filter(
            DailyMileage.date < cutoff_date.date()
        ).delete(synchronize_session=False)

        db.session.commit()

        logger.info(f"✅ Deleted {deleted} old mileage records")

        # Also clean old sync logs (keep 1 year)
        sync_cutoff = datetime.now() - timedelta(days=365)
        deleted_logs = SyncLog.query.filter(
            SyncLog.started_at < sync_cutoff
        ).delete(synchronize_session=False)

        db.session.commit()

        logger.info(f"✅ Deleted {deleted_logs} old sync logs")

        return {
            'success': True,
            'deleted_mileage': deleted,
            'deleted_logs': deleted_logs
        }

    except Exception as e:
        logger.error(f"❌ Error in data cleanup: {e}")
        db.session.rollback()
        raise


@celery.task(name='tasks.recalculate_failed_records')
def recalculate_failed_records():
    """
    Retry failed calculations from the last 7 days
    Runs daily at 04:00
    """
    logger.info("🔄 Recalculating failed records")

    try:
        # Get failed records from last 7 days
        cutoff_date = (datetime.now() - timedelta(days=7)).date()

        failed_records = DailyMileage.query.filter(
            DailyMileage.calculation_status == 'error',
            DailyMileage.date >= cutoff_date,
            DailyMileage.retry_count < 5  # Max 5 retries
        ).all()

        logger.info(f"Found {len(failed_records)} failed records to retry")

        success_count = 0

        for record in failed_records:
            try:
                vehicle = record.vehicle
                if not vehicle or not vehicle.is_active:
                    continue

                logger.info(f"Retrying {vehicle.plate} for {record.date}")

                # Recalculate
                result = ituran_service.get_daily_km(
                    plate=vehicle.plate,
                    date=datetime.combine(record.date, datetime.min.time()),
                    area_id=vehicle.area_id
                )

                if result['success']:
                    record.km_driven = result['km_driven']
                    record.start_odometer = result.get('start_odometer')
                    record.end_odometer = result.get('end_odometer')
                    record.calculation_method = result['method']
                    record.data_source = result.get('data_source')
                    record.calculation_status = 'success'
                    record.error_message = None
                    success_count += 1
                else:
                    record.error_message = result.get('error')

                record.retry_count += 1
                record.updated_at = datetime.utcnow()

            except Exception as e:
                logger.error(f"Failed to retry record {record.id}: {e}")
                record.retry_count += 1
                record.error_message = str(e)

        db.session.commit()

        logger.info(f"✅ Recalculated {success_count}/{len(failed_records)} failed records")

        return {
            'success': True,
            'total_retried': len(failed_records),
            'success_count': success_count
        }

    except Exception as e:
        logger.error(f"❌ Error in recalculate_failed_records: {e}")
        db.session.rollback()
        raise


@celery.task(
    bind=True,
    name='tasks.sync_all_vehicles_mileage'
)
def sync_all_vehicles_mileage(self, target_date: str = None):
    """
    Sincroniza quilometragem de TODOS os veículos ativos

    Esta task é executada automaticamente em horários programados:
    - 06:00, 12:00, 18:00, 23:59

    Usa o MileageService que:
    1. Busca todos os veículos ativos via PHP API
    2. Para cada veículo, busca odômetro via Ituran API
    3. Calcula KM rodados (odômetro_hoje - odômetro_ontem)
    4. Salva no banco via PHP API

    Args:
        target_date: Data para sincronizar (ISO format), padrão: ontem

    Returns:
        Dict com estatísticas de sucesso/falha
    """
    task_id = self.request.id
    logger.info(f"🚀 Iniciando sincronização de quilometragem (task: {task_id})")

    try:
        # Parse target date
        if target_date:
            sync_date = datetime.fromisoformat(target_date)
        else:
            sync_date = None  # MileageService usa ontem por padrão

        # Executar sincronização usando o serviço
        stats = mileage_service.sync_all_vehicles(sync_date)

        logger.info(
            f"✅ Sincronização concluída: "
            f"{stats['success']} sucesso, {stats['failed']} falhas, "
            f"total: {stats['total']} veículos"
        )

        # Update progress
        self.update_state(
            state='SUCCESS',
            meta={
                'total': stats['total'],
                'success': stats['success'],
                'failed': stats['failed']
            }
        )

        # ========== TRIGGER PÓS-SYNC: Gerar Alertas de Manutenção ==========
        # Após cada sync de KM bem-sucedido, recalcular alertas de manutenção
        if stats['success'] > 0:
            try:
                logger.info("🔄 Disparando geração de alertas de manutenção pós-sync...")
                # Chamar diretamente a função de geração de alertas (não como task assíncrona)
                alert_result = alerts_service.generate_all_alerts()
                if alert_result.get('success'):
                    logger.info(
                        f"✅ Alertas gerados pós-sync: "
                        f"{alert_result.get('data', {}).get('alertas_gerados', 0)} novos, "
                        f"{alert_result.get('data', {}).get('alertas_atualizados', 0)} atualizados"
                    )
                else:
                    logger.warning(f"⚠️ Falha ao gerar alertas pós-sync: {alert_result.get('error')}")
            except Exception as alert_error:
                logger.warning(f"⚠️ Erro ao gerar alertas pós-sync (não-fatal): {alert_error}")
        # ========== FIM TRIGGER PÓS-SYNC ==========

        return {
            'success': True,
            'statistics': stats,
            'timestamp': datetime.utcnow().isoformat()
        }

    except Exception as e:
        logger.error(f"❌ Erro fatal na sincronização de quilometragem: {e}")

        return {
            'success': False,
            'error': str(e),
            'timestamp': datetime.utcnow().isoformat()
        }


@celery.task(
    bind=True,
    name='tasks.monitor_sync_health'
)
def monitor_sync_health(self):
    """
    Monitora saúde dos syncs de quilometragem

    Roda a cada 10 minutos. Verifica:
    - Se última sync rodou há menos de 7 horas
    - Se há dias com problemas nos últimos 7 dias
    - Se há anomalias nos dados (>50% veículos com 0km em dia de semana)

    Se detectar problemas, envia alertas e pode disparar reprocessamento.
    """
    task_id = self.request.id
    logger.info(f"🔍 Monitoramento de saúde dos syncs (task: {task_id})")

    try:
        # Verificar últimos 7 dias
        report = monitoring_service.detect_and_report_issues()

        logger.info(
            f"📊 Relatório: {report['healthy_days']}/{report['total_days_checked']} dias saudáveis, "
            f"{report['critical_issues']} problemas críticos"
        )

        # Se há problemas críticos, verificar se precisamos agir
        if report['critical_issues'] > 0:
            logger.warning(f"⚠️ {report['critical_issues']} dia(s) com problemas críticos detectados")

            # Enviar alerta detalhado
            for issue in report['issues']:
                if issue.get('action_required') == 'REPROCESS':
                    date_str = issue['date']
                    issues_list = issue.get('issues', [])

                    logger.warning(f"⚠️ {date_str}: {', '.join(issues_list)}")

                    # Criar alerta
                    monitoring_service.send_alert(
                        'ERROR',
                        f"Sync do dia {date_str} precisa ser reprocessado",
                        issue
                    )

        # Verificar se última sync foi há muito tempo (mais de 7 horas)
        last_sync_log = SyncLog.query.filter_by(
            task_name='sync_all_vehicles_mileage'
        ).order_by(SyncLog.started_at.desc()).first()

        if last_sync_log:
            time_since_last = datetime.utcnow() - last_sync_log.started_at
            hours_since_last = time_since_last.total_seconds() / 3600

            if hours_since_last > 7:
                logger.error(
                    f"🚨 ALERTA CRÍTICO: Última sync foi há {hours_since_last:.1f} horas! "
                    f"Esperado: máximo 6 horas entre syncs"
                )

                monitoring_service.send_alert(
                    'CRITICAL',
                    f"Sync de quilometragem atrasado ({hours_since_last:.1f}h desde última execução)",
                    {
                        'last_sync_at': last_sync_log.started_at.isoformat(),
                        'hours_since_last': round(hours_since_last, 2),
                        'status': last_sync_log.status
                    }
                )

                # Tentar disparar sync emergencial se última falhou ou está muito atrasada
                if hours_since_last > 12 or last_sync_log.status == 'failed':
                    logger.warning("🚨 Disparando sync emergencial...")
                    sync_all_vehicles_mileage.delay()

        return {
            'success': True,
            'report': report,
            'timestamp': datetime.utcnow().isoformat()
        }

    except Exception as e:
        logger.error(f"❌ Erro no monitoramento: {e}")
        return {
            'success': False,
            'error': str(e)
        }


@celery.task(
    bind=True,
    name='tasks.auto_recovery_check'
)
def auto_recovery_check(self):
    """
    Verifica e recupera automaticamente syncs com problemas

    Roda a cada hora. Para cada dia com problemas:
    - Verifica se >50% veículos com 0km em dia de semana
    - Verifica se sync rodou atrasado
    - Se detectar problema, dispara reprocessamento automático

    Evita reprocessar o mesmo dia mais de 1x por dia.
    """
    task_id = self.request.id
    logger.info(f"🔧 Auto-recovery check (task: {task_id})")

    try:
        # Verificar últimos 3 dias (excluindo hoje)
        recovery_count = 0

        for days_ago in range(1, 4):
            check_date = datetime.now() - timedelta(days=days_ago)
            date_str = check_date.strftime('%Y-%m-%d')

            health = monitoring_service.check_sync_health(check_date)

            if not health['healthy'] and health.get('action_required') == 'REPROCESS':
                logger.warning(f"⚠️ {date_str} precisa de reprocessamento")

                # Verificar se já reprocessamos hoje
                already_reprocessed = SyncLog.query.filter(
                    SyncLog.task_name == 'sync_all_vehicles_mileage',
                    SyncLog.started_at >= datetime.utcnow().replace(hour=0, minute=0, second=0),
                    SyncLog.metadata.like(f'%{date_str}%')  # Verifica se foi para esta data
                ).first()

                if already_reprocessed:
                    logger.info(f"  ℹ️ {date_str} já foi reprocessado hoje, pulando...")
                    continue

                logger.info(f"  🔄 Iniciando reprocessamento automático de {date_str}")

                # Disparar sync para data específica
                result = sync_all_vehicles_mileage.delay(target_date=date_str)

                # Criar log de recovery
                recovery_log = SyncLog(
                    task_id=result.id,
                    task_name='sync_all_vehicles_mileage',
                    started_at=datetime.utcnow(),
                    status='running',
                    metadata=f'auto_recovery for {date_str}'
                )
                db.session.add(recovery_log)
                db.session.commit()

                recovery_count += 1

                # Enviar alerta
                monitoring_service.send_alert(
                    'INFO',
                    f"Auto-recovery iniciado para {date_str}",
                    {
                        'task_id': result.id,
                        'health_report': health
                    }
                )

        logger.info(f"✅ Auto-recovery concluído: {recovery_count} dia(s) reprocessados")

        return {
            'success': True,
            'recovery_count': recovery_count,
            'timestamp': datetime.utcnow().isoformat()
        }

    except Exception as e:
        logger.error(f"❌ Erro no auto-recovery: {e}")
        return {
            'success': False,
            'error': str(e)
        }


@celery.task(
    bind=True,
    name='tasks.generate_maintenance_alerts'
)
def generate_maintenance_alerts(self, placa: str = None):
    """
    Gera alertas de manutenção preventiva para todos os veículos

    Esta task é executada automaticamente 2x por dia:
    - 06:00 (antes do início do expediente)
    - 18:00 (ao final do expediente)

    Pode também ser executada manualmente passando uma placa específica.

    Para cada veículo:
    1. Busca plano de manutenção do modelo
    2. Busca última OS preventiva finalizada
    3. Busca KM atual do veículo
    4. Calcula KM restantes até próxima manutenção
    5. Cria/atualiza alertas em avisos_manutencao

    Args:
        placa: Placa do veículo (opcional). Se None, processa todos.

    Returns:
        Dict com estatísticas de geração
    """
    task_id = self.request.id
    logger.info(f"🔔 Iniciando geração de alertas de manutenção (task: {task_id})")

    try:
        # Executar geração via serviço
        result = alerts_service.generate_all_alerts(placa)

        logger.info(
            f"✅ Geração de alertas concluída: "
            f"{result.get('alertas_gerados', 0)} gerados, "
            f"{result.get('alertas_atualizados', 0)} atualizados"
        )

        # Atualizar estado
        self.update_state(
            state='SUCCESS',
            meta={
                'alertas_gerados': result.get('alertas_gerados', 0),
                'alertas_atualizados': result.get('alertas_atualizados', 0),
                'total_veiculos': result.get('total_veiculos', 0)
            }
        )

        return {
            'success': result.get('success', False),
            'statistics': result,
            'timestamp': datetime.utcnow().isoformat()
        }

    except Exception as e:
        logger.error(f"❌ Erro fatal na geração de alertas: {e}")
        return {
            'success': False,
            'error': str(e),
            'timestamp': datetime.utcnow().isoformat()
        }


@celery.task(
    bind=True,
    name='tasks.send_alert_notifications'
)
def send_alert_notifications(self):
    """
    Envia notificações de alertas críticos via WhatsApp

    Esta task é executada automaticamente 1x por dia às 07:00
    (após a geração de alertas das 06:00)

    Funcionalidade:
    1. Busca alertas críticos e de alta prioridade não notificados
    2. Busca destinatários configurados por severidade
    3. Formata mensagem resumida
    4. Envia via API WhatsApp (Evolution API)
    5. Marca alertas como notificados

    Returns:
        Dict com estatísticas de envio
    """
    task_id = self.request.id
    logger.info(f"📤 Iniciando envio de notificações de alertas (task: {task_id})")

    try:
        # Buscar alertas críticos e altos não notificados
        alertas = alerts_service.get_critical_alerts()

        if not alertas:
            logger.info("✅ Nenhum alerta crítico para notificar")
            return {
                'success': True,
                'message': 'Nenhum alerta para notificar',
                'notificados': 0,
                'timestamp': datetime.utcnow().isoformat()
            }

        logger.info(f"📋 Encontrados {len(alertas)} alertas para notificar")

        # Buscar destinatários
        destinatarios = AlertRecipient.query.filter_by(
            is_active=True,
            alert_type='maintenance'
        ).all()

        if not destinatarios:
            # Fallback: buscar destinatários de qualquer tipo
            destinatarios = AlertRecipient.query.filter_by(is_active=True).limit(5).all()

        if not destinatarios:
            logger.warning("⚠️ Nenhum destinatário configurado para notificações")
            return {
                'success': True,
                'message': 'Nenhum destinatário configurado',
                'notificados': 0,
                'timestamp': datetime.utcnow().isoformat()
            }

        # Formatar mensagem resumida
        mensagem = alerts_service.format_summary_message(alertas)

        # Enviar para cada destinatário
        enviados = 0
        erros = []

        for dest in destinatarios:
            try:
                # Chamar API PHP de WhatsApp
                response = requests.post(
                    "https://floripa.in9automacao.com.br/enviar-alertas-whatsapp.php",
                    json={
                        "telefone": dest.phone_number,
                        "mensagem": mensagem,
                        "tipo": "maintenance_alert"
                    },
                    timeout=30
                )

                if response.status_code == 200:
                    result = response.json()
                    if result.get('success'):
                        enviados += 1
                        logger.info(f"✅ Notificação enviada para {dest.name}")
                    else:
                        erros.append(f"{dest.name}: {result.get('error')}")
                else:
                    erros.append(f"{dest.name}: HTTP {response.status_code}")

            except Exception as e:
                erros.append(f"{dest.name}: {str(e)}")
                logger.error(f"Erro ao enviar para {dest.name}: {e}")

        # Marcar alertas como notificados
        alert_ids = [a.get('id') for a in alertas if a.get('id')]
        alerts_service.mark_as_notified(alert_ids)

        logger.info(f"✅ Notificações enviadas: {enviados}/{len(destinatarios)}")

        return {
            'success': True,
            'total_alertas': len(alertas),
            'destinatarios': len(destinatarios),
            'enviados': enviados,
            'erros': erros,
            'timestamp': datetime.utcnow().isoformat()
        }

    except Exception as e:
        logger.error(f"❌ Erro fatal no envio de notificações: {e}")
        return {
            'success': False,
            'error': str(e),
            'timestamp': datetime.utcnow().isoformat()
        }


@celery.task(
    bind=True,
    name='tasks.check_all_routes_compliance'
)
def check_all_routes_compliance(self):
    """
    Check compliance for ALL active routes (status='em_andamento')

    Runs every 5 minutes via Celery Beat.
    For each active route:
    - Fetch current GPS position
    - Compare with planned route
    - Detect deviations
    - Send WhatsApp alerts if needed

    Returns:
        Dict with statistics
    """
    task_id = self.request.id
    logger.info(f"🔍 Starting route compliance check (task: {task_id})")

    try:
        # Fetch active routes from PHP API
        api_url = 'https://floripa.in9automacao.com.br/cpanel-api/rotas-api.php'
        response = requests.get(api_url, params={'status': 'em_andamento'}, timeout=10)

        if response.status_code != 200:
            logger.error(f"Failed to fetch routes: HTTP {response.status_code}")
            return {
                'success': False,
                'error': f'API returned {response.status_code}'
            }

        data = response.json()
        routes = data.get('rotas', [])

        logger.info(f"📊 Found {len(routes)} routes in progress")

        if len(routes) == 0:
            return {
                'success': True,
                'message': 'No active routes to monitor',
                'checked': 0
            }

        # Check compliance for each route
        results = []
        compliant_count = 0
        deviation_count = 0

        for i, route in enumerate(routes, 1):
            try:
                route_id = route['id']
                logger.info(f"  [{i}/{len(routes)}] Checking route #{route_id}")

                # Call compliance service
                result = route_compliance_service.check_route_compliance(route_id)

                results.append({
                    'route_id': route_id,
                    'is_compliant': result['is_compliant'],
                    'deviations_count': len(result.get('deviations', [])),
                    'distance_km': result.get('distance_km', 0)
                })

                if result['is_compliant']:
                    compliant_count += 1
                else:
                    deviation_count += 1

                # Update progress
                self.update_state(
                    state='PROGRESS',
                    meta={
                        'current': i,
                        'total': len(routes),
                        'route_id': route_id,
                        'compliant': compliant_count,
                        'deviations': deviation_count
                    }
                )

                # Delay between checks (avoid API overload)
                time.sleep(2)

            except Exception as e:
                logger.error(f"  ❌ Error checking route #{route['id']}: {e}")
                results.append({
                    'route_id': route['id'],
                    'error': str(e)
                })

        logger.info(
            f"✅ Compliance check completed: "
            f"{compliant_count} compliant, {deviation_count} with deviations"
        )

        return {
            'success': True,
            'checked': len(routes),
            'compliant': compliant_count,
            'deviations': deviation_count,
            'results': results
        }

    except Exception as e:
        logger.error(f"❌ Fatal error in compliance check: {e}")
        return {
            'success': False,
            'error': str(e)
        }


# Celery Beat Schedule (runs automatically)
def get_beat_schedule():
    """
    Get Celery Beat schedule configuration
    """
    schedule = {}

    # Scheduled sync times (from config)
    for time_str in Config.SYNC_TIMES:
        hour, minute = time_str.split(':')
        schedule[f'sync-{time_str.replace(":", "")}'] = {
            'task': 'tasks.calculate_daily_mileage_all',
            'schedule': crontab(hour=int(hour), minute=int(minute)),
            'args': ()  # Uses default (yesterday)
        }

    # Daily at midnight - calculate previous day
    schedule['daily-midnight-calculation'] = {
        'task': 'tasks.calculate_daily_mileage_all',
        'schedule': crontab(hour=0, minute=5),  # 00:05 AM
        'args': ()
    }

    # Retry failed records - daily at 04:00
    schedule['retry-failed-records'] = {
        'task': 'tasks.recalculate_failed_records',
        'schedule': crontab(hour=4, minute=0),
        'args': ()
    }

    # Cleanup old data - monthly on 1st at 03:00
    schedule['monthly-cleanup'] = {
        'task': 'tasks.cleanup_old_data',
        'schedule': crontab(hour=3, minute=0, day_of_month=1),
        'args': ()
    }

    # ⭐ NEW: Route compliance monitoring - every 5 minutes
    schedule['check-route-compliance'] = {
        'task': 'tasks.check_all_routes_compliance',
        'schedule': crontab(minute='*/5'),  # Every 5 minutes
        'args': ()
    }

    # ⭐ NEW: Automatic mileage sync - 4 times daily
    schedule['sync-mileage-06h'] = {
        'task': 'tasks.sync_all_vehicles_mileage',
        'schedule': crontab(hour=6, minute=0),  # 06:00
        'args': ()
    }

    schedule['sync-mileage-12h'] = {
        'task': 'tasks.sync_all_vehicles_mileage',
        'schedule': crontab(hour=12, minute=0),  # 12:00
        'args': ()
    }

    schedule['sync-mileage-18h'] = {
        'task': 'tasks.sync_all_vehicles_mileage',
        'schedule': crontab(hour=18, minute=0),  # 18:00
        'args': ()
    }

    # REMOVIDO sync das 23:59 - causava bug de odometer_start errado
    # Porque às 23:59 a API Ituran ainda não tem dados de meia-noite do dia seguinte
    # Substituído por sync às 00:05 que calcula KM do dia anterior (completo)
    schedule['sync-mileage-00h05'] = {
        'task': 'tasks.sync_all_vehicles_mileage',
        'schedule': crontab(hour=0, minute=5),  # 00:05 - calcula dia anterior
        'args': ()
    }

    # ⭐ NEW: Monitoring and health check - every 10 minutes
    schedule['monitor-sync-health'] = {
        'task': 'tasks.monitor_sync_health',
        'schedule': crontab(minute='*/10'),  # Every 10 minutes
        'args': ()
    }

    # ⭐ NEW: Auto recovery - every hour
    schedule['auto-recovery-check'] = {
        'task': 'tasks.auto_recovery_check',
        'schedule': crontab(minute=15),  # At :15 of every hour
        'args': ()
    }

    # ========== ALERTAS DE MANUTENÇÃO PREVENTIVA ==========

    # Gerar alertas de manutenção - 2x por dia
    schedule['generate-alerts-06h'] = {
        'task': 'tasks.generate_maintenance_alerts',
        'schedule': crontab(hour=6, minute=0),  # 06:00 - antes do expediente
        'args': ()
    }

    schedule['generate-alerts-18h'] = {
        'task': 'tasks.generate_maintenance_alerts',
        'schedule': crontab(hour=18, minute=0),  # 18:00 - após expediente
        'args': ()
    }

    # Enviar notificações de alertas - 1x por dia
    schedule['send-alert-notifications'] = {
        'task': 'tasks.send_alert_notifications',
        'schedule': crontab(hour=7, minute=0),  # 07:00 - após geração das 06:00
        'args': ()
    }

    return schedule
