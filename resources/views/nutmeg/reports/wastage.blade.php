@extends('layouts.app')
@section('title', __('lang_v1.wastage_report'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.wastage_report')
        <small>@lang('lang_v1.wastage_report_help')</small>
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
        <div class="col-md-4 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.total_wastage_qty')</p>
                <h3 style="margin:4px 0 0;">
                    <span id="sum_wastage"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                    <small class="text-muted" id="sum_wastage_pct"></small>
                </h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.wastage_value')</p>
                <h3 style="margin:4px 0 0;" id="sum_wastage_value"><i class="fas fa-sync fa-spin fa-fw"></i></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget')
                <p class="text-muted" style="margin:0;">@lang('lang_v1.drying_entries')</p>
                <h3 style="margin:4px 0 0;" id="sum_count"><i class="fas fa-sync fa-spin fa-fw"></i></h3>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.wastage_by_product')])
        <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
            <table class="table table-bordered table-striped" id="wastage_by_product_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('sale.product')</th>
                        <th>@lang('lang_v1.drying_entries')</th>
                        <th>@lang('lang_v1.before_drying')</th>
                        <th>@lang('lang_v1.after_drying')</th>
                        <th>@lang('lang_v1.wastage')</th>
                        <th>@lang('lang_v1.wastage_pct')</th>
                        <th>@lang('lang_v1.wastage_value')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.wastage_detail')])
        <div class="table-responsive pvs-table-scroll vspos-table-scroll-x">
            <table class="table table-bordered table-striped" id="wastage_detail_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('sale.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.wastage')</th>
                        <th>@lang('lang_v1.wastage_pct')</th>
                        <th>@lang('lang_v1.avg_cost')</th>
                        <th>@lang('lang_v1.avg_cost') (@lang('lang_v1.after_drying'))</th>
                        <th>@lang('lang_v1.wastage_value')</th>
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

        var byProductTable = $('#wastage_by_product_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ url("/nutmeg/reports/wastage/by-product") }}',
                data: filterData
            },
            columns: [
                { data: 'product_name', name: 'p.name' },
                { data: 'drying_count', name: 'drying_count' },
                { data: 'total_before', name: 'total_before' },
                { data: 'total_after', name: 'total_after' },
                { data: 'total_wastage', name: 'total_wastage' },
                { data: 'wastage_pct', name: 'wastage_pct', orderable: false, searchable: false },
                { data: 'total_wastage_value', name: 'total_wastage_value' }
            ],
            buttons: [
                { extend: 'csv', text: '<i class="fa fa-file-csv"></i> CSV', className: 'btn btn-default btn-sm' },
                { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-default btn-sm' },
                { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm' }
            ],
            dom: '<"row margin-bottom-12"<"col-sm-6"B><"col-sm-6"f>>rtip',
            fnDrawCallback: function () {
                __currency_convert_recursively($('#wastage_by_product_table'));
            }
        });

        var detailTable = $('#wastage_detail_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ url("/nutmeg/reports/wastage/data") }}',
                data: filterData
            },
            columns: [
                { data: 'created_at', name: 'nutmeg_dryings.created_at' },
                { data: 'product_label', name: 'p.name' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'qty_wastage', name: 'qty_wastage' },
                { data: 'wastage_pct', name: 'wastage_pct' },
                { data: 'unit_cost_before', name: 'unit_cost_before' },
                { data: 'unit_cost_after', name: 'unit_cost_after' },
                { data: 'wastage_value', name: 'wastage_value' }
            ],
            buttons: [
                { extend: 'csv', text: '<i class="fa fa-file-csv"></i> CSV', className: 'btn btn-default btn-sm' },
                { extend: 'excel', text: '<i class="fa fa-file-excel"></i> Excel', className: 'btn btn-default btn-sm' },
                { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm' }
            ],
            dom: '<"row margin-bottom-12"<"col-sm-6"B><"col-sm-6"f>>rtip',
            fnDrawCallback: function () {
                __currency_convert_recursively($('#wastage_detail_table'));
            }
        });

        function loadSummary() {
            var data = {};
            filterData(data);
            $.get('{{ url("/nutmeg/reports/summary") }}', data, function (res) {
                $('#sum_count').text(res.drying_count);
                $('#sum_wastage').html('<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">' + res.total_wastage + '</span>');
                $('#sum_wastage_pct').text('(' + res.wastage_pct + '%)');
                $('#sum_wastage_value').html('<span class="display_currency" data-currency_symbol="true">' + res.total_wastage_value + '</span>');
                __currency_convert_recursively($('.content'));
            });
        }

        function reloadAll() {
            byProductTable.ajax.reload();
            detailTable.ajax.reload();
            loadSummary();
        }

        $('#location_id, #product_id').on('change', reloadAll);
        loadSummary();
    });
</script>
@endsection
