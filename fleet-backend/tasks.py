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


# Will be initialized in app.py
celery = None


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

    schedule['sync-mileage-23h59'] = {
        'task': 'tasks.sync_all_vehicles_mileage',
        'schedule': crontab(hour=23, minute=59),  # 23:59
        'args': ()
    }

    return schedule
