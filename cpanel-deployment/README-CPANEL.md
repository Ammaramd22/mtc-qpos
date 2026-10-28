# QPOS — extract inside public_html

You are already in `/home/mtcqpos/public_html`. Upload and extract this ZIP in that folder. Tick **Overwrite existing files**.

After extract, this same list must contain `index.php` next to `cgi-bin`. There must not be a `public` folder in the address.

## After extract

1. Delete the folder named `public` if it is still in this list. That folder is what caused `/public/login`.
2. Edit `.env` in this same folder:

```text
APP_URL=https://mtc-qpos.avenque.io
ALLOW_REGISTRATION=true
```

No `/public` at the end.

3. Delete the ZIP.
4. Open `https://mtc-qpos.avenque.io/login`.

`/public/login` redirects to `/login`.

## First user

1. Open `https://mtc-qpos.avenque.io/business/register`.
2. On the Owner step, username `softwareowner`, password `123456789`.
3. Sign in on the Password tab.
4. Set `ALLOW_REGISTRATION=false` in `.env`.

Do not open `/install`. That wizard deletes every database table.

Your existing `.env` is not inside the ZIP, so extract will not replace the database password.
