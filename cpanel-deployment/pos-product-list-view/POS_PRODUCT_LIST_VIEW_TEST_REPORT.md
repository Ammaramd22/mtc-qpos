# POS product list view — test report

Date: 31 October 2026

This was not tested by logging into the live register at `https://mtc.qpos.avenque.io/pos/create`. Payment, receipt printing, a USB barcode scanner, and live stock checks were not run against the live database.

## Automated

| Check | Result |
|---|---|
| `php -l` on `SellPosController.php` | No syntax errors |
| `php tests/pos_product_list_render.php` | Passed. Empty first page still says no products. A later empty page does not. Search with no match does not say "No Products to display". Cards still include the grid name, SKU, and stock. Each card also includes one list block. Size and color are marked only when the product has them. |
| `node tests/js/pos-product-list-view.test.js` | Passed. Grid markup remains. The view switch does not call the product loader. The Add label is a span and has no click handler. The suggestion query reads category, and size and color only if those columns exist. |
| `node tests/js/pos-product-loading.test.js` | Passed. Existing product-loading guards were not changed. |

## Browser harness

A local page loaded the real `pos-touch.css` and product HTML from the POS product-list Blade view. It is not the live POS, and it does not call the cart or payment code. Clicks were counted on the same product card the live screen uses.

| Check | Result |
|---|---|
| Grid, before switching | Cards showed name, SKU, stock, and price. Ten products. No page overflow. |
| Switch to List | No page reload. List button became the active control. |
| Narrow product panel (about 600px, the width left beside the cart at 1440×900) | Rows showed image, name with ellipsis, full sample SKU, stock, price, and Add. No overlapping columns. |
| Size and color | Shown as chips when present (`Size Large`, `Black`). A product with neither did not get an empty size or color column. |
| Long name | Did not widen the page. On a phone-width panel it wrapped to two lines. Beside the cart it ellipsized. |
| One click on a row | Cart count went from 0 to 1. |
| Click on the Add label | Count went from 1 to 2. The hit target was the product card, so the product was not added twice. |
| Cart after those clicks | Still 2. Switching the view is not what added the lines. |

Size and color columns were not shown in that 600px panel. They are reserved for a product panel at least 780px wide. The standard POS layout keeps the cart beside the products, so chips are the layout cashiers will see.

## Not verified on the live site

- Signing in and loading `/pos/create` from the server
- AJAX product loading against the live catalogue
- Barcode scanner hardware
- MRP / price-group changes on a real bill
- Out-of-stock rejection from the server
- Discount, shipping, payment, and receipt printing
- Phone and tablet on the live register (only the local harness was resized)

After cPanel install, use the checks in `CPANEL_INSTALLATION_GUIDE.md` on one register before the other registers rely on List view.
