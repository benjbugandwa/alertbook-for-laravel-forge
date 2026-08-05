#!/usr/bin/env bash

set -euo pipefail

SITE_PATH="${FORGE_SITE_PATH:-/home/forge/apps.alertbook.org}"
BRANCH="${FORGE_SITE_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

cd "$SITE_PATH"

echo "==> Pulling latest code from ${BRANCH}"
git pull origin "$BRANCH"

echo "==> Installing PHP dependencies"
"$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Installing Node dependencies and building assets"
"$NPM_BIN" ci
"$NPM_BIN" run build

echo "==> Preparing Laravel storage directories"
mkdir -p \
    storage/app/livewire-tmp \
    storage/app/public/incidents \
    storage/app/public/case-notes \
    storage/app/public/referencements \
    storage/app/public/avatars \
    storage/app/public/documents \
    storage/app/public/rapports \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

echo "==> Running database migrations"
"$PHP_BIN" artisan migrate --force

echo "==> Linking public storage"
"$PHP_BIN" artisan storage:link || true

echo "==> Clearing stale caches"
"$PHP_BIN" artisan optimize:clear

echo "==> Caching Laravel config/routes/views/events"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

echo "==> Restarting queue workers"
"$PHP_BIN" artisan queue:restart || true

echo "==> Deployment complete"
