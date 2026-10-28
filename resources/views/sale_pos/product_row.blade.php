@php
	$common_settings = session()->get('business.common_settings');
	$multiplier = 1;
	$mobile_shop_enabled = in_array('mobile_shop', session('business.enabled_modules', []));

	$action = !empty($action) ? $action : '';
@endphp

@foreach($sub_units as $key => $value)
	@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
		@php
			$multiplier = $value['multiplier'];
		@endphp
	@endif
@endforeach

@php
	$is_inline_return = isset($product->quantity_ordered) && (float) $product->quantity_ordered < 0;
@endphp
<tr class="product_row @if($is_inline_return) vspos-inline-return @endif" data-row_index="{{$row_count}}" @if(!empty($so_line)) data-so_id="{{$so_line->transaction_id}}" @endif>
	@if(!empty($is_serial_no))
		<td class="serial_no pos-td-serial"></td>
	@endif
	<td class="pos-td-product">
		@if(!empty($so_line))
			<input type="hidden" 
			name="products[{{$row_count}}][so_line_id]" 
			value="{{$so_line->id}}">
		@endif
		@php
			$product_name = e($product->product_name);
			$product_sku_brand = $product->sub_sku;
			if(!empty($product->brand)){ $product_sku_brand .= ' · ' . e($product->brand); }
		@endphp

		<div class="vspos-line-product">
			<img class="vspos-line-thumb" src="@if(count($product->media) > 0)
							{{$product->media->first()->display_url}}
						@elseif(!empty($product->product_image))
							{{asset('/uploads/img/' . rawurlencode($product->product_image))}}
						@else
							{{asset('/img/default.png')}}
						@endif" alt="" loading="lazy" onerror="this.style.display='none'">
			<div class="vspos-line-meta">
				@if($is_inline_return)
					<span class="vspos-return-badge">@lang('lang_v1.inline_return')</span>
				@endif
				@if( ($edit_price || $edit_discount) && empty($is_direct_sell) )
				<div title="@lang('lang_v1.pos_edit_product_price_help')">
				<span class="vspos-line-name text-link text-info" data-toggle="modal" data-target="#row_edit_product_price_modal_{{$row_count}}">
					{!! $product_name !!}<i class="fa fa-pen vspos-line-edit-ico"></i>
				</span>
				</div>
				@else
					<span class="vspos-line-name">{!! $product_name !!}</span>
				@endif
				<div class="vspos-line-sku">{{ $product_sku_brand }}@if($product->enable_stock) · {{ @num_format($product->qty_available) }} {{$product->unit}}@endif</div>
			</div>
		</div>


		<input type="hidden" class="enable_sr_no" value="{{$product->enable_sr_no}}">
		<input type="hidden" 
			class="product_type" 
			name="products[{{$row_count}}][product_type]" 
			value="{{$product->product_type}}">

		@php
			$hide_tax = 'hide';
	        if(session()->get('business.enable_inline_tax') == 1){
	            $hide_tax = '';
	        }
	        
			$tax_id = $product->tax_id;
			$item_tax = !empty($product->item_tax) ? $product->item_tax : 0;
			$unit_price_inc_tax = $product->sell_price_inc_tax;

			if($hide_tax == 'hide' || empty($tax_id)){
				$tax_id = null;
				$item_tax = 0;
				// Keep charged price in sync with the visible unit price (default_sell_price)
				$unit_price_inc_tax = $product->default_sell_price;
			}

			if(!empty($so_line) && $action !== 'edit') {
				$tax_id = $so_line->tax_id;
				$item_tax = $so_line->item_tax;
				$unit_price_inc_tax = $so_line->unit_price_inc_tax;
			}

			$discount_type = !empty($product->line_discount_type) ? $product->line_discount_type : 'fixed';
			$discount_amount = !empty($product->line_discount_amount) ? $product->line_discount_amount : 0;
			
			if(!empty($discount)) {
				$discount_type = $discount->discount_type;
				$discount_amount = $discount->discount_amount;
			}

			if(!empty($so_line) && $action !== 'edit') {
				$discount_type = $so_line->line_discount_type;
				$discount_amount = $so_line->line_discount_amount;
			}

  			$sell_line_note = '';
  			if(!empty($product->sell_line_note)){
  				$sell_line_note = $product->sell_line_note;
  			}
			  if(!empty($so_line)){
  				$sell_line_note = $so_line->sell_line_note;
  			}
  		@endphp

		@if(!empty($discount))
			{!! Form::hidden("products[$row_count][discount_id]", $discount->id); !!}
		@endif

		@php
			$warranty_id = !empty($action) && $action == 'edit' && !empty($product->warranties->first())  ? $product->warranties->first()->id : $product->warranty_id;

			if($discount_type == 'fixed') {
				$discount_amount = $discount_amount * $multiplier;
			}
		@endphp

		@if(empty($is_direct_sell))
		<div class="modal fade row_edit_product_price_model" id="row_edit_product_price_modal_{{$row_count}}" tabindex="-1" role="dialog">
			@include('sale_pos.partials.row_edit_product_price_modal')
		</div> 
		@endif
		<!-- Description modal end -->
		@if(in_array('modifiers' , $enabled_modules))
			<div class="modifiers_html">
				@if(!empty($product->product_ms))
					@include('restaurant.product_modifier_set.modifier_for_product', array('edit_modifiers' => true, 'row_count' => $loop->index, 'product_ms' => $product->product_ms ) )
				@endif
			</div>
		@endif

		@php
			$is_pop_row = !empty($is_pop) || request()->get('for_pop') == '1' || request()->get('for_pop') == 'true' || request()->is('purchase-pos*');

			$max_quantity = $product->qty_available;
			$formatted_max_quantity = $product->formatted_qty_available;

			if(!empty($action) && $action == 'edit') {
				if(!empty($so_line)) {
					$qty_available = $so_line->quantity - $so_line->so_quantity_invoiced + $product->quantity_ordered;
					$max_quantity = $qty_available;
					$formatted_max_quantity = number_format($qty_available, session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']);
				}
			} else {
				if(!empty($so_line) && $so_line->qty_available <= $max_quantity) {
					$max_quantity = $so_line->qty_available;
					$formatted_max_quantity = $so_line->formatted_qty_available;
				}
			}
			

			$max_qty_rule = $max_quantity;
			$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $formatted_max_quantity, 'unit' => $product->unit  ]);
		@endphp

		@if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
		@php
			$lot_enabled = session()->get('business.enable_lot_number');
			$exp_enabled = session()->get('business.enable_product_expiry');
			$lot_no_line_id = '';
			if(!empty($product->lot_no_line_id)){
				$lot_no_line_id = $product->lot_no_line_id;
			}
		@endphp
		@if(!empty($product->lot_numbers) && empty($is_sales_order))
			<select class="form-control lot_number input-sm" name="products[{{$row_count}}][lot_no_line_id]" @if(!empty($product->transaction_sell_lines_id)) disabled @endif>
				<option value="">@lang('lang_v1.lot_n_expiry')</option>
				@foreach($product->lot_numbers as $lot_number)
					@php
						$selected = "";
						if($lot_number->purchase_line_id == $lot_no_line_id){
							$selected = "selected";

							$max_qty_rule = $lot_number->qty_available;
							$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ]);
						}

						$expiry_text = '';
						if($exp_enabled == 1 && !empty($lot_number->exp_date)){
							if( \Carbon::now()->gt(\Carbon::createFromFormat('Y-m-d', $lot_number->exp_date)) ){
								$expiry_text = '(' . __('report.expired') . ')';
							}
						}

						//preselected lot number if product searched by lot number
						if(!empty($purchase_line_id) && $purchase_line_id == $lot_number->purchase_line_id) {
							$selected = "selected";

							$max_qty_rule = $lot_number->qty_available;
							$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ]);
						}
					@endphp
					<option value="{{$lot_number->purchase_line_id}}" data-qty_available="{{$lot_number->qty_available}}" data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ])" {{$selected}}>@if(!empty($lot_number->lot_number) && $lot_enabled == 1){{$lot_number->lot_number}} @endif @if($lot_enabled == 1 && $exp_enabled == 1) - @endif @if($exp_enabled == 1 && !empty($lot_number->exp_date)) @lang('product.exp_date'): {{@format_date($lot_number->exp_date)}} @endif {{$expiry_text}}</option>
				@endforeach
			</select>
		@endif
	@endif
	@if(!empty($is_direct_sell))
  		<br>
  		<textarea class="form-control" name="products[{{$row_count}}][sell_line_note]" rows="2">{{$sell_line_note}}</textarea>
  		<p class="help-block"><small>@lang('lang_v1.sell_line_description_help')</small></p>
	@endif
	</td>

	<td class="pos-td-qty v-center">
		{{-- If edit then transaction sell lines will be present --}}
		@if(!empty($product->transaction_sell_lines_id))
			<input type="hidden" name="products[{{$row_count}}][transaction_sell_lines_id]" class="form-control" value="{{$product->transaction_sell_lines_id}}">
		@endif

		<input type="hidden" name="products[{{$row_count}}][product_id]" class="form-control product_id" value="{{$product->product_id}}">

		<input type="hidden" value="{{$product->variation_id}}" 
			name="products[{{$row_count}}][variation_id]" class="row_variation_id">

		<input type="hidden" value="{{$product->enable_stock}}" 
			name="products[{{$row_count}}][enable_stock]">
		
		@if(!isset($product->quantity_ordered) || $product->quantity_ordered === '' || $product->quantity_ordered === null)
			@php
				$product->quantity_ordered = 1;
			@endphp
		@endif

		@php
			$allow_decimal = true;
			if($product->unit_allow_decimal != 1) {
				$allow_decimal = false;
			}
			$is_inline_return = (float) $product->quantity_ordered < 0;
		@endphp
		@foreach($sub_units as $key => $value)
        	@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
        		@php
        			$max_qty_rule = $max_qty_rule / $multiplier;
        			$unit_name = $value['name'];
        			$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);

        			if(!empty($product->lot_no_line_id)){
        				$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);
        			}

        			if($value['allow_decimal']) {
        				$allow_decimal = true;
        			}
        		@endphp
        	@endif
        @endforeach
		<div class="input-group input-number vspos-qty-stepper">
			<span class="input-group-btn"><button type="button" class="btn quantity-down vspos-qty-btn" aria-label="Decrease quantity"><i class="fa fa-minus"></i></button></span>
			<input type="text" data-min="{{ $is_inline_return ? '-999999' : '1' }}"
				class="form-control pos_quantity input_number mousetrap input_quantity"
				value="{{@format_quantity($product->quantity_ordered)}}" name="products[{{$row_count}}][quantity]" data-allow-overselling="@if($is_pop_row || !empty($pos_settings['allow_overselling'])){{'true'}}@else{{'false'}}@endif"
				@if($allow_decimal)
					data-decimal=1
				@else
					data-decimal=0
					data-rule-abs_digit="true"
					data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
				@endif
				data-rule-required="true"
				data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
				@if(!$is_pop_row && !$is_inline_return && $product->enable_stock && empty($pos_settings['allow_overselling']) && empty($is_sales_order) )
					data-rule-max-value="{{$max_qty_rule}}" data-qty_available="{{$product->qty_available}}" data-msg-max-value="{{$max_qty_msg}}"
					data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit  ])"
				@endif
			>
			<span class="input-group-btn"><button type="button" class="btn quantity-up vspos-qty-btn" aria-label="Increase quantity"><i class="fa fa-plus"></i></button></span>
		</div>
		
		<input type="hidden" name="products[{{$row_count}}][product_unit_id]" value="{{$product->unit_id}}">
		@if(count($sub_units) > 1)
			<select name="products[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit">
                @foreach($sub_units as $key => $value)
                    <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                        {{$value['name']}}
                    </option>
                @endforeach
           </select>
		@elseif(count($sub_units) == 1)
			@php $_su_key = array_key_first($sub_units); $_su = $sub_units[$_su_key]; @endphp
			<input type="hidden" name="products[{{$row_count}}][sub_unit_id]" value="{{$_su_key}}">
			<span class="pos-unit-label">{{$_su['name']}}</span>
		@else
			<span class="pos-unit-label">{{$product->unit}}</span>
		@endif

		@if(!empty($product->second_unit))
            <br>
            <span style="white-space: nowrap;">
            @lang('lang_v1.quantity_in_second_unit', ['unit' => $product->second_unit])*:</span><br>
            <input type="text" 
            name="products[{{$row_count}}][secondary_unit_quantity]" 
            value="{{@format_quantity($product->secondary_unit_quantity)}}"
            class="form-control input-sm input_number"
            required>
        @endif

		<input type="hidden" class="base_unit_multiplier" name="products[{{$row_count}}][base_unit_multiplier]" value="{{$multiplier}}">

		<input type="hidden" class="hidden_base_unit_sell_price" value="{{$product->default_sell_price / $multiplier}}">
		
		{{-- Hidden fields for combo products --}}
		@if($product->product_type == 'combo'&& !empty($product->combo_products))

			@foreach($product->combo_products as $k => $combo_product)

				@if(isset($action) && $action == 'edit')
					@php
						$combo_product['qty_required'] = $combo_product['quantity'] / $product->quantity_ordered;

						$qty_total = $combo_product['quantity'];
					@endphp
				@else
					@php
						$qty_total = $combo_product['qty_required'];
					@endphp
				@endif

				<input type="hidden" 
					name="products[{{$row_count}}][combo][{{$k}}][product_id]"
					value="{{$combo_product['product_id']}}">

					<input type="hidden" 
					name="products[{{$row_count}}][combo][{{$k}}][variation_id]"
					value="{{$combo_product['variation_id']}}">

					<input type="hidden"
					class="combo_product_qty" 
					name="products[{{$row_count}}][combo][{{$k}}][quantity]"
					data-unit_quantity="{{$combo_product['qty_required']}}"
					value="{{$qty_total}}">

					@if(isset($action) && $action == 'edit')
						<input type="hidden" 
							name="products[{{$row_count}}][combo][{{$k}}][transaction_sell_lines_id]"
							value="{{$combo_product['id']}}">
					@endif

			@endforeach
		@endif
	</td>
	@if(empty($is_direct_sell))
		@php
			$pos_unit_price_val = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price;
			if(!empty($so_line) && $action !== 'edit') {
				$pos_unit_price_val = $so_line->unit_price_before_discount;
			}
		@endphp
		<td class="pos-td-unitprice v-center text-center">
			<div class="vspos-rate-field {{ !empty($is_pop_row) ? 'vspos-rate-field--pop' : '' }}">
			@php
				$can_edit_rate = !empty($is_pop_row)
					|| auth()->user()->can('edit_product_price_from_pos_screen')
					|| auth()->user()->can('edit_product_price_from_sale_screen');
			@endphp
			@if($can_edit_rate)
				<input type="text" name="products[{{$row_count}}][unit_price]" class="form-control input_number pos_unit_price" value="{{@num_format($pos_unit_price_val)}}" @if(empty($is_pop_row) && !empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price_val}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price_val)])}}" @endif>
			@else
				<span class="vspos-rate-readonly">@format_currency($pos_unit_price_val)</span>
				<input type="hidden" name="products[{{$row_count}}][unit_price]" class="pos_unit_price" value="{{@num_format($pos_unit_price_val)}}">
			@endif
			@if($is_pop_row && isset($product->last_purchased_price) && $product->last_purchased_price !== null && $product->last_purchased_price !== '')
				<small class="text-muted vspos-last-buy-hint">Last: @format_currency($product->last_purchased_price)</small>
			@endif
			@if($is_pop_row && function_exists('nutmeg_enabled') && nutmeg_enabled())
				<input type="hidden" name="products[{{$row_count}}][product_expenses_json]" class="product_expenses_json" value="[]">
				<button type="button" class="btn btn-xs btn-default pop-product-expenses-btn" title="@lang('lang_v1.product_expenses')" style="margin-top:4px;width:100%;">
					<i class="fa fa-truck"></i> @lang('lang_v1.product_expenses')
					<span class="pop-pe-badge label label-warning hide">0</span>
				</button>
			@endif
			</div>
		</td>
		<td class="hide vspos-pos-hidden-fields">
			{!! Form::hidden("products[$row_count][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}
			{!! Form::hidden("products[$row_count][tax_id]", $tax_id, ['class' => 'tax_id']); !!}
			@if(!empty($common_settings['enable_product_warranty']))
				{!! Form::hidden("products[$row_count][warranty_id]", $warranty_id, ['class' => 'warranty_id']); !!}
			@endif
			{!! Form::hidden("products[$row_count][sell_line_note]", $sell_line_note, ['class' => 'pos_sell_line_note']); !!}
		</td>
	@endif
	@if(!empty($is_direct_sell))
		@if(!empty($pos_settings['inline_service_staff']))
			<td>
				<div class="form-group">
					<div class="input-group">
						{!! Form::select("products[" . $row_count . "][res_service_staff_id]", $waiters, !empty($product->res_service_staff_id) ? $product->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
					</div>
				</div>
			</td>
		@endif
		@php
			$pos_unit_price = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price;

			if(!empty($so_line) && $action !== 'edit') {
				$pos_unit_price = $so_line->unit_price_before_discount;
			}
		@endphp
		<td class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
			<input type="text" name="products[{{$row_count}}][unit_price]" class="form-control pos_unit_price input_number mousetrap" value="{{@num_format($pos_unit_price)}}" @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price)])}}" @endif> 

			@if(!empty($last_sell_line))
				<br>
				<small class="text-muted">@lang('lang_v1.prev_unit_price'): @format_currency($last_sell_line->unit_price_before_discount)</small>
			@endif
		</td>
		<td @if(!$edit_discount) class="hide" @endif>
			{!! Form::text("products[$row_count][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number row_discount_amount']); !!}<br>
			{!! Form::select("products[$row_count][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type']); !!}
			@if(!empty($discount))
				<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
			@endif

			@if(!empty($last_sell_line))
				<br>
				<small class="text-muted">
					@lang('lang_v1.prev_discount'): 
					@if($last_sell_line->line_discount_type == 'percentage')
						{{@num_format($last_sell_line->line_discount_amount)}}%
					@else
						@format_currency($last_sell_line->line_discount_amount)
					@endif
				</small>
			@endif
		</td>
		<td class="text-center {{$hide_tax}}">
			{!! Form::hidden("products[$row_count][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}
		
			{!! Form::select("products[$row_count][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); !!}
		</td>

	@else
		@if(!empty($pos_settings['inline_service_staff']))
			<td>
				<div class="form-group">
					<div class="input-group">
						{!! Form::select("products[" . $row_count . "][res_service_staff_id]", $waiters, !empty($product->res_service_staff_id) ? $product->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
					</div>
				</div>
			</td>
		@endif
	@endif
	<td class="{{$hide_tax}} @if(empty($is_direct_sell)) hide @endif">
		<input type="text" style="width: auto" name="products[{{$row_count}}][unit_price_inc_tax]" class="form-control pos_unit_price_inc_tax input_number" value="{{@num_format($unit_price_inc_tax)}}" @if(!$edit_price) readonly @endif @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$unit_price_inc_tax}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($unit_price_inc_tax)])}}" @endif>
	</td>
	@if(!empty($common_settings['enable_product_warranty']) && !empty($is_direct_sell))
		<td>
			{!! Form::select("products[$row_count][warranty_id]", $warranties, $warranty_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control']); !!}
		</td>
	@endif
	<td class="pos-td-subtotal text-right">
		@php
			$subtotal_type = !empty($pos_settings['is_pos_subtotal_editable']) ? 'text' : 'hidden';

		@endphp
		<div class="vspos-line-total">
		<input style="width: auto" type="{{$subtotal_type}}" class="form-control pos_line_total @if(!empty($pos_settings['is_pos_subtotal_editable'])) input_number @endif" value="{{@num_format($product->quantity_ordered*$unit_price_inc_tax )}}">
		<span class="display_currency pos_line_total_text @if(!empty($pos_settings['is_pos_subtotal_editable'])) hide @endif" data-currency_symbol="true">{{$product->quantity_ordered*$unit_price_inc_tax}}</span>
		</div>
	</td>
	<td class="pos-td-action text-center v-center">
		@if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
		<button type="button" class="vspos-line-exchange @if($is_inline_return) hide @endif" title="Exchange / Trade-In" aria-label="Exchange / Trade-In">
			<i class="fas fa-exchange-alt"></i>
		</button>
		@endif
		<button type="button" class="pos_remove_row vspos-line-remove" aria-label="Remove item">
			<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
		</button>
	</td>
</tr>