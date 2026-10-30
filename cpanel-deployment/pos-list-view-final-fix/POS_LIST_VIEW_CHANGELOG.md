# POS list view changelog

## What was wrong

List View was still drawn inside the five-column product grid. The heading and every product became one narrow grid cell, so names, SKU, stock, and price stacked like a card. Thumbnails had no size, so they collapsed. The heading did not span the product panel.

## What this package changes

List View now uses its own template, `resources/views/sale_pos/partials/product_list_rows.blade.php`.

- The heading and the product rows sit inside one full-width wrapper. That wrapper spans every column of the existing product grid, so a cached five-column layout cannot squeeze the table.
- Each product is one horizontal row with eight aligned columns: image, product name, SKU / barcode, size, color, stock, selling price, and Add.
- The thumbnail is a 36×36 image. Long names stay on one line and end with an ellipsis. The row title shows the full name and SKU.
- Size and Color stay as columns so every row lines up. The cell is blank when that product has no value.
- On screens 767px wide and below, SKU, Size, and Color are hidden. The row keeps the thumbnail, name, stock, price, and Add.
- Grid View is still the original product-card template. Switching views reloads only the product list through the existing suggestion request and keeps the category, search text, and cart.
- Clicking a row uses the same product-box add path as a grid card. The Add label does not receive its own click.

## Files

- `resources/views/sale_pos/partials/product_list_rows.blade.php` — list table markup
- `resources/views/sale_pos/partials/product_list.blade.php` — chooses the list template when the request asks for list
- `resources/views/sale_pos/partials/touch_main.blade.php` — Grid and List buttons, and the suggestion URL that carries the selected view
- `public/css/pos-touch.css` — full-width wrapper and mobile column rules
- `public/js/pos-touch.js` — view switch
- `public/js/pos.js` — sends the selected view with the product suggestion request
- `app/Http/Controllers/SellPosController.php` — passes the view to the product list and selects size and color when those columns exist

No product records, stock quantities, prices, cart maths, or payment code were changed. No database migration was added.
