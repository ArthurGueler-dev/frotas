#!/bin/bash
# Initialize database with migrations

echo "🗄️  Initializing Database"
echo "======================="

# Activate virtual environment
source venv/bin/activate

# Initialize Flask-Migrate (if not done)
if [ ! -d "migrations" ]; then
    echo "📝 Initializing Flask-Migrate..."
    flask db init
fi

# Create migration
echo "📝 Creating migration..."
flask db migrate -m "Initial migration"

# Apply migration
echo "🔄 Applying migrations..."
flask db upgrade

echo ""
echo "✅ Database initialized!"
echo ""
echo "Tables created:"
python -c "
from app import app, db
with app.app_context():
    tables = db.metadata.tables.keys()
    for table in tables:
        print(f'  - {table}')
"
