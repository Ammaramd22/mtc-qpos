<div class="modal-dialog" role="document">
  <div class="modal-content">
@php
  $quick_add = !empty($quick_add);
  $as_sub = !empty($as_sub);
  $form_id = $quick_add ? 'quick_add_category_form' : 'category_add_form';
  $name_label = !empty($module_category_data['taxonomy_label']) ? $module_category_data['taxonomy_label'] : __( 'category.category_name' );
  $cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
  $cat_code_label = !empty($module_category_data['taxonomy_code_label']) ? $module_category_data['taxonomy_code_label'] : __( 'category.code' );
  $enable_sub_category = isset($module_category_data['enable_sub_taxonomy']) && !$module_category_data['enable_sub_taxonomy'] ? false : true;
  $category_code_help_text = !empty($module_category_data['taxonomy_code_help_text']) ? $module_category_data['taxonomy_code_help_text'] : __('lang_v1.category_code_help');
  $modal_title = $as_sub ? __('product.sub_category') : ($quick_add ? __('category.add_category') : __('messages.add'));
@endphp

    {!! Form::open(['url' => action([\App\Http\Controllers\TaxonomyController::class, 'store']), 'method' => 'post', 'id' => $form_id ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">{{ $modal_title }}</h4>
    </div>

    <div class="modal-body">
      <input type="hidden" name="category_type" value="{{$category_type}}">
      @if($quick_add && $as_sub)
        <input type="hidden" name="add_as_sub_cat" value="1">
        <input type="hidden" name="quick_add_target" value="sub_category">
      @elseif($quick_add)
        <input type="hidden" name="quick_add_target" value="category">
      @endif

      <div class="form-group">
        {!! Form::label('name', ($as_sub ? __('product.sub_category') : $name_label) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => $as_sub ? __('product.sub_category') : $name_label]); !!}
      </div>
      @if($cat_code_enabled)
      <div class="form-group">
        {!! Form::label('short_code', $cat_code_label . ':') !!}
        {!! Form::text('short_code', null, ['class' => 'form-control', 'placeholder' => $cat_code_label]); !!}
        <p class="help-block">{!! $category_code_help_text !!}</p>
      </div>
      @endif
      @if(!$quick_add)
      <div class="form-group">
        {!! Form::label('description', __( 'lang_v1.description' ) . ':') !!}
        {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.description'), 'rows' => 3]); !!}
      </div>
      @endif

      @if($as_sub && !empty($parent_categories))
        <div class="form-group">
          {!! Form::label('parent_id', __( 'category.select_parent_category' ) . ':*') !!}
          {!! Form::select('parent_id', $parent_categories, $selected_parent_id ?? null, ['class' => 'form-control', 'required']); !!}
        </div>
      @elseif(!$quick_add && !empty($parent_categories) && $enable_sub_category)
        <div class="form-group">
            <div class="checkbox">
              <label>
                 {!! Form::checkbox('add_as_sub_cat', 1, false,[ 'class' => 'toggler', 'data-toggle_id' => 'parent_cat_div' ]); !!} @lang( 'lang_v1.add_as_sub_txonomy' )
              </label>
            </div>
        </div>
        <div class="form-group hide" id="parent_cat_div">
          {!! Form::label('parent_id', __( 'category.select_parent_category' ) . ':') !!}
          {!! Form::select('parent_id', $parent_categories, null, ['class' => 'form-control']); !!}
        </div>
      @endif
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
