@php
    $is_mobile = isMobile();
@endphp
<div class="vspos-action-bar no-print">
    <div class="vspos-action-inner">

        {{-- Left: secondary actions --}}
        <div class="vspos-action-secondary">

            @if (empty($edit))
                <button type="button" class="vspos-action-btn vspos-action-btn--cancel js-pos-cancel" title="@lang('sale.cancel')">
                    <i class="fas fa-times-circle"></i>
                    <span>@lang('sale.cancel')</span>
                </button>
            @else
                <button type="button" class="vspos-action-btn vspos-action-btn--danger hide js-pos-delete" id="pos-delete"
                    @if (!empty($only_payment)) disabled @endif title="@lang('messages.delete')">
                    <i class="fas fa-trash-alt"></i>
                    <span>@lang('messages.delete')</span>
                </button>
            @endif

            @if (!Gate::check('disable_draft') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button" class="vspos-action-btn vspos-action-btn--draft @if ($pos_settings['disable_draft'] != 0) hide @endif"
                    id="pos-draft" @if (!empty($only_payment)) disabled @endif title="@lang('sale.draft')">
                    <i class="fas fa-save"></i>
                    <span>@lang('sale.draft')</span>
                </button>
            @endif

            @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button" class="vspos-action-btn vspos-action-btn--quote @if (!empty($pos_settings['disable_quotation']) && $pos_settings['disable_quotation'] != 0) hide @endif"
                    id="pos-quotation" @if (!empty($only_payment)) disabled @endif title="@lang('lang_v1.quotation')">
                    <i class="fas fa-file-alt"></i>
                    <span>@lang('lang_v1.quotation')</span>
                </button>
            @endif

            @if (!Gate::check('disable_suspend_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                @if (empty($pos_settings['disable_suspend']))
                    <button type="button" class="vspos-action-btn vspos-action-btn--suspend pos-express-finalize"
                        data-pay_method="suspend" title="@lang('lang_v1.tooltip_suspend')"
                        @if (!empty($only_payment)) disabled @endif>
                        <i class="fas fa-pause-circle"></i>
                        <span>@lang('lang_v1.suspend')</span>
                    </button>
                @endif
            @endif

            @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                @if (empty($pos_settings['disable_credit_sale_button']))
                    <input type="hidden" name="is_credit_sale" value="0" id="is_credit_sale">
                    <button type="button" class="vspos-action-btn vspos-action-btn--credit pos-express-finalize"
                        data-pay_method="credit_sale" title="@lang('lang_v1.tooltip_credit_sale')"
                        @if (!empty($only_payment)) disabled @endif>
                        <i class="fas fa-user-clock"></i>
                        <span>@lang('lang_v1.credit_sale')</span>
                    </button>
                @endif
            @endif

            @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="vspos-action-btn vspos-action-btn--card pos-express-finalize @if (!array_key_exists('card', $payment_types)) hide @endif"
                    data-pay_method="card" title="@lang('lang_v1.tooltip_express_checkout_card')">
                    <i class="fas fa-credit-card"></i>
                    <span>@lang('lang_v1.express_checkout_card')</span>
                </button>
            @endif

            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                <button type="button" class="vspos-action-btn vspos-action-btn--recent"
                    data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions">
                    <i class="fas fa-history"></i>
                    <span>@lang('lang_v1.recent_transactions')</span>
                </button>
            @endif

            <button type="button" class="vspos-action-btn vspos-action-btn--supplier"
                id="pos_supplier_pay_btn" title="@lang('lang_v1.pay_supplier')">
                <i class="fas fa-hand-holding-usd"></i>
                <span>@lang('lang_v1.pay_supplier')</span>
            </button>
        </div>

        {{-- Right: primary CTAs --}}
        <div class="vspos-action-primary">
            @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="vspos-cta-btn vspos-cta-btn--multi pos-finalize no-print @if ($pos_settings['disable_pay_checkout'] != 0) hide @endif"
                    title="@lang('lang_v1.tooltip_checkout_multi_pay')">
                    <i class="fas fa-money-check-alt"></i>
                    <span>@lang('lang_v1.checkout_multi_pay')</span>
                </button>
            @endif

            @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="vspos-cta-btn vspos-cta-btn--cash pos-express-finalize no-print @if ($pos_settings['disable_express_checkout'] != 0 || !array_key_exists('cash', $payment_types)) hide @endif"
                    data-pay_method="cash" title="@lang('tooltip.express_checkout')">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>@lang('lang_v1.express_checkout_cash')</span>
                </button>
            @endif
        </div>

    </div>
</div>

<style>
/* ===== VsoftPOS Action Bar ===== */
.vspos-action-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #1e293b, #0f172a);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.25);
    border-top: 1px solid rgba(255,255,255,0.06);
}

.vspos-action-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px;
    overflow-x: auto;
    min-height: 56px;
    flex-wrap: wrap;
}

.vspos-action-secondary {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    flex: 1;
}

.vspos-action-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

/* Secondary icon buttons */
.vspos-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 6px 10px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    transition: opacity 0.15s, transform 0.1s;
    min-width: 52px;
    line-height: 1;
}
.vspos-action-btn i { font-size: 14px; margin-bottom: 1px; }
.vspos-action-btn span { font-size: 9px; }
.vspos-action-btn:hover { opacity: 0.85; }
.vspos-action-btn:active { transform: scale(0.95); }

.vspos-action-btn--cancel  { background: #fee2e2; color: #dc2626; }
.vspos-action-btn--danger  { background: #dc2626; color: #fff; }
.vspos-action-btn--draft   { background: #e0f2fe; color: #0369a1; }
.vspos-action-btn--quote   { background: #fef3c7; color: #b45309; }
.vspos-action-btn--suspend { background: #fce7f3; color: #be185d; }
.vspos-action-btn--credit  { background: #ede9fe; color: #6d28d9; }
.vspos-action-btn--card    { background: #fdf2f8; color: #9d174d; }
.vspos-action-btn--recent  { background: rgba(99,102,241,0.2); color: #a5b4fc; }
.vspos-action-btn--supplier { background: #ecfdf5; color: #059669; }

/* Primary CTA buttons */
.vspos-cta-btn {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.2px;
    white-space: nowrap;
    color: #fff;
    transition: opacity 0.15s, transform 0.1s;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
}
.vspos-cta-btn i { font-size: 15px; }
.vspos-cta-btn:hover { opacity: 0.9; }
.vspos-cta-btn:active { transform: scale(0.97); }

.vspos-cta-btn--multi { background: linear-gradient(135deg, #6366f1, #4338ca); }
.vspos-cta-btn--cash  { background: linear-gradient(135deg, #10b981, #047857); }

@media (max-width: 480px) {
    .vspos-cta-btn span { display: none; }
    .vspos-cta-btn { padding: 10px 14px; }
    .vspos-action-btn span { display: none; }
    .vspos-action-btn { padding: 8px; }
}
</style>
@if (isset($transaction))
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $transaction->discount_amount,
        'discount_type' => $transaction->discount_type,
        'rp_redeemed' => $transaction->rp_redeemed,
        'rp_redeemed_amount' => $transaction->rp_redeemed_amount,
        'max_available' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0,
    ])
@else
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $business_details->default_sales_discount,
        'discount_type' => 'percentage',
        'rp_redeemed' => 0,
        'rp_redeemed_amount' => 0,
        'max_available' => 0,
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
    @include('sale_pos.partials.edit_order_tax_modal', [
        'selected_tax' => $business_details->default_sales_tax,
    ])
@endif

@include('sale_pos.partials.edit_shipping_modal')
