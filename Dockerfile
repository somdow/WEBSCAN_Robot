FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions \
    intl \
    bcmath \
    zip \
    pdo_mysql \
    gd \
    curl \
    mbstring \
    xml \
    opcache \
    pcntl \
    redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN npm run build \
    && rm -rf node_modules

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN cp .env.example .env || true

RUN php artisan package:discover --ansi

EXPOSE ${PORT:-8080}

ENTRYPOINT ["sh", "-c", "\
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan storage:link 2>/dev/null; \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
