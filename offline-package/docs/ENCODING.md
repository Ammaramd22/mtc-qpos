# Encoding QPOS for customer (ionCube)

You asked for an **encoded** install. Encoding needs a commercial encoder on **your** build PC (usually Windows/Linux with ionCube Encoder).

This Mac environment does **not** have `ioncube_encoder` installed — so the release we build is a **clean protected package** (no Git/secrets). You encode before final customer handoff.

## What encoding does

- Customer can **run** the app
- They cannot easily **read/edit** your PHP business logic
- Still combine with a license/support contract

## Buy / install encoder

1. Get [ionCube Encoder](https://www.ioncube.com/encoders.php) for your PHP version (8.2/8.3).
2. On customer PHP (Laragon), enable **ionCube Loader**:
   - Download loader for their PHP version  
   - Enable in `php.ini` (Laragon → PHP → php.ini)  
   - Check with `php -v` (should mention ionCube Loader)

## What to encode

Encode typically:

- `app/` (almost all `.php`)
- Optionally `routes/` except if bootstrap issues
- Leave **plain**:
  - `public/index.php`
  - `artisan`
  - `bootstrap/`
  - `vendor/` (or encode selectively — vendor is huge; usually leave as-is)
  - `config/` (often leave plain so install works; or encode non-env configs carefully)
  - `resources/views/` (Blade can stay plain — or encode if your encoder supports)

Exact options vary by ionCube GUI — use “Laravel” presets if available.

## Encode workflow (after prepare-release)

1. Build release:

```bash
./offline-package/build/prepare-release.sh
```

2. On Windows encoder PC, open the release `app\` folder.
3. Encode into e.g. `app_encoded\` then replace `app\`.
4. Test on your Windows Laragon (login + POS).
5. Zip and deliver.

## Script placeholder

See `offline-package/build/encode-ioncube.example.bat` — edit paths after you install the encoder.

## Without ionCube yet

You can still **test install on Windows** with the plain release zip (safe enough for your own PC trial).  
For **paying customers**, encode first once you have ionCube.

## Extra hardening (recommended)

- Don’t ship `.git`
- Don’t ship `public/fix.php`, `update.php`, etc. (already stripped by prepare-release)
- Empty Gemini keys
- One license / one PC in your invoice terms
