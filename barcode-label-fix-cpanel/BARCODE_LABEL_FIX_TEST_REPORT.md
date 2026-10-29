# Barcode label fix — test report

Date: 29 October 2026

Package: `QPOS-Barcode-Label-Fix-cPanel.zip` (minimal patch, not a full application)

## Root cause

The stickers in the photo are the QPOS browser label page (`Print Labels (Browser)`), not a BarTender layout. BarTender export is already turned off. Each sticker is the same design: business name, product name, Code 128 barcode, SKU, cost code, and price.

On the roll, that design is turned 90 degrees. Barcode bars run across the sticker instead of down it. “Marjan Thobe Center” and the price sit on the side edges, are cut off, and cross the gap onto the next sticker. The upper sticker in the photo shows the start of the name and a sideways barcode; the next sticker shows the rest of the name, the price, and another sideways barcode.

The label size stored for this page is the sticker face: **width across the roll** and **height along the feed**. The built-in default is **38 mm × 25 mm**. The photo was not used to invent a different size.

Two things in the print page caused the bad roll:

1. **The print page was landscape, and the printer path turns landscape pages sideways.** The CSS page was `label width × label height` (38 mm × 25 mm) with upright text. The printed result is that page rotated 90 degrees clockwise: the 38 mm side runs along the feed. The sticker along the feed is only 25 mm, so each page straddles the gap. The name (top of the design) and the price (bottom of the design) land on the sticker edges and are clipped. Vertical barcode bars become horizontal bars.

2. **The contents were not kept inside the sticker.** The barcode image was stretched to a fixed pixel height (`object-fit: fill`). The price was allowed to paint outside its box (`overflow: visible`). At the default sizes, the name, barcode, SKU, and price together are taller than 25 mm, so the extra ink falls on the next sticker.

Receipt printing, KOT/BOT, and the old sheet-label preview were not involved.

## Correction

- One sticker is one printed page, with page margin 0.
- The name, barcode, SKU, cost code, and price stay inside the sticker. The barcode keeps its own proportions. The price is no longer allowed to spill out. A very long product name stays on two lines inside the sticker.
- **Print rotation** is saved with the label layout. The default is **90°**. The printed page is then the sticker height by the sticker width, with the content turned the other way. A driver that rotates the old landscape page clockwise places upright text on the 38 mm × 25 mm sticker. **0°** prints the preview as-is. **270°** is the other direction.
- The on-screen preview stays upright so the dashed sticker matches the roll you want, not the sideways page sent to that driver.

Barcode values, price formatting (`Rs 1,062.50`), quantity, and which fields are shown are unchanged.

## Files changed

- `resources/views/labels/browser_print.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `app/Http/Controllers/LabelsController.php`
- `app/Http/Controllers/BusinessController.php`
- `lang/en/lang_v1.php`

No other application files were edited. No database migration was added.

## Tests performed

The label Blade view was rendered with the real template and five stickers:

| # | Product name | SKU | Price | Copies |
| --- | --- | --- | --- | --- |
| 1–2 | Cap | 001 | 1,062.50 | 2 |
| 3 | Premium Embroidered Thobe With Extra Long Product Title For Label Wrapping | THB-88921-XL | 9.50 | 1 |
| 4 | A | 9 | 15,000.00 | 1 |
| 5 | Kids Thobe | 1234567890123 | 250.00 | 1 |

Business name: Marjan Thobe Center. Size: 38 mm × 25 mm. Barcode: Code 128.

### Print to PDF (Chrome headless)

This is a browser print, not a label printer.

**Rotation 0°** (page matches the sticker, no counter-rotation):

- 5 pages. No extra blank page.
- Each page measured 38.10 mm × 25.06 mm (Chrome rounds 38 × 25 mm).
- Pages 1 and 2 were identical and in that order.
- Text was horizontal. Barcode bars were vertical and not stretched.
- “Rs 1,062.50”, “Rs 15,000.00”, and “Rs 250.00” were complete.
- The long name used two lines and an ellipsis. It did not leave the page.
- The 13-digit SKU was complete.
- Dark pixels stayed inside the page, with about 0.7 mm of padding at the nearest edge.

**Rotation 90°** (new default):

- 5 pages, each 25.06 mm × 38.10 mm.
- On the PDF the content is sideways. That is the page sent to a driver that rotates clockwise.
- Each page was then rotated 90° clockwise in software, to stand in for that driver. The result was the same upright 38 mm × 25 mm label as rotation 0°: full price, vertical bars, long name inside the page.

### Screen preview

Chrome opened the same HTML as a normal page. The toolbar, the **Print rotation** control (set to 90°), and five separate dashed stickers were visible. The stickers did not overlap. Order was Cap, Cap, the long name, “A”, then Kids Thobe.

### Source checks

- `php -l` passed for `LabelsController.php`, `BusinessController.php`, and `lang/en/lang_v1.php`.
- The Blade view compiled and rendered, including `@page`, the rotation rule, and the five sticker blocks.
- Barcode images were still produced with the existing Code 128 generator. The template only changes how that image is sized.

## Tests not performed

- No label printer was attached. Nothing was printed on a physical roll.
- No barcode scanner was used.
- The live cPanel site was not updated.
- POS receipts, KOT/BOT tickets, product edit screens, and stock screens were not opened. Those files were not part of this change. The diff is limited to the five files above.

## Printer steps still required

The application cannot set the Windows print dialog by itself.

- Margins: **None**
- Scale: **100** (not Fit to page)
- Headers and footers: **Off**
- Do not change the paper to A4 or Letter. With rotation 90°, Chrome’s paper size is the swapped sticker size (25 mm × 38 mm for a 38 mm × 25 mm sticker).
- If the roll is still sideways, set **Print rotation** to **270°**, click **Save layout**, and print one sticker.
- If the text is upright but split across two stickers, set **Print rotation** to **0°**, save, and in the dialog choose **Landscape** and a paper size equal to the sticker width and height.
- Width is across the roll. Height is the sticker face only, not the gap. Leave gap detection to the printer.

## Unrelated functionality

No receipt, kitchen-ticket, sale, stock, product, or stored barcode logic was changed on purpose. Saving the label layout still writes only the browser-label settings on the business record.
