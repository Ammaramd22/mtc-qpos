# QPOS 80mm thermal receipt fix

## Fixed

- Classic 80mm thermal receipts print solid black text on white paper. Grey labels, light fills, overlapping SKU ghost text, and duplicate Paid lines are corrected.
- Browser 80mm print margins/padding no longer squeeze or clip the roll. Theme and designer accent colours do not affect thermal ink.
- Still uses the Invoice Layout Management design (Classic 80mm / Classic 80mm Mobile). No new layout and no calculation changes.

# QPOS POS product loading fix

## Fixed

- Product cards compile again. A size/color line in the card template was invalid PHP, so the product request failed before any product could be shown.
- Opening POS loads the product cards from the database without a manual refresh.
- The product request no longer fails when size or color text is long. Those values are read as short display text, so the database sort can finish.
- Stock is read once per variation for the open business location. Products are not dropped or repeated because of extra stock rows.
- A missing tyre size column no longer stops the product list.
- Category, search, barcode, and SKU filters use the same product query. All Categories shows every eligible product again.
- A failed load is retried once. The Retry button starts a new request. The browser does not keep an empty or failed product response.
- If the sign-in session has ended, POS says so instead of looking like there are no products.
- One bad price row or product image no longer hides the rest of the products.
- Inactive products stay off the POS grid. Products with no size, one size, many sizes, colors, or variations still appear when they are active and assigned to the location.

## Unchanged

- No database change. Product rows, sizes, colors, stock, prices, discounts, receipts, and payments are not modified.
- Size Management and Color Management are not modified.

# QPOS advanced label editor

## Added

- Print Labels asks for one size and one color when the product has them. The label prints that choice only.
- The same product can be added again with a different size or color. Each row keeps its own choice.
- Products with only sizes, only colors, both, or neither still print.
- The live label preview can be clicked. Business name, item name, size, color, barcode, SKU, price, cost code, brand, and category can be edited there.
- Size and color use a list of the product's real values. The other items can be typed.
- Font size, weight, alignment, show or hide, and drag position apply to the preview and the printed stickers together.
- Save layout and Save as profile keep positions and element styling. Older profiles still open.
- A price typed on the label is only for that print. It does not change the product price, cost, MRP, sales, or POS totals.

## Unchanged

- Size Management, Color Management, stock, product create, and POS calculations are not changed.
- No new database tables. Existing size and color rows are only read.

# QPOS colour add fix

## Fixed

- Add Product and Edit Product: typing a colour and pressing Add, or pressing Enter, adds it as a removable tag.
- Empty text is ignored. The same colour cannot be added twice on one product.
- Extra spaces are removed. A colour still typed in the box is included when the product is saved.
- The barcode scanner on the product form no longer takes the Enter key away from the colour box.
- Saved colours stay in the product colour rows and in the colour text used by the product list, POS, and print labels.

## Unchanged

- Size selection, stock quantities, product variations, and POS totals are not changed.
- No database change is required for this fix.

# QPOS size dropdown fix

## Fixed

- Add Product and Edit Product load the Size list from Size Management only.
- Old product size text that was copied into the sizes table (for example 105, 110, 30, 32) is no longer offered when adding a product.
- The Sizes page lists the same sizes, written into the page itself, so the list no longer depends on a background request.
- Adding a size in Size Management makes it available on Add Product. Renaming it updates the name. Deleting it removes it from new products when no product still uses it.
- A size already saved on a product still appears when that product is edited, so saving the product does not drop it.
- Adding a name that already exists only as old product text keeps that row and makes it available in Size Management.

## Unchanged

- Colour entry, stock quantities, product variations, POS totals, purchases, and payments are not changed.
- Existing products, size rows, and product-size links are not deleted.

## Database

- New column: `sizes.is_catalog` (0 = not offered on new products, 1 = saved from Size Management).
- Run `database/cpanel_size_dropdown_fix.sql` in phpMyAdmin. Do not re-run the earlier size and colour script unless you are installing that feature for the first time.

# QPOS size and colour update

## Added

- Sizes page under Products. Add, edit, delete, search, and duplicate-name checks. A size that is still assigned to a product cannot be deleted.
- Add Product and Edit Product: searchable multi-select for sizes, and a tag field for colours. Both are optional. Selections are kept when editing or duplicating a product.
- Many sizes per product, and many products per size. Colours are stored per product and cannot be repeated on the same product.
- Product list, product view, POS, and print labels read the size and colour text already shown on the product.
- POS cart: one size or colour is shown on the line. Several sizes or colours become a choice on that line. Stock quantity and price are unchanged.
- Print Labels already had Size and Color switches. Multiple values wrap onto two lines so they stay inside the label, including 50mm × 25mm.

## Unchanged

- Product variations still create their own barcodes and stock. This update does not add a stock row for every size and colour.
- Sales totals, purchase totals, stock quantities, and payments are not recalculated by this change.
- Existing products with no size or colour stay valid. Existing size and colour text is copied into the new tables.

## Database

- New tables: `sizes`, `product_size`, `product_colors`.
- New optional columns on `transaction_sell_lines`: `line_size`, `line_color`.
- `products.size` and `products.color` are widened to text and kept as the display copy (`S, M, L` and `Black, White`).
