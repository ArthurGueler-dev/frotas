"""
Celery Worker Entrypoint
Run: celery -A celery_app worker --loglevel=info
"""
from app import create_app

# Create Flask app
flask_app = create_app()

# Get Celery instance
celery = flask_app.celery
