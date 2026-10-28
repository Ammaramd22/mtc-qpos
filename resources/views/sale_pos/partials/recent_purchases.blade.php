@if(!empty($transactions) && count($transactions))
	<div class="table-responsive vspos-recent-tx-table-wrap">
	<table class="table vspos-recent-tx-table">
		@foreach ($transactions as $transaction)
			@php
				$contact_label = trim(($transaction->contact->supplier_business_name ?? '') . ' ' . ($transaction->contact->name ?? ''));
				$contact_label = $contact_label !== '' ? $contact_label : '—';
			@endphp
			<tr class="vspos-recent-tx-row" title="{{ $contact_label }}">
				<td>{{ $loop->iteration }}.</td>
				<td class="col-md-5">
					{{ $transaction->ref_no }}
					<span class="text-muted">({{ $contact_label }})</span>
				</td>
				<td class="display_currency col-md-2">
					{{ $transaction->final_total }}
				</td>
				<td class="col-md-5 tw-flex tw-flex-nowrap tw-items-center" style="gap:6px; flex-wrap:nowrap;">
					<button type="button"
						class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-info tw-dw-btn-sm btn-modal"
						style="padding:4px 10px; font-size:12px; line-height:1.4;"
						data-href="{{ action([\App\Http\Controllers\PurchaseController::class, 'show'], [$transaction->id]) }}"
						data-container=".view_modal"
						title="Details">
						<i class="fas fa-eye" style="font-size:11px;" aria-hidden="true"></i>
						Details
					</button>

					<a href="{{ url('/purchase-pos/' . $transaction->id . '/print') }}"
						class="print-invoice-link js-pop-reprint tw-dw-btn tw-dw-btn-outline tw-dw-btn-success tw-dw-btn-sm"
						style="padding:4px 10px; font-size:12px; line-height:1.4;"
						data-id="{{ $transaction->id }}"
						title="Reprint">
						<i class="fa fa-print" style="font-size:11px;" aria-hidden="true"></i>
						Reprint
					</a>
				</td>
			</tr>
		@endforeach
	</table>
	</div>
@else
	<p class="vspos-recent-tx-empty">@lang('sale.no_recent_transactions')</p>
@endif
