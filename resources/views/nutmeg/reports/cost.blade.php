@extends('layouts.app')
@section('title', __('lang_v1.nutmeg_cost_report'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.nutmeg_cost_report')
        <small>@lang('lang_v1.nutmeg_cost_report_help')</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'id' => 'location_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_id', __('sale.product') . ':') !!}
                        {!! Form::select('product_id', $products, null, ['class' => 'form-control select2', 'id' => 'product_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'date_range', 'readonly']) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.expense_total')</p>
                <h3 style="margin:4px 0 0;" id="sum_expense"><i class="fas fa-sync fa-spin fa-fw"></i></h3>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.wastage') / @lang('lang_v1.wastage_pct')</p>
                <h3 style="margin:4px 0 0;">
                    <span id="sum_wastage"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                    <small class="text-muted" id="sum_wastage_pct"></small>
                </h3>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.wastage_value')</p>
                <h3 style="margin:4px 0 0;" id="sum_wastage_value"><i class="fas fa-sync fa-spin fa-fw"></i></h3>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.drying_entries')</p>
                <h3 style="margin:4px 0 0;" id="sum_count"><i class="fas fa-sync fa-spin fa-fw"></i></h3>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.avg_cost_breakdown')])
        <p class="help-block" style="margin-top:0;">@lang('lang_v1.avg_cost_breakdown_help')</p>
        <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
            <table class="table table-bordered table-striped" id="avg_cost_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('sale.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('report.current_stock')</th>
                        <th>@lang('lang_v1.buy_in_avg')</th>
                        <th>@lang('lang_v1.expense_per_unit')</th>
                        <th>@lang('lang_v1.avg_cost')</th>
                        <th>@lang('lang_v1.expense_total')</th>
                        <th>@lang('lang_v1.wastage')</th>
                        <th>@lang('lang_v1.wastage_value')</th>
                        <th>@lang('lang_v1.avg_cost') (@lang('lang_v1.before_drying'))</th>
                        <th>@lang('lang_v1.avg_cost') (@lang('lang_v1.after_drying'))</th>
                        <th>@lang('lang_v1.stock_value')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_drying" data-toggle="tab">@lang('lang_v1.nutmeg_drying') / @lang('lang_v1.wastage')</a></li>
            <li><a href="#tab_expenses" data-toggle="tab">@lang('lang_v1.product_expenses')</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_drying">
                <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
                    <table class="table table-bordered table-striped" id="drying_detail_table" style="width:100%">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.product')</th>
                                <th>@lang('business.location')</th>
                                <th>@lang('lang_v1.before_drying')</th>
                                <th>@lang('lang_v1.after_drying')</th>
                                <th>@lang('lang_v1.wastage')</th>
                                <th>@lang('lang_v1.avg_cost') (@lang('lang_v1.before_drying'))</th>
                                <th>@lang('lang_v1.avg_cost') (@lang('lang_v1.after_drying'))</th>
                                <th>@lang('lang_v1.wastage_value')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="tab-pane" id="tab_expenses">
                <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
                    <table class="table table-bordered table-striped" id="expense_detail_table" style="width:100%">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('purchase.ref_no')</th>
                                <th>@lang('sale.product')</th>
                                <th>@lang('purchase.supplier')</th>
                                <th>@lang('business.location')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('lang_v1.buy_unit_cost')</th>
                                <th>@lang('lang_v1.expense_per_unit')</th>
                                <th>@lang('lang_v1.landed_unit_cost')</th>
                                <th>@lang('lang_v1.expense_total')</th>
                                <th>@lang('lang_v1.product_expenses')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    dateRangeSettings.startDate = moment().subtract(30, 'days');
    dateRangeSettings.endDate = moment();
    $('#date_range').daterangepicker(dateRangeSettings, function (start, end) {
        $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        reloadAll();
    });
    $('#date_range').on('cancel.daterangepicker', function () {
        $(this).val('');
        reloadAll();
    });
    $('#date_range').val(
        dateRangeSettings.startDate.format(moment_date_format) + ' ~ ' + dateRangeSettings.endDate.format(moment_date_format)
    );

    $('.select2').select2({ width: '100%' });

    function filterData(d) {
        d.location_id = $('#location_id').val();
        d.product_id = $('#product_id').val();
        var range = $('#date_range').val();
        if (range) {
            var parts = range.split(' ~ ');
            d.start_date = moment(parts[0], moment_date_format).format('YYYY-MM-DD');
            d.end_date = moment(parts[1], moment_date_format).format('YYYY-MM-DD');
        }
    }

    var avgTable = $('#avg_cost_table').DataTable({
        processing: true,
        serverSide: false,
        scrollX: true,
        autoWidth: false,
        ajax: { url: '{{ url("/nutmeg/reports/avg-cost") }}', data: filterData },
        columns: [
            { data: 'product_label', name: 'product_name' },
            { data: 'location_name', name: 'location_name' },
            { data: 'stock', name: 'stock' },
            { data: 'buy_in_avg', name: 'buy_in_avg' },
            { data: 'expense_per_unit', name: 'expense_per_unit' },
            { data: 'avg_cost', name: 'avg_cost' },
            { data: 'expense_total', name: 'expense_total' },
            { data: 'qty_wastage', name: 'qty_wastage' },
            { data: 'wastage_value', name: 'wastage_value' },
            { data: 'avg_cost_before', name: 'avg_cost_before' },
            { data: 'avg_cost_after', name: 'avg_cost_after' },
            { data: 'stock_value', name: 'stock_value' }
        ],
        buttons: [
            { extend: 'csv', text: '<i class="fa fa-file-csv"></i> CSV', className: 'btn btn-default btn-sm' },
            { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-default btn-sm' },
            { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm' }
        ],
        dom: '<"row margin-bottom-12"<"col-sm-6"B><"col-sm-6"f>>rtip',
        order: [[0, 'asc']],
        fnDrawCallback: function () { __currency_convert_recursively($('#avg_cost_table')); }
    });

    var dryingTable = $('#drying_detail_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: { url: '{{ url("/nutmeg/reports/drying/data") }}', data: filterData },
        columns: [
            { data: 'created_at', name: 'nutmeg_dryings.created_at' },
            { data: 'product_label', name: 'p.name' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'qty_before', name: 'qty_before' },
            { data: 'qty_after', name: 'qty_after' },
            { data: 'qty_wastage', name: 'qty_wastage' },
            { data: 'unit_cost_before', name: 'unit_cost_before' },
            { data: 'unit_cost_after', name: 'unit_cost_after' },
            { data: 'wastage_value', name: 'wastage_value', orderable: false, searchable: false }
        ],
        buttons: [
            { extend: 'csv', text: '<i class="fa fa-file-csv"></i> CSV', className: 'btn btn-default btn-sm' },
            { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-default btn-sm' },
            { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm' }
        ],
        dom: '<"row margin-bottom-12"<"col-sm-6"B><"col-sm-6"f>>rtip',
        fnDrawCallback: function () { __currency_convert_recursively($('#drying_detail_table')); }
    });

    var expenseTable = $('#expense_detail_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: { url: '{{ url("/nutmeg/reports/expenses/data") }}', data: filterData },
        columns: [
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'ref_no', name: 't.ref_no' },
            { data: 'product_label', name: 'p.name' },
            { data: 'supplier', name: 'contacts.name' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'quantity', name: 'quantity' },
            { data: 'buy_price', name: 'buy_price', orderable: false, searchable: false },
            { data: 'expense_per_unit', name: 'expense_per_unit', orderable: false, searchable: false },
            { data: 'landed_unit', name: 'purchase_price_inc_tax' },
            { data: 'expense_total', name: 'expense_total', orderable: false, searchable: false },
            { data: 'breakdown', name: 'breakdown', orderable: false, searchable: false }
        ],
        buttons: [
            { extend: 'csv', text: '<i class="fa fa-file-csv"></i> CSV', className: 'btn btn-default btn-sm' },
            { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-default btn-sm' },
            { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm' }
        ],
        dom: '<"row margin-bottom-12"<"col-sm-6"B><"col-sm-6"f>>rtip',
        fnDrawCallback: function () { __currency_convert_recursively($('#expense_detail_table')); }
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    function loadSummary() {
        var data = {};
        filterData(data);
        $.get('{{ url("/nutmeg/reports/summary") }}', data, function (res) {
            $('#sum_count').text(res.drying_count);
            $('#sum_expense').html('<span class="display_currency" data-currency_symbol="true">' + res.total_expense + '</span>');
            $('#sum_wastage').html('<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">' + res.total_wastage + '</span>');
            $('#sum_wastage_pct').text('(' + res.wastage_pct + '%)');
            $('#sum_wastage_value').html('<span class="display_currency" data-currency_symbol="true">' + res.total_wastage_value + '</span>');
            __currency_convert_recursively($('.content'));
        });
    }

    function reloadAll() {
        avgTable.ajax.reload(function () { avgTable.columns.adjust(); });
        dryingTable.ajax.reload(function () { dryingTable.columns.adjust(); });
        expenseTable.ajax.reload(function () { expenseTable.columns.adjust(); });
        loadSummary();
    }

    $('#location_id, #product_id').on('change', reloadAll);
    loadSummary();
    setTimeout(function () {
        avgTable.columns.adjust();
        dryingTable.columns.adjust();
        expenseTable.columns.adjust();
    }, 200);
});
</script>
@endsection
