# QPOS 80mm thermal receipt fix

## Fixed

- Classic 80mm and Classic 80mm Mobile receipts print in high-contrast black on white. Grey labels, light fills, and low-opacity text that faded on thermal printers are removed.
- Item name, SKU, quantity, and amount no longer overlap. Empty SKU/description rows are hidden so ghost text does not appear under the product line.
- When payment lines are shown, the extra summary “Paid” row under Total is omitted so the same amount is not printed twice.
- Browser print uses 80mm roll size with zero page margin, no double padding, and visible overflow so long receipts are not clipped onto a second blank page incorrectly.
- Black & White invoice appearance and designer accent colours no longer leave grey ink on `.t80` thermal markup. Theme (light/dark) colours do not affect the printed receipt.
- Payments box title uses the Payments label instead of reusing the Paid label.

## Unchanged

- Invoice Layout Management settings, selected design, letterhead, headings, totals, discounts, and payment data sources are unchanged.
- Financial calculations, stock, invoice numbering, and customer balances are not modified.
- A4 / A5 invoice templates are not redesigned. No database migration is required.
- `.env` is not included and must not be overwritten on the live server.
