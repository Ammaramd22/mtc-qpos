<?php

namespace App\Utils;

use App\Business;
use App\BusinessLocation;
use App\InvoiceLayout;
use Illuminate\Http\Request;

/**
 * Builds a sample receipt with the same blades used for printing.
 * Nothing is written to the transactions table.
 */
class InvoiceLayoutPreview
{
    /**
     * @param  array<string, string>  $allowedDesigns
     */
    public static function document(Request $request, ?InvoiceLayout $layout, array $allowedDesigns): string
    {
        $design = (string) $request->input('design', $layout->design ?? 'classic');
        if (! isset($allowedDesigns[$design])) {
            $design = isset($allowedDesigns['classic']) ? 'classic' : (string) array_key_first($allowedDesigns);
        }

        if ($design === 'english-arabic' && ! class_exists(\NumberFormatter::class)) {
            return view('invoice_layout.partials.preview_document', [
                'receipt_html' => '<p style="padding:16px;font-family:Arial,Helvetica,sans-serif;">English-Arabic uses the PHP intl extension, which is not enabled on this server. Enable intl to preview and print that design. Other designs are unaffected.</p>',
                'paper' => 'a4',
            ])->render();
        }

        $details = self::sample($request, $layout);
        $receiptHtml = InvoicePrintAppearance::render('sale_pos.receipts.'.$design, [
            'receipt_details' => $details,
        ]);

        $paper = 'a4';
        if ($design === 'slim2') {
            $paper = '58';
        } elseif (in_array($design, ['slim', 'classic_80mm', 'classic_80mm_mobile', 'quotation_80mm'], true)) {
            $paper = '80';
        } elseif ($design === 'a5_half_landscape') {
            $paper = 'a5';
        }

        return view('invoice_layout.partials.preview_document', [
            'receipt_html' => $receiptHtml,
            'paper' => $paper,
        ])->render();
    }

    /**
     * First paint for the edit page, using the saved layout rather than a form post.
     */
    public static function documentForSavedLayout(Request $request, InvoiceLayout $layout, array $allowedDesigns): string
    {
        $saved = $request->duplicate();
        if ($request->hasSession()) {
            $saved->setLaravelSession($request->session());
        }

        $attributes = $layout->attributesToArray();
        if (isset($attributes['common_settings']) && is_string($attributes['common_settings'])) {
            $decoded = json_decode($attributes['common_settings'], true);
            $attributes['common_settings'] = is_array($decoded) ? $decoded : [];
        }
        unset($attributes['module_info'], $attributes['table_tax_headings']);
        $saved->merge($attributes);
        $saved->merge(['preview_document' => 'invoice_unpaid']);

        return self::document($saved, $layout, $allowedDesigns);
    }

