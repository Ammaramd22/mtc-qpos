# Product reactivation test report

Date: 30 October 2026

This report covers the white screen at `/products/activate/{id}` from Products → List Products. The live site `mtc.qpos.avenque.io` was not changed.

## Root cause

The blank page is an empty HTTP 200, not a hidden database error.

`public/js/common.js` detaches the Actions dropdown and appends it to `document.body` so DataTables scrolling does not clip the menu. The Reactivate listener was bound with `$('table#product_table tbody').on('click', 'a.activate-product', ...)`. After the menu moves, that click is no longer inside the table body, so `preventDefault()` never runs and the browser navigates to `/products/activate/21`.

`ProductController::activate()` performed the `is_inactive` update only inside `if (request()->ajax())` and returned nothing otherwise. A normal navigation is not AJAX, so the method returned null. Laravel responds to that with HTTP 200 and an empty body: the white screen. The product stayed inactive, because the update never ran.

The route was already `GET products/activate/{id}` → `ProductController@activate`. Product status is the `products.is_inactive` flag. The `Product` model does not use soft deletes.

## Files modified

- `app/Http/Controllers/ProductController.php`
- `resources/views/product/index.blade.php`
- `lang/en/lang_v1.php`

No route file, migration, or `.env` file was changed.

## Backend correction

`activate()` now:

- requires the existing `product.update` permission
- loads the product for the signed-in business
- sets `is_inactive` from `1` to `0` on that same row
- returns JSON for AJAX
- redirects to `/products` with a session status for a normal browser request
- returns an error notification when the product is missing, already active, or the update does not change a row
- logs unexpected exceptions and shows the existing generic message

The product list now listens on `document` for `a.activate-product`, calls the same URL with AJAX, shows toastr, and reloads the table. If that request fails, it shows the existing “something went wrong” toast and does not leave the list.

## Database behavior

The only write is:

```sql
UPDATE products
SET is_inactive = 0
WHERE business_id = :business
  AND id = :id
  AND is_inactive = 1
```

No table was created, altered, or dropped. Stock, prices, variations, locations, purchase lines, and sell lines are not updated.

## Redirect and notification

| Request | Response |
|---|---|
| Actions → Reactivate (AJAX) | Stay on `/products`. Green toast: “Product reactivated successfully”. The table reloads. |
| Address bar `/products/activate/{id}` | HTTP 302 to `/products`. The existing `#status_span` handler shows the same toast. |
| Missing id, other business, or already active | HTTP 302 to `/products` (or JSON for AJAX) with an error toast. Success is not reported. |
| User without `product.update` | Existing HTTP 403, “Unauthorized action.” |

## How the tests were run

Local MySQL was not running (`DB_HOST` `127.0.0.1` port `3306` refused the connection), and no `mysqld` binary was installed on this machine. The live Marjan database was not used.

The controller, product-list query, permission check, and redirect were executed with PHP 8.5 against a throwaway SQLite file created for the test and deleted afterward. Cache and session drivers for that process were `array`, so the application file cache was not overwritten. The SQLite schema contained only the tables this path reads and writes. That is the same SQL the MySQL `products.is_inactive` update uses. It is not a substitute for clicking the live shop.

A separate browser page reproduced the dropdown move from `common.js`. The menu’s parent was `body`, and the Reactivate link was not inside the table body. The old table-body listener did not run. The new document listener prevented the navigation. The address stayed on the test page and the page text became `prevented:/products/activate/21 old-handler:no`.

## Reactivation results

All of the following passed on the throwaway database.

| Case | Result |
|---|---|
| Inactive product id 21, name Cap, with stock, a purchase line, a sell line, and a group price | `is_inactive` became `0`. Redirect 302 to `/products`. Flash and JSON: success, “Product reactivated successfully”. No other product column changed. Related rows unchanged. Product count unchanged. |
| A second inactive product | Same success result. |
| Product with stock quantity 12 | Stock quantity unchanged after reactivation. |
| Product with stock quantity 0 | Reactivated. Stock stayed 0. |
| Variable product with two variations and group price | Both variations and the group price unchanged. |
| Product with purchase and sale rows | Those rows unchanged. |
| Reactivating the same product again | Error: “This product is already active”. `is_inactive` stayed `0`. |
| Id `999999` | Error: “Product not found”. Product count unchanged. |
| Inactive product in another business | Error: “Product not found”. That row stayed inactive. |
| User with `product.view` and without `product.update` | HTTP 403, “Unauthorized action.” |
| Database error (products table removed before the call) | Redirect 302 to `/products`. Error: “Something went wrong, please try again later”. The method did not return null. |
| POS visibility predicate `is_inactive = 0` and `not_for_selling = 0` | True after reactivation. `not_for_selling` was not modified. |

## Listing results

The real `ProductController::index()` AJAX query was executed for product Cap (`CAP-21`).

While inactive, the row HTML was `Cap` plus the Inactive badge, and the action menu contained `activate-product`, View, Edit, Delete, Duplicate, and stock history.

After `is_inactive` was `0`, the badge and `activate-product` were gone. View, Edit, Delete, Duplicate, and stock history were still in the menu.

The product-list Blade source contains `$(document).on('click', 'a.activate-product'` and no longer binds that click only on the table body.

## Regression results

| Area | Result |
|---|---|
| Deactivate Selected (`massDeactivate`) | Executed. Response success, “Products deactivated successfully”. `is_inactive` became `1`. The method was not edited. |
| View, Edit, Delete, Duplicate, stock history links | Present in the generated action HTML before and after reactivation. |
| Category, brand, location, variation, stock, purchase, sale, and group-price rows | Unchanged by the reactivation update in the cases above. |
| Add Product, saving an edit, deleting a product, labels, search, filters, and the POS screen | Not re-run. Those methods were not modified, and the local MySQL server was unavailable. |
| Live browser session on `mtc.qpos.avenque.io` | Not used. The live shop was not updated. |

## Remaining limitations

- The full signed-in Products page was not clicked in a browser against MySQL. The click proof is the detached-menu page plus the controller and listing tests above.
- A user without permission still gets the application’s normal 403 page on a direct visit. That page is not blank. An AJAX 403 shows the generic error toast and stays on the list.
- English strings were added. Other languages fall back to English for these three new keys.
- Reactivation still uses the existing GET route. It does not create a new product, and it does not change stock or prices.
