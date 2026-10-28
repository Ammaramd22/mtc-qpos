<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('lang_v1.product_expenses')
                — {{ $purchase->ref_no }}
            </h4>
        </div>
        <div class="modal-body">
            <div class="row tw-mb-3">
                <div class="col-sm-4">
                    <strong>@lang('messages.date'):</strong>
                    {{ @format_datetime($purchase->transaction_date) }}
                </div>
                <div class="col-sm-4">
                    <strong>@lang('purchase.supplier'):</strong>
                    {{ $purchase->contact->supplier_business_name ?? ($purchase->contact->name ?? '—') }}
                </div>
                <div class="col-sm-4">
                    <strong>@lang('purchase.business_location'):</strong>
                    {{ $purchase->location->name ?? '—' }}
                </div>
            </div>

            <div class="table-responsive pvs-table-scroll">
                <table class="table table-bordered table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('lang_v1.quantity')</th>
                            <th>@lang('lang_v1.buy_unit_cost')</th>
                            <th>@lang('lang_v1.product_expenses')</th>
                            <th>@lang('lang_v1.landed_unit_cost')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $line)
                            <tr>
                                <td>
                                    {{ $line['name'] }}
                                    <br><small class="text-muted">{{ $line['sku'] }}</small>
                                </td>
                                <td>{{ @format_quantity($line['quantity']) }}</td>
                                <td>
                                    <span class="display_currency" data-currency_symbol="true">{{ $line['buy_unit_cost'] }}</span>
                                </td>
                                <td>
                                    @if(!empty($line['product_expenses']))
                                        <ul class="list-unstyled tw-mb-0">
                                            @foreach($line['product_expenses'] as $exp)
                                                <li>
                                                    {{ $exp['name'] ?? '' }}:
                                                    <span class="display_currency" data-currency_symbol="true">{{ $exp['amount'] ?? 0 }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <small class="text-muted">
                                            @lang('lang_v1.expense_total'):
                                            <span class="display_currency" data-currency_symbol="true">{{ $line['expense_total'] }}</span>
                                        </small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="display_currency" data-currency_symbol="true">{{ $line['landed_unit_cost'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">@lang('lang_v1.no_data')</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">@lang('lang_v1.expense_total')</th>
                            <th colspan="2">
                                <span class="display_currency" data-currency_symbol="true">{{ $expense_grand }}</span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="help-block"><i>@lang('lang_v1.product_expenses_help')</i></p>
        </div>
        <div class="modal-footer">
            <a href="{{ action([\App\Http\Controllers\ProductExpenseController::class, 'create'], ['purchase_id' => $purchase->id]) }}"
               class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.edit')
            </a>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
<script>
    __currency_convert_recursively($('.view_modal'));
</script>
