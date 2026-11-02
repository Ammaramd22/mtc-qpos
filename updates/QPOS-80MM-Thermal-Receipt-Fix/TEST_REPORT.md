# TEST REPORT — QPOS 80mm thermal receipt fix

Date: 2026-11-02  
Scope: Receipt printing only (Classic 80mm / Classic 80mm Mobile + shared print CSS/JS + B&W appearance helper).

## Root cause confirmed

1. `classic_80mm` / `classic_80mm_mobile` used grey label colours (`#555`, `#6b7280`, `#333`) and light section fills. Thermal heads print those as faded or nearly invisible ink.
2. Filled coloured bars with white text became washed-out grey-on-grey after B&W rewrite.
3. SKU/description sat in a low-contrast line that looked like overlapping “ghost” text under the item name.
4. Summary **Paid** row plus **Payments** box printed the same amount twice.
5. Print iframe added extra padding and `overflow:hidden`, which squeezed or clipped roll content.
6. POS theme `@page { margin: 4mm }` ate printable width on fallback window.print paths.

## Automated / static checks

| Check | Result |
|-------|--------|
| `php -l app/Utils/InvoicePrintAppearance.php` | Pass |
| B&W decorate removes `#555` / `#6b7280` and injects thermal `.t80` black rules | Pass |
| Blade templates still use configured `$receipt_details` (layout headings, customer, lines, totals, payments, footer) | Pass |
| No payment / discount / stock calculation files changed | Pass |

## Manual scenarios (POS + Invoice Layout preview)

| # | Scenario | Expected | Status |
|---|----------|----------|--------|
| 1 | Normal cash sale | Clear black text; Payments shows CASH; no duplicate Paid under Total | Ready to verify on live printer |
| 2 | Card payment | Method + amount readable in Payments box | Ready |
| 3 | Split payment | Each method listed; totals unchanged | Ready |
| 4 | Credit sale | Due line visible in black; payments empty or partial as configured | Ready |
| 5 | Sale with discount | Discount row readable; total correct | Ready |
| 6 | Multiple products | Each item name / qty / amount separated; no overlap | Ready |
| 7 | Long product name | Wraps inside 80mm; amount stays right-aligned | Ready |
| 8 | Size + colour on variation | Shown on item name line | Ready |
| 9 | Different quantities | Qty × unit price line clear | Ready |
| 10 | Named customer + mobile | Customer section black ink | Ready |
| 11 | Walk-in customer | Walk-In text readable | Ready |
| 12 | Previous balance due (layout enabled) | Previous due / balance rows print when data present | Ready |
| 13 | Classic 80mm vs Classic 80mm Mobile layout | Correct blade selected from Invoice Layout design | Ready |
| 14 | Short receipt | Single continuous slip; no large blank bands | Ready |
| 15 | Long receipt | Continues on roll; content not clipped by overflow:hidden | Ready |
| 16 | Light theme POS | Printed receipt remains black on white | Ready |
| 17 | Dark theme POS | Printed receipt remains black on white | Ready |
| 18 | Invoice Layout live preview Classic 80mm | Matches print structure and contrast | Ready |
| 19 | A4 Classic layout reprint | Unchanged workflow (not thermal template) | Ready — smoke after deploy |

## Browser print settings recommended for cashiers

- Paper: 80mm roll (or “Receipt” / custom 80mm)
- Scale: 100% / Default (do not “Fit to page”)
- Margins: None / Minimum
- Background graphics: On (borders/separators)

## Files in this package

- `app/Utils/InvoicePrintAppearance.php`
- `config/constants.php`
- `public/js/functions.js`
- `public/css/print-preview.css`
- `public/css/pos-theme.css`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`
