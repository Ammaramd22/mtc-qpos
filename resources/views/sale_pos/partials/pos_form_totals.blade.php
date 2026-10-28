{{-- hidden inputs needed by JS --}}
<input type="hidden" name="discount_type" id="discount_type" value="@if(empty($edit)){{'percentage'}}@else{{$transaction->discount_type}}@endif" data-default="percentage">
<input type="hidden" name="discount_amount" id="discount_amount" value="@if(empty($edit)) {{@num_format($business_details->default_sales_discount)}} @else {{@num_format($transaction->discount_amount)}} @endif" data-default="{{$business_details->default_sales_discount}}">
<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="@if(empty($edit)){{'0'}}@else{{$transaction->rp_redeemed}}@endif">
<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="@if(empty($edit)){{'0'}}@else {{$transaction->rp_redeemed_amount}} @endif">
<input type="hidden" name="loyalty_has_card" id="loyalty_has_card_input" value="0">
<input type="hidden" name="tax_rate_id" id="tax_rate_id" value="@if(empty($edit)) {{$business_details->default_sales_tax}} @else {{$transaction->tax_id}} @endif" data-default="{{$business_details->default_sales_tax}}">
<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" value="@if(empty($edit)) {{@num_format($business_details->tax_calculation_amount)}} @else {{@num_format($transaction->tax?->amount)}} @endif" data-default="{{$business_details->tax_calculation_amount}}">
<input type="hidden" name="shipping_details" id="shipping_details" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_details}}@endif" data-default="">
<input type="hidden" name="shipping_address" id="shipping_address" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_address}}@endif">
<input type="hidden" name="shipping_status" id="shipping_status" value="@if(empty($edit)){{''}}@else{{$transaction->shipping_status}}@endif">
<input type="hidden" name="delivered_to" id="delivered_to" value="@if(empty($edit)){{''}}@else{{$transaction->delivered_to}}@endif">
<input type="hidden" name="delivery_person" id="delivery_person" value="@if(empty($edit)){{''}}@else{{$transaction->delivery_person}}@endif">
<input type="hidden" name="shipping_charges" id="shipping_charges" value="@if(empty($edit)){{@num_format(0.00)}} @else{{@num_format($transaction->shipping_charges)}} @endif" data-default="0.00">
<input type="hidden" name="trade_in_total" id="trade_in_total" value="0">
<input type="hidden" id="trade_in_refund_due" value="0">
@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
    <input type="hidden" name="round_off_amount" id="round_off_amount" value=0>
@endif

{{-- Weighing scale barcode settings for auto-detection --}}
@php
    $common_settings = session('business.common_settings');
    if (is_string($common_settings)) {
        $common_settings = json_decode($common_settings, true);
    }
    if (!is_array($common_settings)) {
        $common_settings = [];
    }
@endphp
<input type="hidden" id="weighing_scale_sku_length" value="{{ !empty($common_settings['weighing_scale_sku_length']) ? $common_settings['weighing_scale_sku_length'] : 6 }}">
<input type="hidden" id="weighing_scale_weight_digits" value="{{ !empty($common_settings['weighing_scale_weight_digits']) ? $common_settings['weighing_scale_weight_digits'] : 4 }}">
<input type="hidden" id="enable_weighing_scale_scan" value="{{ !empty($common_settings['enable_weighing_scale_scan']) ? '1' : '0' }}">

