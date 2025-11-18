#!/bin/bash
set -e # Exit immediately if a command exits with a non-zero status.

echo "Starting custom startup script..."

# Check if the Nginx config file exists
if [ ! -f "/etc/nginx/sites-available/default" ]; then
    echo "Nginx default config file not found!"
    exit 1
fi

echo "Original Nginx config:"
cat /etc/nginx/sites-available/default

# Set the document root
echo "Attempting to change Nginx document root..."
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default

# Verify the change
echo "Modified Nginx config:"
cat /etc/nginx/sites-available/default

# Test Nginx configuration
echo "Testing Nginx configuration..."
nginx -t

# Reload Nginx to apply changes
echo "Reloading Nginx..."
nginx -s reload

# Start PHP-FPM
echo "Starting php-fpm..."
php-fpm

echo "Custom startup script finished."
