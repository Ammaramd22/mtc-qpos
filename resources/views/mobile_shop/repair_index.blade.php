@extends('layouts.app')
@section('title', __('lang_v1.mobile_repairs'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.mobile_repairs')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}" class="btn btn-success" title="@lang('lang_v1.mobile_repair_pos_select')">
                    <i class="fa fa-cash-register"></i> @lang('lang_v1.mobile_repair_collect_payment')
                </a>
                <button type="button" class="btn btn-primary btn-modal"
                    data-href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairCreate']) }}"
                    data-container=".view_modal">
                    <i class="fa fa-plus"></i> @lang('lang_v1.new_repair')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="mobile_repair_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.reference_no')</th>
                    <th>@lang('lang_v1.imei')</th>
                    <th>@lang('product.product_name')</th>
                    <th>@lang('contact.customer')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('lang_v1.payment')</th>
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
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');

    $('#mobile_repair_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action([\App\Http\Controllers\MobileShopController::class, "repairIndex"]) }}',
        columns: [
            { data: 'reference_no', name: 'reference_no' },
            { data: 'imei_no', name: 'imei_no', orderable: false },
            { data: 'product_name', name: 'product_name', orderable: false },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'payment_label', name: 'payment_label', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection
