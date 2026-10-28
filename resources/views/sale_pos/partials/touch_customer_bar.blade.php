{{-- Touch POS customer bar (shared above products + cart) --}}
<div class="vspos-touch-customer" id="vspos_touch_customer">
	<div class="vspos-touch-customer-inner">
		<span class="vspos-touch-customer-icon"><i class="fi fi-rr-user pos-fi-icon"></i></span>
		<input type="hidden" id="default_customer_id" value="{{ !empty($edit) && !empty($transaction->contact) ? $transaction->contact->id : ($walk_in_customer['id'] ?? '')}}">
		<input type="hidden" id="default_customer_name" value="{{ !empty($edit) && !empty($transaction->contact) ? $transaction->contact->name : ($walk_in_customer['name'] ?? '')}}">
		<input type="hidden" id="default_customer_balance" value="{{ !empty($edit) && !empty($transaction->contact) ? ($transaction->contact->balance ?? '') : ($walk_in_customer['balance'] ?? '')}}">
		<input type="hidden" id="default_customer_address" value="{{ !empty($edit) && !empty($transaction->contact) ? ($transaction->contact->shipping_address ?? '') : ($walk_in_customer['shipping_address'] ?? '')}}">
		@if(empty($edit) && !empty($walk_in_customer['price_calculation_type']) && $walk_in_customer['price_calculation_type'] == 'selling_price_group')
			<input type="hidden" id="default_selling_price_group" value="{{ $walk_in_customer['selling_price_group_id'] ?? ''}}">
		@endif
		@if(!empty($edit) && !empty($transaction->selling_price_group_id))
			<input type="hidden" id="default_selling_price_group" value="{{ $transaction->selling_price_group_id }}">
		@endif
		{!! Form::select('contact_id', [], null, [
			'class' => 'form-control mousetrap',
			'id' => 'customer_id',
			'placeholder' => !empty($is_pop) ? __('lang_v1.pop_contact_placeholder') : 'Customer name / phone',
			'required',
		]); !!}
		<div class="vspos-touch-customer-actions">
			@if(empty($is_pop) && session('business.enable_rp') == 1)
			@php
				$_loy_sc = session('business.keyboard_shortcuts');
				if (is_string($_loy_sc)) { $_loy_sc = json_decode($_loy_sc, true) ?? []; }
				$_loy_key = strtoupper($_loy_sc['pos']['loyalty_customer'] ?? 'f10');
			@endphp
			<button type="button" class="btn vspos-touch-cust-btn vspos-loyalty-open-btn" id="vspos_loyalty_open_btn"
				title="{{ session('business.rp_name') ?: __('lang_v1.loyalty') }} ({{ $_loy_key }})">
				<i class="fa fa-gift"></i>
			</button>
			@endif
			@php
				$pop_add_disabled = !empty($is_pop)
					? (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create'))
					: !auth()->user()->can('customer.create');
			@endphp
			<button type="button" class="btn vspos-touch-cust-btn add_new_customer" data-name=""
				@if($pop_add_disabled) disabled @endif
				title="{{ !empty($is_pop) ? __('lang_v1.add_contact') : __('contact.add_customer') }}">
				<i class="fa fa-plus"></i>
			</button>
			@if(empty($is_pop))
			@can('sell.payments')
			<button type="button" id="pos-receive-customer-payment" class="btn vspos-touch-cust-btn"
				title="@lang('lang_v1.receive_payment')">
				<i class="fas fa-hand-holding-usd"></i>
			</button>
			@endcan
			@endif
		</div>
	</div>
	<div class="vspos-customer-meta vspos-touch-customer-meta" id="vspos_customer_meta" aria-live="polite">
		@if(session('business.enable_rp') == 1)
		<div id="pos_loyalty_badge" class="pos-loyalty-badge hide">
			<span class="pos-loyalty-badge-icon"><i class="fa fa-gift"></i></span>
			<span class="pos-loyalty-badge-label">{{ session('business.rp_name') ?: __('lang_v1.loyalty') }}</span>
			<span class="pos-loyalty-badge-points"><span id="pos_loyalty_points_value">0</span> pts</span>
		</div>
		@endif
		<div id="pos_customer_due_badge" class="pos-balance-badge pos-balance-badge--due hide" title="@lang('account.customer_due')">
			<span class="pos-balance-badge-icon"><i class="fa fa-file-invoice-dollar"></i></span>
			<span class="pos-balance-badge-label">@lang('lang_v1.balance')</span>
			<span class="pos-balance-badge-value" id="pos_customer_due_value">0</span>
		</div>
		<div id="pos_customer_advance_badge" class="pos-balance-badge pos-balance-badge--advance hide" title="@lang('lang_v1.advance_balance')">
			<span class="pos-balance-badge-icon"><i class="fa fa-coins"></i></span>
			<span class="pos-balance-badge-label">@lang('lang_v1.advance')</span>
			<span class="pos-balance-badge-value" id="pos_customer_advance_value">0</span>
		</div>
	</div>
	<small class="text-danger hide contact_due_text" aria-hidden="true"><strong>@lang('account.customer_due'):</strong> <span></span></small>
</div>
