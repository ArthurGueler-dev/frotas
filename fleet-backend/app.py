"""
Fleet Management Backend API
Flask + Celery + SQLAlchemy
"""
import logging
import os
from flask import Flask, jsonify, request
from flask_cors import CORS
from flask_migrate import Migrate
from datetime import datetime, timedelta
from config import config
from models import db, Vehicle, DailyMileage, Area, SyncLog
from tasks import make_celery, get_beat_schedule

# Logging configuration
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


def create_app(config_name=None):
    """Application factory"""
    if config_name is None:
        config_name = os.getenv('FLASK_ENV', 'development')

    app = Flask(__name__)
    app.config.from_object(config[config_name])

    # Initialize extensions
    db.init_app(app)
    CORS(app)
    migrate = Migrate(app, db)

    # Initialize Celery
    celery = make_celery(app)
    celery.conf.beat_schedule = get_beat_schedule()

    # Store celery in app for access
    app.celery = celery

    # Update tasks module celery instance
    import tasks
    tasks.celery = celery

    # Register routes
    register_routes(app)

    # Create tables
    with app.app_context():
        db.create_all()
        logger.info("✅ Database tables created")

    logger.info(f"🚀 Fleet Management API started ({config_name} mode)")

    return app


def register_routes(app):
    """Register API routes"""

    @app.route('/')
    def index():
        return jsonify({
            'service': 'Fleet Management API',
            'version': '1.0.0',
            'status': 'online'
        })

    @app.route('/health')
    def health():
        """Health check endpoint"""
        try:
            # Test database connection
            db.session.execute('SELECT 1')
            db_status = 'healthy'
        except Exception as e:
            db_status = f'unhealthy: {str(e)}'

        return jsonify({
            'status': 'healthy' if db_status == 'healthy' else 'degraded',
            'database': db_status,
            'timestamp': datetime.utcnow().isoformat()
        })

    # ==================== VEHICLES ====================

    @app.route('/api/vehicles', methods=['GET'])
    def get_vehicles():
        """Get all vehicles"""
        vehicles = Vehicle.query.all()
        return jsonify([v.to_dict() for v in vehicles])

    @app.route('/api/vehicles/<int:vehicle_id>', methods=['GET'])
    def get_vehicle(vehicle_id):
        """Get vehicle by ID"""
        vehicle = db.session.get(Vehicle, vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        return jsonify(vehicle.to_dict())

    @app.route('/api/vehicles', methods=['POST'])
    def create_vehicle():
        """Create new vehicle"""
        data = request.get_json()

        # Validate required fields
        if not data.get('plate'):
            return jsonify({'error': 'Plate is required'}), 400

        # Check if exists
        existing = Vehicle.query.filter_by(plate=data['plate']).first()
        if existing:
            return jsonify({'error': 'Vehicle with this plate already exists'}), 409

        vehicle = Vehicle(
            plate=data['plate'],
            brand=data.get('brand'),
            model=data.get('model'),
            year=data.get('year'),
            area_id=data.get('area_id'),
            is_active=data.get('is_active', True)
        )

        db.session.add(vehicle)
        db.session.commit()

        logger.info(f"✅ Vehicle created: {vehicle.plate}")

        return jsonify(vehicle.to_dict()), 201

    @app.route('/api/vehicles/<int:vehicle_id>', methods=['PUT'])
    def update_vehicle(vehicle_id):
        """Update vehicle"""
        vehicle = db.session.get(Vehicle, vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404

        data = request.get_json()

        if 'plate' in data:
            vehicle.plate = data['plate']
        if 'brand' in data:
            vehicle.brand = data['brand']
        if 'model' in data:
            vehicle.model = data['model']
        if 'year' in data:
            vehicle.year = data['year']
        if 'area_id' in data:
            vehicle.area_id = data['area_id']
        if 'is_active' in data:
            vehicle.is_active = data['is_active']

        vehicle.updated_at = datetime.utcnow()
        db.session.commit()

        return jsonify(vehicle.to_dict())

    # ==================== MILEAGE ====================

    @app.route('/api/mileage/daily', methods=['GET'])
    def get_daily_mileage():
        """
        Get daily mileage records
        Query params: vehicle_id, start_date, end_date, status
        """
        query = DailyMileage.query

        # Filters
        vehicle_id = request.args.get('vehicle_id', type=int)
        if vehicle_id:
            query = query.filter_by(vehicle_id=vehicle_id)

        start_date = request.args.get('start_date')
        if start_date:
            query = query.filter(DailyMileage.date >= start_date)

        end_date = request.args.get('end_date')
        if end_date:
            query = query.filter(DailyMileage.date <= end_date)

        status = request.args.get('status')
        if status:
            query = query.filter_by(calculation_status=status)

        # Pagination
        page = request.args.get('page', 1, type=int)
        per_page = request.args.get('per_page', 50, type=int)

        pagination = query.order_by(DailyMileage.date.desc()).paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )

        return jsonify({
            'items': [m.to_dict() for m in pagination.items],
            'total': pagination.total,
            'page': page,
            'per_page': per_page,
            'pages': pagination.pages
        })

    @app.route('/api/mileage/summary', methods=['GET'])
    def get_mileage_summary():
        """
        Get mileage summary for a vehicle
        Query params: vehicle_id (required), start_date, end_date
        """
        vehicle_id = request.args.get('vehicle_id', type=int)
        if not vehicle_id:
            return jsonify({'error': 'vehicle_id is required'}), 400

        vehicle = db.session.get(Vehicle, vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404

        # Date range (default: last 30 days)
        end_date = request.args.get('end_date')
        if end_date:
            end_date = datetime.fromisoformat(end_date).date()
        else:
            end_date = datetime.now().date()

        start_date = request.args.get('start_date')
        if start_date:
            start_date = datetime.fromisoformat(start_date).date()
        else:
            start_date = end_date - timedelta(days=30)

        # Query mileage records
        records = DailyMileage.query.filter(
            DailyMileage.vehicle_id == vehicle_id,
            DailyMileage.date >= start_date,
            DailyMileage.date <= end_date,
            DailyMileage.calculation_status == 'success'
        ).order_by(DailyMileage.date).all()

        total_km = sum(r.km_driven for r in records)
        avg_km = total_km / len(records) if records else 0

        return jsonify({
            'vehicle': vehicle.to_dict(),
            'period': {
                'start': start_date.isoformat(),
                'end': end_date.isoformat()
            },
            'summary': {
                'total_km': round(total_km, 2),
                'average_km_per_day': round(avg_km, 2),
                'total_days': len(records)
            },
            'daily_records': [r.to_dict() for r in records]
        })

    # ==================== TASKS/JOBS ====================

    @app.route('/api/jobs/calculate-mileage', methods=['POST'])
    def trigger_calculate_mileage():
        """
        Manually trigger mileage calculation
        Body: { "vehicle_id": int (optional), "date": "YYYY-MM-DD" (optional) }
        """
        data = request.get_json() or {}
        vehicle_id = data.get('vehicle_id')
        target_date = data.get('date')

        if vehicle_id:
            # Single vehicle
            from tasks import calculate_vehicle_mileage
            task = calculate_vehicle_mileage.delay(vehicle_id, target_date)
        else:
            # All vehicles
            from tasks import calculate_daily_mileage_all
            task = calculate_daily_mileage_all.delay(target_date)

        return jsonify({
            'success': True,
            'task_id': task.id,
            'status_url': f'/api/jobs/status/{task.id}'
        }), 202

    @app.route('/api/jobs/status/<task_id>', methods=['GET'])
    def get_task_status(task_id):
        """Get Celery task status"""
        task = app.celery.AsyncResult(task_id)

        response = {
            'task_id': task_id,
            'status': task.state,
            'ready': task.ready()
        }

        if task.state == 'PROGRESS':
            response['progress'] = task.info
        elif task.state == 'SUCCESS':
            response['result'] = task.result
        elif task.state == 'FAILURE':
            response['error'] = str(task.info)

        return jsonify(response)

    @app.route('/api/jobs/sync-logs', methods=['GET'])
    def get_sync_logs():
        """Get synchronization logs"""
        page = request.args.get('page', 1, type=int)
        per_page = request.args.get('per_page', 20, type=int)

        pagination = SyncLog.query.order_by(
            SyncLog.started_at.desc()
        ).paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )

        return jsonify({
            'items': [log.to_dict() for log in pagination.items],
            'total': pagination.total,
            'page': page,
            'per_page': per_page,
            'pages': pagination.pages
        })

    # ==================== AREAS ====================

    @app.route('/api/areas', methods=['GET'])
    def get_areas():
        """Get all areas"""
        areas = Area.query.all()
        return jsonify([a.to_dict() for a in areas])

    @app.route('/api/areas', methods=['POST'])
    def create_area():
        """Create new area"""
        data = request.get_json()

        area = Area(
            name=data['name'],
            geo_entity_id=data.get('geo_entity_id')
        )

        db.session.add(area)
        db.session.commit()

        return jsonify(area.to_dict()), 201

    # ==================== MAINTENANCE ALERTS ====================

    @app.route('/api/maintenance-alerts/generate', methods=['POST'])
    def trigger_generate_alerts():
        """
        Manualmente dispara a geração de alertas de manutenção
        Útil para testar ou forçar recálculo após importação de dados
        """
        from tasks import generate_maintenance_alerts
        task = generate_maintenance_alerts.delay()

        return jsonify({
            'success': True,
            'message': 'Geração de alertas iniciada',
            'task_id': task.id,
            'status_url': f'/api/jobs/status/{task.id}'
        }), 202

    @app.route('/api/maintenance-alerts/notify', methods=['POST'])
    def trigger_send_notifications():
        """
        Manualmente dispara o envio de notificações de alertas críticos
        """
        from tasks import send_alert_notifications
        task = send_alert_notifications.delay()

        return jsonify({
            'success': True,
            'message': 'Envio de notificações iniciado',
            'task_id': task.id,
            'status_url': f'/api/jobs/status/{task.id}'
        }), 202

    @app.route('/api/maintenance-alerts/sync-km', methods=['POST'])
    def trigger_sync_km():
        """
        Manualmente dispara sincronização de KM + geração de alertas
        """
        from tasks import sync_all_vehicles_mileage
        task = sync_all_vehicles_mileage.delay()

        return jsonify({
            'success': True,
            'message': 'Sincronização de KM iniciada (alertas serão gerados automaticamente após)',
            'task_id': task.id,
            'status_url': f'/api/jobs/status/{task.id}'
        }), 202

    @app.route('/api/maintenance-alerts/health', methods=['GET'])
    def get_alerts_health():
        """
        Verifica saúde do sistema de alertas
        """
        from services.alerts_service import alerts_service
        from services.notification_service import notification_service

        # Testar conexões
        notification_health = notification_service.test_connection()

        # Buscar estatísticas de alertas via API PHP
        try:
            import requests
            response = requests.get(
                'https://floripa.in9automacao.com.br/avisos-manutencao-api.php',
                params={'limit': 1},
                timeout=10
            )
            if response.status_code == 200:
                result = response.json()
                stats = result.get('data', {}).get('stats', {})
                alerts_health = {
                    'status': 'OK',
                    'stats': stats
                }
            else:
                alerts_health = {
                    'status': 'ERROR',
                    'http_code': response.status_code
                }
        except Exception as e:
            alerts_health = {
                'status': 'ERROR',
                'error': str(e)
            }

        return jsonify({
            'status': 'healthy' if alerts_health.get('status') == 'OK' else 'degraded',
            'alerts': alerts_health,
            'notification_apis': notification_health,
            'timestamp': datetime.utcnow().isoformat()
        })

    # Error handlers
    @app.errorhandler(404)
    def not_found(error):
        return jsonify({'error': 'Not found'}), 404

    @app.errorhandler(500)
    def internal_error(error):
        db.session.rollback()
        return jsonify({'error': 'Internal server error'}), 500


# Create app instance
app = create_app()


if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5001,
        debug=app.config['DEBUG']
    )
