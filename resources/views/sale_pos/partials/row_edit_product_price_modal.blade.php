<div class="modal-dialog vspos-row-edit-modal-dialog" role="document">
	<div class="modal-content vspos-pos-modal-content">
		<div class="modal-header vspos-pos-modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel">{{$product->product_name}} - {{$product->sub_sku}}</h4>
		</div>
		<div class="modal-body vspos-pos-modal-body">
			<div class="row">
				<div class="form-group col-xs-12 @if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
					@php
						$pos_unit_price = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price;
					@endphp
					<label>@lang('sale.unit_price')</label>
						{{-- Mirror only — must NOT share name with the POS row unit_price or PHP/submit can desync unit_price vs unit_price_inc_tax --}}
						<input type="text" class="form-control pos_unit_price input_number mousetrap vspos-modal-unit-price-mirror" value="{{@num_format($pos_unit_price)}}" @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price)])}}" @endif>
				</div>
				@if(!auth()->user()->can('edit_product_price_from_sale_screen'))
					<div class="form-group col-xs-12">
						<strong>@lang('sale.unit_price'):</strong> {{@num_format(!empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price)}}
					</div>
				@endif
				<div class="form-group col-xs-12 col-sm-6 @if(!$edit_discount) hide @endif">
					<label>@lang('sale.discount_type')</label>
						{!! Form::select("products[$row_count][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type']); !!}
				</div>
				<div class="form-group col-xs-12 col-sm-6 @if(!$edit_discount) hide @endif">
					<label>@lang('sale.discount_amount')</label>
						{!! Form::text("products[$row_count][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number row_discount_amount']); !!}
				</div>
				@if(!empty($discount))
					<div class="form-group col-xs-12">
						<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
					</div>
				@endif
				<div class="form-group col-xs-12 {{$hide_tax}}">
					<label>@lang('sale.tax')</label>
					<input type="hidden" class="item_tax vspos-modal-item-tax-mirror" value="{{ @num_format($item_tax) }}">
					{!! Form::select('', $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id vspos-modal-tax-mirror'], $tax_dropdown['attributes']); !!}
				</div>
				@if(!empty($warranties))
					<div class="form-group col-xs-12">
						<label>@lang('lang_v1.warranty')</label>
						{!! Form::select('', $warranties, $warranty_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control vspos-modal-warranty-mirror']); !!}
					</div>
				@endif

				@php
					$serial_per_qty_on = !empty($pos_settings['enable_serial_per_qty']) && !empty($product->enable_sr_no);
					$existing_serials = [];
					if ($serial_per_qty_on && !empty($sell_line_note)) {
						$existing_serials = preg_split("/\r\n|\n|\r/", trim($sell_line_note));
						$existing_serials = array_values(array_filter(array_map('trim', $existing_serials)));
					}
				@endphp

				@if($serial_per_qty_on)
					@if(!empty($mobile_shop_enabled) && !empty($product->enable_sr_no))
					<div class="form-group col-xs-12 vspos-mobile-imei-wrap"
						data-product-id="{{ $product->product_id ?? $product->id ?? '' }}"
						data-variation-id="{{ $product->variation_id ?? '' }}">
						<label><i class="fa fa-mobile-alt"></i> @lang('lang_v1.select_imei_from_stock')</label>
						<div class="vspos-mobile-imei-list"></div>
						<small class="help-block">@lang('lang_v1.mobile_shop_select_imei_help')</small>
					</div>
					@endif
					<div class="form-group col-xs-12 vspos-serial-per-qty-wrap" data-serial-per-qty="1" data-row-index="{{$row_count}}" data-sku="{{ $product->sub_sku }}">
						<label class="vspos-serial-title">
							<i class="fas fa-barcode"></i> @lang('lang_v1.serial_numbers')
							<span class="vspos-serial-count-label text-muted"></span>
						</label>
						<div class="vspos-serial-toolbar">
							<div class="input-group input-group-sm" style="max-width:280px;">
								<span class="input-group-addon">@lang('lang_v1.prefix')</span>
								<input type="text" class="form-control vspos-serial-prefix" value="{{ $product->sub_sku }}" placeholder="SN">
								<span class="input-group-btn">
									<button type="button" class="btn btn-primary vspos-serial-generate-btn">
										<i class="fas fa-magic"></i> @lang('lang_v1.generate')
									</button>
								</span>
							</div>
							<small class="help-block" style="margin:6px 0 0;">@lang('lang_v1.serial_per_qty_help')</small>
						</div>
						<div class="vspos-serial-list" data-existing='@json($existing_serials)'></div>
						<textarea class="form-control hide vspos-sell-line-note-sync" rows="2">{{$sell_line_note}}</textarea>
					</div>
				@else
					<div class="form-group col-xs-12">
			      		<label>@lang('lang_v1.description')</label>
			      		<textarea class="form-control" name="products[{{$row_count}}][sell_line_note]" rows="3">{{$sell_line_note}}</textarea>
			      		<p class="help-block">@lang('lang_v1.sell_line_description_help')</p>
			      	</div>
				@endif
			</div>
		</div>
		<div class="modal-footer vspos-pos-modal-footer">
			<button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div>
</div>