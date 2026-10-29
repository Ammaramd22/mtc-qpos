# Barcode orientation test report — 50 × 25 mm

## Root cause

At **0°** the label was one upright **50 × 25 mm** page. On the roll that page is turned **counter-clockwise**. “Marjan Thobe Center” reads upward on the left, the price sits on the upper sticker, and the 50 mm side runs along the feed, so the liner gap cuts through the barcode. An earlier print of the same upright page was turned the other way and still used two stickers. Changing the print window between Portrait and Landscape flipped the direction and left the feed length at 50 mm.

The preview was also enlarged past its column, so the name and price looked cut off on screen.

## Correction

For a roll sticker that is wider than it is tall, the print page uses `page-orientation: rotate-right`. Chrome’s PDF for a 50 × 25 mm label is then one **25.06 × 50.12 mm** page with the design turned clockwise, which is the opposite of the latest roll photo. The same printer turn that produced that photo puts the shop name back across the top and the 25 mm side along the feed.

The on-screen preview is not turned. It still shows the upright sticker. A portrait roll size such as 25 × 38 mm is not given this extra turn. The barcode still shrinks so the price stays inside the sticker.

## Files changed

- `resources/views/labels/browser_print.blade.php`
- `lang/en/lang_v1.php` (the label-page hint only)

No controller, route, database, product, receipt, or stock file was changed.

## What was tested

Chrome headless print-to-PDF, then page size and text measured from the PDF. The preview was also opened in headless Chrome at 1600 × 1000.

| Case | Labels | PDF pages | Page size sent to the printer | Text |
| --- | --- | --- | --- | --- |
| 50 × 25 mm, 0° | 1 | 1 | 25.06 × 50.12 mm, design turned clockwise | Marjan Thobe Center, Cap, 0021, Rs 1,062.50 |
| 50 × 25 mm, 90° | 1 | 1 | 25.06 × 50.12 mm | Same text, plus the selected turn |
| 50 × 25 mm, 180° | 1 | 1 | 25.06 × 50.12 mm | Same text, plus the selected turn |
| 50 × 25 mm, 270° | 1 | 1 | 25.06 × 50.12 mm | Same text, plus the selected turn |
| 38 × 25 mm, 0° | 1 | 1 | 25.06 × 38.10 mm, same clockwise feed correction | Full name and Rs 1,062.50 |
| 25 × 38 mm, 0° | 1 | 1 | 25.06 × 38.10 mm, no extra turn | Name ellipsized to the narrow width, Rs 9.50 present |
| 50 × 25 mm, 0°, two copies | 2 | 2 | 25.06 × 50.12 mm each | Full price on each page |

The on-screen preview for 50 × 25 mm at 0° stays upright: shop name on top, `Rs 1,062.50` inside the white sticker. That preview is in the toolbar and is omitted from the print.

## Physical printer

The roll was not printed from this environment. The photo supplied with the request is the evidence for the Portrait turn. Do not treat the PDF pages above as a physical print.

## Still required at the printer

Print with **Landscape**, margins **None**, and scale **100**, the same window used for the latest sideways sticker. The Chrome sheet can look tall. That is the counter-turn.

The roll was not printed from this environment. If this copy is upright but still crosses two stickers, or upright and upside down, the printer’s turn is not the one in the latest photo.
