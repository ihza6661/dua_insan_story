#!/bin/bash

# Database Migration Script for Azure App Service
# Run this inside the SSH session

echo "=========================================="
echo "Database Setup for Dua Insan Story"
echo "=========================================="
echo ""

# Navigate to app directory
cd /home/site/wwwroot

echo "Step 1: Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
echo "✓ Permissions set"
echo ""

echo "Step 2: Creating storage symlink..."
php artisan storage:link --force
echo "✓ Storage link created"
echo ""

echo "Step 3: Testing database connection..."
php artisan tinker --execute="echo 'DB Test: '; DB::connection()->getPdo(); echo 'Connected!';"
if [ $? -eq 0 ]; then
    echo "✓ Database connection successful"
else
    echo "✗ Database connection failed. Check your DB credentials."
    exit 1
fi
echo ""

echo "Step 4: Running migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo "✓ Migrations completed"
else
    echo "✗ Migrations failed"
    exit 1
fi
echo ""

echo "Step 5: Seeding production data..."
echo "  - Admin user..."
php artisan db:seed --class=AdminUserSeeder --force

echo "  - Product categories..."
php artisan db:seed --class=ProductCategorySeeder --force

echo "  - Attributes..."
php artisan db:seed --class=AttributeSeeder --force

echo "  - Add-ons..."
php artisan db:seed --class=AddOnSeeder --force

echo "  - Products..."
php artisan db:seed --class=ProductsTableSeeder --force

echo "  - Product variants..."
php artisan db:seed --class=ProductVariantsTableSeeder --force

echo "  - Product images..."
php artisan db:seed --class=ProductImageSeeder --force

echo "  - Invitation templates..."
php artisan db:seed --class=InvitationTemplateSeeder --force

echo "  - Template fields..."
php artisan db:seed --class=TemplateFieldSeeder --force

echo "✓ All seeders completed"
echo ""

echo "Step 6: Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Application optimized"
echo ""

echo "=========================================="
echo "✅ Database setup complete!"
echo "=========================================="
echo ""
echo "Default admin credentials:"
echo "  Email: admin@duainsan.story"
echo "  Password: password"
echo ""
echo "⚠️  Remember to change the password!"
