@forelse($products as $product)
	@php
		$retail_price = isset($product->retail_price_baseline) ? $product->retail_price_baseline : $product->selling_price;
		$group_price_map = [];
		if (!empty($product->group_prices)) {
			foreach ($product->group_prices as $group_price) {
				if (array_key_exists($group_price->price_group_id, $allowed_group_prices)) {
					$group_price_map[(string) $group_price->price_group_id] = (float) $group_price->calculated_price;
				}
			}
		}
		$display_price = $product->selling_price;
	@endphp
	<div class="col-md-3 col-xs-4 no-print !tw-px-[3px]">
		<div class="product_box tw-w-full tw-mb-1 tw-text-center tw-cursor-pointer tw-font-semibold tw-bg-white tw-rounded-lg tw-p-1 tw-border tw-border-[#e5e7eb] tw-shadow-[0_1px_3px_rgba(0,0,0,0.06)] tw-transition-all tw-duration-150 hover:-tw-translate-y-px hover:tw-shadow-[0_4px_12px_rgba(0,0,0,0.1)] active:tw-scale-[0.97] @if(empty($for_pop) && $product->enable_stock && $product->qty_available <= 0) product_out_of_stock !tw-bg-[#f3f4f6] tw-opacity-60 @endif"
			tabindex="0"
			role="button"
			data-variation_id="{{$product->id}}"
			data-name="{{$product->name}}"
			data-tyre_size="{{$product->tyre_size ?? ''}}"
			data-type="{{$product->type}}"
			data-variation="{{$product->type == 'variable' ? $product->variation : ''}}"
			data-sku="{{$product->sub_sku}}"
			data-unit="{{$product->unit ?? ''}}"
			data-selling_price="{{$display_price}}"
			data-retail_price="{{$retail_price}}"
			data-group_prices="{{ e(json_encode($group_price_map)) }}"
			data-last_purchase_price="{{ $product->last_purchased_price ?? '' }}"
			data-enable_stock="{{$product->enable_stock ? 1 : 0}}"
			data-qty_available="{{$product->qty_available}}"
			title="{{ \App\Product::formatNameWithTyreSize($product->name, $product->tyre_size ?? null) }} @if($product->type == 'variable')- {{$product->variation}} @endif {{ '(' . $product->sub_sku . ')'}} @if(!empty($show_prices)) @lang('lang_v1.default') - @format_currency($retail_price) @foreach($group_price_map as $gid => $gprice) @if(array_key_exists($gid, $allowed_group_prices)) {{$allowed_group_prices[$gid]}} - @format_currency($gprice) @endif @endforeach @endif">

		<div class="image-container tw-h-[58px] tw-mx-auto tw-w-full tw-mb-[3px]"
			style="background-image: url(
					@if(count($product->media) > 0)
						{{$product->media->first()->display_url}}
					@elseif(!empty($product->product_image))
						{{asset('/uploads/img/' . rawurlencode($product->product_image))}}
					@else
						{{asset('/img/default.png')}}
					@endif
				);
			background-repeat: no-repeat; background-position: center;
			background-size: contain;">

		</div>

		<div class="text_div tw-mt-0.5">
			<small class="text text-muted tw-w-full tw-line-clamp-1 !tw-leading-[13px] tw-max-h-[13px] !tw-text-[11px]">{{ \App\Product::formatNameWithTyreSize($product->name, $product->tyre_size ?? null) }}
			@if($product->type == 'variable')
				- {{$product->variation}}
			@endif
			</small>

			<small class="text-muted">
				({{$product->sub_sku}})
			</small><br>
			<small class="text-muted" style="font-size: 10px;">
				@if($product->enable_stock)
				{{ @num_format($product->qty_available) }} {{$product->unit}} @lang('lang_v1.in_stock')
				@else
					--
				@endif
			</small><br>
			@if(!empty($show_prices))
				<span class="product_price !tw-text-[11px] tw-font-bold tw-text-[#15803d] tw-leading-[13px] tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis">@format_currency($display_price)</span>
				@if(!empty($for_pop) && isset($product->last_purchased_price) && $product->last_purchased_price !== null && $product->last_purchased_price !== '')
					<br>
					<small class="vspos-last-buy-price tw-text-[10px] tw-font-semibold tw-text-[#b45309] tw-leading-[12px]">
						Last: @format_currency($product->last_purchased_price)
					</small>
				@endif
			@endif
		</div>

		</div>
	</div>
@empty
	<input type="hidden" id="no_products_found">
	<div class="col-md-12 vspos-no-products">
		<h4 class="text-center vspos-no-products-title">
			@if(!empty($term))
				No matching products found
			@else
				@lang('lang_v1.no_products_to_display')
			@endif
		</h4>
		@if(empty($term))
		<p class="text-center" style="margin-bottom:24px;">
			<button type="button" class="btn btn-sm btn-default" id="vspos_retry_products">
				Retry
			</button>
		</p>
		@endif
	</div>
@endforelse
