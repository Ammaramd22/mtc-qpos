# POS product loading — test report

Date: 30 October 2026

The live POS at `https://mtc.qpos.avenque.io/pos/create` was not logged into and was not modified. Checks below were run on the project files, a Laravel view render, and a local browser harness that loaded the real `public/js/pos.js` loader with a stand-in product response.

## Root cause

Confirmed in code and reproduced with the old scroll condition:

- `scrollTop + innerHeight >= scrollHeight` is true when the product panel is taller than the cards (`scrollTop` 0, panel 700, content 420).
- That path incremented the page and could abort the page 1 request.
- The empty Blade block was then appended, which is the "No Products to display" message and Retry button in both screenshots.

The replacement scroll guard returns false for that short-grid case. An empty page 2 response is classified as end-of-list and is not written into the grid when cards are already visible. If cards are not visible, the loader requests page 1 again once.

## Files changed

- `public/js/pos.js`
- `public/js/pos-live-search.js`
- `public/js/pos-touch.js`
- `public/css/pos-touch.css`
- `resources/views/sale_pos/partials/product_list.blade.php`
- `config/constants.php` (`asset_version` 919 → 920, local only, not in the ZIP)

Git diff stat for those files: 294 insertions, 74 deletions.

## Backend

`SellPosController::getProductSuggestion` was not edited. Business, branch/location, active, not-for-selling, and category (`category_id` or `sub_category_id`) filters are unchanged. Page size remains 50.

The Blade partial was rendered through Laravel 9 without the database:

| Case | Result |
|---|---|
| Page 2, 12 products exist, current page empty | Hidden `#suggestion_end` only. The words "No Products to display" are absent. |
| Page 1, zero products | "No Products to display", `#no_products_found`, and Retry. |
| Page 1 search with zero matches | "No matching products found". Retry button absent. |
| Page 1 with two fake cards | Two `product_box` nodes, SKU `62292014102165`, stock text, no empty state, no end marker. |

Command: `php tests/pos_product_list_render.php` — 15 PASS, exit 0. PHP printed existing Laravel 9 deprecation notices on PHP 8.5. Those notices were already present and are unrelated to this patch.

## Frontend and AJAX handling

`node --check` passed for `public/js/pos.js`, `public/js/pos-live-search.js`, and `public/js/pos-touch.js`.

`node tests/js/pos-product-loading.test.js` passed. It executes the real guard functions from `pos.js` and checks the wiring:

| Check | Result |
|---|---|
| Short grid does not request page 2 | Pass |
| Scroll during reset, while loading, or after the list ends | Pass, blocked |
| Top of a long list does not request the next page | Pass |
| Near the bottom of a long list does | Pass |
| 12 cards on page 1 replace the grid and mark the list ended | Pass |
| 50 cards can load another page; a later full page is appended | Pass |
| Empty page 2 while cards are visible is ignored | Pass |
| End marker while cards are visible is ignored | Pass |
| End marker with no cards requests recovery of page 1 | Pass |
| Older request id is ignored | Pass |
| Real empty page 1 still shows the empty state | Pass |
| HTML that is not a product response becomes an error, not an empty catalogue | Pass |
| Invalid later page rewinds the page number | Pass |
| Loading markup does not contain `no_products_found` | Pass |
| Error markup contains Retry and does not contain `no_products_found` | Pass |
| Scroll lock is set before the grid is cleared | Pass |
| A blocked later page does not abort the current request | Pass |
| All Categories sets `#suggestion_page` back to 1 | Pass |
| Live search does not clear the grid with `.html('')` before the request | Pass |

## Browser (Chromium)

A local page loaded jQuery and the real `public/js/pos.js`, then answered the suggestion URL from the page so no live sale was created. Results read from the page after the run:

| Step | Result |
|---|---|
| First paint | 12 cards. "No Products to display" was not shown. |
| Scroll the grid | Still 12 cards. Empty message still absent. |
| Category attar | 3 cards: ROSE BERRY, SAKEENA, WHITE OUD. |
| Empty category | Previous cards removed. Text was "No Products to display" and Retry. The message spans the grid. |
| Forced request failure | "Could not load products." and Retry. The empty-catalogue sentence was not used. |
| Retry after that failure | 12 cards returned. Empty message absent. |
| All, then attar, then All in one turn | Final grid was the 12 All Categories cards. No mixed names, no leftover loading text, no empty message. |
| Category click before the response | Heading "Loading products..." was on screen and the previous cards were gone. |

Screenshots of the harness showed the 12-card grid, the 3-card attar grid, and the full-width empty state with Retry. The harness layout is a narrow test page, so card text wraps more than on the live POS. The live card markup was not redesigned.

## Search and barcode

- Source check: search still writes `window.global_p_search_term` and `get_product_suggestion_list` still sends `term` from that value or from `#search_product`.
- Blade render: a search with no rows returns "No matching products found" and does not offer Retry as if the catalogue were empty.
- Numeric strings of 8 or more digits still skip the live grid filter in `pos-live-search.js`, which is the existing scanner path.
- A physical scanner and the live `/products/list` barcode lookup were not exercised.

## Loading, empty, and error states

| State | When it appears | Observed |
|---|---|---|
| Loading products... | Page 1 request is running | Yes, in the browser, before cards returned |
| No Products to display | Page 1 succeeds and the filter has zero products | Yes, Blade render and browser empty category |
| Could not load products. + Retry | The request fails, or the body is not a product response | Yes, browser forced failure and Retry |
| End of list | Page 2+ is empty | Marker only. Cards left in place in the unit test and the scroll step |

Retry uses the current `global_p_category_id`, brand, location, stock filter, and search term. The browser retry restored All Categories after the forced error.

## POS regression

These areas were not executed against a sale:

- Adding a card to the cart
- Quantity, price, and MRP popups
- Variations
- Discounts, taxes, and totals
- Payment and receipt printing

The card HTML (`data-variation_id`, name, SKU, stock, price) is the same loop as before. The touch card click handler in `pos-touch.js` was not changed. Cart, payment, and receipt files were not edited.

## Not performed

- Sign-in to the live Marjan Thobe Center POS, so the real categories Cap, counter, MISWAK, and PERFUME were not clicked against MySQL.
- A second browser. The checks used the Cursor Chromium browser. Edge was not opened separately.
- DevTools network throttling. The harness delayed responses by about 30ms, which was enough to see the loading heading.
- Session expiry and CSRF failure on the live site. The suggestion call is GET. An unexpected HTML body is now an error with Retry rather than an empty catalogue; that branch was unit-tested with a fake "Server Error" body.
- A full checkout.

## Limitations

- Cashiers who still have the old `pos.js` cached will see the old behaviour until `asset_version` is increased on the server, or until they hard-refresh. The ZIP does not replace `config/constants.php`.
- If `bootstrap/cache/config.php` exists, the version edit is ignored until that cache file is deleted in File Manager.
- A category that truly has no products for the current location will still show "No Products to display". That is the successful empty result.
