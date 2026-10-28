{{-- Classic A4 — clean letter-size invoice (tiles / ceramics / retail) --}}
@include('sale_pos.receipts.partial.money_font')
<style>
@page { size: A4; margin: 12mm 12mm 14mm; }
.a4-inv {
    width: 100%;
    max-width: 190mm;
    margin: 0 auto;
    color: #111 !important;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 12px;
    line-height: 1.4;
    box-sizing: border-box;
}
.a4-inv * { box-sizing: border-box; }
.a4-inv .a4-top {
    display: table;
    width: 100%;
    border-bottom: 3px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    padding-bottom: 12px;
    margin-bottom: 14px;
}
.a4-inv .a4-top-left,
.a4-inv .a4-top-right {
    display: table-cell;
    vertical-align: top;
    width: 50%;
}
.a4-inv .a4-top-right { text-align: right; }
.a4-inv .a4-logo {
    max-height: 72px;
    width: auto;
    margin-bottom: 6px;
}
.a4-inv .a4-biz-name {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: .02em;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
    text-transform: uppercase;
}
.a4-inv .a4-biz-meta {
    margin: 0;
    font-size: 11px;
    color: #444 !important;
    line-height: 1.45;
}
.a4-inv .a4-doc-title {
    margin: 0 0 6px;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a4-inv .a4-doc-meta {
    margin: 0;
    font-size: 12px;
}
.a4-inv .a4-doc-meta strong { color: #333; }
.a4-inv .a4-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 10px;
    border-radius: 4px;
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-size: 12px;
    font-weight: 700;
}
.a4-inv .a4-parties {
    display: table;
    width: 100%;
    margin-bottom: 14px;
    border-spacing: 10px 0;
    margin-left: -10px;
    margin-right: -10px;
}
.a4-inv .a4-party {
    display: table-cell;
    width: 50%;
    vertical-align: top;
    padding: 10px 12px;
    background: #f7f8fa;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
}
.a4-inv .a4-party-label {
    display: block;
    margin-bottom: 4px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a4-inv .a4-party-body {
    margin: 0;
    font-size: 12px;
    color: #222 !important;
}
.a4-inv table.a4-lines {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 12px;
    font-size: 11.5px;
}
.a4-inv table.a4-lines thead th {
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 10px;
    padding: 8px 8px;
    border: 0;
}
.a4-inv table.a4-lines tbody td {
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
    color: #111 !important;
}
.a4-inv table.a4-lines tbody tr:nth-child(even) td {
    background: #fafbfc;
}
.a4-inv .a4-item-name { font-weight: 700; color: #111 !important; }
.a4-inv .a4-item-sub {
    display: block;
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}
.a4-inv .a4-item-repair-issue {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #1e3a5f;
    margin-top: 2px;
}
.a4-inv .a4-item-repair-meta {
    display: block;
    font-size: 10px;
    color: #64748b;
}
.a4-inv .a4-item-img {
    float: left;
    width: 42px;
    height: 42px;
    object-fit: cover;
    margin-right: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
}
.a4-inv .a4-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.a4-inv .a4-center { text-align: center; }
.a4-inv .a4-bottom {
    display: table;
    width: 100%;
    margin-top: 4px;
}
.a4-inv .a4-bottom-left,
.a4-inv .a4-bottom-right {
    display: table-cell;
    vertical-align: top;
    width: 50%;
}
.a4-inv .a4-pay-box {
    padding: 0;
    border: 1.5px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    border-radius: 4px;
    overflow: hidden;
    font-size: 11px;
    color: #333 !important;
}
.a4-inv .a4-pay-head {
    display: block;
    padding: 7px 12px;
    background: {{ $receipt_details->color ?? '#1a3a4a' }};
    color: #fff !important;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}
.a4-inv .a4-pay-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 12px;
    border-top: 1px dotted #d1d5db;
}
.a4-inv .a4-pay-row:first-of-type { border-top: 0; }
.a4-inv .a4-pay-row.is-change { background: #f8fafc; }
.a4-inv .a4-pay-method {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 11px;
    color: #111 !important;
}
.a4-inv .a4-pay-row.is-change .a4-pay-method { color: #64748b !important; }
.a4-inv .a4-pay-meta {
    display: block;
    margin-top: 2px;
    font-size: 10px;
    font-weight: 400;
    color: #6b7280 !important;
    letter-spacing: 0;
    text-transform: none;
}
.a4-inv .a4-pay-amt {
    text-align: right;
    font-weight: 700;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.a4-inv .a4-pay-row.is-change .a4-pay-amt { color: #64748b !important; }
.a4-inv .a4-totals {
    width: 100%;
    max-width: 280px;
    margin-left: auto;
    border-collapse: collapse;
    font-size: 12px;
}
.a4-inv .a4-totals td {
    padding: 4px 0;
    color: #222 !important;
}
.a4-inv .a4-totals .a4-tot-label { text-align: left; color: #555 !important; }
.a4-inv .a4-totals .a4-tot-val { text-align: right; font-weight: 600; white-space: nowrap; }
.a4-inv .a4-totals tr.a4-grand td {
    padding-top: 8px;
    border-top: 2px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    font-size: 15px;
    font-weight: 800;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
}
.a4-inv .a4-foot {
    margin-top: 18px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
    font-size: 10.5px;
    color: #555 !important;
    text-align: center;
}
.a4-inv .a4-sign {
    display: table;
    width: 100%;
    margin-top: 28px;
}
.a4-inv .a4-sign-cell {
    display: table-cell;
    width: 45%;
    text-align: center;
    vertical-align: bottom;
}
.a4-inv .a4-sign-line {
    border-top: 1px solid #999;
    margin: 0 20px 4px;
    padding-top: 4px;
    font-size: 10px;
    color: #666 !important;
}
.a4-inv .a4-save-box {
    margin: 14px 0 4px;
    padding: 12px 16px;
    border: 2px solid {{ $receipt_details->color ?? '#1a3a4a' }};
    border-radius: 6px;
    text-align: center;
    background: #fff;
}
.a4-inv .a4-save-box .a4-save-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #555 !important;
    margin-bottom: 2px;
}
.a4-inv .a4-save-box .a4-save-amount {
    display: block;
    font-size: 20px;
    font-weight: 800;
    color: {{ $receipt_details->color ?? '#1a3a4a' }} !important;
    letter-spacing: .02em;
}
@media print {
    .a4-inv { max-width: none; }
}
</style>

<div class="a4-inv">
    {{-- Header --}}
    <div class="a4-top">
        <div class="a4-top-left">
            @if(!empty($receipt_details->logo))
                <img src="{{ $receipt_details->logo }}" class="a4-logo" alt="">
            @endif
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

    {{-- Parties --}}
    <div class="a4-parties">
        <div class="a4-party">
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
        <div class="a4-party">
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

    {{-- Lines --}}
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
        </tbody>
    </table>

    {{-- Totals + payments --}}
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
                <div class="a4-pay-box" style="margin-top:8px;padding:10px 12px;">
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

    {{-- Signatures --}}
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
</div>
