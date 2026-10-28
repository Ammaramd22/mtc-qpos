@php
    $is_admin = auth()->user()->hasRole('Admin#'.session('user.business_id'));
    $can_sell = auth()->user()->can('sell.create') || auth()->user()->can('direct_sell.access');
    $sc = !empty($shortcuts['pos']) ? $shortcuts['pos'] : [];
    $cash_shortcut = strtoupper($sc['express_checkout'] ?? 'f1');
    $cash_add_shortcut = 'F2';
    $pay_shortcut = strtoupper($sc['pay_n_ckeckout'] ?? 'f3');
    $card_shortcut = strtoupper($sc['express_checkout_card'] ?? 'f5');
    $credit_shortcut = strtoupper($sc['credit_sale'] ?? 'f6');
    $cheque_shortcut = 'F7';
    $split_shortcut = strtoupper($sc['add_payment_row'] ?? 'f12');
    $cancel_shortcut = strtoupper($sc['cancel'] ?? 'esc');
    $draft_shortcut = strtoupper($sc['draft'] ?? 'shift+d');
    $bank_shortcut = 'F11';
@endphp

<div class="vspos-mobile-pay-backdrop" id="vspos_mobile_pay_backdrop" aria-hidden="true"></div>
<div class="vspos-mobile-pay-popup vspos-pay-v2" id="vspos_mobile_pay_popup" aria-hidden="true" role="dialog" aria-label="Payment options"
    data-key-cash="{{ strtolower($sc['express_checkout'] ?? 'f1') }}"
    data-key-cash-add="f2"
    data-key-card="{{ strtolower($sc['express_checkout_card'] ?? 'f5') }}"
    data-key-credit="{{ strtolower($sc['credit_sale'] ?? 'f6') }}"
    data-key-cheque="f7"
    data-key-bank="f11"
    data-key-split="{{ strtolower($sc['add_payment_row'] ?? 'f12') }}">

    {{-- VIEW 1: Quick pay --}}
    <div class="vspos-pay-view" id="vspos_pay_view_main">
        <div class="vspos-mobile-pay-popup-handle" aria-hidden="true"><span></span></div>

        <div class="vspos-pay-v2-head">
            <button type="button" class="vspos-mpos-pay-back" id="vspos_mpos_pay_back" aria-label="Back">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
            </button>
            <div class="vspos-pay-v2-head-left">
                <div class="vspos-pay-v2-title">
                    <i class="fi fi-rr-wallet pos-fi-icon"></i> @lang('lang_v1.payment')
                    @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch' && !empty($shortcuts['pos']['pay_n_ckeckout']))
                        <kbd class="vspos-touch-pay-popup-kbd">{{ strtoupper($shortcuts['pos']['pay_n_ckeckout']) }}</kbd>
                    @endif
                </div>
                @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) !== 'touch')
                <div class="vspos-pay-v2-keys">
                    <span><kbd>{{ $cash_shortcut }}</kbd> Cash</span>
                    <span><kbd>{{ $card_shortcut }}</kbd> Card</span>
                    <span><kbd>{{ $credit_shortcut }}</kbd> Credit</span>
                </div>
                @endif
            </div>
            <button type="button" class="vspos-mobile-pay-popup-close" id="vspos_mobile_pay_close" aria-label="Close">
                <i class="fi fi-rr-cross pos-fi-icon"></i>
            </button>
        </div>

        @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) !== 'touch')
        <div class="vspos-pay-v2-hero">
            <div class="vspos-pay-v2-hero-label">@lang('sale.total_payable')</div>
            <div class="vspos-pay-v2-hero-amount grand_total_mirror">0.00</div>
        </div>
        @endif

        @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch')
        {{-- Touch checkout: methods left · keypad right · multipay list below --}}
        <div class="vspos-touch-pay" id="vspos_touch_pay"
            data-default-payment-accounts="{{ is_string($default_location->default_payment_accounts ?? null) ? ($default_location->default_payment_accounts ?? '') : json_encode($default_location->default_payment_accounts ?? []) }}">
            <div class="vspos-touch-pay-scroll">
            <div class="vspos-touch-pay-hero">
                <div class="vspos-touch-pay-hero-main">
                    <div class="vspos-pay-v2-hero-label">@lang('sale.total_payable')</div>
                    <div class="vspos-pay-v2-hero-amount grand_total_mirror">0.00</div>
                </div>
                <div class="vspos-touch-pay-meters">
                    <div class="vspos-touch-pay-meter">
                        <span>Paid</span>
                        <strong id="vspos_touch_pay_paid">0.00</strong>
                    </div>
                    <div class="vspos-touch-pay-meter vspos-touch-pay-meter--due">
                        <span>Due</span>
                        <strong id="vspos_touch_pay_due">0.00</strong>
                    </div>
                    <div class="vspos-touch-pay-meter vspos-touch-pay-meter--change">
                        <span>Change</span>
                        <strong id="vspos_touch_pay_change">0.00</strong>
                    </div>
                </div>
            </div>

            <div class="vspos-touch-pay-list-panel">
                <div class="vspos-touch-pay-list-head">
                    <span>Payments</span>
                    <button type="button" class="vspos-touch-pay-clear-list" id="vspos_touch_pay_clear">Clear</button>
                </div>
                <div id="vspos_touch_pay_list" class="vspos-touch-pay-list">
                    <div class="vspos-touch-pay-list-empty">No payments yet</div>
                </div>
                <button type="button" class="vspos-touch-add-another" id="vspos_touch_add_another">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    <span>Add Another Payment</span>
                </button>
                <div id="vspos_multipay_rows_mount" class="vspos-touch-pay-rows-host"></div>
            </div>

            <div class="vspos-touch-pay-workspace">
                <aside class="vspos-touch-pay-methods" aria-label="Payment methods">
                    @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                        @if (!empty($payment_types) && array_key_exists('cash', $payment_types))
                            <button type="button"
                                class="vspos-touch-pay-method vspos-touch-pay-method--fullcash @if(!empty($pos_settings['disable_express_checkout']) && $pos_settings['disable_express_checkout'] != 0) hide @endif"
                                id="vspos_cash_full_pay_btn">
                                <i class="fi fi-rr-money pos-fi-icon"></i>
                                <span>@lang('lang_v1.cash_full_pay')</span>
                                <kbd>{{ $cash_shortcut }}</kbd>
                            </button>
                        @endif
                    @endif

                    @if (!empty($payment_types) && array_key_exists('cash', $payment_types))
                        <button type="button" class="vspos-touch-pay-method vspos-touch-pay-method--cash" data-touch-pay-method="cash">
                            <i class="fi fi-rr-coins pos-fi-icon"></i>
                            <span>Cash</span>
                            <kbd>{{ $cash_add_shortcut }}</kbd>
                        </button>
                    @endif
                    @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                        @if (!empty($payment_types) && array_key_exists('card', $payment_types))
                            <button type="button" class="vspos-touch-pay-method vspos-touch-pay-method--card" data-touch-pay-method="card">
                                <i class="fi fi-rr-credit-card pos-fi-icon"></i>
                                <span>Card</span>
                                <kbd>{{ $card_shortcut }}</kbd>
                            </button>
                        @endif
                    @endif
                    @if (!empty($payment_types) && array_key_exists('bank_transfer', $payment_types))
                        <button type="button" class="vspos-touch-pay-method vspos-touch-pay-method--bank" data-touch-pay-method="bank_transfer">
                            <i class="fi fi-rr-bank pos-fi-icon"></i>
                            <span>@lang('lang_v1.bank_transfer')</span>
                            <kbd>{{ $bank_shortcut }}</kbd>
                        </button>
                    @endif
                    @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                        @if (empty($pos_settings['disable_credit_sale_button']))
                            <button type="button" class="vspos-touch-pay-method vspos-touch-pay-method--credit vspos-mobile-pay-trigger"
                                data-vspos-trigger=".vspos-order-panel .vspos-pay-quick--credit">
                                <i class="fi fi-rr-user-clock pos-fi-icon"></i>
                                <span>Credit</span>
                                <kbd>{{ $credit_shortcut }}</kbd>
                            </button>
                        @endif
                    @endif
                    @if (!empty($payment_types) && array_key_exists('cheque', $payment_types))
                        <button type="button" class="vspos-touch-pay-method vspos-touch-pay-method--cheque" data-touch-pay-method="cheque">
                            <i class="fi fi-rr-file-invoice-dollar pos-fi-icon"></i>
                            <span>Cheque</span>
                            <kbd>{{ $cheque_shortcut }}</kbd>
                        </button>
                    @endif
                </aside>

                <div class="vspos-touch-pay-keypad-col">
                    <div class="vspos-pay-v2-tender vspos-touch-pay-tender">
                        <label class="vspos-pay-v2-tender-label" for="vspos_pay_amount_input">Amount</label>
                        <input type="text" id="vspos_pay_amount_input" class="vspos-pay-amount-input vspos-pay-v2-input input_number" inputmode="decimal" placeholder="0.00" autocomplete="off">
                        <div class="vspos-pay-v2-change">
                            <span>@lang('lang_v1.change_return')</span>
                            <strong id="vspos_pay_change_preview">0.00</strong>
                        </div>
                    </div>
                    @if (!empty($accounts))
                        <div class="vspos-touch-pay-account" id="vspos_touch_pay_account_wrap">
                            <label class="vspos-pay-v2-tender-label" for="vspos_touch_pay_account">@lang('lang_v1.payment_account')</label>
                            <select id="vspos_touch_pay_account" class="vspos-touch-pay-account-select">
                                @foreach($accounts as $acc_id => $acc_name)
                                    <option value="{{ $acc_id }}">{{ $acc_name }}</option>
                                @endforeach
                            </select>
                            <p class="vspos-touch-pay-account-hint" id="vspos_touch_pay_account_hint">Auto-linked from location default · change only if needed</p>
                        </div>
                        <script type="application/json" id="vspos_touch_pay_accounts_json">{!! json_encode($accounts, JSON_UNESCAPED_UNICODE) !!}</script>
                    @endif
                    @include('sale_pos.partials.touch_keypad')
                    <p class="vspos-touch-pay-hint">Enter amount → tap a method to add · repeat for split</p>
                </div>
            </div>
            </div>

            <div class="vspos-touch-pay-footer">
                <button type="button" class="vspos-pay-v2-sec vspos-pay-v2-sec--cancel vspos-mobile-pay-trigger" data-vspos-trigger=".vspos-order-panel .js-pos-cancel">
                    <i class="fi fi-rr-cross-circle pos-fi-icon"></i>
                    <span>Cancel</span>
                </button>
                <button type="button" class="vspos-touch-pay-complete" id="vspos_multipay_finalize">
                    <i class="fi fi-rr-check pos-fi-icon"></i>
                    <span>Complete</span>
                </button>
            </div>

            {{-- Cheque details sheet (shown when Cheque is tapped) --}}
            @if (!empty($payment_types) && array_key_exists('cheque', $payment_types))
            <div class="vspos-touch-cheque-sheet" id="vspos_touch_cheque_sheet" aria-hidden="true" hidden>
                <div class="vspos-touch-cheque-sheet-card">
                    <div class="vspos-touch-cheque-sheet-head">
                        <div>
                            <strong><i class="fi fi-rr-file-invoice-dollar pos-fi-icon"></i> Cheque Details</strong>
                            <p>Enter cheque info to continue</p>
                        </div>
                        <button type="button" class="vspos-touch-cheque-sheet-close" id="vspos_touch_cheque_cancel" aria-label="Close">&times;</button>
                    </div>
                    <div class="vspos-touch-cheque-sheet-amount">
                        Amount <strong id="vspos_touch_cheque_amount_label">0.00</strong>
                    </div>
                    <div class="vspos-touch-cheque-fields">
                        <label>
                            <span>@lang('lang_v1.cheque_name')</span>
                            <input type="text" id="vspos_touch_cheque_name" class="vspos-touch-cheque-input" placeholder="@lang('lang_v1.cheque_name_placeholder')" autocomplete="off">
                        </label>
                        <label>
                            <span>@lang('lang_v1.cheque_no')</span>
                            <input type="text" id="vspos_touch_cheque_number" class="vspos-touch-cheque-input" placeholder="@lang('lang_v1.cheque_no')" autocomplete="off">
                        </label>
                        <label class="vspos-touch-cheque-field--bank">
                            <span>@lang('lang_v1.cheque_bank')</span>
                            <select id="vspos_touch_cheque_bank" class="vspos-touch-cheque-input vspos-touch-cheque-bank-select select2" data-placeholder="@lang('lang_v1.select_bank')">
                                <option value="">@lang('lang_v1.select_bank')</option>
                                @foreach(\App\Utils\SriLankanBankUtil::getBanks() as $bank_val => $bank_label)
                                    <option value="{{ $bank_val }}">{{ $bank_label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="vspos-touch-cheque-field--date">
                            <span>@lang('lang_v1.cheque_due_date')</span>
                            <input type="text" id="vspos_touch_cheque_date" class="vspos-touch-cheque-input cheque_date_picker" placeholder="{{ now()->format(session('business.date_format', 'd-m-Y')) }}" autocomplete="off" readonly>
                        </label>
                    </div>
                    <div class="vspos-touch-cheque-sheet-actions">
                        <button type="button" class="vspos-touch-cheque-btn vspos-touch-cheque-btn--cancel" id="vspos_touch_cheque_back">
                            <span>Back</span>
                        </button>
                        <button type="button" class="vspos-touch-cheque-btn vspos-touch-cheque-btn--add" id="vspos_touch_cheque_confirm">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <span>Add Cheque</span>
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @else
        <div class="vspos-pay-v2-tender">
            <div class="vspos-pay-v2-tender-row">
                <label class="vspos-pay-v2-tender-label" for="vspos_pay_amount_input">@lang('lang_v1.cd_enter_amount')</label>
                <input type="text" id="vspos_pay_amount_input" class="vspos-pay-amount-input vspos-pay-v2-input input_number" inputmode="decimal" placeholder="0.00" autocomplete="off">
            </div>
            <div class="vspos-pay-v2-change">
                <span>@lang('lang_v1.change_return')</span>
                <strong id="vspos_pay_change_preview">0.00</strong>
            </div>
        </div>

        <div class="vspos-pay-v2-actions">
            @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                @if (!empty($payment_types) && array_key_exists('cash', $payment_types))
                    <button type="button"
                        class="vspos-pay-v2-cash @if(!empty($pos_settings['disable_express_checkout']) && $pos_settings['disable_express_checkout'] != 0) hide @endif"
                        id="vspos_cash_full_pay_btn">
                        <span class="vspos-pay-v2-cash-main">
                            <i class="fi fi-rr-money pos-fi-icon"></i>
                            <span>@lang('lang_v1.cash_full_pay')</span>
                        </span>
                        <span class="vspos-pay-v2-kbd">{{ $cash_shortcut }}</span>
                    </button>
                @endif
            @endif

            <div class="vspos-pay-v2-express">
                @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                    @if (!empty($payment_types) && array_key_exists('card', $payment_types))
                        <button type="button" class="vspos-pay-v2-chip vspos-pay-v2-chip--card vspos-mobile-pay-trigger"
                            data-vspos-trigger=".vspos-order-panel .vspos-pay-quick--card">
                            <i class="fi fi-rr-credit-card pos-fi-icon"></i>
                            <span>Card</span>
                            <span class="vspos-pay-v2-kbd">{{ $card_shortcut }}</span>
                        </button>
                    @endif
                @endif
                @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                    @if (empty($pos_settings['disable_credit_sale_button']))
                        <button type="button" class="vspos-pay-v2-chip vspos-pay-v2-chip--credit vspos-mobile-pay-trigger"
                            data-vspos-trigger=".vspos-order-panel .vspos-pay-quick--credit">
                            <i class="fi fi-rr-user-clock pos-fi-icon"></i>
                            <span>Credit</span>
                            <span class="vspos-pay-v2-kbd">{{ $credit_shortcut }}</span>
                        </button>
                    @endif
                @endif
                @if (!empty($payment_types) && array_key_exists('bank_transfer', $payment_types))
                    <button type="button" class="vspos-pay-v2-chip vspos-pay-v2-chip--bank vspos-mobile-pay-trigger"
                        data-vspos-trigger=".vspos-order-panel .vspos-pay-quick--bank">
                        <i class="fi fi-rr-bank pos-fi-icon"></i>
                        <span>@lang('lang_v1.bank_transfer')</span>
                        <span class="vspos-pay-v2-kbd">{{ $bank_shortcut }}</span>
                    </button>
                @endif
            </div>

            @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                <button type="button"
                    class="vspos-pay-v2-split @if(!empty($pos_settings['disable_pay_checkout']) && $pos_settings['disable_pay_checkout'] != 0) hide @endif"
                    id="vspos_pay_multipay_btn">
                    <span class="vspos-pay-v2-split-main">
                        <i class="fi fi-rr-layers pos-fi-icon"></i>
                        <span>@lang('lang_v1.split_payment')</span>
                    </span>
                    <span class="vspos-pay-v2-kbd">{{ $split_shortcut }}</span>
                </button>
            @endif
        </div>

        <div class="vspos-pay-v2-foot">
            <button type="button" class="vspos-pay-v2-sec vspos-pay-v2-sec--cancel vspos-mobile-pay-trigger" data-vspos-trigger=".vspos-order-panel .js-pos-cancel">
                <i class="fi fi-rr-cross-circle pos-fi-icon"></i>
                <span>Cancel</span>
                <span class="vspos-pay-v2-kbd">{{ $cancel_shortcut }}</span>
            </button>
            <button type="button" class="vspos-pay-v2-sec vspos-pay-v2-sec--draft vspos-mobile-pay-trigger" data-vspos-trigger="#pos-draft">
                <i class="fi fi-rr-disk pos-fi-icon"></i>
                <span>Draft</span>
                <span class="vspos-pay-v2-kbd">{{ $draft_shortcut }}</span>
            </button>
            @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || !empty($is_admin) || !empty($can_sell))
            <button type="button" class="vspos-pay-v2-sec vspos-pay-v2-sec--quote vspos-mobile-pay-trigger @if(!empty($pos_settings['disable_quotation']) && $pos_settings['disable_quotation'] != 0) hide @endif" data-vspos-trigger="#pos-quotation">
                <i class="fi fi-rr-document pos-fi-icon"></i>
                <span>@lang('lang_v1.quotation')</span>
                <span class="vspos-pay-v2-kbd">{{ !empty($shortcuts['pos']['quotation']) ? $shortcuts['pos']['quotation'] : 'Shift+Q' }}</span>
            </button>
            @endif
            <button type="button" class="vspos-pay-v2-sec vspos-pay-v2-sec--details" id="vspos_mobile_pay_details_btn">
                <i class="fi fi-rr-receipt pos-fi-icon"></i>
                <span>Details</span>
            </button>
        </div>
        @endif
    </div>

    {{-- VIEW 2: Split / Multi Pay (classic only) --}}
    @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) !== 'touch')
    <div class="vspos-pay-view vspos-pay-view--hidden" id="vspos_pay_view_multipay">
        <div class="vspos-mp-header">
            <button type="button" class="vspos-mp-back" id="vspos_multipay_back" aria-label="Back">
                <i class="fi fi-rr-angle-small-left pos-fi-icon"></i>
            </button>
            <div class="vspos-mp-header-text">
                <div class="vspos-mp-kicker">Checkout</div>
                <div class="vspos-mp-title">@lang('lang_v1.split_payment')</div>
            </div>
            <button type="button" class="vspos-mobile-pay-popup-close vspos-mp-close" id="vspos_multipay_close" aria-label="Close">
                <i class="fi fi-rr-cross pos-fi-icon"></i>
            </button>
        </div>

        <div class="vspos-mp-hero">
            <div class="vspos-mp-hero-label">@lang('sale.total_payable')</div>
            <div class="vspos-mp-hero-amount grand_total_mirror">0.00</div>
            <div class="vspos-mp-hero-hint">@lang('lang_v1.add_multiple_payment_rows')</div>
        </div>

        <div class="vspos-mp-meters">
            <div class="vspos-mp-meter">
                <span class="lbl">@lang('lang_v1.total_paying')</span>
                <span class="val vspos-mp-paying total_paying">0.00</span>
            </div>
            <div class="vspos-mp-meter vspos-mp-meter--due">
                <span class="lbl">@lang('lang_v1.balance')</span>
                <span class="val vspos-mp-balance balance_due">0.00</span>
            </div>
            <div class="vspos-mp-meter vspos-mp-meter--change">
                <span class="lbl">@lang('lang_v1.change_return')</span>
                <span class="val vspos-mp-change change_return_span">0.00</span>
            </div>
        </div>

        <div class="vspos-mp-body">
            <div class="vspos-mp-section-label"><i class="fi fi-rr-credit-card pos-fi-icon"></i> Payment methods</div>
            <div id="vspos_multipay_rows_mount" class="vspos-mp-rows"></div>
            <button type="button" class="vspos-mp-add-row" id="vspos_multipay_add_row">
                <i class="fi fi-rr-plus pos-fi-icon"></i> @lang('sale.add_payment_row')
            </button>
        </div>

        <div class="vspos-mp-footer">
            <button type="button" class="vspos-mp-finalize" id="vspos_multipay_finalize">
                <i class="fi fi-rr-check pos-fi-icon"></i> @lang('sale.finalize_payment')
            </button>
        </div>
    </div>
    @else
    {{-- Touch keeps these stubs so classic JS selectors don't throw --}}
    <div id="vspos_pay_view_multipay" class="vspos-pay-view vspos-pay-view--hidden" hidden></div>
    <button type="button" id="vspos_multipay_back" class="hide" tabindex="-1" aria-hidden="true"></button>
    <button type="button" id="vspos_multipay_close" class="hide" tabindex="-1" aria-hidden="true"></button>
    <button type="button" id="vspos_multipay_add_row" class="hide" tabindex="-1" aria-hidden="true"></button>
    <button type="button" id="vspos_pay_multipay_btn" class="hide" tabindex="-1" aria-hidden="true"></button>
    @endif
</div>

{{-- Loyalty OTP popup (for payment-row redeem) --}}
@php
    $_mp_cs = session('business.common_settings') ?? [];
    if (is_string($_mp_cs)) { $_mp_cs = json_decode($_mp_cs, true) ?? []; }
@endphp
@if(session('business.enable_rp') == 1 && !empty($_mp_cs['loyalty_otp_on_redeem']))
<div class="modal fade vspos-pos-modal" id="vsposLoyaltyOtpModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog" role="document" style="width:min(380px,92vw);margin:18vh auto;">
        <div class="modal-content vspos-pos-modal-content" style="border-radius:16px;overflow:hidden;border:1px solid #333;background:#141414;">
            <div class="modal-header" style="border-bottom:1px solid #2a2a2a;background:#1a1a1a;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.8;"><span>&times;</span></button>
                <h4 class="modal-title" style="color:#f5c518;font-weight:800;">
                    <i class="fa fa-shield"></i> @lang('lang_v1.loyalty_otp_verification')
                </h4>
            </div>
            <div class="modal-body" style="padding:18px;">
                <p style="color:#a3a3a3;font-size:13px;margin:0 0 12px;" id="vspos_loyalty_otp_mobile_hint">
                    OTP sent to customer mobile
                </p>
                <label style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;">@lang('lang_v1.enter_otp')</label>
                <input type="text" id="vspos_loyalty_otp_input" class="form-control" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                    placeholder="••••••" style="height:48px;font-size:22px;font-weight:800;letter-spacing:8px;text-align:center;background:#0c0c0c;border:1px solid #333;color:#fafafa;border-radius:10px;">
                <p id="vspos_loyalty_otp_modal_status" style="margin:10px 0 0;font-size:12px;font-weight:600;"></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid #2a2a2a;background:#121212;">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="vspos_loyalty_otp_verify_btn" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:800;">
                    <i class="fa fa-check"></i> @lang('lang_v1.verify_otp')
                </button>
            </div>
        </div>
    </div>
</div>
@endif
