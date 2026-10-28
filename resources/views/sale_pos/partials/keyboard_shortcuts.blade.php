<script type="text/javascript">
	$(document).ready( function() {
		if (typeof Mousetrap === 'undefined') {
			return;
		}

		// POS: allow shortcuts while focus is in inputs (barcode/qty), and always allow F-keys
		var _vsposOrigStop = Mousetrap.prototype.stopCallback;
		Mousetrap.prototype.stopCallback = function(e, element, combo) {
			if (combo && /^f([1-9]|1[0-2])$/i.test(String(combo).replace(/\s/g, ''))) {
				return false;
			}
			if (element && element.classList && element.classList.contains('mousetrap')) {
				return false;
			}
			// Search / qty / rate fields on POS should never block shortcuts
			if (element && element.id && (
				element.id === 'search_product' ||
				element.id === 'vspos_entry_qty' ||
				element.id === 'vspos_entry_rate' ||
				element.id === 'vspos_entry_desc' ||
				element.id === 'vspos_pay_amount_input'
			)) {
				return false;
			}
			return _vsposOrigStop.call(this, e, element, combo);
		};

		// Stop browser from stealing F3 (Find)
		document.addEventListener('keydown', function(e) {
			var k = (e.key || '').toLowerCase();
			if (k === 'f3' || e.keyCode === 114) {
				e.preventDefault();
			}
		}, true);

		function vsposTriggerPayShortcut(e) {
			if (e) {
				e.preventDefault();
				e.stopPropagation();
			}
			if (window._vsposPayShortcutLock) {
				return false;
			}
			window._vsposPayShortcutLock = true;
			setTimeout(function() { window._vsposPayShortcutLock = false; }, 400);

			if (typeof window.vsposOpenPayPopup === 'function') {
				window.vsposOpenPayPopup();
			} else if ($('.vspos-open-pay-popup:visible').length) {
				$('.vspos-open-pay-popup:visible').first().trigger('click');
			} else {
				$('#vspos_pos_finalize_trigger').trigger('click');
			}
			return false;
		}

		//shortcut for express checkout
		@if(!empty($shortcuts["pos"]["express_checkout"]) && empty($pos_settings['disable_express_checkout']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["express_checkout"]) }}', function(e) {
				e.preventDefault();
				$('button.pos-express-finalize[data-pay_method="cash"]:visible').first().trigger('click');
				return false;
			});
		@endif

		//shortcut for cancel checkout
		@if(!empty($shortcuts["pos"]["cancel"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["cancel"]) }}', function(e) {
				e.preventDefault();
				$('button.js-pos-cancel:visible').first().trigger('click');
				return false;
			});
		@endif

		//shortcut for draft checkout
		@if(!empty($shortcuts["pos"]["draft"]) && empty($pos_settings['disable_draft']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["draft"]) }}', function(e) {
				e.preventDefault();
				$('#pos-draft').trigger('click');
				return false;
			});
		@endif

		//shortcut for pay & checkout (e.g. F3)
		@if(!empty($shortcuts["pos"]["pay_n_ckeckout"]) && empty($pos_settings['disable_pay_checkout']))
			(function() {
				var payKey = '{{ strtolower($shortcuts["pos"]["pay_n_ckeckout"]) }}';
				Mousetrap.unbind(payKey);
				Mousetrap.bind(payKey, vsposTriggerPayShortcut);

				// Native capture fallback — works even if Mousetrap misses F-keys
				document.addEventListener('keydown', function(e) {
					var pressed = (e.key || '').toLowerCase();
					var codeMap = { 112:'f1',113:'f2',114:'f3',115:'f4',116:'f5',117:'f6',118:'f7',119:'f8',120:'f9',121:'f10',122:'f11',123:'f12' };
					if (e.keyCode && codeMap[e.keyCode]) {
						pressed = codeMap[e.keyCode];
					}
					if (pressed !== payKey) {
						return;
					}
					// Ignore when another modal is open (not pay popup)
					if ($('.modal:visible').length && !$('#modal_payment').is(':visible')) {
						return;
					}
					vsposTriggerPayShortcut(e);
				}, true);
			})();
		@endif

		//shortcut for edit discount
		@if(!empty($shortcuts["pos"]["edit_discount"]) && empty($pos_settings['disable_discount']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["edit_discount"]) }}', function(e) {
				e.preventDefault();
				$('#pos-edit-discount').trigger('click');
				return false;
			});
		@endif

		//shortcut for edit tax
		@if(!empty($shortcuts["pos"]["edit_order_tax"]) && empty($pos_settings['disable_order_tax']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["edit_order_tax"]) }}', function(e) {
				e.preventDefault();
				$('#pos-edit-tax').trigger('click');
				return false;
			});
		@endif

		//shortcut for add payment row
		@if(!empty($shortcuts["pos"]["add_payment_row"]) && empty($pos_settings['disable_pay_checkout']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["add_payment_row"]) }}', function(e, combo) {
				if($('#modal_payment').is(':visible') || !$('#vspos_pay_view_multipay').hasClass('vspos-pay-view--hidden')){
					e.preventDefault();
					$('#add-payment-row').trigger('click');
					return false;
				}
			});
		@endif

		//shortcut for finalize payment
		@if(!empty($shortcuts["pos"]["finalize_payment"]) && empty($pos_settings['disable_pay_checkout']))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["finalize_payment"]) }}', function(e, combo) {
				if($('#modal_payment').is(':visible')){
					e.preventDefault();
					$('#pos-save-footer').trigger('click');
					return false;
				} else if(!$('#vspos_pay_view_multipay').hasClass('vspos-pay-view--hidden')){
					e.preventDefault();
					$('#vspos_multipay_finalize').trigger('click');
					return false;
				}
			});
		@endif

		//Shortcuts to go recent product quantity
		@if(!empty($shortcuts["pos"]["recent_product_quantity"]))
			shortcut_length_prev = 0;
			shortcut_position_now = null;

			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["recent_product_quantity"]) }}', function(e, combo) {
				var length_now = $('table#pos_table tr').length;

				if(length_now != shortcut_length_prev){
					shortcut_length_prev = length_now;
					shortcut_position_now = length_now;
				} else {
					shortcut_position_now = shortcut_position_now - 1;
				}

				var last_qty_field = $('table#pos_table tr').eq(shortcut_position_now - 1).contents().find('input.pos_quantity');
				if(last_qty_field.length >=1){
					last_qty_field.focus().select();
				} else {
					shortcut_position_now = length_now + 1;
					Mousetrap.trigger('{{ strtolower($shortcuts["pos"]["recent_product_quantity"]) }}');
				}
				return false;
			});

			//On focus of quantity field go back to search when stop typing
			var timeout = null;
			$('table#pos_table').on('focus', 'input.pos_quantity', function () {
			    $(this).on('keyup', function(e){

			    	if (timeout !== null) {
			        	clearTimeout(timeout);
			    	}

			    	var code = e.keyCode || e.which;
			    	if (code != '9') {
    					timeout = setTimeout(function () {
			        		$('input#search_product').focus().select();
			    		}, 5000);
    				}
			    });
			});
		@endif

		//shortcut to go to add new products
		@if(!empty($shortcuts["pos"]["add_new_product"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["add_new_product"]) }}', function(e) {
				e.preventDefault();
				var $btn = $('button.pos_add_quick_product').first();
				if ($btn.length) {
					$btn.trigger('click');
				} else {
					$('input#search_product').focus().select();
				}
				return false;
			});
		@endif

		// Bulk product search (name / code · cost & stock) — default F8 (F7 used by IC mode)
		@php
			$bulk_search_key = !empty($shortcuts['pos']['bulk_product_search'])
				? strtolower($shortcuts['pos']['bulk_product_search'])
				: 'f8';
			$loyalty_customer_key = !empty($shortcuts['pos']['loyalty_customer'])
				? strtolower($shortcuts['pos']['loyalty_customer'])
				: 'f10';
			$pay_supplier_key = !empty($shortcuts['pos']['pay_supplier'])
				? strtolower($shortcuts['pos']['pay_supplier'])
				: 'f9';
		@endphp
		Mousetrap.bind('{{ $bulk_search_key }}', function(e) {
			e.preventDefault();
			if (typeof window.vsposOpenBulkProductSearch === 'function') {
				window.vsposOpenBulkProductSearch();
			} else {
				$('#vspos_bulk_search_btn').trigger('click');
			}
			return false;
		});

		// Loyalty / customer by mobile — default F10
		@if(session('business.enable_rp') == 1)
		Mousetrap.bind('{{ $loyalty_customer_key }}', function(e) {
			e.preventDefault();
			if (typeof window.vsposOpenLoyaltyCustomer === 'function') {
				window.vsposOpenLoyaltyCustomer();
			} else {
				$('#vspos_loyalty_open_btn').trigger('click');
			}
			return false;
		});
		// Native F-key capture so it works even inside inputs / modals (except pay)
		document.addEventListener('keydown', function(e) {
			var codeMap = { 112:'f1',113:'f2',114:'f3',115:'f4',116:'f5',117:'f6',118:'f7',119:'f8',120:'f9',121:'f10',122:'f11',123:'f12' };
			var pressed = (e.key || '').toLowerCase();
			if (e.keyCode && codeMap[e.keyCode]) {
				pressed = codeMap[e.keyCode];
			}
			if (pressed !== '{{ $loyalty_customer_key }}') {
				return;
			}
			if ($('#vspos_mobile_pay_popup').hasClass('vspos-mobile-pay-popup--open') || $('#modal_payment').is(':visible')) {
				return;
			}
			e.preventDefault();
			if (typeof window.vsposOpenLoyaltyCustomer === 'function') {
				window.vsposOpenLoyaltyCustomer();
			}
		}, true);
		@endif

		//shortcut for weighing scale
		@if(!empty($shortcuts["pos"]["weighing_scale"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["weighing_scale"]) }}', function(e) {
				e.preventDefault();
				$('button#weighing_scale_btn').trigger('click');
				return false;
			});
		@endif

		//shortcut for pay supplier — default F9
		Mousetrap.bind('{{ $pay_supplier_key }}', function(e) {
			e.preventDefault();
			$('#pos_supplier_pay_btn').trigger('click');
			return false;
		});

		//shortcut for card
		@if(!empty($shortcuts["pos"]["express_checkout_card"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["express_checkout_card"]) }}', function(e) {
				e.preventDefault();
				$('button.pos-express-finalize[data-pay_method="card"]:visible').first().trigger('click');
				return false;
			});
		@endif

		//shortcut for credit sale
		@if(!empty($shortcuts["pos"]["credit_sale"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["credit_sale"]) }}', function(e) {
				e.preventDefault();
				$('button.pos-express-finalize[data-pay_method="credit_sale"]:visible').first().trigger('click');
				return false;
			});
		@endif

		//shortcut for suspend
		@if(!empty($shortcuts["pos"]["suspend"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["suspend"]) }}', function(e) {
				e.preventDefault();
				$('button.pos-express-finalize[data-pay_method="suspend"]:visible').first().trigger('click');
				return false;
			});
		@endif

		//shortcut for quotation
		@if(!empty($shortcuts["pos"]["quotation"]))
			Mousetrap.bind('{{ strtolower($shortcuts["pos"]["quotation"]) }}', function(e) {
				e.preventDefault();
				$('#pos-quotation:visible').first().trigger('click');
				return false;
			});
		@endif
	});
</script>
