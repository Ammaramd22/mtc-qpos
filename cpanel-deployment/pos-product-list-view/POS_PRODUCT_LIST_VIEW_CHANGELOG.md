# POS product list view — changelog

## Why

Cashiers were scanning a photo grid. A compact row shows the name, SKU, stock, price, and size or color without using a full card, so more products fit in the same panel.

## What changed

- Touch POS product toolbar has **Grid** and **List**. Switching does not reload the page, does not clear the search, does not change the category, and does not touch the cart.
- The last choice is stored in the browser under `qpos.pos.productView`. No database setting and no migration.
- List rows are rendered from the same product suggestion HTML as the cards. The card markup is still there and is what Grid view shows.
- Clicking a row uses the existing product-card click. The **Add** label does not have its own click handler, so one tap adds the product once.
- The suggestion query now also returns the category name, and size and color when those product columns exist. Products, stock, and prices are not written.

## Files

| File | Change |
|---|---|
| `resources/views/sale_pos/partials/touch_main.blade.php` | Grid / List switch and the list header |
| `resources/views/sale_pos/partials/product_list.blade.php` | List cells inside each existing product card |
| `public/css/pos-touch.css` | List row layout. Grid card rules are unchanged unless List is selected |
| `public/js/pos-touch.js` | View switch, saved preference, and show size or color columns only when a loaded product has that value |
| `app/Http/Controllers/SellPosController.php` | Category, size, and color on the existing suggestion query |

## Layout

On the usual POS screen the cart sits beside the products, so the product panel is not wide enough for a full Size and Color column set. Those values show as small chips under the name, and only when the product has them. If the product panel is at least 780px wide and at least one loaded product has a size or a color, that column is shown. Stock, SKU, price, and Add stay in columns.

Category is a second line under the name on **All Categories**. It is hidden when one category is selected.

## Unchanged

Cart maths, payment, receipts, barcode enter-to-add, search requests, category filtering, stock checks, discounts, shipping, and MRP selection. No new frontend library. No database migration.