    public static function sample(Request $request, ?InvoiceLayout $layout): PreviewReceiptDetails
    {
        $businessId = $request->session()->get('user.business_id');
        $business = $businessId ? Business::with('currency')->find($businessId) : null;
        $location = null;
        if ($businessId) {
            $location = BusinessLocation::where('business_id', $businessId)->orderBy('id')->first();
        }

        $precision = 2;
        if ($business && isset($business->currency_precision)) {
            $precision = (int) $business->currency_precision;
        }
        if ($precision < 0 || $precision > 4) {
            $precision = 2;
        }
        $symbol = '';
        if ($business && $business->currency) {
            $symbol = (string) ($business->currency->symbol ?? '');
        }
        $money = function ($amount) use ($symbol, $precision) {
            $formatted = number_format((float) $amount, $precision, '.', ',');

            return trim($symbol) === '' ? $formatted : ($symbol.' '.$formatted);
        };

        $format = trim((string) $request->input('date_time_format', ''));
        if ($format === '') {
            $format = 'd/m/Y h:i A';
        }
        try {
            $invoiceDate = \Carbon\Carbon::now()->format($format);
            $dueDate = \Carbon\Carbon::now()->addDays(7)->format($format);
        } catch (\Throwable $e) {
            $invoiceDate = \Carbon\Carbon::now()->format('d/m/Y h:i A');
            $dueDate = \Carbon\Carbon::now()->addDays(7)->format('d/m/Y h:i A');
        }

        $document = (string) $request->input('preview_document', 'invoice_unpaid');
        $heading = trim((string) $request->input('invoice_heading', 'Invoice'));
        $prefix = trim((string) $request->input('invoice_no_prefix', 'Invoice No.'));
        $isPaid = $document === 'invoice_paid';
        $showDue = false;

        if ($document === 'invoice_paid') {
            $suffix = trim((string) $request->input('invoice_heading_paid', ''));
            if ($suffix !== '') {
                $heading = trim($heading.' '.$suffix);
            }
        } elseif ($document === 'quotation') {
            $heading = trim((string) $request->input('quotation_heading', '')) ?: 'Quotation';
            $prefix = trim((string) $request->input('quotation_no_prefix', '')) ?: 'Quotation No.';
        } elseif ($document === 'sales_order') {
            $heading = trim((string) $request->input('common_settings.sales_order_heading', '')) ?: 'Sales Order';
            $prefix = trim((string) $request->input('quotation_no_prefix', '')) ?: 'Order No.';
            $showDue = self::checked($request, 'common_settings.show_due_date');
        } elseif ($document === 'proforma') {
            $heading = trim((string) $request->input('common_settings.proforma_heading', '')) ?: 'Proforma Invoice';
            $showDue = self::checked($request, 'common_settings.show_due_date');
        } else {
            $suffix = trim((string) $request->input('invoice_heading_not_paid', ''));
            if ($suffix !== '') {
                $heading = trim($heading.' '.$suffix);
            }
            $showDue = self::checked($request, 'common_settings.show_due_date');
        }

        $showSku = self::checked($request, 'show_sku');
        $showBrand = self::checked($request, 'show_brand');
        $showCustomer = self::checked($request, 'show_customer');
        $showPayments = self::checked($request, 'show_payments');
        $showLetterHead = self::checked($request, 'show_letter_head');
        $showLogo = self::checked($request, 'show_logo');
        $itemDiscountLabel = trim((string) $request->input('common_settings.item_discount_label', ''));

        $line1 = self::line('(Sample) Thobe', 'White', '2', 'Pc', $money(45), $itemDiscountLabel !== '' ? $money(5) : '', $money(85), $showSku ? 'SAMPLE-THB' : '', $showBrand ? 'Sample brand' : '');
        $line2 = self::line('(Sample) Tailoring', '', '1', 'Job', $money(40), $itemDiscountLabel !== '' ? $money(0) : '', $money(40), $showSku ? 'SAMPLE-TAIL' : '', '');

        $total = 131.25;
        $paidAmount = $isPaid ? $total : ($document === 'quotation' ? 0 : 50);
        $dueAmount = $document === 'quotation' ? 0 : round($total - $paidAmount, 2);

        $payments = [];
        if ($showPayments && $document !== 'quotation' && $paidAmount > 0) {
            $payments[] = [
                'method' => 'Cash',
                'amount' => $money($paidAmount),
                'date' => $invoiceDate,
                'is_return' => false,
            ];
        }

        $businessName = '';
        if (self::checked($request, 'show_business_name') && $business) {
            $businessName = (string) $business->name;
        }
        $locationName = '';
        if (self::checked($request, 'show_location_name') && $location) {
            $locationName = (string) $location->name;
        }
        $displayName = $businessName;
        if ($locationName !== '' && strcasecmp($businessName, $locationName) !== 0) {
            $displayName = trim($displayName.($displayName !== '' ? ', ' : '').$locationName);
        }

        $mode = InvoicePrintAppearance::normalize($request->input('common_settings.print_appearance'));
        $skin = InvoicePrintAppearance::normalizeSkin($request->input('common_settings.designer', []));

        $data = [
            'header_text' => (string) $request->input('header_text', ''),
            'business_name' => $businessName,
            'location_name' => $locationName,
            'display_name' => $displayName,
            'sub_heading_line1' => (string) $request->input('sub_heading_line1', ''),
            'sub_heading_line2' => (string) $request->input('sub_heading_line2', ''),
            'sub_heading_line3' => (string) $request->input('sub_heading_line3', ''),
            'sub_heading_line4' => (string) $request->input('sub_heading_line4', ''),
            'sub_heading_line5' => (string) $request->input('sub_heading_line5', ''),
            'address' => self::address($request, $location),
            'contact' => self::contact($request, $location),
            'website' => ($location && ! empty($location->website)) ? $location->website : '',
            'location_custom_fields' => '',
            'logo' => self::logoUrl($request, $business, $layout, $showLogo),
            'letter_head' => $showLetterHead ? self::letterHeadUrl($request, $layout) : null,
            'invoice_heading' => $heading,
            'invoice_no' => 'SAMPLE-0001',
            'invoice_no_prefix' => $prefix,
            'date_label' => (string) $request->input('date_label', 'Date'),
            'invoice_date' => $invoiceDate,
            'due_date_label' => $showDue ? (string) $request->input('common_settings.due_date_label', 'Due date') : '',
            'due_date' => $showDue ? $dueDate : '',
            'customer_label' => $showCustomer ? ((string) $request->input('customer_label', '') ?: 'Customer') : '',
            'customer_name' => $showCustomer ? '(Sample) Walk-in customer' : '',
            'customer_info' => $showCustomer ? '<b>(Sample) Walk-in customer</b><br>Mobile: 0000 0000' : '',
            'customer_tax_number' => '',
            'customer_tax_label' => (string) $request->input('client_tax_label', ''),
            'client_tax_label' => (string) $request->input('client_tax_label', ''),
            'client_id_label' => self::checked($request, 'show_client_id') ? (string) $request->input('client_id_label', '') : '',
            'client_id' => self::checked($request, 'show_client_id') ? 'SAMPLE-C' : '',
            'sales_person_label' => self::checked($request, 'show_sales_person') ? ((string) $request->input('sales_person_label', '') ?: 'Sales person') : '',
            'sales_person' => self::checked($request, 'show_sales_person') ? '(Sample) Cashier' : '',
            'commission_agent_label' => '',
            'commission_agent' => '',
            'table_product_label' => (string) $request->input('table_product_label', 'Product'),
            'table_qty_label' => (string) $request->input('table_qty_label', 'Qty'),
            'table_unit_price_label' => (string) $request->input('table_unit_price_label', 'Unit Price'),
            'table_subtotal_label' => (string) $request->input('table_subtotal_label', 'Subtotal'),
            'subtotal_label' => (string) $request->input('sub_total_label', 'Subtotal'),
            'subtotal' => $money(125),
            'discount_label' => (string) $request->input('discount_label', 'Discount'),
            'discount' => $money(0),
            'tax_label' => (string) $request->input('tax_label', 'Tax'),
            'tax' => $money(6.25),
            'total_label' => (string) $request->input('total_label', 'Total'),
            'total' => $money($total),
            'total_unformatted' => $total,
            'subtotal_unformatted' => 125,
            'discount_amount_unformatted' => 0,
            'paid_label' => (string) $request->input('paid_label', 'Paid'),
            'total_paid' => ($showPayments && $paidAmount > 0) ? $money($paidAmount) : '',
            'total_due_label' => (string) $request->input('total_due_label', 'Total due'),
            'total_due' => ($dueAmount > 0) ? $money($dueAmount) : '',
            'footer_text' => (string) $request->input('footer_text', ''),
            'lines' => [$line1, $line2],
            'payments' => $payments,
            'taxes' => [],
            'item_discount_label' => $itemDiscountLabel,
            'show_barcode' => self::checked($request, 'show_barcode') ? 1 : 0,
            'show_qr_code' => 0,
            'qr_code_text' => '',
            'show_sku' => $showSku ? 1 : 0,
            'show_brand' => $showBrand ? 1 : 0,
            'cat_code_label' => (string) $request->input('cat_code_label', ''),
            'table_tax_headings' => array_values(array_filter((array) $request->input('table_tax_headings', []), 'strlen')),
            'design' => (string) $request->input('design', $layout->design ?? 'classic'),
            'print_appearance' => $mode,
            'design_skin' => $skin,
            'currency' => [
                'symbol' => $symbol,
                'thousand_separator' => ($business && ! empty($business->thousand_separator)) ? $business->thousand_separator : ',',
                'decimal_separator' => ($business && ! empty($business->decimal_separator)) ? $business->decimal_separator : '.',
            ],
            'currency_symbol' => $symbol,
            'additional_notes' => 'Sample note only. This is not a saved sale.',
            'shipping_address' => '',
            'is_quotation' => $document === 'quotation' ? 1 : 0,
            'status' => $document === 'quotation' ? 'draft' : 'final',
            'sub_status' => $document === 'quotation' ? 'quotation' : ($document === 'proforma' ? 'proforma' : null),
            'hide_price' => self::checked($request, 'common_settings.hide_price'),
            'show_total_in_words' => 0,
            'total_in_words' => '',
            'tax_label1' => '',
            'tax_info1' => '',
            'tax_label2' => '',
            'tax_info2' => '',
        ];

        if ($business && self::checked($request, 'show_tax_1') && ! empty($business->tax_number_1)) {
            $data['tax_label1'] = ! empty($business->tax_label_1) ? $business->tax_label_1.': ' : '';
            $data['tax_info1'] = $business->tax_number_1;
        }
        if ($business && self::checked($request, 'show_tax_2') && ! empty($business->tax_number_2)) {
            $data['tax_label2'] = ! empty($business->tax_label_2) ? $business->tax_label_2.': ' : '';
            $data['tax_info2'] = $business->tax_number_2;
        }

        if ($mode === InvoicePrintAppearance::BLACK_WHITE) {
            $data['color'] = '#111111';
        } elseif (! empty($skin['use_colours']) && $skin['primary'] !== '') {
            $data['color'] = $skin['primary'];
        }

        return new PreviewReceiptDetails($data);
    }

