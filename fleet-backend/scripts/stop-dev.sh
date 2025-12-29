#!/bin/bash
# Stop all development services

echo "🛑 Stopping Fleet Management Backend"
echo "===================================="

# Read PIDs from file
if [ -f ".dev_pids" ]; then
    read -r API_PID WORKER_PID BEAT_PID FLOWER_PID < .dev_pids

    echo "Stopping services..."

    [ ! -z "$API_PID" ] && kill $API_PID 2>/dev/null && echo "  ✅ Stopped Flask API (PID: $API_PID)"
    [ ! -z "$WORKER_PID" ] && kill $WORKER_PID 2>/dev/null && echo "  ✅ Stopped Celery Worker (PID: $WORKER_PID)"
    [ ! -z "$BEAT_PID" ] && kill $BEAT_PID 2>/dev/null && echo "  ✅ Stopped Celery Beat (PID: $BEAT_PID)"
    [ ! -z "$FLOWER_PID" ] && kill $FLOWER_PID 2>/dev/null && echo "  ✅ Stopped Flower (PID: $FLOWER_PID)"

    rm .dev_pids
else
    echo "⚠️  No PID file found. Killing by port..."
    lsof -ti:5001 | xargs kill -9 2>/dev/null
    lsof -ti:5555 | xargs kill -9 2>/dev/null
    pkill -f "celery.*celery_app" 2>/dev/null
fi

echo ""
echo "✅ All services stopped"
