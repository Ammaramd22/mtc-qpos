# Test report

Date: 31 October 2026. The live site was not changed and was not logged into.

## Method

A local page loaded `public/css/qpos-light.css` first, then the same colour rules the updated blades print in the page body. Computed colours were read in the browser for Light Theme and, without a reload, after setting `data-vspos-theme` to `dark`.

## Light Theme

| Item | Colour | Surface | Result |
| --- | --- | --- | --- |
| Print Labels product name | rgb(17, 24, 39) `#111827` | white cell | Pass. This is the “ACQUAD GIO 6ml nrml” span. |
| Print Labels header | rgb(55, 65, 81) `#374151` | `#f8fafc` | Pass |
| Ledger cell | `#111827` | white | Pass |
| Product view cell | `#111827` | white | Pass |
| Cheque report cell | `#111827` | white | Pass |
| Button label inside the cell | white, inherited from the button | not forced to charcoal | Pass. Status badges and buttons keep their own colours. |

## Dark Theme

| Item | Colour | Surface | Result |
| --- | --- | --- | --- |
| Print Labels product name | `#f8fafc` | `#0b1220` | Pass. Previous dark row is intact. |
| Print Labels header | `#f8fafc` | `#0f172a` | Pass |
| Variation text | `#94a3b8` | dark cell | Pass |
| Ledger cell | `#d4d4d4` | `#0f0f0f` | Pass |
| Product view cell | `#e2e8f0` | `#0a0a0a` | Pass |
| Cheque report cell | `#d4d4d4` | transparent on the dark modal | Pass |

Light Theme rules did not remain after the attribute switched to dark.

## Not exercised on the live server

Search, sorting, pagination, and modal tables on the live account need a logged-in check after you upload. The shared table rule covers ordinary DataTables cells, and the id-level pages above were checked in the fixture. Business calculations were not modified, so sale, purchase, and stock totals are unchanged.

## Desktop and mobile

The colour rules are not tied to a viewport width. The ledger file’s existing mobile layout rules were not rewritten. A phone-width check on the live Print Labels page is still worth doing after upload.
