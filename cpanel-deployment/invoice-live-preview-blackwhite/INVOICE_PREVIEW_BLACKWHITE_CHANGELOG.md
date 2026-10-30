# Changelog — invoice live preview and black & white printing

Minimal patch for the existing QPOS invoice layout editor and browser invoice printing. No Laravel upgrade. No change to sale totals, stock, or payments.

## Added

- Live invoice preview on **Edit invoice layout** and **Add invoice layout**.
- The preview renders the same receipt Blade templates used for printing, with sample lines labelled as sample. It does not insert a transaction.
- Preview controls: design, sample document (unpaid, paid, quotation, sales order, proforma), colour / black & white, refresh, fit, zoom in, zoom out.
- **Invoice print appearance** on each layout: Colour (default) or Black & White.
- The choice is stored in `invoice_layouts.common_settings` as `print_appearance`. No new column.
- Black & white treatment on the printed HTML: brand fills become light gray, white ink on those fills becomes near-black, light gray text is darkened, and logo/letterhead images get a CSS grayscale filter. Uploaded image files are not rewritten.
- Classic A4, Classic 80mm, and Classic 80mm Mobile now show a letterhead image when **Show letter head** is on and a file is saved, and they show the due date when that layout option is on and the invoice is not paid.

## Fixed

- The live edit page could show the preview controls with a black empty stage. The sample is now part of the first page response, and the preview script waits until jQuery is available (including when jQuery is loaded at the bottom of the layout). The frame has a white background. Screen zoom scales the wrapper with CSS `zoom` rather than transforming the frame. A failed refresh shows a message in the stage. `invoice-layouts/live-preview` accepts POST and PUT.

## Unchanged

- Colour mode still uses the existing accent colours (including `#1a3a4a` on Classic A4 and Classic 80mm).
- Sale calculations, stored invoice rows, receipt printer text mode, KOT/BOT, and other modules.
- POS behaviour that prints non-thermal POS bills as A5 landscape and quotations as the 80mm quotation slip. Those templates still receive the layout’s colour or black & white setting.

## Files

New:

- `app/Utils/InvoicePrintAppearance.php`
- `app/Utils/InvoiceLayoutPreview.php`
- `app/Utils/PreviewReceiptDetails.php`
- `resources/views/invoice_layout/partials/live_preview.blade.php`
- `resources/views/invoice_layout/partials/print_appearance_field.blade.php`
- `resources/views/invoice_layout/partials/preview_document.blade.php`
- `public/css/invoice-layout-preview.css`
- `public/js/invoice-layout-preview.js`

Updated:

- `app/Http/Controllers/InvoiceLayoutController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/TransactionUtil.php`
- `routes/web.php`
- `resources/views/invoice_layout/edit.blade.php`
- `resources/views/invoice_layout/create.blade.php`
- `resources/views/sale_pos/receipts/classic_a4.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`
- `lang/en/invoice.php`
