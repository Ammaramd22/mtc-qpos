<div class="pos-tab-content" data-settings-tab="reward_point_settings">
@php
    $cs = !empty($common_settings) ? $common_settings : [];
    $loyalty_redeem_mode = $cs['loyalty_redeem_mode'] ?? ((!empty($business->min_redeem_point) && $business->min_redeem_point > 0) ? 'min_points' : 'any');
    $earn_spend_raw = !empty($cs['loyalty_earn_spend']) ? $cs['loyalty_earn_spend'] : ($business->amount_for_unit_rp * 10);
    $earn_spend = (float) str_replace([',', ' '], '', (string) $earn_spend_raw);
    if ($earn_spend <= 0) {
        $earn_spend = (float) $business->amount_for_unit_rp * 10;
    }
    $earn_points = (float) (!empty($cs['loyalty_earn_points']) ? $cs['loyalty_earn_points'] : 10);
    if ($earn_points <= 0) {
        $earn_points = 10;
    }
@endphp
<div class="row well">
    <div class="col-sm-4">
        <div class="form-group">
            <div class="checkbox">
                <label>
                {!! Form::checkbox('enable_rp', 1, $business->enable_rp ,
                [ 'class' => 'input-icheck', 'id' => 'enable_rp']); !!} {{ __( 'lang_v1.enable_rp' ) }} / Loyalty
                </label>
            </div>
            <p class="help-block">Enable loyalty points earn &amp; redeem on POS and sales.</p>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('rp_name', __('lang_v1.rp_name') . ':') !!}
            {!! Form::text('rp_name', $business->rp_name ?: 'Loyalty Points', ['class' => 'form-control','placeholder' => __('lang_v1.rp_name')]); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <div class="checkbox">
                <label>
                {!! Form::checkbox('common_settings[loyalty_auto_enroll]', 1, !empty($cs['loyalty_auto_enroll']) || !isset($cs['loyalty_auto_enroll']),
                [ 'class' => 'input-icheck', 'id' => 'loyalty_auto_enroll']); !!}
                Auto-create loyalty when customer is added
                </label>
            </div>
            <p class="help-block">New customers start with a loyalty balance (0 points).</p>
        </div>
    </div>

    <div class="clearfix"></div>
    <div class="col-sm-12">
        <h4>Earn points (example: spend 1000 → get 10 points)</h4>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('loyalty_earn_spend', 'Purchase amount') !!}
            {!! Form::text('common_settings[loyalty_earn_spend]', @num_format($earn_spend), ['class' => 'form-control input_number', 'id' => 'loyalty_earn_spend']); !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('loyalty_earn_points', 'Points earned') !!}
            {!! Form::number('common_settings[loyalty_earn_points]', $earn_points, ['class' => 'form-control', 'id' => 'loyalty_earn_points', 'min' => 1, 'step' => '0.01']); !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('amount_for_unit_rp', __('lang_v1.amount_for_unit_rp') . ':') !!} @show_tooltip(__('lang_v1.amount_for_unit_rp_tooltip'))
            {!! Form::text('amount_for_unit_rp', @num_format((float) $business->amount_for_unit_rp), ['class' => 'form-control input_number','placeholder' => __('lang_v1.amount_for_unit_rp'), 'id' => 'amount_for_unit_rp', 'readonly']); !!}
            <p class="help-block">Auto-calculated: amount ÷ points</p>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('min_order_total_for_rp', __('lang_v1.min_order_total_for_rp') . ':') !!}
            {!! Form::text('min_order_total_for_rp', @num_format((float) $business->min_order_total_for_rp), ['class' => 'form-control input_number']); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('max_rp_per_order', __('lang_v1.max_rp_per_order') . ':') !!}
            {!! Form::number('max_rp_per_order', $business->max_rp_per_order, ['class' => 'form-control']); !!}
        </div>
    </div>
</div>

