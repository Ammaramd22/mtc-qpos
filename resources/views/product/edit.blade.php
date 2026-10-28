@extends('layouts.app')
@section('title', __('product.edit_product'))

@section('content')

@php
  $is_image_required = !empty($common_settings['is_product_image_required']) && empty($product->image);
@endphp

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-white">@lang('product.edit_product')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'update'] , [$product->id] ), 'method' => 'PUT', 'id' => 'product_add_form',
        'class' => 'product_form', 'files' => true ]) !!}
    <input type="hidden" id="product_id" value="{{ $product->id }}">

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('name', __('product.product_name') . ':*') !!}
                  {!! Form::text('name', $product->name, ['class' => 'form-control', 'required',
                  'placeholder' => __('product.product_name')]); !!}
                {!! Form::hidden('name_is_manual', '0', ['id' => 'name_is_manual']); !!}
                @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
                <p class="help-block" style="margin-bottom:0;">@lang('product.tyre_auto_name_help')</p>
                @endif
              </div>
            </div>

            @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
            @include('product.partials.tyre_size_field')
            @endif

            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('sku', __('product.sku')  . ':*') !!} @show_tooltip(__('tooltip.sku'))
                {!! Form::text('sku', $product->sku, ['class' => 'form-control',
                'placeholder' => __('product.sku'), 'required']); !!}
              </div>
            </div>

            {{-- Barcode type kept as hidden default; not needed in the UI --}}
            {!! Form::hidden('barcode_type', $product->barcode_type ?: ($barcode_default ?? 'C128')) !!}

            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':*') !!}
                <div class="input-group">
                  {!! Form::select('unit_id', $units, $product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat quick_add_unit btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>

            <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

                <select name="sub_unit_ids[]" class="form-control select2" multiple id="sub_unit_ids">
                  @foreach($sub_units as $sub_unit_id => $sub_unit_value)
                    <option value="{{$sub_unit_id}}" 
                      @if(is_array($product->sub_unit_ids) &&in_array($sub_unit_id, $product->sub_unit_ids))   selected 
                      @endif>{{$sub_unit_value['name']}}</option>
                  @endforeach
                </select>
              </div>
            </div>

            @if(!empty($common_settings['enable_secondary_unit']))
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('secondary_unit_id', __('lang_v1.secondary_unit') . ':') !!} @show_tooltip(__('lang_v1.secondary_unit_help'))
                        {!! Form::select('secondary_unit_id', $units, $product->secondary_unit_id, ['class' => 'form-control select2']); !!}
                    </div>
                </div>
            @endif

            <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
              <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                <div class="input-group">
                  {!! Form::select('brand_id', $brands, $product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
              <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ':') !!}
                <div class="input-group">
                  {!! Form::select('category_id', $categories, $product->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('category.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'], ['type' => 'product', 'quick_add' => true])}}" title="@lang('category.add_category')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>

            <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_category_id', __('product.sub_category')  . ':') !!}
                <div class="input-group">
                  {!! Form::select('sub_category_id', $sub_categories, $product->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('category.create')) disabled @endif class="btn btn-default bg-white btn-flat quick_add_sub_category_btn" data-href-base="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'], ['type' => 'product', 'quick_add' => true, 'as_sub' => true])}}" title="@lang('lang_v1.add_as_sub_txonomy')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>

            @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
            @include('product.partials.tyre_note_field')
            @endif

            @include('product.partials.industry_fields')

            @if(count($business_locations) > 1)
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
                  {!! Form::select('product_locations[]', $business_locations, $product->product_locations->pluck('id'), ['class' => 'form-control select2', 'multiple', 'id' => 'product_locations']); !!}
              </div>
            </div>
            @else
              @foreach($business_locations as $loc_id => $loc_name)
                {!! Form::hidden('product_locations[]', $loc_id) !!}
              @endforeach
            @endif

            <div class="col-sm-4">
              <div class="form-group">
                <label>
                  {!! Form::checkbox('enable_stock', 1, $product->enable_stock, ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
                </label>@show_tooltip(__('tooltip.enable_stock'))
              </div>
            </div>
            <div class="col-sm-4" id="alert_quantity_div" @if(!$product->enable_stock) style="display:none" @endif>
              <div class="form-group">
                {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
                {!! Form::text('alert_quantity', $alert_quantity, ['class' => 'form-control input_number',
                'placeholder' => __('product.alert_quantity') , 'min' => '0']); !!}
              </div>
            </div>
            @if(!empty($common_settings['enable_product_warranty']))
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
                {!! Form::select('warranty_id', $warranties, $product->warranty_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
              </div>
            </div>
            @endif
            <!-- include module fields -->
            @if(!empty($pos_module_data))
                @foreach($pos_module_data as $key => $value)
                    @if(!empty($value['view_path']))
                        @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                    @endif
                @endforeach
            @endif
            <div class="clearfix"></div>
            <div class="col-sm-8">
              <div class="form-group">
                {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
                  {!! Form::textarea('product_description', $product->product_description, ['class' => 'form-control']); !!}
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*', 'required' => $is_image_required]); !!}
                <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]). @lang('lang_v1.aspect_ratio_should_be_1_1') @if(!empty($product->image)) <br> @lang('lang_v1.previous_image_will_be_replaced') @endif</p></small>
              </div>
            </div>
            </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
        @if(session('business.enable_product_expiry'))

          @if(session('business.expiry_type') == 'add_expiry')
            @php
              $expiry_period = 12;
              $hide = true;
            @endphp
          @else
            @php
              $expiry_period = null;
              $hide = false;
            @endphp
          @endif
          <div class="col-sm-4 @if($hide) hide @endif">
            <div class="form-group">
              <div class="multi-input">
                @php
                  $disabled = false;
                  $disabled_period = false;
                  if( empty($product->expiry_period_type) || empty($product->enable_stock) ){
                    $disabled = true;
                  }
                  if( empty($product->enable_stock) ){
                    $disabled_period = true;
                  }
                @endphp
                  {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
                  {!! Form::text('expiry_period', @num_format($product->expiry_period), ['class' => 'form-control pull-left input_number',
                    'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;', 'disabled' => $disabled]); !!}
                  {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], $product->expiry_period_type, ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type', 'disabled' => $disabled_period]); !!}
              </div>
            </div>
          </div>
          @endif
          <div class="col-sm-4">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('enable_sr_no', 1, $product->enable_sr_no, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
              </label>
              @show_tooltip(__('lang_v1.tooltip_sr_no'))
            </div>
          </div>

          <div class="col-sm-4">
          <div class="form-group">
            <label>
              {!! Form::checkbox('not_for_selling', 1, $product->not_for_selling, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
            </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
          </div>
        </div>

        <div class="clearfix"></div>

        <!-- Rack, Row & position number -->
        @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
          <div class="col-md-12">
            <h4>@lang('lang_v1.rack_details'):
              @show_tooltip(__('lang_v1.tooltip_rack_details'))
            </h4>
          </div>
          @foreach($business_locations as $id => $location)
            <div class="col-sm-3">
              <div class="form-group">
                {!! Form::label('rack_' . $id,  $location . ':') !!}

                
                  @if(!empty($rack_details[$id]))
                    @if(session('business.enable_racks'))
                      {!! Form::text('product_racks_update[' . $id . '][rack]', $rack_details[$id]['rack'], ['class' => 'form-control', 'id' => 'rack_' . $id]); !!}
                    @endif

                    @if(session('business.enable_row'))
                      {!! Form::text('product_racks_update[' . $id . '][row]', $rack_details[$id]['row'], ['class' => 'form-control']); !!}
                    @endif

                    @if(session('business.enable_position'))
                      {!! Form::text('product_racks_update[' . $id . '][position]', $rack_details[$id]['position'], ['class' => 'form-control']); !!}
                    @endif
                  @else
                    {!! Form::text('product_racks[' . $id . '][rack]', null, ['class' => 'form-control', 'id' => 'rack_' . $id, 'placeholder' => __('lang_v1.rack')]); !!}

                    {!! Form::text('product_racks[' . $id . '][row]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}

                    {!! Form::text('product_racks[' . $id . '][position]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]); !!}
                  @endif

              </div>
            </div>
          @endforeach
        @endif

        <div class="clearfix"></div>
        
        @php
            $custom_labels = json_decode(session('business.custom_labels'), true);
            $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
            $product_cf_details = !empty($custom_labels['product_cf_details']) ? $custom_labels['product_cf_details'] : [];
        @endphp
        <!--custom fields-->

        @include('product.partials.supplier_costcode_fields')

        @foreach($product_custom_fields as $index => $cf)
            @if(!empty($cf) && ! in_array($loop->iteration, [1, 2], true))
                @php
                    $db_field_name = 'product_custom_field' . $loop->iteration;
                    $cf_type = !empty($product_cf_details[$loop->iteration]['type']) ? $product_cf_details[$loop->iteration]['type'] : 'text';
                    $dropdown = !empty($product_cf_details[$loop->iteration]['dropdown_options']) ? explode(PHP_EOL, $product_cf_details[$loop->iteration]['dropdown_options']) : [];
                @endphp

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label($db_field_name, $cf . ':') !!}
                        @if(in_array($cf_type, ['text', 'date']))
                            <input type="{{$cf_type}}" name="{{$db_field_name}}" id="{{$db_field_name}}" 
                            value="{{$product->$db_field_name}}" class="form-control" placeholder="{{$cf}}">
                        @elseif($cf_type == 'dropdown')
                            <!-- {!! Form::select($db_field_name, $dropdown, $product->$db_field_name, ['placeholder' => $cf, 'class' => 'form-control select2']); !!} -->
                             <select name="{{$db_field_name}}" id="{{$db_field_name}}" class="form-control select2">
                                @foreach($dropdown as $option)
                                    <option value="{{$option}}" @if($option == $product->$db_field_name) selected @endif>{{$option}}</option>
                                @endforeach
                             </select>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        <!--custom fields-->
        @include('layouts.partials.module_form_part')
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-4 @if(!is_tax_enabled()) hide @endif tax-field">
              <div class="form-group">
                {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
                  {!! Form::select('tax', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
              </div>
            </div>

            <div class="col-sm-4 @if(!is_tax_enabled()) hide @endif tax-field">
              <div class="form-group">
                {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':') !!}
                  {!! Form::select('tax_type',['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $product->tax_type,
                  ['class' => 'form-control select2']); !!}
              </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
                {!! Form::select('type', $product_types, $product->type, ['class' => 'form-control select2',
                  'required','disabled', 'data-action' => 'edit', 'data-product_id' => $product->id ]); !!}
              </div>
            </div>

            <div class="form-group col-sm-12" id="product_form_part"></div>
            <input type="hidden" id="variation_counter" value="0">
            <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">
            </div>
    @endcomponent

  <div class="row">
    <input type="hidden" name="submit_type" id="submit_type">
        <div class="col-sm-12">
          <div class="text-center">
            <div class="btn-group">
              @if($selling_price_group_count)
                <button type="submit" value="submit_n_add_selling_prices" class="tw-dw-btn tw-dw-btn-warning tw-text-white tw-dw-btn-lg submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
              @endif

              @can('product.opening_stock')
              <button type="submit" @if(empty($product->enable_stock)) disabled="true" @endif id="opening_stock_button"  value="update_n_edit_opening_stock" class="tw-dw-btn tw-text-white tw-dw-btn-lg bg-purple submit_product_form">@lang('lang_v1.update_n_edit_opening_stock')</button>
              @endif

              <button type="submit" value="save_n_add_another" class="tw-dw-btn tw-text-white tw-dw-btn-lg bg-maroon submit_product_form">@lang('lang_v1.update_n_add_another')</button>

              <button type="submit" value="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg submit_product_form">@lang('messages.update')</button>
            </div>
          </div>
        </div>
  </div>
{!! Form::close() !!}

@php $__stock_price_module = in_array('stock_price_management', session('business.enabled_modules') ?? []); @endphp
@if($__stock_price_module)
{{-- ── MULTIPLE PRICES SECTION (outside product form to avoid nested form issue) ── --}}
@php
  $existing_stock_prices = \App\VariationStockPrice::where('product_id', $product->id)->orderBy('variation_id')->orderBy('created_at','desc')->get();
@endphp
@component('components.widget', ['class' => 'box-primary', 'title' => 'Multiple Selling Prices'])
  <p class="text-muted" style="margin-top:-8px;margin-bottom:12px;">Add multiple selling prices for this product. At POS, if a product has more than one price, a selection popup will appear.</p>
  <form id="__sp_add_form">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <div class="row">
      <div class="col-sm-3">
        <div class="form-group">
          <label>Variation</label>
          <select name="variation_id" id="__sp_variation" class="form-control" required>
            @foreach($product->variations as $v)
              <option value="{{ $v->id }}">
                {{ $product->type == 'variable' ? optional($v->product_variation)->name . ' - ' . $v->name : 'Default' }}
                ({{ $v->sub_sku }})
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Label <small class="text-muted">(optional, e.g. Old Price)</small></label>
          <input type="text" name="label" id="__sp_label" class="form-control" placeholder="e.g. Old Price, New Price">
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>Selling Price (inc. tax)</label>
          <input type="number" name="sell_price_inc_tax" id="__sp_price" class="form-control" step="0.01" min="0" required placeholder="0.00">
        </div>
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          <label>&nbsp;</label><br>
          <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Price</button>
        </div>
      </div>
    </div>
  </form>
  <div id="__sp_list">
    @if($existing_stock_prices->isEmpty())
      <p class="text-muted" id="__sp_empty">No extra prices added yet.</p>
    @else
      <table class="table table-bordered table-condensed" id="__sp_table">
        <thead><tr><th>Variation</th><th>Label</th><th>Selling Price</th><th>Added</th><th></th></tr></thead>
        <tbody>
          @foreach($existing_stock_prices as $sp)
          <tr id="__sp_row_{{ $sp->id }}">
            <td>
              @if($sp->variation)
                {{ $product->type == 'variable' ? optional($sp->variation->product_variation)->name . ' - ' . $sp->variation->name : 'Default' }}
                <small class="text-muted">({{ $sp->variation->sub_sku }})</small>
              @endif
            </td>
            <td>{{ $sp->label ?: '—' }}</td>
            <td><strong>@format_currency($sp->sell_price_inc_tax)</strong></td>
            <td>{{ $sp->created_at->format('d M Y') }}</td>
            <td><button type="button" class="btn btn-xs btn-danger __sp_delete" data-id="{{ $sp->id }}"><i class="fa fa-trash"></i></button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endcomponent
@endif

</section>
<!-- /.content -->

@endsection

@section('javascript')
  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
  <script type="text/javascript">
    $(document).ready( function(){
      __page_leave_confirmation('#product_add_form');

      @if($__stock_price_module)
      // Multiple Prices
      $('#__sp_add_form').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.ajax({
          url: '/products/stock-prices',
          method: 'POST',
          data: $(this).serialize(),
          success: function(res) {
            btn.prop('disabled', false);
            if (!res.success) { toastr.error('Error saving price.'); return; }
            toastr.success(res.msg);
            var row = '<tr id="__sp_row_' + res.id + '">'
              + '<td>' + res.variation_name + '</td>'
              + '<td>' + (res.label || '—') + '</td>'
              + '<td><strong>' + res.price_formatted + '</strong></td>'
              + '<td>' + res.created_at + '</td>'
              + '<td><button type="button" class="btn btn-xs btn-danger __sp_delete" data-id="' + res.id + '"><i class="fa fa-trash"></i></button></td>'
              + '</tr>';
            if ($('#__sp_table').length) {
              $('#__sp_table tbody').append(row);
            } else {
              $('#__sp_empty').remove();
              $('#__sp_list').html('<table class="table table-bordered table-condensed" id="__sp_table"><thead><tr><th>Variation</th><th>Label</th><th>Selling Price</th><th>Added</th><th></th></tr></thead><tbody>' + row + '</tbody></table>');
            }
            $('#__sp_label').val('');
            $('#__sp_price').val('');
          },
          error: function() { btn.prop('disabled', false); toastr.error('Something went wrong.'); }
        });
      });

      $(document).on('click', '.__sp_delete', function() {
        if (!confirm('Delete this price?')) return;
        var id = $(this).data('id');
        $.ajax({
          url: '/products/stock-prices/' + id,
          method: 'DELETE',
          data: { _token: $('meta[name=csrf-token]').attr('content') },
          success: function(res) {
            if (res.success) {
              $('#__sp_row_' + id).remove();
              if ($('#__sp_table tbody tr').length === 0) {
                $('#__sp_list').html('<p class="text-muted" id="__sp_empty">No extra prices added yet.</p>');
              }
              toastr.success('Price deleted.');
            }
          }
        });
      });
      @endif
    });
  </script>
@endsection