# Size and Color label rendering

## Why the sticker stayed blank

The Show on label boxes for Size and Color were already on the browser print page. The sticker template only draws `Size: …` and `Color: …` when the print payload contains a non-empty value. The payload was built from a second product lookup that ran only when `Schema::hasColumn` reported `products.size` and `products.color`. That check reads `information_schema`, which some cPanel database users cannot see, so the lookup was skipped and both values were sent as empty strings. The template then omitted the line entirely, and ticking the boxes had nothing to reveal.

The same lookup also ignored the variation that was actually selected. A variable product whose template is Size or Color stores the value on `variations.name`, not only on `products.size` / `products.color`.

## What changed

- Label printing loads Size and Color for the selected variation with `SHOW COLUMNS` and a direct product/variation query. The product id from the print list is used together with the variation id.
- A non-empty `products.size` or `products.color` is shown as entered.
- If that field is empty and the selected variation’s template is Size or Color (including Colour), the variation value is shown. A dummy variation named `DUMMY` is not shown.
- `hw_size` and `hw_colour` fill a remaining gap only. They do not replace a dedicated Size or Color.
- The live preview and the print batch include the same sticker partial. Ticking or unticking Size or Color updates both at once.
- An empty value does not add a line. Hiding the last remaining value collapses the line so it does not leave a gap.
- The attribute line uses the SKU font size so it stays readable on a 50 × 25 mm sticker and sits between the item name and the SKU.
- Saved layouts, profiles, presets, and Restore defaults are unchanged. Missing Size and Color flags still default to off.

## Files

- `app/Http/Controllers/LabelsController.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/labels/partials/browser_sticker.blade.php`

No migration, no new column, no change to product create or edit, stock, prices, invoices, or barcode generation.
