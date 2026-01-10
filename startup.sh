#!/bin/bash

echo "Starting application..."

# Set working directory
cd /home/site/wwwroot

# Ensure storage directories exist and have correct permissions
mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache

# Clear and optimize caches
echo "Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Application started successfully!"
