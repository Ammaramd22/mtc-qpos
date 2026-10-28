{{-- 80mm thermal Profit / Loss receipt --}}
@php
    $is_avco = !empty($data['accounting_method']) && $data['accounting_method'] === 'avco';
    $opening_stock_added = $data['total_opening_stock_added'] ?? 0;
    $opening = $is_avco
        ? ($data['opening_stock_average'] ?? $data['opening_stock'] ?? 0)
        : ($data['opening_stock_lot'] ?? $data['opening_stock'] ?? 0);
    $closing = $is_avco
        ? ($data['closing_stock_average'] ?? $data['closing_stock'] ?? 0)
        : ($data['closing_stock_lot'] ?? $data['closing_stock'] ?? 0);
    $cogs = ($opening + ($data['total_purchase'] ?? 0) + $opening_stock_added) - $closing;
    $method_label = $is_avco
        ? __('business.avco')
        : (($data['accounting_method'] ?? '') === 'lifo' ? __('business.lifo') : __('business.fifo'));
    // Do not use Blade @format_date inside @php (@ becomes error-suppression)
    $date_format = session('business.date_format') ?: 'd-m-Y';
    $start_fmt = \Carbon\Carbon::parse($start_date)->format($date_format);
    $end_fmt = \Carbon\Carbon::parse($end_date)->format($date_format);
    $period_label = $start_date === $end_date ? $start_fmt : ($start_fmt.' - '.$end_fmt);
@endphp
<div id="pl_thermal_80mm" class="vspos-pl-receipt no-print" data-paper-width="80" style="display:none;">
    <div class="vspos-pl-receipt-inner" style="width:80mm;max-width:80mm;margin:0 auto;padding:3mm 2.5mm 4mm;font-family:'Courier New',Courier,monospace;font-size:11px;line-height:1.35;color:#111;background:#fff;box-sizing:border-box;">
        <div style="text-align:center;margin-bottom:6px;padding-bottom:6px;border-bottom:1px solid #111;">
            <p style="margin:0 0 2px;font-size:14px;font-weight:700;text-transform:uppercase;">{{ $business_name }}</p>
            @if(!empty($location_name))
                <p style="margin:0 0 1px;font-size:10px;color:#333;">{{ $location_name }}</p>
            @endif
            <p style="margin:4px 0 0;font-size:12px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">@lang('report.profit_loss')</p>
            <p style="margin:2px 0 0;font-size:10px;">{{ $period_label }}</p>
            <p style="margin:1px 0 0;font-size:9px;color:#555;">{{ $method_label }}</p>
        </div>

        <p style="margin:6px 0 3px;font-size:10px;font-weight:700;text-transform:uppercase;border-bottom:1px solid #111;padding-bottom:2px;">
            @lang('lang_v1.costs_and_deductions')
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:10.5px;">
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('report.opening_stock')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($opening)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_opening_stock_added')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($opening_stock_added)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('home.total_purchase')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_purchase'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('report.total_stock_adjustment')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_adjustment'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('report.total_expense')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_expense'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_purchase_shipping_charge')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_purchase_shipping_charge'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.purchase_additional_expense')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_purchase_additional_expense'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_transfer_shipping_charge')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_transfer_shipping_charges'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_sell_discount')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_discount'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_reward_amount')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_reward_amount'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_sell_return')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_return'] ?? 0)</td>
            </tr>
            @foreach(($data['left_side_module_data'] ?? []) as $module_data)
            <tr>
                <td style="padding:2px 0;vertical-align:top;">{{ $module_data['label'] }}</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($module_data['value'] ?? 0)</td>
            </tr>
            @endforeach
        </table>

        <p style="margin:8px 0 3px;font-size:10px;font-weight:700;text-transform:uppercase;border-bottom:1px solid #111;padding-bottom:2px;">
            @lang('lang_v1.revenue_and_income')
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:10.5px;">
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('report.closing_stock')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($closing)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('home.total_sell')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_sell_shipping_charge')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_shipping_charge'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.sell_additional_expense')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_additional_expense'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('report.total_stock_recovered')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_recovered'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_purchase_return')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_purchase_return'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_purchase_discount')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_purchase_discount'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_sell_round_off')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_round_off'] ?? 0)</td>
            </tr>
            <tr>
                <td style="padding:2px 0;vertical-align:top;">@lang('lang_v1.total_sell_return_discount')</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($data['total_sell_return_discount'] ?? 0)</td>
            </tr>
            @foreach(($data['right_side_module_data'] ?? []) as $module_data)
            <tr>
                <td style="padding:2px 0;vertical-align:top;">{{ $module_data['label'] }}</td>
                <td style="padding:2px 0;text-align:right;font-weight:600;white-space:nowrap;">@format_currency($module_data['value'] ?? 0)</td>
            </tr>
            @endforeach
        </table>

        <div style="margin-top:8px;padding-top:6px;border-top:1px solid #111;">
            <table style="width:100%;border-collapse:collapse;font-size:11px;">
                <tr>
                    <td style="padding:3px 0;font-weight:700;">@lang('lang_v1.cogs')</td>
                    <td style="padding:3px 0;text-align:right;font-weight:700;white-space:nowrap;">@format_currency($cogs)</td>
                </tr>
                <tr>
                    <td style="padding:3px 0;font-weight:700;">@lang('lang_v1.gross_profit')</td>
                    <td style="padding:3px 0;text-align:right;font-weight:700;white-space:nowrap;">@format_currency($data['gross_profit'] ?? 0)</td>
                </tr>
                <tr>
                    <td style="padding:4px 0 2px;font-size:13px;font-weight:900;border-top:1px dashed #111;">@lang('report.net_profit')</td>
                    <td style="padding:4px 0 2px;text-align:right;font-size:13px;font-weight:900;border-top:1px dashed #111;white-space:nowrap;">@format_currency($data['net_profit'] ?? 0)</td>
                </tr>
            </table>
        </div>

        <div style="text-align:center;margin-top:8px;padding-top:6px;border-top:1px dashed #555;font-size:9px;color:#555;">
            {{ config('app.brand_footer') }}
        </div>
    </div>
</div>
