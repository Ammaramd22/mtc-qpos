<div class="vspos-cart-toolbar">
@if(empty($hide_customer_toolbar))
<div class="row">
	<div class="col-md-12">
		<div class="form-group" style="margin-bottom:8px;">
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fi fi-rr-user pos-fi-icon"></i>
				</span>
				<input type="hidden" id="default_customer_id" 
				value="{{ !empty($edit) && !empty($transaction->contact) ? $transaction->contact->id : ($walk_in_customer['id'] ?? '')}}" >
				<input type="hidden" id="default_customer_name" 
				value="{{ !empty($edit) && !empty($transaction->contact) ? $transaction->contact->name : ($walk_in_customer['name'] ?? '')}}" >
				<input type="hidden" id="default_customer_balance" 
				value="{{ !empty($edit) && !empty($transaction->contact) ? ($transaction->contact->balance ?? '') : ($walk_in_customer['balance'] ?? '')}}" >
				<input type="hidden" id="default_customer_address" 
				value="{{ !empty($edit) && !empty($transaction->contact) ? ($transaction->contact->shipping_address ?? '') : ($walk_in_customer['shipping_address'] ?? '')}}" >
				@if(empty($edit) && !empty($walk_in_customer['price_calculation_type']) && $walk_in_customer['price_calculation_type'] == 'selling_price_group')
					<input type="hidden" id="default_selling_price_group" 
				value="{{ $walk_in_customer['selling_price_group_id'] ?? ''}}" >
				@endif
				@if(!empty($edit) && !empty($transaction->selling_price_group_id))
					<input type="hidden" id="default_selling_price_group" value="{{ $transaction->selling_price_group_id }}">
				@endif
				{!! Form::select('contact_id', 
					[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => !empty($is_pop) ? __('lang_v1.pop_contact_placeholder') : 'Enter Customer name / phone', 'required']); !!}
				<span class="input-group-btn">
					@if(session('business.enable_rp') == 1)
					@php
						$_loy_sc = session('business.keyboard_shortcuts');
						if (is_string($_loy_sc)) { $_loy_sc = json_decode($_loy_sc, true) ?? []; }
						$_loy_key = strtoupper($_loy_sc['pos']['loyalty_customer'] ?? 'f10');
					@endphp
					<button type="button" class="btn btn-default bg-white btn-flat vspos-loyalty-open-btn" id="vspos_loyalty_open_btn"
						title="{{ session('business.rp_name') ?: __('lang_v1.loyalty') }} ({{ $_loy_key }})">
						<i class="fa fa-gift text-warning"></i>
						<span class="keycap">{{ $_loy_key }}</span>
					</button>
					@endif
					<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""  @if(!auth()->user()->can('customer.create')) disabled @endif><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
					@can('sell.payments')
					<button type="button" id="pos-receive-customer-payment" class="btn btn-default bg-white btn-flat" title="@lang('lang_v1.receive_payment')"><i class="fas fa-hand-holding-usd text-primary fa-lg"></i></button>
					@endcan
				</span>
			</div>
			<small class="text-danger hide contact_due_text" aria-hidden="true"><strong>@lang('account.customer_due'):</strong> <span></span></small>
			<div class="vspos-customer-meta" id="vspos_customer_meta" aria-live="polite">
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
		</div>
	</div>
</div>
@endif

{{-- Quick entry: Barcode/SKU → Product name → Qty → Rate → Cart --}}
@if(!empty($hide_quick_entry))
<script>window.VSPOS_QUICK_ENTRY = 0;</script>
<input type="hidden" id="vspos_entry_variation_id" value="">
<input type="hidden" id="vspos_entry_purchase_line_id" value="">
<input type="hidden" id="vspos_entry_enable_stock" value="">
<input type="hidden" id="vspos_entry_qty_available" value="">
<input type="hidden" id="vspos_entry_desc" value="">
<input type="hidden" id="vspos_entry_qty" value="1">
<input type="hidden" id="vspos_entry_rate" value="">
@else
<script>window.VSPOS_QUICK_ENTRY = 1;</script>
<div class="vspos-entry-bar" id="vspos_quick_entry">
	<input type="hidden" id="vspos_entry_variation_id" value="">
	<input type="hidden" id="vspos_entry_purchase_line_id" value="">
	<input type="hidden" id="vspos_entry_enable_stock" value="">
	<input type="hidden" id="vspos_entry_qty_available" value="">

	<div class="vspos-entry-col vspos-entry-search">
		<label class="vspos-entry-label">Barcode / SKU / Name</label>
		<div class="vspos-entry-search-row">
			<div class="input-group vspos-entry-search-field">
				<span class="input-group-addon"><i class="fas fa-barcode"></i></span>
				{!! Form::text('search_product', null, [
					'class' => 'form-control mousetrap',
					'id' => 'search_product',
					'placeholder' => 'Search product name, barcode or SKU...',
					'disabled' => is_null($default_location)? true : false,
					'autofocus' => is_null($default_location)? false : true,
					'autocomplete' => 'off',
					'inputmode' => 'text',
				]); !!}
				<span class="input-group-btn">
					<button type="button" class="btn btn-default bg-white btn-flat hidden-md hidden-lg" id="barcode_scanner_btn"
						title="Scan barcode with camera" data-toggle="modal" data-target="#barcodeScannerModal">
						<i class="fas fa-camera text-primary"></i>
					</button>
					@if(isset($pos_settings['enable_weighing_scale']) && $pos_settings['enable_weighing_scale'] == 1)
						<button type="button" class="btn btn-default bg-white btn-flat" id="weighing_scale_btn" data-toggle="modal" data-target="#weighing_scale_modal" 
						title="@lang('lang_v1.weighing_scale')"><i class="fa fa-digital-tachograph text-primary fa-lg"></i></button>
					@endif
				</span>
			</div>
			<button type="button" class="btn vspos-entry-search-btn" id="vspos_bulk_search_btn"
				title="Product search — name / code · cost &amp; stock (F8)">
				<i class="fas fa-search"></i>
				<span class="keycap">F8</span>
			</button>
		</div>
	</div>

	<div class="vspos-entry-col vspos-entry-desc">
		<label class="vspos-entry-label">Product name</label>
		<input type="text" class="form-control" id="vspos_entry_desc" readonly placeholder="—" autocomplete="off">
	</div>

	<div class="vspos-entry-col vspos-entry-qty">
		<label class="vspos-entry-label">Qty</label>
		<input type="text" class="form-control input_number text-center mousetrap" id="vspos_entry_qty" value="1" inputmode="decimal">
	</div>

	<div class="vspos-entry-col vspos-entry-rate">
		<label class="vspos-entry-label">Rate</label>
		<input type="text" class="form-control input_number text-right mousetrap" id="vspos_entry_rate" value="" inputmode="decimal" placeholder="0.00">
	</div>
