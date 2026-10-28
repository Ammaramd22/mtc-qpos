{!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'repairStore']), 'method' => 'post', 'id' => $form_id ?? 'mobile_repair_add_form']) !!}
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('imei', __('lang_v1.imei') . ':') !!}
            {!! Form::text('imei', $imei ?? request('imei'), ['class' => 'form-control', 'required', 'placeholder' => '35xxxxxxxxxxxxxx']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('issue_description', __('lang_v1.issue_description') . ':') !!}
            {!! Form::textarea('issue_description', null, ['class' => 'form-control', 'required', 'rows' => 3, 'placeholder' => __('lang_v1.issue_description_placeholder')]) !!}
        </div>
        <div class="form-group">
            {!! Form::label('repair_notes', __('lang_v1.repair_notes') . ':') !!}
            {!! Form::textarea('repair_notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('cost', __('lang_v1.repair_cost') . ':') !!}
                    {!! Form::text('cost', 0, ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('charge', __('lang_v1.repair_charge') . ':') !!}
                    {!! Form::text('charge', 0, ['class' => 'form-control input_number']) !!}
                </div>
            </div>
        </div>
        <p class="help-block text-muted">@lang('lang_v1.repair_warranty_note')</p>
    </div>
    <div class="col-md-6">
        @include('mobile_shop.partials.phone_check', ['phone_check' => []])
        <div class="form-group">
            @if(!empty($is_modal))
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            @else
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairIndex']) }}" class="btn btn-default">@lang('messages.cancel')</a>
            @endif
        </div>
    </div>
</div>
{!! Form::close() !!}
