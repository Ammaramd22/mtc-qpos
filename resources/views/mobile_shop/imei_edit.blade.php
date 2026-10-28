<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'imeiUpdate'], [$imei->id]), 'method' => 'post', 'id' => 'mobile_imei_edit_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.imei'): {{ $imei->imei }}</h4>
        </div>
        <div class="modal-body">
            <p><strong>@lang('product.product_name'):</strong> {{ $imei->product->name ?? '—' }}</p>
            <p><strong>@lang('sale.status'):</strong> @lang('lang_v1.mobile_imei_status_' . $imei->status)</p>

            @if($imei->status === 'sold' || $imei->status === 'in_repair')
            <div class="form-group">
                {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
                {!! Form::select('warranty_id', $warranties, $imei->warranty_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
            </div>
            <div class="form-group">
                {!! Form::label('warranty_start', __('lang_v1.warranty_start_date') . ':') !!}
                {!! Form::text('warranty_start', $imei->warranty_starts_at ? $imei->warranty_starts_at->format('Y-m-d') : ($imei->sold_at ? $imei->sold_at->format('Y-m-d') : ''), ['class' => 'form-control datepicker']) !!}
            </div>
            @if($imei->warranty_ends_at)
            <p class="text-muted">@lang('lang_v1.warranty_exp_date'): {{ $imei->warranty_ends_at->format('d-m-Y') }}</p>
            @endif
            @endif

            <div class="form-group">
                {!! Form::label('status', __('sale.status') . ':') !!}
                {!! Form::select('status', [
                    'available' => __('lang_v1.mobile_imei_status_available'),
                    'sold' => __('lang_v1.mobile_imei_status_sold'),
                    'in_repair' => __('lang_v1.mobile_imei_status_in_repair'),
                    'returned' => __('lang_v1.mobile_imei_status_returned'),
                ], $imei->status, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('notes', __('brand.note') . ':') !!}
                {!! Form::textarea('notes', $imei->notes, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>
$('#mobile_imei_edit_form').submit(function (e) {
    e.preventDefault();
    var form = $(this);
    $.ajax({
        method: 'POST',
        url: form.attr('action'),
        data: form.serialize(),
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                $('.view_modal').modal('hide');
                if ($.fn.DataTable.isDataTable('#mobile_imei_table')) {
                    $('#mobile_imei_table').DataTable().ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        }
    });
});
</script>
