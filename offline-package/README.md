# QPOS Offline Package (Tyre Shop)

Local, offline POS for a customer PC — **no internet required** after install.

This folder is the **delivery kit**. It does not replace your Laravel app; it packages and runs it on Windows with Laragon/XAMPP (or `php artisan serve` + MySQL).

## What the customer gets

```text
QPOS-Offline/
  app/                      ← your Laravel project (release copy)
  runtime/                  ← Laragon OR XAMPP (you place this)
  data/backups/             ← automatic DB backups land here
  windows/
    start-qpos.bat          ← double-click to open POS
    stop-qpos.bat
    backup-qpos.bat
    open-pos.bat
  .env                      ← created on install (never ship your own .env)
  README-CUSTOMER.txt
```

## Quick path (recommended)

1. On **your** Mac/PC: build a clean release (see `PACKAGING.md`).
2. On **customer Windows PC**: install Laragon (or XAMPP).
3. Copy release into Laragon `www/qpos` (or package `app/`).
4. Create MySQL database + import SQL / run installer.
5. Put `windows/start-qpos.bat` on Desktop.
6. Train tyre products + fitting services (see `TYRE_SHOP_SETUP.md`).

### Try on Mac first

See **`mac/README.md`** — double-click `Start QPOS.command` to run your local project offline-style via `php artisan serve`.

## Electron (optional later)

See `electron-wrapper/` — a thin desktop window that opens `http://127.0.0.1`.  
PHP/MySQL still run underneath (same as browser). Use after the `.bat` flow works.

## Security reminder

- Do **not** ship Git history or your master `.env`.
- Prefer encoded PHP (ionCube) for paid local installs.
- Give a licensed runtime, not your full development source tree.
