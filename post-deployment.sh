#!/bin/bash

# Post-deployment script for Azure
# This runs automatically after deployment

echo "Starting post-deployment tasks..."

cd /home/site/wwwroot

# Set permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Create storage link
echo "Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# Check if migrations have been run
echo "Checking database..."
php artisan migrate:status 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Running migrations..."
    php artisan migrate --force
    
    echo "Seeding database..."
    php artisan db:seed --class=AdminUserSeeder --force
    php artisan db:seed --class=ProductCategorySeeder --force
    php artisan db:seed --class=AttributeSeeder --force
    php artisan db:seed --class=AddOnSeeder --force
    php artisan db:seed --class=ProductsTableSeeder --force
    php artisan db:seed --class=ProductVariantsTableSeeder --force
    php artisan db:seed --class=ProductImageSeeder --force
    php artisan db:seed --class=InvitationTemplateSeeder --force
    php artisan db:seed --class=TemplateFieldSeeder --force
fi

# Optimize
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Post-deployment tasks complete!"
