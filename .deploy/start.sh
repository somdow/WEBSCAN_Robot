#!/bin/bash
set -e

cd /app

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor/supervisord.conf
