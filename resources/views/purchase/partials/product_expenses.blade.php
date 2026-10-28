{{-- Nutmeg: per-line product expenses → stock / AVCO cost (not P&L) --}}
@php
    $pe_row = $pe_row ?? $row_count ?? 0;
    $pe_expenses = $pe_expenses ?? [];
    if (!is_array($pe_expenses)) {
        $pe_expenses = [];
    }
    if (count($pe_expenses) === 0) {
        $pe_expenses = [['name' => '', 'amount' => '']];
    }
@endphp
<div class="product-expenses-wrap tw-mt-1 tw-text-left">
    <button type="button" class="btn btn-xs btn-default toggle-product-expenses">
        <i class="fa fa-plus-circle"></i> @lang('lang_v1.product_expenses')
        <span class="product-expense-badge label label-warning hide">0</span>
    </button>
    <div class="product-expenses-panel" style="display:none; margin-top:6px; padding:8px; background:#fafafa; border:1px solid #e5e5e5; border-radius:4px;">
        <p class="help-block tw-m-0 tw-mb-2" style="font-size:11px;">
            <i>@lang('lang_v1.product_expenses_help')</i>
        </p>
        <table class="table table-condensed tw-mb-1 product-expenses-table" style="margin-bottom:4px !important;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.product_expense_name')</th>
                    <th style="width:110px;">@lang('lang_v1.product_expense_amount')</th>
                    <th style="width:28px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pe_expenses as $ei => $exp)
                    <tr class="product-expense-row">
                        <td>
                            <input type="text"
                                name="purchases[{{ $pe_row }}][product_expenses][{{ $ei }}][name]"
                                value="{{ $exp['name'] ?? '' }}"
                                class="form-control input-sm product-expense-name"
                                placeholder="Transport">
                        </td>
                        <td>
                            <input type="text"
                                name="purchases[{{ $pe_row }}][product_expenses][{{ $ei }}][amount]"
                                value="{{ isset($exp['amount']) && $exp['amount'] !== '' ? @num_format($exp['amount']) : '' }}"
                                class="form-control input-sm input_number product-expense-amount">
                        </td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger remove-product-expense" tabindex="-1">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" class="btn btn-xs btn-primary add-product-expense">
            <i class="fa fa-plus"></i> @lang('lang_v1.add_product_expense')
        </button>
        <div class="tw-mt-1" style="font-size:12px;">
            <span class="text-muted">@lang('lang_v1.expense_total'):</span>
            <strong class="product-expense-total-text">0</strong>
            &nbsp;|&nbsp;
            <span class="text-muted">@lang('lang_v1.landed_unit_cost'):</span>
            <strong class="product-landed-unit-cost-text">0</strong>
        </div>
    </div>
</div>
