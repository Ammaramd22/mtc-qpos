#!/usr/bin/env bash
# Stop QPOS artisan serve started by start-qpos.sh
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PID_FILE="$SCRIPT_DIR/.qpos-serve.pid"
PORT="${QPOS_PORT:-8088}"

if [[ -f "$PID_FILE" ]]; then
  PID="$(cat "$PID_FILE" || true)"
  if [[ -n "${PID:-}" ]] && kill -0 "$PID" 2>/dev/null; then
    echo "Stopping QPOS offline (pid $PID)..."
    kill "$PID" 2>/dev/null || true
    sleep 0.4
    kill -9 "$PID" 2>/dev/null || true
  fi
  rm -f "$PID_FILE"
fi

PIDS="$(lsof -nP -iTCP:"$PORT" -sTCP:LISTEN -t 2>/dev/null || true)"
if [[ -n "$PIDS" ]]; then
  echo "Freeing port $PORT..."
  echo "$PIDS" | xargs kill 2>/dev/null || true
fi

echo "QPOS offline stopped (port $PORT)."
