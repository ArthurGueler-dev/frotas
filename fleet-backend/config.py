"""
Fleet Management - Configuration
"""
import os
from datetime import timedelta
from dotenv import load_dotenv

load_dotenv()


class Config:
    """Base configuration"""

    # Flask
    SECRET_KEY = os.getenv('SECRET_KEY', 'dev-secret-key-change-me')
    DEBUG = os.getenv('DEBUG', 'False').lower() == 'true'

    # Database
    SQLALCHEMY_DATABASE_URI = os.getenv(
        'DATABASE_URL',
        'sqlite:///fleet.db'
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    SQLALCHEMY_ECHO = DEBUG

    # Redis & Celery
    REDIS_URL = os.getenv('REDIS_URL', 'redis://localhost:6379/0')
    CELERY_BROKER_URL = os.getenv('CELERY_BROKER_URL', REDIS_URL)
    CELERY_RESULT_BACKEND = os.getenv('CELERY_RESULT_BACKEND', REDIS_URL)

    # Celery configuration
    CELERY_CONFIG = {
        'broker_url': CELERY_BROKER_URL,
        'result_backend': CELERY_RESULT_BACKEND,
        'task_serializer': 'json',
        'accept_content': ['json'],
        'result_serializer': 'json',
        'timezone': os.getenv('TIMEZONE', 'America/Sao_Paulo'),
        'enable_utc': True,
        'task_track_started': True,
        'task_time_limit': 30 * 60,  # 30 minutes
        'task_soft_time_limit': 25 * 60,  # 25 minutes
        'worker_prefetch_multiplier': 1,
        'worker_max_tasks_per_child': 1000,
        # Resiliência e retry
        'task_acks_late': True,  # Acknowledge tasks after completion
        'task_reject_on_worker_lost': True,  # Requeue if worker crashes
        'broker_connection_retry': True,
        'broker_connection_retry_on_startup': True,
        'broker_connection_max_retries': 10,
    }

    # Ituran API
    ITURAN_USERNAME = os.getenv('ITURAN_USERNAME')
    ITURAN_PASSWORD = os.getenv('ITURAN_PASSWORD')
    ITURAN_SERVICE3_URL = os.getenv('ITURAN_SERVICE3_URL')
    ITURAN_MOBILE_URL = os.getenv('ITURAN_MOBILE_URL')

    # Cache timeouts
    CACHE_TIMEOUT_DAILY = 5 * 60  # 5 minutes
    CACHE_TIMEOUT_MONTHLY = 24 * 60 * 60  # 24 hours

    # Data retention
    DATA_RETENTION_YEARS = int(os.getenv('DATA_RETENTION_YEARS', 5))

    # Sync schedule times
    SYNC_TIMES = os.getenv(
        'CELERY_BEAT_SCHEDULE_TIMES',
        '06:00,12:00,18:00,23:59'
    ).split(',')

    # Timezone
    TIMEZONE = os.getenv('TIMEZONE', 'America/Sao_Paulo')

    # Monitoring & Alerts
    ALERT_WEBHOOK_URL = os.getenv('ALERT_WEBHOOK_URL', None)  # Webhook para alertas (Slack, Discord, etc)
    MONITORING_ENABLED = os.getenv('MONITORING_ENABLED', 'true').lower() == 'true'
    AUTO_RECOVERY_ENABLED = os.getenv('AUTO_RECOVERY_ENABLED', 'true').lower() == 'true'

    # Thresholds de alerta
    MAX_SYNC_DELAY_HOURS = int(os.getenv('MAX_SYNC_DELAY_HOURS', 7))  # Alerta se sync atrasado >7h
    MAX_ZERO_KM_PERCENT_WEEKDAY = int(os.getenv('MAX_ZERO_KM_PERCENT_WEEKDAY', 50))  # 50% em dia de semana
    MAX_ZERO_KM_PERCENT_WEEKEND = int(os.getenv('MAX_ZERO_KM_PERCENT_WEEKEND', 70))  # 70% em fim de semana


class DevelopmentConfig(Config):
    """Development configuration"""
    DEBUG = True
    SQLALCHEMY_ECHO = True


class ProductionConfig(Config):
    """Production configuration"""
    DEBUG = False
    SQLALCHEMY_ECHO = False

    # Use strong secret key in production
    SECRET_KEY = os.getenv('SECRET_KEY')
    if not SECRET_KEY:
        raise ValueError("SECRET_KEY must be set in production!")


# Config dictionary
config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'default': DevelopmentConfig
}
