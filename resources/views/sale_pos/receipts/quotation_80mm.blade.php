<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation-{{ $receipt_details->invoice_no }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap');
        * { font-family: 'Inter', Arial, Helvetica, sans-serif; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-size: 11px;
            color: #111;
            line-height: 1.35;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page { size: 80mm auto; margin: 0; }
        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 3mm 2.5mm 4mm;
            background: #fff;
            overflow: hidden;
        }
        .ticket p { margin: 0; padding: 0; }
        .text-box {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid #111;
        }
        .receipt-logo {
            display: block;
            max-width: 58mm;
            max-height: 22mm;
            width: auto;
            height: auto;
            margin: 0 auto 5px;
            object-fit: contain;
        }
        .business-name {
            font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif !important;
            font-size: 16px !important;
            font-weight: 900 !important;
            text-align: center !important;
            display: block !important;
            margin: 2px 0 4px !important;
            color: #000 !important;
            letter-spacing: 0.6px !important;
            text-transform: uppercase !important;
            line-height: 1.15 !important;
        }
        .quote-badge {
            display: block;
            text-align: center;
            margin: 6px 0 4px;
            padding: 5px 4px;
            border: 2px solid #111;
            font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .quote-note {
            text-align: center;
            font-size: 9px;
            color: #333;
            margin: 0 0 6px;
            line-height: 1.35;
        }
        .textbox-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
            padding: 3px 0;
            font-size: 10.5px;
            border-bottom: 1px dotted #999;
            width: 100%;
        }
        .textbox-info.border-top {
            border-top: 1px solid #111;
            margin-top: 4px;
            padding-top: 4px;
        }
        .textbox-info > p { margin: 0; }
        .textbox-info .f-left {
            flex: 0 1 auto;
            max-width: 40%;
            font-weight: 700;
            text-align: left;
        }
        .textbox-info .f-right {
            flex: 1 1 auto;
            min-width: 0;
            text-align: right;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .bw { word-wrap: break-word; overflow-wrap: anywhere; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 8px 0 4px;
            table-layout: fixed;
        }
        thead th {
            border-top: 1.5px solid #111;
            border-bottom: 1.5px solid #111;
            padding: 4px 1px;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        thead th:nth-child(1), tbody td:nth-child(1) { width: 4mm; text-align: left; }
        thead th:nth-child(2), tbody td:nth-child(2) { width: auto; text-align: left; }
        thead th:nth-child(3), tbody td:nth-child(3) { width: 12mm; text-align: right; }
        thead th:nth-child(4), tbody td:nth-child(4) { width: 14mm; text-align: right; }
        thead th:nth-child(5), tbody td:nth-child(5) { width: 15mm; text-align: right; }
        tbody td {
            padding: 4px 1px;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
            word-break: break-word;
        }
        .f-8 { font-size: 8px; color: #444; }
        .flex-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
            padding: 2px 0;
            font-size: 11px;
        }
        .flex-box > p { margin: 0; }
        .flex-box.total-row {
            border-top: 1.5px solid #111;
            border-bottom: 1.5px solid #111;
            margin: 4px 0;
            padding: 5px 0;
        }
        .flex-box.total-row > p { font-size: 13px; font-weight: 800; }
        .centered { text-align: center; }
        .footer-banner {
            margin-top: 8px;
            padding: 6px 4px;
            border: 1.5px dashed #111;
            text-align: center;
            font-size: 9.5px;
            font-weight: 700;
            line-height: 1.4;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .software-footer {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .center-block { display: block; margin: 6px auto 0; max-width: 100%; }
    </style>
</head>
<body>
<div class="ticket">
    @if(empty($receipt_details->letter_head))
        <div class="text-box">
            @if(!empty($receipt_details->logo))
                <img class="receipt-logo" src="{{ $receipt_details->logo }}" alt="">
            @endif
            <span class="business-name">{{ $receipt_details->display_name ?? ($receipt_details->business_name ?? '') }}</span>
            <p>
                @if(!empty($receipt_details->address))
                    {!! $receipt_details->address !!}<br>
                @endif
                @if(!empty($receipt_details->contact))
                    {!! $receipt_details->contact !!}<br>
                @endif
                @if(!empty($receipt_details->website))
                    {{ $receipt_details->website }}<br>
                @endif
                @if(!empty($receipt_details->location_custom_fields))
                    {!! $receipt_details->location_custom_fields !!}
                @endif
                @if(!empty($receipt_details->tax_info1))
                    <br><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                @endif
                @if(!empty($receipt_details->tax_info2))
                    <br><b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
                @endif
            </p>
        </div>
    @else
        <div class="text-box">
            <img class="receipt-logo" style="max-width:100%;max-height:none;width:100%;" src="{{ $receipt_details->letter_head }}" alt="">
        </div>
    @endif

    <div class="quote-badge">
        {!! $receipt_details->invoice_heading ?: __('lang_v1.quotation') !!}
    </div>
    <p class="quote-note">@lang('lang_v1.quotation_not_an_invoice')</p>

    <div class="border-top textbox-info">
        <p class="f-left"><strong>{!! $receipt_details->invoice_no_prefix ?: __('lang_v1.quotation_no') !!}</strong></p>
        <p class="f-right">{{ $receipt_details->invoice_no }}</p>
    </div>
    <div class="textbox-info">
        <p class="f-left"><strong>{!! $receipt_details->date_label !!}</strong></p>
        <p class="f-right">{{ $receipt_details->invoice_date }}</p>
    </div>

    @if(!empty($receipt_details->sales_person_label))
        <div class="textbox-info">
            <p class="f-left"><strong>{{ $receipt_details->sales_person_label }}</strong></p>
            <p class="f-right">{{ $receipt_details->sales_person }}</p>
        </div>
    @endif

    <div class="textbox-info">
        <p class="f-left"><strong>{{ $receipt_details->customer_label ?? __('contact.customer') }}</strong></p>
        <p class="f-right bw">
            @if(!empty($receipt_details->customer_info))
                {!! $receipt_details->customer_info !!}
            @endif
        </p>
    </div>

    @if(!empty($receipt_details->client_id_label))
        <div class="textbox-info">
            <p class="f-left"><strong>{{ $receipt_details->client_id_label }}</strong></p>
            <p class="f-right">{{ $receipt_details->client_id }}</p>
        </div>
    @endif

    @if(!empty($receipt_details->customer_tax_label))
        <div class="textbox-info">
            <p class="f-left"><strong>{{ $receipt_details->customer_tax_label }}</strong></p>
            <p class="f-right">{{ $receipt_details->customer_tax_number }}</p>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('sale.product')</th>
                <th>@lang('sale.qty')</th>
                @if(empty($receipt_details->hide_price))
                    <th>@lang('sale.unit_price')</th>
                    <th>@lang('sale.subtotal')</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($receipt_details->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $line['name'] }} {{ $line['product_variation'] ?? '' }} {{ $line['variation'] ?? '' }}
                        @if(!empty($line['sub_sku']))
                            <br><span class="f-8">{{ $line['sub_sku'] }}</span>
                        @endif
                        @if(!empty($line['sell_line_note']))
                            <br><span class="f-8">{!! $line['sell_line_note'] !!}</span>
                        @endif
                    </td>
                    <td>
                        {{ $line['quantity'] }}
                        @if(!empty($line['units']))
                            <br><span class="f-8">{{ $line['units'] }}</span>
                        @endif
                    </td>
                    @if(empty($receipt_details->hide_price))
                        <td>{{ $line['unit_price_before_discount'] }}</td>
                        <td>{{ $line['line_total'] }}</td>
                    @endif
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>

    @if(empty($receipt_details->hide_price))
        <div class="flex-box">
            <p>{!! $receipt_details->subtotal_label !!}</p>
            <p>{{ $receipt_details->subtotal }}</p>
        </div>

        @if(!empty($receipt_details->discount))
            <div class="flex-box">
                <p>{!! $receipt_details->discount_label !!}</p>
                <p>(-) {{ $receipt_details->discount }}</p>
            </div>
        @endif

        @if(!empty($receipt_details->tax))
            <div class="flex-box">
                <p>{!! $receipt_details->tax_label !!}</p>
                <p>(+) {{ $receipt_details->tax }}</p>
            </div>
        @endif

        @if(!empty($receipt_details->shipping_charges))
            <div class="flex-box">
                <p>{!! $receipt_details->shipping_charges_label !!}</p>
                <p>{{ $receipt_details->shipping_charges }}</p>
            </div>
        @endif

        <div class="flex-box total-row">
            <p>{!! $receipt_details->total_label ?: __('sale.total') !!}</p>
            <p>{{ $receipt_details->total }}</p>
        </div>

        @if(!empty($receipt_details->total_in_words))
            <p class="centered" style="font-size:9px;margin:2px 0 4px;">
                <small>({{ $receipt_details->total_in_words }})</small>
            </p>
        @endif
    @endif

    @if(!empty($receipt_details->additional_notes))
        <p class="centered" style="margin-top:6px;">{!! nl2br($receipt_details->additional_notes) !!}</p>
    @endif

    <div class="footer-banner">
        @lang('lang_v1.quotation_footer_note')
    </div>

    @if($receipt_details->show_barcode)
        <img class="center-block" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}">
    @endif

    @if(!empty($receipt_details->footer_text))
        <p class="centered" style="margin-top:6px;font-size:9px;">{!! $receipt_details->footer_text !!}</p>
    @endif

    <div class="software-footer">
        <p><strong>{{ config('app.brand_footer') }}</strong></p>
    </div>
</div>
</body>
</html>
