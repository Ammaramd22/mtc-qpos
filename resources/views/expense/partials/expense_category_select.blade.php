@php
  $selected = $selected ?? null;
  $selectId = $selectId ?? 'expense_category_id';
  $selectName = $selectName ?? 'expense_category_id';
  $categories = $categories ?? [];
  $canCreate = auth()->user()->can('expense.add') || auth()->user()->can('expense.edit');
@endphp
<div class="input-group">
  {!! Form::select($selectName, $categories, $selected, [
      'class' => 'form-control select2',
      'placeholder' => __('messages.please_select'),
      'id' => $selectId,
  ]) !!}
  @if($canCreate)
  <span class="input-group-btn">
    <button type="button"
      class="btn btn-default bg-white btn-flat btn-modal"
      data-href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'create'], ['quick_add' => true]) }}"
      data-container=".view_modal"
      title="@lang('expense.add_expense_category')">
      <i class="fa fa-plus-circle text-primary fa-lg"></i>
    </button>
  </span>
  @endif
</div>
