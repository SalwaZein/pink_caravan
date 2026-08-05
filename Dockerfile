# syntax=docker/dockerfile:1

# --- Stage 1: build front-end assets (Vite / Tailwind v4) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- Stage 2: PHP 8.3 runtime ---
FROM php:8.3-cli AS app

# git + unzip for Composer. PHP extensions are installed via mlocati's helper,
# which pulls in each extension's required system libraries automatically
# (mPDF/dompdf: gd, mbstring, zip; DB: sqlite/mysql; intl/bcmath/exif).
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_sqlite mbstring gd zip intl bcmath exif

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (better layer caching).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# App source + the built assets from stage 1.
COPY . .
COPY --from=assets /app/public/build ./public/build

# Build a pre-migrated, pre-seeded SQLite database INTO the image so runtime
# cold starts are fast (no migrate/seed at boot). The free tier wipes the disk
# on every spin-down, so the container always boots from this baked-in DB.
RUN composer dump-autoload --optimize \
    && mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public storage/logs \
    && touch database/database.sqlite \
    && APP_KEY="base64:hsdSks/45/IKKaUi3SmAtoMi+TVjbSPsH+WiNMmG2Fg=" \
       DB_CONNECTION=sqlite DB_DATABASE=/var/www/html/database/database.sqlite \
       php artisan migrate --force --seed \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
CMD ["/usr/local/bin/entrypoint.sh"]
