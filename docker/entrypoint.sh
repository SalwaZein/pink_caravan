#!/usr/bin/env sh
set -e
cd /var/www/html

# Writable dirs + SQLite database file (ephemeral on free hosting — fine for a demo).
mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public storage/logs
touch database/database.sqlite

# Fresh, seeded demo data on every boot (seeders are idempotent; disk resets anyway).
php artisan config:clear
php artisan migrate:fresh --seed --force
php artisan storage:link || true

# Cache for speed (env vars are read at boot from the host).
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host 0.0.0.0 --port "${PORT:-8080}"
