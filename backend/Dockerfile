# syntax=docker/dockerfile:1

# =====================================================================
# Stage 1 (vendor): pasang dependensi Composer (tanpa paket dev)
# =====================================================================
FROM composer:2.9.8 AS vendor
WORKDIR /app

# Layer dependensi: hanya berubah kalau composer.json / composer.lock berubah
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    --no-scripts --no-autoloader --ignore-platform-reqs

# Salin kode aplikasi, lalu bentuk autoloader yang sudah dioptimalkan
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# =====================================================================
# Stage 2 (runtime): FrankenPHP (web server Caddy + PHP)
# =====================================================================
FROM dunglas/frankenphp:1.13.3-php8.3-alpine AS runtime

RUN install-php-extensions pdo_mysql opcache

WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stdout \
    LOG_LEVEL=info \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

COPY --from=vendor /app /app
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

# - normalkan line ending (jaga-jaga file dari Windows/CRLF)
# - package:discover membentuk cache paket (hanya paket production)
# - siapkan folder yang perlu ditulis oleh user non-root
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint /etc/caddy/Caddyfile \
    && chmod +x /usr/local/bin/entrypoint \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
       storage/logs bootstrap/cache /data/caddy /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data /config

USER www-data

EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8000/health > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
