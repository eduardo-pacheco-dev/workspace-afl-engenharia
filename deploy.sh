#!/usr/bin/env bash

set -euo pipefail

BRANCH="${1:-production}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Deploying branch: $BRANCH"
echo "==> Application directory: $APP_DIR"

cd "$APP_DIR"

echo "==> Pulling latest changes..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
git clean -fd

echo "==> Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Installing frontend dependencies..."
npm ci
echo "==> Building frontend assets..."
npm run build

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Creating storage link..."
php artisan storage:link --force

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Restarting services..."
# Descomente as linhas conforme seu setup:
# sudo systemctl restart php8.3-fpm
# sudo systemctl restart nginx
# sudo systemctl restart queue:work
# sudo supervisorctl restart all

echo "==> Deploy complete!"
