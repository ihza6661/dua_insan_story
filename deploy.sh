#!/bin/bash
set -e

echo "=========================================="
echo "Starting Azure Deployment"
echo "=========================================="

# Navigate to deployment directory
cd /home/site/wwwroot

echo "Installing Composer..."
# Install composer if not exists
if [ ! -f composer.phar ]; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    rm composer-setup.php
fi

echo "Installing dependencies..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction

echo "Setting permissions..."
# Set proper permissions
chmod -R 755 storage bootstrap/cache

echo "Creating storage directories..."
# Create storage directories if they don't exist
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/fonts
mkdir -p storage/app/public

echo "Setting storage permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo "Creating symbolic link for storage..."
# Create symbolic link for storage
php artisan storage:link --force || true

echo "Optimizing application..."
# Run Laravel optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=========================================="
echo "Deployment completed successfully!"
echo "=========================================="
