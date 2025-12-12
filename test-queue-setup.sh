#!/bin/bash

# Queue Worker Test Script for Digital Invitations
# This script helps test the queue architecture before deployment

echo "======================================"
echo "Queue Infrastructure Test"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Check if queue table exists
echo "1. Checking queue infrastructure..."
if php artisan tinker --execute="echo Schema::hasTable('jobs') ? 'exists' : 'missing';" | grep -q "exists"; then
    echo -e "${GREEN}✓${NC} Jobs table exists"
else
    echo -e "${RED}✗${NC} Jobs table missing - run: php artisan queue:table && php artisan migrate"
    exit 1
fi

# 2. Check if failed_jobs table exists
if php artisan tinker --execute="echo Schema::hasTable('failed_jobs') ? 'exists' : 'missing';" | grep -q "exists"; then
    echo -e "${GREEN}✓${NC} Failed jobs table exists"
else
    echo -e "${YELLOW}!${NC} Failed jobs table missing - recommended: php artisan queue:failed-table && php artisan migrate"
fi

# 3. Check queue connection in .env
echo ""
echo "2. Checking queue configuration..."
QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" .env | cut -d'=' -f2)
echo -e "   Queue connection: ${GREEN}${QUEUE_CONN}${NC}"

if [ "$QUEUE_CONN" = "sync" ]; then
    echo -e "${YELLOW}!${NC} Warning: Using 'sync' driver - jobs will run synchronously"
    echo "   Consider changing to 'database' or 'redis' for production"
fi

# 4. Count pending jobs
echo ""
echo "3. Checking queue status..."
PENDING=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null)
echo "   Pending jobs: ${PENDING}"

# 5. Count failed jobs
FAILED=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null)
if [ "$FAILED" -gt 0 ]; then
    echo -e "   Failed jobs: ${RED}${FAILED}${NC}"
    echo "   Run 'php artisan queue:failed' to see details"
else
    echo -e "   Failed jobs: ${GREEN}${FAILED}${NC}"
fi

# 6. Check if queue worker is running
echo ""
echo "4. Checking queue worker status..."
if pgrep -f "queue:work" > /dev/null; then
    echo -e "${GREEN}✓${NC} Queue worker is running"
    WORKER_PID=$(pgrep -f "queue:work")
    echo "   Process ID: ${WORKER_PID}"
else
    echo -e "${YELLOW}!${NC} Queue worker is NOT running"
    echo "   Start it with: php artisan queue:work"
    echo ""
    echo "   Or in a separate terminal:"
    echo "   cd $(pwd)"
    echo "   php artisan queue:work --verbose --tries=3"
fi

# 7. Test ProcessDigitalInvitations job exists
echo ""
echo "5. Verifying job class..."
if [ -f "app/Jobs/ProcessDigitalInvitations.php" ]; then
    echo -e "${GREEN}✓${NC} ProcessDigitalInvitations job exists"
else
    echo -e "${RED}✗${NC} ProcessDigitalInvitations job missing"
    exit 1
fi

# 8. Summary
echo ""
echo "======================================"
echo "Summary"
echo "======================================"
echo ""

if pgrep -f "queue:work" > /dev/null; then
    echo -e "${GREEN}✓ System ready for testing!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Test a digital product purchase"
    echo "2. Check logs: tail -f storage/logs/laravel.log"
    echo "3. Monitor notifications table"
else
    echo -e "${YELLOW}⚠ Action required:${NC}"
    echo ""
    echo "Start the queue worker in a separate terminal:"
    echo ""
    echo "  cd $(pwd)"
    echo "  php artisan queue:work --verbose --tries=3"
    echo ""
    echo "Then test a purchase with digital products."
fi

echo ""
echo "Useful commands:"
echo "  php artisan queue:monitor              # View queue status"
echo "  php artisan queue:failed               # View failed jobs"
echo "  php artisan queue:retry all            # Retry all failed jobs"
echo "  php artisan queue:flush                # Clear failed jobs"
echo "  tail -f storage/logs/laravel.log       # Watch logs"
echo ""
