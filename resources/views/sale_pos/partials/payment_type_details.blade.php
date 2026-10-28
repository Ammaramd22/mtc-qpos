<div class="payment_details_div @if( $payment_line['method'] !== 'card' ) {{ 'hide' }} @endif" data-type="card" >
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_number_$row_index", __('lang_v1.card_no') . ' (first 4)') !!}
			{!! Form::text("payment[$row_index][card_number]", $payment_line['card_number'], ['class' => 'form-control card_number_input', 'placeholder' => 'e.g. 5455', 'id' => "card_number_$row_index", 'maxlength' => 4, 'inputmode' => 'numeric', 'autocomplete' => 'off']); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_holder_name_$row_index", __('lang_v1.card_holder_name')) !!}
			{!! Form::text("payment[$row_index][card_holder_name]", $payment_line['card_holder_name'], ['class' => 'form-control', 'placeholder' => __('lang_v1.card_holder_name'), 'id' => "card_holder_name_$row_index"]); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_transaction_number_$row_index",__('lang_v1.card_transaction_no')) !!}
			{!! Form::text("payment[$row_index][card_transaction_number]", $payment_line['card_transaction_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.card_transaction_no'), 'id' => "card_transaction_number_$row_index"]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_type_$row_index", __('lang_v1.card_type')) !!}
			{!! Form::select("payment[$row_index][card_type]", ['credit' => 'Credit Card', 'debit' => 'Debit Card','visa' => 'Visa', 'master' => 'MasterCard'], $payment_line['card_type'],['class' => 'form-control', 'id' => "card_type_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_month_$row_index", __('lang_v1.month')) !!}
			{!! Form::text("payment[$row_index][card_month]", $payment_line['card_month'], ['class' => 'form-control', 'placeholder' => __('lang_v1.month'),
			'id' => "card_month_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_year_$row_index", __('lang_v1.year')) !!}
			{!! Form::text("payment[$row_index][card_year]", $payment_line['card_year'], ['class' => 'form-control', 'placeholder' => __('lang_v1.year'), 'id' => "card_year_$row_index" ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_security_$row_index",__('lang_v1.security_code')) !!}
			{!! Form::text("payment[$row_index][card_security]", $payment_line['card_security'], ['class' => 'form-control', 'placeholder' => __('lang_v1.security_code'), 'id' => "card_security_$row_index"]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque">
	<div class="col-md-12">
		<div class="vspos-cheque-details-panel">
			<div class="vspos-cheque-details-title"><i class="fas fa-money-check-alt"></i>Cheque Details</div>
			<div class="vspos-cheque-details-grid">
				<div class="vspos-cheque-field">
					{!! Form::label("cheque_name_$row_index", __('lang_v1.cheque_name'), ['class' => 'vspos-cheque-label']) !!}
					<div class="input-group input-group-sm">
						<span class="input-group-addon vspos-cheque-addon"><i class="fas fa-user"></i></span>
						{!! Form::text("payment[$row_index][cheque_name]", $payment_line['cheque_name'] ?? '', ['class' => 'form-control cheque_name_input', 'placeholder' => __('lang_v1.cheque_name_placeholder'), 'id' => "cheque_name_$row_index", 'autocomplete' => 'off']); !!}
					</div>
				</div>
				<div class="vspos-cheque-field">
					{!! Form::label("cheque_number_$row_index", __('lang_v1.cheque_no'), ['class' => 'vspos-cheque-label']) !!}
					<div class="input-group input-group-sm">
						<span class="input-group-addon vspos-cheque-addon"><i class="fas fa-hashtag"></i></span>
						{!! Form::text("payment[$row_index][cheque_number]", $payment_line['cheque_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no'), 'id' => "cheque_number_$row_index"]); !!}
					</div>
				</div>
				<div class="vspos-cheque-field">
					{!! Form::label("cheque_date_$row_index", __('lang_v1.cheque_due_date'), ['class' => 'vspos-cheque-label']) !!}
					<div class="input-group input-group-sm date cheque_date_group">
						<span class="input-group-addon vspos-cheque-addon vspos-cheque-addon--clickable" onclick="var inp=$(this).siblings('.cheque_date_picker'); if(!inp.hasClass('cheque-dp-ready')&&typeof initChequeDatepicker==='function'){initChequeDatepicker(inp.closest('.payment_row'));} inp.datepicker('show');"><i class="fa fa-calendar"></i></span>
						{!! Form::text("payment[$row_index][cheque_date]", !empty($payment_line['cheque_date']) ? \Carbon\Carbon::parse($payment_line['cheque_date'])->format(session('business.date_format', 'm/d/Y')) : '', ['class' => 'form-control cheque_date_picker', 'placeholder' => session('business.date_format', 'm/d/Y'), 'id' => "cheque_date_$row_index", 'autocomplete' => 'off', 'onfocus' => "if(!$(this).hasClass('cheque-dp-ready')&&typeof initChequeDatepicker==='function'){initChequeDatepicker($(this).closest('.payment_row'));} $(this).datepicker('show');"]); !!}
					</div>
				</div>
				<div class="vspos-cheque-field">
					{!! Form::label("cheque_bank_name_$row_index", __('lang_v1.cheque_bank'), ['class' => 'vspos-cheque-label']) !!}
					<div class="input-group input-group-sm">
						<span class="input-group-addon vspos-cheque-addon"><i class="fas fa-university"></i></span>
						@if(\App\Utils\SriLankanBankUtil::enabled())
							{!! Form::select("payment[$row_index][cheque_bank_name]", \App\Utils\SriLankanBankUtil::options(), $payment_line['cheque_bank_name'] ?? '', ['class' => 'form-control select2', 'placeholder' => __('lang_v1.select_bank'), 'id' => "cheque_bank_name_$row_index"]); !!}
						@else
							{!! Form::text("payment[$row_index][cheque_bank_name]", $payment_line['cheque_bank_name'] ?? '', ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_name'), 'id' => "cheque_bank_name_$row_index"]); !!}
						@endif
					</div>
				</div>
			</div>
			<p class="vspos-cheque-help">
				<i class="fas fa-info-circle"></i>@lang('lang_v1.cheque_due_reminder_help')
			</p>
			<p class="vspos-cheque-help vspos-cheque-walkin-note hide">
				<i class="fas fa-exclamation-triangle"></i>@lang('lang_v1.cheque_not_allowed_walk_in')
			</p>
		</div>
	</div>
</div>
@php
    // Bootstrap-datepicker format (dd-mm-yyyy) — project uses bootstrap-datepicker, not jQuery UI
    $cheque_bs_format = str_replace(['d', 'm', 'Y'], ['dd', 'mm', 'yyyy'], session('business.date_format', 'd-m-Y'));
@endphp
<style id="vspos-cheque-dp-force-dark">
/* Bootstrap datepicker — dark (pay cheque / POS) */
body.vspos-cheque-dp-open .datepicker,
body.vspos-cheque-dp-open .datepicker.dropdown-menu,
body.vspos-mobile-pay-open .datepicker.dropdown-menu,
.datepicker.dropdown-menu.vspos-dp--cheque {
    z-index: 30060 !important;
    background: #0f0f0f !important;
    background-color: #0f0f0f !important;
    border: 1px solid #2e2e2e !important;
    border-radius: 14px !important;
    box-shadow: 0 24px 56px rgba(0,0,0,.8) !important;
    color: #f5f5f5 !important;
    padding: 10px !important;
}
body.vspos-cheque-dp-open .datepicker:before,
body.vspos-mobile-pay-open .datepicker.dropdown-menu:before,
.datepicker.dropdown-menu.vspos-dp--cheque:before {
    border-bottom-color: #2e2e2e !important;
    border-top-color: #2e2e2e !important;
}
body.vspos-cheque-dp-open .datepicker:after,
body.vspos-mobile-pay-open .datepicker.dropdown-menu:after,
.datepicker.dropdown-menu.vspos-dp--cheque:after {
    border-bottom-color: #0f0f0f !important;
    border-top-color: #0f0f0f !important;
}
body.vspos-cheque-dp-open .datepicker table,
.datepicker.dropdown-menu.vspos-dp--cheque table {
    background: transparent !important;
}
body.vspos-cheque-dp-open .datepicker th,
body.vspos-cheque-dp-open .datepicker td,
body.vspos-cheque-dp-open .datepicker .datepicker-switch,
body.vspos-cheque-dp-open .datepicker .prev,
body.vspos-cheque-dp-open .datepicker .next,
.datepicker.dropdown-menu.vspos-dp--cheque th,
.datepicker.dropdown-menu.vspos-dp--cheque td,
.datepicker.dropdown-menu.vspos-dp--cheque .datepicker-switch,
.datepicker.dropdown-menu.vspos-dp--cheque .prev,
.datepicker.dropdown-menu.vspos-dp--cheque .next {
    color: #f5f5f5 !important;
    background: transparent !important;
    border-radius: 8px !important;
}
body.vspos-cheque-dp-open .datepicker .dow,
.datepicker.dropdown-menu.vspos-dp--cheque .dow {
    color: #a3a3a3 !important;
    font-weight: 700 !important;
}
body.vspos-cheque-dp-open .datepicker td.day:hover,
body.vspos-cheque-dp-open .datepicker td.focused,
.datepicker.dropdown-menu.vspos-dp--cheque td.day:hover {
    background: #1f1f1f !important;
    color: #f5c518 !important;
}
body.vspos-cheque-dp-open .datepicker td.active,
body.vspos-cheque-dp-open .datepicker td.active:hover,
body.vspos-cheque-dp-open .datepicker td.active.highlighted,
.datepicker.dropdown-menu.vspos-dp--cheque td.active,
.datepicker.dropdown-menu.vspos-dp--cheque td.active:hover {
    background: #f5c518 !important;
    background-image: none !important;
    color: #111 !important;
    text-shadow: none !important;
}
body.vspos-cheque-dp-open .datepicker td.today,
.datepicker.dropdown-menu.vspos-dp--cheque td.today {
    background: rgba(245,197,24,.15) !important;
    color: #f5c518 !important;
}
body.vspos-cheque-dp-open .datepicker td.old,
body.vspos-cheque-dp-open .datepicker td.new,
.datepicker.dropdown-menu.vspos-dp--cheque td.old,
.datepicker.dropdown-menu.vspos-dp--cheque td.new {
    color: #525252 !important;
}
body.vspos-cheque-dp-open .datepicker .prev:hover,
body.vspos-cheque-dp-open .datepicker .next:hover,
body.vspos-cheque-dp-open .datepicker .datepicker-switch:hover,
.datepicker.dropdown-menu.vspos-dp--cheque .prev:hover,
.datepicker.dropdown-menu.vspos-dp--cheque .next:hover,
.datepicker.dropdown-menu.vspos-dp--cheque .datepicker-switch:hover {
    background: #1a1a1a !important;
    color: #f5c518 !important;
}
</style>
<script>
window.vsposPaintChequeDatepicker = function() {
    document.body.classList.add('vspos-cheque-dp-open');
    var $dp = $('.datepicker.datepicker-dropdown:visible, .datepicker.dropdown-menu:visible').last();
    if (!$dp.length) {
        $dp = $('.datepicker.datepicker-dropdown, .datepicker.dropdown-menu').last();
    }
    if (!$dp.length) {
        return;
    }
    $dp.addClass('vspos-dp--cheque');
    $dp.css({
        'z-index': 30060,
        background: '#0f0f0f',
        'background-color': '#0f0f0f',
        border: '1px solid #2e2e2e',
        color: '#f5f5f5',
        'box-shadow': '0 24px 56px rgba(0,0,0,.8)'
    });
    $dp.find('th, td, .datepicker-switch, .prev, .next').css({
        color: '#f5f5f5',
        background: 'transparent'
    });
    $dp.find('td.active, td.active:hover').css({
        background: '#f5c518',
        color: '#111'
    });
};

// Bootstrap-datepicker (this project’s $.fn.datepicker) — dark cheque due date
window.initChequeDatepicker = function($container) {
    var bsFormat = (typeof datepicker_date_format !== 'undefined' && datepicker_date_format)
        ? datepicker_date_format
        : '{{ $cheque_bs_format }}';
    var $inputs = $container ? $container.find('.cheque_date_picker') : $('.cheque_date_picker');
    $inputs.each(function() {
        var $input = $(this);
        try {
            if ($input.data('datepicker')) {
                $input.datepicker('destroy');
            }
        } catch (e1) {
            try { $input.datepicker('remove'); } catch (e2) {}
        }
        // Clear any jQuery UI leftover marks
        $input.removeClass('hasDatepicker cheque-dp-ready');
        $input.off('.vsposChequeDp');
        $input.addClass('cheque-dp-ready').datepicker({
            format: bsFormat,
            autoclose: true,
            todayHighlight: true,
            assumeNearbyYear: true,
            orientation: 'bottom auto',
            container: 'body',
            zIndexOffset: 30000
        }).on('show.vsposChequeDp', function () {
            setTimeout(window.vsposPaintChequeDatepicker, 0);
            setTimeout(window.vsposPaintChequeDatepicker, 30);
            setTimeout(window.vsposPaintChequeDatepicker, 80);
        }).on('hide.vsposChequeDp', function () {
            document.body.classList.remove('vspos-cheque-dp-open');
            $('.datepicker').removeClass('vspos-dp--cheque');
        });
    });
};

if (!window._chequeDpInitDone) {
    window._chequeDpInitDone = true;
    $(function() { initChequeDatepicker(); });
}
</script>
<div class="payment_details_div @if( $payment_line['method'] !== 'bank_transfer' ) {{ 'hide' }} @endif" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("bank_account_number_$row_index",__('lang_v1.bank_account_number')) !!}
			{!! Form::text( "payment[$row_index][bank_account_number]", $payment_line['bank_account_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number'), 'id' => "bank_account_number_$row_index"]); !!}
		</div>
	</div>
</div>

@for ($i = 1; $i < 8; $i++)
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_' . $i ) {{ 'hide' }} @endif" data-type="custom_pay_{{$i}}" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_{$i}_{$row_index}", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_{$i}]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_{$i}_{$row_index}"]); !!}
		</div>
	</div>
</div>
@endfor

@php
	$_loy_cs = session('business.common_settings') ?? [];
	if (is_string($_loy_cs)) { $_loy_cs = json_decode($_loy_cs, true) ?? []; }
	$_loy_otp = !empty($_loy_cs['loyalty_otp_on_redeem']);
	$_loy_name = session('business.rp_name') ?: __('lang_v1.loyalty');
	$_loy_per = session('business.redeem_amount_per_unit_rp') ?: 1;
@endphp
@if(session('business.enable_rp') == 1)
<div class="payment_details_div @if( ($payment_line['method'] ?? '') !== 'loyalty' ) hide @endif" data-type="loyalty">
	<div class="col-md-12">
		<div class="vspos-loyalty-pay-panel">
			<div class="vspos-loyalty-pay-title">
				<i class="fa fa-gift"></i> {{ $_loy_name }}
				<span class="vspos-loyalty-pay-avail">@lang('lang_v1.available'): <strong class="loyalty-pay-available-pts">0</strong> pts</span>
			</div>
			<p class="vspos-loyalty-pay-msg text-danger hide"></p>
			<p class="vspos-loyalty-pay-hint">Enter amount to redeem (1 pt = {{ session('currency.symbol', 'Rs') }} {{ @num_format($_loy_per) }}). Amount counts as paying.</p>
			<div class="vspos-loyalty-pay-meta">
				<span>≈ <strong class="loyalty-pay-points-eq">0</strong> pts</span>
				<span class="loyalty-pay-verified-badge hide"><i class="fa fa-check-circle"></i> @lang('lang_v1.loyalty_otp_verified')</span>
			</div>
			@if($_loy_otp)
			<div class="vspos-loyalty-pay-otp-actions">
				<button type="button" class="btn btn-sm vspos-loyalty-send-otp-btn">
					<i class="fa fa-mobile"></i> @lang('lang_v1.send_otp')
				</button>
				<span class="loyalty-pay-otp-hint">OTP will be sent to customer mobile</span>
			</div>
			@endif
		</div>
	</div>
</div>
@endif
