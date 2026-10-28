{{-- QPOS Delivery Note — 80mm thermal --}}
@php
	$dn_lines = is_array($receipt_details->lines ?? null) ? $receipt_details->lines : [];
	$dn_qty_total = 0;
	foreach ($dn_lines as $__line) {
		$dn_qty_total += (float) str_replace(',', '', (string) ($__line['quantity'] ?? 0));
	}
	$dn_has_shipping = !empty($receipt_details->shipping_address)
		|| !empty($receipt_details->shipping_custom_field_1_value)
		|| !empty($receipt_details->shipping_custom_field_2_value)
		|| !empty($receipt_details->shipping_custom_field_3_value)
		|| !empty($receipt_details->shipping_custom_field_4_value)
		|| !empty($receipt_details->shipping_custom_field_5_value);

	// Avoid printing customer name twice (name + customer_info often repeat)
	$dn_cust_extra = (string) ($receipt_details->customer_info ?? '');
	if (!empty($receipt_details->customer_name) && $dn_cust_extra !== '') {
		$name = trim((string) $receipt_details->customer_name);
		$dn_cust_extra = preg_replace('/^\s*' . preg_quote($name, '/') . '\s*(<br\s*\/?>|\r?\n)+/iu', '', $dn_cust_extra);
		$dn_cust_extra = preg_replace('/^\s*' . preg_quote($name, '/') . '\s*$/iu', '', trim($dn_cust_extra));
	}