</div>
{{-- Inline Enter flow (avoids stale cached JS) --}}
<script>
(function () {
	window.VSPOS_QUICK_ENTRY = 1;
	if (window.__vspos_inline_entry_bound) return;
	window.__vspos_inline_entry_bound = true;

	function locId() {
		var el = document.getElementById('location_id');
		return el ? el.value : '';
	}
	function setVal(id, v) {
		var el = document.getElementById(id);
		if (el) el.value = v;
	}
	function getVal(id) {
		var el = document.getElementById(id);
		return el ? String(el.value || '').trim() : '';
	}
	function focusId(id) {
		setTimeout(function () {
			var el = document.getElementById(id);
			if (el) { el.focus(); if (el.select) el.select(); }
		}, 10);
	}
	function moneyFmt(n) {
		n = parseFloat(n) || 0;
		return n.toFixed(2);
	}
	function decodeHtml(s) {
		if (!s || s.indexOf('&') === -1) return s || '';
		var t = document.createElement('textarea');
		t.innerHTML = s;
		return t.value;
	}
	function openBulk(term) {
		if (typeof window.vsposOpenBulkProductSearch === 'function') {
			window.vsposOpenBulkProductSearch(term || '');
		} else {
			var btn = document.getElementById('vspos_bulk_search_btn');
			if (btn) btn.click();
		}
	}
	/** Numeric barcode / SKU (scanner or typed code) */
	function looksLikeBarcodeCode(term) {
		return /^[0-9]{4,}$/.test(String(term || '').trim());
	}
	function fill(item) {
		if (!item || !item.variation_id) return;
		var name = item.name || '';
		if (item.type == 'variable' && item.variation) name += ' - ' + item.variation;
		name = decodeHtml(name);
		var price = item.variation_group_price || item.selling_price || 0;
		setVal('vspos_entry_variation_id', String(item.variation_id));
		setVal('vspos_entry_desc', name);
		setVal('vspos_entry_qty', '1');
		setVal('vspos_entry_rate', moneyFmt(price));
		var bar = document.getElementById('vspos_quick_entry');
		if (bar) bar.classList.add('is-ready');
		setVal('search_product', '');
		focusId('vspos_entry_qty');
	}
	function pickExactSku(products, term) {
		var termL = String(term || '').toLowerCase();
		for (var i = 0; i < products.length; i++) {
			if (!products[i] || !products[i].variation_id || products[i].auto_added) continue;
			var sku = String(products[i].sub_sku || products[i].sku || '').toLowerCase();
			if (sku === termL) return products[i];
		}
		return null;
	}
	function pickExactName(products, term) {
		var termL = String(term || '').toLowerCase().replace(/\s+/g, ' ').trim();
		for (var i = 0; i < products.length; i++) {
			if (!products[i] || !products[i].variation_id || products[i].auto_added) continue;
			var name = String(products[i].name || '').toLowerCase().replace(/\s+/g, ' ').trim();
			if (name === termL) return products[i];
		}
		return null;
	}
	/**
	 * Exact SKU / single name hit → fill Qty/Rate.
	 * Never opens F8 (live autocomplete handles name search).
	 */
	function lookup(term) {
		term = String(term || '').trim();
		if (!term) {
			return;
		}
		if (!locId()) {
			if (window.toastr) toastr.warning('Select location');
			return;
		}
		if (window.__vspos_looking) return;
		window.__vspos_looking = true;
		var bar = document.getElementById('vspos_quick_entry');
		if (bar) bar.classList.add('is-looking');

		var qs = new URLSearchParams();
		qs.set('term', term);
		qs.set('location_id', locId());
		qs.set('not_for_selling', '0');
		qs.set('auto_add_single', '0');
		qs.append('search_fields[]', 'sku');
		qs.append('search_fields[]', 'name');
		var cid = document.getElementById('customer_id');
		if (cid && cid.value) qs.set('customer_id', cid.value);
		var pg = document.getElementById('price_group') || document.getElementById('default_price_group');
		if (pg && pg.value) qs.set('price_group', pg.value);

		var base = (typeof base_path !== 'undefined' && base_path) ? base_path : '';
		fetch(base + '/products/list?' + qs.toString(), {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			}
		})
		.then(function (r) {
			if (!r.ok) throw new Error('HTTP ' + r.status);
			return r.json();
		})
		.then(function (data) {
			window.__vspos_looking = false;
			if (bar) bar.classList.remove('is-looking');
			var products = Array.isArray(data) ? data : (data && data.products) || [];
			products = products.filter(function (p) { return p && p.variation_id && !p.auto_added; });

			var item = pickExactSku(products, term);
			if (!item && !looksLikeBarcodeCode(term)) {
				item = pickExactName(products, term);
			}
			if (!item && products.length === 1 && !looksLikeBarcodeCode(term)) {
				item = products[0];
			}

			if (item) {
				window.__vspos_last_search_item = item;
				if (typeof window.vspos_entry_fill === 'function') {
					window.vspos_entry_fill(item, null);
					focusId('vspos_entry_qty');
				} else {
					fill(item);
				}
				return;
			}

			if (window.toastr) toastr.info('No matching products found');
			focusId('search_product');
		})
		.catch(function (err) {
			window.__vspos_looking = false;
			if (bar) bar.classList.remove('is-looking');
			console.error('Product lookup failed', err);
			if (window.toastr) toastr.error('Product lookup failed');
			focusId('search_product');
		});
	}
	function commit() {
		// Always use the POS commit (handles async stock-price + cart insert)
		if (typeof window.vspos_entry_commit === 'function') {
			window.__vspos_entry_committing = true;
			window.vspos_entry_commit();
			return;
		}
		var vid = getVal('vspos_entry_variation_id');
		if (!vid) {
			if (window.toastr) toastr.error('Scan / enter barcode or SKU first');
			focusId('search_product');
			return;
		}
		var qty = parseFloat(String(getVal('vspos_entry_qty')).replace(/,/g, '')) || 1;
		var rate = parseFloat(String(getVal('vspos_entry_rate')).replace(/,/g, '')) || 0;
		window.__vspos_pending_entry_rate = rate;
		window.__vspos_pending_entry_qty = qty;
		window.__vspos_entry_committing = true;
		try {
			if (typeof window.pos_product_row === 'function') {
				window.pos_product_row(vid, null, null, qty);
			}
			if (typeof window.vspos_apply_pending_entry_to_last_row === 'function') {
				window.vspos_apply_pending_entry_to_last_row();
			} else if (window.jQuery) {
				var $row = window.jQuery('#pos_table tbody tr.product_row').last();
				if ($row.length) {
					if (qty !== 1) {
						var $qty = $row.find('td input.pos_quantity').first();
						if ($qty.length && typeof window.__write_number === 'function') {
							window.__write_number($qty, qty);
							$qty.trigger('change');
						}
					}
					var $price = $row.find('td.pos-td-unitprice input.pos_unit_price').first();
					if ($price.length && typeof window.__write_number === 'function') {
						window.__write_number($price, rate);
						$price.trigger('change').trigger('blur');
					}
				}
			}
			setVal('vspos_entry_variation_id', '');
			setVal('vspos_entry_desc', '');
			setVal('vspos_entry_qty', '1');
			setVal('vspos_entry_rate', '');
			var bar2 = document.getElementById('vspos_quick_entry');
			if (bar2) bar2.classList.remove('is-ready');
			focusId('search_product');
		} finally {
			window.__vspos_entry_committing = false;
		}
	}

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter' && e.which !== 13) return;
		if (!document.getElementById('vspos_quick_entry')) return;
		var t = e.target;
		if (!t || !t.id) return;
		var id = t.id;
		if (id !== 'search_product' && id !== 'vspos_entry_desc' && id !== 'vspos_entry_qty' && id !== 'vspos_entry_rate') return;
		// Live search owns Enter on #search_product
		if (id === 'search_product' && window.vsposLiveSearch) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		if (e.stopImmediatePropagation) e.stopImmediatePropagation();
		if (id === 'search_product') {
			var term = String(t.value || '').trim();
			if (term) {
				lookup(term);
			}
			return;
		}
		if (id === 'vspos_entry_desc') {
			if (!getVal('vspos_entry_variation_id')) { focusId('search_product'); return; }
			focusId('vspos_entry_qty');
			return;
		}
		if (id === 'vspos_entry_qty') {
			if (!getVal('vspos_entry_variation_id')) { focusId('search_product'); return; }
			focusId('vspos_entry_rate');
			return;
		}
		if (id === 'vspos_entry_rate') commit();
	}, true);

	/**
	 * Scanner-only auto-lookup: USB scanners type very fast then pause.
	 * Slow typing (e.g. 00021) waits for Enter so 0002 is never matched early.
	 */
	(function bindScannerAuto() {
		var searchEl = document.getElementById('search_product');
		if (!searchEl || searchEl.__vspos_scan_bound) return;
		searchEl.__vspos_scan_bound = true;
		var scanTimer = null;
		var lastInputAt = 0;
		var burstCount = 0;
		var humanGapMs = 85;
		var scanQuietMs = 140;

		searchEl.addEventListener('input', function () {
			var now = Date.now();
			var term = String(this.value || '').trim();
			if (scanTimer) clearTimeout(scanTimer);

			if (!term) {
				burstCount = 0;
				lastInputAt = 0;
				return;
			}

			var gap = lastInputAt ? (now - lastInputAt) : 0;
			lastInputAt = now;

			if (gap > humanGapMs) {
				burstCount = 1;
			} else {
				burstCount += 1;
			}

			// Only auto-fill after a fast burst (scanner). Typing waits for Enter.
			if (!looksLikeBarcodeCode(term) || burstCount < 4) {
				return;
			}

			scanTimer = setTimeout(function () {
				if (getVal('search_product') !== term) return;
				if (window.__vspos_looking) return;
				if (burstCount < 4) return;
				lookup(term);
			}, scanQuietMs);
		});

		searchEl.addEventListener('keydown', function (e) {
			// Enter always handled by capture listener above — cancel pending scan timer
			if (e.key === 'Enter' || e.which === 13) {
				if (scanTimer) clearTimeout(scanTimer);
				burstCount = 0;
			}
		});
	})();

	// Expose lookup so other scripts can reuse
	window.vspos_entry_lookup = lookup;
	window.vspos_open_bulk_from_search = openBulk;
})();
</script>
@endif
</div>
<div class="row">
	@if(!empty($pos_settings['show_invoice_layout']))
	<div class="col-md-4">
		<div class="form-group">
		{!! Form::select('invoice_layout_id', 
					$invoice_layouts, $default_location->invoice_layout_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.select_invoice_layout'), 'id' => 'invoice_layout_id']); !!}
		</div>
	</div>
	@endif
	<input type="hidden" name="pay_term_number" id="pay_term_number" value="{{ !empty($edit) ? ($transaction->pay_term_number ?? '') : ($walk_in_customer['pay_term_number'] ?? '') }}">
	<input type="hidden" name="pay_term_type" id="pay_term_type" value="{{ !empty($edit) ? ($transaction->pay_term_type ?? '') : ($walk_in_customer['pay_term_type'] ?? '') }}">
	
	@if(!empty($commission_agent))
		@php
			$is_commission_agent_required = !empty($pos_settings['is_commission_agent_required']);
		@endphp
		<div class="col-md-4">
			<div class="form-group">
			{!! Form::select('commission_agent', 
						$commission_agent, !empty($edit) ? $transaction->commission_agent : null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.commission_agent'), 'id' => 'commission_agent', 'required' => $is_commission_agent_required]); !!}
			</div>
		</div>
	@endif
	@if(!empty($pos_settings['enable_transaction_date']))
		<div class="col-md-4 col-sm-6">
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</span>
					{!! Form::text('transaction_date', !empty($edit) ? @format_datetime($transaction->transaction_date) : $default_datetime, ['class' => 'form-control', 'readonly', 'required', 'id' => 'transaction_date']); !!}
				</div>
			</div>
		</div>
	@endif
	@if(config('constants.enable_sell_in_diff_currency') == true)
		<div class="col-md-4 col-sm-6">
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fas fa-exchange-alt"></i>
					</span>
					{!! Form::text('exchange_rate', !empty($edit) ? @num_format($transaction->exchange_rate) : config('constants.currency_exchange_rate'), ['class' => 'form-control input-sm input_number', 'placeholder' => __('lang_v1.currency_exchange_rate'), 'id' => 'exchange_rate']); !!}
				</div>
			</div>
		</div>
	@endif
	@php
		$wholesale_enabled = !empty($wholesale_enabled);
		$wholesale_price_group_id = !empty($wholesale_price_group_id) ? $wholesale_price_group_id : null;
		$selected_mode_pg = !empty($edit) && !empty($transaction->selling_price_group_id)
			? (int) $transaction->selling_price_group_id
			: (!empty($default_price_group_id) ? (int) $default_price_group_id : 0);
		$is_wholesale_mode = $wholesale_enabled && $wholesale_price_group_id
			&& (int) $selected_mode_pg === (int) $wholesale_price_group_id;
		$use_wholesale_toggle_only = $wholesale_enabled && !empty($wholesale_price_group_id)
			&& (empty($price_groups) || count($price_groups) <= 2);
	@endphp
	{{-- Retail/Wholesale switch lives in POS header (next to HIGH) --}}
	@if($use_wholesale_toggle_only)
		{!! Form::hidden('hidden_price_group', $is_wholesale_mode ? $wholesale_price_group_id : 0, ['id' => 'hidden_price_group']) !!}
		{!! Form::hidden('price_group', $is_wholesale_mode ? $wholesale_price_group_id : 0, ['id' => 'price_group']) !!}
	@elseif(!empty($price_groups) && count($price_groups) > 1)
		<div class="col-md-4 col-sm-6">
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fas fa-money-bill-alt"></i>
					</span>
					@php
						reset($price_groups);
						$selected_price_group = !empty($edit) && !empty($transaction->selling_price_group_id)
							? $transaction->selling_price_group_id
							: (!empty($default_price_group_id) && array_key_exists($default_price_group_id, $price_groups) ? $default_price_group_id : null);
					@endphp
					{!! Form::hidden('hidden_price_group', key($price_groups), ['id' => 'hidden_price_group']) !!}
					{!! Form::select('price_group', $price_groups, $selected_price_group, ['class' => 'form-control select2', 'id' => 'price_group']); !!}
					<span class="input-group-addon">
						@show_tooltip(__('lang_v1.price_group_help_text'))
					</span> 
				</div>
			</div>
		</div>
	@elseif(!empty($price_groups))
		@php
			reset($price_groups);
			$single_price_group = !empty($edit) && !empty($transaction->selling_price_group_id)
				? $transaction->selling_price_group_id
				: key($price_groups);
		@endphp
		{!! Form::hidden('price_group', $single_price_group, ['id' => 'price_group']) !!}
	@else
		{!! Form::hidden('price_group', !empty($edit) ? ($transaction->selling_price_group_id ?? '') : '', ['id' => 'price_group']) !!}
	@endif
	@if(!empty($default_price_group_id))
		{!! Form::hidden('default_price_group', $default_price_group_id, ['id' => 'default_price_group']) !!}
	@endif

	@if(in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
		<div class="col-md-4 col-sm-6">
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fa fa-external-link-square-alt text-primary service_modal_btn"></i>
					</span>
					{!! Form::select('types_of_service_id', $types_of_service, !empty($edit) ? $transaction->types_of_service_id : null, ['class' => 'form-control', 'id' => 'types_of_service_id', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.select_types_of_service')]); !!}

					{!! Form::hidden('types_of_service_price_group', null, ['id' => 'types_of_service_price_group']) !!}

					<span class="input-group-addon">
						@show_tooltip(__('lang_v1.types_of_service_help'))
					</span> 
				</div>
				<small><p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'): <span></span></p></small>
			</div>
		</div>
		<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
	@endif

	@if(!empty($pos_settings['show_invoice_scheme']))
		@php
			$invoice_scheme_id = $default_invoice_schemes->id;
			if(!empty($default_location->invoice_scheme_id)) {
				$invoice_scheme_id = $default_location->invoice_scheme_id;
			}
		@endphp
		<div class="col-md-4 col-sm-6">
			<div class="form-group">
				{!! Form::select('invoice_scheme_id', $invoice_schemes, $invoice_scheme_id, 
					['class' => 'form-control', 'placeholder' => __('lang_v1.select_invoice_scheme'), 
					'id' => 'invoice_scheme_id']); !!}
			</div>
		</div>
	@endif
	@if(in_array('subscription', $enabled_modules))
		<div class="col-md-4 col-sm-6">
			<label>
              {!! Form::checkbox('is_recurring', 1, !empty($edit) ? $transaction->is_recurring : false, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
            </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link-square-alt"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
		</div>
	@endif
	
	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<span id="restaurant_module_span"
    		@if(!empty($edit) && !empty($transaction)) data-transaction_id="{{ $transaction->id }}" @endif>
      		<div class="col-md-3"></div>
    	</span>
    @endif

	@if(in_array('kitchen' ,$enabled_modules))
		<div class="col-md-3">
			<div class="form-group">
				<div class="checkbox">
				<label>
						{!! Form::checkbox('is_kitchen_order', 1, !empty($edit) ? $transaction->is_kitchen_order : false, ['class' => 'input-icheck status', 'id' => 'is_kitchen_order']); !!} {{ __('lang_v1.kitchen_order') }}
				</label>
				@show_tooltip(__('lang_v1.kitchen_order_tooltip'))
				</div>
			</div>
		</div>
    @endif
    
</div>
<!-- include module fields -->
@if(!empty($pos_module_data))
    @foreach($pos_module_data as $key => $value)
        @if(!empty($value['view_path']))
            @includeIf($value['view_path'], ['view_data' => $value['view_data']])
        @endif
    @endforeach
@endif
<div class="row" style="margin:0;">
	<div class="col-sm-12 pos_product_div" style="padding:4px 0 0 0;">
		<div class="vspos-cart-watermark" aria-hidden="true">
			<img src="{{ asset('img/qpos-avenque-logo.png?v=' . config('constants.asset_version')) }}" alt="">
		</div>
		<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">

		<!-- Keeps count of product rows -->
		<input type="hidden" id="product_row_count" 
			value="{{ !empty($edit) && !empty($sell_details) ? count($sell_details) : 0 }}">
		@php
			$hide_tax = '';
			if( session()->get('business.enable_inline_tax') == 0){
				$hide_tax = 'hide';
			}
		@endphp
		<table class="table table-condensed table-responsive tw-border-0 vspos-ticket-cart" id="pos_table">
			<colgroup>
				<col class="pos-col-serial">
				<col class="pos-col-product">
				<col class="pos-col-qty">
				@if(empty($is_direct_sell))
				<col class="pos-col-unitprice">
				@endif
				@if(!empty($pos_settings['inline_service_staff']))
				<col class="pos-col-staff">
				@endif
				<col class="pos-col-price {{$hide_tax}} @if(empty($is_direct_sell)) hide @endif">
				<col class="pos-col-subtotal">
				<col class="pos-col-action">
			</colgroup>
			<thead>
				<tr class="vspos-ticket-head">
					<th class="pos-th-serial" style="text-align:center;">#</th>
					<th class="text-left pos-th-product">
						Item @show_tooltip(__('lang_v1.tooltip_sell_product_column'))
					</th>
					<th class="text-center pos-th-qty">Qty</th>
					@if(empty($is_direct_sell))
					<th class="text-center pos-th-unitprice">Rate</th>
					@endif
					@if(!empty($pos_settings['inline_service_staff']))
						<th class="text-center pos-th-staff">
							@lang('restaurant.service_staff')
						</th>
					@endif
					<th class="text-center pos-th-price {{$hide_tax}} @if(empty($is_direct_sell)) hide @endif">
						@lang('sale.price_inc_tax')
					</th>
					<th class="text-right pos-th-subtotal">Total</th>
					<th class="pos-th-action"></th>
				</tr>
			</thead>
			<tbody>
				@if(!empty($edit) && !empty($sell_details))
					@foreach($sell_details as $sell_line)
						@include('sale_pos.product_row', [
							'product' => $sell_line,
							'row_count' => $loop->index,
							'tax_dropdown' => $taxes,
							'sub_units' => !empty($sell_line->unit_details) ? $sell_line->unit_details : [],
							'action' => 'edit'
						])
					@endforeach
				@else
				<tr class="pos-empty-state-row">
					<td colspan="100" class="!tw-border-0 !tw-p-0">
						<div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center tw-py-10 md:tw-py-14 tw-px-6 tw-gap-3 vspos-empty-state">
							<div class="vspos-empty-state-icon tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center">
								<i class="fi fi-rr-shopping-cart pos-fi-icon" style="font-size:28px;"></i>
							</div>
							<div class="vspos-empty-state-title tw-text-[15px] tw-font-semibold">Your cart is empty</div>
							<div class="vspos-empty-state-sub tw-text-[13px] tw-leading-relaxed tw-max-w-sm">Enter barcode/SKU → set Qty &amp; Rate → Enter to add.</div>
						</div>
					</td>
				</tr>
				@endif
			</tbody>
		</table>
		<style>
			/* Quick entry bar: Search | Description | Qty | Rate */
			.vspos-entry-bar {
				display: grid;
				grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1.6fr) 88px 110px;
				gap: 8px;
				align-items: end;
				margin: 0 0 6px;
				padding: 8px;
				border-radius: 12px;
				background: rgba(245, 197, 24, 0.06);
				border: 1px solid rgba(245, 197, 24, 0.22);
			}
			.vspos-entry-label {
				display: block;
				font-size: 10px;
				font-weight: 800;
				letter-spacing: 0.06em;
				text-transform: uppercase;
				color: #9ca3af;
				margin: 0 0 4px;
			}
			.vspos-entry-col .form-control {
				height: 40px;
				border-radius: 8px;
				font-weight: 600;
			}
			.vspos-entry-search-row {
				display: flex;
				align-items: stretch;
				gap: 8px;
				width: 100%;
			}
			.vspos-entry-search-field {
				flex: 1 1 auto;
				min-width: 0;
				margin: 0;
			}
			.vspos-entry-search-field .input-group-addon {
				border-radius: 8px 0 0 8px !important;
			}
			.vspos-entry-search-field .form-control {
				height: 40px;
				border-radius: 0 8px 8px 0 !important;
			}
			.vspos-entry-search-field .input-group-btn .btn {
				height: 40px;
			}
			.vspos-entry-bar .vspos-entry-search-btn {
				flex: 0 0 auto;
				height: 40px !important;
				min-width: 64px;
				margin: 0;
				border-radius: 10px !important;
				align-self: stretch;
				z-index: 2;
				position: relative;
			}
			.vspos-entry-desc .form-control {
				background: #f8fafc;
				color: #0f172a;
				font-weight: 700;
			}
			.vspos-entry-qty .form-control,
			.vspos-entry-rate .form-control {
				font-variant-numeric: tabular-nums;
			}
			.vspos-entry-bar.is-looking { opacity: 0.85; }
			.vspos-entry-bar.is-ready #vspos_entry_desc { border-color: #f5c518; }
			@media (max-width: 900px) {
				.vspos-entry-bar { grid-template-columns: 1fr 1fr; }
				.vspos-entry-search,
				.vspos-entry-desc { grid-column: 1 / -1; }
			}

			#pos_table:not(.pos-has-rows) thead { display: none !important; }
			#pos_table.pos-has-rows .pos-empty-state-row { display: none !important; }
			#add_pos_sell_form:not(.pos-has-rows) .vspos-summary-cards,
			#add_pos_sell_form:not(.pos-has-rows) .vspos-total-hero,
			#edit_pos_sell_form:not(.pos-has-rows) .vspos-summary-cards,
			#edit_pos_sell_form:not(.pos-has-rows) .vspos-total-hero { display: none !important; }

			.pos_product_div {
				position: relative;
				max-height: calc(100vh - 300px);
				overflow-y: auto;
				padding: 0 4px 8px;
			}
			@media (min-width: 992px) and (max-width: 1680px) {
				.pos_product_div {
					max-height: none;
					flex: 1 1 auto;
					min-height: 0;
					padding: 0 2px 4px;
				}
				.vspos-entry-bar {
					grid-template-columns: minmax(140px, 1.3fr) minmax(110px, 1.4fr) 72px 92px;
					gap: 6px;
					padding: 6px;
					margin: 0 0 4px;
				}
				.vspos-entry-col .form-control,
				.vspos-entry-search-field .form-control,
				.vspos-entry-search-field .input-group-btn .btn,
				.vspos-entry-bar .vspos-entry-search-btn {
					height: 34px;
				}
				.vspos-entry-bar .vspos-entry-search-btn {
					min-width: 54px;
				}
				#pos_table.vspos-ticket-cart {
					border-spacing: 0 6px !important;
				}
				#pos_table.vspos-ticket-cart tbody tr.product_row > td {
					padding: 8px 6px !important;
				}
			}

			/* ── QPOS ticket cart (distinct from UltimatePOS table) ── */
			#pos_table.vspos-ticket-cart {
				border-collapse: separate !important;
				border-spacing: 0 10px !important;
				width: 100%;
				margin: 0;
			}
			#pos_table.vspos-ticket-cart thead th {
				position: sticky;
				top: 0;
				z-index: 5;
				background: transparent !important;
				border: 0 !important;
				padding: 4px 8px 2px !important;
				font-size: 10px !important;
				font-weight: 700 !important;
				letter-spacing: 0.08em;
				text-transform: uppercase;
				color: #737373 !important;
				white-space: nowrap;
			}
			#pos_table.vspos-ticket-cart tbody tr.product_row > td {
				background: #121212 !important;
				border-top: 1px solid #262626 !important;
				border-bottom: 1px solid #262626 !important;
				padding: 12px 10px !important;
				vertical-align: middle !important;
			}
			#pos_table.vspos-ticket-cart tbody tr.product_row > td:first-child {
				border-left: 3px solid #f5c518 !important;
				border-radius: 12px 0 0 12px;
				padding-left: 8px !important;
			}
			#pos_table.vspos-ticket-cart tbody tr.product_row > td:last-child {
				border-right: 1px solid #262626 !important;
				border-radius: 0 12px 12px 0;
			}
			#pos_table.vspos-ticket-cart tbody tr.product_row:hover > td {
				background: #171717 !important;
				border-color: #3a3a3a !important;
			}
			#pos_table.vspos-ticket-cart tbody tr.product_row:hover > td:first-child {
				border-left-color: #f5c518 !important;
			}

			#pos_table tbody tr td.pos-td-serial {
				width: 40px !important;
				text-align: center;
				font-size: 12px;
				font-weight: 800;
				color: #f5c518;
				font-variant-numeric: tabular-nums;
			}

			.vspos-line-product {
				display: flex;
				align-items: center;
				gap: 6px;
				min-width: 0;
			}
			.vspos-line-thumb {
				width: 32px;
				height: 32px;
				flex-shrink: 0;
				border-radius: 8px;
				object-fit: cover;
				background: #1a1a1a;
				border: 1px solid #2a2a2a;
			}
			.vspos-line-meta { min-width: 0; flex: 1; }
			.vspos-line-name {
				display: -webkit-box;
				-webkit-line-clamp: 1;
				-webkit-box-orient: vertical;
				overflow: hidden;
				font-size: 12px;
				font-weight: 700;
				line-height: 1.2;
				color: #f5f5f5;
				cursor: pointer;
			}
			.vspos-line-edit-ico {
				margin-left: 6px;
				font-size: 10px;
				opacity: 0.45;
				color: #f5c518;
			}
			.vspos-line-name:hover .vspos-line-edit-ico { opacity: 1; }
			.vspos-line-sku {
				margin-top: 1px;
				font-size: 10px;
				color: #737373;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			/* Qty pill stepper — compact, left-aligned */
			.vspos-qty-stepper {
				display: inline-flex !important;
				align-items: stretch;
				width: auto !important;
				max-width: 122px !important;
				margin: 0 !important;
				border-radius: 999px;
				overflow: hidden;
				border: 1px solid #333 !important;
				background: #0a0a0a;
				box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
			}
			.vspos-qty-stepper .input-group-btn {
				display: inline-flex !important;
				width: auto !important;
				flex: 0 0 auto;
			}
			.vspos-qty-stepper .vspos-qty-btn,
			.vspos-qty-stepper .btn {
				width: 26px !important;
				min-width: 26px !important;
				height: 30px !important;
				padding: 0 !important;
				margin: 0 !important;
				border: 0 !important;
				border-radius: 0 !important;
				background: transparent !important;
				color: #f5c518 !important;
				box-shadow: none !important;
				display: inline-flex !important;
				align-items: center;
				justify-content: center;
				flex: 0 0 26px !important;
			}
			.vspos-qty-stepper .vspos-qty-btn:hover,
			.vspos-qty-stepper .btn:hover {
				background: #1a1a1a !important;
				color: #ffe566 !important;
			}
			.vspos-qty-stepper input.pos_quantity {
				width: 56px !important;
				min-width: 52px !important;
				max-width: 64px !important;
				flex: 0 0 56px !important;
				height: 30px !important;
				border: 0 !important;
				border-left: 1px solid #2a2a2a !important;
				border-right: 1px solid #2a2a2a !important;
				border-radius: 0 !important;
				background: #111 !important;
				color: #fff !important;
				font-weight: 800 !important;
				font-size: 12px !important;
				text-align: center !important;
				font-variant-numeric: tabular-nums;
				box-shadow: none !important;
			}
			.pos-unit-label {
				display: block;
				text-align: left;
				margin-top: 5px;
				margin-left: 2px;
				font-size: 10px;
				font-weight: 600;
				letter-spacing: 0.04em;
				text-transform: uppercase;
				color: #737373;
			}

			/* Rate field */
			.vspos-rate-field {
				display: inline-flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: 2px;
			}
			.vspos-rate-field input.pos_unit_price {
				width: 76px !important;
				height: 30px !important;
				border-radius: 8px !important;
				border: 1px solid #333 !important;
				background: #0a0a0a !important;
				color: #f5f5f5 !important;
				font-weight: 700 !important;
				font-size: 12px !important;
				text-align: center !important;
				font-variant-numeric: tabular-nums;
				box-shadow: none !important;
			}
			.vspos-rate-field input.pos_unit_price:focus {
				border-color: #f5c518 !important;
				outline: none;
				box-shadow: 0 0 0 2px rgba(245, 197, 24, 0.2) !important;
			}
			.vspos-rate-readonly {
				font-weight: 700;
				font-size: 13px;
				color: #f5f5f5;
			}
			.vspos-last-buy-hint {
				display: block;
				max-width: 90px;
				font-size: 9px !important;
				line-height: 1.2;
				color: #737373 !important;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				text-align: center;
			}

			/* Line total */
			.vspos-line-total {
				display: flex;
				flex-direction: column;
				align-items: flex-end;
				gap: 2px;
			}
			.vspos-line-total .pos_line_total_text {
				font-size: 15px !important;
				font-weight: 800 !important;
				color: #f5c518 !important;
				font-variant-numeric: tabular-nums;
				letter-spacing: -0.01em;
			}

			/* Remove — ghost X, not UltimatePOS red trash circle */
			.vspos-line-remove {
				width: 34px;
				height: 34px;
				border-radius: 10px;
				border: 1px solid #333;
				background: transparent;
				color: #a3a3a3;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				transition: background .15s ease, color .15s ease, border-color .15s ease;
			}
			.vspos-line-remove:hover {
				background: rgba(239, 68, 68, 0.12);
				border-color: #ef4444;
				color: #ef4444;
			}

			@media (min-width: 768px) {
				#pos_table col.pos-col-serial { width: 28px; }
				#pos_table col.pos-col-product { width: 42%; }
				#pos_table col.pos-col-qty { width: 18%; min-width: 100px; }
				#pos_table col.pos-col-unitprice { width: 16%; min-width: 84px; }
				#pos_table col.pos-col-subtotal { width: 18%; min-width: 100px; }
				#pos_table col.pos-col-action { width: 40px; }
				#pos_table .pos-th-qty,
				#pos_table .pos-td-qty { text-align: center; }
				#pos_table .pos-th-unitprice,
				#pos_table .pos-td-unitprice { text-align: center; }
				#pos_table .pos-td-product { padding-right: 10px; text-align: left; }
				#pos_table .pos-td-subtotal {
					text-align: right !important;
					padding-right: 8px !important;
					white-space: nowrap;
				}
			}

			@media (max-width: 767px) {
				.pos_product_div {
					max-height: calc(100vh - 340px);
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
				#pos_table.vspos-ticket-cart {
					min-width: 0 !important;
					width: 100% !important;
					font-size: 12px;
				}
				body:not(.vspos-touch-ui) #pos_table .pos-th-unitprice,
				body:not(.vspos-touch-ui) #pos_table .pos-td-unitprice { display: none; }
				body:not(.vspos-touch-ui) #pos_table .pos-td-product { max-width: 140px; }
				.vspos-line-thumb { width: 34px; height: 34px; border-radius: 8px; }
				.vspos-line-name { font-size: 12px; }
				.vspos-qty-stepper input.pos_quantity { width: 52px !important; }
				.vspos-line-total .pos_line_total_text { font-size: 13px !important; }
			}

			.vspos-customer-meta {
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 6px;
				margin-top: 6px;
				min-height: 0;
			}
			.vspos-customer-meta:empty { display: none; }
			.pos-loyalty-badge,
			.pos-balance-badge {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				margin-top: 0;
				padding: 4px 10px;
				border-radius: 999px;
				font-size: 12px;
				font-weight: 600;
				line-height: 1.2;
				white-space: nowrap;
			}
			.pos-loyalty-badge {
				background: linear-gradient(135deg, rgba(234, 179, 8, 0.18), rgba(245, 158, 11, 0.12));
				border: 1px solid rgba(234, 179, 8, 0.45);
				color: #fbbf24;
			}
			.pos-loyalty-badge.hide,
			.pos-balance-badge.hide { display: none !important; }
			.pos-loyalty-badge-icon,
			.pos-balance-badge-icon {
				display: inline-flex;
				width: 18px;
				height: 18px;
				align-items: center;
				justify-content: center;
				border-radius: 50%;
				background: rgba(234, 179, 8, 0.25);
				font-size: 10px;
			}
			.pos-loyalty-badge-label,
			.pos-balance-badge-label { opacity: 0.9; font-weight: 500; }
			.pos-loyalty-badge-points {
				color: #fde68a;
				font-weight: 800;
				letter-spacing: 0.2px;
			}
			.pos-balance-badge--due {
				background: linear-gradient(135deg, rgba(239, 68, 68, 0.16), rgba(220, 38, 38, 0.1));
				border: 1px solid rgba(248, 113, 113, 0.45);
				color: #f87171;
			}
			.pos-balance-badge--due .pos-balance-badge-icon {
				background: rgba(239, 68, 68, 0.25);
			}
			.pos-balance-badge--due .pos-balance-badge-value {
				color: #fecaca;
				font-weight: 800;
			}
			.pos-balance-badge--advance {
				background: linear-gradient(135deg, rgba(34, 197, 94, 0.16), rgba(22, 163, 74, 0.1));
				border: 1px solid rgba(74, 222, 128, 0.4);
				color: #4ade80;
			}
			.pos-balance-badge--advance .pos-balance-badge-icon {
				background: rgba(34, 197, 94, 0.25);
			}
			.pos-balance-badge--advance .pos-balance-badge-value {
				color: #bbf7d0;
				font-weight: 800;
			}
			html[data-vspos-theme="light"] .pos-loyalty-badge {
				background: linear-gradient(135deg, #fef3c7, #fde68a);
				border-color: #f59e0b;
				color: #92400e;
			}
			html[data-vspos-theme="light"] .pos-loyalty-badge-points { color: #b45309; }
			html[data-vspos-theme="light"] .pos-balance-badge--due {
				background: #fee2e2;
				border-color: #f87171;
				color: #991b1b;
			}
			html[data-vspos-theme="light"] .pos-balance-badge--due .pos-balance-badge-value { color: #b91c1c; }
			html[data-vspos-theme="light"] .pos-balance-badge--advance {
				background: #dcfce7;
				border-color: #4ade80;
				color: #166534;
			}
			html[data-vspos-theme="light"] .pos-balance-badge--advance .pos-balance-badge-value { color: #15803d; }
			.contact_due_text { display: none !important; }
		</style>
	</div>
</div>