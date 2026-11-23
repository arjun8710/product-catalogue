# Local-development image for the Laravel API.
# Production would differ: no dev dependencies, php-fpm/FrankenPHP instead of
# `artisan serve`, and config/route caching. See DECISIONS.md.
FROM php:8.3-cli-alpine

COPY --from=composer:2.11 /usr/bin/composer /usr/bin/composer

# Networks that inspect TLS (e.g. corporate Zscaler) need their root CA trusted
# before Composer can download packages. Put the .crt in docker/certs/ (git-ignored).
# The directory is empty by default, so this is a no-op for everyone else.
# (Alpine's update-ca-certificates ignores subdirectories, so copy to the top level.)
COPY docker/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates

WORKDIR /app

# Install dependencies first so this layer is cached until composer.lock changes.
# Dev dependencies are kept so the test suite can run inside the container.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data /app

USER www-data

ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
# --no-reload: without it, `artisan serve` drops most environment variables and
# re-reads .env, silently ignoring values set by docker compose (e.g. MAIL_HOST).
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
