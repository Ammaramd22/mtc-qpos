# Packaging a clean offline release

Run these on your development machine before copying to the customer PC.

## 1. Clean copy (no secrets)

From the project root, create a release folder (example):

```bash
# from /Users/admin/Sites/VsoftPOS
./offline-package/build/prepare-release.sh
```

Or manually copy the project **excluding**:

- `.git/`
- `.env` (use `env.offline.example` instead)
- `node_modules/` (if not needed at runtime)
- `storage/logs/*` (keep folders, clear files)
- `storage/framework/cache/data/*`
- `offline-package/` is optional on customer (keep scripts only)
- `public/uploads` of other clients
- IDE folders (`.idea`, `.vscode`)

## 2. Production optimize

On the build machine (with PHP/Composer):

```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link
```

## 3. Database

Either:

- Export a **starter SQL** (empty tyre shop + admin user), or  
- Use the built-in QPOS web installer on first launch.

Never include another customer’s live sales DB.

## 4. Windows runtime

Customer PC needs one of:

| Runtime | Notes |
|---------|--------|
| **Laragon** | Easiest on Windows |
| **XAMPP** | Common; Apache + MySQL + PHP |
| Portable ZIP | You bundle Laragon Full in `runtime/` |

PHP should match what QPOS needs (typically 8.1+).

## 5. First-run on customer PC

1. Start MySQL + Apache (Laragon “Start All”).
2. Point document root / vhost to `public/`.
3. Copy `env.offline.example` → `.env`, set DB password.
4. `php artisan key:generate` (once).
5. Migrate or import SQL.
6. Login → create business → set tyre categories (see `TYRE_SHOP_SETUP.md`).
7. Test `windows/start-qpos.bat`.

## 6. Protect code (paid install)

Before delivery:

1. Encode `app/` (and sensitive PHP) with ionCube/SourceGuardian.
2. Install encoder loader on customer PHP.
3. Contract: no reverse engineering / resale.
4. Optional: machine-bound license later.

## 7. What to put on Desktop

- `Start QPOS.bat` → runs `windows/start-qpos.bat`
- Shortcut to POS URL if you prefer Chrome pinned
