# Production Deployment Guide

## Server Requirements

- PHP 8.4+ with extensions: `dom`, `curl`, `mbstring`, `zip`, `sqlite3` (or `pdo_mysql` for MySQL)
- Composer 2.x
- Node.js 22+ and npm (for building assets)
- MySQL 8.0+ (recommended) or SQLite
- Web server: Nginx or Apache with `mod_rewrite`
- HTTPS certificate (Let's Encrypt recommended)
- Supervisor (for queue workers)
- Cron access (for Laravel scheduler)

## Initial Setup

### 1. Clone and Install

```bash
git clone <repository-url> /var/www/hello-seo
cd /var/www/hello-seo
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 2. Environment Configuration

```bash
cp .env.production.example .env
php artisan key:generate
```

Edit `.env` with your production values. See `.env.production.example` for annotated reference.

### 3. Database Setup

```bash
# Create MySQL database
mysql -u root -p -e "CREATE DATABASE hello_seo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate --force

# Seed initial data (plans, super admin)
php artisan db:seed --force
```

### 4. File Permissions

```bash
chown -R www-data:www-data /var/www/hello-seo
chmod -R 755 /var/www/hello-seo
chmod -R 775 storage bootstrap/cache
```

### 5. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Web Server Configuration

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/hello-seo/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}
```

## Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/hello-seo-worker.conf`:

```ini
[program:hello-seo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hello-seo/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hello-seo/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hello-seo-worker:*
```

## Scheduler (Cron)

Add to crontab for `www-data`:

```bash
* * * * * cd /var/www/hello-seo && php artisan schedule:run >> /dev/null 2>&1
```

## Stripe Webhook

Register your webhook endpoint in the Stripe Dashboard:

- **URL:** `https://yourdomain.com/stripe/webhook`
- **Events:** `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_succeeded`, `invoice.payment_failed`
- Copy the signing secret to `STRIPE_WEBHOOK_SECRET` in `.env`

## Deployment Updates

```bash
cd /var/www/hello-seo
git pull origin master
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart hello-seo-worker:*
```

## Troubleshooting

- **500 errors:** Check `storage/logs/laravel.log` and ensure `APP_DEBUG=false`
- **Queue not processing:** Verify Supervisor is running: `sudo supervisorctl status`
- **Emails not sending:** Verify SMTP credentials and check mail log
- **Stripe webhooks failing:** Verify `STRIPE_WEBHOOK_SECRET` matches and URL is accessible
- **Assets not loading:** Run `npm run build` and verify `public/build/` directory exists
