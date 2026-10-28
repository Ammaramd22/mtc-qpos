# QPOS — Upload to cPanel WITHOUT Terminal

Use this when you have **no SSH / Terminal** access.

---

## Option A — Already live shop (UPDATE only — Tyre Size)

### 1) Upload the new code
- Upload `QPOS-cPanel-Upload.zip` (or only changed files)
- Extract so Laravel folders (`app`, `public`, `database`, `vendor`, …) land correctly
- **Do not overwrite** your live `.env`

### 2) Add the database column (pick ONE)

#### Easiest: phpMyAdmin
1. cPanel → **phpMyAdmin**
2. Select your QPOS database
3. Open **SQL**
4. Paste contents of `database/cpanel_tyre_size.sql`
5. Click **Go**

If it says the column already exists, you are done for the DB part.

#### Or: one-click browser migrate
1. Edit `public/cpanel_run_migrations.php`
2. Change `MIGRATE_SECRET` to a random password
3. Open:
   `https://YOURDOMAIN.com/cpanel_run_migrations.php?key=YOUR_SECRET`
4. Wait for **SUCCESS**
5. **Delete** `public/cpanel_run_migrations.php` immediately

### 3) Clear caches (optional, File Manager)
Delete these folders’ *contents* (keep the folders):
- `bootstrap/cache/*.php` (except `.gitignore` if present)
- `storage/framework/views/*`
- `storage/framework/cache/data/*`

Or visit any admin page after upload — Laravel will rebuild caches.

### 4) Test
- Products → Add Product → **Tyre Size** field
- Save a tyre with size `205/55R16`
- Search `205` in POS

---

## Option B — Fresh / empty hosting (full install)

1. Create MySQL database + user in cPanel
2. Upload ZIP → extract
3. Point domain **Document Root** to `.../public` (preferred)
4. Copy `.env.cpanel.example` → `.env` and fill DB + `APP_URL`
5. Open `https://YOURDOMAIN.com/install` and complete the web installer  
   (installer runs migrations — no terminal)
6. Delete `public/cpanel_run_migrations.php` if still present
7. Set permissions: `storage/`, `bootstrap/cache/`, `public/uploads/` writable (755/775)

---

## Important

| Do | Don't |
|----|--------|
| Keep existing `.env` on update | Overwrite `.env` with an empty one |
| Delete `cpanel_run_migrations.php` after use | Leave the migrate URL public |
| Use PHP **8.1+** | Use PHP 7.x |
| Prefer document root = `public/` | Expose `.env` on the web |

Full architecture details: see `CPANEL_DEPLOYMENT_GUIDE.md`
