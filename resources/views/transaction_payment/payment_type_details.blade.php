<div class="payment_details_div @if( $payment_line->method !== 'card' ) {{ 'hide' }} @endif" data-type="card" >
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_number", __('lang_v1.card_no')) !!}
			{!! Form::text("card_number", $payment_line->card_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_no')]); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_holder_name", __('lang_v1.card_holder_name')) !!}
			{!! Form::text("card_holder_name", $payment_line->card_holder_name, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_holder_name')]); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::label("card_transaction_number",__('lang_v1.card_transaction_no')) !!}
			{!! Form::text("card_transaction_number", $payment_line->card_transaction_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_transaction_no')]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_type", __('lang_v1.card_type')) !!}
			{!! Form::select("card_type", ['credit' => 'Credit Card', 'debit' => 'Debit Card', 'visa' => 'Visa', 'master' => 'MasterCard'], $payment_line->card_type,['class' => 'form-control select2']); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_month", __('lang_v1.month')) !!}
			{!! Form::text("card_month", $payment_line->card_month, ['class' => 'form-control', 
			'placeholder' => __('lang_v1.month') ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_year", __('lang_v1.year')) !!}
			{!! Form::text("card_year", $payment_line->card_year, ['class' => 'form-control', 'placeholder' => __('lang_v1.year') ]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("card_security",__('lang_v1.security_code')) !!}
			{!! Form::text("card_security", $payment_line->card_security, ['class' => 'form-control', 'placeholder' => __('lang_v1.security_code')]); !!}
		</div>
	</div>
	<div class="clearfix"></div>
</div>
@php
    // Convert PHP date_format (m/d/Y) to jQuery UI format (mm/dd/yy)
    $cheque_dp_format = str_replace(['Y','m','d'], ['yy','mm','dd'], session('business.date_format', 'm/d/Y'));
@endphp

<div class="payment_details_div @if( $payment_line->method !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque" >
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("cheque_number",__('lang_v1.cheque_no') . ':*') !!}
			{!! Form::text("cheque_number", $payment_line->cheque_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no')]); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("cheque_date",__('lang_v1.cheque_date') . ':*') !!}
			<div class="input-group cheque_date_group">
				<span class="input-group-addon" style="cursor:pointer;" onclick="var inp=$(this).siblings('.cheque_date_picker'); if(!inp.hasClass('cheque-dp-ready')&&typeof initChequeDatepickerTxn==='function'){initChequeDatepickerTxn(inp.closest('.modal-content, .payment_row'));} inp.datepicker('show');"><i class="fa fa-calendar"></i></span>
				{!! Form::text('cheque_date', !empty($payment_line->cheque_date) ? @format_date($payment_line->cheque_date) : null, ['class' => 'form-control cheque_date_picker', 'placeholder' => __('lang_v1.cheque_date'), 'readonly', 'onfocus' => "if(!$(this).hasClass('cheque-dp-ready')&&typeof initChequeDatepickerTxn==='function'){initChequeDatepickerTxn($(this).closest('.modal-content, .payment_row'));} $(this).datepicker('show');"]); !!}
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("cheque_bank_name", 'Bank' . ':*') !!}
			<div class="input-group">
				<span class="input-group-addon"><i class="fas fa-university"></i></span>
				@if(\App\Utils\SriLankanBankUtil::enabled())
					{!! Form::select("cheque_bank_name", \App\Utils\SriLankanBankUtil::options(), $payment_line->cheque_bank_name ?? '', ['class' => 'form-control select2', 'placeholder' => '-- Select Bank --']); !!}
				@else
					{!! Form::text("cheque_bank_name", $payment_line->cheque_bank_name ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Bank Name']); !!}
				@endif
			</div>
		</div>
	</div>
	@if(!empty($accounts))
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::label("cheque_account_id" , __('lang_v1.payment_account') . ':') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-university"></i>
				</span>
				{!! Form::select("account_id", $accounts, !empty($payment_line->account_id) ? $payment_line->account_id : '' , ['class' => 'form-control select2', 'style' => 'width:100%;']); !!}
			</div>
		</div>
	</div>
	@endif
	<div class="clearfix"></div>
</div>

<script>
if (typeof window.initChequeDatepickerTxn !== 'function') {
    window.initChequeDatepickerTxn = function($container) {
        var dpFormat = '{{ $cheque_dp_format }}';
        var $inputs = $container ? $container.find('.cheque_date_picker:not(.cheque-dp-ready)') : $('.cheque_date_picker:not(.cheque-dp-ready)');
        $inputs.each(function() {
            var $input = $(this);
            $input.addClass('cheque-dp-ready').datepicker({
                dateFormat: dpFormat,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-1:c+5',
                showButtonPanel: false,
                beforeShow: function(input, inst) {
                    setTimeout(function() {
                        var $picker = $('#ui-datepicker-div');
                        if ($picker.length) {
                            $picker.css('z-index', 9999);
                        }
                    }, 0);
                },
                onSelect: function(dateText) {
                    $(this).val(dateText).trigger('change');
                }
            });
        });
    };
}
if (!window._chequeTxnDpInitDone) {
    window._chequeTxnDpInitDone = true;
    initChequeDatepickerTxn();
}
</script>
<div class="payment_details_div @if( $payment_line->method !== 'bank_transfer' ) {{ 'hide' }} @endif" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("bank_account_number",__('lang_v1.bank_account_number')) !!}
			{!! Form::text( "bank_account_number", $payment_line->bank_account_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_1' ) {{ 'hide' }} @endif" data-type="custom_pay_1" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_1", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_1", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_2' ) {{ 'hide' }} @endif" data-type="custom_pay_2" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_2", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_2", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_3' ) {{ 'hide' }} @endif" data-type="custom_pay_3" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_3", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_3", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>