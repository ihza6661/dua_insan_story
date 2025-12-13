#!/bin/bash

# Queue Worker Startup Script for Dua Insan Story
# Usage: ./start-queue-worker.sh [foreground|background]

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Dua Insan Story - Queue Worker Start    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
echo ""

# Check if already running
if pgrep -f "queue:work" > /dev/null; then
    echo -e "${YELLOW}⚠️  Queue worker is already running!${NC}"
    echo ""
    echo "Process details:"
    ps aux | grep "queue:work" | grep -v grep
    echo ""
    echo "To stop it, run: kill \$(pgrep -f 'queue:work')"
    exit 1
fi

# Default mode
MODE="${1:-foreground}"

if [ "$MODE" = "background" ]; then
    echo -e "${GREEN}🚀 Starting queue worker in BACKGROUND mode...${NC}"
    echo ""
    
    # Start in background
    nohup php artisan queue:work --tries=3 --timeout=120 > storage/logs/queue-worker.log 2>&1 &
    
    sleep 2
    
    if pgrep -f "queue:work" > /dev/null; then
        PID=$(pgrep -f "queue:work")
        echo -e "${GREEN}✅ Queue worker started successfully!${NC}"
        echo ""
        echo "Process ID: $PID"
        echo "Log file: storage/logs/queue-worker.log"
        echo ""
        echo "To view logs: tail -f storage/logs/queue-worker.log"
        echo "To stop: kill $PID"
    else
        echo -e "${RED}❌ Failed to start queue worker${NC}"
        exit 1
    fi
    
elif [ "$MODE" = "foreground" ]; then
    echo -e "${GREEN}🚀 Starting queue worker in FOREGROUND mode...${NC}"
    echo -e "${YELLOW}ℹ️  Press Ctrl+C to stop${NC}"
    echo ""
    
    # Start in foreground
    php artisan queue:work --tries=3 --timeout=120 --verbose
    
else
    echo -e "${RED}❌ Invalid mode: $MODE${NC}"
    echo "Usage: $0 [foreground|background]"
    exit 1
fi
