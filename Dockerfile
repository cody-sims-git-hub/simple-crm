# Production image for SimpleCRM — nginx + php-fpm tuned for Laravel.
# serversideup/php serves public/ on port 8080 as the non-root www-data user.
FROM serversideup/php:8.4-fpm-nginx

# Ensure the SQLite PDO driver is present, and pre-create the data directory.
# The empty DB file seeds the named volume on first run (owned by www-data),
# so Laravel migrations have a file to run against.
USER root
RUN install-php-extensions pdo_sqlite \
 && mkdir -p /data \
 && touch /data/database.sqlite \
 && chown -R www-data:www-data /data

# Copy the application source (vendor/, .env, dev artifacts excluded via .dockerignore).
COPY --chown=www-data:www-data . /var/www/html

USER www-data
WORKDIR /var/www/html

# Install production dependencies and optimize the autoloader.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress
