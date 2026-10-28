{{-- A5 Landscape POS bill — 210mm × 148mm --}}
@include('sale_pos.receipts.partial.money_font')
<style>
/* Physical page: A5 landscape. Safe content padding lives on .a5-sheet (not @page). */
@page {
    size: A5 landscape;
    margin: 0;
}

.a5-sheet {
    width: 100%;
    max-width: 210mm;
    min-height: 148mm;
    height: 148mm;
    max-height: 148mm;
    margin: 0;
    padding: 8mm 10mm;
    overflow: hidden;
    box-sizing: border-box;
    color: #111 !important;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 10px;
    line-height: 1.3;
    background: #fff;
    display: flex;
    flex-direction: column;
}
.a5-sheet *,
.a5-sheet *::before,
.a5-sheet *::after {
    box-sizing: border-box;
}
.a5-sheet-main {
    flex: 1 1 auto;
    min-height: 0;
}

.a5-sheet .a4-top {
    display: table;
    width: 100%;
    table-layout: fixed;
    border-bottom: 2px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    padding-bottom: 5px;
    margin: 0 0 6px;
}
.a5-sheet .a4-top-left,
.a5-sheet .a4-top-right {
    display: table-cell;
    vertical-align: middle;
    width: 50%;
}
.a5-sheet .a4-top-right { text-align: right; vertical-align: top; }

