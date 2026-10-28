<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'repairUpdate'], [$repair->id]), 'method' => 'post', 'id' => 'mobile_repair_edit_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">{{ $repair->reference_no }} — {{ $repair->imei->imei ?? '' }}</h4>
        </div>
        <div class="modal-body">
            <p><strong>@lang('product.product_name'):</strong> {{ $repair->imei->product->name ?? '—' }}</p>
            <p><strong>@lang('lang_v1.issue_description'):</strong> {{ $repair->issue_description }}</p>
            @if($repair->warranty_covered)
                <span class="label label-success">@lang('lang_v1.warranty_covered')</span>
            @endif
            <p style="margin-top:8px;">
                <strong>@lang('lang_v1.payment'):</strong>
                @if($repair->payment_status === 'paid')
                    <span class="label label-success">@lang('lang_v1.mobile_repair_payment_paid')</span>
                @elseif($repair->payment_status === 'unpaid')
                    <span class="label label-warning">@lang('lang_v1.mobile_repair_payment_pending')</span>
                @else
                    <span class="label label-default">@lang('lang_v1.mobile_repair_payment_waived')</span>
                @endif
            </p>

            <div class="form-group">
                {!! Form::label('status', __('sale.status') . ':') !!}
                {!! Form::select('status', [
                    'received' => __('lang_v1.mobile_repair_status_received'),
                    'in_progress' => __('lang_v1.mobile_repair_status_in_progress'),
                    'completed' => __('lang_v1.mobile_repair_status_completed'),
                    'cancelled' => __('lang_v1.mobile_repair_status_cancelled'),
                ], $repair->status, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('repair_notes', __('lang_v1.repair_notes') . ':') !!}
                {!! Form::textarea('repair_notes', $repair->repair_notes, ['class' => 'form-control', 'rows' => 3]) !!}
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('cost', __('lang_v1.repair_cost') . ':') !!}
                        {!! Form::text('cost', @num_format($repair->cost), ['class' => 'form-control input_number']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('charge', __('lang_v1.repair_charge') . ':') !!}
                        {!! Form::text('charge', @num_format($repair->charge), ['class' => 'form-control input_number']) !!}
                    </div>
                </div>
            </div>
            @include('mobile_shop.partials.phone_check', ['phone_check' => $repair->phone_check ?? []])
        </div>
        <div class="modal-footer">
            @if($repair->status === 'completed' && $repair->payment_status === 'unpaid' && (float) $repair->charge > 0)
                <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairCollectPayment'], [$repair->id]) }}" class="btn btn-success pull-left">
                    <i class="fa fa-cash-register"></i> @lang('lang_v1.mobile_repair_collect_payment')
                </a>
            @endif
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>
$('#mobile_repair_edit_form').submit(function (e) {
    e.preventDefault();
    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                $('.view_modal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                if ($.fn.DataTable.isDataTable('#mobile_repair_table')) {
                    $('#mobile_repair_table').DataTable().ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        }
    });
});
</script>
