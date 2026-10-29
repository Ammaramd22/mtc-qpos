# Barcode orientation and live preview — changelog

Patch only. No Laravel upgrade, no new table, no change to sales, receipts, KOT/BOT, stock, or stored barcodes.

## Root cause

Content rotation **90°** did not mean “turn the design 90°”. It swapped the printed page from 38 × 25 mm to 25 × 38 mm and counter-rotated the artwork, while the on-screen stickers stayed upright. This printer then fitted that portrait page onto the landscape sticker. The two turns landed as **180°**: the price at the top of the sticker and “Marjan Thobe Center” at the bottom, both upside down. The screen never showed that turn, so it could not be caught before printing.

## Correction

The printed page is always the physical sticker (or the sheet grid). Rotation no longer changes the page size.

- **0°** — upright, shop name at the top. This is the default.
- **90°** — the whole design turns clockwise inside the sticker.
- **180°** — the whole design is upside down.
- **270°** — the whole design turns the other way.

The same sticker markup and the same rotation rules are used for the live preview, the batch, and the print. A saved value of 90 is no longer a hidden page swap. After this update it means clockwise content, which the preview shows. Set it back to **0°** and save so the next print is upright.

## Files

- `app/Utils/BrowserLabelLayout.php` — page size ignores rotation; 180° is allowed; a sheet can rotate content inside each cell.
- `resources/views/labels/partials/browser_sticker.blade.php` — one sticker used by the preview and the print.
- `resources/views/labels/browser_print.blade.php` — live preview, batch preview, shared rotation, barcode image refresh.
- `app/Http/Controllers/LabelsController.php` — `POST /labels/browser-print/barcode` redraws a preview image and does not change the product.
- `routes/web.php` — that route, inside the existing login group.
- `resources/views/business/partials/settings_bartender.blade.php` — 180° in the rotation list.
- `lang/en/lang_v1.php` — labels for upright, clockwise, upside down, and the preview.
