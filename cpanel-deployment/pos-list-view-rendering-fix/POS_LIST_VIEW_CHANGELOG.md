# POS list view rendering fix

## Why

List View was still the grid card. The list header and the list fields were inserted into the same card HTML and hidden with CSS. On the live POS that CSS did not replace the card, so the header sat above the cards and the name, SKU, stock, and Add text were shown twice.

## What changed

- Grid View renders only the original product card (`product_list.blade.php`).
- List View renders a separate row template (`product_list_rows.blade.php`): thumbnail, name, optional category, optional size chip, optional color chip, SKU / barcode, stock, selling price, Add.
- Empty size and color are omitted. There is no blank Size or Color column.
- The product suggestion request sends `product_view=list` or `product_view=grid`. The controller returns only that template.
- Clicking Grid or List replaces the product HTML through the existing suggestion request. The POS page does not reload. Search text, category, and the cart are left as they are.
- A list row is still a `product_box`, so the existing tap-to-add, quantity popup, variation, price mode, and stock data attributes are unchanged.
- Add does not have its own click. The click reaches the row once.
- On a narrow product panel, SKU and stock move under the name. Price and Add stay on the row.

## What did not change

- Cart totals, discounts, shipping, and payment.
- Product records, stock quantities, and prices.
- Barcode search, category filtering, and the suggestion page size.
- No database migration and no new frontend package.
