# Light Theme table font fix

## What was wrong

On Print Labels, the product name “ACQUAD GIO 6ml nrml” sits in `<span class="labels-product-name">`. The Print Labels page stylesheet, which is printed in the page body after the theme file, set that span to `#f8fafc`. The row behind it is white in Light Theme. A colour on the table cell does not replace a colour the span sets itself, so the name stayed near-white.

The same pattern appears anywhere a page style uses an id or `!important` to force a dark-theme colour. Those rules are stronger than a class-only rule in `qpos-light.css`, so a later override in the theme file cannot win. The live screenshot matches this: the header text is readable, and the product name is not.

## What changed

Colours are now chosen in the same file that used to hardcode them.

- Light Theme is the default in those blocks: text `#111827` or `#374151`, muted text `#4b5563`, links `#1d4ed8`, surfaces `#ffffff` and `#f8fafc`, borders `#e5e7eb`.
- Dark Theme colours are restored only under `html[data-vspos-theme="dark"]`, with the same `!important` and the same ids the old rules used.

Pages updated at the source:

- `resources/views/labels/show.blade.php` — product table, product name, variation, attributes, search, empty state, suggestions, guide, remove button.
- `resources/views/contact/partials/ledger_screen_styles.blade.php` — `#ledger_table` header and body, headings, party name.
- `resources/views/product/view-modal.blade.php` — product sheet tables on screen. `@media print` is unchanged.
- `resources/views/cheque/index.blade.php` — report table cells, hover, totals, and the card action menu.
- `resources/views/cheque/report.blade.php` — report footer cells.
- `resources/views/sale_pos/partials/pos_form.blade.php` — cart line name and read-only rate.
- `public/css/pos-touch.css` — product list name and category in Light Theme only.

`public/css/qpos-light.css` also sets Light Theme colour on ordinary table cells and on text inside those cells. Buttons, badges, links, status classes, icons, and dropdown menus are left on their own colours so paid/due labels and action buttons keep their existing look. That rule is inside `@media screen`, so receipts and stickers are not recoloured.

The admin and restaurant layouts load `qpos-light.css?v={asset_version}-lt2` so browsers do not keep the previous file.

## What was left alone

- Sales, purchase, stock, tax, and payment calculations.
- Database structure and `.env`.
- Receipt, sticker, and other print ink. Label stickers stay black on white.
- Dark Theme appearance. Switching `data-vspos-theme` back to `dark` restores the previous dark rows and light text.
- Modules whose tables already inherit the shared table colour and do not set their own near-white text.

## Files in this update

- `public/css/qpos-light.css`
- `public/css/pos-touch.css`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/restaurant.blade.php`
- `resources/views/labels/show.blade.php`
- `resources/views/product/view-modal.blade.php`
- `resources/views/contact/partials/ledger_screen_styles.blade.php`
- `resources/views/cheque/index.blade.php`
- `resources/views/cheque/report.blade.php`
- `resources/views/sale_pos/partials/pos_form.blade.php`
