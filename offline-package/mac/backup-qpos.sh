#!/usr/bin/env bash
# Backup MySQL database for offline Mac try-out
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/../data/backups"
mkdir -p "$BACKUP_DIR"

# Defaults — override with env: DB_DATABASE DB_USERNAME DB_PASSWORD DB_HOST
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-qpos_tyre}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

# Try to read from project .env if present
ENV_FILE=""
if [[ -f "$SCRIPT_DIR/../../.env" ]]; then
  ENV_FILE="$SCRIPT_DIR/../../.env"
elif [[ -f "$SCRIPT_DIR/../app/.env" ]]; then
  ENV_FILE="$SCRIPT_DIR/../app/.env"
fi

if [[ -n "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  set +u
  # Parse key=value lines safely
  while IFS='=' read -r key value; do
    key="$(echo "$key" | tr -d '[:space:]')"
    value="${value%$'\r'}"
    value="${value%\"}"
    value="${value#\"}"
    case "$key" in
      DB_HOST) DB_HOST="$value" ;;
      DB_PORT) DB_PORT="$value" ;;
      DB_DATABASE) DB_DATABASE="$value" ;;
      DB_USERNAME) DB_USERNAME="$value" ;;
      DB_PASSWORD) DB_PASSWORD="$value" ;;
    esac
  done < <(grep -E '^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' "$ENV_FILE" || true)
  set -u
fi

DUMP="$(command -v mysqldump || true)"
if [[ -z "$DUMP" ]]; then
  # Common Homebrew mysql paths
  for c in /opt/homebrew/bin/mysqldump /usr/local/bin/mysqldump; do
    [[ -x "$c" ]] && DUMP="$c" && break
  done
fi

if [[ -z "$DUMP" ]]; then
  echo "ERROR: mysqldump not found. Install MySQL client: brew install mysql-client"
  exit 1
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
OUT="$BACKUP_DIR/qpos_${STAMP}.sql"

echo "Backing up $DB_DATABASE @ $DB_HOST:$DB_PORT ..."
if [[ -n "$DB_PASSWORD" ]]; then
  "$DUMP" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" >"$OUT"
else
  "$DUMP" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE" >"$OUT"
fi

echo "Backup OK: $OUT"
