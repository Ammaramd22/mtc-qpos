#!/usr/bin/env bash
# Build a clean QPOS Offline release (ready for Windows + optional ionCube).
# Usage:
#   ./offline-package/build/prepare-release.sh
#   ./offline-package/build/prepare-release.sh /path/to/output
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
OUT="${1:-$HOME/Desktop/QPOS-Offline-Release}"
STAMP="$(date +%Y%m%d_%H%M%S)"
DEST="$OUT/QPOS_Offline_$STAMP"

echo "Source: $ROOT"
echo "Dest:   $DEST"
mkdir -p "$DEST/app" "$DEST/windows" "$DEST/data/backups" "$DEST/docs" "$DEST/electron-wrapper" "$DEST/assets"

rsync -a \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='.gitattributes' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='.cursor/' \
  --exclude='.idea/' \
  --exclude='.vscode/' \
  --exclude='.DS_Store' \
  --exclude='node_modules/' \
  --exclude='tests/' \
  --exclude='phpunit.xml' \
  --exclude='phpunit.xml.dist' \
  --exclude='webpack.mix.js' \
  --exclude='package-lock.json' \
  --exclude='yarn.lock' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/debugbar/' \
  --exclude='offline-package/' \
  --exclude='vendor/' \
  --exclude='*.zip' \
  --exclude='public/fix.php' \
  --exclude='public/fix-all.php' \
  --exclude='public/diagnose.php' \
  --exclude='public/update.php' \
  --exclude='public/manual-update.php' \
  --exclude='public/deploy-htaccess.php' \
  --exclude='public/exports/*.csv' \
  "$ROOT/" "$DEST/app/"

mkdir -p "$DEST/app/storage/logs" \
  "$DEST/app/storage/framework/cache/data" \
  "$DEST/app/storage/framework/sessions" \
  "$DEST/app/storage/framework/views" \
  "$DEST/app/bootstrap/cache"

# Ensure writable placeholders
touch "$DEST/app/storage/logs/.gitkeep"
touch "$DEST/app/bootstrap/cache/.gitkeep"

# Offline Windows kit
cp -R "$ROOT/offline-package/windows/"* "$DEST/windows/"
cp -R "$ROOT/offline-package/assets/"* "$DEST/assets/" 2>/dev/null || true
cp "$ROOT/offline-package/electron-wrapper/icon.ico" "$DEST/electron-wrapper/" 2>/dev/null || true
cp "$ROOT/offline-package/electron-wrapper/icon.png" "$DEST/electron-wrapper/" 2>/dev/null || true
cp "$ROOT/offline-package/env.offline.example" "$DEST/env.offline.example"
cp "$ROOT/offline-package/TYRE_SHOP_SETUP.md" "$DEST/docs/TYRE_SHOP_SETUP.md"
cp "$ROOT/offline-package/PACKAGING.md" "$DEST/docs/PACKAGING.md"
cp "$ROOT/offline-package/windows/README-CUSTOMER.txt" "$DEST/README-CUSTOMER.txt"

# vendor/ is NOT bundled (keeps release small for low-disk Mac builds).
# Install on Windows with: composer install --no-dev
cat > "$DEST/app/INSTALL_VENDOR.txt" <<'EOF'
Run inside this app folder on Windows (Laragon terminal):

  composer install --no-dev --optimize-autoloader

Requires Composer + internet once. Then offline forever.
EOF

# Do not require vendor on build machine
rm -f "$DEST/app/.env" "$DEST/app/.env.backup" "$DEST/app/.env.local" 2>/dev/null || true

# Docs for this release
cp "$ROOT/offline-package/docs/WINDOWS_INSTALL.md" "$DEST/WINDOWS_INSTALL.md" 2>/dev/null || true
cp "$ROOT/offline-package/docs/ENCODING.md" "$DEST/ENCODING.md" 2>/dev/null || true

cat > "$DEST/START_HERE.txt" <<'EOF'
QPOS Offline — START HERE (Windows test)
========================================

1. Read WINDOWS_INSTALL.md
2. On Windows: Laragon + copy app to C:\laragon\www\qpos
3. In app folder: composer install --no-dev
4. Copy env.offline.example to .env → php artisan key:generate
5. Create DB qpos_tyre → migrate or installer
6. Optional encode later: ENCODING.md (needs ionCube)

Your Mac disk was low, so vendor/ is installed on Windows — not in this zip.
EOF

# Zip for USB (skip if not enough space)
ZIP="$OUT/QPOS_Offline_$STAMP.zip"
echo "Creating zip: $ZIP"
(
  cd "$OUT"
  if zip -rq "QPOS_Offline_$STAMP.zip" "QPOS_Offline_$STAMP" -x "*.DS_Store"; then
    echo "Zip OK: $OUT/QPOS_Offline_$STAMP.zip"
  else
    echo "WARNING: zip failed (disk space?). Use the folder instead: $DEST"
  fi
)

echo ""
echo "========================================"
echo "RELEASE READY"
echo "  Folder: $DEST"
echo "  Zip:    $OUT/QPOS_Offline_$STAMP.zip"
echo "========================================"
