<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => !empty($partner) ? action([\App\Http\Controllers\CapitalAccountController::class, 'updatePartner'], [$partner->id]) : action([\App\Http\Controllers\CapitalAccountController::class, 'storePartner']), 'method' => !empty($partner) ? 'put' : 'post', 'id' => 'capital_partner_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">{{ !empty($partner) ? __('lang_v1.edit_partner') : __('lang_v1.add_partner') }}</h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('lang_v1.capital_partner_name') . ':*') !!}
        {!! Form::text('name', $partner->name ?? null, ['class' => 'form-control', 'required']) !!}
      </div>
      <div class="form-group">
        {!! Form::label('short_name', __('lang_v1.capital_short_name') . ':') !!}
        {!! Form::text('short_name', $partner->short_name ?? null, ['class' => 'form-control', 'placeholder' => 'e.g. A / B']) !!}
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('investment_amount', __('lang_v1.capital_investment') . ':*') !!}
            {!! Form::text('investment_amount', isset($partner) ? @num_format($partner->investment_amount) : 0, ['class' => 'form-control input_number', 'required']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('profit_percent', __('lang_v1.capital_profit_percent') . ':*') !!}
            {!! Form::text('profit_percent', isset($partner) ? @num_format($partner->profit_percent) : 0, ['class' => 'form-control input_number', 'required']) !!}
            <p class="help-block">@lang('lang_v1.capital_profit_percent_help')</p>
          </div>
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('notes', __('brand.note') . ':') !!}
        {!! Form::textarea('notes', $partner->notes ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
      </div>
      <div class="form-group">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('is_active', 1, !isset($partner) || !empty($partner->is_active), ['class' => 'input-icheck']) !!}
            @lang('lang_v1.active')
          </label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
