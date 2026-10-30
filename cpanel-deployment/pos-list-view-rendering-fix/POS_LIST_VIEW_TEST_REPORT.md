# POS list view test report

Test date: 31 October 2026. These checks were run on the local project. They were not run on https://mtc.qpos.avenque.io.

## Automated

| Check | Result |
|---|---|
| `php -l app/Http/Controllers/SellPosController.php` | No syntax errors |
| `php tests/pos_product_list_render.php` | Pass. Grid HTML has cards and no list rows. List HTML has one row per product, one header, no card image, no card text block, one name element per row, one SKU column per row, size and color chips only on the product that has them. Page 2 of a list does not repeat the header. Empty and search-miss messages are unchanged. |
| `node tests/js/pos-product-list-view.test.js` | Pass |
| `node tests/js/pos-product-loading.test.js` | Pass |
| `node --check public/js/pos-touch.js` | Pass |

PHP printed existing Monolog and Laravel deprecation notices on PHP 8.5. Those notices were already present and were not changed.

## Browser harness

Served `tests/js/pos-product-list-view-harness.html` and exercised Grid and List in the browser. The harness uses the real Blade output and `pos-touch.css` plus `pos-mobile-retail.css`. It does not log into the live POS, so it does not call the live product API.

At a 1440×900 window (product panel about 584px wide):

- List showed 10 full-width rows, stacked, `display: grid`.
- Grid card pieces were absent: 0 `.image-container`, 0 `.text_div`, 0 `.col-md-3`.
- Header and rows used the same columns: thumbnail, product, SKU / barcode, stock, price, Add.
- The long product name ellipsized (166px visible, 554px of text). It did not wrap over the SKU.
- Barcode `62920141012010` fit in the SKU column (118px, not clipped).
- Stock values such as `19.00 Pc(s)` fit. Prices such as `OMR 29.00` fit. Add fit. No column overlap.
- Size and color chips appeared only for products that have them. The product without them had no empty chip.
- Choosing Cap hid the category line. All Categories showed it again.
- One click on a row increased the harness cart from 0 to 1. The point on top of Add resolved to the row, and one further activation increased the cart from 1 to 2. The log was variation id `1` twice, not a second handler.
- Switching to Grid left the cart at 2, removed the rows and header, and restored 10 cards with images (image height 78px).
- Switching back to List left the cart at 2.

At a 390×844 window:

- 10 full-width rows, no card images, no card text.
- SKU column hidden. SKU and stock shown under the name. Name limited to 2 lines.
- Price and Add did not overlap. The page did not scroll sideways.
- Cart stayed at 2.

## Not tested

These were not run against the live register:

- Login to https://mtc.qpos.avenque.io/pos/create
- The live product suggestion response
- A USB barcode scanner
- MRP / price-group change on a real bill
- Server rejection of an out-of-stock sale
- Discounts, shipping, payment, and receipt printing

After upload, check Grid, List, search, category, barcode, and one add on the register before using List View for sales.
