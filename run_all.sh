#!/bin/bash

echo "🚀 Starting all retribusi repositories..."

# Function to kill all background processes on exit
cleanup() {
    echo "🛑 Stopping all services..."
    kill $(jobs -p)
    exit
}

trap cleanup SIGINT SIGTERM

# Start Backend API
echo "📦 Starting API (Port 8000)..."
cd /Users/pondokit/Herd/retribusi-api && php artisan serve --port=8000 > /dev/null 2>&1 &

# Wait for API to start
sleep 2

# Start Admin Frontend
echo "🖥️ Starting Admin (Port 3004)..."
cd /Users/pondokit/Herd/retribusi-admin && npm run dev > /dev/null 2>&1 &

# Start Mobile Frontend
echo "📱 Starting Mobile (Port 3002)..."
cd /Users/pondokit/Herd/retribusi-mobile && npm run dev > /dev/null 2>&1 &

# Start Petugas Frontend
echo "👮 Starting Petugas (Port 3003)..."
cd /Users/pondokit/Herd/retribusi-petugas && npm run dev > /dev/null 2>&1 &

echo "✨ All services are starting in the background."
echo "   - API: http://localhost:8000"
echo "   - Admin: http://localhost:3004"
echo "   - Mobile: http://localhost:3002"
echo "   - Petugas: http://localhost:3003"
echo "Press Ctrl+C to stop all services."

wait
