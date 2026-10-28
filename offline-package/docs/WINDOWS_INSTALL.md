# QPOS Offline — Windows install (try on your PC first)

Use this on **your Windows laptop/PC** before giving to a customer.

## What you need

- Windows 10/11
- [Laragon Full](https://laragon.org/download/) (includes PHP + MySQL + Apache)
- This release folder or zip (`QPOS_Offline_YYYYMMDD_HHMMSS`)

## First Windows trial (no ionCube yet)

### 1) Install Laragon
1. Install Laragon Full.
2. Open Laragon → **Menu → PHP** → pick **8.2 or 8.3** (must match your app).
3. Click **Start All** (Apache + MySQL).

### 2) Copy QPOS
Recommended:

```text
C:\QPOS\QPOS_Offline_xxxx\     ← this whole release folder
```

Then either:

**Option A (easy):** put the Laravel app into Laragon www:

1. Copy everything inside `app\` to:
   `C:\laragon\www\qpos\`
2. So you have: `C:\laragon\www\qpos\public\index.php`

**Option B:** keep release at `C:\QPOS\...` and add a Laragon virtual host pointing document root to:
`C:\QPOS\QPOS_Offline_xxxx\app\public`

### 3) Create database
1. Open http://localhost/phpmyadmin (or HeidiSQL in Laragon)
2. Create database: `qpos_tyre`
3. Collation: `utf8mb4_unicode_ci`

### 4) Create `.env`
1. Copy `env.offline.example` → `C:\laragon\www\qpos\.env`
2. Edit:

```env
APP_URL=http://qpos.test
DB_DATABASE=qpos_tyre
DB_USERNAME=root
DB_PASSWORD=
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
CACHE_DRIVER=array
```

3. In Laragon terminal / CMD:

```bat
cd C:\laragon\www\qpos
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

> Release zip may omit `vendor/` (smaller). Composer needs internet **once**, then runs offline.
### 5) Install / migrate
If you have a SQL dump, import it into `qpos_tyre`.

Otherwise:

```bat
cd C:\laragon\www\qpos
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
```

Or open the web installer in browser if your build uses it:
`http://qpos.test` or `http://localhost/qpos/public`

Laragon usually maps `C:\laragon\www\qpos` → **http://qpos.test**

### 6) Desktop shortcut
1. Edit `windows\start-qpos.bat` if needed:
   - `QPOS_URL=http://qpos.test`
2. Run `windows\create-desktop-shortcut.bat`
3. Double-click **QPOS Offline** on Desktop

### 7) Tyre shop demo data
Follow `docs\TYRE_SHOP_SETUP.md` — add tyre products + Fitting service.

### 8) Daily backup
Run `windows\backup-qpos.bat` (set DB name + mysqldump path inside the file).

---

## Avoid 419 Page Expired on Windows

- Use one URL only (`http://qpos.test` **or** `http://localhost/qpos/public`)
- Keep `SESSION_DOMAIN=` empty
- `SESSION_SECURE_COOKIE=false`
- After `.env` changes: `php artisan optimize:clear`

---

## Customer delivery checklist

Before customer:

- [ ] You tested login + POS sale + print on Windows
- [ ] Removed your personal `.env` / keys
- [ ] Optional: encode with ionCube (`ENCODING.md`)
- [ ] ZIP on USB + README-CUSTOMER.txt
- [ ] Teach backup to USB

## Folder map on customer PC

```text
C:\laragon\www\qpos\          ← application
C:\QPOS\data\backups\         ← SQL backups
Desktop\QPOS Offline.lnk      ← start
```
