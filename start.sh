#!/bin/sh

echo "Fixing permissions..."

mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Creating storage link..."
php artisan storage:link --force || true

php-fpm -D
nginx -g 'daemon off;'