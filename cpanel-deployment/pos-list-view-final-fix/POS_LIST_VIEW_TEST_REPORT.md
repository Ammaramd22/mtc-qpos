# POS list view test report

Date: 31 October 2026

The live register at `mtc.qpos.avenque.io` was not opened. The host could not be resolved from this machine, and this package was not uploaded to the server. The checks below were run locally against the POS product-list harness and the rendered Blade output.

## Passed locally

Desktop product panel, about 584px wide, with the parent product area forced back to the live five-column grid:

- List wrapper, heading, and every product row measured 584px, the full product panel.
- The heading and the rows used the same eight tracks: 36px, flexible name, 110px, 42px, 46px, 62px, 70px, 44px.
- Rows stacked vertically. There were no grid-card image containers inside the list.
- Each row contained a 36×36 image.
- A long product name stayed on one line with an ellipsis. The SKU `62920141012010` stayed on one line.
- Clicking a row added that product to the harness cart once.
- Switching back to Grid left the cart line in place and restored ten product cards.

Phone width 390px, again with the parent forced to a five-column grid:

- Wrapper, heading, and row measured 365px, the full product panel.
- SKU, Size, and Color were hidden.
- Stock, price, and Add stayed visible, in tracks of 64px, 72px, and 46px.
- The page did not grow wider than the screen. The row did not scroll sideways.

Automated checks:

- `php tests/pos_product_list_render.php`
- `node tests/js/pos-product-list-view.test.js`
- `node tests/js/pos-product-loading.test.js`

These confirmed separate list markup, one product box per row, the view query on the suggestion request, and the existing product-loading guards.

## Not tested on the live register

- Logging in to `https://mtc.qpos.avenque.io/pos/create`
- The live product catalogue and product photos from the server
- USB barcode scanning
- MRP price mode on a real till
- Server-side out-of-stock rejection
- Discounts, cart totals, payment, and the receipt

After installing through cPanel, check those on one register before using the list during a sale. Grid View should still be the original cards. List View should be the full-width table described above. The cart must keep its lines when the view changes.
