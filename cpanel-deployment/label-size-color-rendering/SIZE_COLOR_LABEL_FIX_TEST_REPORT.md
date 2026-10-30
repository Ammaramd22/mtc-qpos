# Size and Color label rendering — test report

Date: 30 October 2026.

The local MySQL server was not running (`127.0.0.1:3306` refused the connection), so the live product row for Cap, SKU 0021, was not read from the database. The live browser print page for that product was inspected: Size and Color were enabled under Show on label, and the 50 × 25 mm preview showed the business name, Cap, the barcode, SKU 0021, and Rs 1,062.50, with no Size or Color line. That is the result when the sticker payload’s size and color strings are empty. A physical printer was not used.

## Resolver

| Case | Result |
| --- | --- |
| Product size Large and color Black | Both kept |
| Size Medium and a blank color | Color dropped |
| Color Red only | Size empty |
| Both blank | Both empty |
| Variation template Size, value Large, product size empty | Size Large |
| Variation template Colour, value Black | Color Black |
| Product size XL and variation Large | XL kept |
| Dummy variation named DUMMY | Not used as a size |
| Empty product fields with hardware M8 / Zinc | Hardware values used |
| Dedicated Large / Black plus hardware values | Dedicated values kept |
| Layout saved before these flags, width 50, barcode width 94, brand on | Width and barcode kept, Size and Color off |
| Saved Size on and Color off | Flags kept |
| All six layout presets | Size and Color flags present and off |

## Rendered 50 × 25 mm page

The real `labels.browser_print` view was rendered with five stickers: Cap Large/Black twice (quantity 2), Shirt Medium only, Scarf Red only, and Plain with neither. Business name Marjan Thobe Center, SKU 0021, price Rs 1,062.50, barcode position middle, rotation 0°.

| Size | Color | Preview and print sheet |
| --- | --- | --- |
| On | On | `Size: Large` and `Color: Black` |
| On | Off | Size visible, Color `display: none` |
| Off | On | Color visible, Size `display: none` |
| Off | Off | Attribute line `display: none` |

Shirt showed only `Size: Medium`. Scarf showed only `Color: Red`. Plain had no attribute line. The print stylesheet hides the toolbar and leaves the batch sheet visible. The attribute line uses the SKU font size.

## Browser

The rendered page was opened in Chrome.

- With both boxes ticked, the dashed 50 × 25 mm preview showed `Size: Large` and `Color: Black` under Cap, then SKU 0021, cost code, and Rs 1,062.50.
- Unticking Size hid Size on the preview and on every batch sticker. Color: Black stayed. Shirt’s size-only line collapsed to height 0. Scarf still showed Color: Red. Plain stayed blank.
- Unticking Color as well set the Cap line height to 0. Name and price stayed.
- Ticking Size only showed `Size: Large` and kept Color hidden. Shirt’s `Size: Medium` came back.
- Ticking Color again showed both on the preview.
- Print-media emulation hid the toolbar. The batch sheet still showed both values on both Cap copies, size only on Shirt, color only on Scarf, and neither on Plain. On the Cap sticker the name ended above the attribute line, and the SKU and price stayed below it.
- An older profile restored width 38 mm, turned Size and Color off, and kept Price on. The attribute line height became 0.
- Restore defaults did the same.
- The saved “Size and color” profile restored width 50 mm and showed both values again.

Barcode image overlap was not measured on this fixture because the sample stickers had no barcode PNG. The attribute line is in the header, above the barcode body, and the header does not shrink when the barcode grows.

## Not tested

- The live Cap row in MySQL.
- A physical label printer.
- Saving a layout to the live `business.common_settings` column. Profile switching was tested in the browser against the layouts already stored in the page.
