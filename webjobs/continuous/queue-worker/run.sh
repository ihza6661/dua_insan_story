#!/bin/bash

echo "Starting Queue Worker..."

cd /home/site/wwwroot

# Run queue worker with restart on failure
while true; do
    echo "[$(date)] Queue worker starting..."
    php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
    
    # If the worker exits, wait a bit before restarting
    echo "[$(date)] Queue worker stopped. Restarting in 5 seconds..."
    sleep 5
done
