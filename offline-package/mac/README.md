# Try QPOS Offline on Mac

You can test the same offline idea on your Mac before making the Windows tyre-shop USB.

## Requirements

- PHP 8.1+ (`php -v`) — Homebrew or [Laravel Herd](https://herd.laravel.com)
- MySQL/MariaDB running locally
- Project `.env` with working `DB_*` (your current VsoftPOS `.env` is fine for try-out)

## Start (easiest)

1. In Finder go to `offline-package/mac/`
2. Right-click **Start QPOS.command** → Open (first time macOS may ask to allow)
3. Browser opens `http://127.0.0.1:8088` (not `:8000` — that avoids session/419 with Herd)

Or in Terminal:

```bash
cd /Users/admin/Sites/VsoftPOS
chmod +x offline-package/mac/*.sh offline-package/mac/*.command
./offline-package/mac/start-qpos.sh
```

**419 Page Expired?** That happens if you open `127.0.0.1:8000` while `.env` is set for `vsoftpos.test`.  
Always use the Offline starter URL on **port 8088**. The script disables `SESSION_DOMAIN` and uses `CACHE_DRIVER=array`.
## Stop

```bash
./offline-package/mac/stop-qpos.sh
```

Or double-click **Stop QPOS.command**.

## Backup

```bash
./offline-package/mac/backup-qpos.sh
```

Uses DB settings from project `.env`. SQL files go to `offline-package/data/backups/`.

## Optional: Electron window on Mac

```bash
cd offline-package/electron-wrapper
npm install
QPOS_URL=http://127.0.0.1:8000/pos/create npm start
```

Start the PHP server first with `start-qpos.sh`.

## Notes

| Item | Mac try-out | Windows customer |
|------|-------------|------------------|
| Runtime | `php artisan serve` | Laragon / XAMPP |
| Launcher | `.command` / `.sh` | `.bat` |
| Icon | `electron-wrapper/icon.png` | `icon.ico` |
| Internet | Not required for sales | Not required |

This Mac flow is for **you to demo**. Customer installs still use the Windows kit in `offline-package/windows/`.
