from app import create_app

# Create Flask app
flask_app = create_app()

# Get celery instance from app
celery = flask_app.celery
