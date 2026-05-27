#!/bin/bash
# Plesk Git post-deploy hook for triad-kz.
#
# Set this script as "Additional deployment actions" in Plesk → Domains →
# dev.triad.kz → Git → (your repo) → Settings → Additional deployment actions:
#
#     bash deploy/post-deploy.sh
#
# Plesk runs commands inside the repository checkout directory as the system
# user (triadkz). On Plesk shared hosting `proc_open` is disabled, so
# Composer can NOT run here — we ship a pre-built vendor/ in the repo
# instead. This script only does runtime tasks that pure PHP can perform.

set -e

echo "→ Detecting PHP CLI binary"
PHP_BIN="${PHP_BIN:-/opt/plesk/php/8.4/bin/php}"
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php || echo php)"
fi
echo "  Using: $PHP_BIN"
$PHP_BIN -v | head -1

echo ""
echo "→ artisan migrate --force"
# --force: required in non-interactive/production environments.
# Pure PHP + PDO, no proc_open needed.
$PHP_BIN artisan migrate --force

echo ""
echo "→ artisan storage:link"
# symlink public/storage → storage/app/public. Idempotent.
$PHP_BIN artisan storage:link --force || true

echo ""
echo "→ Cache rebuild (config + route + view)"
# All pure PHP — no subprocesses.
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Filament's own caches.
$PHP_BIN artisan filament:cache-components || true
$PHP_BIN artisan icons:cache || true

echo ""
echo "✓ post-deploy completed (composer skipped — vendor/ ships with repo)"