@endphp
<div class="dn-wrap" data-paper-width="80">
<style>
	@page {
		size: 80mm auto;
		margin: 0;
	}
	.dn-wrap {
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
	.dn-wrap * { box-sizing: border-box; }
	.dn-brand { text-align: center; margin: 0 0 6px; }
	.dn-logo {
		max-width: 48mm;
		max-height: 16mm;
		width: auto;
		height: auto;
		margin: 0 auto 4px;
		display: block;
		object-fit: contain;
	}
	.dn-name {
		font-size: 13px;
		font-weight: 800;
		letter-spacing: 0.2px;
		margin: 0 0 2px;
	}
	.dn-muted {
		color: #333;
		font-size: 9.5px;
		line-height: 1.4;
		word-break: break-word;
	}
	.dn-title {
		text-align: center;
		border-top: 1.5px dashed #111;
		border-bottom: 1.5px dashed #111;
		padding: 6px 2px;
		margin: 6px 0;
	}
	.dn-title h1 {
		margin: 0;
		font-size: 13px;
		font-weight: 800;
		letter-spacing: 1px;
		text-transform: uppercase;
		white-space: nowrap;
	}
	.dn-meta {
		margin: 0 0 6px;
		font-size: 10.5px;
	}
	.dn-meta-row {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		gap: 6px;
		margin: 2px 0;
	}
	.dn-meta-row .lbl { color: #555; font-weight: 600; flex: 0 0 auto; max-width: 42%; }
	.dn-meta-row .val { font-weight: 700; text-align: right; flex: 1 1 auto; word-break: break-word; }
	.dn-block {
		border: 1px solid #ddd;
		padding: 5px 6px;
		margin: 0 0 5px;
		border-radius: 2px;
	}
	.dn-block-label {
		font-size: 9px;
		font-weight: 800;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		color: #666;
		margin: 0 0 3px;
	}
	.dn-block-name {
		font-size: 11px;
		font-weight: 700;
		margin: 0 0 2px;
	}
	.dn-table {
		width: 100%;
		border-collapse: collapse;
		margin: 4px 0 6px;
		font-size: 10.5px;
		table-layout: fixed;
	}
	.dn-table thead th {
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
	.dn-table thead th.dn-num,
	.dn-table tbody td.dn-num {
		width: 8mm;
		text-align: center;
	}
	.dn-table thead th.dn-qty,
	.dn-table tbody td.dn-qty {
		width: 18mm;
		text-align: right;
	}
	.dn-table tbody td {
		border-bottom: 1px dashed #ccc;
		padding: 5px 3px;
		vertical-align: top;
		color: #111;
		word-break: break-word;
	}
	.dn-prod-name { font-weight: 700; font-size: 10.5px; }
	.dn-prod-meta {
		color: #555;
		font-size: 9px;
		margin-top: 1px;
	}
	.dn-summary {
		border-top: 1.5px dashed #111;
		border-bottom: 1.5px dashed #111;
		padding: 5px 0;
		margin: 4px 0 6px;
		font-size: 11px;
		font-weight: 700;
		display: flex;
		justify-content: space-between;
		gap: 8px;
	}
	.dn-note {
		border: 1px dashed #999;
		padding: 5px 6px;
		margin: 0 0 8px;
		font-size: 9.5px;
		color: #333;
		text-align: center;
	}
	.dn-signs { margin-top: 10px; font-size: 9.5px; }
	.dn-sign { margin-bottom: 14px; }
	.dn-sign-line {
		border-top: 1px solid #111;
		padding-top: 4px;
		font-weight: 700;
		color: #333;
		width: 70%;
	}
	.dn-sign-sub {
		font-size: 9px;
		color: #666;
		margin-top: 2px;
	}
	.dn-barcode { text-align: center; margin-top: 8px; }
	.dn-barcode img { max-width: 68mm; height: auto; }
	.dn-footer {
		margin-top: 8px;
		padding-top: 6px;
		border-top: 1px dashed #ccc;
		color: #555;
		font-size: 9px;
		text-align: center;
	}
	@media print {
		.dn-wrap {
			width: 80mm !important;
			max-width: 80mm !important;
			color: #000 !important;
		}
		.dn-table thead th {
			background: #111 !important;
			color: #fff !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
	}
</style>

	<div class="dn-brand">
		@if(!empty($receipt_details->logo))
			<img class="dn-logo" src="{{ $receipt_details->logo }}" alt="">
		@endif
		@if(!empty($receipt_details->display_name))
			<p class="dn-name">{{ $receipt_details->display_name }}</p>
		@endif
		<div class="dn-muted">
			@if(!empty($receipt_details->address))
				{!! $receipt_details->address !!}
			@endif
			@if(!empty($receipt_details->contact))
				<br>{!! $receipt_details->contact !!}
			@endif
			@if(!empty($receipt_details->website))
				<br>{{ $receipt_details->website }}
			@endif
			@if(!empty($receipt_details->tax_info1))
				<br>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
			@endif
			@if(!empty($receipt_details->tax_info2))
				<br>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
			@endif
		</div>
	</div>

	<div class="dn-title">
		<h1>@lang('lang_v1.delivery_note')</h1>
	</div>

	<div class="dn-meta">
		<div class="dn-meta-row">
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
			<div class="dn-meta-row">
				<span class="lbl">{{ $receipt_details->date_label ?? __('messages.date') }}</span>
				<span class="val">{{ $receipt_details->invoice_date }}</span>
			</div>
		@endif
		@if(!empty($receipt_details->sales_person_label) && !empty($receipt_details->sales_person))
			<div class="dn-meta-row">
				<span class="lbl">{{ $receipt_details->sales_person_label }}</span>
				<span class="val">{{ $receipt_details->sales_person }}</span>
			</div>
		@endif
	</div>

	<div class="dn-block">
		<div class="dn-block-label">{{ $receipt_details->customer_label ?? __('contact.customer') }}</div>
		@if(!empty($receipt_details->customer_name))
			<div class="dn-block-name">{{ $receipt_details->customer_name }}</div>
		@endif
		<div class="dn-muted">
			@if(!empty($dn_cust_extra))
				{!! $dn_cust_extra !!}
			@endif
			@if(!empty($receipt_details->client_id_label))
				<br>{{ $receipt_details->client_id_label }} {{ $receipt_details->client_id }}
			@endif
			@if(!empty($receipt_details->customer_tax_number))
				<br>{{ $receipt_details->customer_tax_label }} {{ $receipt_details->customer_tax_number }}
			@endif
		</div>
	</div>

	@if($dn_has_shipping)
		<div class="dn-block">
			<div class="dn-block-label">@lang('lang_v1.shipping_address')</div>
			<div class="dn-muted">
				@if(!empty($receipt_details->shipping_address))
					{!! $receipt_details->shipping_address !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_1_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_1_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_1_value ?? '' !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_2_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_2_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_2_value ?? '' !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_3_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_3_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_3_value ?? '' !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_4_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_4_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_4_value ?? '' !!}
				@endif
				@if(!empty($receipt_details->shipping_custom_field_5_label))
					<br><strong>{!! strip_tags($receipt_details->shipping_custom_field_5_label) !!}:</strong> {!! $receipt_details->shipping_custom_field_5_value ?? '' !!}
				@endif
			</div>
		</div>
	@endif

	<table class="dn-table">
		<thead>
			<tr>
				<th class="dn-num">#</th>
				<th>{{ $receipt_details->table_product_label ?? __('sale.product') }}</th>
				<th class="dn-qty">{{ $receipt_details->table_qty_label ?? __('lang_v1.quantity') }}</th>
			</tr>
		</thead>
		<tbody>
			@forelse($dn_lines as $line)
				<tr>
					<td class="dn-num">{{ $loop->iteration }}</td>
					<td>
						<div class="dn-prod-name">
							{{ $line['name'] }}
							@if(!empty($line['product_variation']) || !empty($line['variation']))
								{{ $line['product_variation'] }} {{ $line['variation'] }}
							@endif
						</div>
						<div class="dn-prod-meta">
							@if(!empty($line['sub_sku'])) SKU: {{ $line['sub_sku'] }} @endif
							@if(!empty($line['brand'])) · {{ $line['brand'] }} @endif
							@if(!empty($line['product_custom_fields'])) · {{ $line['product_custom_fields'] }} @endif
							@if(!empty($line['sell_line_note'])) · {!! $line['sell_line_note'] !!} @endif
							@if(!empty($line['lot_number']))
								<br>{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}
							@endif
							@if(!empty($line['product_expiry']))
								<br>{{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}
							@endif
						</div>
					</td>
					<td class="dn-qty">
						<strong>{{ $line['quantity'] }}</strong>
						@if(!empty($line['units']))
							<br><span style="font-size:8.5px;font-weight:500;">{{ $line['units'] }}</span>
						@endif
					</td>
				</tr>
				@if(!empty($line['modifiers']))
					@foreach($line['modifiers'] as $modifier)
						<tr>
							<td class="dn-num"></td>
							<td>
								<div class="dn-prod-meta">
									+ {{ $modifier['name'] }} {{ $modifier['variation'] ?? '' }}
									@if(!empty($modifier['sub_sku'])) ({{ $modifier['sub_sku'] }}) @endif
								</div>
							</td>
							<td class="dn-qty">{{ $modifier['quantity'] }} {{ $modifier['units'] ?? '' }}</td>
						</tr>
					@endforeach
				@endif
			@empty
				<tr>
					<td colspan="3" style="text-align:center;color:#737373;padding:12px;">—</td>
				</tr>
			@endforelse
		</tbody>
	</table>

	<div class="dn-summary">
		<span>@lang('lang_v1.items'): {{ count($dn_lines) }}</span>
		<span>@lang('sale.qty'): {{ number_format($dn_qty_total, 3) }}</span>
	</div>

	<div class="dn-note">
		@lang('lang_v1.above_mentioned_items_received_in_good_condition')
	</div>

	<div class="dn-signs">
		<div class="dn-sign">
			<div class="dn-sign-line">@lang('lang_v1.authorized_signatory')</div>
			<div class="dn-sign-sub">@lang('business.business')</div>
		</div>
		<div class="dn-sign">
			<div class="dn-sign-line">@lang('lang_v1.received_by')</div>
			<div class="dn-sign-sub">@lang('lang_v1.date'): _______________</div>
		</div>
	</div>

	@if(!empty($receipt_details->show_barcode))
		<div class="dn-barcode">
			<img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true) }}" alt="barcode">
		</div>
	@endif

	@if(!empty($receipt_details->footer_text))
		<div class="dn-footer">
			{!! $receipt_details->footer_text !!}
		</div>
	@endif
</div>
