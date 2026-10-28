{{-- Unified Bills hub: last bill/purchase + reprint + recent list --}}
@php
	$is_pop_hub = !empty($is_pop) || request()->is('purchase-pos*');
@endphp
<div class="modal fade no-print vspos-pos-modal vspos-recent-tx-modal vspos-bills-hub-modal"
	id="recent_transactions_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_bills_hub_title">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content vspos-pos-modal-content">
			<div class="modal-header vspos-pos-modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="vspos_bills_hub_title">
					@if($is_pop_hub)
						<i class="fi fi-rr-print pos-fi-icon"></i> Purchase Invoices
					@else
						<i class="fi fi-rr-print pos-fi-icon"></i> Bills &amp; Reprint
					@endif
				</h4>
			</div>
			<div class="modal-body vspos-pos-modal-body vspos-recent-tx-body">
				{{-- Last bill / purchase + reprint --}}
				<div class="vspos-bills-hub-last" id="vspos_bills_hub_last">
					<div class="vspos-bills-hub-last-info">
						<span class="vspos-bills-hub-kicker">
							<i class="fi fi-rr-receipt pos-fi-icon"></i>
							{{ $is_pop_hub ? 'Last purchase' : 'Last bill' }}
						</span>
						@if(!empty($last_sell))
							<div class="vspos-bills-hub-meta">
								<span class="vspos-bills-hub-invoice vspos-hub-invoice"># {{ $last_sell->invoice_no }}</span>
								<span class="vspos-bills-hub-amount vspos-hub-amount">@format_currency($last_sell->final_total)</span>
							</div>
						@else
							<div class="vspos-bills-hub-meta">
								<span class="vspos-bills-hub-invoice vspos-hub-invoice">{{ $is_pop_hub ? 'No recent purchase' : 'No recent bill' }}</span>
								<span class="vspos-bills-hub-amount vspos-hub-amount"></span>
							</div>
						@endif
					</div>
					<div class="vspos-bills-hub-last-actions">
					<button type="button"
						class="vspos-bills-hub-print js-print-last-bill"
						id="vspos_hub_print_last"
						data-id="{{ !empty($last_sell) ? $last_sell->id : '' }}"
						@if(empty($last_sell)) disabled @endif
						title="{{ $is_pop_hub ? 'Reprint last purchase' : 'Reprint last bill' }}">
						<i class="fi fi-rr-print pos-fi-icon"></i>
						<span>Reprint</span>
					</button>
					@if($is_pop_hub)
						<button type="button"
							class="vspos-bills-hub-print vspos-bills-hub-details btn-modal"
							data-href="{{ !empty($last_sell) ? action([\App\Http\Controllers\PurchaseController::class, 'show'], [$last_sell->id]) : '#' }}"
							data-container=".view_modal"
							@if(empty($last_sell)) disabled @endif
							title="Details">
							<i class="fas fa-eye"></i>
							<span>Details</span>
						</button>
					@endif
					</div>
				</div>

				<div class="vspos-bills-hub-section-title">
					<i class="fi fi-rr-time-past pos-fi-icon"></i>
					{{ $is_pop_hub ? __('purchase.purchases') : __('lang_v1.recent_transactions') }}
				</div>

				@if($is_pop_hub)
					<div class="vspos-recent-tx-tabs">
						<div class="tab-content">
							<div class="tab-pane active" id="tab_final"></div>
						</div>
					</div>
				@else
					<div class="vspos-recent-tx-tabs">
						<ul class="nav nav-tabs">
							<li class="active"><a href="#tab_final" data-toggle="tab" aria-expanded="true"><b><i class="fa fa-check"></i> @lang('sale.final')</b></a></li>
							<li class=""><a href="#tab_quotation" data-toggle="tab" aria-expanded="false"><b><i class="fa fa-terminal"></i> @lang('lang_v1.quotation')</b></a></li>
							<li class=""><a href="#tab_draft" data-toggle="tab" aria-expanded="false"><b><i class="fa fa-terminal"></i> @lang('sale.draft')</b></a></li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane active" id="tab_final"></div>
							<div class="tab-pane" id="tab_quotation"></div>
							<div class="tab-pane" id="tab_draft"></div>
						</div>
					</div>
				@endif
			</div>
			<div class="modal-footer vspos-pos-modal-footer">
				<button type="button" class="vspos-modal-close-btn" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div>
	</div>
</div>
