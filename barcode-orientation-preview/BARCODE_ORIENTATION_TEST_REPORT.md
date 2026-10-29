# Barcode orientation and live preview — test report

Date: 29 October 2026. Local QPOS source, PHP 8.5.9, Chrome headless print-to-PDF, PyMuPDF. No live database was written. The physical printer was not used in this test. The upside-down photograph supplied with the request is the production sample: rotation was set to 90° on `/labels/browser-print`, and the stickers came out upside down.

## Root cause

The 90° setting swapped the page to 25 mm × 38 mm and counter-rotated the artwork. The on-screen labels stayed upright. The printer fitted that page onto the 38 mm × 25 mm sticker, and the two rotations printed the design upside down (price along the top edge, shop name along the bottom edge).

## Files changed

- `app/Utils/BrowserLabelLayout.php`
- `app/Http/Controllers/LabelsController.php`
- `routes/web.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/labels/partials/browser_sticker.blade.php` (added)
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`

`php -l` reported no syntax errors on the PHP files. The print view rendered.

## How orientation was corrected

The page size stays the sticker size at every rotation. 0°, 90°, 180°, and 270° rotate that one design inside the sticker. Preview, batch, and print share `labels.partials.browser_sticker`. Print CSS does not apply a second turn.

## How the live preview works

A scaled sticker sits in the settings bar, at the label’s real aspect ratio (zoom on screen only; print hides it). Its caption shows sticker size, content angle, and print-page size. Changing rotation, size, type, weight, alignment, padding, offset, gaps, or visibility updates that sticker and the batch without a reload. Barcode format, bar thickness, and barcode height request a new image from `POST /labels/browser-print/barcode`. That request does not write product data.

## What was verified

| Check | Result |
| --- | --- |
| Rotation 90° page size | Stays **38 × 25 mm**. It is not swapped to 25 × 38. |
| Rotation 180° and an invalid 45° | 180 is kept. 45 falls back to 0°. |
| Sheet of two 38 × 25 cells with a 2 mm gap, content at 180° | Page **78 × 25 mm**. Rotation does not resize the sheet. |
| Rendered page | “Live label preview” is present. Upright job has the preview sticker plus the batch stickers. |
| Chrome PDF, rotation 0°, two products | 2 pages, each **38.10 × 25.06 mm**. Text direction left to right. “Marjan Thobe Center” at the top (y ≈ 0.1 pt). `Rs 1,062.50` at the bottom. |
| Chrome PDF, rotation 180° | Same page size. Text direction reversed. Shop name at the bottom, price at the top. This matches the upside-down photograph. |
| Chrome PDF, rotation 90° | Same page size. Text direction downward. Shop name on the right, price on the left. One clockwise turn. |
| Chrome PDF, rotation 270° | Same page size. Text direction upward. Shop name on the left, price on the right. |
| Raster of the 0° PDF | Shop name, “Cap”, barcode, “001”, cost code, and full price, all upright inside the page. |
| Raster of the 180° PDF | The same elements, upside down. |

The preview sticker is marked not to print, so the PDF page count is the batch only.

## Not performed

- No physical print and no scanner test in this session. Do not treat the PDF as proof of the roll’s gap sensor. Print one sticker at **0°** and compare it with the preview.
- Profile save was not written to a database.
- Receipts, KOT/BOT, product edit, stock, and payments were not opened. Those files were not part of this change.

## Printer setup that is still required

Chrome or Edge: margins **None**, scale **100%**, no headers or footers, and do not change the paper to A4. Width 38 mm is across the roll. Height 25 mm is the sticker face, not the gap. After installing, set **Content rotation** to **0° — upright**, check that the preview shows the shop name at the top, click **Save layout**, then print one sticker. Choose **180°** only if that test sticker is upside down. The preview will show the upside-down design before you print it.

## Limitations

A layout already saved at 90° remains 90° until someone selects 0° and saves. It now means clockwise content, which the preview shows, not the old page-swap. Quiet zone is padding around the barcode image. The barcode redraw route needs the user to be logged in. It does not change the stored SKU.