    private static function line($name, $variation, $qty, $units, $unitPrice, $discount, $total, $sku, $brand): array
    {
        return [
            'name' => $name,
            'product_variation' => 'DUMMY',
            'variation' => $variation !== '' ? $variation : 'DUMMY',
            'quantity' => $qty,
            'units' => $units,
            'unit_price_before_discount' => $unitPrice,
            'unit_price_inc_tax' => $unitPrice,
            'line_discount' => $discount,
            'line_total' => $total,
            'line_total_exc_tax' => $total,
            'unit_price_exc_tax' => $unitPrice,
            'price_exc_tax' => 0,
            'tax_unformatted' => 0,
            'quantity_uf' => (float) $qty,
            'tax_name' => '',
            'tax_percent' => '',
            'group_tax_details' => [],
            'total_line_discount' => $discount,
            'line_discount_percent' => '',
            'base_unit_multiplier' => 1,
            'base_unit_price' => '',
            'orig_quantity' => $qty,
            'sub_sku' => $sku,
            'brand' => $brand,
            'product_description' => 'Sample item — not a stock movement',
            'sell_line_note' => '',
            'image' => '',
            'tax' => '',
            'item_tax' => '',
            'cat_code' => '',
            'lot_number' => '',
            'product_custom_fields' => '',
            'modifiers' => [],
            'warranty_name' => '',
            'warranty_exp_date' => '',
            'base_unit_name' => '',
            'unit_price_uf' => 0,
            'line_total_uf' => 0,
            'is_repair_line' => false,
        ];
    }

