{{-- Classic 80mm — Mobile Shop (IMEI + warranty), tuned for monochrome thermal --}}
@include('sale_pos.receipts.partial.money_font')
@php
    $t80_has_payments = !empty($receipt_details->payments);
@endphp
<style>
@page { size: 80mm auto; margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff !important; color: #000 !important; }
.t80 {
    width: 80mm;
    max-width: 80mm;
    margin: 0 auto;
    padding: 2.5mm 2.5mm 3.5mm;
    color: #000 !important;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.4;
    background: #fff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.t80 *,
.t80 p,
.t80 span,
.t80 td,
.t80 div {
    color: #000 !important;
    background-color: transparent;
}
.t80 p { margin: 0; }
.t80 .rcpt-cur {
    font-family: Arial, Helvetica, sans-serif !important;
    font-style: normal !important;
    font-weight: 700 !important;
    font-size: 1em !important;
}
.t80 .t80-head {
    text-align: center;
    padding-bottom: 6px;
    margin-bottom: 6px;
    border-bottom: 2px solid #000;
}
.t80 .t80-logo {
    display: block;
    max-width: 42mm;
    max-height: 16mm;
    width: auto;
    height: auto;
    margin: 0 auto 4px;
    object-fit: contain;
}
.t80 .t80-letterhead {
    display: block;
    width: 100%;
    height: auto;
    margin: 0 0 4px;
}
.t80 .t80-biz {
    margin: 0 0 2px;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .03em;
    text-transform: uppercase;
    color: #000 !important;
    line-height: 1.2;
}
.t80 .t80-tag {
    margin: 0 0 3px;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #000 !important;
}
.t80 .t80-meta {
    font-size: 10px;
    font-weight: 600;
    color: #000 !important;
    line-height: 1.4;
}
.t80 .t80-doc {
    text-align: center;
    margin: 6px 0 4px;
}
.t80 .t80-doc-title {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #000 !important;
}
.t80 .t80-row {
    display: table;
    width: 100%;
    table-layout: fixed;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 0;
}
.t80 .t80-row .l,
.t80 .t80-row .r {
    display: table-cell;
    vertical-align: top;
    color: #000 !important;
}
.t80 .t80-row .l {
    width: 42%;
    text-align: left;
    font-weight: 700;
}
.t80 .t80-row .r {
    width: 58%;
    text-align: right;
    word-break: break-word;
    font-weight: 700;
}
.t80 .t80-party {
    margin: 6px 0;
    padding: 5px 0;
    background: #fff !important;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
}
.t80 .t80-party-label {
    display: block;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #000 !important;
    margin-bottom: 2px;
}
.t80 .t80-party-body {
    font-size: 10.5px;
    font-weight: 700;
    color: #000 !important;
}
.t80 .t80-bar {
    margin: 6px 0 0;
    padding: 5px 0;
    background: #fff !important;
    color: #000 !important;
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
}
.t80 .t80-bar span {
    float: right;
    color: #000 !important;
    font-weight: 800;
}
.t80 .t80-item {
    padding: 7px 0 6px;
    border-bottom: 1px dashed #000;
    clear: both;
}
.t80 .t80-item-name {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #000 !important;
    margin: 0 0 3px;
    line-height: 1.3;
    word-break: break-word;
}
.t80 .t80-item-sub {
    display: block;
    font-size: 9.5px;
    font-weight: 600;
    color: #000 !important;
    margin: 0 0 4px;
    line-height: 1.3;
    word-break: break-word;
}
.t80 .t80-item-sub:empty { display: none; margin: 0; }
.t80 .t80-item-repair-issue {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #000 !important;
}
.t80 .t80-item-repair-meta {
    display: block;
    font-size: 9.5px;
    font-weight: 600;
    color: #000 !important;
}
.t80 .t80-item-line {
    display: table;
    width: 100%;
    table-layout: fixed;
    font-size: 10.5px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    clear: both;
}
.t80 .t80-item-line > span {
    display: table-cell;
    vertical-align: top;
    color: #000 !important;
}
.t80 .t80-item-line > span:first-child {
    text-align: left;
    padding-right: 4px;
}
.t80 .t80-item-line .amt {
    text-align: right;
    font-weight: 800;
    white-space: nowrap;
    width: 28%;
}
.t80 .t80-item-imei {
    font-size: 9.5px;
    font-weight: 700;
    color: #000 !important;
    margin: 0 0 3px;
    word-break: break-all;
}
.t80 .t80-item-wty {
    font-size: 9px;
    font-weight: 600;
    color: #000 !important;
    margin: 0 0 3px;
}
.t80 .t80-totals {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    font-size: 11px;
}
.t80 .t80-totals td {
    padding: 3px 0;
    color: #000 !important;
    font-weight: 700;
}
.t80 .t80-totals .lab { text-align: left; color: #000 !important; }
.t80 .t80-totals .val { text-align: right; font-weight: 800; white-space: nowrap; color: #000 !important; }
.t80 .t80-totals tr.grand td {
    padding-top: 6px;
    border-top: 2px solid #000;
    font-size: 13px;
    font-weight: 800;
    color: #000 !important;
}
.t80 .t80-pay {
    margin-top: 8px;
    border: 2px solid #000;
    border-radius: 0;
    overflow: visible;
    font-size: 10.5px;
    color: #000 !important;
    background: #fff !important;
}
.t80 .t80-pay-head {
    display: block;
    padding: 5px 6px;
    background: #fff !important;
    color: #000 !important;
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    border-bottom: 1.5px solid #000;
}
.t80 .t80-pay-row {
    display: table;
    width: 100%;
    table-layout: fixed;
    padding: 6px;
    border-top: 1px dashed #000;
}
.t80 .t80-pay-row:first-of-type {
    border-top: 0;
}
.t80 .t80-pay-row.is-change {
    background: #fff !important;
}
.t80 .t80-pay-row > div {
    display: table-cell;
    vertical-align: top;
}
.t80 .t80-pay-method {
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
    font-size: 10px;
    color: #000 !important;
}
.t80 .t80-pay-row.is-change .t80-pay-method {
    color: #000 !important;
}
.t80 .t80-pay-meta {
    display: block;
    margin-top: 2px;
    font-size: 9px;
    font-weight: 600;
    color: #000 !important;
    letter-spacing: 0;
    text-transform: none;
}
.t80 .t80-pay-amt {
    text-align: right;
    font-weight: 800;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    color: #000 !important;
    width: 34%;
}
.t80 .t80-pay-row.is-change .t80-pay-amt {
    color: #000 !important;
}
.t80 .t80-save {
    margin: 8px 0 4px;
    padding: 8px 6px;
    border: 2px solid #000;
    border-radius: 0;
    text-align: center;
    background: #fff !important;
}
.t80 .t80-save .lab {
    display: block;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #000 !important;
}
.t80 .t80-save .amt {
    display: block;
    margin-top: 1px;
    font-size: 15px;
    font-weight: 800;
    color: #000 !important;
}
.t80 .t80-foot {
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px solid #000;
    font-size: 9.5px;
    font-weight: 600;
    color: #000 !important;
    text-align: center;
    line-height: 1.35;
}
.t80 .t80-thanks {
    margin-top: 6px;
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    color: #000 !important;
}
.t80 .t80-bc {
    text-align: center;
    margin-top: 6px;
}
.t80 .t80-bc img {
    max-width: 68mm;
    height: auto;
}
.clear { clear: both; }
@media print {
    .t80, .t80 * {
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .t80 .t80-bar,
    .t80 .t80-pay,
    .t80 .t80-pay-head,
    .t80 .t80-party,
    .t80 .t80-save {
        background: #fff !important;
    }
}
</style>

<div class="t80" data-paper-width="80">
    <div class="t80-head">
        @if(!empty($receipt_details->letter_head))
            <img src="{{ $receipt_details->letter_head }}" class="t80-letterhead" alt="">
        @elseif(!empty($receipt_details->logo))
            <img src="{{ $receipt_details->logo }}" class="t80-logo" alt="">
        @endif
        @if(!empty($receipt_details->display_name))
            <div class="t80-biz">{{ $receipt_details->display_name }}</div>
        @endif
        @if(!empty($receipt_details->header_text))
            <div class="t80-tag">{!! strip_tags($receipt_details->header_text) !!}</div>
        @endif
        <div class="t80-meta">
            @if(!empty($receipt_details->address))
                {!! $receipt_details->address !!}<br>
            @endif
            @if(!empty($receipt_details->contact))
                {!! $receipt_details->contact !!}<br>
            @endif
            @if(!empty($receipt_details->sub_heading_line1))
                {{ $receipt_details->sub_heading_line1 }}<br>
            @endif
            @if(!empty($receipt_details->sub_heading_line2))
                {{ $receipt_details->sub_heading_line2 }}
            @endif
        </div>
    </div>

    <div class="t80-doc">
        @if(!empty($receipt_details->invoice_heading))
            <div class="t80-doc-title">{!! strip_tags($receipt_details->invoice_heading) !!}</div>
        @endif
        <div class="t80-row">
            <span class="l">{!! $receipt_details->invoice_no_prefix !!}</span>
            <span class="r">{{ $receipt_details->invoice_no }}</span>
        </div>
        <div class="t80-row">
            <span class="l">{{ $receipt_details->date_label ?? 'Date' }}</span>
            <span class="r">{{ $receipt_details->invoice_date }}</span>
        </div>
        @if(!empty($receipt_details->due_date_label))
            <div class="t80-row">
                <span class="l">{{ $receipt_details->due_date_label }}</span>
                <span class="r">{{ $receipt_details->due_date }}</span>
            </div>
        @endif
        @if(!empty($receipt_details->sales_person))
            <div class="t80-row">
                <span class="l">{{ $receipt_details->sales_person_label ?? 'Cashier' }}</span>
                <span class="r">{{ $receipt_details->sales_person }}</span>
            </div>
        @endif
    </div>

    @if(!empty($receipt_details->customer_info) || !empty($receipt_details->customer_label))
        <div class="t80-party">
            <span class="t80-party-label">{{ $receipt_details->customer_label ?? 'Bill To' }}</span>
            <div class="t80-party-body">
                @if(!empty($receipt_details->customer_info))
                    {!! $receipt_details->customer_info !!}
                @else
                    —
                @endif
            </div>
        </div>
    @endif

    <div class="t80-bar">
        Items
        <span>Amount</span>
        <div class="clear"></div>
    </div>

    @forelse($receipt_details->lines as $line)
        <div class="t80-item">
            <div class="t80-item-name">
                {{ $loop->iteration }}. {{ $line['name'] }}
                @if(!empty($line['product_variation']) && $line['product_variation'] != 'DUMMY')
                    {{ $line['product_variation'] }}
                @endif
                @if(!empty($line['variation']) && $line['variation'] != 'DUMMY')
                    {{ $line['variation'] }}
                @endif
            </div>
            @php
                $t80_sub_parts = [];
                if (!empty($line['is_repair_line']) && !empty($line['sell_line_note'])) {
                    $repair_note_lines = preg_split("/\r\n|\n|\r/", strip_tags($line['sell_line_note']));
                } else {
                    $repair_note_lines = [];
                    if (!empty($line['sub_sku'])) {
                        $t80_sub_parts[] = $line['sub_sku'];
                    }
                    if (!empty($line['product_description'])) {
                        $desc = trim(strip_tags($line['product_description']));
                        if ($desc !== '') {
                            $t80_sub_parts[] = $desc;
                        }
                    }
                }
            @endphp
            @if(!empty($line['is_repair_line']) && !empty($repair_note_lines))
                <div class="t80-item-sub">
                    @if(!empty($repair_note_lines[0]))
                        <span class="t80-item-repair-issue">{{ $repair_note_lines[0] }}</span>
                    @endif
                    @if(!empty($repair_note_lines[1]))
                        <span class="t80-item-repair-meta">{{ $repair_note_lines[1] }}</span>
                    @endif
                </div>
            @elseif(!empty($t80_sub_parts))
                <div class="t80-item-sub">{{ implode(' · ', $t80_sub_parts) }}</div>
            @endif
            @if(!empty($line['sell_line_note']) && empty($line['is_repair_line']))
            <div class="t80-item-imei">IMEI: {!! strip_tags(str_replace(["\r\n", "\n", "\r"], ', ', $line['sell_line_note'])) !!}</div>
            @endif
            @if(!empty($line['warranty_name']))
            <div class="t80-item-wty">
                Warranty: {{ $line['warranty_name'] }}
                @if(!empty($line['warranty_exp_date']))
                    · Exp {{ @format_date($line['warranty_exp_date']) }}
                @endif
            </div>
            @endif
            <div class="t80-item-line">
                <span>
                    {{ $line['quantity'] }}@if(!empty($line['units'])) {{ $line['units'] }}@endif
                    × {!! vspos_rcpt_money($line['unit_price_before_discount']) !!}
                </span>
                <span class="amt">{!! vspos_rcpt_money($line['line_total']) !!}</span>
            </div>
        </div>
    @empty
        <div class="t80-item" style="text-align:center;">—</div>
    @endforelse

    <table class="t80-totals">
        @if(!empty($receipt_details->subtotal_label))
        <tr>
            <td class="lab">{{ $receipt_details->subtotal_label }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->subtotal) !!}</td>
        </tr>
        @endif
        @if(!empty($receipt_details->discount) && (float) preg_replace('/[^0-9.\-]/', '', strip_tags((string) $receipt_details->discount)) != 0)
        <tr>
            <td class="lab">{{ strip_tags($receipt_details->discount_label ?? 'Discount') }}</td>
            <td class="val">(-) {!! vspos_rcpt_money($receipt_details->discount) !!}</td>
        </tr>
        @endif
        @if(!empty($receipt_details->tax))
        <tr>
            <td class="lab">{{ strip_tags($receipt_details->tax_label ?? 'Tax') }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->tax) !!}</td>
        </tr>
        @endif
        <tr class="grand">
            <td class="lab">{{ strip_tags($receipt_details->total_label ?? 'Total') }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->total) !!}</td>
        </tr>
        @if(!empty($receipt_details->total_paid) && !$t80_has_payments)
        <tr>
            <td class="lab">{{ $receipt_details->total_paid_label ?? $receipt_details->paid_label ?? 'Paid' }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->total_paid) !!}</td>
        </tr>
        @endif
        @if(!empty($receipt_details->total_due))
        <tr>
            <td class="lab">{{ $receipt_details->total_due_label ?? 'Due' }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->total_due) !!}</td>
        </tr>
        @endif
        @if(!empty($receipt_details->total_previous_due))
        <tr>
            <td class="lab">{{ $receipt_details->total_previous_due_label ?? 'Previous Due' }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->total_previous_due) !!}</td>
        </tr>
        @endif
        @if(!empty($receipt_details->all_due))
        <tr>
            <td class="lab">{{ $receipt_details->all_bal_label ?? 'Balance' }}</td>
            <td class="val">{!! vspos_rcpt_money($receipt_details->all_due) !!}</td>
        </tr>
        @endif
    </table>

    @if(!empty($receipt_details->show_mrp_savings) && !empty($receipt_details->mrp_savings))
        <div class="t80-save">
            <span class="lab">{{ $receipt_details->mrp_savings_label ?? __('lang_v1.your_discount_is') }}</span>
            <span class="amt">{!! vspos_rcpt_money($receipt_details->mrp_savings) !!}</span>
        </div>
    @endif

    @if($t80_has_payments)
        <div class="t80-pay">
            <span class="t80-pay-head">@lang('sale.payments')</span>
            @foreach($receipt_details->payments as $payment)
                <div class="t80-pay-row {{ !empty($payment['is_return']) ? 'is-change' : '' }}">
                    <div>
                        <span class="t80-pay-method">
                            {{ $payment['method'] }}
                            @if(!empty($payment['is_return'])) (-)@endif
                        </span>
                        @if(!empty($payment['date']))
                            <span class="t80-pay-meta">{{ $payment['date'] }}</span>
                        @endif
                    </div>
                    <div class="t80-pay-amt">{!! vspos_rcpt_money($payment['amount']) !!}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($receipt_details->footer_text))
        <div class="t80-foot">{!! $receipt_details->footer_text !!}</div>
    @endif

    <div class="t80-thanks">Thank you</div>
</div>
