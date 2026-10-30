# Test report — invoice live preview and black & white printing

Date: 30 October 2026  
Scope: invoice layout preview and print appearance only.

## What was wrong before

The edit page at `/invoice-layouts/{id}/edit` saved layout labels and the design, but it did not show the invoice while those fields were being changed. Staff had to save and then print a real sale to see the result.

There was also no per-layout choice between colour and black & white. Classic A4 and Classic 80mm paint headings and total bars with `#1a3a4a` and white text. Elegant, detailed, and columnize-taxes use a hardcoded blue (`#357ca5`) with white text. A generic grayscale filter on the whole page would leave white text on a mid-gray bar and would leave pale gray line text (`#64748b`, `#6b7280`) hard to read on a thermal printer.

Classic A4, Classic 80mm, and Classic 80mm Mobile also ignored **Show letter head** and **Show due date**, even though the form already stored those options and the older Classic template already printed them.

## What was implemented

The preview posts the current form (without `_method`, so Laravel does not treat it as a layout update) to `POST /invoice-layouts/live-preview`. The server builds a sample receipt and renders `resources/views/sale_pos/receipts/{design}.blade.php` — the same views used for browser printing. Sample invoice number is `SAMPLE-0001`. Product names start with `(Sample)`. Nothing is written to `transactions`.

The preview sits in a panel on the edit page (sticky on the right from 1100px wide, stacked above the form on smaller screens). The edit page includes the sample invoice in the first response, then refreshes it from the form. Zoom uses CSS `zoom` on the wrapper around the preview frame. A CSS transform is not applied to the frame. Printed invoices are not scaled by that control. The preview panel is hidden in `@media print` so it is not an extra page if someone prints the settings screen.

**Colour** does not change receipt colours. `print_appearance` is omitted from the colour path, so templates keep their existing fallback accent.

**Black & White** sets the accent ink to `#111111` before the Blade view is rendered, then rewrites the HTML:

- Brand fills (`#357ca5`, `#2d2360`, `#1a3a4a` when used as a background) become `#e6e6e6` or `#dcdcdc`.
- White text on those bars becomes `#111111`.
- Pale gray text becomes `#333333`.
- Very light borders (`#e5e7eb`, `#d1d5db`) become darker gray.
- Images, including logo and letterhead, get `filter: grayscale(1) contrast(1.25)` in the browser. The files in `uploads/` are not modified.

The choice is saved in `common_settings.print_appearance` on that layout row. Missing value means colour. **Reset appearance to Colour** only selects the Colour radio; it is stored when the layout is updated.

Browser print, packing slip, delivery note, and the PDF attachment paths go through `InvoicePrintAppearance::render()`, so a saved black & white layout is applied there too. Amounts still come from `getReceiptDetails()`.

## Database

No new column, no migration, and no SQL script. `invoice_layouts.common_settings` already exists (JSON, cast to an array). Existing rows without `print_appearance` keep colour.

## Files changed

See `INVOICE_PREVIEW_BLACKWHITE_CHANGELOG.md`.

## Tests performed

Rendered the real receipt templates through `InvoiceLayoutPreview` on this machine (PHP, Laravel bootstrap, no sale inserted). Then opened the HTML in a browser.

| Check | Result |
| --- | --- |
| Classic, Classic A4, A5, Classic 80mm, Classic 80mm Mobile, Elegant, Detailed, Columnize taxes, Slim, Slim 2, Quotation 80mm | Rendered in colour and in black & white. Sample number `SAMPLE-0001` present. |
| Colour Classic A4 and Classic 80mm | Still use `#1a3a4a` and white text on the item and payment bars. Confirmed in the browser. |
| Black & white Classic A4 and Classic 80mm | No `#1a3a4a` or `#357ca5`. Bars are light gray, totals and body text are black. Confirmed in the browser. |
| Elegant black & white | Inline `#357ca5` / `color: white` rewritten to `#dcdcdc` and `#111111`. |
| Unpaid sample | Heading suffix “Unpaid”, due date shown on Classic, Classic A4, Classic 80mm, Classic 80mm Mobile, Slim, Slim 2, Elegant, Detailed, Columnize taxes. |
| Paid sample (Classic A4, Classic 80mm) | Heading “Invoice Paid”. Due date omitted. |
| Quotation sample (Classic A4, Classic 80mm) | Heading “Quotation”. Due date omitted. |
| Footer, tax, subtotal, paid, and due lines | Present on the Classic A4 sample with the labels from the form. |
| English-Arabic on this PC | PHP `intl` / `NumberFormatter` is not installed. Preview returns a short explanation instead of a PHP error. The edit form already disables that design when intl is missing. Not a failure of the other designs. |
| PHP syntax | `php -l` passed on the changed PHP files. |
| Physical printer | Not available. No paper receipt was produced. |
| Logged-in click-through of `/invoice-layouts/{id}/edit` on the live site | Not done. That page writes to the live database when saved. |
| Blank preview on the live edit page (30 Oct 2026 screenshot) | The controls were visible and the stage under them was black. The sample was only inserted by a script that ran in the page body and called jQuery immediately. On a layout that loads jQuery in the footer, that script stops before the request, and the empty frame sits on the dark stage. |
| Preview script with jQuery loaded after it | Confirmed in the browser. The script waited, posted the form, and showed SAMPLE-0001 in the frame. Zoom was on the wrapper (`zoom` about 2.19 in that wide window). The frame’s own transform was `none`. |

## Known limitations

- A5 landscape is a fixed 148mm sheet with overflow hidden. Letterhead and due date were not added there, because extra lines can clip the total. The preview matches that template. POS checkout still uses A5 for non-thermal POS bills; that rule was not changed. Black & white still applies to A5.
- Quotations printed from the POS still use the quotation 80mm template. The layout’s colour or black & white setting still applies to that HTML.
- A location whose receipt printer type is a raw printer (not “browser”) still receives the existing text receipt. That path does not use these HTML colours.
- Email PDFs get the same colour replacements. The PDF library may ignore the CSS grayscale filter, so a logo inside an emailed PDF can stay in colour. Browser printing applies the filter. Source images are never overwritten.
- If the business already has a logo file, printing uses that logo first, then the layout logo. The preview follows the same rule.
- Layouts that already have **Show letter head** on, and a letterhead file saved, will now print that image on Classic A4 and both Classic 80mm designs. Layouts with the box ticked but no file are unchanged.
- Layouts that already have **Show due date** on will now print the due date on those three designs when the invoice is not paid.
