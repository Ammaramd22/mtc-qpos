# Changelog

## Light Theme table font fix

Table text that was painted in near-white now uses dark charcoal in Light Theme. Dark Theme keeps the previous dark surfaces and light text.

- Print Labels product names, variation text, size and colour text, search label, hints, empty state, suggestion menu, and the guide popover now default to dark text on light surfaces. The old light colours apply only when `html[data-vspos-theme="dark"]`.
- Contact ledger cells, headings, and party text follow the same split. The ledger rules include the `#ledger_table` id so they win over the previous id-level colours.
- Product view tables and price sheets use dark text and white cells in Light Theme. Print and PDF output stays black on white paper.
- Cheque report table cells, totals, and the row action menu use dark text on white in Light Theme, and the previous light-on-dark colours in Dark Theme.
- POS cart line names and read-only rates use the theme text colour. Product list names in the touch layout use charcoal in Light Theme.
- Shared Light Theme stylesheet sets table header, body, hover, empty-state, and pagination colours, without recolouring buttons, badges, links, or status text.
- Stylesheet cache key is `qpos-light.css?v=…-lt2` in the admin and restaurant layouts.

No sales, purchase, stock, payment, or database behaviour was changed.