<div class="row well">
    <div class="col-sm-12">
        <h4>Redeem points as cash</h4>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('redeem_amount_per_unit_rp', __('lang_v1.redeem_amount_per_unit_rp') . ':') !!}
            {!! Form::text('redeem_amount_per_unit_rp', @num_format((float) $business->redeem_amount_per_unit_rp), ['class' => 'form-control input_number']); !!}
            <p class="help-block">Cash value of 1 point (e.g. 1 = Rs 1 per point)</p>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('loyalty_redeem_mode', 'Redeem rule') !!}
            {!! Form::select('common_settings[loyalty_redeem_mode]', [
                'min_points' => 'Only if points ≥ minimum (e.g. 1000+)',
                'any' => 'Any points can be redeemed',
            ], $loyalty_redeem_mode, ['class' => 'form-control', 'id' => 'loyalty_redeem_mode']); !!}
        </div>
    </div>
    <div class="col-sm-4" id="min_redeem_point_wrap">
        <div class="form-group">
            {!! Form::label('min_redeem_point', __('lang_v1.min_redeem_point') . ':') !!}
            {!! Form::number('min_redeem_point', $business->min_redeem_point ?: 1000, ['class' => 'form-control', 'id' => 'min_redeem_point']); !!}
            <p class="help-block">Used when “minimum points” rule is selected</p>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('min_order_total_for_redeem', __('lang_v1.min_order_total_for_redeem') . ':') !!}
            {!! Form::text('min_order_total_for_redeem', @num_format((float) $business->min_order_total_for_redeem), ['class' => 'form-control input_number']); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('max_redeem_point', __('lang_v1.max_redeem_point') . ':') !!}
            {!! Form::number('max_redeem_point', $business->max_redeem_point, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('rp_expiry_period', __('lang_v1.rp_expiry_period') . ':') !!}
            <div class="input-group">
                {!! Form::number('rp_expiry_period', $business->rp_expiry_period, ['class' => 'form-control']); !!}
                <span class="input-group-addon">-</span>
                {!! Form::select('rp_expiry_type', ['month' => __('lang_v1.month'), 'year' => __('lang_v1.year')], $business->rp_expiry_type, ['class' => 'form-control']); !!}
            </div>
        </div>
    </div>
</div>

<div class="row well">
    <div class="col-sm-12">
        <h4>POS &amp; security</h4>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <div class="checkbox">
                <label>
                {!! Form::checkbox('common_settings[loyalty_ask_mobile_on_pos]', 1, !empty($cs['loyalty_ask_mobile_on_pos']),
                [ 'class' => 'input-icheck', 'id' => 'loyalty_ask_mobile_on_pos']); !!}
                Ask customer mobile number when opening POS
                </label>
            </div>
            <p class="help-block">Popup to find / add loyalty customer by phone before selling.</p>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <div class="checkbox">
                <label>
                {!! Form::checkbox('common_settings[loyalty_otp_on_redeem]', 1, !empty($cs['loyalty_otp_on_redeem']),
                [ 'class' => 'input-icheck', 'id' => 'loyalty_otp_on_redeem']); !!}
                OTP verification when redeeming without card
                </label>
            </div>
            <p class="help-block">If customer has only their phone (no card), send OTP by SMS before redeem.</p>
        </div>
    </div>
</div>
</div>

@push('javascript')
<script>
(function () {
    function syncEarnRate() {
        var spend = parseFloat(String($('#loyalty_earn_spend').val() || '0').replace(/,/g, '')) || 0;
        var pts = parseFloat($('#loyalty_earn_points').val()) || 0;
        if (spend > 0 && pts > 0) {
            $('#amount_for_unit_rp').val((spend / pts).toFixed(4));
        }
    }
    function syncRedeemMode() {
        if ($('#loyalty_redeem_mode').val() === 'any') {
            $('#min_redeem_point_wrap').hide();
            $('#min_redeem_point').val(0);
        } else {
            $('#min_redeem_point_wrap').show();
            if (parseFloat($('#min_redeem_point').val()) <= 0) {
                $('#min_redeem_point').val(1000);
            }
        }
    }
    $(document).on('change keyup', '#loyalty_earn_spend, #loyalty_earn_points', syncEarnRate);
    $(document).on('change', '#loyalty_redeem_mode', syncRedeemMode);
    $(document).ready(function () {
        syncEarnRate();
        syncRedeemMode();
    });
})();
</script>
@endpush
