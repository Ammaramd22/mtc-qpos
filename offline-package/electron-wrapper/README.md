# Optional Electron desktop shell

This is **only a window** around your local QPOS URL.  
You still need Laragon/XAMPP + MySQL + the Laravel app running.

## App icon

| File | Use |
|------|-----|
| `icon.ico` | Windows Electron build + Desktop shortcut |
| `icon.png` | Electron window / Linux |
| `../assets/qpos-icon-*.png` | PNG sizes 16–1024 |
| `../assets/qpos-logo-full.png` | Full brand logo (QPOS BY AVENQUE) |

## Use after `.bat` flow works

```bash
cd offline-package/electron-wrapper
npm install
QPOS_URL=http://127.0.0.1/qpos/public/pos/create npm start
```

## Build Windows portable .exe (on Windows)

```bash
npm run dist
```

The build uses `icon.ico` automatically via `package.json` → `build.win.icon`.

## Important

- Do not put API secrets in the Electron app.
- Antivirus may flag unsigned Electron apps — sign later if needed.
- For tyre shop v1, prefer `start-qpos.bat` + Chrome; add Electron when stable.
