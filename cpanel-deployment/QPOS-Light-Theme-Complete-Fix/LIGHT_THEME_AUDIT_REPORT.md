# QPOS Light Theme audit

Light Theme is `html[data-vspos-theme="light"]`. Dark Theme remains the default. The switch lives in `public/js/pos-theme.js` and is restored from `localStorage` before the page paints. Shared admin surfaces were already remapped in `public/css/qpos-light.css`. The unreadable text in the Print Labels screenshot came from later page styles that still painted near-white text after the shared stylesheet had turned the table white.

## How a page becomes unreadable

1. Dark-theme page CSS sets a near-white colour such as `#f8fafc` on a product name, heading, or hint.
2. Light Theme then paints the parent cell or card white.
3. The child’s own colour is more specific than inheritance, so the text stays near-white on white.

The same pattern showed up in modals and settings panels that use `!important` or an ID selector. Those needed a matching Light Theme rule with higher specificity. Rules are limited to `@media screen`, so receipt, PDF, and browser-print sticker colours are not rewritten.

## Confirmed unreadable or dark-on-light cases

| Area | What stayed on the dark palette |
|---|---|
| Print Labels (`labels/show.blade.php`) | Product name `#f8fafc`, variation and hints `#94a3b8`, suggestion menu, guide popover, table header and body fills. This is the screenshot. |
| Browser label setup (`labels/browser_print.blade.php`) | Toolbar, panels, and inputs. Sticker ink stays black on white paper. |
| Product view modal | Meta text, section titles, expense cards, and custom price tables on `#0a0a0a`. |
| Business settings | Inline label and help colours (`#f5f5f5`, `#e5e5e5`, `#a3a3a3`) and the cost-code repeat panel. |
| User profile | Profile card, meta rows, and field values in near-white. |
| Contacts and ledger | Hero, tabs, ledger document, and party name on charcoal. |
| Cheque list, modals, and report | Hero, stat values, filters, and modal forms. |
| Stock report cards and expense chart | Card values and Highcharts label fill. |
| Purchase bulk search and quantity modal | Dark dialog, white input text. |
| POS payment, product search, communication, and loyalty dialogs | Dark shells and near-white labels. Confirm buttons keep their existing fills. |
| AI assistant panel | Charcoal panel and bot bubble. |
| Shared | `legend { color: #fff }` in `app.css`, and DataTables info text forced to `#cbd5e1` outside Dark Theme. Field placeholders were `#9ca3af` (too faint on white). |

## Already correct, left as they are

- Dashboard (`.db-page`) already swaps its own variables in Light Theme.
- Report hub already swaps card variables. The gold icon accent is darkened to `#b45309` so it stays visible on a white card.
- Sidebar, top bar, mobile bottom nav, DataTables, Select2, SweetAlert, and the main POS touch shell already had Light Theme blocks.
- Sales receipt and invoice print templates were not edited. Print media on the admin shell still forces black text on white paper.

## Colour system used for the new Light Theme rules

| Role | Colour |
|---|---|
| Primary text | `#111827` |
| Secondary text | `#374151` |
| Muted text and placeholders | `#4b5563` / `#6b7280` |
| Links | `#1d4ed8` |
| Page and card | `#f8fafc` / `#ffffff` |
| Borders | `#e5e7eb` |
| Accent text that used to be gold | `#92400e` or `#b45309` |
| Errors | `#b91c1c` |

Dark Theme rules were not replaced. New rules apply only when `data-vspos-theme="light"`.
