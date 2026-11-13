#!/usr/bin/env bash
# Jalankan di VPS: bash deploy.sh
set -euo pipefail

PHP=php8.4
BRANCH=main
QUEUE_SERVICE=rp-queue

cd "$(dirname "$(readlink -f "$0")")"

trap '$PHP artisan up || true' EXIT

echo "==> Mode maintenance"
$PHP artisan down --retry=60 || true

echo "==> Ambil kode terbaru"
git pull --ff-only origin "$BRANCH"

echo "==> Install dependency PHP"
$PHP "$(command -v composer)" install --no-dev --optimize-autoloader --no-interaction

echo "==> Build frontend"
npm ci
npm run build

echo "==> Migrasi database"
$PHP artisan migrate --force

echo "==> Refresh cache"
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "==> Perbaiki permission"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Restart service"
systemctl reload "${PHP}-fpm"
if systemctl list-unit-files | grep -q "^${QUEUE_SERVICE}.service"; then
    systemctl restart "$QUEUE_SERVICE"
fi

echo "==> Selesai"
