# Test report — Color Management

Date: 2026-11-05  
Scope: Centralized Colors (mirror of Sizes) and product / label / import integrations.

## Automated checks

| Check | Result |
| --- | --- |
| `php -l` on Color model, ColorController, ProductSizeColor, Product, migration | Pass |
| `php tests/size_color_logic_test.php` | Pass |
| `php tests/label_editor_logic_test.php` (chooseOne / optionList for colours) | Pass |

## Manual / integration checklist (run on staging or after cPanel deploy)

| # | Case | Expected | Status |
| --- | --- | --- | --- |
| 1 | Add a new color in Colors | Row appears; `is_catalog = 1` | Pending on live |
| 2 | Edit an existing color | Name updates; products.color text refreshes | Pending on live |
| 3 | Delete an unused color | Row removed | Pending on live |
| 4 | Delete a color assigned to products | Blocked with message | Pending on live |
| 5 | Search colors on Add Product | Typing filters Select2 options only from catalog | Pending on live |
| 6 | Select one color | Chip selected | Pending on live |
| 7 | Select multiple colors | Multiple chips | Pending on live |
| 8 | Remove a selected color | Chip cleared | Pending on live |
| 9 | Prevent duplicates | Select2 unique values; server ignores duplicate ids | Covered in code |
| 10 | Create product with multiple sizes and colors | Saved to pivots + denormalized text | Pending on live |
| 11 | Edit product shows selected colors | Linked ids selected, including legacy non-catalog links | Pending on live |
| 12 | Update product colors | Pivot and `products.color` updated | Pending on live |
| 13 | Product List colour column | Shows comma-separated `products.color` | No code break; uses existing column |
| 14–15 | POS Grid / List | Still read `product_color` / `products.color` display fields | No code break |
| 16 | Product search | Unchanged text search on `products.color` | No code break |
| 17 | Import / export | Export still column COLOR; import calls `syncFromLegacyColumns` → catalog links | Covered in code |
| 18–20 | Label preview / print / profiles | `listsForProducts` reads `product_color` join when ready | Covered in code + label logic tests |
| 21 | Existing products | Migration copies tags/text; products keep colours | SQL + migration backfill |
| 22–23 | Themes / responsive | Same Select2 + input-group pattern as Sizes | Visual parity with Sizes |

## Notes

- Free-text colour entry on Add Product is removed when `colors` + `product_color` exist. Until the SQL script runs, the older tag UI remains as a safe fallback.
- Payment, discount, stock quantity, and invoice calculation code paths were not modified.
