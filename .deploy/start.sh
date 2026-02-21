#!/bin/bash

cd /app

php artisan migrate --force || echo "Migration failed — check logs"
php artisan db:seed --force || echo "Seeding failed — check logs"
php artisan storage:link --relative 2>/dev/null || php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor/supervisord.conf
