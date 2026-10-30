# Changelog — invoice layout designer

Minimal patch for the invoice layout editor and browser invoice printing. No Laravel upgrade. No change to sale totals, stock, or payments. No new database table.

## Fixed

- Colour, Black & white, the design and sample lists, and the zoom row (Fit, Refresh, Top, Bottom, Expand preview) now respond without waiting for jQuery. The chosen colour button turns blue, and the zoom percentage updates as soon as − or + is clicked. Save layout still submits the existing layout form.
- The preview could sit on the bottom of the sample (totals and thank-you line only), so heading and design edits looked as if they did nothing. The stage is now a fixed tall panel and the whole invoice is scaled to fit inside it, starting at the top. The preview design follows the form design. Typing, checkboxes, and the design list refresh the sample. The status reads **Updated** when that refresh succeeds.
- The live preview showed only the top of the sample invoice. The frame height was following the short visible area, so items, totals, and the footer were cut off.
- The preview stage is now tall enough to show a full 80mm sample, and the frame height follows the invoice content. Zoom scales the wrapper around the frame. Screen zoom does not change the printed size.
- The preview script still waits until jQuery is available.

## Added

- **Invoice appearance** on each layout, stored in the existing `invoice_layouts.common_settings` JSON as `designer`. No new column.
- Font (Arial, Tahoma, Verdana, Times New Roman, Courier New), type sizes, alignment, logo height, section gap, cell padding, border width, and page margin.
- Colour presets (Classic teal, Slate, Forest) and a colour picker. Colours are applied only when **Use these colours on the invoice** is checked. Blank sizes keep the template’s own sizes, so existing layouts stay as they are until someone sets these fields and clicks **Save layout**.
- The same appearance is applied when the invoice is rendered for browser printing, including colour and black & white. Black & white still forces readable monochrome and ignores the custom colours. Logo and letterhead files are not rewritten.
- Clicking the business heading, invoice title, items, totals, or footer in the preview scrolls to that part of the form.
- **Expand preview** hides the settings column. **Top** and **Bottom** move through a long preview. **Save layout** uses the existing update action.
- **Restore appearance defaults** clears the new appearance fields in the form. It is stored only when the layout is saved.

## Unchanged

- Sale calculations, stored invoice rows, receipt printer text mode, KOT/BOT, and other modules.
- Item column order inside each design. Headings and show/hide options already on the form still control what prints.
- Several layouts can already be saved from Invoice settings. This patch does not add another profile table.
- POS behaviour that prints non-thermal POS bills as A5 landscape and quotations as the 80mm quotation slip. Those templates still receive the layout’s colour, black & white, and appearance settings.

## Files

New:

- `resources/views/invoice_layout/partials/designer_fields.blade.php`

Updated for this designer, including the earlier preview and black & white files:

- `app/Utils/InvoicePrintAppearance.php`
- `app/Utils/InvoiceLayoutPreview.php`
- `app/Utils/PreviewReceiptDetails.php`
- `app/Http/Controllers/InvoiceLayoutController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/TransactionUtil.php`
- `routes/web.php`
- `resources/views/invoice_layout/edit.blade.php`
- `resources/views/invoice_layout/create.blade.php`
- `resources/views/invoice_layout/partials/live_preview.blade.php`
- `resources/views/invoice_layout/partials/print_appearance_field.blade.php`
- `resources/views/invoice_layout/partials/preview_document.blade.php`
- `resources/views/sale_pos/receipts/classic_a4.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`
- `lang/en/invoice.php`
- `public/css/invoice-layout-preview.css`
- `public/js/invoice-layout-preview.js`
