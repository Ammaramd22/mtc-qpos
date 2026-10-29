# Barcode label customization — test report

Date: 29 October 2026. Environment: local QPOS source, PHP 8.5.9, Chrome headless print-to-PDF, PyMuPDF page measurement. No live database was written. The physical label printer and the shop scanner were not available.

## Root cause

The photographed sheet shows upright text (“Marjan Thobe Center”, “Cap”, barcode, “001”, “Rs 1,062.50”) with the gap between stickers cutting through the design. The left sticker is empty.

That happens when the printed page is longer than one sticker along the feed. Rotation 90° set the page to 25 mm × 38 mm, so 38 mm of upright content was fed across a sticker of about 25 mm. The gap landed in the middle of the label.

The corrected roll page is **38 mm wide × 25 mm high** at rotation **0°**. Width is across the roll. Height is the sticker face. The liner gap is not part of the page.

## Files changed

- `app/Utils/BrowserLabelLayout.php` (added)
- `app/Http/Controllers/LabelsController.php`
- `app/Http/Controllers/BusinessController.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`

`php -l` reported no syntax errors on the PHP files. The print view and the Business Settings partial both rendered.

## Fixes

- Default rotation is 0°. Page size for a roll equals one sticker.
- Inner padding and calibration offsets no longer change the page size.
- Sheet mode is a separate grid and cannot rotate.
- Price and barcode stay inside the sticker. Barcode images keep their proportions (`object-fit: contain`).
- Invalid sizes, fonts, barcode types, and HTML in the shop name or profile name are rejected or stripped on the server.

## Customization added

Millimetre width and height, roll or sheet, columns and rows, gaps, four paddings, horizontal and vertical offsets, rotation, six presets, font and alignment, per-field show/hide (including brand and category), font sizes and weights, barcode format, position, width, height, thickness, and quiet zone. Live preview for size, type, alignment, offsets, and visibility. Save layout, save named profile (max 12 per business), delete profile, restore defaults. Stored in `business.common_settings` under `browser_label_layout` and `browser_label_profiles`. No migration.

## Automated results

Layout math (`BrowserLabelLayout`):

| Check | Result |
| --- | --- |
| Default 38 × 25, rotation 0, roll, one sticker per page | Pass |
| Rotation 90 swaps the page to 25 × 38 | Pass |
| Negative width, huge height, offset 80, rotation 45, bad font, script in the shop name | Clamped or stripped |
| Sheet 2 × 1 with a 2 mm gap is a 78 × 25 mm page, rotation forced to 0, two labels per page | Pass |
| EAN-13 on “001” falls back to Code 128; a 12-digit code stays EAN-13; Code 39 letters stay | Pass |
| Blank profile names dropped; name tags stripped; valid width kept | Pass |
| DNS1D PNG for SKU `001` as Code 128 | Pass |

Chrome headless PDF (margins off, no header/footer), then measured:

| Case | Result |
| --- | --- |
| Five roll stickers: Cap 001 at 1062.50 (twice), long thobe name, “A” at 15000, Kids Thobe 13-digit SKU | 5 pages, each **38.10 × 25.06 mm**. Page 1 text is the full `Rs 1,062.50` with name, SKU, and cost code. Ink about 1.06–36.78 mm across and 0.79–24.43 mm down, inside the page. Duplicate Cap matches page 1. |
| Long name | Two lines, then ellipsis, still on that one page |
| Explicit rotation 90, one sticker | 1 page, **25.06 × 38.10 mm**. Full price present. Ink spans the long side (about 0.79–36.87 mm), so the counter-rotation fills the swapped page |
| Sheet, 2 columns, five stickers, requested rotation 90 | 3 pages of **77.98 × 25.06 mm**. Pages 1–2 contain two labels each. Page 3 contains one label on the left (ink ends near 36.8 mm). Rotation stayed 0 |
| Rendered HTML | No injected `<script>alert` from the shop-name test |

Repeated print CSS is rewritten when width, height, rotation, stock, columns, or gaps change, and again when Print is clicked.

## Not performed

- No physical printer, sticker stock, or scanner. Alignment on the real roll and scan success are **not** verified. Print one sticker on site before a batch.
- No live database save. Profile create/delete was checked by the normalizer only, not by writing `business.common_settings`.
- Receipt, KOT/BOT, POS totals, and stock were not exercised. Those files were not modified.
- Product create/edit screens were not clicked. Barcode values are only read for the label.

## Database

No migration. No seeder. Settings remain JSON on the existing business row. Do not run Artisan against production for this patch.

## Limitations

- A layout already saved as rotation 90° stays at 90° until it is changed and saved. The print screen shows a warning.
- Chrome must use margins None, scale 100%, and no headers. Choosing A4 will mis-scale the page.
- Browser preview does not guarantee the printer’s gap sensor.
- Quiet zone is padding around the image. The barcode library has no separate quiet-zone argument.
- Category is loaded only when that field is turned on.
- If the sticker is portrait (taller along the feed), use the 25 × 38 preset or type those sizes. Do not infer a new size from the photograph alone; the configured stock is 38 mm across by 25 mm along the feed.
