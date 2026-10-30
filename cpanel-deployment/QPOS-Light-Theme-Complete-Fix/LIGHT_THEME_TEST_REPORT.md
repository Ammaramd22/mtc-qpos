# QPOS Light Theme test report

Date: 31 October 2026.

## What was verified

A local page loaded `public/css/qpos-light.css` together with the same hardcoded Print Labels, cost-code, cheque, user-profile, stock-card, and `legend` colours that the live pages use. Computed styles were read in the browser.

Light Theme (`data-vspos-theme="light"`):

| Element | Result |
|---|---|
| Print Labels product name | `#111827` on a white cell |
| Variation / search hint | `#4b5563` |
| Search label | `#111827` |
| Table header | `#374151` on `#f8fafc` |
| Settings link | `#1d4ed8` |
| Cost-code heading and panel | `#111827` on white |
| Cheque hero and user title | `#111827` on white |
| Stock value | `#111827` on white |
| `legend` | `#111827` |

Dark Theme on the same page, without reloading the stylesheet:

| Element | Result |
|---|---|
| Product name | `#f8fafc` (original dark-skin colour) |
| Table header | `#f8fafc` on `#0f172a` |

Light rules did not leak into Dark Theme. Placeholder text in the light stylesheet is `#6b7280`, which meets ordinary contrast on white. Screen-only light rules sit outside `@media print`.

## What was not run on the live server

The live site was not modified and was not used as a test environment. These flows still need a check after the cPanel copy, in both themes:

- Sign-in, theme toggle, refresh, and moving between modules.
- Dashboard, products, add/edit product, purchases, POS sell, payments, returns, stock transfer, stock adjustment, expenses, reports, contacts, users, permissions, business settings, and invoice layout preview.
- A real browser label print: stickers must stay black on white, and the print dialog must not show the setup toolbar.
- Phone and tablet width for the sidebar, bottom nav, tables, and modals.

Business calculations, stock, payments, and permissions were not changed.
