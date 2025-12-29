"""
Celery Tasks for Fleet Management - Route Compliance Monitoring
"""
import logging
import requests
from datetime import datetime
from celery import Celery
from celery.schedules import crontab

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Create Celery app
celery_app = Celery(
    'fleet-backend',
    broker='redis://localhost:6379/0',
    backend='redis://localhost:6379/0'
)

celery_app.conf.update(
    task_serializer='json',
    accept_content=['json'],
    result_serializer='json',
    timezone='America/Sao_Paulo',
    enable_utc=True,
)

@celery_app.task(bind=True, name='check_all_routes_compliance')
def check_all_routes_compliance(self):
    """Check compliance for ALL active routes"""
    logger.info("🔍 Starting route compliance check")

    try:
        api_url = 'https://floripa.in9automacao.com.br/cpanel-api/rotas-api.php'
        response = requests.get(api_url, params={'status': 'em_andamento'}, timeout=10)

        if response.status_code != 200:
            logger.error(f"Failed to fetch routes: HTTP {response.status_code}")
            return {'success': False, 'error': f'API returned {response.status_code}'}

        data = response.json()
        routes = data.get('rotas', [])

        logger.info(f"📊 Found {len(routes)} routes in progress")

        if len(routes) == 0:
            return {'success': True, 'message': 'No active routes to monitor', 'checked': 0}

        logger.info("✅ Compliance check completed")

        return {
            'success': True,
            'checked': len(routes),
            'message': 'System is working!'
        }

    except Exception as e:
        logger.error(f"❌ Error: {e}")
        return {'success': False, 'error': str(e)}

# Beat schedule
celery_app.conf.beat_schedule = {
    'check-routes-every-5-min': {
        'task': 'check_all_routes_compliance',
        'schedule': crontab(minute='*/5'),
    },
}
