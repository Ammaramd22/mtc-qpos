<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => empty($vehicle)
            ? action([\App\Http\Controllers\VehicleModificationController::class, 'vehicleStore'])
            : action([\App\Http\Controllers\VehicleModificationController::class, 'vehicleUpdate'], [$vehicle->id]),
            'method' => 'post']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">{{ empty($vehicle) ? __('lang_v1.add_vehicle') : __('lang_v1.edit_vehicle') }}</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('contact_id', __('contact.customer') . ':*') !!}
                {!! Form::select('contact_id', $customers, $vehicle->contact_id ?? null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('registration_number', __('lang_v1.registration_number') . ':') !!}
                {!! Form::text('registration_number', $vehicle->registration_number ?? null, ['class' => 'form-control']) !!}
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('make', __('lang_v1.vehicle_make') . ':') !!}
                        {!! Form::text('make', $vehicle->make ?? null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('model', __('lang_v1.vehicle_model') . ':') !!}
                        {!! Form::text('model', $vehicle->model ?? null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('year', __('lang_v1.vehicle_year') . ':') !!}
                        {!! Form::number('year', $vehicle->year ?? null, ['class' => 'form-control', 'min' => 1900, 'max' => 2100]) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('vehicle_type', __('lang_v1.vehicle_type') . ':') !!}
                        <select name="vehicle_type" class="form-control">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @if(($vehicle->vehicle_type ?? '') === $type) selected @endif>
                                    @lang('lang_v1.vehicle_type_'.$type)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                {!! Form::textarea('notes', $vehicle->notes ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script>
$(function () {
    $('.select2').select2({ dropdownParent: $('.view_modal') });
});
</script>
