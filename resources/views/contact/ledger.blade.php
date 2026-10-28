@if(!empty($for_pdf))
	@include('contact.partials.ledger_pdf_styles')
@endif

@php
	$is_pdf = !empty($for_pdf);
	$ledger_type_label = $contact->type == 'both'
		? __('role.customer') . ' & ' . __('role.supplier')
		: (__('role.' . $contact->type) ?? __('lang_v1.ledger'));
@endphp

@if($is_pdf)
{{-- ── PDF / print markup (unchanged structure for mPDF) ── --}}
<div class="col-md-12 col-sm-12 width-100 align-right">
        <p class="text-right align-right"><strong>{{$contact->business->name}}</strong>
        	<br>
        	@if(!empty($location))
        		{!! $location->location_address !!}
        	@else
        		{!! $contact->business->business_address !!}
        	@endif
        </p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 width-50 f-left">
	<p class="blue-heading p-4 width-50">@lang('lang_v1.to'):</p>
	<p><strong>{{$contact->name}}</strong><br> {!! $contact->contact_address !!} @if(!empty($contact->email)) <br>@lang('business.email'): {{$contact->email}} @endif
	<br>@lang('contact.mobile'): {{$contact->mobile}}
	@if(!empty($contact->tax_number)) <br>@lang('contact.tax_no'): {{$contact->tax_number}} @endif
</p>
</div>

<div class="col-md-6 col-sm-6 col-xs-6 text-right align-right width-50 f-left">
		<h3 class="mb-0 blue-heading p-4">@lang('lang_v1.account_summary')</h3>
	<div style="border: 1px solid #000; padding: 10px;">
		<b>{{$ledger_details['start_date']}} @lang('lang_v1.to') {{$ledger_details['end_date']}}</b>
		<table class="table table-condensed text-left align-left no-border table-pdf">
		@if( $contact->type == 'supplier' || $contact->type == 'both')
			<tr>
				<td>@lang('report.total_purchase')</td>
				<td class="align-right">@format_currency($ledger_details['total_purchase'])</td>
			</tr>
		@endif
		@if( $contact->type == 'customer' || $contact->type == 'both')
			<tr>
				<td>@lang('lang_v1.total_invoice')</td>
				<td class="align-right">@format_currency($ledger_details['total_invoice'])</td>
			</tr>
		@endif
		<tr>
			<td>@lang('sale.total_paid')</td>
			<td class="align-right">@format_currency($ledger_details['total_paid'])</td>
		</tr>
		@if($ledger_details['ledger_discount'] > 0)
			<tr>
				<td>@lang('lang_v1.ledger_discount')</td>
				<td class="align-right">@format_currency($ledger_details['ledger_discount'])</td>
			</tr>
		@endif
		</table>
	</div>

	<div style="border: 1px solid #000; padding: 10px;">
		<b> @lang('lang_v1.overall_summary') </b>
		<table class="table table-condensed text-left align-left no-border table-pdf">
			@if( $contact->type == 'supplier' || $contact->type == 'both')
				<tr>
					<td>@lang('report.total_purchase')</td>
					<td class="align-right">@format_currency($ledger_details['all_total_purchase'])</td>
				</tr>
			@endif
			@if( $contact->type == 'customer' || $contact->type == 'both')
				<tr>
					<td>@lang('lang_v1.total_invoice')</td>
					<td class="align-right">@format_currency($ledger_details['all_total_invoice'])</td>
				</tr>
			@endif
            @if( $contact->type == 'customer' || $contact->type == 'both')
                <tr>
                    <td>@lang('sale.total_paid')</td>
                    <td class="align-right">@format_currency($ledger_details['all_invoice_paid'])</td>
                </tr>
            @endif
            @if( $contact->type == 'supplier' || $contact->type == 'both')
                <tr>
                    <td>@lang('sale.total_paid')</td>
                    <td class="align-right">@format_currency($ledger_details['all_purchase_paid'])</td>
                </tr>
            @endif
			<tr >
				<td><strong>@lang('lang_v1.balance_due')</strong></td>
				<td class="align-right">@format_currency($ledger_details['all_balance_due'])</td>
			</tr>
		</table>
	</div>
</div>

