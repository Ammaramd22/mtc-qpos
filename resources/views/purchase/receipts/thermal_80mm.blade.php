{{-- 80mm thermal Purchase Invoice (Point of Purchase) --}}
<div class="vspos-pop-receipt" data-paper-width="80" style="width:80mm;max-width:80mm;margin:0 auto;padding:3mm 2.5mm 4mm;font-family:'Courier New',Courier,monospace;font-size:11px;line-height:1.35;color:#111;background:#fff;box-sizing:border-box;">
    <style>
        @page { size: 80mm auto; margin: 0; }
        .vspos-pop-receipt * { box-sizing: border-box; }
        .vspos-pop-receipt .pr-head { text-align: center; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #111; }
        .vspos-pop-receipt .pr-biz { margin: 0 0 2px; font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .vspos-pop-receipt .pr-sub { margin: 0 0 1px; font-size: 10px; color: #333; }
        .vspos-pop-receipt .pr-title { text-align: center; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin: 6px 0 8px; padding: 4px 0; border-top: 1px dashed #111; border-bottom: 1px dashed #111; }
        .vspos-pop-receipt .pr-row { display: flex; justify-content: space-between; gap: 6px; padding: 2px 0; font-size: 10.5px; border-bottom: 1px dotted #ccc; }
        .vspos-pop-receipt .pr-label { color: #444; flex-shrink: 0; }
        .vspos-pop-receipt .pr-value { text-align: right; font-weight: 600; word-break: break-word; }
        .vspos-pop-receipt .pr-items { margin: 6px 0; border-top: 1px solid #111; }
        .vspos-pop-receipt .pr-items-head {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            padding: 3px 0 2px;
            border-bottom: 1px solid #111;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .vspos-pop-receipt .pr-items-head .h-qty { width: 18%; text-align: left; }
        .vspos-pop-receipt .pr-items-head .h-rt { width: 32%; text-align: right; }
        .vspos-pop-receipt .pr-items-head .h-tot { width: 50%; text-align: right; }
        .vspos-pop-receipt .pr-item {
            padding: 4px 0 3px;
            border-bottom: 1px dotted #ddd;
        }
        .vspos-pop-receipt .pr-item-name {
            font-weight: 700;
            font-size: 11px;
            line-height: 1.25;
            word-break: break-word;
            white-space: normal;
            margin: 0;
        }
        .vspos-pop-receipt .pr-item-sku {
            font-size: 9px;
            color: #555;
            line-height: 1.2;
            margin: 1px 0 0;
        }
        .vspos-pop-receipt .pr-item-nums {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px;
            margin-top: 2px;
            font-size: 10px;
            line-height: 1.2;
        }
        .vspos-pop-receipt .pr-item-nums .n-qty { width: 18%; text-align: left; white-space: nowrap; }
        .vspos-pop-receipt .pr-item-nums .n-rt { width: 32%; text-align: right; white-space: nowrap; }
        .vspos-pop-receipt .pr-item-nums .n-tot { width: 50%; text-align: right; white-space: nowrap; font-weight: 700; }
        .vspos-pop-receipt .pr-amt { white-space: nowrap; display: inline; }
        .vspos-pop-receipt .pr-totals { margin-top: 4px; border-top: 1px solid #111; padding-top: 4px; }
        .vspos-pop-receipt .pr-total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10.5px; }
        .vspos-pop-receipt .pr-grand { font-size: 13px; font-weight: 700; margin-top: 4px; padding-top: 4px; border-top: 1px dashed #111; }
        .vspos-pop-receipt .pr-pay { margin-top: 6px; border-top: 1px dashed #999; padding-top: 4px; }
        .vspos-pop-receipt .pr-foot { text-align: center; margin-top: 8px; padding-top: 6px; border-top: 1px solid #111; font-size: 9px; color: #444; }
    </style>

    <div class="pr-head">
        <p class="pr-biz">{{ $business_name }}</p>
        @if(!empty($location))
            <p class="pr-sub">{{ $location->name }}</p>
            @if(!empty($location->landmark))
                <p class="pr-sub">{{ $location->landmark }}</p>
            @endif
            @if(!empty($location->city) || !empty($location->state))
                <p class="pr-sub">{{ implode(', ', array_filter([$location->city, $location->state, $location->country])) }}</p>
            @endif
            @if(!empty($location->mobile))
                <p class="pr-sub">{{ $location->mobile }}</p>
            @endif
        @endif
    </div>

    <div class="pr-title">@lang('lang_v1.purchase_invoice')</div>

    <div class="pr-row">
        <span class="pr-label">@lang('purchase.ref_no')</span>
        <span class="pr-value">{{ $purchase->ref_no }}</span>
    </div>
    <div class="pr-row">
        <span class="pr-label">@lang('messages.date')</span>
        <span class="pr-value">{{ @format_datetime($purchase->transaction_date) }}</span>
    </div>
    @if(!empty($contact_name))
        <div class="pr-row">
            <span class="pr-label">@lang('lang_v1.bought_from')</span>
            <span class="pr-value">{{ $contact_name }}</span>
        </div>
    @endif
    @if(!empty($purchase->payment_status))
        <div class="pr-row">
            <span class="pr-label">@lang('purchase.payment_status')</span>
            <span class="pr-value">{{ __('lang_v1.' . $purchase->payment_status) }}</span>
        </div>
    @endif

    <div class="pr-items">
        <div class="pr-items-head">
            <span class="h-qty">@lang('lang_v1.qty')</span>
            <span class="h-rt">@lang('lang_v1.rate')</span>
            <span class="h-tot">@lang('sale.total')</span>
        </div>
        @foreach($purchase->purchase_lines as $line)
            @php
                $name = $line->product->name_with_tyre_size ?? ($line->product->name ?? '');
                if (!empty($line->variations) && !empty($line->variations->name) && $line->variations->name != 'DUMMY') {
                    $name .= ' - ' . $line->variations->name;
                }
                $sku = $line->variations->sub_sku ?? ($line->product->sku ?? '');
                $qty = (float) $line->quantity;
                $rate = (float) $line->purchase_price_inc_tax;
                $line_total = $qty * $rate;
            @endphp
            <div class="pr-item">
                <div class="pr-item-name">{{ $name }}</div>
                @if(!empty($sku))
                    <div class="pr-item-sku">{{ $sku }}</div>
                @endif
                <div class="pr-item-nums">
                    <span class="n-qty">{{ @num_format($qty) }}</span>
                    <span class="n-rt">{{ @num_format($rate) }}</span>
                    <span class="n-tot"><span class="pr-amt">@format_currency($line_total)</span></span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pr-totals">
        <div class="pr-total-row">
            <span>@lang('sale.subtotal')</span>
            <span class="pr-amt">@format_currency($purchase->total_before_tax)</span>
        </div>
        @if((float) $purchase->discount_amount != 0)
            <div class="pr-total-row">
                <span>@lang('sale.discount')</span>
                <span class="pr-amt">- @format_currency($purchase->discount_amount)</span>
            </div>
        @endif
        @if((float) $purchase->tax_amount != 0)
            <div class="pr-total-row">
                <span>@lang('sale.tax')</span>
                <span class="pr-amt">@format_currency($purchase->tax_amount)</span>
            </div>
        @endif
        @if((float) $purchase->shipping_charges != 0)
            <div class="pr-total-row">
                <span>@lang('sale.shipping')</span>
                <span class="pr-amt">@format_currency($purchase->shipping_charges)</span>
            </div>
        @endif
        <div class="pr-total-row pr-grand">
            <span>@lang('sale.total')</span>
            <span class="pr-amt">@format_currency($purchase->final_total)</span>
        </div>
    </div>

    @if(!empty($payments) && count($payments))
        <div class="pr-pay">
            @foreach($payments as $pay)
                <div class="pr-total-row">
                    <span>{{ $pay['method_label'] }}</span>
                    <span class="pr-amt">@format_currency($pay['amount'])</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="pr-foot">
        @lang('lang_v1.purchase_invoice') · {{ $purchase->ref_no }}<br>
        {{ config('app.brand_footer', 'QPOS') }}
    </div>
</div>
