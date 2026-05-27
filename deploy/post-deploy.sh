#!/bin/bash
# Plesk Git post-deploy hook for triad-kz.
#
# Set this script as "Additional deployment actions" in Plesk → Domains →
# dev.triad.kz → Git → (your repo) → Settings → Additional deployment actions.
#
# Plesk runs commands inside the repository checkout directory (which IS
# the document parent — dev.triad.kz/), as the system user (triadkz).
#
# Composer must be available on PATH. Plesk ships Composer at:
#   /opt/plesk/php/8.4/bin/php /usr/lib64/plesk-9.0/composer.phar
# But typically Plesk's "Composer" panel installs it as `composer` in PATH.
#
# This script is idempotent — safe to run on every deploy.

set -e

echo "→ Detecting PHP CLI binary"
PHP_BIN="${PHP_BIN:-/opt/plesk/php/8.4/bin/php}"
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php || echo php)"
fi
echo "  Using: $PHP_BIN"
$PHP_BIN -v | head -1

echo ""
echo "→ composer install (production)"
# --no-dev: don't install pest/larastan/pint on the server
# --optimize-autoloader: classmap auth for max speed
# --no-progress: shorter logs in Plesk deployment-action output
# --classmap-authoritative: no further classmap rebuilds at runtime
composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative

echo ""
echo "→ artisan migrate --force"
# --force: required in non-interactive/production environments
$PHP_BIN artisan migrate --force

echo ""
echo "→ artisan storage:link"
# Creates public/storage → storage/app/public symlink. Idempotent.
$PHP_BIN artisan storage:link --force || true

echo ""
echo "→ Cache rebuild"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Filament has its own caches (icons, components, blade-components).
$PHP_BIN artisan filament:cache-components || true
$PHP_BIN artisan icons:cache || true

echo ""
echo "✓ post-deploy completed"
