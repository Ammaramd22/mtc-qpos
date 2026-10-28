<div style="border-top:3px solid #ddd; padding-top:12px;">

    @php
        $is_avco = !empty($data['accounting_method']) && $data['accounting_method'] === 'avco';
        $opening_stock_added = $data['total_opening_stock_added'] ?? 0;
        $cogs_primary = ($data['opening_stock'] + $data['total_purchase'] + $opening_stock_added) - $data['closing_stock'];
        $cogs_lot = (($data['opening_stock_lot'] ?? $data['opening_stock']) + $data['total_purchase'] + $opening_stock_added) - ($data['closing_stock_lot'] ?? $data['closing_stock']);
        $cogs_avg = (($data['opening_stock_average'] ?? $data['opening_stock']) + $data['total_purchase'] + $opening_stock_added) - ($data['closing_stock_average'] ?? $data['closing_stock']);
        $method_label = $is_avco
            ? __('business.avco')
            : ($data['accounting_method'] === 'lifo' ? __('business.lifo') : __('business.fifo'));
    @endphp

    <p class="text-muted" style="margin:0 0 10px 0;">
        <strong>@lang('lang_v1.primary_accounting_method'):</strong> {{ $method_label }}
    </p>

    {{-- COGS --}}
    <div style="margin-bottom:10px;">
        <h4 class="text-muted" style="margin:0; font-weight:600;">
            @lang('lang_v1.cogs')
            <span class="display_currency" data-currency_symbol="true">{{ $cogs_primary }}</span>
        </h4>
        <small class="help-block" style="margin:2px 0 0 0;">@lang('lang_v1.cogs_help_text')</small>
    </div>

    {{-- Gross Profit --}}
    <div style="border-top:1px solid #eee; padding-top:10px; margin-bottom:10px;">
        <h3 class="{{ $data['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}" style="margin:0; font-weight:700;">
            {{ __('lang_v1.gross_profit') }}:
            <span class="display_currency" data-currency_symbol="true">{{$data['gross_profit']}}</span>
            @if(!empty($data['total_sell']) && $data['total_sell'] != 0)
                <small>({{ number_format(($data['gross_profit'] / $data['total_sell']) * 100, 2) }}%)</small>
            @endif
        </h3>
        <small class="help-block" style="margin:2px 0 0 0;">
            (@lang('lang_v1.total_sell_price') - @lang('lang_v1.total_purchase_price'))
            @foreach ($data['gross_profit_label'] as $val)
                + {{$val}}
            @endforeach
        </small>
    </div>

    {{-- Net Profit --}}
    <div style="border-top:1px solid #eee; padding-top:10px;">
        <h2 class="{{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}" style="margin:0; font-weight:700;">
            {{ __('report.net_profit') }}:
            <span class="display_currency" data-currency_symbol="true">{{$data['net_profit']}}</span>
            @if(!empty($data['total_sell']) && $data['total_sell'] != 0)
                <small style="font-size:60%;">({{ number_format(($data['net_profit'] / $data['total_sell']) * 100, 2) }}%)</small>
            @endif
        </h2>
        <small class="help-block" style="margin:2px 0 0 0;">
            {{-- total_sell_return_discount: discount recovered when customer returns a discounted sale --}}
            @lang('lang_v1.gross_profit') + (@lang('lang_v1.total_sell_shipping_charge') + @lang('lang_v1.sell_additional_expense') + @lang('report.total_stock_recovered') + @lang('lang_v1.total_purchase_discount') + @lang('lang_v1.total_sell_round_off') + @lang('lang_v1.total_sell_return_discount')
            @foreach($data['right_side_module_data'] as $module_data)
                @if(!empty($module_data['add_to_net_profit']))
                    + {{$module_data['label']}}
                @endif
            @endforeach
            ) <br> - (@lang('report.total_stock_adjustment') + @lang('report.total_expense') + @lang('lang_v1.total_purchase_shipping_charge') + @lang('lang_v1.total_transfer_shipping_charge') + @lang('lang_v1.purchase_additional_expense') + @lang('lang_v1.total_sell_discount') + @lang('lang_v1.total_reward_amount')
            @foreach($data['left_side_module_data'] as $module_data)
                @if(!empty($module_data['add_to_net_profit']))
                    + {{$module_data['label']}}
                @endif
            @endforeach
            )
        </small>
    </div>

    {{-- Side-by-side comparison only when AVCO is not the sole selected method --}}
    @if(!$is_avco && isset($data['gross_profit_average']) && isset($data['gross_profit_lot']))
    <div style="border-top:2px solid #ddd; padding-top:12px; margin-top:14px;">
        <h4 style="margin:0 0 10px 0; font-weight:700;">
            <i class="fa fa-balance-scale"></i>&nbsp; @lang('lang_v1.profit_loss_comparison')
        </h4>
        <div class="row">
            <div class="col-md-6" style="margin-bottom:12px;">
                <div style="border:1px solid #e5e5e5; border-radius:8px; padding:12px; {{ $is_avco ? 'background:#f8fff4; border-color:#b7e1a8;' : 'background:#fafafa;' }}">
                    <strong>@lang('lang_v1.profit_loss_by_average_cost')</strong>
                    @if($is_avco)
                        <span class="label label-success" style="margin-left:6px;">@lang('lang_v1.primary_accounting_method')</span>
                    @endif
                    <table class="table table-condensed" style="margin:10px 0 0 0;">
                        <tr>
                            <th>@lang('lang_v1.cogs_average')</th>
                            <td><span class="display_currency" data-currency_symbol="true">{{ $cogs_avg }}</span></td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.gross_profit')</th>
                            <td class="{{ ($data['gross_profit_average'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <span class="display_currency" data-currency_symbol="true">{{ $data['gross_profit_average'] }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('report.net_profit')</th>
                            <td class="{{ ($data['net_profit_average'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong><span class="display_currency" data-currency_symbol="true">{{ $data['net_profit_average'] }}</span></strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6" style="margin-bottom:12px;">
                <div style="border:1px solid #e5e5e5; border-radius:8px; padding:12px; {{ !$is_avco ? 'background:#f8fff4; border-color:#b7e1a8;' : 'background:#fafafa;' }}">
                    <strong>@lang('lang_v1.profit_loss_by_lot_cost')</strong>
                    @if(!$is_avco)
                        <span class="label label-success" style="margin-left:6px;">@lang('lang_v1.primary_accounting_method')</span>
                    @endif
                    <table class="table table-condensed" style="margin:10px 0 0 0;">
                        <tr>
                            <th>@lang('lang_v1.cogs_lot')</th>
                            <td><span class="display_currency" data-currency_symbol="true">{{ $cogs_lot }}</span></td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.gross_profit')</th>
                            <td class="{{ ($data['gross_profit_lot'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <span class="display_currency" data-currency_symbol="true">{{ $data['gross_profit_lot'] }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('report.net_profit')</th>
                            <td class="{{ ($data['net_profit_lot'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong><span class="display_currency" data-currency_symbol="true">{{ $data['net_profit_lot'] }}</span></strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tax Summary: informational only, not part of P&L calculation (report uses exc. tax values) -->
    @if(!empty($data['total_sell_tax']) || !empty($data['total_purchase_tax']))
    <div style="border-top:2px solid #ddd; padding-top:10px; margin-top:12px;">
        <h4 class="text-muted" style="margin:0 0 8px 0; font-weight:600;">
            <i class="fa fa-calculator"></i>&nbsp; @lang('lang_v1.tax_summary')
        </h4>
        <table class="table table-condensed" style="margin-bottom:0; width:auto;">
            <tr>
                <th>@lang('lang_v1.tax_collected_on_sales'):</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_tax'] }}</span></td>
            </tr>
            <tr>
                <th>@lang('lang_v1.tax_paid_on_purchases'):</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_purchase_tax'] }}</span></td>
            </tr>
            <tr style="border-top:1px solid #ddd;">
                @php $net_tax = $data['total_sell_tax'] - $data['total_purchase_tax']; @endphp
                <th>@lang('lang_v1.net_tax_liability'):</th>
                <td><strong class="{{ $net_tax >= 0 ? 'text-danger' : 'text-success' }}">
                    <span class="display_currency" data-currency_symbol="true">{{ $net_tax }}</span>
                </strong></td>
            </tr>
        </table>
        <small class="help-block" style="margin:2px 0 0 0;">@lang('lang_v1.tax_summary_help')</small>
    </div>
    @endif

</div>
