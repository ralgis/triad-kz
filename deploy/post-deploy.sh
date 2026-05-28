#!/bin/bash
# Plesk Git post-deploy hook for triad-kz.
#
# Set this script as "Additional deployment actions" in Plesk → Domains →
# dev.triad.kz → Git → (your repo) → Settings → Additional deployment actions:
#
#     bash deploy/post-deploy.sh
#
# Plesk hides stdout/stderr of git deploy hooks in its UI, and on this
# hosting plan user Scheduled Tasks appear restricted (commands exit
# instantly with "errors"). So we tee everything to storage/logs/deploy.log
# — readable via Plesk File Manager.

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOG="$REPO_ROOT/storage/logs/deploy.log"
mkdir -p "$(dirname "$LOG")"

# Truncate, then duplicate every line into the log AND the original stdout.
exec > >(tee "$LOG") 2>&1

echo "=== post-deploy started $(date '+%Y-%m-%d %H:%M:%S') ==="
echo "REPO_ROOT=$REPO_ROOT"
echo "PWD=$(pwd)"
echo "USER=$(whoami 2>/dev/null || id -un)"
echo "SHELL=$SHELL"
echo "PATH=$PATH"
echo ""

echo "→ Probing available PHP CLI binaries"
PHP_BIN=""
for cand in \
    /opt/plesk/php/8.4/bin/php \
    /opt/plesk/php/8.4.21/bin/php \
    /usr/bin/php8.4 \
    /usr/bin/php \
    /usr/local/bin/php; do
    if [ -x "$cand" ]; then
        echo "  [✓] $cand → $("$cand" -r 'echo PHP_VERSION;' 2>/dev/null)"
        [ -z "$PHP_BIN" ] && PHP_BIN="$cand"
    else
        echo "  [ ] $cand (not executable or missing)"
    fi
done
# Fallback to PATH lookup
if [ -z "$PHP_BIN" ]; then
    if command -v php >/dev/null; then
        PHP_BIN="$(command -v php)"
        echo "  [✓] PATH lookup → $PHP_BIN"
    fi
fi
if [ -z "$PHP_BIN" ]; then
    echo "  [✗] No PHP CLI binary found. Cannot proceed with artisan."
    echo "=== post-deploy aborted ==="
    exit 1
fi
echo "  → Using: $PHP_BIN"
echo ""

# Run from the project root so artisan finds its bootstrap regardless of CWD.
cd "$REPO_ROOT"

run_step() {
    local label="$1"; shift
    echo "→ $label"
    if "$@"; then
        echo "  ✓ ok"
    else
        local rc=$?
        echo "  ✗ failed (exit $rc)"
        return $rc
    fi
    echo ""
}

# Diagnostic mode: keep going even if a step fails, so the full log shows
# every command's outcome instead of stopping at the first error.
run_step "artisan migrate:status (pre-flight)" "$PHP_BIN" artisan migrate:status || true
run_step "artisan migrate --force"             "$PHP_BIN" artisan migrate --force || true
run_step "artisan storage:link --force"        "$PHP_BIN" artisan storage:link --force || true
run_step "artisan config:cache"                "$PHP_BIN" artisan config:cache || true
run_step "artisan route:cache"                 "$PHP_BIN" artisan route:cache || true
run_step "artisan view:cache"                  "$PHP_BIN" artisan view:cache || true
run_step "artisan filament:cache-components"   "$PHP_BIN" artisan filament:cache-components || true
run_step "artisan icons:cache"                 "$PHP_BIN" artisan icons:cache || true

echo "=== post-deploy finished $(date '+%Y-%m-%d %H:%M:%S') ==="
