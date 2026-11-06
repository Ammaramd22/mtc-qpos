# Test report — Print Labels search

Date: 6 November 2026

Local MySQL (`127.0.0.1:3306`, database `VsoftPOS`) refused the connection, so the live catalog could not be queried from this machine. The live site was not updated. Browser clicks on the live Print Labels page were not run.

What ran here:

- `php -l` on `LabelsController.php`, `LabelProductSearch.php`, and `BrowserLabelLayout.php` — no syntax errors.
- `node --check public/js/labels.js` — no syntax errors.
- `php tests/label_product_search_test.php` — passed.
- `php tests/label_editor_logic_test.php` — passed.
- `php tests/label_product_search_db_test.php` — skipped, database connection refused.

## Rules covered by the automated test

| Check | Result |
| --- | --- |
| Size `54` matches `54, 56, 58, 60` | Pass |
| Size `54` does not match `154` or `540` | Pass |
| A number is not treated as a product-name search | Pass |
| `Whi` matches color `White` | Pass |
| `white` does not match `Offwhite` | Pass |
| Catalog sizes win over the text column and over variations | Pass |
| Text column is used when the catalog is empty | Pass |
| Variation sizes are used only when the product has no catalog or text sizes | Pass |
| Mixed search `AL HADEEL 54` preselects size `54` | Pass |
| Ambiguous color prefix is not preselected | Pass |
| Selected size `50` and color `White` are the values that print | Pass |
| Suggestion window is 40 products, not a product-list page | Pass |

## Scenario list

| # | Scenario | Result |
| --- | --- | --- |
| 1 | Full product name | Code searches `products.name` across the whole business. Not clicked on the live page. |
| 2 | Partial product name | Same name match, minimum 2 characters. Not clicked on the live page. |
| 3 | SKU | `products.sku`, including leading zeros on numeric SKUs. Not clicked on the live page. |
| 4 | Barcode | `variations.sub_sku`. Exact barcode selects that variation. Not clicked on the live page. |
| 5 | Size | Exact assigned size from `product_size`, `products.size`, or a Size variation. Unit-tested. |
| 6 | Color | Assigned color, exact or prefix. Unit-tested. |
| 7 | Several sizes | All assigned sizes are listed. The chosen one is stored as `label_size`. |
| 8 | Several colors | Same for `label_color`. |
| 9 | No size | Product still matches by name, SKU, or barcode. No size join is required. |
| 10 | No color | Same. No color join is required. |
| 11 | Zero stock | Stock is not joined and quantity is not filtered. |
| 12 | With image | Image is not a filter. |
| 13 | Without image | Image is not a filter. |
| 14 | Products past the first list page | Query has no page offset. The old 25-variation cap is gone. |
| 15 | Other business locations | Same permitted-location rule as List Products. Users who can see all locations are not filtered. |
| 16 | Select a suggestion | Click or Enter adds the row. Typing does not. |
| 17 | Choose size and color | Dropdowns list only that product’s values. Search by size preselects it. |
| 18 | Several products | Each selected product is a row. Repeat of the same size increases quantity. |
| 19 | Label preview | Chosen size and color are passed into the browser print data. First preview turns Size and Color on when a value exists. |
| 20 | Printed output | Not sent to a printer from this machine. Preview uses the same sticker data the print dialog uses. Barcode value is still the SKU / variation barcode. |
| 21 | Saved label profiles | This update does not rewrite saved profiles. A rebuild that posts Size/Color off keeps them off. |
| 22 | Light and dark theme | Suggestion, table, and empty-state styles include both themes. Not screenshotted. |
| 23 | Desktop and mobile | Search, suggestion menu, size dropdown, and print button have a 640px layout. Not screenshotted. |

## After upload, check on the live site

1. Search a product that is on page 2 or later of List Products, using its full name.
2. Search an inactive product by SKU.
3. Search size `54`, then `56`, for AL HADEEL (or the real thobe that uses those sizes).
4. Confirm `154` does not appear unless that product’s size, SKU, or barcode is actually `154`.
5. Add two products, choose size and color, open browser print, and print one label.
