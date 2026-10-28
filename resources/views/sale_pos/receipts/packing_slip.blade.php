{{-- QPOS Packing Slip — 80mm thermal --}}
@php
	$ps_lines = is_array($receipt_details->lines ?? null) ? $receipt_details->lines : [];
	$ps_qty_total = 0;
	foreach ($ps_lines as $__line) {
		$ps_qty_total += (float) str_replace(',', '', (string) ($__line['quantity'] ?? 0));
	}
@endphp
<div class="ps-wrap" data-paper-width="80">
<style>
	@page {
		size: 80mm auto;
		margin: 0;
	}
	.ps-wrap {
		width: 80mm;
		max-width: 80mm;
		margin: 0 auto;
		padding: 3mm 2.5mm 4mm;
		color: #111;
		font-family: Arial, Helvetica, sans-serif;
		font-size: 11px;
		line-height: 1.35;
		background: #fff;
		box-sizing: border-box;
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
	}
	.ps-wrap * { box-sizing: border-box; }
	.ps-brand {
		text-align: center;
		margin: 0 0 6px;
	}
	.ps-logo {
		max-width: 48mm;
		max-height: 16mm;
		width: auto;
		height: auto;
		margin: 0 auto 4px;
		display: block;
		object-fit: contain;
	}
	.ps-name {
		font-size: 13px;
		font-weight: 800;
		letter-spacing: 0.2px;
		margin: 0 0 2px;
	}
	.ps-muted {
		color: #333;
		font-size: 9.5px;
		line-height: 1.4;
	}
	.ps-title {
		text-align: center;
		border-top: 1.5px dashed #111;
		border-bottom: 1.5px dashed #111;
		padding: 6px 0;
		margin: 6px 0;
	}
	.ps-title h1 {
		margin: 0;
		font-size: 12px;
		font-weight: 800;
		letter-spacing: 0.6px;
		text-transform: uppercase;
	}
	.ps-meta {
		margin: 0 0 6px;
		font-size: 10.5px;
	}
	.ps-meta-row {
		display: flex;
		justify-content: space-between;
		gap: 6px;
		margin: 2px 0;
	}
	.ps-meta-row .lbl { color: #555; font-weight: 600; }
	.ps-meta-row .val { font-weight: 700; text-align: right; }
	.ps-block {
		border: 1px solid #ddd;
		padding: 5px 6px;
		margin: 0 0 5px;
		border-radius: 2px;
	}
	.ps-block-label {
		font-size: 9px;
		font-weight: 800;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		color: #666;
		margin: 0 0 3px;
	}
	.ps-block-name {
		font-size: 11px;
		font-weight: 700;
		margin: 0 0 2px;
	}
	.ps-table {
		width: 100%;
		border-collapse: collapse;
		margin: 4px 0 6px;
		font-size: 10.5px;
	}
	.ps-table thead th {
		background: #111 !important;
		color: #fff !important;
		font-size: 9px;
		font-weight: 700;
		letter-spacing: 0.04em;
		text-transform: uppercase;
		padding: 4px 3px;
		text-align: left;
		border: none;
	}
	.ps-table thead th.ps-num,
	.ps-table tbody td.ps-num {
		width: 12%;
		text-align: center;
	}
	.ps-table thead th.ps-qty,
	.ps-table tbody td.ps-qty {
		width: 28%;
		text-align: right;
	}
	.ps-table tbody td {
		border-bottom: 1px dashed #ccc;
		padding: 5px 3px;
		vertical-align: top;
		color: #111;
	}
	.ps-prod-name { font-weight: 700; font-size: 10.5px; word-break: break-word; }
	.ps-prod-meta {
		color: #555;
		font-size: 9px;
		margin-top: 1px;
		word-break: break-word;
	}
	.ps-summary {
		border-top: 1.5px dashed #111;
		border-bottom: 1.5px dashed #111;
		padding: 5px 0;
		margin: 4px 0 6px;
		font-size: 11px;
		font-weight: 700;
		display: flex;
		justify-content: space-between;
	}
	.ps-note {
		border: 1px dashed #999;
		padding: 5px 6px;
		margin: 0 0 8px;
		font-size: 9.5px;
		color: #333;
		text-align: center;
	}
	.ps-signs {
		margin-top: 10px;
		font-size: 9.5px;
	}
	.ps-sign {
		margin-bottom: 14px;
	}
	.ps-sign-line {
		border-top: 1px solid #111;
		padding-top: 4px;
		font-weight: 700;
		color: #333;
		width: 70%;
	}
	.ps-sign-sub {
		font-size: 9px;
		color: #666;
		margin-top: 2px;
	}
	.ps-barcode {
		text-align: center;
		margin-top: 8px;
	}
	.ps-barcode img {
		max-width: 68mm;
		height: auto;
	}
	.ps-footer {
		margin-top: 8px;
		padding-top: 6px;
		border-top: 1px dashed #ccc;
		color: #555;
		font-size: 9px;
		text-align: center;
	}
	@media print {
		.ps-wrap {
			width: 80mm !important;
			max-width: 80mm !important;
			color: #000 !important;
		}
		.ps-table thead th {
			background: #111 !important;
			color: #fff !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
	}
</style>

	<div class="ps-brand">
		@if(!empty($receipt_details->logo))
			<img class="ps-logo" src="{{ $receipt_details->logo }}" alt="">
		@endif
		@if(!empty($receipt_details->display_name))
			<p class="ps-name">{{ $receipt_details->display_name }}</p>
		@endif
		<div class="ps-muted">
			@if(!empty($receipt_details->address))
				{!! $receipt_details->address !!}
			@endif
			@if(!empty($receipt_details->contact))
				<br>{!! $receipt_details->contact !!}
			@endif
			@if(!empty($receipt_details->tax_info1))
				<br>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
			@endif
		</div>
	</div>

	<div class="ps-title">
		<h1>@lang('lang_v1.packing_slip')</h1>
	</div>

	<div class="ps-meta">
		<div class="ps-meta-row">
			<span class="lbl">
				@if(!empty($receipt_details->invoice_no_prefix))
					{!! strip_tags($receipt_details->invoice_no_prefix) !!}
				@else
					@lang('sale.invoice_no')
				@endif
			</span>
			<span class="val">{{ $receipt_details->invoice_no }}</span>
		</div>
		@if(!empty($receipt_details->invoice_date))
			<div class="ps-meta-row">
				<span class="lbl">{{ $receipt_details->date_label ?? __('messages.date') }}</span>
				<span class="val">{{ $receipt_details->invoice_date }}</span>
			</div>
		@endif
	</div>

	<div class="ps-block">
		<div class="ps-block-label">{{ $receipt_details->customer_label ?? __('contact.customer') }}</div>
		@if(!empty($receipt_details->customer_name))
			<div class="ps-block-name">{{ $receipt_details->customer_name }}</div>
		@endif
		<div class="ps-muted">
			@if(!empty($receipt_details->customer_info))
				{!! $receipt_details->customer_info !!}
			@endif
			@if(!empty($receipt_details->client_id_label))
				<br>{{ $receipt_details->client_id_label }} {{ $receipt_details->client_id }}
			@endif
		</div>
	</div>

	@if(!empty($receipt_details->shipping_address) || !empty($receipt_details->shipping_custom_field_1_value))
		<div class="ps-block">
			<div class="ps-block-label">@lang('lang_v1.shipping_address')</div>
			<div class="ps-muted">
				@if(!empty($receipt_details->shipping_address))
					{!! $receipt_details->shipping_address !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_1_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_1_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_1_value ?? '' !!}
				@endif
			</div>
		</div>
	@endif

	<table class="ps-table">
		<thead>
			<tr>
				<th class="ps-num">#</th>
				<th>{{ $receipt_details->table_product_label ?? __('sale.product') }}</th>
				<th class="ps-qty">{{ $receipt_details->table_qty_label ?? __('lang_v1.quantity') }}</th>
			</tr>
		</thead>
		<tbody>
			@forelse($ps_lines as $line)
				<tr>
					<td class="ps-num">{{ $loop->iteration }}</td>
					<td>
						<div class="ps-prod-name">
							{{ $line['name'] }}
							@if(!empty($line['product_variation']) || !empty($line['variation']))
								{{ $line['product_variation'] }} {{ $line['variation'] }}
							@endif
						</div>
						<div class="ps-prod-meta">
							@if(!empty($line['sub_sku'])) SKU: {{ $line['sub_sku'] }} @endif
							@if(!empty($line['brand'])) · {{ $line['brand'] }} @endif
							@if(!empty($line['sell_line_note'])) · {!! $line['sell_line_note'] !!} @endif
							@if(!empty($line['lot_number']))
								<br>{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}
							@endif
						</div>
					</td>
					<td class="ps-qty">
						<strong>{{ $line['quantity'] }}</strong>
						@if(!empty($line['units'])) {{ $line['units'] }} @endif
					</td>
				</tr>
				@if(!empty($line['modifiers']))
					@foreach($line['modifiers'] as $modifier)
						<tr>
							<td class="ps-num"></td>
							<td>
								<div class="ps-prod-meta">
									+ {{ $modifier['name'] }} {{ $modifier['variation'] ?? '' }}
									@if(!empty($modifier['sub_sku'])) ({{ $modifier['sub_sku'] }}) @endif
								</div>
							</td>
							<td class="ps-qty">{{ $modifier['quantity'] }} {{ $modifier['units'] ?? '' }}</td>
						</tr>
					@endforeach
				@endif
			@empty
				<tr>
					<td colspan="3" style="text-align:center;color:#737373;padding:10px;">—</td>
				</tr>
			@endforelse
		</tbody>
	</table>

	<div class="ps-summary">
		<span>@lang('lang_v1.items'): {{ count($ps_lines) }}</span>
		<span>Qty: {{ rtrim(rtrim(number_format($ps_qty_total, 3, '.', ''), '0'), '.') }}</span>
	</div>

	<div class="ps-note">
		@lang('lang_v1.above_mentioned_items_received_in_good_condition')
	</div>

	<div class="ps-signs">
		<div class="ps-sign">
			<div class="ps-sign-line">@lang('lang_v1.authorized_signatory')</div>
			<div class="ps-sign-sub">@lang('business.business')</div>
		</div>
		<div class="ps-sign">
			<div class="ps-sign-line">@lang('lang_v1.received_by')</div>
			<div class="ps-sign-sub">@lang('lang_v1.date'): _______________</div>
		</div>
	</div>

	@if(!empty($receipt_details->show_barcode))
		<div class="ps-barcode">
			<img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}" alt="barcode">
		</div>
	@endif

	@if(!empty($receipt_details->footer_text))
		<div class="ps-footer">
			{!! $receipt_details->footer_text !!}
		</div>
	@endif
</div>
