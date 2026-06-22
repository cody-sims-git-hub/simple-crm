# ── Stage 1: compile front-end assets (Tailwind/Vite) ──
# Produces public/build (gitignored), copied into the runtime image below.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund
COPY . .
RUN npm run build

# ── Stage 2: production image for SimpleCRM — nginx + php-fpm tuned for Laravel.
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

# Bring in the compiled front-end assets from the build stage. public/build is
# gitignored/dockerignored, so it is supplied here rather than from the context.
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build

USER www-data
WORKDIR /var/www/html

# Install production dependencies and optimize the autoloader.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress
