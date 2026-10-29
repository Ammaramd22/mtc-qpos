# Barcode label fix — changelog

Minimal patch for sideways, clipped barcode stickers. No database change. No framework upgrade.

## `resources/views/labels/browser_print.blade.php`

Print layout for browser labels.

- Each sticker is one page with a zero page margin, so the next label does not start in the gap.
- Label contents sit in an inner box that stays inside the sticker width and height.
- The barcode image keeps its proportions instead of being stretched to the box.
- The price stays inside the sticker. It no longer paints into the next label.
- Long names stay on two lines inside the sticker.
- **Print rotation** (0°, 90°, 270°) updates the printed page immediately. The default page for 90° is turned so a clockwise label driver prints upright text on the roll. The on-screen preview stays upright.

## `app/Http/Controllers/LabelsController.php`

Reads and saves `print_rotate` with the other label layout fields. If a saved layout has no rotation yet, it uses 90°.

## `app/Http/Controllers/BusinessController.php`

Keeps `print_rotate` when Business Settings are saved, so a settings save does not drop the new field.

## `resources/views/business/partials/settings_bartender.blade.php`

Adds the Print rotation field on the browser-label settings form.

## `lang/en/lang_v1.php`

Wording for Print rotation and a shorter hint on the label page.
