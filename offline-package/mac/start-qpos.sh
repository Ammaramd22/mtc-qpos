#!/usr/bin/env bash
# ============================================================
# QPOS Offline — Start (macOS)
# Uses 127.0.0.1 with session/cookie settings that avoid 419.
# Double-click "Start QPOS.command" or:
#   ./offline-package/mac/start-qpos.sh
# ============================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [[ -f "$SCRIPT_DIR/../../artisan" ]]; then
  QPOS_APP="$(cd "$SCRIPT_DIR/../.." && pwd)"
elif [[ -f "$SCRIPT_DIR/../app/artisan" ]]; then
  QPOS_APP="$(cd "$SCRIPT_DIR/../app" && pwd)"
else
  echo "ERROR: Cannot find Laravel artisan."
  exit 1
fi

# Dedicated offline port so we don't hit Herd/vsoftpos.test on :8000 (causes 419)
PORT="${QPOS_PORT:-8088}"
HOST="${QPOS_HOST:-127.0.0.1}"
URL="http://${HOST}:${PORT}"
PID_FILE="$SCRIPT_DIR/.qpos-serve.pid"
LOG_FILE="$SCRIPT_DIR/.qpos-serve.log"

cd "$QPOS_APP"

PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
if [[ -z "$PHP_BIN" ]]; then
  echo "ERROR: php not found. Install PHP (brew install php) or Laravel Herd."
  exit 1
fi

# Stop our previous offline server
if [[ -f "$PID_FILE" ]]; then
  OLD_PID="$(cat "$PID_FILE" || true)"
  if [[ -n "${OLD_PID:-}" ]] && kill -0 "$OLD_PID" 2>/dev/null; then
    echo "Stopping previous QPOS offline server (pid $OLD_PID)..."
    kill "$OLD_PID" 2>/dev/null || true
    sleep 0.5
    kill -9 "$OLD_PID" 2>/dev/null || true
  fi
  rm -f "$PID_FILE"
fi

# If our offline port is still taken, free it
if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Freeing port $PORT..."
  lsof -nP -iTCP:"$PORT" -sTCP:LISTEN -t 2>/dev/null | xargs kill 2>/dev/null || true
  sleep 0.4
fi

# Clear Laravel caches / old sessions (no stale config for vsoftpos.test cookies)
echo "Clearing Laravel cache & sessions..."
"$PHP_BIN" artisan optimize:clear >/dev/null 2>&1 || true
rm -f storage/framework/sessions/* 2>/dev/null || true
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/packages.php 2>/dev/null || true

# Force localhost-friendly env (overrides .env SESSION_DOMAIN=.vsoftpos.test)
export APP_URL="$URL"
export APP_ENV="${APP_ENV:-local}"
export CACHE_DRIVER=array
export SESSION_DRIVER=file
export SESSION_DOMAIN=
export SESSION_SECURE_COOKIE=false
export SESSION_SAME_SITE=lax
export SANCTUM_STATEFUL_DOMAINS="127.0.0.1,localhost,127.0.0.1:${PORT},localhost:${PORT}"

echo "Starting QPOS Offline on $URL ..."
echo "App: $QPOS_APP"
nohup env \
  APP_URL="$APP_URL" \
  CACHE_DRIVER=array \
  SESSION_DRIVER=file \
  SESSION_DOMAIN= \
  SESSION_SECURE_COOKIE=false \
  SESSION_SAME_SITE=lax \
  SANCTUM_STATEFUL_DOMAINS="$SANCTUM_STATEFUL_DOMAINS" \
  "$PHP_BIN" artisan serve --host="$HOST" --port="$PORT" >"$LOG_FILE" 2>&1 &
echo $! >"$PID_FILE"

for i in {1..40}; do
  if curl -sf -o /dev/null "$URL/login" || curl -sf -o /dev/null "$URL"; then
    break
  fi
  sleep 0.25
done

open "${URL}/login"
sleep 0.4
open "${URL}/pos/create" 2>/dev/null || true

echo ""
echo "QPOS Offline ready — use THIS URL only (not :8000 / vsoftpos.test):"
echo "  Login: $URL/login"
echo "  POS:   $URL/pos/create"
echo ""
echo "Cache driver: array (no persistent cache)"
echo "Stop: $SCRIPT_DIR/stop-qpos.sh"
echo "Log:  $LOG_FILE"
