@php
	$subtype = '';
@endphp
@if(!empty($transaction_sub_type))
	@php
		$subtype = '?sub_type='.$transaction_sub_type;
	@endphp
@endif

@if(!empty($transactions))
	<div class="table-responsive vspos-recent-tx-table-wrap">
	<table class="table vspos-recent-tx-table">
		@foreach ($transactions as $transaction)
			<tr class="cursor-pointer vspos-recent-tx-row"
	    		title="Customer: {{$transaction->contact?->name}} 
		    		@if(!empty($transaction->contact->mobile) && $transaction->contact->is_default == 0)
		    			<br/>Mobile: {{$transaction->contact->mobile}}
		    		@endif
	    		" >
				<td>
					{{ $loop->iteration}}.
				</td>
				<td class="col-md-4">
					{{ $transaction->invoice_no }} ({{$transaction->contact?->name}})
					@if(!empty($transaction->table))
						- {{$transaction->table->name}}
					@endif
				</td>
				<td class="display_currency col-md-2">
					{{ $transaction->final_total }}
				</td>
				<td class="col-md-6 tw-flex tw-flex-nowrap tw-items-center" style="gap:4px; flex-wrap:nowrap;">
					<button type="button"
						class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-info tw-dw-btn-sm btn-modal"
						style="padding:2px 8px; font-size:11px; line-height:1.4;"
						data-href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$transaction->id]) }}"
						data-container=".view_modal"
						title="@lang('messages.view')">
						<i class="fas fa-eye text-muted" style="font-size:10px;" aria-hidden="true"></i>
						Details
					</button>

					@if(auth()->user()->can('sell.update') || auth()->user()->can('direct_sell.update'))
					<a href="{{action([\App\Http\Controllers\SellPosController::class, 'edit'], [$transaction->id]).$subtype}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-info tw-dw-btn-sm" style="padding:2px 8px; font-size:11px; line-height:1.4;">
	    				<i class="fas fa-pen text-muted" style="font-size:10px;" aria-hidden="true" title="{{__('lang_v1.click_to_edit')}}"></i>
                        @lang('messages.edit')
	    			</a>
	    			@endif

					@if(!auth()->user()->can('sell.update') && auth()->user()->can('edit_pos_payment'))
						<a href="{{route('edit-pos-payment', ['id' => $transaction->id])}}" 
						title="@lang('lang_v1.add_edit_payment')" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-info tw-dw-btn-sm" style="padding:2px 8px; font-size:11px; line-height:1.4;">
						    <i class="fas fa-money-bill-alt text-muted" style="font-size:10px;"></i>
						</a>
					@endif

	    			<a href="{{action([\App\Http\Controllers\SellPosController::class, 'printInvoice'], [$transaction->id])}}" class="print-invoice-link tw-dw-btn tw-dw-btn-outline tw-dw-btn-success tw-dw-btn-sm" style="padding:2px 8px; font-size:11px; line-height:1.4;">
	    				<i class="fa fa-print text-muted" style="font-size:10px;" aria-hidden="true" title="{{__('lang_v1.click_to_print')}}"></i>
                        Reprint
	    			</a>
                    <button type="button" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-primary resend-ebill-btn tw-dw-btn-sm" style="padding:2px 8px; font-size:11px; line-height:1.4;"
                        data-transaction-id="{{ $transaction->id }}"
                        data-customer-name="{{ $transaction->contact?->name }}"
                        data-customer-mobile="{{ $transaction->contact?->mobile }}">
                        <i class="fas fa-paper-plane text-muted" style="font-size:10px;" title="Resend eBill"></i> eBill
                    </button>

                    @if(auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete'))
	    			    <a href="{{action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$transaction->id])}}" class="delete-sale tw-dw-btn tw-dw-btn-outline tw-dw-btn-error tw-dw-btn-sm" style="padding:2px 8px; font-size:11px; line-height:1.4;">
                            <i class="fa fa-trash text-danger" style="font-size:10px;" title="{{__('lang_v1.click_to_delete')}}"></i>
                            @lang('messages.delete')
                        </a>
	    			@endif
				</td>
			</tr>
		@endforeach
	</table>
	</div>
@else
	<p class="vspos-recent-tx-empty">@lang('sale.no_recent_transactions')</p>
@endif