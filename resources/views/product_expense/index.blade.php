@extends('layouts.app')
@section('title', __('lang_v1.product_expenses'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.product_expenses')
        <small>@lang('lang_v1.product_expenses_area_help')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="tw-dw-btn tw-dw-btn-primary tw-text-white"
                   href="{{ action([\App\Http\Controllers\ProductExpenseController::class, 'create']) }}">
                    <i class="fa fa-plus"></i> @lang('lang_v1.add_product_expense')
                </a>
            </div>
        @endslot

        <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
            <table class="table table-bordered table-striped" id="product_expenses_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('purchase.supplier')</th>
                        <th>@lang('purchase.business_location')</th>
                        <th>@lang('lang_v1.expense_total')</th>
                        <th>@lang('sale.total')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    $('#product_expenses_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action([\App\Http\Controllers\ProductExpenseController::class, "index"]) }}',
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transactions.transaction_date' },
            { data: 'ref_no', name: 'transactions.ref_no' },
            { data: 'supplier', name: 'contacts.name' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'expense_total', name: 'expense_total', orderable: false, searchable: false },
            { data: 'final_total', name: 'transactions.final_total' },
        ],
        fnDrawCallback: function () {
            __currency_convert_recursively($('#product_expenses_table'));
        },
    });
});
</script>
@endsection
