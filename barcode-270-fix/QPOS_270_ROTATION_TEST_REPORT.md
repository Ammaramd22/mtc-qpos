# 270° duplicate sticker — test report

Date: 29 October 2026. Local QPOS source, PHP 8.5.9, Chrome headless. No live database was written. The physical printer was not used in this test. The photograph supplied with the request is the production sample: one “Cap” label at 270° was split by the gap onto a second sticker, with the shop name on the first sticker and the price on the second.

## Root cause

The 270° rule sets `.label-inner` to 25 mm × 38 mm and then rotates it. The page is 38 mm × 25 mm. The unrotated layout height is the sticker width (38 mm), which is taller than the sticker (25 mm), so the browser print layout continues onto the next sticker. The preview did not show this because the screen clips the box. The page still contained one label element; the bug was the box height, not a second copy in the loop.

## File changed

`resources/views/labels/browser_print.blade.php`

## Correction

90° and 270° print as one bitmap per requested sticker. The bitmap is the preview artwork at 4× resolution, sized to the sticker. 0° and 180° still print as HTML, because their layout box is already the sticker size. The live preview is not part of `.sheet` and is not printed.

## Results

Chrome loaded the label page and the Print button was invoked with `window.print` recorded. Barcode height was 80 px, matching the screenshot. SKU `0021`, price `Rs 1,062.50`.

| Case | Sheet labels | Print images | Elements taller than the sticker |
| --- | --- | --- | --- |
| 270°, one copy | 1 | 1 | 0 |
| 270°, two copies | 2 | 2 | 0 |
| 90°, one copy | 1 | 1 | 0 |
| 0°, one copy | 1 | 0 (HTML, as before) | 0 |
| 180°, one copy | 1 | 0 (HTML, as before) | 0 |

The live preview stayed a separate single sticker in every case (not added to the sheet).

The 270° bitmap measured **576 × 376** pixels, which is a 38 mm × 25 mm sticker at 4× (about 144 × 94 CSS pixels). It shows one landscape sticker: the shop name and “Cap” turned along the left, the barcode across the middle, `0021`, and the price along the right. It is not two stickers stacked.

## Not performed

- No physical print. Confirm in the browser dialog that one copy is **one** page, then print one sticker.
- Profile save was not written to a database. Rotation is still the same saved field.
- Receipts, KOT/BOT, products, stock, and payments were not opened. Those files were not changed.

## Printer setup

Margins **None**, scale **100%**, no headers or footers. Do not switch the paper to A4. Width 38 mm is across the roll. Height 25 mm is one sticker face.
