# Print Labels search and Size/Color changelog

Two changes to the existing browser label flow. Product records, stock, prices, barcodes, and other POS screens are unchanged.

## Product search

Typing in **Search Product** on `/labels/show` no longer adds a product.

The old autocomplete treated a single match as a selection. A few letters that matched one name or SKU called the select handler immediately, so the product appeared in the label list before anyone chose it.

Search now starts at 2 characters and shows a dropdown. A row is added only when the user clicks a suggestion or presses Enter on the highlighted suggestion. Escape closes the dropdown. An empty field closes it and drops any in-flight request. A newer search replaces an older response. A failed search shows an error in the dropdown and does not add a row.

Suggestions include the product name, SKU (the barcode value stored on the variation), the variation name when the product is variable, and Size and Color when those product fields have text.

Selecting a variation that is already in the list increases **No. of labels** by 1. It does not add a second row and it does not replace a quantity the user already typed. Two quick selections of the same product before the first row returns still end as one row with the extra quantity applied.

## Size and Color on the label

**Show on label** has two new checkboxes: **Size** and **Color**. They are independent.

The values are read from the existing `products.size` and `products.color` columns. They are not variation rows, and this update does not add columns. A variable product uses the same product-level Size and Color on every variation. The variation name is still printed with the item name when it is not the dummy variation. It is not treated as Size or Color.

Empty values are omitted. There is no “Size:” label with a blank value, and the line collapses when both values are hidden or missing.

The text sits in the sticker header, under the item name and above the barcode, SKU, and price. The live preview and the printed batch use `labels/partials/browser_sticker.blade.php`. Turning a checkbox on or off updates both without a separate preview template.

Older saved layouts and profiles do not contain these switches. They load as off, so existing stickers stay the same until someone enables Size or Color. New saves, named profiles, layout presets, and **Restore defaults** store the switches in the existing `business.common_settings` JSON (`browser_label_layout` and `browser_label_profiles`). No new table is used.

Business Settings → browser label display includes the same two switches.

## Files

- `app/Http/Controllers/LabelsController.php`
- `app/Utils/BrowserLabelLayout.php`
- `routes/web.php`
- `public/js/labels.js`
- `resources/views/labels/show.blade.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/labels/partials/browser_sticker.blade.php`
- `resources/views/labels/partials/show_table_rows.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`

## Not changed

Product create and edit, stock, prices, invoices, barcode encoding, sticker millimetres, rotation, fonts, and the rest of the POS were not modified. No migration was added.
