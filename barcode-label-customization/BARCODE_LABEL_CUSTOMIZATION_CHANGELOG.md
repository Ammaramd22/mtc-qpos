# Barcode label customization — changelog

Patch only. No Laravel upgrade, no new database table, no change to sales, receipts, KOT/BOT, stock, or stored barcodes.

## Root cause

Printed pages were taller than one sticker. With rotation 90°, the page height became the roll width (38 mm) while this printer feeds along the page height by about one 25 mm sticker. The liner gap then cut through an upright label: shop name, product, barcode, SKU, and price were split, and the next sticker was blank.

The page for a roll is now the sticker itself: width across the roll, height along the feed. The gap stays between pages. Rotation 0° is the default. 90° and 270° remain only when a driver actually turns the page.

## Files

- `app/Utils/BrowserLabelLayout.php` — new. Defaults, presets, clamping, page size, barcode-type fallback, profile cleanup.
- `app/Http/Controllers/LabelsController.php` — browser print and save use that layout. Partial saves merge with the layout already stored. Profiles (create, replace, delete, maximum 12) are written beside it.
- `app/Http/Controllers/BusinessController.php` — Business Settings save normalizes the label layout and keeps keys the form did not post.
- `resources/views/labels/browser_print.blade.php` — one page per roll sticker, sheet grid, padding, offsets, presets, profiles, content and barcode controls, print CSS.
- `resources/views/business/partials/settings_bartender.blade.php` — the same dimensions and content options. Default rotation is 0°.
- `lang/en/lang_v1.php` — labels for the new controls.

## Behavior

- Roll: `@page` size is label width × label height. One sticker per page, so a batch does not accumulate a gap error.
- Sheet: page size is columns × width plus gaps, by rows × height plus gaps. Rotation is forced to 0°.
- Padding is inside the sticker. Offsets translate the design for calibration and do not change the page size.
- Long product names stay on two lines inside the sticker. Prices keep their full formatted value, including `Rs 1,062.50`.
- Barcode art uses the existing DNS1D PNG renderer, `object-fit: contain`, thickness 1–4, and a CSS quiet zone.
- Shop name and profile names are tag-stripped. Sizes, fonts, alignment, formats, and flags are limited to an allow-list.
- Show brand and show category default off.
- Authorization is the existing logged-in business session. No new permission table.

A layout already saved with rotation 90° is not overwritten until someone chooses 0° and saves. The print screen warns when rotation is not 0°.
