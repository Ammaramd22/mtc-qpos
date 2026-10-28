<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => !empty($entry) ? action([\App\Http\Controllers\CapitalAccountController::class, 'updateEntry'], [$entry->id]) : action([\App\Http\Controllers\CapitalAccountController::class, 'storeEntry']), 'method' => !empty($entry) ? 'put' : 'post', 'id' => 'capital_entry_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">{{ !empty($entry) ? __('lang_v1.edit_capital_entry') : __('lang_v1.add_capital_entry') }}</h4>
    </div>
    <div class="modal-body">
      @php
        $expenseCategories = $expenseCategories ?? [];
        $settings = $settings ?? [];
        $defaultCat = $entry->expense_category_id ?? ($settings['expense_category_id'] ?? null);
        $expenseTypes = \App\CapitalEntry::EXPENSE_CATEGORY_TYPES;
      @endphp
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('entry_date', __('messages.date') . ':*') !!}
            {!! Form::text('entry_date', isset($entry) ? @format_date($entry->entry_date) : @format_date(\Carbon\Carbon::now()), ['class' => 'form-control', 'required', 'readonly', 'id' => 'capital_entry_date']) !!}
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('amount', __('sale.amount') . ':*') !!}
            {!! Form::text('amount', isset($entry) ? @num_format($entry->amount) : null, ['class' => 'form-control input_number', 'required']) !!}
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('type', __('lang_v1.capital_entry_type') . ':*') !!}
            {!! Form::select('type', $typeLabels, $entry->type ?? 'expense', ['class' => 'form-control select2', 'style' => 'width:100%', 'required', 'id' => 'capital_entry_type']) !!}
            <p class="help-block">@lang('lang_v1.capital_entry_type_help')</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            {!! Form::label('source', __('lang_v1.capital_source') . ':*') !!}
            {!! Form::select('source', $sourceLabels, $entry->source ?? 'cash_drawer', ['class' => 'form-control select2', 'style' => 'width:100%', 'required']) !!}
            <p class="help-block">@lang('lang_v1.capital_source_link_help')</p>
          </div>
        </div>
      </div>
      <div class="form-group capital-expense-category-wrap">
        {!! Form::label('expense_category_id', __('expense.expense_category') . ':') !!}
        @include('expense.partials.expense_category_select', [
          'categories' => $expenseCategories,
          'selected' => $defaultCat,
          'selectId' => 'capital_expense_category_id',
          'selectName' => 'expense_category_id',
        ])
        <p class="help-block">
          @lang('lang_v1.capital_entry_category_help')
        </p>
      </div>
      <div class="form-group capital-asset-name-wrap" style="display:none;">
        {!! Form::label('asset_name', __('lang_v1.capital_asset_name') . ':*') !!}
        {!! Form::text('asset_name', $entry->asset_name ?? null, ['class' => 'form-control', 'id' => 'capital_asset_name', 'placeholder' => __('lang_v1.capital_asset_name_placeholder')]) !!}
        <p class="help-block">@lang('lang_v1.capital_asset_name_help')</p>
      </div>
      <div class="form-group">
        {!! Form::label('capital_partner_id', __('lang_v1.capital_partner_name') . ':') !!}
        {!! Form::select('capital_partner_id', $partners, $entry->capital_partner_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') . ' / ' . __('lang_v1.shared')]) !!}
        <p class="help-block">@lang('lang_v1.capital_entry_partner_help')</p>
      </div>
      <div class="form-group">
        {!! Form::label('reference_no', __('purchase.ref_no') . ':') !!}
        {!! Form::text('reference_no', $entry->reference_no ?? null, ['class' => 'form-control']) !!}
      </div>
      <div class="form-group">
        {!! Form::label('note', __('brand.note') . ':') !!}
        {!! Form::textarea('note', $entry->note ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
<script>
  (function() {
    var expenseTypes = @json($expenseTypes);
    function toggleCapitalFields() {
      var t = $('#capital_entry_type').val();
      var isExpense = expenseTypes.indexOf(t) !== -1;
      var isAsset = t === 'asset';
      $('.capital-expense-category-wrap').toggle(isExpense);
      $('.capital-asset-name-wrap').toggle(isAsset);
      if (isAsset) {
        $('#capital_asset_name').attr('required', true);
      } else {
        $('#capital_asset_name').removeAttr('required');
      }
    }
    $('#capital_entry_date').datepicker({ autoclose: true, format: datepicker_date_format });
    $('.capital_entry_modal').find('.select2').select2({ dropdownParent: $('.capital_entry_modal') });
    $('#capital_entry_type').on('change', toggleCapitalFields);
    toggleCapitalFields();
  })();
</script>