<div class="col-md-12 col-sm-12 width-100">
	<p class="text-center" style="text-align: center;"><strong>@lang('lang_v1.ledger_table_heading', ['start_date' => $ledger_details['start_date'], 'end_date' => $ledger_details['end_date']])</strong></p>
	<div class="table-responsive">
	<table class="table table-striped table-pdf td-border" id="ledger_table">
		<thead>
			<tr class="row-border blue-heading">
				<th width="18%" class="text-center">@lang('lang_v1.date')</th>
				<th width="9%" class="text-center">@lang('purchase.ref_no')</th>
				<th width="8%" class="text-center">@lang('lang_v1.type')</th>
				<th width="10%" class="text-center">@lang('sale.location')</th>
				<th width="5%" class="text-center">@lang('sale.payment_status')</th>
				<th width="10%" class="text-center">@lang('account.debit')</th>
				<th width="10%" class="text-center">@lang('account.credit')</th>
				<th width="5%" class="text-center">@lang('lang_v1.payment_method')</th>
				<th width="15%" class="text-center">@lang('report.others')</th>
			</tr>
		</thead>
		<tbody>
			@foreach($ledger_details['ledger'] as $data)
                @if($data['type'] == 'Opening Balance')
                    @continue
                @endif
				<tr @if($loop->iteration % 2 == 0) class="odd" @endif>
					<td class="row-border">{{@format_datetime($data['date'])}}</td>
					<td>{{$data['ref_no']}}</td>
					<td>{{$data['type']}}</td>
					<td>{{$data['location']}}</td>
					<td>{{$data['payment_status']}}</td>
					<td class="ws-nowrap align-right">@if($data['debit'] != '') @format_currency($data['debit']) @endif</td>
					<td class="ws-nowrap align-right">@if($data['credit'] != '') @format_currency($data['credit']) @endif</td>
					<td>
						{{$data['payment_method']}}
						@if(!empty($data['cheque_number']))
							<br><small>Chq#: {{$data['cheque_number']}}</small>
						@endif
					</td>
					<td>{!! $data['others'] !!}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
	</div>
</div>

