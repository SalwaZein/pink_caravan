#!/usr/bin/env sh
set -e
cd /var/www/html

# Writable dirs (already in the image, but be safe if a disk gets mounted here).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public storage/logs

# The seeded SQLite database is baked into the image at build time, so we do NOT
# migrate/seed on every boot — that is what made cold starts slow. Only fall back
# to creating & seeding it if it is somehow missing (e.g. an empty mounted disk).
if [ ! -s database/database.sqlite ]; then
  touch database/database.sqlite
  php artisan migrate --force --seed
fi

php artisan storage:link || true

# Fast caches (env vars are read from the host at boot).
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host 0.0.0.0 --port "${PORT:-8080}"
