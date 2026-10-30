# POS product loading fix

The POS product panel sometimes drew **No Products to display** even though products existed. The same screen could show the cards and that message together, or only the message, until the cashier refreshed `/pos/create`.

## Root cause

`/pos/create` loads the grid with `GET /sells/pos/get-product-suggestion`, 50 products per page. `public/js/pos.js` appended every response into `#product_list_items`.

The scroll handler requested the next page when `scrollTop + innerHeight >= scrollHeight`. That comparison is also true when the cards are shorter than the panel. Clearing the grid for a new category fires a scroll event, so the handler often ran during the first request:

1. Page 1 started and cleared the grid.
2. The scroll handler incremented `#suggestion_page` and started page 2.
3. Page 2 aborted page 1.
4. Page 2 of a short catalogue is empty. The Blade `@empty` block is the "No Products to display" message and the Retry button.
5. That HTML was appended into the grid.

If page 1 had already finished, the message sat in the grid next to the cards (first screenshot). If page 1 was aborted, the cleared grid received only that message (second screenshot, category **attar**). The next refresh could succeed until the next scroll or category click. Retry could take the same path.

The product query was not excluding those products. Business, location, active, and category filters were left as they are.

## What changed

| File | Change |
|---|---|
| `public/js/pos.js` | The grid request now keeps a sequence number, ignores an older response, and does not let a later page abort page 1 while page 1 is running. Scroll requests the next page only when the list overflows, the user is near the bottom, and no request is in flight. A short first page is treated as the end of the list. Page 1 shows "Loading products..." until a valid response arrives. A failed or unexpected response shows "Could not load products." and Retry. An empty later page is not drawn. If that later page arrives after page 1 was lost, page 1 is loaded once. **All Categories** resets the page to 1. |
| `public/js/pos-live-search.js` | Search no longer empties the grid before the loader runs, which was another way to fire the scroll handler. The typed term is still sent with the category request. |
| `public/js/pos-touch.js` | The touch category rail sets the same scroll lock and resets the page before each category load, including the first load. |
| `resources/views/sale_pos/partials/product_list.blade.php` | Page 2 or later with zero rows returns a hidden end marker. The visible empty state remains on page 1 only. |
| `public/css/pos-touch.css` | The loading, empty, and error blocks span the whole product grid instead of one card cell. |
| `config/constants.php` | Local `asset_version` increased from 919 to 920 so browsers request the new assets. This file is **not** in the cPanel ZIP. Increase the live number by 1 in File Manager. |

## What stayed the same

- Product query filters: business, location, inactive, not-for-selling, category or subcategory, brand, and the 50-row page size.
- Card click, quantity, price, MRP, variations, stock, barcode add, cart totals, discounts, taxes, payment, and receipt printing.
- No database migration, no stock or price edits, no Laravel upgrade.

## Package

`QPOS-POS-Product-Loading-Fix-cPanel.zip` is a minimal patch of the five application files listed above, excluding `config/constants.php`.