@else
{{-- ── Screen markup (QPOS dark ledger) ── --}}
<div class="qpos-ledger">
	<div class="ql-header">
		<div class="ql-brand">
			<img src="{{ asset('img/qpos-icon.png') }}" alt="QPOS" class="ql-logo">
			<div class="ql-brand-text">
				<strong>{{ $contact->business->name }}</strong>
				<span>
					@if(!empty($location))
						{!! $location->location_address !!}
					@else
						{!! $contact->business->business_address !!}
					@endif
				</span>
			</div>
		</div>
		<span class="ql-chip">{{ $ledger_type_label }} · @lang('lang_v1.ledger')</span>
	</div>

	<div class="ql-grid">
		<div class="ql-card">
			<div class="ql-card-head">@lang('lang_v1.to')</div>
			<div class="ql-card-body">
				<p class="ql-party-name">{{ $contact->name }}</p>
				<p class="ql-party-meta">
					{!! $contact->contact_address !!}
					@if(!empty($contact->email))<br>@lang('business.email'): {{ $contact->email }}@endif
					<br>@lang('contact.mobile'): {{ $contact->mobile }}
					@if(!empty($contact->tax_number))<br>@lang('contact.tax_no'): {{ $contact->tax_number }}@endif
				</p>
			</div>
		</div>

		<div class="ql-card">
			<div class="ql-card-head">@lang('lang_v1.account_summary')</div>
			<div class="ql-card-body">
				<div class="ql-period">{{ $ledger_details['start_date'] }} @lang('lang_v1.to') {{ $ledger_details['end_date'] }}</div>
				<table class="ql-summary-table">
					@if( $contact->type == 'supplier' || $contact->type == 'both')
						<tr>
							<td>@lang('report.total_purchase')</td>
							<td>@format_currency($ledger_details['total_purchase'])</td>
						</tr>
					@endif
					@if( $contact->type == 'customer' || $contact->type == 'both')
						<tr>
							<td>@lang('lang_v1.total_invoice')</td>
							<td>@format_currency($ledger_details['total_invoice'])</td>
						</tr>
					@endif
					<tr>
						<td>@lang('sale.total_paid')</td>
						<td>@format_currency($ledger_details['total_paid'])</td>
					</tr>
					@if($ledger_details['ledger_discount'] > 0)
						<tr>
							<td>@lang('lang_v1.ledger_discount')</td>
							<td>@format_currency($ledger_details['ledger_discount'])</td>
						</tr>
					@endif
				</table>
			</div>
		</div>
	</div>

	<div class="ql-card" style="margin-bottom: 16px;">
		<div class="ql-card-head">@lang('lang_v1.overall_summary')</div>
		<div class="ql-card-body">
			<table class="ql-summary-table">
				@if( $contact->type == 'supplier' || $contact->type == 'both')
					<tr>
						<td>@lang('report.total_purchase')</td>
						<td>@format_currency($ledger_details['all_total_purchase'])</td>
					</tr>
				@endif
				@if( $contact->type == 'customer' || $contact->type == 'both')
					<tr>
						<td>@lang('lang_v1.total_invoice')</td>
						<td>@format_currency($ledger_details['all_total_invoice'])</td>
					</tr>
				@endif
				@if( $contact->type == 'customer' || $contact->type == 'both')
					<tr>
						<td>@lang('sale.total_paid')</td>
						<td>@format_currency($ledger_details['all_invoice_paid'])</td>
					</tr>
				@endif
				@if( $contact->type == 'supplier' || $contact->type == 'both')
					<tr>
						<td>@lang('sale.total_paid')</td>
						<td>@format_currency($ledger_details['all_purchase_paid'])</td>
					</tr>
				@endif
				<tr class="ql-due">
					<td>@lang('lang_v1.balance_due')</td>
					<td>@format_currency($ledger_details['all_balance_due'])</td>
				</tr>
			</table>
		</div>
	</div>

	<p class="ql-table-title">@lang('lang_v1.ledger_table_heading', ['start_date' => $ledger_details['start_date'], 'end_date' => $ledger_details['end_date']])</p>
	<div class="table-responsive">
		<table class="table table-striped" id="ledger_table">
			<thead>
				<tr>
					<th class="text-center">@lang('lang_v1.date')</th>
					<th class="text-center">@lang('purchase.ref_no')</th>
					<th class="text-center">@lang('lang_v1.type')</th>
					<th class="text-center">@lang('sale.location')</th>
					<th class="text-center">@lang('sale.payment_status')</th>
					<th class="text-center">@lang('account.debit')</th>
					<th class="text-center">@lang('account.credit')</th>
					<th class="text-center">@lang('lang_v1.payment_method')</th>
					<th class="text-center">@lang('report.others')</th>
				</tr>
			</thead>
			<tbody>
				@foreach($ledger_details['ledger'] as $data)
					@if($data['type'] == 'Opening Balance')
						@continue
					@endif
					<tr>
						<td>{{@format_datetime($data['date'])}}</td>
						<td>{{$data['ref_no']}}</td>
						<td>{{$data['type']}}</td>
						<td>{{$data['location']}}</td>
						<td>{{$data['payment_status']}}</td>
						<td class="ws-nowrap align-right">@if($data['debit'] != '') @format_currency($data['debit']) @endif</td>
						<td class="ws-nowrap align-right">@if($data['credit'] != '') @format_currency($data['credit']) @endif</td>
						<td>
							{{$data['payment_method']}}
							@if(!empty($data['cheque_number']))
								<br><small class="text-muted">Chq#: {{$data['cheque_number']}}</small>
							@endif
						</td>
						<td>
							{!! $data['others'] !!}
							@if(!empty($is_admin) && !empty($data['transaction_id']) && $data['transaction_type'] == 'ledger_discount')
								<br>
								<button type="button" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_ledger_discount" data-href="{{action([\App\Http\Controllers\LedgerDiscountController::class, 'destroy'], ['ledger_discount' => $data['transaction_id']])}}"><i class="fas fa-trash"></i></button>
								<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-href="{{action([\App\Http\Controllers\LedgerDiscountController::class, 'edit'], ['ledger_discount' => $data['transaction_id']])}}" data-container="#edit_ledger_discount_modal"><i class="fas fa-edit"></i></button>
							@endif
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endif
