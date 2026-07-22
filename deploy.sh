#!/usr/bin/env bash

set -euo pipefail

BRANCH="${1:-production}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Deploying branch: $BRANCH"
echo "==> Application directory: $APP_DIR"

cd "$APP_DIR"

# ============================================
# 1. Pull do código
# ============================================
echo "==> Pulling latest changes..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
git clean -fd

# ============================================
# 2. Variáveis de ambiente
# ============================================
echo "==> Checking .env file..."
if [ ! -f .env ]; then
    echo "==> Creating .env from .env.example..."
    cp .env.example .env
fi

# ============================================
# 3. Dependências PHP
# ============================================
echo "==> Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ============================================
# 4. Dependências e build do frontend
# ============================================
echo "==> Installing frontend dependencies..."
npm ci
echo "==> Building frontend assets..."
npm run build

# ============================================
# 5. Chave da aplicação (se não existir)
# ============================================
echo "==> Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# ============================================
# 6. Database
# ============================================
echo "==> Running database migrations..."
php artisan migrate --force

# ============================================
# 7. Storage link
# ============================================
echo "==> Creating storage link..."
php artisan storage:link --force

# ============================================
# 8. Cache de performance
# ============================================
echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache 2>/dev/null || true

# ============================================
# 9. Limpeza de caches obsoletos
# ============================================
echo "==> Clearing obsolete caches..."
php artisan cache:prune-stale-tags 2>/dev/null || true

# ============================================
# 10. Permissões
# ============================================
echo "==> Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ============================================
# 11. Restart dos serviços
# ============================================
echo "==> Restarting services..."

# Queue worker
(pkill -f "artisan queue:work" 2>/dev/null && sleep 2) || true
nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 > /dev/null 2>&1 &

# Scheduler (via cron, mas forçando execução imediata)
php artisan schedule:run --no-interaction 2>/dev/null &

# PHP-FPM (descomente conforme seu setup)
# sudo systemctl restart php8.3-fpm

# Nginx (descomente conforme seu setup)
# sudo systemctl restart nginx

# Supervisor (se usar para queue workers)
# sudo supervisorctl restart all

echo "==> Deploy complete!"
