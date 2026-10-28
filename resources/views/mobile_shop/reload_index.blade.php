@extends('layouts.app')
@section('title', __('lang_v1.bill_payments'))

@section('content')
<section class="content-header">
    <h1><i class="fa fa-bolt"></i> @lang('lang_v1.bill_payments') <small>MyReload &amp; Utilities</small></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('lang_v1.new_payment')</h3>
                </div>
                {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'reloadStore']), 'method' => 'post', 'id' => 'mobile_reload_form', 'class' => 'vspos-bill-payment-form']) !!}
                <div class="box-body">
                    @if(!$myreload_configured)
                    <div class="alert alert-info">
                        @lang('lang_v1.myreload_demo_mode')
                        <a href="{{ url('/business/settings') }}">@lang('lang_v1.reload_module')</a>
                        ·
                        <a href="https://myreload.lk/" target="_blank" rel="noopener">MyReload.lk</a>
                    </div>
                    @elseif(!empty($myreload_demo))
                    <div class="alert alert-warning">
                        <i class="fa fa-flask"></i>
                        @lang('lang_v1.myreload_demo_mode')
                    </div>
                    @endif
                    @include('mobile_shop.partials.bill_payment_form', ['form_id' => 'mobile_reload_form', 'show_history_link' => false])
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-check"></i> @lang('lang_v1.process_payment')
                    </button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
        <div class="col-md-8">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.bill_history')])
                <div class="vspos-bill-history-table-wrap">
                    <table class="table table-bordered table-striped" id="mobile_reload_table" style="width:100% !important;">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.reference_no')</th>
                                <th>@lang('lang_v1.type')</th>
                                <th>@lang('lang_v1.bill_provider')</th>
                                <th>@lang('lang_v1.account_number')</th>
                                <th>@lang('sale.amount')</th>
                                <th>@lang('sale.status')</th>
                                <th>@lang('lang_v1.date')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<style>
.vspos-bill-history-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#mobile_reload_table_wrapper {
    width: 100% !important;
    max-width: 100%;
}
#mobile_reload_table_wrapper .dataTables_scroll,
#mobile_reload_table_wrapper .dataTables_scrollHead,
#mobile_reload_table_wrapper .dataTables_scrollBody,
#mobile_reload_table_wrapper .dataTables_scrollHeadInner {
    width: 100% !important;
}
#mobile_reload_table_wrapper .dataTables_scrollHeadInner table,
#mobile_reload_table {
    width: 100% !important;
    margin: 0 !important;
}
#mobile_reload_table th,
#mobile_reload_table td {
    white-space: nowrap;
    vertical-align: middle;
}
#mobile_reload_table_wrapper .row {
    margin-left: 0;
    margin-right: 0;
}
#mobile_reload_table_wrapper .dataTables_length,
#mobile_reload_table_wrapper .dataTables_filter,
#mobile_reload_table_wrapper .dataTables_info,
#mobile_reload_table_wrapper .dataTables_paginate {
    margin-bottom: 8px;
}
</style>
@endsection

@section('javascript')
<script src="{{ asset('js/mobile-bill-payments.js?v=' . config('constants.asset_version')) }}"></script>
<script>
$(function () {
    var table = $('#mobile_reload_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: '{{ action([\App\Http\Controllers\MobileShopController::class, "reloadIndex"]) }}',
        order: [[6, 'desc']],
        columns: [
            { data: 'reference_no', name: 'reference_no' },
            { data: 'payment_type_label', name: 'payment_type', orderable: false },
            { data: 'provider_label', name: 'bill_provider', orderable: false },
            { data: 'account_display', name: 'account_number', orderable: false },
            { data: 'amount', name: 'amount', className: 'text-right' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' }
        ],
        drawCallback: function () {
            table.columns.adjust();
        }
    });

    setTimeout(function () {
        table.columns.adjust();
    }, 200);

    $(window).on('resize', function () {
        table.columns.adjust();
    });

    vsposInitBillPaymentForms({
        formSelector: '#mobile_reload_form',
        onSuccess: function () {
            table.ajax.reload();
        }
    });
});
</script>
@endsection
