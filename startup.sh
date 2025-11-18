#!/bin/bash

# Set the document root
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default

# Restart Nginx and start PHP-FPM
service nginx restart
php-fpm
