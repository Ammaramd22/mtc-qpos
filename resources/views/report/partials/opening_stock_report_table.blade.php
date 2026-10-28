@php
    $is_avco = !empty($data['accounting_method']) && $data['accounting_method'] === 'avco';
@endphp
<table class="table table-striped">
    <thead>
        <tr>
            <th colspan="2"
                style="background-color:#f4f4f4; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#555; border-bottom:2px solid #ddd;">
                <i class="fa fa-arrow-circle-down text-red"></i>&nbsp; @lang('lang_v1.costs_and_deductions')
            </th>
        </tr>
    </thead>

    @if($is_avco)
    {{-- Opening Stock by Average Cost only (AVCO selected) --}}
    <tr>
        <th>
            {{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_average_cost'))</small>:
            <br><small class="text-muted">@lang('lang_v1.opening_stock_at_period_start_help')</small>
        </th>
        <td><span class="display_currency" data-currency_symbol="true">{{ $data['opening_stock_average'] ?? $data['opening_stock'] }}</span></td>
    </tr>
    @else
    {{-- Opening Stock (purchase lot / FIFO-LIFO) --}}
    <tr>
        <th>
            {{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_purchase_lot'))</small>:
            <br><small class="text-muted">@lang('lang_v1.opening_stock_at_period_start_help')</small>
        </th>
        <td><span class="display_currency" data-currency_symbol="true">{{ $data['opening_stock_lot'] ?? $data['opening_stock'] }}</span></td>
    </tr>

    {{-- Opening Stock by Average Cost --}}
    <tr>
        <th>{{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_average_cost'))</small>:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{ $data['opening_stock_average'] ?? $data['opening_stock'] }}</span></td>
    </tr>

    {{-- Opening Stock by Sale Price --}}
    <tr>
        <th>{{ __('report.opening_stock') }} <br><small class="text-muted">(@lang('lang_v1.by_sale_price'))</small>:</th>
        <td>
            @if(isset($stocks['opening_stock_by_sp']))
                <span class="display_currency" data-currency_symbol="true">{{ $stocks['opening_stock_by_sp'] }}</span>
            @else
                <span id="opening_stock_by_sp"><i class="fa fa-sync fa-spin fa-fw"></i></span>
            @endif
        </td>
    </tr>
    @endif

    {{-- Opening stock entries added during the selected period --}}
    <tr style="background-color:#fff8e1;">
        <th style="font-size:14px;">
            @lang('lang_v1.total_opening_stock_added'):
            <br><small class="text-muted">@lang('lang_v1.total_opening_stock_added_help')</small>
        </th>
        <td style="font-size:14px; font-weight:700;">
            <span class="display_currency" data-currency_symbol="true">{{ $data['total_opening_stock_added'] ?? 0 }}</span>
        </td>
    </tr>

    {{-- Total Purchase --}}
    <tr style="background-color:#fff8e1;">
        <th style="font-size:14px;">{{ __('home.total_purchase') }}:<br><small class="text-muted">(@lang('product.exc_of_tax'), @lang('sale.discount'))</small></th>
        <td style="font-size:14px; font-weight:700;"><span class="display_currency" data-currency_symbol="true">{{$data['total_purchase']}}</span></td>
    </tr>

    {{-- Stock Adjustment --}}
    <tr>
        <th>{{ __('report.total_stock_adjustment') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_adjustment']}}</span></td>
    </tr>

    {{-- Total Expense --}}
    <tr>
        <th>
            {{ __('report.total_expense') }}:
            @if(!empty($data['expenses_by_category']) && count($data['expenses_by_category']) > 0)
                <a href="#" class="btn-link" style="font-size:11px; margin-left:5px;"
                   onclick="event.preventDefault(); $('.expense-category-row').toggle();">
                    <i class="fa fa-eye"></i> @lang('lang_v1.details')
                </a>
            @endif
        </th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_expense']}}</span></td>
    </tr>
    @if(!empty($data['expenses_by_category']) && count($data['expenses_by_category']) > 0)
        @foreach($data['expenses_by_category'] as $expense_cat)
        <tr class="expense-category-row" style="display:none; background-color:#fafafa;">
            <th style="padding-left:30px; font-weight:normal; font-size:12px;">
                <i class="fa fa-caret-right text-muted"></i> {{ $expense_cat->category_name }}
            </th>
            <td style="font-size:12px;"><span class="display_currency" data-currency_symbol="true">{{ $expense_cat->category_total }}</span></td>
        </tr>
        @endforeach
    @endif

    {{-- Purchase Shipping --}}
    <tr>
        <th>{{ __('lang_v1.total_purchase_shipping_charge') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_purchase_shipping_charge']}}</span></td>
    </tr>

    {{-- Purchase Additional Expense --}}
    <tr>
        <th>{{ __('lang_v1.purchase_additional_expense') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_purchase_additional_expense']}}</span></td>
    </tr>

    {{-- Transfer Shipping --}}
    <tr>
        <th>{{ __('lang_v1.total_transfer_shipping_charge') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_transfer_shipping_charges']}}</span></td>
    </tr>

    {{-- Sell Discount --}}
    <tr>
        <th>{{ __('lang_v1.total_sell_discount') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_sell_discount']}}</span></td>
    </tr>

    {{-- Reward Amount --}}
    <tr>
        <th>{{ __('lang_v1.total_reward_amount') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_reward_amount']}}</span></td>
    </tr>

    {{-- Sell Return --}}
    <tr>
        <th>{{ __('lang_v1.total_sell_return') }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{$data['total_sell_return']}}</span></td>
    </tr>

    {{-- Left-side module data (Payroll, Production Cost, etc.) --}}
    @foreach($data['left_side_module_data'] as $key => $module_data)
    <tr>
        <th>{{ $module_data['label'] }}:</th>
        <td><span class="display_currency" data-currency_symbol="true">{{ $module_data['value'] }}</span></td>
    </tr>
    @endforeach
</table>
