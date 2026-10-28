@extends('layouts.app')
@section('title', __('lang_v1.mobile_imei_inventory'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.mobile_imei_inventory')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{ action([\App\Http\Controllers\MobileShopController::class, 'imeiCreate']) }}"
                    data-container=".view_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <div class="row" style="margin-bottom:12px;">
            <div class="col-sm-3">
                {!! Form::select('status_filter', [
                    '' => __('lang_v1.all'),
                    'available' => __('lang_v1.mobile_imei_status_available'),
                    'sold' => __('lang_v1.mobile_imei_status_sold'),
                    'in_repair' => __('lang_v1.mobile_imei_status_in_repair'),
                ], null, ['class' => 'form-control select2', 'id' => 'status_filter']) !!}
            </div>
            <div class="col-sm-4">
                {!! Form::select('product_filter', $products, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all_products'), 'id' => 'product_filter']) !!}
            </div>
        </div>

        <table class="table table-bordered table-striped" id="mobile_imei_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.imei')</th>
                    <th>@lang('product.product_name')</th>
                    <th>@lang('business.location')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(function () {
    var table = $('#mobile_imei_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action([\App\Http\Controllers\MobileShopController::class, "imeiIndex"]) }}',
            data: function (d) {
                d.status = $('#status_filter').val();
                d.product_id = $('#product_filter').val();
            }
        },
        columns: [
            { data: 'imei', name: 'imei' },
            { data: 'product_name', name: 'product_name', orderable: false },
            { data: 'location_name', name: 'location_name', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    $('#status_filter, #product_filter').change(function () { table.ajax.reload(); });
});
</script>
@endsection
