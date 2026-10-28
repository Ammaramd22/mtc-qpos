<!-- business information here -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Receipt-{{$receipt_details->invoice_no}}</title>
        <style>
            /* Professional 80mm thermal receipt — printable ~72mm content */
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap');
            * { font-family: 'Inter', Arial, Helvetica, sans-serif; box-sizing: border-box; }
            html, body { margin: 0; padding: 0; }
            body { font-size: 11px; color: #111; line-height: 1.35; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: 80mm auto; margin: 0; }
            .ticket {
                width: 80mm;
                max-width: 80mm;
                margin: 0 auto;
                padding: 3mm 2.5mm 4mm;
                background: #fff;
                overflow: hidden;
            }
            .ticket p { margin: 0; padding: 0; }
            .text-box { text-align: center; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #111; }
            .receipt-logo {
                display: block;
                max-width: 58mm;
                max-height: 22mm;
                width: auto;
                height: auto;
                margin: 0 auto 5px;
                object-fit: contain;
            }
            .text-box img:not(.receipt-logo) { max-width: 58mm; max-height: 22mm; height: auto; margin: 0 auto 5px; display: block; object-fit: contain; }
            .text-box p { text-align: center; margin: 0 0 2px; font-size: 10px; color: #333; line-height: 1.4; word-wrap: break-word; }
            .text-box p b, .text-box p strong { color: #111; font-weight: 600; }
            .headings,
            .business-name {
                font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif !important;
                font-size: 18px !important;
                font-weight: 900 !important;
                text-align: center !important;
                display: block !important;
                margin: 2px 0 4px !important;
                color: #000 !important;
                letter-spacing: 0.8px !important;
                text-transform: uppercase !important;
                line-height: 1.15 !important;
            }
            .sub-headings {
                font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif !important;
                font-size: 12px !important;
                font-weight: 800 !important;
                font-style: normal !important;
                color: #111 !important;
                text-align: center !important;
                display: block !important;
                margin: 5px 0 0 !important;
                letter-spacing: 1.8px !important;
                text-transform: uppercase !important;
            }
            .centered { text-align: center; }

            /* Meta rows: label left, value right — never clip */
            .textbox-info {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 6px;
                padding: 3px 0;
                font-size: 10.5px;
                border-bottom: 1px dotted #999;
                width: 100%;
            }
            .textbox-info.border-top {
                border-top: 1px solid #111;
                margin-top: 4px;
                padding-top: 4px;
                border-bottom: 1px dotted #999;
            }
            .textbox-info > p,
            .textbox-info .f-left,
            .textbox-info .f-right { margin: 0; }
            .textbox-info > p:first-child,
            .textbox-info .f-left {
                flex: 0 1 auto;
                max-width: 38%;
                font-weight: 700;
                color: #111;
                text-align: left;
            }
            .textbox-info > p:last-child,
            .textbox-info .f-right {
                flex: 1 1 auto;
                min-width: 0;
                color: #111;
                text-align: right;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            .bw { word-wrap: break-word; overflow-wrap: anywhere; }

            .loyalty-receipt-block {
                margin: 6px 0 4px;
                padding: 5px 4px 4px;
                border: 1.5px dashed #111;
                border-radius: 2px;
            }
            .loyalty-receipt-title {
                text-align: center !important;
                font-weight: 800 !important;
                font-size: 11px !important;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                margin: 0 0 4px !important;
                padding-bottom: 3px;
                border-bottom: 1px solid #111;
            }
            .loyalty-receipt-block .textbox-info {
                border-bottom: none;
                padding: 2px 0;
                font-size: 10px;
            }
            .loyalty-receipt-balance {
                border-top: 1px dotted #666 !important;
                margin-top: 2px;
                padding-top: 3px !important;
            }

            /* Line items table — fits 80mm without clipping */
            table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 8px 0 4px; table-layout: fixed; }
            thead th {
                border-top: 1.5px solid #111;
                border-bottom: 1.5px solid #111;
                padding: 4px 1px;
                font-weight: 700;
                color: #111;
                font-size: 8.5px;
                text-transform: uppercase;
                letter-spacing: 0;
                white-space: nowrap;
                vertical-align: bottom;
            }
            thead th:nth-child(1), tbody td:nth-child(1) { width: 4mm; text-align: left; }
            thead th:nth-child(2), tbody td:nth-child(2) { width: auto; text-align: left; }
            thead th:nth-child(3), tbody td:nth-child(3) { width: 14mm; text-align: right; }
            thead th:nth-child(4), tbody td:nth-child(4) { width: 14mm; text-align: right; }
            thead th:nth-child(5), tbody td:nth-child(5) { width: 15mm; text-align: right; }
            thead th.quantity, thead th.unit_price, thead th.price,
            thead th.text-right { text-align: right; }
            tbody td {
                padding: 4px 1px;
                border-bottom: 1px dotted #bbb;
                vertical-align: top;
                color: #111;
                word-wrap: break-word;
                overflow-wrap: anywhere;
            }
            tbody td.quantity,
            tbody td.unit_price,
            tbody td.price,
            tbody td.text-right { text-align: right; font-variant-numeric: tabular-nums; }
            tbody td.quantity { font-size: 9px; }
            tbody td.description { font-size: 10px; }

            /* Totals / payments — consistent label left + amount right */
            .flex-box,
            .table-info {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                gap: 6px;
                width: 100%;
                padding: 2px 0;
                font-size: 11px;
            }
            .flex-box > p,
            .table-info > p {
                margin: 0;
                max-width: none;
                width: auto !important;
            }
            .flex-box > p:first-child,
            .table-info > p:first-child {
                flex: 1 1 auto;
                min-width: 0;
                text-align: left !important;
                font-weight: 600;
            }
            .flex-box > p:last-child,
            .table-info > p:last-child {
                flex: 0 0 auto;
                text-align: right !important;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }
            .flex-box .sub-headings,
            .table-info .fw {
                font-size: 12px !important;
                font-weight: 800 !important;
                letter-spacing: 0.5px !important;
                margin: 0 !important;
                text-align: inherit !important;
                display: inline !important;
            }
            .flex-box.total-row {
                border-top: 1.5px solid #111;
                border-bottom: 1.5px solid #111;
                margin: 4px 0;
                padding: 5px 0;
            }
            .flex-box.total-row > p { font-size: 13px; font-weight: 800; }

            hr, .line { border: none; border-top: 1px solid #999; margin: 6px 0; }
            .top-bottom { border-top: 1.5px solid #111; border-bottom: 1.5px solid #111; padding: 6px 0; margin: 6px 0; }
            .small-text { font-size: 9px; color: #444; text-align: center; margin-top: 6px; padding-top: 4px; border-top: 1px solid #ddd; line-height: 1.4; }

            .width-50 { width: auto; }
            .width-100 { width: 100%; }
            .left { text-align: left; }
            .right { text-align: right; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .text-center { text-align: center; }
            .border-bottom { border-bottom: 1px solid #999; padding-bottom: 3px; margin-bottom: 3px; }
            .border-bottom-dotted { border-bottom: 1px dotted #999; }
            .border-top { border-top: 1px solid #111; }
            .mb-10 { margin-bottom: 4px; }
            .mt-5 { margin-top: 5px; }
            .center-block { display: block; margin: 0 auto; max-width: 100%; }
            .f-8 { font-size: 8px !important; }
            .discount-highlight { margin: 6px 0; padding: 5px; border: 1.5px solid #111; text-align: center; font-size: 10px; font-weight: 700; color: #111; }
            .software-footer { margin-top: 8px; border-top: 1px solid #ddd; padding-top: 5px; text-align: center; font-size: 9px; color: #111; line-height: 1.4; font-weight: 700; }
            .software-footer p { font-weight: 700; color: #111; }
            .software-footer strong { color: #000; font-weight: 800; }
            .table-f-12 th, .table-f-12 td { font-size: 10px; padding: 3px 1px; word-break: break-word; }
            .hidden-print { display: none !important; }

            /* High-density professional thermal ink theme */
            .ticket {
                color: #000;
                text-rendering: geometricPrecision;
                -webkit-font-smoothing: auto;
                font-variant-numeric: tabular-nums;
            }
            .ticket,
            .ticket p,
            .ticket span,
            .ticket td,
            .ticket th,
            .ticket div {
                color: #000;
            }
            .text-box {
                border-bottom: 1.5px solid #000;
            }
            .text-box p {
                color: #000;
                font-size: 10.5px;
                font-weight: 500;
            }
            .text-box p b,
            .text-box p strong {
                color: #000;
                font-weight: 800;
            }
            .textbox-info {
                color: #000;
                font-size: 11px;
                font-weight: 600;
                border-bottom-color: #555;
            }
            .textbox-info > p:first-child,
            .textbox-info .f-left {
                color: #000;
                font-weight: 800;
            }
            .textbox-info > p:last-child,
            .textbox-info .f-right {
                color: #000;
                font-weight: 600;
            }
            table {
                color: #000;
                font-size: 10.5px;
            }
            thead th {
                color: #000;
                font-size: 9px;
                font-weight: 900;
                border-top: 2px solid #000;
                border-bottom: 2px solid #000;
                padding-top: 5px;
                padding-bottom: 5px;
            }
            tbody td {
                color: #000;
                font-weight: 600;
                border-bottom-color: #777;
            }
            tbody td.description {
                color: #000;
                font-size: 10.5px;
                font-weight: 700;
            }
            tbody td.quantity,
            tbody td.unit_price,
            tbody td.price,
            tbody td.text-right {
                color: #000;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
            }
            .flex-box,
            .table-info {
                color: #000;
                font-size: 11.5px;
                font-weight: 700;
            }
            .flex-box > p:first-child,
            .table-info > p:first-child {
                color: #000;
                font-weight: 700;
            }
            .flex-box > p:last-child,
            .table-info > p:last-child {
                color: #000;
                font-weight: 800;
                font-variant-numeric: tabular-nums;
            }
            .flex-box.total-row {
                border-top: 2px solid #000;
                border-bottom: 2px solid #000;
                padding: 6px 0;
            }
            .flex-box.total-row > p {
                color: #000;
                font-size: 14px;
                font-weight: 900;
            }
            .small-text,
            small,
            .f-8 {
                color: #111 !important;
                font-weight: 600;
            }
            hr,
            .line,
            .border-top,
            .border-bottom {
                border-color: #000;
            }
            @media print {
                * {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                body {
                    color: #000;
                    font-family: 'Inter', Arial, Helvetica, sans-serif;
                }
                .ticket {
                    width: 80mm;
                    max-width: 80mm;
                    padding: 2mm 2mm 3mm;
                    color: #000;
                    font-weight: 600;
                }
            }
        </style>
    </head>
    <body>
        <div class="ticket">
        	@if(empty($receipt_details->letter_head))
				<div class="text-box">
					@if(!empty($receipt_details->logo))
						<img class="receipt-logo" src="{{$receipt_details->logo}}" alt="Logo">
					@endif

					@if(!empty($receipt_details->header_text))
						<span class="headings">{!! $receipt_details->header_text !!}</span>
					@endif

					@if(!empty($receipt_details->display_name))
						<span class="business-name headings">{{$receipt_details->display_name}}</span>
					@endif

					<p class="centered">
						@if(!empty($receipt_details->address))
							{!! $receipt_details->address !!}<br/>
						@endif

						@if(!empty($receipt_details->contact))
							{!! $receipt_details->contact !!}
						@endif
						@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
							,
						@endif
						@if(!empty($receipt_details->website))
							{{ $receipt_details->website }}
						@endif
						@if(!empty($receipt_details->location_custom_fields))
							<br>{{ $receipt_details->location_custom_fields }}
						@endif

						@if(!empty($receipt_details->sub_heading_line1))
							<br/>{{ $receipt_details->sub_heading_line1 }}
						@endif
						@if(!empty($receipt_details->sub_heading_line2))
							<br/>{{ $receipt_details->sub_heading_line2 }}
						@endif
						@if(!empty($receipt_details->sub_heading_line3))
							<br/>{{ $receipt_details->sub_heading_line3 }}
						@endif
						@if(!empty($receipt_details->sub_heading_line4))
							<br/>{{ $receipt_details->sub_heading_line4 }}
						@endif
						@if(!empty($receipt_details->sub_heading_line5))
							<br/>{{ $receipt_details->sub_heading_line5 }}
						@endif

						@if(!empty($receipt_details->tax_info1))
							<br/><b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
						@endif
						@if(!empty($receipt_details->tax_info2))
							<br/><b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }}
						@endif
					</p>

					@if(!empty($receipt_details->invoice_heading))
						<span class="sub-headings">{!! $receipt_details->invoice_heading !!}</span>
					@endif
				</div>
			@else
				<div class="text-box">
					<img class="receipt-logo" style="max-width:100%;max-height:none;width:100%;" src="{{$receipt_details->letter_head}}" alt="">
					@if(!empty($receipt_details->invoice_heading))
						<span class="sub-headings">{!! $receipt_details->invoice_heading !!}</span>
					@endif
				</div>
			@endif
			<div class="border-top textbox-info">
				<p class="f-left"><strong>{!! $receipt_details->invoice_no_prefix !!}</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_no}}
				</p>
			</div>
			<div class="textbox-info">
				<p class="f-left"><strong>{!! $receipt_details->date_label !!}</strong></p>
				<p class="f-right">
					{{$receipt_details->invoice_date}}
				</p>
			</div>
			
			@if(!empty($receipt_details->due_date_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->due_date_label}}</strong></p>
					<p class="f-right">{{$receipt_details->due_date ?? ''}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->sales_person_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->sales_person_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->sales_person}}</p>
				</div>
			@endif
			@if(!empty($receipt_details->commission_agent_label))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->commission_agent_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->commission_agent}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->brand_label) || !empty($receipt_details->repair_brand))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->brand_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_brand}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->device_label) || !empty($receipt_details->repair_device))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->device_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_device}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->model_no_label) || !empty($receipt_details->repair_model_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->model_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_model_no}}</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->serial_no_label) || !empty($receipt_details->repair_serial_no))
				<div class="textbox-info">
					<p class="f-left"><strong>{{$receipt_details->serial_no_label}}</strong></p>
				
					<p class="f-right">{{$receipt_details->repair_serial_no}}</p>
				</div>
			@endif

			@if(!empty($receipt_details->repair_status_label) || !empty($receipt_details->repair_status))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!! $receipt_details->repair_status_label !!}
					</strong></p>
					<p class="f-right">
						{{$receipt_details->repair_status}}
					</p>
				</div>
        	@endif

        	@if(!empty($receipt_details->repair_warranty_label) || !empty($receipt_details->repair_warranty))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->repair_warranty_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->repair_warranty}}
	        		</p>
	        	</div>
        	@endif

        	<!-- Waiter info -->
			@if(!empty($receipt_details->service_staff_label) || !empty($receipt_details->service_staff))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			{!! $receipt_details->service_staff_label !!}
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->service_staff}}
					</p>
	        	</div>
	        @endif

	        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
	        	<div class="textbox-info">
	        		<p class="f-left"><strong>
	        			@if(!empty($receipt_details->table_label))
							<b>{!! $receipt_details->table_label !!}</b>
						@endif
	        		</strong></p>
	        		<p class="f-right">
	        			{{$receipt_details->table}}
	        		</p>
	        	</div>
	        @endif

			@if (!empty($receipt_details->sell_custom_field_1_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_1_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_1_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_2_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_2_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_2_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_3_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_3_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_3_value}}
					</p>
				</div>
			@endif
			@if (!empty($receipt_details->sell_custom_field_4_value))
				<div class="textbox-info">
					<p class="f-left"><strong>{!! $receipt_details->sell_custom_field_4_label !!}</strong></p>
					<p class="f-right">
						{{$receipt_details->sell_custom_field_4_value}}
					</p>
				</div>
			@endif

	        <!-- customer info -->
	        <div class="textbox-info">
	        	<p class="f-left"><strong>{{$receipt_details->customer_label ?? ''}}</strong></p>
	        	<p class="f-right bw">
	        		@if(!empty($receipt_details->customer_info))
						{!! $receipt_details->customer_info !!}
					@endif
	        	</p>
	        </div>
			
			@if(!empty($receipt_details->client_id_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->client_id_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->client_id }}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->customer_tax_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{{ $receipt_details->customer_tax_label }}
					</strong></p>
					<p class="f-right">
						{{ $receipt_details->customer_tax_number }}
					</p>
				</div>
			@endif

			@if(!empty($receipt_details->customer_custom_fields))
				<div class="textbox-info">
					<p class="centered">
						{!! $receipt_details->customer_custom_fields !!}
					</p>
				</div>
			@endif
			
			@if(!empty($receipt_details->shipping_custom_field_1_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_1_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_1_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_2_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_2_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_2_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_3_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_3_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_3_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_4_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_4_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_4_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->shipping_custom_field_5_label))
				<div class="textbox-info">
					<p class="f-left"><strong>
						{!!$receipt_details->shipping_custom_field_5_label!!} 
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->shipping_custom_field_5_value ?? ''!!}
					</p>
				</div>
			@endif
			@if(!empty($receipt_details->sale_orders_invoice_no))
				<div class="textbox-info">
					<p class="f-left"><strong>
						@lang('restaurant.order_no')
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->sale_orders_invoice_no ?? ''!!}
					</p>
				</div>
			@endif

			@if(!empty($receipt_details->sale_orders_invoice_date))
				<div class="textbox-info">
					<p class="f-left"><strong>
						@lang('lang_v1.order_dates')
					</strong></p>
					<p class="f-right">
						{!!$receipt_details->sale_orders_invoice_date ?? ''!!}
					</p>
				</div>
			@endif
            <table class="width-100 table-f-12 mb-10">
                <thead>
                    <tr>
                        <th class="serial_number">#</th>
                        <th class="description">Item</th>
                        <th class="quantity text-right">Qty</th>
                        @if(empty($receipt_details->hide_price))
                        <th class="unit_price text-right">Price</th>
                        @if(!empty($receipt_details->discounted_unit_price_label))
							<th class="text-right">Net</th>
						@endif
                        @if(!empty($receipt_details->item_discount_label))
							<th class="text-right">Disc</th>
						@endif
                        <th class="price text-right">Amt</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                	@forelse($receipt_details->lines as $line)
	                    <tr>
	                        <td class="serial_number">{{$loop->iteration}}</td>
	                        <td class="description">
	                        	{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}}
	                        	@if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
	                        	@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
	                        	@if(!empty($line['product_description']))
	                            	<div class="f-8">{!!$line['product_description']!!}</div>
	                            @endif
	                        	@if(!empty($line['sell_line_note']))
	                        	<br><span class="f-8">{!!$line['sell_line_note']!!}</span>
	                        	@endif
	                        	@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}: {{$line['lot_number']}} @endif
	                        	@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}: {{$line['product_expiry']}} @endif
	                        	@if(!empty($line['warranty_name']))
	                            	<br><small>{{$line['warranty_name']}}</small>
	                            @endif
	                            @if(!empty($line['warranty_exp_date']))
	                            	<small> - {{@format_date($line['warranty_exp_date'])}}</small>
	                            @endif
	                            @if(!empty($line['warranty_description']))
	                            	<small> {{$line['warranty_description'] ?? ''}}</small>
	                            @endif
	                            @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
		                            <br><small>
		                            	1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
                            			{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
		                            </small>
	                            @endif
	                        </td>
	                        <td class="quantity text-right">
	                        	{{$line['quantity']}}@if(!empty($line['units']))<br><span class="f-8">{{$line['units']}}</span>@endif
	                        	@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                            		<br><small>{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}</small>
                            	@endif
                            </td>
	                        @if(empty($receipt_details->hide_price))
	                        <td class="unit_price text-right">{{$line['unit_price_before_discount']}}</td>
	                        @if(!empty($receipt_details->discounted_unit_price_label))
								<td class="text-right">{{$line['unit_price_inc_tax']}}</td>
							@endif
	                        @if(!empty($receipt_details->item_discount_label))
								<td class="text-right">
									{{$line['total_line_discount'] ?? '0.00'}}
									@if(!empty($line['line_discount_percent']))
								 		<br><span class="f-8">({{$line['line_discount_percent']}}%)</span>
									@endif
								</td>
							@endif
	                        <td class="price text-right">{{$line['line_total']}}</td>
	                        @endif
	                    </tr>
	                    @if(!empty($line['modifiers']))
							@foreach($line['modifiers'] as $modifier)
								<tr>
									<td>&nbsp;</td>
									<td>
			                            {{$modifier['name']}} {{$modifier['variation']}}
			                            @if(!empty($modifier['sub_sku'])), {{$modifier['sub_sku']}} @endif @if(!empty($modifier['cat_code'])), {{$modifier['cat_code']}}@endif
			                            @if(!empty($modifier['sell_line_note']))({!!$modifier['sell_line_note']!!}) @endif
			                        </td>
									<td class="quantity text-right">{{$modifier['quantity']}}@if(!empty($modifier['units']))<br><span class="f-8">{{$modifier['units']}}</span>@endif</td>
									@if(empty($receipt_details->hide_price))
									<td class="unit_price text-right">{{$modifier['unit_price_inc_tax']}}</td>
									@if(!empty($receipt_details->discounted_unit_price_label))
										<td class="text-right">{{$modifier['unit_price_exc_tax']}}</td>
									@endif
									@if(!empty($receipt_details->item_discount_label))
										<td class="text-right">0.00</td>
									@endif
									<td class="price text-right">{{$modifier['line_total']}}</td>
									@endif
								</tr>
							@endforeach
						@endif
                    @empty
                    @endforelse
                </tbody>
            </table>
			@if(!empty($receipt_details->total_quantity_label))
				<div class="flex-box">
					<p>{!! $receipt_details->total_quantity_label !!}</p>
					<p>{{$receipt_details->total_quantity}}</p>
				</div>
			@endif
			@if(!empty($receipt_details->total_items_label))
				<div class="flex-box">
					<p>{!! $receipt_details->total_items_label !!}</p>
					<p>{{$receipt_details->total_items}}</p>
				</div>
			@endif
			@if(empty($receipt_details->hide_price))
                <div class="flex-box">
                    <p>{!! $receipt_details->subtotal_label !!}</p>
                    <p>{{$receipt_details->subtotal}}</p>
                </div>

                <!-- Shipping Charges -->
				@if(!empty($receipt_details->shipping_charges))
					<div class="flex-box">
						<p>{!! $receipt_details->shipping_charges_label !!}</p>
						<p>{{$receipt_details->shipping_charges}}</p>
					</div>
				@endif

				@if(!empty($receipt_details->packing_charge))
					<div class="flex-box">
						<p>{!! $receipt_details->packing_charge_label !!}</p>
						<p>{{$receipt_details->packing_charge}}</p>
					</div>
				@endif

				<!-- Discount -->
				@if( !empty($receipt_details->discount) )
					<div class="flex-box">
						<p>{!! $receipt_details->discount_label !!}</p>
						<p>(-) {{$receipt_details->discount}}</p>
					</div>
				@endif

				@if( !empty($receipt_details->total_line_discount) )
					<div class="flex-box">
						<p>{!! $receipt_details->line_discount_label !!}</p>
						<p>(-) {{$receipt_details->total_line_discount}}</p>
					</div>
				@endif

				@if( !empty($receipt_details->additional_expenses) )
					@foreach($receipt_details->additional_expenses as $key => $val)
						<div class="flex-box">
							<p>{{$key}}:</p>
							<p>(+) {{$val}}</p>
						</div>
					@endforeach
				@endif

				@if(!empty($receipt_details->reward_point_label) )
					<div class="flex-box">
						<p>{!! $receipt_details->reward_point_label !!}</p>
						<p>(-) {{$receipt_details->reward_point_amount}}</p>
					</div>
				@endif

				@if( !empty($receipt_details->tax) )
					<div class="flex-box">
						<p>{!! $receipt_details->tax_label !!}</p>
						<p>(+) {{$receipt_details->tax}}</p>
					</div>
				@endif

				@if( $receipt_details->round_off_amount > 0)
					<div class="flex-box">
						<p>{!! $receipt_details->round_off_label !!}</p>
						<p>{{$receipt_details->round_off}}</p>
					</div>
				@endif

				<div class="flex-box total-row">
					<p>{!! $receipt_details->total_label !!}</p>
					<p>{{$receipt_details->total}}</p>
				</div>
				@if(!empty($receipt_details->total_in_words))
				<p class="text-right mb-0" style="font-size:9px;margin:2px 0 4px;">
					<small>({{$receipt_details->total_in_words}})</small>
				</p>
				@endif
				@if(!empty($receipt_details->payments))
					@foreach($receipt_details->payments as $payment)
						<div class="flex-box">
							<p>{{$payment['method']}} ({{$payment['date']}})</p>
							<p>{{$payment['amount']}}</p>
						</div>
					@endforeach
				@endif

				<!-- Total Paid-->
				@if(!empty($receipt_details->total_paid))
					<div class="flex-box">
						<p>{!! $receipt_details->total_paid_label !!}</p>
						<p>{{$receipt_details->total_paid}}</p>
					</div>
				@endif

				@if(!empty($receipt_details->show_mrp_savings) && !empty($receipt_details->mrp_savings))
					<div style="margin:8px 0 6px;padding:8px 6px;border:2px solid #111;border-radius:4px;text-align:center;">
						<div style="font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">{{ $receipt_details->mrp_savings_label ?? __('lang_v1.your_discount_is') }}</div>
						<div style="font-size:15px;font-weight:800;margin-top:2px;">{{ $receipt_details->mrp_savings }}</div>
					</div>
				@endif

				@if(!empty($receipt_details->show_loyalty_block) || !empty($receipt_details->customer_rp_label))
					<div class="loyalty-receipt-block">
						<p class="loyalty-receipt-title">{{ $receipt_details->customer_rp_label ?? __('lang_v1.loyalty') }}</p>
						@if(!empty($receipt_details->loyalty_customer_name))
						<div class="textbox-info loyalty-receipt-row">
							<p class="f-left"><strong>@lang('contact.customer')</strong></p>
							<p class="f-right">{{ $receipt_details->loyalty_customer_name }}</p>
						</div>
						@endif
						@if(!empty($receipt_details->loyalty_points_earned))
						<div class="textbox-info loyalty-receipt-row">
							<p class="f-left"><strong>@lang('lang_v1.earned')</strong></p>
							<p class="f-right">+{{ $receipt_details->loyalty_points_earned }}</p>
						</div>
						@endif
						@if(!empty($receipt_details->loyalty_points_redeemed))
						<div class="textbox-info loyalty-receipt-row">
							<p class="f-left"><strong>@lang('lang_v1.redeemed')</strong></p>
							<p class="f-right">-{{ $receipt_details->loyalty_points_redeemed }}</p>
						</div>
						@endif
						<div class="textbox-info loyalty-receipt-row loyalty-receipt-balance">
							<p class="f-left"><strong>@lang('lang_v1.available')</strong></p>
							<p class="f-right"><strong>{{ $receipt_details->customer_total_rp ?? 0 }} pts</strong></p>
						</div>
					</div>
				@endif

				<!-- Total Due-->
				@if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
					<div class="flex-box">
						<p>{!! $receipt_details->total_due_label !!}</p>
						<p>{{$receipt_details->total_due}}</p>
					</div>
				@endif
				@if(!empty($receipt_details->total_previous_due))
					<div class="flex-box">
						<p>{!! $receipt_details->total_previous_due_label !!}</p>
						<p>{{$receipt_details->total_previous_due}}</p>
					</div>
				@endif
				@if(!empty($receipt_details->all_due))
					<div class="flex-box">
						<p>{!! $receipt_details->all_bal_label !!}</p>
						<p>{{$receipt_details->all_due}}</p>
					</div>
				@endif
				
			@endif
            <div class="border-bottom width-100">&nbsp;</div>
			{{-- YOUR DISCOUNT FOR BILL highlight box --}}
			@if(!empty($receipt_details->discount))
<div class="discount-highlight">
Your Discount For Bill {{$receipt_details->discount}}
</div>
			@endif
            @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) )
	            <!-- tax -->
	            @if(!empty($receipt_details->taxes))
	            	<table class="border-bottom width-100 table-f-12">
	            		<tr>
	            			<th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
	            		</tr>
	            		@foreach($receipt_details->taxes as $key => $val)
	            			<tr>
	            				<td class="left">{{$key}}</td>
	            				<td class="right">{{$val}}</td>
	            			</tr>
	            		@endforeach
	            	</table>
	            @endif
            @endif

            @if(!empty($receipt_details->additional_notes))
	            <p class="centered">
	            	{!! nl2br($receipt_details->additional_notes) !!}
	            </p>
            @endif

            {{-- Barcode hidden when show_barcode is off --}}
			@if(!empty($receipt_details->show_barcode))
				<br/>
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif

			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE')}}">
			@endif
			
			@if(!empty($receipt_details->footer_text))
				<p class="centered">
					{!! $receipt_details->footer_text !!}
				</p>
			@endif

			{{-- VSOFT SOFTWARE FOOTER (no logo) --}}
			<div class="software-footer">
				<p><strong>{{ config('app.brand_footer') }}</strong></p>
			</div>
		</div>
        <!-- <button id="btnPrint" class="hidden-print">Print</button>
        <script src="script.js"></script> -->
    </body>
</html>