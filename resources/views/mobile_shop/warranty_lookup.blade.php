@extends('layouts.app')
@section('title', __('lang_v1.mobile_warranty_lookup'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.mobile_warranty_lookup')</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'warrantyLookup']), 'method' => 'get']) !!}
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('imei', __('lang_v1.enter_imei') . ':') !!}
                        {!! Form::text('imei', request('imei'), ['class' => 'form-control input-lg', 'placeholder' => '35xxxxxxxxxxxxxx', 'autofocus']) !!}
                    </div>
                </div>
                <div class="col-sm-2" style="padding-top:25px;">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-search"></i> @lang('lang_v1.search')</button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

    @if(request()->filled('imei'))
        @if($result)
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $result['product_name'] }} — {{ $result['imei'] }}</h3>
                @if($result['under_warranty'])
                    <span class="label label-success pull-right">@lang('lang_v1.under_warranty')</span>
                @else
                    <span class="label label-default pull-right">@lang('lang_v1.warranty_expired')</span>
                @endif
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-condensed">
                            <tr><th>@lang('product.sku')</th><td>{{ $result['sku'] }}</td></tr>
                            <tr><th>@lang('sale.status')</th><td>@lang('lang_v1.mobile_imei_status_' . ($result['status'] ?? 'sold'))</td></tr>
                            <tr><th>@lang('lang_v1.sold_on')</th><td>{{ $result['sold_at'] ?? '—' }}</td></tr>
                            <tr><th>@lang('sale.invoice_no')</th><td>{{ $result['invoice_no'] ?? '—' }}</td></tr>
                            <tr><th>@lang('contact.customer')</th><td>{{ $result['customer'] ?? '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-condensed">
                            <tr><th>@lang('lang_v1.warranty')</th><td>{{ $result['warranty_name'] ?? __('lang_v1.not_set') }}</td></tr>
                            <tr><th>@lang('lang_v1.warranty_start_date')</th><td>{{ $result['warranty_starts_at'] ?? '—' }}</td></tr>
                            <tr><th>@lang('lang_v1.warranty_exp_date')</th><td>{{ $result['warranty_ends_at'] ?? '—' }}</td></tr>
                            <tr><th>@lang('lang_v1.repairs')</th><td>{{ $result['repairs_count'] }}</td></tr>
                        </table>
                    </div>
                </div>

                @if(empty($result['warranty_id']))
                <hr>
                <h4>@lang('lang_v1.assign_warranty')</h4>
                {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'warrantyAssign']), 'method' => 'post', 'id' => 'assign_warranty_form']) !!}
                {!! Form::hidden('imei_id', $result['id']) !!}
                <div class="row">
                    <div class="col-sm-4">
                        {!! Form::select('warranty_id', $warranties, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                    <div class="col-sm-3">
                        {!! Form::text('warranty_start', '', ['class' => 'form-control datepicker', 'placeholder' => __('lang_v1.warranty_start_date')]) !!}
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-success">@lang('lang_v1.assign_warranty')</button>
                    </div>
                </div>
                {!! Form::close() !!}
                @endif

                @if(!empty($result['repairs']))
                <hr>
                <h4>@lang('lang_v1.repair_history')</h4>
                <table class="table table-bordered">
                    <thead><tr><th>@lang('lang_v1.reference_no')</th><th>@lang('sale.status')</th><th>@lang('lang_v1.issue')</th><th>@lang('lang_v1.received_at')</th></tr></thead>
                    <tbody>
                    @foreach($result['repairs'] as $r)
                        <tr>
                            <td>{{ $r['reference_no'] }}</td>
                            <td>{{ $r['status'] }}</td>
                            <td>{{ $r['issue'] }}</td>
                            <td>{{ $r['received_at'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif

                <div style="margin-top:12px;">
                    <button type="button" class="btn btn-warning btn-modal"
                        data-href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairCreate']) }}?imei={{ urlencode($result['imei']) }}"
                        data-container=".view_modal">
                        <i class="fa fa-tools"></i> @lang('lang_v1.new_repair')
                    </button>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-warning">@lang('lang_v1.mobile_imei_not_found')</div>
        @endif
    @endif
</section>
@endsection

@section('javascript')
<script>
$('#assign_warranty_form').submit(function (e) {
    e.preventDefault();
    $.post($(this).attr('action'), $(this).serialize(), function (r) {
        if (r.success) { toastr.success(r.msg); location.reload(); }
        else { toastr.error(r.msg); }
    });
});
</script>
@endsection
