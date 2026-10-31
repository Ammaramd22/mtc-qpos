# POS product loading — test report

Date: 1 November 2026

## Cause

The POS screen loads products with a separate request after the page opens. Categories are printed with the page, so they can appear while that request fails.

The request was doing too much work before it returned the first 50 products:

- It joined every stock row for a variation, including rows with no location, so one product could be repeated and the sort grew very large.
- Size and color are stored as long text. Putting that text in the sorted query made MySQL build a heavy temporary result. On shared hosting that query sometimes ran out of sort memory or hit the time limit. The next attempt could succeed, which matches products appearing only after a refresh.
- The same request also looked up the latest purchase price once per row while sorting, and then looked up percentage prices again while drawing each card. One bad price or image stopped the whole grid.
- The product request is a GET. Browsers and some cPanel caches can reuse an empty or failed response, so Retry kept showing the failure.

The screen then showed "Could not load products" even though the product list page and the database had the products.

## What was checked here

| Check | Result |
| --- | --- |
| PHP syntax of `SellPosController.php` | Passed |
| Product grid and list Blade templates compile | Passed |
| `node tests/js/pos-product-loading.test.js` | Passed |
| `node tests/js/pos-product-list-view.test.js` | Passed |
| Local MySQL (`VsoftPOS` on 127.0.0.1) | Not running, so the live query and a signed-in POS sale were not executed on this machine |

## Expected result after upload

1. All Categories shows active products for the open register location, including products with no size or color, one size, many sizes and colors, and variations.
2. Inactive products stay hidden.
3. A category with products shows those products. A category with none shows the empty message, not the load error.
4. Name, SKU, and barcode search use the same list. Clearing the search restores it.
5. Switching categories quickly keeps the latest category.
6. Retry sends a new request. A network or server timeout is tried once before the error is shown.
7. Cart prices, discounts, stock totals, and receipts are not part of this change.

## Not verified in a browser

The POS site in the screenshots is the current live install. These file changes are not on that server until they are uploaded. This machine had no database server running, so the product query and a completed test sale could not be clicked through here.
