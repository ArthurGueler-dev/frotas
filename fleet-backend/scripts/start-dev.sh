#!/bin/bash
# Start all services for development

echo "🚀 Starting Fleet Management Backend (Development)"
echo "=================================================="

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "❌ Virtual environment not found. Run: python -m venv venv"
    exit 1
fi

# Activate virtual environment
source venv/bin/activate

# Check Redis
echo ""
echo "🔍 Checking Redis..."
if ! redis-cli ping > /dev/null 2>&1; then
    echo "❌ Redis is not running!"
    echo "   Start Redis: redis-server"
    exit 1
fi
echo "✅ Redis is running"

# Check database
echo ""
echo "🔍 Checking database..."
if ! python -c "from app import db; db.session.execute('SELECT 1')" > /dev/null 2>&1; then
    echo "⚠️  Database connection failed. Running migrations..."
    flask db upgrade
fi
echo "✅ Database is ready"

# Start services in separate terminals/processes
echo ""
echo "🚀 Starting services..."

# Kill any existing processes on ports
lsof -ti:5001 | xargs kill -9 2>/dev/null
lsof -ti:5555 | xargs kill -9 2>/dev/null

# Start Flask API
echo "  📡 Starting Flask API (port 5001)..."
python app.py &
API_PID=$!

sleep 2

# Start Celery Worker
echo "  ⚙️  Starting Celery Worker..."
celery -A celery_app worker --loglevel=info &
WORKER_PID=$!

# Start Celery Beat
echo "  📅 Starting Celery Beat..."
celery -A celery_app beat --loglevel=info &
BEAT_PID=$!

# Start Flower
echo "  🌸 Starting Flower (port 5555)..."
celery -A celery_app flower --port=5555 &
FLOWER_PID=$!

echo ""
echo "=================================================="
echo "✅ All services started!"
echo ""
echo "📡 Flask API: http://localhost:5001"
echo "🌸 Flower:    http://localhost:5555"
echo ""
echo "PIDs:"
echo "  API:    $API_PID"
echo "  Worker: $WORKER_PID"
echo "  Beat:   $BEAT_PID"
echo "  Flower: $FLOWER_PID"
echo ""
echo "To stop all services, run: ./scripts/stop-dev.sh"
echo "=================================================="

# Save PIDs to file for stop script
echo "$API_PID $WORKER_PID $BEAT_PID $FLOWER_PID" > .dev_pids

# Wait for interrupt
wait
