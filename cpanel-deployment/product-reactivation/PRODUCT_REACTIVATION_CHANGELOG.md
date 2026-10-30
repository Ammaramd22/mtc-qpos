# Product reactivation white-screen fix

Minimal patch. No route change, no database migration, and no new screen.

## Root cause

Clicking **Reactivate** opened `/products/activate/{id}` as a normal page and the controller returned an empty response.

Two existing pieces caused that:

1. `public/js/common.js` moves an Actions menu out of the product table and onto `document.body` so the DataTables scroll area does not clip it. The product list listened for `a.activate-product` only inside `table#product_table tbody`. A click on the moved menu never reached that listener, so the browser followed the link.
2. `ProductController::activate()` updated `is_inactive` and returned JSON only when the request was AJAX. A normal browser request fell off the end of the method. Laravel turns that empty return into HTTP 200 with no body, which is the blank white page. The database update did not run on that request, because it lived inside the AJAX check.

The route `GET products/activate/{id}` already pointed at `ProductController@activate`. The product id was passed correctly. Products are not soft-deleted. Inactive means `products.is_inactive = 1`. Deactivation already sets that flag to `1` and leaves the same row in place.

## Files

| File | Change |
|---|---|
| `app/Http/Controllers/ProductController.php` | `activate()` now updates the existing row and always returns a response |
| `resources/views/product/index.blade.php` | Reactivate click is handled on `document`, including a menu that was moved to `body` |
| `lang/en/lang_v1.php` | Messages for success, already active, and product not found |

`public/js/common.js` was not changed. Other product actions were not changed. The activate route is still `GET`, matching the existing Actions link. No CSRF token is required for that GET.

## Controller behavior

`activate($id)` still requires `product.update`. A user without that permission receives the existing 403 response.

For an allowed user, the method loads the product in the current business and then:

| Situation | Result |
|---|---|
| Product id is missing, or it belongs to another business | Error: Product not found. No row is created. |
| `is_inactive` is already `0` | Error: This product is already active. The row is not rewritten. |
| `is_inactive` is `1` and the update changes the row | Success: Product reactivated successfully. Only `is_inactive` is set to `0`. |
| The update changes no row, or the query throws | Error: Something went wrong, please try again later. The exception text is written to the Laravel log, not shown to the user. |

An AJAX request receives JSON (`success` `1` or `0`, plus `msg`). The product list uses that JSON for the existing toastr notification and reloads the table.

A normal browser request redirects to `/products` with the same status in the session. The layout already turns that session status into a toastr notification (`data-status="1"` for success, `data-status="0"` for an error).

## What reactivation does not change

The same `products.id` is updated. Name, SKU, barcode, category, brand, prices, stock, variations, locations, purchase lines, and sell lines are not written. A new product row is not inserted.

## Listing

The product list already hides the Inactive badge and the Reactivate item when `is_inactive` is `0`, and the POS product query already requires `is_inactive = 0` and `not_for_selling = 0`. Those rules were left as they were.