<div class="vspos-summary-bar">
    {{-- Summary cards row --}}
    <div class="vspos-summary-cards">

        {{-- Total Products --}}
        <div class="vspos-card vspos-card--blue">
            <div class="vspos-card-icon"><i class="fas fa-shopping-basket"></i></div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('sale.item')</span>
                <span class="vspos-card-value total_quantity">0</span>
            </div>
        </div>

        {{-- Subtotal --}}
        <div class="vspos-card vspos-card--indigo">
            <div class="vspos-card-icon"><i class="fas fa-receipt"></i></div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('sale.subtotal')</span>
                <span class="vspos-card-value price_total">0</span>
            </div>
        </div>

        {{-- Discount (inline editor) --}}
        <div class="vspos-card vspos-card--rose vspos-card--discount">
            <div class="vspos-card-icon">
                <i class="fas fa-tag"></i>
            </div>
            <div class="vspos-card-body" style="flex:1;min-width:0;">
                <span class="vspos-card-label">@lang('sale.discount') (−) &nbsp;<span class="vspos-disc-applied" id="discount_applied_badge" style="display:none;"></span></span>
                <div class="vspos-disc-inline">
                    {{-- % / Fixed pill toggle --}}
                    <div class="vspos-disc-type-pill" id="vspos_disc_type_pill">
                        <button type="button" class="vspos-disc-pill-btn active" id="vspos_disc_pct_btn" data-type="percentage">%</button>
                        <button type="button" class="vspos-disc-pill-btn" id="vspos_disc_fixed_btn" data-type="fixed">@lang('lang_v1.fixed')</button>
                    </div>
                    {{-- Amount input --}}
                    <input type="number" id="vspos_disc_inline_amount"
                        class="vspos-disc-amount-input input_number"
                        value="@if(empty($edit)){{@num_format($business_details->default_sales_discount)}}@else{{@num_format($transaction->discount_amount)}}@endif"
                        min="0" step="any" placeholder="0">
                    <button type="button" class="vspos-disc-apply-btn" id="vspos_disc_apply_btn">✓</button>
                </div>
                <span class="vspos-card-value" id="total_discount" style="font-size:11px;margin-top:2px;">0</span>
            </div>
        </div>

        {{-- Loyalty --}}
        <div class="vspos-card vspos-card--purple @if(!$is_rp_enabled) hide @endif" id="pos_loyalty_summary_card" title="Available loyalty points / redeemed this sale">
            <div class="vspos-card-icon"><i class="fas fa-star"></i></div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">{{session('business.rp_name') ?: 'Loyalty'}} <small id="pos_loyalty_bal_label" style="opacity:.75;"></small></span>
                <span class="vspos-card-value" id="loyalty_amount_display">0</span>
            </div>
        </div>

        {{-- Tax --}}
        <div class="vspos-card vspos-card--amber tax-field">
            <div class="vspos-card-icon">
                <i class="fas fa-percent"></i>
                <i class="fas fa-edit vspos-edit-icon" title="@lang('sale.edit_order_tax')" data-toggle="modal" data-target="#posEditOrderTaxModal" id="pos-edit-tax"></i>
            </div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('sale.order_tax') (+)</span>
                <span class="vspos-card-value" id="order_tax">@if(empty($edit)) 0 @else {{$transaction->tax_amount}} @endif</span>
            </div>
        </div>

        {{-- Shipping --}}
        <div class="vspos-card vspos-card--cyan">
            <div class="vspos-card-icon">
                <i class="fas fa-truck"></i>
                <i class="fas fa-edit vspos-edit-icon" title="@lang('sale.shipping')" data-toggle="modal" data-target="#posShippingModal"></i>
            </div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('sale.shipping') (+)</span>
                <span class="vspos-card-value" id="shipping_charges_amount">0</span>
            </div>
        </div>

        {{-- Exchange / Trade-In (Tyre Shop module) --}}
        @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
        <div class="vspos-card vspos-card--tradein">
            <div class="vspos-card-icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">Exchange / Trade-In (−)</span>
                <span class="vspos-card-value" id="trade_in_amount_display">0</span>
            </div>
        </div>
        @endif

        {{-- Packing --}}
        @if(in_array('types_of_service', $enabled_modules))
        <div class="vspos-card vspos-card--teal">
            <div class="vspos-card-icon">
                <i class="fas fa-box"></i>
                <i class="fas fa-edit vspos-edit-icon service_modal_btn"></i>
            </div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('lang_v1.packing_charge') (+)</span>
                <span class="vspos-card-value" id="packing_charge_text">0</span>
            </div>
        </div>
        @endif

        {{-- Round off --}}
        @if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
        <div class="vspos-card vspos-card--slate">
            <div class="vspos-card-icon"><i class="fas fa-adjust"></i></div>
            <div class="vspos-card-body">
                <span class="vspos-card-label">@lang('lang_v1.round_off')</span>
                <span class="vspos-card-value" id="round_off_text">0</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Total payable hero --}}
    <div class="vspos-total-hero">
        <div class="vspos-total-hero-inner">
            <span class="vspos-total-label">@lang('sale.total_payable')</span>
            <span id="total_payable" class="vspos-total-value number">0</span>
            <input type="hidden" name="final_total" id="final_total_input" value="0.00">
        </div>
    </div>
    <div id="vspos_tradein_refund_banner"></div>