/* Logo LEFT of shop name/address — compact, no overlap */
.a5-sheet .a5-brand-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    width: 100%;
    min-width: 0;
}
.a5-sheet .a5-brand-logo {
    flex: 0 0 40px;
    width: 40px;
    max-width: 40px;
    overflow: hidden;
    line-height: 0;
}
.a5-sheet .a5-brand-text {
    flex: 1 1 auto;
    min-width: 0;
}
.a5-sheet .a4-logo,
.a5-sheet img.a4-logo,
.a5-sheet .a5-brand-logo img {
    display: block !important;
    max-height: 38px !important;
    max-width: 40px !important;
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    float: none !important;
    position: static !important;
    object-fit: contain !important;
}
.a5-sheet .a4-biz-name {
    margin: 0 0 2px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .02em;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
    text-transform: uppercase;
    word-break: break-word;
}
.a5-sheet .a4-biz-meta {
    margin: 0;
    font-size: 8.5px;
    color: #444 !important;
    line-height: 1.35;
    word-break: break-word;
}
.a5-sheet .a4-doc-title {
    margin: 0 0 2px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a5-sheet .a4-doc-meta {
    margin: 0;
    font-size: 9.5px;
}
.a5-sheet .a4-doc-meta strong { color: #333; }
.a5-sheet .a4-badge {
    display: inline-block;
    margin-top: 3px;
    padding: 2px 6px;
    border-radius: 3px;
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-size: 9px;
    font-weight: 700;
}

/* No negative margins — those caused left-edge clipping on physical print */
.a5-sheet .a4-parties {
    display: table;
    width: 100%;
    table-layout: fixed;
    margin: 0 0 6px;
    border-spacing: 0;
}
.a5-sheet .a4-party {
    display: table-cell;
    width: 50%;
    vertical-align: top;
}
.a5-sheet .a4-party > .a4-party-inner {
    padding: 5px 7px;
    background: #f7f8fa;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
}
.a5-sheet .a4-party:first-child {
    padding-right: 3px;
}
.a5-sheet .a4-party:last-child {
    padding-left: 3px;
}
.a5-sheet .a4-party-label {
    display: block;
    margin-bottom: 2px;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a5-sheet .a4-party-body {
    margin: 0;
    font-size: 9.5px;
    color: #222 !important;
    word-break: break-word;
}

.a5-sheet table.a4-lines {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin: 0 0 6px;
    font-size: 9.5px;
}
.a5-sheet table.a4-lines thead th {
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 8px;
    padding: 4px 5px;
    border: 0;
}
.a5-sheet table.a4-lines tbody td {
    padding: 4px 5px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
    color: #111 !important;
    overflow: hidden;
}
.a5-sheet table.a4-lines tbody tr:nth-child(even) td {
    background: #fafbfc;
}
.a5-sheet .a4-item-name {
    font-weight: 700;
    color: #111 !important;
    display: block;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.a5-sheet .a4-item-sub {
    display: block;
    font-size: 8px;
    color: #64748b;
    margin-top: 1px;
    word-break: break-word;
}
.a5-sheet .a4-item-repair-issue {
    display: block;
    font-size: 9px;
    font-weight: 700;
    color: #1e3a5f;
    margin-top: 1px;
}
.a5-sheet .a4-item-repair-meta {
    display: block;
    font-size: 8px;
    color: #64748b;
}
.a5-sheet .a4-item-img {
    float: left;
    width: 24px;
    height: 24px;
    object-fit: cover;
    margin-right: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 2px;
}
.a5-sheet .a4-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.a5-sheet .a4-center { text-align: center; }

.a5-sheet .a4-bottom {
    display: table;
    width: 100%;
    table-layout: fixed;
    margin-top: 2px;
}
.a5-sheet .a4-bottom-left,
.a5-sheet .a4-bottom-right {
    display: table-cell;
    vertical-align: top;
    width: 50%;
}
.a5-sheet .a4-bottom-right { padding-left: 8px; }
.a5-sheet .a4-pay-box {
    padding: 0;
    border: 1.5px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    border-radius: 3px;
    overflow: hidden;
    font-size: 9px;
    color: #333 !important;
}
.a5-sheet .a4-pay-head {
    display: block;
    padding: 3px 7px;
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}
.a5-sheet .a4-pay-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
    padding: 3px 7px;
    border-top: 1px dotted #d1d5db;
}
.a5-sheet .a4-pay-row:first-of-type { border-top: 0; }
.a5-sheet .a4-pay-row.is-change { background: #f8fafc; }
.a5-sheet .a4-pay-method {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 9px;
    color: #111 !important;
}
.a5-sheet .a4-pay-row.is-change .a4-pay-method { color: #64748b !important; }
.a5-sheet .a4-pay-meta {
    display: block;
    margin-top: 1px;
    font-size: 8px;
    font-weight: 400;
    color: #6b7280 !important;
    letter-spacing: 0;
    text-transform: none;
}
.a5-sheet .a4-pay-amt {
    text-align: right;
    font-weight: 700;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.a5-sheet .a4-pay-row.is-change .a4-pay-amt { color: #64748b !important; }
.a5-sheet .a4-totals {
    width: 100%;
    max-width: 100%;
    margin-left: 0;
    border-collapse: collapse;
    font-size: 10px;
}
.a5-sheet .a4-totals td {
    padding: 2px 0;
    color: #222 !important;
}
.a5-sheet .a4-totals .a4-tot-label { text-align: left; color: #555 !important; }
.a5-sheet .a4-totals .a4-tot-val { text-align: right; font-weight: 600; white-space: nowrap; }
.a5-sheet .a4-totals tr.a4-grand td {
    padding-top: 4px;
    border-top: 2px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    font-size: 12px;
    font-weight: 800;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a5-sheet .a4-foot {
    margin-top: 6px;
    padding-top: 4px;
    border-top: 1px solid #e5e7eb;
    font-size: 8px;
    color: #555 !important;
    text-align: center;
}
.a5-sheet .a4-sign {
    display: table;
    width: 100%;
    margin-top: 8px;
}
.a5-sheet .a4-sign-cell {
    display: table-cell;
    width: 45%;
    text-align: center;
    vertical-align: bottom;
}
.a5-sheet .a4-sign-line {
    border-top: 1px solid #999;
    margin: 0 12px 2px;
    padding-top: 2px;
    font-size: 8px;
    color: #666 !important;
}
.a5-sheet .a4-save-box {
    margin: 5px 0 2px;
    padding: 5px 8px;
    border: 1.5px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    border-radius: 4px;
    text-align: center;
    background: #fff;
}
.a5-sheet .a4-save-box .a4-save-label {
    display: block;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #555 !important;
    margin-bottom: 1px;
}
.a5-sheet .a4-save-box .a4-save-amount {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}

/* Thank-you / branding footer at bottom of A5 landscape sheet */
.a5-sheet-brand-footer {
    flex: 0 0 auto;
    margin-top: auto;
    padding-top: 6px;
    border-top: 1px solid #d1d5db;
    text-align: center;
}
.a5-sheet-brand-footer .a5-thanks {
    margin: 0 0 2px;
    font-size: 9.5px;
    font-weight: 700;
    color: #222 !important;
    letter-spacing: .01em;
}
.a5-sheet-brand-footer .a5-powered {
    margin: 0;
    font-size: 7.5px;
    font-weight: 500;
    color: #666 !important;
    letter-spacing: .02em;
}

@media print {
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 210mm;
        height: 148mm;
        overflow: hidden;
        background: #fff !important;
    }
    .a5-sheet {
        width: 210mm !important;
        max-width: 210mm !important;
        min-height: 148mm !important;
        height: 148mm !important;
        max-height: 148mm !important;
        padding: 8mm 10mm !important;
        margin: 0 !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .a5-sheet .a4-logo,
    .a5-sheet .a5-brand-logo img {
        max-width: 40px !important;
        max-height: 38px !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        object-fit: contain !important;
    }
    .a5-sheet .a5-brand-row {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 8px !important;
    }
    .a5-sheet .a5-brand-logo {
        flex: 0 0 40px !important;
        width: 40px !important;
        max-width: 40px !important;
        overflow: hidden !important;
    }
}
</style>

<div class="a5-sheet a4-inv">
    <div class="a5-sheet-main">
    <div class="a4-top">
        <div class="a4-top-left">
            <div class="a5-brand-row">
                @if(!empty($receipt_details->logo))
                    <div class="a5-brand-logo">
                        <img src="{{ $receipt_details->logo }}" class="a4-logo" alt="">
                    </div>
                @endif
                <div class="a5-brand-text">
                    @if(!empty($receipt_details->display_name))
                        <h1 class="a4-biz-name">{{ $receipt_details->display_name }}</h1>
                    @endif
                    @if(!empty($receipt_details->header_text))
                        <div>{!! $receipt_details->header_text !!}</div>
                    @endif
                    <p class="a4-biz-meta">
                        @if(!empty($receipt_details->address))
                            {!! $receipt_details->address !!}<br>
                        @endif
                        @if(!empty($receipt_details->contact))
                            {!! $receipt_details->contact !!}<br>
                        @endif
                        @if(!empty($receipt_details->website))
                            {{ $receipt_details->website }}<br>
                        @endif
                        @if(!empty($receipt_details->tax_info1))
                            <strong>{{ $receipt_details->tax_label1 }}</strong> {{ $receipt_details->tax_info1 }}
                        @endif
                        @if(!empty($receipt_details->tax_info2))
                            &nbsp; <strong>{{ $receipt_details->tax_label2 }}</strong> {{ $receipt_details->tax_info2 }}
                        @endif
                        @if(!empty($receipt_details->sub_heading_line1))
                            <br>{{ $receipt_details->sub_heading_line1 }}
                        @endif
                        @if(!empty($receipt_details->sub_heading_line2))
                            <br>{{ $receipt_details->sub_heading_line2 }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="a4-top-right">
            @if(!empty($receipt_details->invoice_heading))
                <div class="a4-doc-title">{!! $receipt_details->invoice_heading !!}</div>
            @endif
            <p class="a4-doc-meta">
                @if(!empty($receipt_details->invoice_no_prefix))
                    <strong>{!! $receipt_details->invoice_no_prefix !!}</strong>
                @endif
                {{ $receipt_details->invoice_no }}<br>
                @if(!empty($receipt_details->date_label))
                    <strong>{{ $receipt_details->date_label }}:</strong>
                @endif
                {{ $receipt_details->invoice_date }}
                @if(!empty($receipt_details->sales_person))
                    <br><strong>{{ $receipt_details->sales_person_label ?? 'Sales Person' }}:</strong> {{ $receipt_details->sales_person }}
                @endif
            </p>
            @if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
                <span class="a4-badge">{!! $receipt_details->total_due_label !!}: {{ $receipt_details->total_due }}</span>
            @endif
        </div>
    </div>

    <div class="a4-parties">
        <div class="a4-party">
            <div class="a4-party-inner">
                <span class="a4-party-label">{{ $receipt_details->customer_label ?? 'Bill To' }}</span>
                <div class="a4-party-body">
                    @if(!empty($receipt_details->customer_info))
                        {!! $receipt_details->customer_info !!}
                    @else
                        —
                    @endif
                    @if(!empty($receipt_details->customer_tax_number))
                        <br><strong>{{ $receipt_details->client_tax_label ?? 'Tax' }}:</strong> {{ $receipt_details->customer_tax_number }}
                    @endif
                </div>
            </div>
        </div>
        <div class="a4-party">
            <div class="a4-party-inner">
                <span class="a4-party-label">@lang('lang_v1.shipping_address') / Notes</span>
                <div class="a4-party-body">
                    @if(!empty($receipt_details->shipping_address))
                        {!! $receipt_details->shipping_address !!}
                    @elseif(!empty($receipt_details->shipping_custom_fields))
                        {!! $receipt_details->shipping_custom_fields !!}
                    @else
                        @if(!empty($receipt_details->additional_notes))
                            {!! nl2br(e($receipt_details->additional_notes)) !!}
                        @else
                            Delivered / Collected at showroom
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $show_disc_col = !empty($receipt_details->item_discount_label);
        $col_desc = $show_disc_col ? '46%' : '52%';
    @endphp
    <table class="a4-lines">
        <thead>
            <tr>
                <th style="width:4%;" class="a4-center">#</th>
                <th style="width:{{ $col_desc }}; text-align:left;">{{ $receipt_details->table_product_label }}</th>
                <th style="width:12%;" class="a4-center">{{ $receipt_details->table_qty_label }}</th>
                <th style="width:14%;" class="a4-num">{{ $receipt_details->table_unit_price_label }}</th>
                @if($show_disc_col)
                    <th style="width:10%;" class="a4-num">{{ $receipt_details->item_discount_label }}</th>
                @endif
                <th style="width:16%;" class="a4-num">{{ $receipt_details->table_subtotal_label }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receipt_details->lines as $line)
                <tr>
                    <td class="a4-center">{{ $loop->iteration }}</td>
                    <td>
                        @if(!empty($line['image']))
                            <img src="{{ $line['image'] }}" alt="" class="a4-item-img">
                        @endif
                        <span class="a4-item-name">
                            {{ $line['name'] }}
                            @if(!empty($line['product_variation']) && $line['product_variation'] != 'DUMMY')
                                {{ $line['product_variation'] }}
                            @endif
                            @if(!empty($line['variation']) && $line['variation'] != 'DUMMY')
                                {{ $line['variation'] }}
                            @endif
                        </span>
                        <span class="a4-item-sub">
                            @if(!empty($line['is_repair_line']) && !empty($line['sell_line_note']))
                                @php
                                    $repair_note_lines = preg_split("/\r\n|\n|\r/", strip_tags($line['sell_line_note']));
                                @endphp
                                @if(!empty($repair_note_lines[0]))
                                    <span class="a4-item-repair-issue">{{ $repair_note_lines[0] }}</span>
                                @endif
                                @if(!empty($repair_note_lines[1]))
                                    @if(!empty($repair_note_lines[0]))<br>@endif
                                    <span class="a4-item-repair-meta">{{ $repair_note_lines[1] }}</span>
                                @endif
                            @else
                                @if(!empty($line['sub_sku'])){{ $line['sub_sku'] }}@endif
                                @if(!empty($line['brand'])) · {{ $line['brand'] }}@endif
                                @if(!empty($line['product_description']))
                                    @if(!empty($line['sub_sku']) || !empty($line['brand'])) · @endif
                                    {!! strip_tags($line['product_description']) !!}
                                @endif
                            @endif
                        </span>
                    </td>
                    <td class="a4-center">
                        {{ $line['quantity'] }}
                        @if(!empty($line['units']))
                            <span class="a4-item-sub" style="display:inline;">{{ $line['units'] }}</span>
                        @endif
                    </td>
                    <td class="a4-num">{!! vspos_rcpt_money($line['unit_price_before_discount']) !!}</td>
                    @if($show_disc_col)
                        <td class="a4-num">{{ $line['line_discount'] ?? '0' }}</td>
                    @endif
                    <td class="a4-num"><strong>{!! vspos_rcpt_money($line['line_total']) !!}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $show_disc_col ? 6 : 5 }}" class="a4-center">—</td>
                </tr>
            @endforelse
            @if(!empty($receipt_details->trade_ins))
                @foreach($receipt_details->trade_ins as $ti)
                    <tr class="a4-tradein-row">
                        <td class="a4-center">—</td>
                        <td colspan="{{ $show_disc_col ? 3 : 2 }}">
                            <span class="a4-item-name" style="color:#b45309;">EXCHANGE / TRADE-IN</span>
                            <span class="a4-item-sub">
                                {{ $ti['label'] ?? '' }}
                                @if(!empty($ti['notes']))
                                    <br>{{ $ti['notes'] }}
                                @endif
                            </span>
                        </td>
                        <td class="a4-num" colspan="{{ $show_disc_col ? 2 : 2 }}">
                            <strong style="color:#b91c1c;">(-) {!! vspos_rcpt_money($ti['exchange_value']) !!}</strong>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="a4-bottom">
        <div class="a4-bottom-left">
            @if(!empty($receipt_details->payments))
                <div class="a4-pay-box">
                    <span class="a4-pay-head">{{ $receipt_details->paid_label ?? 'Payments' }}</span>
                    @foreach($receipt_details->payments as $payment)
                        <div class="a4-pay-row {{ !empty($payment['is_return']) ? 'is-change' : '' }}">
                            <div>
                                <span class="a4-pay-method">
                                    {{ $payment['method'] }}
                                    @if(!empty($payment['is_return'])) (-)@endif
                                </span>
                                @if(!empty($payment['date']))
                                    <span class="a4-pay-meta">{{ $payment['date'] }}</span>
                                @endif
                            </div>
                            <div class="a4-pay-amt">{!! vspos_rcpt_money($payment['amount']) !!}</div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if(!empty($receipt_details->additional_notes) && empty($receipt_details->shipping_address))
                <div class="a4-pay-box" style="margin-top:6px;padding:8px 10px;">
                    <strong>Notes</strong>
                    {!! nl2br(e($receipt_details->additional_notes)) !!}
                </div>
            @endif
        </div>
        <div class="a4-bottom-right">
            <table class="a4-totals">
                @if(!empty($receipt_details->subtotal_label))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->subtotal_label }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->subtotal) !!}</td>
                </tr>
                @endif
                @if(!empty($receipt_details->total_exempt_uf) || (!empty($receipt_details->discount) && (float) str_replace([',',' '], '', strip_tags($receipt_details->discount ?? '0')) != 0))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->discount_label ?? 'Discount' }}</td>
                    <td class="a4-tot-val">(-) {!! vspos_rcpt_money($receipt_details->discount) !!}</td>
                </tr>
                @endif
                @if(!empty($receipt_details->trade_in_total_uf))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->trade_in_label ?? 'Exchange / Trade-In' }}</td>
                    <td class="a4-tot-val">(-) {!! vspos_rcpt_money($receipt_details->trade_in_total) !!}</td>
                </tr>
                @endif
                @if(!empty($receipt_details->tax))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->tax_label ?? 'Tax' }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->tax) !!}</td>
                </tr>
                @endif
                @if(!empty($receipt_details->shipping_charges))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->shipping_charges_label ?? 'Shipping' }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->shipping_charges) !!}</td>
                </tr>
                @endif
                <tr class="a4-grand">
                    <td class="a4-tot-label">{{ $receipt_details->total_label ?? 'Total' }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->total) !!}</td>
                </tr>
                @if(!empty($receipt_details->total_paid))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->paid_label ?? 'Paid' }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->total_paid) !!}</td>
                </tr>
                @endif
                @if(!empty($receipt_details->total_due))
                <tr>
                    <td class="a4-tot-label">{{ $receipt_details->total_due_label ?? 'Due' }}</td>
                    <td class="a4-tot-val">{!! vspos_rcpt_money($receipt_details->total_due) !!}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if(!empty($receipt_details->show_mrp_savings) && !empty($receipt_details->mrp_savings))
        <div class="a4-save-box">
            <span class="a4-save-label">{{ $receipt_details->mrp_savings_label ?? __('lang_v1.your_discount_is') }}</span>
            <span class="a4-save-amount">{!! vspos_rcpt_money($receipt_details->mrp_savings) !!}</span>
        </div>
    @endif

    <div class="a4-sign">
        <div class="a4-sign-cell">
            <div class="a4-sign-line">Customer Signature</div>
        </div>
        <div class="a4-sign-cell">
            <div class="a4-sign-line">Authorized Signature</div>
        </div>
    </div>

    @if(!empty($receipt_details->footer_text))
        <div class="a4-foot">{!! $receipt_details->footer_text !!}</div>
    @endif
    </div>{{-- /.a5-sheet-main --}}

    <div class="a5-sheet-brand-footer">
        <p class="a5-thanks">Thank you for choosing RAJA TYRE SHOP</p>
        <p class="a5-powered">QPOS By AVENQUE (PVT) LTD | 076 822 2201</p>
    </div>
</div>
