# Print Labels search fix

Print Labels was not searching the same catalog as Products > List Products, and it could not find a product by its assigned size or color.

## What was wrong

- Inactive products are visible in List Products, but label search required `is_inactive = 0`, so those products never appeared.
- Search joined every variation and then kept only 25 rows. A product with many variations could fill that window, and other matching products never showed. That cap was not a page of the product list, but it behaved like one.
- Size and color were not search fields. Typing `54` looked through names, SKUs, and barcodes only.
- A number inside a product name was treated the same as an assigned size.

## What changed

- Search reads the full product catalog for the business. It does not load a datatable page and filter that page.
- One suggestion is returned per product, so variations no longer crowd out other products.
- Active and inactive products are both searchable. Inactive rows are marked in the suggestion list.
- Products with zero stock stay searchable. Stock tables are not joined.
- Location rules match List Products: a user who can see every location searches every location; a user limited to locations only sees products in those locations.
- Products without a size or color are included. Size and color are matched with `EXISTS`, not an `INNER JOIN`.
- Search matches product name, SKU, barcode (`sub_sku`), assigned size, and assigned color.
- A pure number such as `54` matches an assigned size, SKU, or barcode. It does not match a product only because `54` appears in the name. `154` and `540` do not count as size `54`.
- Colors match the assigned color name. `Whi` matches `White`. `white` does not match `Offwhite`.
- Suggestions show the name, SKU, barcode when it differs, sizes, colors, and an inactive badge.
- Choosing a suggestion adds the product. Typing does not. The same product and size/color increases the label quantity. Another size or color can still be added with **Another size / color**.
- The size and color printed are the ones assigned to that product. The first preview shows them. Uncheck Size or Color on the preview and rebuild if a label should hide them.
- When the sizes are real stock variations, the label uses that variation’s barcode. Catalog sizes keep the product barcode and print the chosen size as text.

No database tables or columns were added. Existing products, stock, prices, and sales are unchanged.