</div>

<style>
/* ===== VsoftPOS Summary Bar ===== */
.vspos-summary-bar {
    display: flex;
    flex-direction: column;
    background: var(--vspos-panel-bg, #ffffff);
    margin: 0;
}

.vspos-summary-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    padding: 10px 10px 6px;
    gap: 6px;
}

.vspos-card {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1 1 130px;
    min-width: 110px;
    border-radius: 10px;
    padding: 8px 10px;
    position: relative;
    overflow: hidden;
}

.vspos-card-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    opacity: 0.9;
    color: #fff;
    flex-shrink: 0;
}

.vspos-edit-icon {
    font-size: 9px !important;
    cursor: pointer;
    opacity: 0.75;
    transition: opacity 0.15s;
}
.vspos-edit-icon:hover { opacity: 1; }

.vspos-card-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.vspos-card-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.7);
    white-space: nowrap;
    line-height: 1.2;
}

.vspos-card-value {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.3px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    line-height: 1.3;
}

/* Card colours */
.vspos-card--blue    { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.vspos-card--indigo  { background: linear-gradient(135deg, #6366f1, #4338ca); }
.vspos-card--rose    { background: linear-gradient(135deg, #f43f5e, #be123c); }
.vspos-card--purple  { background: linear-gradient(135deg, #a855f7, #7e22ce); }
.vspos-card--amber   { background: linear-gradient(135deg, #f59e0b, #b45309); }
.vspos-card--cyan    { background: linear-gradient(135deg, #06b6d4, #0e7490); }
.vspos-card--teal    { background: linear-gradient(135deg, #14b8a6, #0f766e); }
.vspos-card--slate   { background: linear-gradient(135deg, #64748b, #334155); }

/* ── Inline discount editor ── */
.vspos-card--discount { flex: 1 1 200px; min-width: 160px; }
.vspos-disc-inline {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}
.vspos-disc-type-pill {
    display: flex;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.35);
    flex-shrink: 0;
}
.vspos-disc-pill-btn {
    background: transparent;
    border: none;
    color: rgba(255,255,255,.65);
    font-size: 10px;
    font-weight: 700;
    padding: 3px 7px;
    cursor: pointer;
    transition: background .15s, color .15s;
    line-height: 1;
}
.vspos-disc-pill-btn.active {
    background: rgba(255,255,255,.25);
    color: #fff;
}
.vspos-disc-pill-btn:hover:not(.active) { background: rgba(255,255,255,.12); color: #fff; }
.vspos-disc-amount-input {
    width: 68px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 6px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 6px;
    outline: none;
    text-align: right;
    -moz-appearance: textfield;
}
.vspos-disc-amount-input::placeholder { color: rgba(255,255,255,.5); }
.vspos-disc-amount-input::-webkit-outer-spin-button,
.vspos-disc-amount-input::-webkit-inner-spin-button { -webkit-appearance: none; }
.vspos-disc-amount-input:focus {
    border-color: rgba(255,255,255,.7);
    background: rgba(255,255,255,.22);
}
.vspos-disc-apply-btn {
    background: rgba(255,255,255,.22);
    border: 1px solid rgba(255,255,255,.35);
    border-radius: 6px;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 3px 7px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.vspos-disc-apply-btn:hover { background: rgba(255,255,255,.35); }
.vspos-disc-applied {
    font-size: 9px;
    background: rgba(255,255,255,.25);
    border-radius: 4px;
    padding: 1px 5px;
    color: #fff;
    font-weight: 700;
    vertical-align: middle;
}

/* Total hero */
.vspos-total-hero {
    background: linear-gradient(135deg, #10b981, #047857);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vspos-total-hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 12px;
}

.vspos-total-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.85);
    white-space: nowrap;
}

.vspos-total-value {
    font-size: 24px;
    font-weight: 900;
    color: #fff;
    letter-spacing: -0.5px;
    font-variant-numeric: tabular-nums;
    line-height: 1;
}

@media (min-width: 768px) {
    .vspos-summary-bar {
        flex-direction: row;
        align-items: stretch;
    }
    .vspos-summary-cards {
        flex: 1;
        padding: 10px;
        align-content: flex-start;
    }
    .vspos-total-hero {
        flex: 0 0 200px;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 12px 16px;
        border-radius: 0;
    }
    .vspos-total-hero-inner {
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .vspos-total-label { font-size: 10px; text-align: center; }
    .vspos-total-value { font-size: 30px; text-align: center; }
    .vspos-card-value { font-size: 14px; }
}
</style>

<script>
$(function () {
    /* ── helpers ── */
    function vsposDiscType() {
        return $('#discount_type').val() || 'percentage';
    }

    /* ── sync pill buttons to reflect current hidden value ── */
    function vsposSyncPills(type) {
        if (type === 'percentage') {
            $('#vspos_disc_pct_btn').addClass('active');
            $('#vspos_disc_fixed_btn').removeClass('active');
        } else {
            $('#vspos_disc_fixed_btn').addClass('active');
            $('#vspos_disc_pct_btn').removeClass('active');
        }
    }

    /* ── update the badge showing active discount ── */
    function vsposUpdateBadge() {
        var val  = parseFloat($('#vspos_disc_inline_amount').val()) || 0;
        var type = vsposDiscType();
        var $badge = $('#discount_applied_badge');
        if (val > 0) {
            $badge.text(val + (type === 'percentage' ? '%' : ' fixed')).show();
        } else {
            $badge.hide();
        }
    }

    /* ── apply discount: push to hidden fields and trigger recalc ── */
    function vsposApplyDiscount() {
        var val  = parseFloat($('#vspos_disc_inline_amount').val()) || 0;
        var type = vsposDiscType();
        var maxPct = parseFloat($('#discount_amount_modal').data('max-discount')) || '';

        /* enforce max discount if set */
        if (type === 'percentage' && maxPct !== '' && val > maxPct) {
            val = maxPct;
            $('#vspos_disc_inline_amount').val(val);
            toastr && toastr.warning($('#discount_amount_modal').data('max-discount-error_msg'));
        }

        $('#discount_amount').val(val);
        /* also keep the modal field in sync */
        $('#discount_amount_modal').val(val);
        vsposUpdateBadge();
        if (typeof pos_total_row === 'function') pos_total_row();
    }

    /* ── init: read existing hidden values on page load ── */
    (function vsposDiscInit() {
        var initType = vsposDiscType();
        vsposSyncPills(initType);
        /* pre-fill inline input from the hidden field */
        var initVal = parseFloat($('#discount_amount').val()) || 0;
        $('#vspos_disc_inline_amount').val(initVal > 0 ? initVal : '');
        vsposUpdateBadge();
    })();

    /* ── pill click: switch type ── */
    $(document).on('click', '#vspos_disc_pct_btn', function () {
        $('#discount_type').val('percentage');
        /* also sync the modal select */
        $('#discount_type_modal').val('percentage').trigger('change');
        vsposSyncPills('percentage');
        vsposApplyDiscount();
    });

    $(document).on('click', '#vspos_disc_fixed_btn', function () {
        $('#discount_type').val('fixed');
        $('#discount_type_modal').val('fixed').trigger('change');
        vsposSyncPills('fixed');
        vsposApplyDiscount();
    });

    /* ── apply button click ── */
    $(document).on('click', '#vspos_disc_apply_btn', function () {
        vsposApplyDiscount();
    });

    /* ── Enter key on the input ── */
    $(document).on('keydown', '#vspos_disc_inline_amount', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); vsposApplyDiscount(); }
    });

    /* ── keep inline input in sync if modal updates the hidden fields ── */
    $(document).on('click', '#posEditDiscountModalUpdate', function () {
        /* short delay so modal JS sets the hidden fields first */
        setTimeout(function () {
            var type = vsposDiscType();
            vsposSyncPills(type);
            var val = parseFloat($('#discount_amount').val()) || 0;
            $('#vspos_disc_inline_amount').val(val > 0 ? val : '');
            vsposUpdateBadge();
        }, 80);
    });
});
</script>
