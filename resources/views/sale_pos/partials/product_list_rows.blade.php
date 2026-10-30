{{-- Full-width list table. Grid cards stay in product_list.blade.php. Inline layout keeps each row across the product panel even when the panel is a card grid. --}}
@if($products->isEmpty() && $products->currentPage() > 1)
	<input type="hidden" class="vspos-suggestion-end" id="suggestion_end" value="1">
@elseif($products->isEmpty())
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
@else
	@php
		$list_cols = '36px minmax(0,1fr) 110px 42px 46px 62px 70px 44px';
		$list_grid = 'display:grid !important;grid-template-columns:'.$list_cols.';column-gap:4px;align-items:center;width:100% !important;max-width:100% !important;box-sizing:border-box;';
		$clip = 'min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
	@endphp
	<div class="vspos-plist-wrap" style="display:block !important;width:100% !important;max-width:100% !important;min-width:0 !important;grid-column:1 / -1 !important;float:none !important;">
		<style>
			html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-head,
			html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .product_box.vspos-plist-row {
				grid-template-columns: 36px minmax(0,1fr) 110px 42px 46px 62px 70px 44px !important;
			}
			@media (max-width: 767px) {
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-head,
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .product_box.vspos-plist-row {
					grid-template-columns: 32px minmax(0,1fr) 64px 72px 46px !important;
				}
				.vspos-plist-sku,
				.vspos-plist-size,
				.vspos-plist-color,
				.vspos-plist-head-sku,
				.vspos-plist-head-size,
				.vspos-plist-head-color {
					display: none !important;
				}
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-stock,
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-price,
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-add,
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-head-stock,
				html.vspos-product-view-list body.vspos-touch-ui .vspos-touch-shell .vspos-plist-wrap .vspos-plist-head-price {
					display: block !important;
				}
			}
		</style>
		@if($products->currentPage() === 1)
			<div class="vspos-plist-head" id="vspos_plist_head" style="{{ $list_grid }}padding:6px 8px;background:var(--touch-panel,#111111);color:var(--touch-muted,#9ca3af);border-bottom:1px solid var(--touch-border,#2a2a2a);font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;">
				<span></span>
				<span style="{{ $clip }}">Product</span>
				<span class="vspos-plist-head-sku" style="{{ $clip }}">SKU / Barcode</span>
				<span class="vspos-plist-head-size" style="{{ $clip }}">Size</span>
				<span class="vspos-plist-head-color" style="{{ $clip }}">Color</span>
				<span class="vspos-plist-head-stock" style="{{ $clip }}text-align:right;">Stock</span>
				<span class="vspos-plist-head-price" style="{{ $clip }}text-align:right;">Price</span>
				<span></span>
			</div>
		@endif
		@foreach($products as $product)
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
				$list_size = trim((string) ($product->product_size ?? ''));
				$list_color = trim((string) ($product->product_color ?? ''));
				$list_category = trim((string) ($product->category_name ?? ''));
				if (count($product->media) > 0) {
					$list_thumb = $product->media->first()->display_url;
				} elseif (!empty($product->product_image)) {
					$list_thumb = asset('/uploads/img/' . rawurlencode($product->product_image));
				} else {
					$list_thumb = asset('/img/default.png');
				}
				$list_name = \App\Product::formatNameWithTyreSize($product->name, $product->tyre_size ?? null);
				if ($product->type == 'variable') {
					$list_name .= ' - ' . $product->variation;
				}
				$out = empty($for_pop) && $product->enable_stock && $product->qty_available <= 0;
			@endphp
			<div class="product_box vspos-plist-row @if($out) product_out_of_stock @endif"
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
				data-category="{{ $list_category }}"
				data-size="{{ $list_size }}"
				data-color="{{ $list_color }}"
				title="{{ $list_name }} ({{ $product->sub_sku }})"
				style="{{ $list_grid }}min-height:0 !important;height:auto !important;margin:0 !important;padding:6px 8px !important;border:0 !important;border-bottom:1px solid var(--touch-border,#2a2a2a) !important;border-radius:0 !important;background:transparent !important;box-shadow:none !important;color:var(--touch-text,#f5f5f5) !important;text-align:left !important;transform:none !important;float:none !important;cursor:pointer;">
				<img src="{{ $list_thumb }}" alt="" width="36" height="36" style="width:36px;height:36px;object-fit:contain;border-radius:8px;background:var(--touch-sunken,#141414);display:block;">
				<span style="min-width:0;display:flex;flex-direction:column;gap:1px;">
					<span class="vspos-plist-name" title="{{ $list_name }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;font-weight:700;line-height:1.25;">{{ $list_name }}</span>
					@if($list_category !== '')
						<span class="vspos-plist-cat" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;font-weight:600;line-height:1.2;color:var(--touch-muted,#9ca3af);">{{ $list_category }}</span>
					@endif
				</span>
				<span class="vspos-plist-sku" title="{{ $product->sub_sku }}" style="{{ $clip }}font-size:12px;font-weight:600;font-variant-numeric:tabular-nums;">{{ $product->sub_sku }}</span>
				<span class="vspos-plist-size" style="{{ $clip }}font-size:12px;font-weight:600;">{{ $list_size }}</span>
				<span class="vspos-plist-color" style="{{ $clip }}font-size:12px;font-weight:600;">{{ $list_color }}</span>
				<span class="vspos-plist-stock" style="{{ $clip }}text-align:right;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums;@if($out)color:#f87171;@endif">
					@if($product->enable_stock)
						{{ @num_format($product->qty_available) }} {{ $product->unit }}
					@else
						--
					@endif
				</span>
				@if(!empty($show_prices))
					<span class="product_price vspos-plist-price" style="{{ $clip }}text-align:right;color:var(--touch-price,#f5c518) !important;font-size:13px !important;font-weight:800 !important;">@format_currency($display_price)</span>
				@else
					<span class="vspos-plist-price"></span>
				@endif
				<span class="vspos-plist-add" aria-hidden="true" style="pointer-events:none;display:inline-flex;align-items:center;justify-content:center;height:26px;border-radius:8px;border:1px solid rgba(245,197,24,.55);background:rgba(245,197,24,.14);color:#f5c518;font-size:11px;font-weight:800;">Add</span>
			</div>
		@endforeach
	</div>
@endif
