#!/bin/bash
set -e

LOG_FILE="/home/site/wwwroot/startup_log.txt"
echo "Starting custom startup script at $(date)" > "$LOG_FILE" 2>&1

# Check if the Nginx config file exists
if [ ! -f "/etc/nginx/sites-available/default" ]; then
    echo "Nginx default config file not found!" >> "$LOG_FILE" 2>&1
    exit 1
fi

echo "Original Nginx config:" >> "$LOG_FILE" 2>&1
cat /etc/nginx/sites-available/default >> "$LOG_FILE" 2>&1

# Set the document root
echo "Attempting to change Nginx document root..." >> "$LOG_FILE" 2>&1
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default >> "$LOG_FILE" 2>&1

# Add try_files directive for Laravel clean URLs
echo "Adding try_files directive for Laravel..." >> "$LOG_FILE" 2>&1
sed -i '/index  index.php index.html index.htm hostingstart.html;/a\        try_files $uri $uri/ /index.php?$query_string;' /etc/nginx/sites-available/default >> "$LOG_FILE" 2>&1


# Verify the change
echo "Modified Nginx config:" >> "$LOG_FILE" 2>&1
cat /etc/nginx/sites-available/default >> "$LOG_FILE" 2>&1

# Test Nginx configuration
echo "Testing Nginx configuration..." >> "$LOG_FILE" 2>&1
nginx -t >> "$LOG_FILE" 2>&1

# Reload Nginx to apply changes
echo "Reloading Nginx..." >> "$LOG_FILE" 2>&1
nginx -s reload >> "$LOG_FILE" 2>&1

# Start PHP-FPM
echo "Starting php-fpm..." >> "$LOG_FILE" 2>&1
php-fpm >> "$LOG_FILE" 2>&1

echo "Custom startup script finished at $(date)" >> "$LOG_FILE" 2>&1

