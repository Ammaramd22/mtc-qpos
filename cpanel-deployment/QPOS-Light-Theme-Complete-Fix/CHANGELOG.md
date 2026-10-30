# Changelog

## Light Theme complete contrast fix

Light Theme text and surfaces that were still using the dark palette are readable. Dark Theme is unchanged except that two unscoped rules no longer force light-grey or white text while Light Theme is on.

### Styles

- `public/css/qpos-light.css` — screen-only Light Theme colours for Print Labels, product view, cost code, stock cards, expense charts, user profile, contacts and ledger, cheques, purchase search, the AI panel, POS payment and service dialogs, loyalty, invoice preview status, and nutmeg drying inputs. Placeholders are `#6b7280`. Shared text tokens are `#111827`, `#374151`, and `#4b5563`.
- `public/css/app.css` — `legend` is white only when the theme is not light.
- `resources/views/layouts/partials/css.blade.php` — DataTables info and length labels use `#cbd5e1` only in Dark Theme.

### Theme loading

- `resources/views/layouts/app.blade.php` and `resources/views/layouts/restaurant.blade.php` load `qpos-light.css` with a `-lt1` cache key so browsers pick up the new file without Terminal.

### Label printing

- `resources/views/labels/browser_print.blade.php` — setup chrome follows the active theme. Sticker ink, paper, and `@media print` output are unchanged.
- `resources/views/labels/show.blade.php` and `public/js/labels.js` pass the current theme into the print window.

No database, stock, price, sale, or payment logic was changed.