    private static function checked(Request $request, string $key): bool
    {
        $value = $request->input($key);

        return ! empty($value) && (string) $value !== '0';
    }

    private static function address(Request $request, $location): string
    {
        if (! $location) {
            return '';
        }
        $parts = [];
        $push = function ($flag, $value) use ($request, &$parts) {
            if (! self::checked($request, $flag)) {
                return;
            }
            $value = trim((string) $value);
            if ($value !== '' && ! in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        };
        $push('show_landmark', $location->landmark ?? '');
        $push('show_city', $location->city ?? '');
        $push('show_state', $location->state ?? '');
        $push('show_zip_code', $location->zip_code ?? '');
        $push('show_country', $location->country ?? '');

        return implode(', ', $parts);
    }

    private static function contact(Request $request, $location): string
    {
        if (! $location) {
            return '';
        }
        $contact = '';
        if (self::checked($request, 'show_mobile_number') && ! empty($location->mobile)) {
            $contact .= '<b>Mobile:</b> '.$location->mobile;
        }
        if (self::checked($request, 'show_alternate_number') && ! empty($location->alternate_number)) {
            $contact .= ($contact !== '' ? ', ' : '').$location->alternate_number;
        }
        if (self::checked($request, 'show_email') && ! empty($location->email)) {
            $contact .= ($contact !== '' ? '<br>' : '').'Email: '.$location->email;
        }

        return $contact;
    }

    private static function logoUrl(Request $request, $business, ?InvoiceLayout $layout, bool $showLogo)
    {
        if ($business && ! empty($business->logo) && is_file(public_path('uploads/business_logos/'.$business->logo))) {
            return asset('uploads/business_logos/'.$business->logo);
        }
        $uploaded = self::imageDataUrl($request->input('logo_data_url'));
        if ($showLogo && $uploaded) {
            return $uploaded;
        }
        if ($showLogo && $layout && ! empty($layout->logo) && is_file(public_path('uploads/invoice_logos/'.$layout->logo))) {
            return asset('uploads/invoice_logos/'.$layout->logo);
        }

        return false;
    }

    private static function letterHeadUrl(Request $request, ?InvoiceLayout $layout)
    {
        $uploaded = self::imageDataUrl($request->input('letter_head_data_url'));
        if ($uploaded) {
            return $uploaded;
        }
        if ($layout && ! empty($layout->letter_head) && is_file(public_path('uploads/invoice_logos/'.$layout->letter_head))) {
            return asset('uploads/invoice_logos/'.$layout->letter_head);
        }

        return null;
    }

    private static function imageDataUrl($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        if (strlen($value) > 1400000) {
            return null;
        }
        if (! preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $value)) {
            return null;
        }

        return $value;
    }
}
