@php
	$biz_name = session('business.name');
@endphp
<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content product-view-sheet">
		<style>
			/* Screen only — never leak dark colors into print / PDF */
			@media screen {
				.product-view-sheet {
					color: #e2e8f0;
					display: flex;
					flex-direction: column;
					max-height: calc(100vh - 32px);
				}
				.product-view-sheet > .modal-header,
				.product-view-sheet > .modal-footer {
					flex: 0 0 auto;
				}
				.product-view-sheet > .modal-body {
					flex: 1 1 auto;
					overflow-x: hidden !important;
					overflow-y: auto !important;
					max-height: none;
					padding-bottom: 20px !important;
					-webkit-overflow-scrolling: touch;
				}
				.product-view-sheet .pvs-print-head { display: none; }
				.product-view-sheet .pvs-meta {
					display: grid;
					grid-template-columns: 1fr 1fr minmax(120px, 180px);
					gap: 16px 24px;
					margin-bottom: 16px;
					align-items: start;
				}
				@media (max-width: 767px) {
					.product-view-sheet .pvs-meta {
						grid-template-columns: 1fr;
					}
					.product-view-sheet .pvs-meta-col:last-child {
						text-align: left !important;
					}
				}
				.product-view-sheet .pvs-meta-col p {
					margin: 0 0 6px;
					font-size: 13px;
					line-height: 1.45;
					color: #e2e8f0;
					word-break: break-word;
				}
				.product-view-sheet .pvs-meta-col b {
					color: #f8fafc;
					font-weight: 700;
				}
				.product-view-sheet .pvs-thumb {
					width: 160px;
					height: 160px;
					max-width: 100%;
					object-fit: cover;
					border: 1px solid #333;
					border-radius: 6px;
					background: #1a1a1a;
				}
				.product-view-sheet .pvs-section-title {
					font-size: 14px;
					font-weight: 700;
					margin: 20px 0 10px;
					padding-bottom: 6px;
					border-bottom: 2px solid #444;
					color: #f8fafc;
				}
				.product-view-sheet .pvs-table-scroll {
					width: 100%;
					max-width: 100%;
					overflow-x: auto !important;
					overflow-y: visible !important;
					-webkit-overflow-scrolling: touch;
					margin: 0 0 16px;
					border: 1px solid #333;
					border-radius: 4px;
				}
				.product-view-sheet .table {
					width: 100%;
					min-width: 720px;
					margin-bottom: 0;
					font-size: 12px;
					color: #e2e8f0;
					background: transparent;
				}
				.product-view-sheet .table > thead > tr > th,
				.product-view-sheet .table > tbody > tr > th,
				.product-view-sheet .table > tbody > tr > td {
					padding: 8px 10px;
					border: 1px solid #333 !important;
					vertical-align: middle;
					background: #0a0a0a !important;
					color: #e2e8f0 !important;
				}
				.product-view-sheet .table.bg-gray,
				.product-view-sheet .table > thead > tr.bg-green > th,
				.product-view-sheet tr.bg-green th {
					background: #1a1a1a !important;
					color: #f8fafc !important;
				}
				.product-view-sheet .pvs-table {
					width: max-content;
					min-width: 100%;
					border-collapse: collapse;
					font-size: 12px;
					margin-bottom: 0;
					color: #e2e8f0;
					background: transparent;
				}
				.product-view-sheet .pvs-table th,
				.product-view-sheet .pvs-table td {
					border: 1px solid #333;
					padding: 8px 10px;
					vertical-align: middle;
					text-align: left;
					background: #0a0a0a;
					color: #e2e8f0;
				}
				.product-view-sheet .pvs-table th {
					background: #1a1a1a;
					color: #f8fafc;
					font-weight: 700;
					white-space: nowrap;
				}
				.product-view-sheet .pvs-table th small {
					display: block;
					font-weight: 500;
					color: #a3a3a3;
					white-space: nowrap;
				}
				.product-view-sheet .pvs-table .num { text-align: right; white-space: nowrap; }
				.product-view-sheet .pvs-table td:nth-child(2) {
					min-width: 140px;
					white-space: normal;
				}
				.product-view-sheet .pvs-expenses {
					background: #141414;
					border: 1px solid #333;
					border-radius: 6px;
					padding: 14px 16px 16px;
					margin: 4px 0 12px;
					color: #e2e8f0;
					overflow: visible;
				}
				.product-view-sheet .pvs-expenses h5 {
					margin: 0 0 12px;
					font-size: 13px;
					font-weight: 700;
					color: #f8fafc;
				}
				.product-view-sheet .pvs-expenses h5 small,
				.product-view-sheet .pvs-expenses .text-muted {
					color: #a3a3a3 !important;
				}
				.product-view-sheet .pvs-exp-block {
					margin-bottom: 14px;
				}
				.product-view-sheet .pvs-exp-block:last-child {
					margin-bottom: 0;
				}
				.product-view-sheet .pvs-exp-head {
					font-size: 12px;
					margin-bottom: 8px;
					line-height: 1.4;
				}
				.product-view-sheet .pvs-exp-grid {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
					gap: 10px;
				}
				.product-view-sheet .pvs-exp-item {
					border: 1px solid #333;
					background: #1a1a1a;
					border-radius: 4px;
					padding: 10px 12px;
					color: #e2e8f0;
					min-height: 58px;
					box-sizing: border-box;
				}
				.product-view-sheet .pvs-exp-item .lbl {
					font-size: 11px;
					color: #a3a3a3;
					margin-bottom: 4px;
					line-height: 1.3;
				}
				.product-view-sheet .pvs-exp-item .val {
					font-size: 13px;
					font-weight: 700;
					color: #f8fafc;
					line-height: 1.35;
					word-break: break-word;
				}
				.product-view-sheet .thumbnail {
					background: #1a1a1a;
					border-color: #333;
				}
				.product-view-sheet .row {
					margin-left: 0;
					margin-right: 0;
				}
				.product-view-sheet .pvs-price-wrap .table-responsive,
				.product-view-sheet .pvs-price-wrap .pvs-table-scroll {
					width: 100%;
					max-width: 100%;
					overflow-x: auto !important;
					margin-bottom: 12px;
				}
				.product-view-sheet .pvs-price-wrap .table {
					margin-bottom: 0;
				}
				.product-view-sheet .pvs-price-wrap br {
					display: none;
				}
				.product-view-sheet .pvs-price-wrap h4 {
					margin: 0 0 10px;
					color: #f8fafc;
					font-size: 13px;
				}
			}

			/* Print / PDF: always light paper */
			@media print {
				.no-print { display: none !important; }
				html, body {
					background: #ffffff !important;
					color: #000000 !important;
				}
				.product-view-sheet,
				.product-view-sheet.modal-content,
				.product-view-sheet .modal-body,
				.product-view-sheet .modal-header,
				.product-view-sheet .modal-footer {
					background: #ffffff !important;
					background-color: #ffffff !important;
					background-image: none !important;
					color: #000000 !important;
					box-shadow: none !important;
					border: none !important;
				}
				.product-view-sheet .modal-header,
				.product-view-sheet .modal-footer { display: none !important; }
				.product-view-sheet .pvs-print-head {
					display: block !important;
					margin-bottom: 14px;
					padding-bottom: 10px;
					border-bottom: 2px solid #000 !important;
					color: #000 !important;
					background: #fff !important;
				}
				.product-view-sheet .pvs-print-head h2 {
					margin: 0 0 4px;
					font-size: 18px;
					color: #000 !important;
				}
				.product-view-sheet .pvs-print-head .sub {
					font-size: 11px;
					color: #333 !important;
				}
				.product-view-sheet .modal-body { padding: 0 !important; overflow: visible !important; max-height: none !important; }
				.product-view-sheet .pvs-table-scroll {
					overflow: visible !important;
					border: none !important;
				}
				.product-view-sheet .pvs-meta {
					display: grid;
					grid-template-columns: 1fr 1fr 140px;
					gap: 12px 20px;
					margin-bottom: 14px;
					page-break-inside: avoid;
				}
				.product-view-sheet .pvs-meta-col p {
					margin: 0 0 6px;
					font-size: 12px;
					line-height: 1.45;
					color: #000 !important;
				}
				.product-view-sheet .pvs-meta-col b,
				.product-view-sheet .pvs-section-title,
				.product-view-sheet .pvs-expenses,
				.product-view-sheet .pvs-expenses h5,
				.product-view-sheet .pvs-expenses h5 small,
				.product-view-sheet .pvs-expenses .text-muted,
				.product-view-sheet .pvs-exp-item,
				.product-view-sheet .pvs-exp-item .lbl,
				.product-view-sheet .pvs-exp-item .val,
				.product-view-sheet span,
				.product-view-sheet td,
				.product-view-sheet th,
				.product-view-sheet p,
				.product-view-sheet div,
				.product-view-sheet strong,
				.product-view-sheet small {
					color: #000000 !important;
				}
				.product-view-sheet .pvs-section-title {
					font-size: 14px;
					font-weight: 700;
					margin: 16px 0 8px;
					padding-bottom: 4px;
					border-bottom: 2px solid #000 !important;
					background: transparent !important;
				}
				.product-view-sheet .pvs-thumb {
					width: 120px;
					height: 120px;
					object-fit: cover;
					border: 1px solid #ccc !important;
					background: #fff !important;
					border-radius: 4px;
				}
				.product-view-sheet .pvs-table,
				.product-view-sheet .table {
					width: 100%;
					border-collapse: collapse;
					font-size: 11px;
					page-break-inside: avoid;
					background: #fff !important;
					color: #000 !important;
					margin-bottom: 12px;
				}
				.product-view-sheet .pvs-table th,
				.product-view-sheet .pvs-table td,
				.product-view-sheet .table > thead > tr > th,
				.product-view-sheet .table > tbody > tr > th,
				.product-view-sheet .table > tbody > tr > td {
					border: 1px solid #999 !important;
					padding: 6px 8px !important;
					background: #fff !important;
					color: #000 !important;
					vertical-align: middle;
				}
				.product-view-sheet .pvs-table th,
				.product-view-sheet .bg-green,
				.product-view-sheet .bg-gray,
				.product-view-sheet tr.bg-green th,
				.product-view-sheet .table.bg-gray,
				.product-view-sheet .table > thead > tr.bg-green > th {
					background: #eeeeee !important;
					color: #000 !important;
					font-weight: 700;
					-webkit-print-color-adjust: exact;
					print-color-adjust: exact;
				}
				.product-view-sheet .pvs-table .num { text-align: right; white-space: nowrap; }
				.product-view-sheet .pvs-expenses {
					background: #f7f7f7 !important;
					border: 1px solid #ccc !important;
					border-radius: 4px;
					padding: 10px 12px;
					margin-top: 8px;
				}
				.product-view-sheet .pvs-expenses h5 {
					margin: 0 0 8px;
					font-size: 13px;
					font-weight: 700;
				}
				.product-view-sheet .pvs-exp-grid {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
					gap: 8px;
				}
				.product-view-sheet .pvs-exp-item {
					background: #fff !important;
					border: 1px solid #ccc !important;
					border-radius: 4px;
					padding: 8px;
				}
				.product-view-sheet .pvs-exp-item .lbl {
					font-size: 11px;
					color: #555 !important;
				}
				.product-view-sheet .pvs-exp-item .val {
					font-size: 13px;
					font-weight: 700;
				}
				.product-view-sheet .thumbnail {
					border: 0 !important;
					box-shadow: none !important;
					background: transparent !important;
				}
			}
		</style>

		<div class="modal-header">
			<button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="modalTitle">{{ $product->name_with_tyre_size }}</h4>
		</div>

		<div class="modal-body">
			<div class="pvs-print-head">
				<h2>{{ $product->name_with_tyre_size }}</h2>
				<div class="sub">
					{{ $biz_name }}
					&nbsp;·&nbsp; @lang('product.sku'): {{ $product->sku }}
					&nbsp;·&nbsp; {{ @format_datetime('now') }}
				</div>
			</div>

			@php
				$custom_labels = session('business.custom_labels');
				if (is_string($custom_labels)) {
					$custom_labels = json_decode($custom_labels, true);
				}
				if (! is_array($custom_labels)) {
					$custom_labels = [];
				}
				$cf_defaults = [
					1 => __('lang_v1.costcode'),
					2 => __('lang_v1.supplier_code'),
				];
				$expiry_array = ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ];
				$tax_type = ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')];
			@endphp

			<div class="pvs-meta">
				<div class="pvs-meta-col">
					<p><b>@lang('product.sku'):</b> {{ $product->sku }}</p>
					@if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
					<p><b>@lang('product.tyre_size'):</b> {{ $product->tyre_size ?: '—' }}</p>
					@endif
					@foreach(\App\Utils\ShopModeUtil::extraProductFields() as $shop_col => $meta)
						@if(!empty($product->{$shop_col}))
							<p><b>@lang($meta['label']):</b> {{ $product->{$shop_col} }}</p>
						@endif
					@endforeach
					<p><b>@lang('product.brand'):</b> {{ $product->brand->name ?? '—' }}</p>
					<p><b>@lang('lang_v1.supplier'):</b>
						@if(!empty($product->supplier))
							{{ $product->supplier->supplier_business_name ?: $product->supplier->name }}
						@else
							—
						@endif
					</p>
					<p><b>@lang('product.unit'):</b> {{ $product->unit->short_name ?? '—' }}</p>
					<p><b>@lang('product.barcode_type'):</b> {{ $product->barcode_type ?? '—' }}</p>
					<p><b>@lang('lang_v1.available_in_locations'):</b>
						@if(count($product->product_locations) > 0)
							{{ implode(', ', $product->product_locations->pluck('name')->toArray()) }}
						@else
							@lang('lang_v1.none')
						@endif
					</p>
					@for($i = 1; $i <= 20; $i++)
						@php
							$db_field = 'product_custom_field'.$i;
							$label = 'custom_field_'.$i;
							$cf_label = $custom_labels['product'][$label] ?? ($cf_defaults[$i] ?? '');
						@endphp
						@if(!empty($product->$db_field))
							<p><b>{{ $cf_label }}:</b> {{ $product->$db_field }}</p>
						@endif
					@endfor
					@if(!empty($product->media->first()))
						<p class="no-print">
							<strong>@lang('lang_v1.product_brochure'):</strong>
							<a href="{{ $product->media->first()->display_url }}" download="{{ $product->media->first()->display_name }}">
								{{ $product->media->first()->display_name }}
							</a>
						</p>
					@endif
				</div>

				<div class="pvs-meta-col">
					<p><b>@lang('product.category'):</b> {{ $product->category->name ?? '—' }}</p>
					<p><b>@lang('product.sub_category'):</b> {{ $product->sub_category->name ?? '—' }}</p>
					<p><b>@lang('product.manage_stock'):</b> {{ $product->enable_stock ? __('messages.yes') : __('messages.no') }}</p>
					@if($product->enable_stock)
						<p><b>@lang('product.alert_quantity'):</b> {{ $product->alert_quantity ?? '—' }}</p>
					@endif
					@if(!empty($product->warranty))
						<p><b>@lang('lang_v1.warranty'):</b> {{ $product->warranty->display_name }}</p>
					@endif
					<p><b>@lang('product.expires_in'):</b>
						@if(!empty($product->expiry_period) && !empty($product->expiry_period_type))
							{{ $product->expiry_period }} {{ $expiry_array[$product->expiry_period_type] }}
						@else
							{{ $expiry_array[''] }}
						@endif
					</p>
					@if($product->weight)
						<p><b>@lang('lang_v1.weight'):</b> {{ $product->weight }}</p>
					@endif
					<p><b>@lang('product.applicable_tax'):</b> {{ $product->product_tax->name ?? __('lang_v1.none') }}</p>
					<p><b>@lang('product.selling_price_tax_type'):</b> {{ $tax_type[$product->tax_type] }}</p>
					<p><b>@lang('product.product_type'):</b> @lang('lang_v1.' . $product->type)</p>
				</div>

				<div class="pvs-meta-col" style="text-align:right;">
					<img class="pvs-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
				</div>
			</div>

			@if(!empty($product->product_description))
				<div class="pvs-section-title">@lang('lang_v1.description')</div>
				<div>{!! $product->product_description !!}</div>
			@endif

			@if(!empty($rack_details) && count($rack_details))
				@if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
					<div class="pvs-section-title">@lang('lang_v1.rack_details')</div>
					<div class="table-responsive pvs-table-scroll">
					<table class="pvs-table">
						<thead>
							<tr>
								<th>@lang('business.location')</th>
								@if(session('business.enable_racks'))
									<th>@lang('lang_v1.rack')</th>
								@endif
								@if(session('business.enable_row'))
									<th>@lang('lang_v1.row')</th>
								@endif
								@if(session('business.enable_position'))
									<th>@lang('lang_v1.position')</th>
								@endif
							</tr>
						</thead>
						<tbody>
							@foreach($rack_details as $rd)
								<tr>
									<td>{{ $rd->name }}</td>
									@if(session('business.enable_racks'))
										<td>{{ $rd->rack }}</td>
									@endif
									@if(session('business.enable_row'))
										<td>{{ $rd->row }}</td>
									@endif
									@if(session('business.enable_position'))
										<td>{{ $rd->position }}</td>
									@endif
								</tr>
							@endforeach
						</tbody>
					</table>
					</div>
				@endif
			@endif

			<div class="pvs-section-title">Cost &amp; selling price</div>
			<div class="pvs-price-wrap">
			@if($product->type == 'single')
				@include('product.partials.single_product_details')
			@elseif($product->type == 'variable')
				@include('product.partials.variable_product_details')
			@elseif($product->type == 'combo')
				@include('product.partials.combo_product_details')
			@endif
			</div>

			@if($product->enable_stock == 1)
				<div class="pvs-section-title">@lang('lang_v1.product_stock_details')</div>
				<div id="view_product_stock_details" data-product_id="{{ $product->id }}"></div>
			@endif
		</div>

		<div class="modal-footer">
			<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print"
				aria-label="Print"
				onclick="$(this).closest('div.modal-content').printThis({
					importCSS: false,
					importStyle: true,
					loadCSS: '',
					printContainer: true,
					removeInline: false,
					printDelay: 333,
					header: null,
					footer: null,
					base: false
				});">
				<i class="fa fa-print"></i> @lang('messages.print')
			</button>
			<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div>
</div>
