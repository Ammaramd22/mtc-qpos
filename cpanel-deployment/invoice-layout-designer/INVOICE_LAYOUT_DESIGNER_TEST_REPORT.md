# Test report — invoice layout designer

Date: 30 October 2026

## What was wrong

On the live edit page the preview controls worked and a sample header appeared (Marjan Thobe Center, invoice number, date). The frame stopped there. Items, totals, and the footer were not in view, and the white card was only as tall as that header. The stage sat on the dark page, so the rest of the invoice looked missing rather than scrolled.

The frame height was being taken from the short visible area. A tall invoice was clipped instead of measured from its content.

## What was implemented

The preview frame is sized from the invoice content. The stage is at least most of the screen, with a visible scrollbar when the invoice is taller than the stage. Zoom uses CSS `zoom` on the wrapper. Fit, zoom in, zoom out, top, bottom, refresh, expand preview, and save are on the preview toolbar.

**Invoice appearance** is saved in `common_settings.designer` for that layout only. Empty sizes and colours do not override the template. Custom colours apply only when the checkbox is on. Black & white still replaces those colours with the existing monochrome treatment. The CSS is built from an allow-list (font names, integers, `#rrggbb`), then injected by the same render path used for browser printing.

Clicking a section in the preview scrolls to the matching form block: business, heading, customer, item table, totals, or footer.

Business name, address lines, phone, email, tax numbers, logo, and letterhead still come from the location and the existing show/hide fields. The preview uses those real details plus sample lines marked as sample. Nothing is written to `transactions`.

## Content controls

Already on the form, and still what the preview and the printer read:

- Headings, paid and unpaid suffixes, quotation, proforma, and sales-order headings.
- Number, date, and due-date labels, date format, and due-date visibility.
- Customer label and customer block visibility.
- Product, quantity, price, and subtotal headings, plus the existing show/hide boxes for SKU, brand, description, tax, payments, and footer text.

Item columns stay in the order of the selected design. Reordering columns in the preview alone would not match the printed template, so that control was not added.

Dragging sections was not added for the same reason. Each design prints sections in a fixed order. Clicking a section opens its fields instead.

## Appearance controls

- Font family from a print-safe list.
- Sizes for business name, heading, item table, totals, and footer.
- Alignment for business block, heading, and footer.
- Logo height, section gap, cell padding, border width, page margin.
- Primary, body, table header, header text, border, total, and footer colours, plus three presets.
- Colour / black & white, unchanged in meaning.

## Tests performed

| Check | Result |
| --- | --- |
| Invalid colour `red`, font `Comic Sans`, size 99, negative margin | Rejected or clamped. Primary stored blank, font blank, size 28, margin 0. |
| Custom colour CSS | Contains the chosen hex and the type size. |
| Black & white with the same settings | Type size remains. Custom hex is left out. |
| No appearance values | No extra CSS, so an untouched layout is not restyled. |
| PHP syntax of the changed PHP files | `php -l` passed. |
| Preview script syntax | `node --check` passed. |
| Tall sample in the browser, jQuery loaded after the script | Frame height followed the content (705px for that sample, not the old header-only clip). Items 1–8, subtotal, and footer text were in the frame. Clicking the footer marked the footer settings block. |
| Physical printer | Not available. No paper receipt was produced. |
| Logged-in edit page on the live site | Not opened. Saving there writes the live layout. |
| Local MySQL | Not running in this session, so a full Laravel page render against `invoice_layouts` was not repeated. |

## Database

No new column, no migration, and no SQL file. `common_settings` already exists.

## Regression

Invoice totals, discounts, taxes, payments, and customer balances are not calculated in these files. POS, KOT, and BOT paths were not edited except the existing browser-receipt render, which now also receives the appearance block. When appearance fields are blank, that block is empty.

## Limitations

- Column order and section order follow the selected design template.
- A5 stays a fixed sheet. Extra letterhead or due-date lines were not added there.
- Raw (non-browser) receipt printers still receive the existing text receipt.
- Email PDF libraries may ignore the CSS grayscale filter. Uploaded images are not modified.
- The business logo still takes priority over the layout logo, matching normal printing.
- Multi-page A4 output in the browser is one continuous document you scroll, not a set of numbered page thumbnails.
- Screen zoom does not change the millimetre size sent to the printer.
