@extends('layouts.app')

@section('title', !empty($is_pop) ? __('lang_v1.point_of_purchase') : __('sale.pos_sale'))

@section('content')
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($is_pop))
            <input type="hidden" id="is_pop_mode" value="1">
            {{-- POP always allows adding below current stock (buying in) --}}
            <input type="hidden" id="is_overselling_allowed">
        @elseif (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (!empty($pos_settings['enable_serial_per_qty']))
            <input type="hidden" id="enable_serial_per_qty" value="1">
        @endif
        @if (!empty($pos_settings['enable_handheld_58mm']))
            <input type="hidden" id="enable_handheld_58mm" value="1">
        @endif
        @if (!empty($pos_settings['handheld_immediate_print']))
            <input type="hidden" id="handheld_immediate_print" value="1">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
            $_loyalty_cs = session('business.common_settings') ?? [];
            if (is_string($_loyalty_cs)) {
                $_loyalty_cs = json_decode($_loyalty_cs, true) ?? [];
            }
            $pos_form_id = !empty($edit) ? 'edit_pos_sell_form' : 'add_pos_sell_form';
            $pos_location = !empty($edit) && !empty($transaction)
                ? ($transaction->location ?? $default_location ?? null)
                : ($default_location ?? null);
            // Always follow Business Settings → POS UI (same for create and edit)
            // POP uses pop_ui (already passed as $pos_ui from PurchasePosController)
            if (empty($pos_ui)) {
                $pos_ui = (($pos_settings['pos_ui'] ?? 'classic') === 'touch') ? 'touch' : 'classic';
            }
        @endphp
        @if(!empty($edit) && !empty($transaction))
            {!! Form::open([
                'url' => action([\App\Http\Controllers\SellPosController::class, 'update'], [$transaction->id]),
                'method' => 'post',
                'id' => $pos_form_id,
            ]) !!}
            {{ method_field('PUT') }}
        @elseif(!empty($is_pop))
            {!! Form::open([
                'url' => action([\App\Http\Controllers\PurchasePosController::class, 'store']),
                'method' => 'post',
                'id' => $pos_form_id,
            ]) !!}
        @else
            {!! Form::open([
                'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
                'method' => 'post',
                'id' => $pos_form_id,
            ]) !!}
        @endif
        <div class="row" style="margin:0;">
            <div class="col-md-12" style="padding:0;">
                @if($pos_ui === 'touch')
                    @include('sale_pos.partials.touch_main')
                @else
                <div class="vspos-main-layout">

                    {{-- LEFT: Cart panel --}}
                    <div class="vspos-cart-panel">
                        <div class="vspos-cart-inner box-body pb-0">
                            {!! Form::hidden('location_id', $pos_location->id ?? null, [
                                'id' => 'location_id',
                                'data-receipt_printer_type' => !empty($pos_location->receipt_printer_type)
                                    ? $pos_location->receipt_printer_type
                                    : (!empty($location_printer_type) ? $location_printer_type : 'browser'),
                                'data-default_payment_accounts' => $pos_location->default_payment_accounts ?? '',
                            ]) !!}
                            {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                            @if(!empty($mobile_repair_checkout))
                                {!! Form::hidden('mobile_repair_id', $mobile_repair_checkout['repair_id'], ['id' => 'mobile_repair_id']) !!}
                            @endif
                            @if(!empty($workshop_job_checkout))
                                {!! Form::hidden('workshop_job_id', $workshop_job_checkout['job_id'], ['id' => 'workshop_job_id']) !!}
                            @endif
                            <input type="hidden" id="item_addition_method" value="{{ !empty($is_pop) ? 0 : $business_details->item_addition_method }}">
                            <input type="hidden" name="is_credit_sale" id="is_credit_sale" value="0">
                            @if(!empty($edit) && !empty($transaction))
                                <p class="vspos-edit-invoice-no" style="margin:0 0 8px;font-size:12px;opacity:.75;">
                                    <strong>@lang('sale.invoice_no'):</strong> {{ $transaction->invoice_no }}
                                </p>
                            @endif
                            {{-- Hidden form inputs for totals MUST stay inside the form --}}
                            @include('sale_pos.partials.pos_form_totals', ['edit' => !empty($edit)])
                            @include('sale_pos.partials.pos_form', ['edit' => !empty($edit)])
                            @include('sale_pos.partials.payment_modal')
                            @if (empty($pos_settings['disable_suspend']))
                                @include('sale_pos.partials.suspend_note_modal')
                            @endif
                            @if (empty($pos_settings['disable_recurring_invoice']))
                                @include('sale_pos.partials.recurring_invoice_modal')
                            @endif
                            @if(!empty($only_payment))
                                <div class="overlay"></div>
                            @endif
                        </div>
                    </div>

                    {{-- RIGHT: Order details panel --}}
                    <div class="vspos-order-panel vspos-order-panel--collapsed" id="vspos_order_panel">

                        {{-- Mobile: dark strip — total + Pay shortcut + order details — hidden on desktop --}}
                        <div class="vspos-mobile-strip" id="vspos_mobile_strip" aria-expanded="false" aria-controls="vspos_order_panel_body">
                            <button type="button" class="vspos-mobile-strip-details" id="vspos_mobile_details_btn" aria-label="Order details">
                                <i class="fi fi-rr-angle-small-up pos-fi-icon"></i>
                            </button>
                            <div class="vspos-mobile-strip-total">
                                <span class="vspos-mobile-strip-label">Total Payable</span>
                                <span class="vspos-mobile-strip-value grand_total_mirror">0.00</span>
                            </div>
                            <button type="button" class="vspos-mobile-strip-pay" id="vspos_mobile_pay_btn">
                                <i class="fi fi-rr-wallet pos-fi-icon"></i>
                                <span>Pay</span>
                                @if(!empty($shortcuts['pos']['pay_n_ckeckout']))
                                    <span class="keycap">{{ $shortcuts['pos']['pay_n_ckeckout'] }}</span>
                                @endif
                            </button>
                        </div>

                        <div class="vspos-order-panel-body" id="vspos_order_panel_body">

                        {{-- Mobile drag handle --}}
                        <div class="vspos-mobile-handle" id="vspos_mobile_handle" role="button" tabindex="0" aria-label="Collapse payment panel">
                            <span class="vspos-handle-bar"></span>
                        </div>

                        {{-- Last Bill / Purchase + Recent --}}
                        @php
                            $lb_is_pop = !empty($is_pop);
                            $lb_kicker = $lb_is_pop ? 'Last purchase' : 'Last bill';
                            $lb_empty = $lb_is_pop ? 'No recent purchase' : 'No recent bill';
                            $lb_hub_title = $lb_is_pop ? 'Purchase invoices' : 'Bills & Reprint';
                        @endphp
                        <div class="vspos-last-bill-bar">
                            @if(!empty($last_sell))
                            <div class="vspos-lb-info">
                                <span class="vspos-lb-kicker"><i class="fi fi-rr-receipt pos-fi-icon"></i> {{ $lb_kicker }}</span>
                                <div class="vspos-lb-meta">
                                    <span class="vspos-lb-invoice"># {{ $last_sell->invoice_no }}</span>
                                    <span class="vspos-lb-amount">@format_currency($last_sell->final_total)</span>
                                </div>
                            </div>
                            <div class="vspos-lb-actions">
                                <button type="button" class="vspos-lb-print-btn js-print-last-bill"
                                    data-id="{{ $last_sell->id }}" title="Reprint">
                                    <i class="fi fi-rr-print pos-fi-icon"></i> Reprint
                                </button>
                                @if($lb_is_pop)
                                <button type="button" class="vspos-lb-print-btn btn-modal"
                                    data-href="{{ action([\App\Http\Controllers\PurchaseController::class, 'show'], [$last_sell->id]) }}"
                                    data-container=".view_modal"
                                    title="@lang('messages.view')">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                @endif
                                @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                                <button type="button" class="vspos-lb-recent-btn"
                                    data-toggle="modal" data-target="#recent_transactions_modal"
                                    title="{{ $lb_hub_title }}">
                                    <i class="fi fi-rr-print pos-fi-icon"></i>
                                </button>
                                @endif
                            </div>
                            @else
                            <div class="vspos-lb-info">
                                <span class="vspos-lb-kicker"><i class="fi fi-rr-receipt pos-fi-icon"></i> {{ $lb_empty }}</span>
                            </div>
                            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                            <div class="vspos-lb-actions">
                                <button type="button" class="vspos-lb-recent-btn"
                                    data-toggle="modal" data-target="#recent_transactions_modal"
                                    title="{{ $lb_hub_title }}">
                                    <i class="fi fi-rr-print pos-fi-icon"></i>
                                </button>
                            </div>
                            @endif
                            @endif
                        </div>

                        {{-- Sales history: button only; list opens in popup --}}
                        <div class="vspos-cust-hist-btn-wrap hide" id="vspos_customer_history">
                            <button type="button" class="vspos-cust-hist-btn" id="vspos_open_cust_hist"
                                data-toggle="modal" data-target="#vspos_customer_history_modal"
                                title="@lang('lang_v1.customer_sales_history')">
                                <span class="vspos-cust-hist-btn-main">
                                    <i class="fi fi-rr-time-past pos-fi-icon"></i>
                                    <span>@lang('lang_v1.customer_sales_history')</span>
                                </span>
                                @if(session('business.enable_rp') == 1)
                                <span class="vspos-cust-hist-btn-pts" id="vspos_cust_hist_loyalty">
                                    <i class="fa fa-gift"></i>
                                    <strong><span id="vspos_cust_hist_pts">0</span> pts</strong>
                                </span>
                                @else
                                <span class="hide"><span id="vspos_cust_hist_pts">0</span></span>
                                @endif
                            </button>
                        </div>

                        {{-- This sale summary --}}
                        <div class="vspos-op-summary">
                            <div class="vspos-op-kicker">This sale</div>
                            <div class="vspos-stat-grid">
                                <div class="vspos-stat-card">
                                    <span class="vspos-stat-label">Items</span>
                                    <span class="vspos-stat-value total_quantity_mirror">0</span>
                                </div>
                                <div class="vspos-stat-card">
                                    <span class="vspos-stat-label">Subtotal</span>
                                    <span class="vspos-stat-value price_total_mirror">0.00</span>
                                </div>
                                <div class="vspos-stat-card vspos-stat-card--discount" id="vspos_op_disc_row">
                                    <span class="vspos-stat-label">
                                        Discount
                                        <span data-toggle="modal" data-target="#billDiscountModal" title="Set bill discount" class="vspos-discount-edit">
                                            <i class="fi fi-rr-pencil pos-fi-icon"></i>
                                        </span>
                                    </span>
                                    <span class="vspos-stat-value total_discount_mirror">0.00</span>
                                </div>
                                @if(is_tax_enabled())
                                <div class="vspos-stat-card">
                                    <span class="vspos-stat-label">Tax</span>
                                    <span class="vspos-stat-value total_tax_mirror">0.00</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Pay button (opens payment popup) --}}
                        @php
                            $is_admin = auth()->user()->hasRole('Admin#'.session('user.business_id'));
                            $can_sell = auth()->user()->can('sell.create') || auth()->user()->can('direct_sell.access');
                        @endphp
                        <div class="vspos-op-pay-section vspos-op-pay-section--single">
                            <div class="vspos-op-pay-total">
                                <span class="vspos-op-pay-total-label">@lang('sale.total_payable')</span>
                                <span class="vspos-op-pay-total-value grand_total_mirror">0.00</span>
                            </div>
                            @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                <button type="button"
                                    class="vspos-op-pay-main-btn vspos-open-pay-popup @if(!empty($pos_settings['disable_pay_checkout']) && $pos_settings['disable_pay_checkout'] != 0) hide @endif">
                                    <span class="vspos-op-pay-main-row">
                                        <i class="fi fi-rr-wallet pos-fi-icon"></i>
                                        <span class="vspos-op-pay-main-label">@lang('lang_v1.pay')</span>
                                    </span>
                                    @if(!empty($shortcuts['pos']['pay_n_ckeckout']))
                                        <span class="keycap">{{ $shortcuts['pos']['pay_n_ckeckout'] }}</span>
                                    @endif
                                </button>
                                <button type="button" class="hide" id="vspos_pos_finalize_trigger" tabindex="-1" aria-hidden="true"></button>
                            @endif
                            @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                <button type="button"
                                    class="vspos-op-quote-btn @if(!empty($pos_settings['disable_quotation']) && $pos_settings['disable_quotation'] != 0) hide @endif"
                                    id="pos-quotation"
                                    title="@lang('lang_v1.quotation')">
                                    <span class="vspos-op-quote-row">
                                        <i class="fi fi-rr-document pos-fi-icon"></i>
                                        <span class="vspos-op-quote-label">@lang('lang_v1.quotation')</span>
                                    </span>
                                    @if(!empty($shortcuts['pos']['quotation']))
                                        <span class="keycap">{{ $shortcuts['pos']['quotation'] }}</span>
                                    @endif
                                </button>
                            @endif

                            {{-- Hidden express triggers for popup --}}
                            <div class="hide vspos-op-pay-extra">
                                @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                    @if (!empty($payment_types) && array_key_exists('cash', $payment_types))
                                        <button type="button"
                                            class="vspos-pay-quick-btn vspos-pay-quick--cash pos-express-finalize @if(!empty($pos_settings['disable_express_checkout']) && $pos_settings['disable_express_checkout'] != 0) hide @endif"
                                            data-pay_method="cash"></button>
                                    @endif
                                    @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                        @if (!empty($payment_types) && array_key_exists('card', $payment_types))
                                            <button type="button"
                                                class="vspos-pay-quick-btn vspos-pay-quick--card pos-express-finalize"
                                                data-pay_method="card"></button>
                                        @endif
                                    @endif
                                @endif
                                @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                    @if (empty($pos_settings['disable_credit_sale_button']))
                                        <button type="button"
                                            class="vspos-pay-quick-btn vspos-pay-quick--credit pos-express-finalize"
                                            data-pay_method="credit_sale"></button>
                                    @endif
                                @endif
                                @if (!empty($payment_types) && array_key_exists('bank_transfer', $payment_types))
                                    <button type="button"
                                        class="vspos-pay-quick-btn vspos-pay-quick--bank pos-express-finalize"
                                        data-pay_method="bank_transfer"></button>
                                @endif
                                @if (!empty($payment_types))
                                    @foreach ($payment_types as $pay_key => $pay_label)
                                        @if (strpos($pay_key, 'custom_pay_') === 0)
                                            <button type="button"
                                                class="vspos-pay-quick-btn vspos-pay-quick--{{ $pay_key }} pos-express-finalize"
                                                data-pay_method="{{ $pay_key }}"></button>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Secondary actions --}}
                        <div class="vspos-op-secondary">
                            <div class="vspos-op-sec-group">
                            @if (empty($edit))
                                <button type="button" class="vspos-op-sec-btn vspos-op-sec--cancel js-pos-cancel">
                                    <i class="fi fi-rr-cross-circle pos-fi-icon"></i> Cancel @if(!empty($shortcuts['pos']['cancel']))<span class="keycap">{{ $shortcuts['pos']['cancel'] }}</span>@endif
                                </button>
                            @else
                                <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$transaction->id]) }}"
                                    class="vspos-op-sec-btn vspos-op-sec--cancel delete-sale"
                                    @if (!empty($only_payment)) onclick="return false;" @endif
                                    title="@lang('messages.delete')">
                                    <i class="fi fi-rr-trash pos-fi-icon"></i> @lang('messages.delete')
                                </a>
                            @endif
                            @if (!Gate::check('disable_draft') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                <button type="button" class="vspos-op-sec-btn vspos-op-sec--draft @if(!empty($pos_settings['disable_draft']) && $pos_settings['disable_draft'] != 0) hide @endif" id="pos-draft">
                                    <i class="fi fi-rr-disk pos-fi-icon"></i> Draft @if(!empty($shortcuts['pos']['draft']))<span class="keycap">{{ $shortcuts['pos']['draft'] }}</span>@endif
                                </button>
                            @endif
                            @if (!Gate::check('disable_suspend_sale') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                                @if (empty($pos_settings['disable_suspend']))
                                    <button type="button" class="vspos-op-sec-btn vspos-op-sec--suspend pos-express-finalize" data-pay_method="suspend">
                                        <i class="fi fi-rr-pause pos-fi-icon"></i> Suspend @if(!empty($shortcuts['pos']['suspend']))<span class="keycap">{{ $shortcuts['pos']['suspend'] }}</span>@endif
                                    </button>
                                @endif
                            @endif
                            </div>
                            <div class="vspos-op-sec-group vspos-op-sec-group--drawer">
                            <button type="button" class="vspos-op-sec-btn vspos-op-sec--cashin"
                                data-toggle="modal" data-target="#cash_in_out_modal" data-type="cash_in"
                                title="Add cash to drawer">
                                <i class="fi fi-rr-plus pos-fi-icon"></i> Cash In
                            </button>
                            <button type="button" class="vspos-op-sec-btn vspos-op-sec--cashout"
                                data-toggle="modal" data-target="#cash_in_out_modal" data-type="cash_out"
                                title="Remove cash from drawer">
                                <i class="fi fi-rr-minus pos-fi-icon"></i> Cash Out
                            </button>
                            <button type="button" class="vspos-op-sec-btn vspos-op-sec--supplier"
                                id="pos_supplier_pay_btn"
                                title="@lang('lang_v1.pay_supplier')">
                                <i class="fi fi-rr-money-transfer pos-fi-icon"></i> Pay Supplier @if(!empty($shortcuts['pos']['pay_supplier']))<span class="keycap">{{ $shortcuts['pos']['pay_supplier'] }}</span>@endif
                            </button>
                            </div>
                        </div>

                        </div>{{-- end vspos-order-panel-body --}}

                        {{-- Cash In / Out Modal --}}
                        <div class="modal fade vspos-pos-modal" id="cash_in_out_modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm" role="document">
                                <div class="modal-content vspos-pos-modal-content">
                                    <div class="modal-header vspos-pos-modal-header" id="cash_in_out_header">
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        <h4 class="modal-title" id="cash_in_out_title">Cash In</h4>
                                    </div>
                                    <div class="modal-body vspos-pos-modal-body">
                                        <div class="form-group">
                                            <label>@lang('sale.amount') <span class="text-danger">*</span></label>
                                            <input type="number" id="cash_io_amount" class="form-control input-lg" placeholder="0.00" min="0.01" step="0.01">
                                        </div>
                                        <div class="form-group">
                                            <label>@lang('cash_register.closing_note')</label>
                                            <input type="text" id="cash_io_note" class="form-control" placeholder="Reason / Note (optional)">
                                        </div>
                                    </div>
                                    <div class="modal-footer vspos-pos-modal-footer">
                                        <button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.cancel')</button>
                                        <button type="button" class="btn btn-success" id="cash_io_submit">
                                            <i class="fas fa-check"></i> <span id="cash_io_submit_label">Save</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                            $__pos_modules = $enabled_modules ?? session('business.enabled_modules', []);
                            if (! is_array($__pos_modules)) {
                                $__pos_modules = [];
                            }
                            $__pos_common = session('business.common_settings', []);
                            if (is_string($__pos_common)) {
                                $__pos_common = json_decode($__pos_common, true) ?: [];
                            }
                            $__show_reload_modal = in_array('reload', $__pos_modules, true)
                                || ! empty(($__pos_common['reload']['myreload_enabled'] ?? null));
                        @endphp
                        @if($__show_reload_modal)
                            @include('sale_pos.partials.mobile_reload_modal')
                        @endif
                        @if(in_array('mobile_shop', $__pos_modules, true))
                            @include('sale_pos.partials.mobile_repair_payment_modal')
                        @endif
                        @if(in_array('communication', $__pos_modules, true) || in_array('communication', session('business.enabled_modules', []), true))
                            @include('sale_pos.partials.communication_modal')
                        @endif

                    </div>{{-- end right panel --}}

                </div>
                @endif

                @include('sale_pos.partials.mobile_pay_sheet')
                @if (empty($is_pop))
                @include('sale_pos.partials.sale_complete_modal')
                @endif

                <style>
                /* ===== VsoftPOS layout only — colors live in pos-theme.css ===== */
                .vspos-main-layout {
                    display: flex;
                    flex-direction: row;
                    align-items: stretch;
                    gap: 10px;
                    padding: 10px;
                    min-height: calc(100vh - 120px);
                }
                @media (min-width: 992px) and (max-width: 1680px) {
                    .vspos-main-layout {
                        gap: 8px;
                        padding: 6px 8px;
                        min-height: calc(100vh - 56px);
                        height: calc(100vh - 56px);
                        max-height: calc(100vh - 56px);
                        overflow: hidden;
                        box-sizing: border-box;
                    }
                    .vspos-cart-panel,
                    .vspos-cart-inner {
                        min-height: 0;
                        height: 100%;
                        max-height: 100%;
                    }
                    .vspos-order-panel {
                        width: 268px;
                        height: 100%;
                        max-height: 100%;
                    }
                }
                @media (min-width: 992px) and (max-width: 1440px) {
                    .vspos-order-panel { width: 252px; }
                }
                @media (min-width: 992px) and (max-height: 820px) {
                    .vspos-main-layout {
                        min-height: calc(100vh - 48px);
                        height: calc(100vh - 48px);
                        max-height: calc(100vh - 48px);
                        padding: 4px 6px;
                        gap: 6px;
                    }
                }
                .vspos-cart-panel {
                    flex: 1 1 0;
                    min-width: 0;
                    display: flex;
                    flex-direction: column;
                }
                .vspos-cart-inner {
                    border-radius: 16px;
                    overflow: hidden;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                }
                .vspos-cart-inner .vspos-summary-bar { display: none !important; }
                .vspos-order-panel {
                    width: 300px;
                    flex-shrink: 0;
                    display: flex;
                    flex-direction: column;
                    border-radius: 18px;
                    overflow: hidden;
                    margin-bottom: 0;
                    max-height: calc(100vh - 24px);
                    overflow-y: auto;
                }
                @media (max-width: 991px) {
                    .vspos-main-layout {
                        flex-direction: column;
                        padding: 6px 6px 0;
                        gap: 0;
                        min-height: unset;
                        padding-bottom: 76px;
                        transition: padding-bottom 0.25s ease;
                    }
                    .vspos-main-layout.vspos-sheet-open {
                        padding-bottom: min(85vh, 560px);
                    }
                    .vspos-cart-panel { width: 100%; }
                    .vspos-cart-inner { border-radius: 12px; height: auto; }
                    .vspos-order-panel {
                        position: fixed;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        width: 100%;
                        z-index: 1040;
                        border-radius: 20px 20px 0 0;
                        border: none;
                        overflow: hidden;
                        -webkit-overflow-scrolling: touch;
                        transition: max-height 0.28s ease;
                    }
                    .vspos-order-panel--collapsed {
                        max-height: none;
                    }
                    .vspos-order-panel:not(.vspos-order-panel--collapsed) {
                        max-height: min(85vh, 560px);
                    }
                    .vspos-order-panel--collapsed .vspos-order-panel-body {
                        display: none !important;
                    }
                    .vspos-order-panel:not(.vspos-order-panel--collapsed) .vspos-mobile-strip {
                        display: none !important;
                    }
                    .vspos-order-panel-body {
                        display: flex;
                        flex-direction: column;
                        max-height: min(85vh, 560px);
                        overflow-y: auto;
                    }
                    .vspos-mobile-strip {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        width: 100%;
                        padding: 10px 12px 12px;
                        margin: 0;
                        border: none;
                        text-align: left;
                    }
                    .vspos-mobile-strip-details {
                        flex-shrink: 0;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 36px;
                        height: 36px;
                        padding: 0;
                        margin: 0;
                        border: none;
                        border-radius: 10px;
                        cursor: pointer;
                    }
                    .vspos-mobile-strip-pay {
                        flex-shrink: 0;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 10px 14px;
                        margin: 0;
                        border: none;
                        border-radius: 12px;
                        font-size: 13px;
                        font-weight: 800;
                        cursor: pointer;
                        white-space: nowrap;
                    }
                    .vspos-mobile-strip-pay .keycap {
                        font-size: 9px;
                        padding: 2px 5px;
                        border-radius: 4px;
                        font-weight: 800;
                    }
                    .vspos-mobile-strip-total {
                        flex: 1;
                        min-width: 0;
                        display: flex;
                        flex-direction: column;
                        gap: 2px;
                    }
                    .vspos-mobile-strip-label {
                        font-size: 9px;
                        font-weight: 800;
                        letter-spacing: 0.08em;
                        text-transform: uppercase;
                    }
                    .vspos-mobile-strip-value {
                        font-size: 20px;
                        font-weight: 900;
                        line-height: 1.1;
                        font-variant-numeric: tabular-nums;
                    }
                    .vspos-op-summary { padding: 6px 12px 4px; }
                    .vspos-stat-card { min-height: 54px; padding: 8px 10px; }
                    .vspos-stat-value { font-size: 13px; }
                    .vspos-total-hero-value { font-size: 20px; }
                    .vspos-op-pay-section { padding: 0 10px 8px; }
                    .vspos-op-pay-btns { grid-template-columns: 1fr 1fr; gap: 6px; }
                    .vspos-pay-quick-btn { padding: 10px 6px; font-size: 11px; }
                    .vspos-pay-quick-btn i { font-size: 16px; }
                    .vspos-op-secondary { padding: 4px 10px 10px; gap: 5px; }
                    .vspos-op-sec-btn { padding: 8px 6px; font-size: 11px; }
                    .vspos-last-bill-bar { padding: 7px 12px; }
                    .vspos-lb-invoice { max-width: 80px; }
                    .vspos-op-header { padding: 8px 14px; font-size: 12px; }
                    .vspos-cart-inner .form-control { font-size: 16px !important; }
                    .vspos-cart-inner .input-group-addon,
                    .vspos-cart-inner .input-group-btn .btn { min-height: 40px; }
                    #pos_table_div, .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                    body:not(.vspos-touch-ui) #pos_table { min-width: 480px; }
                    body.vspos-touch-ui #pos_table.vspos-ticket-cart { min-width: 0 !important; overflow: hidden; }
                    #pos_table th, #pos_table td { font-size: 11px; padding: 4px 5px; }
                }
                @media (max-width: 575px) {
                    .vspos-main-layout { padding: 4px 4px 0; padding-bottom: 76px; }
                    .vspos-main-layout.vspos-sheet-open { padding-bottom: min(88vh, 580px); }
                    .vspos-op-pay-btns { grid-template-columns: 1fr 1fr; }
                    .vspos-pay-quick-btn { padding: 9px 4px; }
                }
                @media (min-width: 992px) {
                    .vspos-mobile-strip { display: none !important; }
                    .vspos-order-panel--collapsed .vspos-order-panel-body {
                        display: flex !important;
                    }
                    .vspos-mobile-handle { display: none !important; }
                }
                .vspos-mobile-handle {
                    display: none;
                    justify-content: center;
                    padding: 10px 0 6px;
                    cursor: pointer;
                    touch-action: manipulation;
                }
                @media (max-width: 991px) {
                    .vspos-order-panel:not(.vspos-order-panel--collapsed) .vspos-mobile-handle {
                        display: flex;
                    }
                }
                .vspos-handle-bar {
                    width: 40px;
                    height: 4px;
                    border-radius: 99px;
                    display: block;
                }
                .vspos-last-bill-bar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                }
                .vspos-lb-info {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                    min-width: 0;
                }
                .vspos-lb-meta {
                    display: flex;
                    align-items: baseline;
                    gap: 8px;
                    min-width: 0;
                }
                .vspos-lb-kicker,
                .vspos-lb-label {
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .vspos-lb-invoice {
                    font-size: 11px;
                    font-weight: 600;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 110px;
                }
                .vspos-lb-amount {
                    font-size: 13px;
                    font-weight: 800;
                    white-space: nowrap;
                }
                .vspos-lb-actions {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    flex-shrink: 0;
                }
                .vspos-lb-print-btn {
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 5px 12px;
                    border-radius: 999px;
                    font-size: 11px;
                    font-weight: 700;
                    text-decoration: none !important;
                    border: none;
                    cursor: pointer;
                }
                .vspos-lb-recent-btn {
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 30px;
                    height: 30px;
                    border: none;
                    border-radius: 50%;
                    font-size: 13px;
                    cursor: pointer;
                }
                .vspos-op-header {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px 14px 8px;
                    font-size: 12px;
                    font-weight: 800;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                }
                .vspos-op-secondary {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    padding: 5px 10px 8px;
                }
                .vspos-op-sec-group {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                }
                .vspos-op-sec-btn {
                    flex: 1;
                    padding: 6px 6px;
                    border-radius: 8px;
                    border: 1px solid;
                    font-size: 10px;
                    font-weight: 700;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 4px;
                    transition: transform 0.2s ease;
                    white-space: nowrap;
                }
                .vspos-op-sec-btn:hover { transform: translateY(-1px); }
                .vspos-op-sec-btn .keycap {
                    display: inline-block;
                    background: rgba(0,0,0,0.06);
                    font-size: 8px;
                    font-weight: 700;
                    padding: 1px 4px;
                    border-radius: 3px;
                    letter-spacing: 0.4px;
                    text-transform: uppercase;
                    margin-left: 2px;
                    border: 1px solid rgba(0,0,0,0.08);
                }
                </style>
            </div>
        </div>
        {!! Form::close() !!}
    </section>

    <div class="modal fade vspos-pos-modal contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    @include('sale_pos.partials.loyalty_customer_modal')
    <!-- /.content -->
    <div class="modal fade vspos-pos-modal register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade vspos-pos-modal close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- quick product modal -->
    <div class="modal fade vspos-pos-modal quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade vspos-pos-modal" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade vspos-pos-modal" id="pos_pay_contact_due_modal" tabindex="-1" role="dialog">
    </div>

    {{-- Customer sales history popup --}}
    <div class="modal fade vspos-pos-modal" id="vspos_customer_history_modal" tabindex="-1" role="dialog" aria-labelledby="vsposCustHistLabel">
        <div class="modal-dialog vspos-cust-hist-dialog" role="document">
            <div class="modal-content vspos-pos-modal-content vspos-cust-hist-modal-content">
                <div class="modal-header vspos-pos-modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title" id="vsposCustHistLabel">
                        <i class="fi fi-rr-time-past pos-fi-icon"></i>
                        @lang('lang_v1.customer_sales_history')
                        <span class="vspos-cust-hist-name" id="vspos_cust_hist_name"></span>
                    </h4>
                </div>
                <div class="modal-body vspos-pos-modal-body vspos-cust-hist-modal-body" aria-live="polite">
                    @if(session('business.enable_rp') == 1)
                    <div class="vspos-cust-hist-modal-loy">
                        <i class="fa fa-gift"></i>
                        <span>{{ session('business.rp_name') ?: __('lang_v1.loyalty') }}</span>
                        <strong><span id="vspos_cust_hist_pts_modal">0</span> pts</strong>
                    </div>
                    @endif
                    <div class="vspos-cust-hist-list" id="vspos_cust_hist_list">
                        <div class="vspos-cust-hist-empty">@lang('lang_v1.select_customer_for_history')</div>
                    </div>
                    <div class="vspos-cust-hist-foot hide" id="vspos_cust_hist_foot">
                        <span id="vspos_cust_hist_count"></span>
                    </div>
                </div>
                <div class="modal-footer vspos-pos-modal-footer">
                    <button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Select Supplier (Pay Supplier) --}}
    <div class="modal fade vspos-pos-modal" id="pos_select_supplier_modal" tabindex="-1" role="dialog" aria-labelledby="posSelectSupplierLabel">
        <div class="modal-dialog modal-sm vspos-supplier-select-dialog" role="document">
            <div class="modal-content vspos-pos-modal-content vspos-supplier-select-content">
                <div class="modal-header vspos-pos-modal-header vspos-supplier-select-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title" id="posSelectSupplierLabel">
                        <i class="fi fi-rr-user pos-fi-icon"></i> @lang('lang_v1.select_supplier')
                    </h4>
                </div>
                <div class="modal-body vspos-pos-modal-body vspos-supplier-select-body">
                    <div class="form-group vspos-supplier-select-field">
                        <label for="pos_supplier_select" class="vspos-supplier-select-label">@lang('lang_v1.search_by_name_or_phone')</label>
                        <select id="pos_supplier_select" class="form-control" style="width:100%"></select>
                    </div>
                </div>
                <div class="modal-footer vspos-pos-modal-footer vspos-supplier-select-footer">
                    <button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="button" class="btn btn-success vspos-supplier-pay-confirm" id="pos_supplier_pay_confirm">
                        <i class="fi fi-rr-money-transfer pos-fi-icon"></i> @lang('lang_v1.pay_now')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bill Discount Modal --}}
    <div class="modal fade vspos-pos-modal vspos-bd-modal" id="billDiscountModal" tabindex="-1" role="dialog" aria-labelledby="billDiscountModalLabel">
        <div class="modal-dialog modal-sm vspos-bd-modal-dialog" role="document">
            <div class="modal-content vspos-pos-modal-content vspos-bd-modal-content">
                <div class="modal-header vspos-pos-modal-header vspos-bd-modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title" id="billDiscountModalLabel">
                        <i class="fas fa-tag pos-fi-icon"></i> Bill Discount
                    </h4>
                </div>
                <div class="modal-body vspos-pos-modal-body vspos-bd-modal-body">
                    <div class="form-group vspos-bd-field">
                        <label for="bd_modal_type" class="vspos-bd-label">Discount Type</label>
                        <select id="bd_modal_type" class="form-control vspos-bd-input">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="form-group vspos-bd-field vspos-bd-field--amount">
                        <label for="bd_modal_amount" class="vspos-bd-label">Discount Amount</label>
                        <input type="number" id="bd_modal_amount" class="form-control input_number vspos-bd-input vspos-bd-amount-input"
                            placeholder="0.00" min="0" step="any">
                    </div>
                </div>
                <div class="modal-footer vspos-pos-modal-footer vspos-bd-modal-footer">
                    <button type="button" id="bd_modal_remove" class="btn vspos-bd-btn-remove">Remove</button>
                    <button type="button" id="bd_modal_apply" class="btn vspos-bd-btn-apply">Apply Discount</button>
                </div>
            </div>
        </div>
    </div>

    @include('sale_pos.partials.configure_search_modal')

    @include('sale_pos.partials.bulk_product_search_modal')

    @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
    @include('sale_pos.partials.tyre_shop_services_modal')
    @include('sale_pos.partials.trade_in_modal')
    @endif
    @if(function_exists('vehicle_modification_enabled') && vehicle_modification_enabled())
    @include('sale_pos.partials.workshop_services_modal')
    @include('sale_pos.partials.vehicle_history_modal')
    @endif

    {{-- POP classic: qty/cost modal lives in touch_main for touch UI; include once here otherwise --}}
    @if (!empty($is_pop) && ($pos_ui ?? '') !== 'touch')
        @include('sale_pos.partials.touch_qty_modal')
    @endif

    @include('sale_pos.partials.recent_transactions_modal')

    @include('sale_pos.partials.weighing_scale_modal')

    @if(empty($is_pop) && function_exists('nutmeg_enabled') && nutmeg_enabled())
        @include('sale_pos.partials.nutmeg_bag_modal')
    @endif

    {{-- Barcode Camera Scanner Modal --}}
    <div class="modal fade vspos-pos-modal" id="barcodeScannerModal" tabindex="-1" role="dialog" aria-labelledby="barcodeScannerModalLabel">
        <div class="modal-dialog modal-sm" role="document" style="margin-top:60px;">
            <div class="modal-content vspos-pos-modal-content">
                <div class="modal-header vspos-pos-modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="fas fa-barcode"></i> Scan Barcode
                    </h4>
                </div>
                <div class="modal-body vspos-pos-modal-body" style="padding:0;">
                    <div id="barcode-scanner-region" style="width:100%;min-height:240px;background:#000;"></div>
                    <div id="barcode-scanner-status" class="vspos-barcode-scanner-status" style="padding:10px 14px;text-align:center;font-size:12px;">
                        Point camera at barcode...
                    </div>
                </div>
                <div class="modal-footer vspos-pos-modal-footer" style="display:flex;gap:8px;justify-content:stretch;">
                    <button type="button" id="barcode_scanner_stop" class="btn" data-dismiss="modal" style="flex:1;">Close</button>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($is_pop) && function_exists('nutmeg_enabled') && nutmeg_enabled())
    <div class="modal fade" id="pop_product_expenses_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">@lang('lang_v1.product_expenses')</h4>
                </div>
                <div class="modal-body">
                    <p class="help-block"><i>@lang('lang_v1.product_expenses_help')</i></p>
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.product_expense_name')</th>
                                <th style="width:120px;">@lang('lang_v1.product_expense_amount')</th>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="pop_pe_modal_tbody"></tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary" id="pop_pe_add_row">
                        <i class="fa fa-plus"></i> @lang('lang_v1.add_product_expense')
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="button" class="btn btn-primary" id="pop_pe_save">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
    @endif

@stop
@section('css')
    @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch' || !empty($is_pop))
        <link rel="stylesheet" href="{{ asset('css/pos-touch.css?v=' . ($asset_v ?? config('constants.asset_version'))) }}">
        @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch')
        <link rel="stylesheet" href="{{ asset('css/pos-mobile-retail.css?v=' . ($asset_v ?? config('constants.asset_version'))) }}">
        @endif
        @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch')
            <script>document.documentElement.classList.add('vspos-touch-pending');</script>
        @endif
    @endif
    <style type="text/css">
        .overlay {
            background: rgba(255,255,255,0) !important;
            cursor: not-allowed;
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 50;
        }
        .vspos-cart-inner { position: relative; }
    </style>
    <!-- include module css -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop
@section('javascript')
    <script>
        var __stock_price_management = {{ in_array('stock_price_management', $enabled_modules) ? 'true' : 'false' }};
        var __mobile_shop_enabled = {{ in_array('mobile_shop', $enabled_modules ?? session('business.enabled_modules', [])) ? 'true' : 'false' }};
        var __communication_enabled = {{ in_array('communication', $enabled_modules ?? session('business.enabled_modules', []), true) ? 'true' : 'false' }};
        window.__communication_enabled = __communication_enabled;
        var vspos_mobile_shop_imei_url = '{{ action([\App\Http\Controllers\MobileShopController::class, 'availableImeis']) }}';
        var vspos_mobile_repair_pending_url = '{{ action([\App\Http\Controllers\MobileShopController::class, 'repairPendingPayments']) }}';
        var vspos_mobile_repair_checkout_url = '{{ url('mobile-shop/repairs') }}';
        var vspos_mobile_bill_history_url = '{{ action([\App\Http\Controllers\MobileShopController::class, 'billHistory']) }}';
        var vspos_communication_options_url = '{{ action([\App\Http\Controllers\CommunicationController::class, 'posOptions']) }}';
        window.vspos_communication_options_url = vspos_communication_options_url;
        @if(!empty($mobile_repair_checkout))
        window.__mobile_repair_checkout = @json($mobile_repair_checkout);
        @endif
        @if(!empty($workshop_job_checkout))
        window.__workshop_job_checkout = @json($workshop_job_checkout);
        @endif
    </script>
    {{-- Html5Qrcode for mobile camera barcode scanning --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script src="{{ asset('js/pos-receipt-return.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-stock-alert.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-loyalty.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-quick-entry.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-bulk-search.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-live-search.js?v=' . $asset_v) }}"></script>
    @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
    <script src="{{ asset('js/pos-tyre-services.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-trade-in.js?v=' . $asset_v) }}"></script>
    @endif
    @php
        $__enabled_mods = $enabled_modules ?? session('business.enabled_modules', []);
        if (! is_array($__enabled_mods)) {
            $__enabled_mods = [];
        }
        $__load_mobile_reload_js = in_array('reload', $__enabled_mods, true)
            || in_array('mobile_shop', $__enabled_mods, true)
            || ! empty((session('business.common_settings')['reload']['myreload_enabled'] ?? null));
    @endphp
    @if($__load_mobile_reload_js)
        <script src="{{ asset('js/mobile-bill-payments.js?v=' . $asset_v) }}"></script>
    @endif
    @if(in_array('mobile_shop', $__enabled_mods, true))
        <script src="{{ asset('js/pos-mobile-shop.js?v=' . $asset_v) }}"></script>
    @endif
    @if(function_exists('vehicle_modification_enabled') && vehicle_modification_enabled())
        <script src="{{ asset('js/pos-workshop.js?v=' . $asset_v) }}"></script>
        @if(auth()->check() && (auth()->user()->can('vehicle_modification.view_history') || (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())))
        <script>
            window.__vehicle_history_urls = {
                search: @json(url('/vehicle-modification/history/search')),
                vehicle: @json(url('/vehicle-modification/history/vehicle') . '/'),
                job: @json(url('/vehicle-modification/history/job') . '/'),
                sale_url: @json(url('/sells')),
                reg_placeholder: @json(__('lang_v1.vehicle_history_reg_placeholder')),
                phone_placeholder: @json(__('lang_v1.vehicle_history_phone_placeholder')),
                no_vehicles: @json(__('lang_v1.vehicle_history_no_vehicles')),
                no_jobs: @json(__('lang_v1.vehicle_history_no_jobs')),
                enter_search: @json(__('lang_v1.vehicle_history_enter_search')),
                error: @json(__('messages.something_went_wrong')),
                denied: @json(__('lang_v1.vehicle_history_permission_denied')),
                jobs_label: @json(__('lang_v1.workshop_jobs')),
                customer: @json(__('contact.customer')),
                vehicle_label: @json(__('lang_v1.vehicle')),
                invoice: @json(__('sale.invoice_no')),
                description: @json(__('lang_v1.job_description')),
                notes: @json(__('lang_v1.notes') !== 'lang_v1.notes' ? __('lang_v1.notes') : 'Notes'),
                total: @json(__('sale.total')),
                item: @json(__('sale.product')),
                type: @json(__('lang_v1.item_type')),
                price: @json(__('sale.unit_price')),
                view_sale: @json(__('lang_v1.view_sale') !== 'lang_v1.view_sale' ? __('lang_v1.view_sale') : 'View sale'),
                vehicle_applied: @json(__('lang_v1.vehicle_applied_to_pos'))
            };
        </script>
        <script src="{{ asset('js/pos-vehicle-history.js?v=' . $asset_v) }}"></script>
        @endif
    @endif
    @if(in_array('communication', $__enabled_mods, true) || (($pos_ui ?? '') === 'touch'))
        <script src="{{ asset('js/pos-communication.js?v=' . $asset_v) }}"></script>
    @endif
    @if(!empty($is_pop) && function_exists('nutmeg_enabled') && nutmeg_enabled())
        <script src="{{ asset('js/pos-nutmeg-expenses.js?v=' . $asset_v) }}"></script>
    @endif
    @if(empty($is_pop) && function_exists('nutmeg_enabled') && nutmeg_enabled())
        <script>
            window.__nutmeg_pos_bags_url = @json(url('/nutmeg/pos-bags'));
        </script>
        <script src="{{ asset('js/pos-nutmeg-bags.js?v=' . $asset_v) }}"></script>
    @endif
    @if(($pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic')) === 'touch' || !empty($is_pop))
        <script src="{{ asset('js/pos-touch.js?v=' . $asset_v) }}"></script>
    @endif
    <script src="{{ asset('js/pos-vfd-display.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->
    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
    @include('sale_pos.partials.pos_layout_script', ['form_id' => !empty($edit) ? 'edit_pos_sell_form' : 'add_pos_sell_form'])
    <script>
    (function() {
        // Mirror totals into right panel — simple poll every 300ms, no MutationObserver loops
        var mirrorMap = [
            ['.total_quantity',  '.total_quantity_mirror'],
            ['.price_total',     '.price_total_mirror'],
            ['#total_discount',  '.total_discount_mirror'],
            ['#order_tax',       '.total_tax_mirror'],
            ['#total_payable',   '.grand_total_mirror'],
            ['#shipping_charges_amount', '.shipping_charges_mirror'],
        ];
        function mirrorTotals() {
            mirrorMap.forEach(function(pair) {
                var src = document.querySelector(pair[0]);
                if (!src) return;
                var val = src.textContent.trim();
                document.querySelectorAll(pair[1]).forEach(function(dst) {
                    dst.textContent = val;
                });
            });
        }
        // Poll frequently + hook into the custom event fired by pos.js after each total calc
        setInterval(mirrorTotals, 300);
        $(document).on('invoice_total_calculated', mirrorTotals);

        /* ── Mobile order panel: collapsed by default ── */
        (function () {
            var panel = document.getElementById('vspos_order_panel');
            var strip = document.getElementById('vspos_mobile_strip');
            var handle = document.getElementById('vspos_mobile_handle');
            var layout = document.querySelector('.vspos-main-layout');
            var mq = window.matchMedia('(max-width: 991px)');

            function isMobile() {
                return mq.matches;
            }

            function expandSheet() {
                if (!panel || !isMobile()) return;
                panel.classList.remove('vspos-order-panel--collapsed');
                if (strip) strip.setAttribute('aria-expanded', 'true');
                if (layout) layout.classList.add('vspos-sheet-open');
            }

            function collapseSheet() {
                if (!panel || !isMobile()) return;
                panel.classList.add('vspos-order-panel--collapsed');
                if (strip) strip.setAttribute('aria-expanded', 'false');
                if (layout) layout.classList.remove('vspos-sheet-open');
            }

            function toggleSheet() {
                if (!panel || !isMobile()) return;
                if (panel.classList.contains('vspos-order-panel--collapsed')) {
                    expandSheet();
                } else {
                    collapseSheet();
                }
            }

            function syncSheetForViewport() {
                if (!panel) return;
                if (!isMobile()) {
                    panel.classList.remove('vspos-order-panel--collapsed');
                    if (layout) layout.classList.remove('vspos-sheet-open');
                    if (strip) strip.setAttribute('aria-expanded', 'true');
                    return;
                }
                if (!panel.classList.contains('vspos-sheet-open')) {
                    collapseSheet();
                }
            }

            var detailsBtn = document.getElementById('vspos_mobile_details_btn');
            if (detailsBtn) {
                detailsBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    expandSheet();
                });
            }
            document.addEventListener('vspos:expand-order-sheet', expandSheet);
            if (handle) {
                handle.addEventListener('click', collapseSheet);
                handle.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        collapseSheet();
                    }
                });
            }

            if (mq.addEventListener) {
                mq.addEventListener('change', syncSheetForViewport);
            } else if (mq.addListener) {
                mq.addListener(syncSheetForViewport);
            }
            window.addEventListener('resize', syncSheetForViewport);
            syncSheetForViewport();
        })();

        /* ── Pay popup (mobile + desktop) ── */
        (function () {
            var backdrop = document.getElementById('vspos_mobile_pay_backdrop');
            var popup = document.getElementById('vspos_mobile_pay_popup');
            var payBtn = document.getElementById('vspos_mobile_pay_btn');
            var closeBtn = document.getElementById('vspos_mobile_pay_close');
            var detailsBtn = document.getElementById('vspos_mobile_pay_details_btn');
            var amountInput = document.getElementById('vspos_pay_amount_input');
            var changePreview = document.getElementById('vspos_pay_change_preview');
            var cashFullPayBtn = document.getElementById('vspos_cash_full_pay_btn');
            var multiPayBtn = document.getElementById('vspos_pay_multipay_btn');
            var multipayBackBtn = document.getElementById('vspos_multipay_back');
            var multipayCloseBtn = document.getElementById('vspos_multipay_close');
            var multipayAddRowBtn = document.getElementById('vspos_multipay_add_row');
            var multipayFinalizeBtn = document.getElementById('vspos_multipay_finalize');
            var payViewMain = document.getElementById('vspos_pay_view_main');
            var payViewMultipay = document.getElementById('vspos_pay_view_multipay');
            var touchPayClearBtn = document.getElementById('vspos_touch_pay_clear');
            var paymentRowsHome = null;
            var changeReturnHome = null;
            var multipayViewActive = false;
            var touchPayMode = !!document.getElementById('vspos_touch_pay');
            var touchAddPaymentBusy = false;
            var touchPayments = [];
            var touchPaymentsBaseline = [];
            var touchMethodLabels = {
                cash: 'Cash',
                card: 'Card',
                bank_transfer: 'Bank Transfer',
                cheque: 'Cheque',
                credit_sale: 'Credit'
            };
            var touchAccountsMap = null;

            function isTouchPayUi() {
                return touchPayMode || document.body.classList.contains('vspos-touch-ui');
            }

            function getTouchAccountsMap() {
                if (touchAccountsMap) return touchAccountsMap;
                touchAccountsMap = {};
                var el = document.getElementById('vspos_touch_pay_accounts_json');
                if (el && el.textContent) {
                    try {
                        touchAccountsMap = JSON.parse(el.textContent) || {};
                    } catch (err) {
                        touchAccountsMap = {};
                    }
                }
                return touchAccountsMap;
            }

            function getLocationDefaultPaymentAccounts() {
                function parseDefaults(raw) {
                    if (!raw) return null;
                    if (typeof raw === 'object') {
                        return raw;
                    }
                    if (typeof raw === 'string') {
                        var s = raw.trim();
                        if (!s || s === 'null' || s === 'undefined') return null;
                        try {
                            return JSON.parse(s);
                        } catch (err) {
                            return null;
                        }
                    }
                    return null;
                }

                var candidates = [];
                // Most reliable: defaults baked into Touch Pay container from server
                var $touchPay = $('#vspos_touch_pay');
                if ($touchPay.length) {
                    candidates.push($touchPay.attr('data-default-payment-accounts'));
                    candidates.push($touchPay.data('defaultPaymentAccounts'));
                    candidates.push($touchPay.data('default-payment-accounts'));
                }
                var $sel = $('select#select_location_id');
                if ($sel.length) {
                    var $opt = $sel.find(':selected');
                    candidates.push($opt.attr('data-default_payment_accounts'));
                    candidates.push($opt.data('default_payment_accounts'));
                }
                var $loc = $('#location_id');
                if ($loc.length) {
                    // Prefer attr(): jQuery .data() cache can be wiped by set_location()
                    candidates.push($loc.attr('data-default_payment_accounts'));
                    candidates.push($loc.data('default_payment_accounts'));
                }

                for (var i = 0; i < candidates.length; i++) {
                    var parsed = parseDefaults(candidates[i]);
                    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)
                        && Object.keys(parsed).length) {
                        return parsed;
                    }
                }
                return {};
            }

            function getDefaultAccountIdForMethod(method) {
                var defaults = getLocationDefaultPaymentAccounts();
                if (!method || !defaults[method]) return '';
                var acc = defaults[method].account;
                return (acc !== undefined && acc !== null && acc !== '') ? String(acc) : '';
            }

            function accountLabel(accountId) {
                if (accountId === undefined || accountId === null || accountId === '') return '';
                var map = getTouchAccountsMap();
                return map[accountId] || map[String(accountId)] || '';
            }

            function buildAccountOptionsHtml(selectedId) {
                var map = getTouchAccountsMap();
                var html = '';
                var sel = (selectedId === undefined || selectedId === null) ? '' : String(selectedId);
                Object.keys(map).forEach(function (id) {
                    html += '<option value="' + $('<div>').text(id).html() + '"'
                        + (String(id) === sel ? ' selected' : '') + '>'
                        + $('<div>').text(map[id]).html() + '</option>';
                });
                return html;
            }

            function touchAccountSelect() {
                return $('#vspos_touch_pay_account');
            }

            function updateTouchAccountHint(method) {
                var $hint = $('#vspos_touch_pay_account_hint');
                if (!$hint.length) return;
                var id = touchAccountSelect().val() || getDefaultAccountIdForMethod(method || 'cash');
                var label = accountLabel(id);
                if (label) {
                    $hint.text('Linked: ' + label + ' · change only if needed');
                } else if (id) {
                    $hint.text('Linked account #' + id + ' · change only if needed');
                } else {
                    $hint.text('No default account for this method · select one if required');
                }
            }

            function setTouchAccountSelect(accountId, method, manual) {
                var $sel = touchAccountSelect();
                if (!$sel.length) return;
                var val = (accountId !== undefined && accountId !== null && accountId !== '')
                    ? String(accountId)
                    : '';
                if (val) {
                    var $opt = $sel.find('option').filter(function () {
                        return String($(this).val()) === val;
                    });
                    if (!$opt.length) {
                        // Option missing from dropdown — still keep value so it posts
                        $sel.append($('<option>', { value: val, text: accountLabel(val) || ('Account #' + val) }));
                    }
                }
                $sel.val(val);
                if (method) $sel.attr('data-bound-method', method);
                if (manual) {
                    $sel.attr('data-manual', '1');
                } else {
                    $sel.removeAttr('data-manual');
                }
                updateTouchAccountHint(method || 'cash');
            }

            /** Resolve account for a method: keep manual override if set, else location default. */
            function resolveAccountForMethod(method) {
                var def = getDefaultAccountIdForMethod(method);
                var $sel = touchAccountSelect();
                if (!$sel.length) return def;
                if ($sel.attr('data-manual') === '1') {
                    var manualVal = String($sel.val() || '');
                    $sel.attr('data-bound-method', method || '');
                    // Manual "None" should not block location default
                    if (manualVal) return manualVal;
                }
                setTouchAccountSelect(def, method, false);
                return def;
            }

            function setPaymentRowAccount($row, accountId) {
                if (!$row || !$row.length) return;
                var $acc = $row.find('select.account-dropdown').first();
                if (!$acc.length) return;
                // Never wipe a default that method-change already applied
                if (accountId === undefined || accountId === null || accountId === '') {
                    return;
                }
                var val = String(accountId);
                if (!$acc.find('option').filter(function () { return String($(this).val()) === val; }).length) {
                    $acc.append($('<option>', { value: val, text: accountLabel(val) || ('Account #' + val) }));
                }
                $acc.val(val);
                try { $acc.trigger('change'); } catch (err) {}
            }

            function touchPaidTotal() {
                var sum = 0;
                for (var i = 0; i < touchPayments.length; i++) {
                    sum += Number(touchPayments[i].amount) || 0;
                }
                return sum;
            }

            function touchRemainingDue() {
                return Math.max(0, getTotalPayable() - touchPaidTotal());
            }

            function updateTouchMeters() {
                var paid = touchPaidTotal();
                var due = touchRemainingDue();
                var change = Math.max(0, paid - getTotalPayable());
                var $paid = $('#vspos_touch_pay_paid');
                var $due = $('#vspos_touch_pay_due');
                var $chg = $('#vspos_touch_pay_change');
                if ($paid.length) $paid.text(formatPayMoney(paid));
                if ($due.length) $due.text(formatPayMoney(due));
                if ($chg.length) $chg.text(formatPayMoney(change));
                if (changePreview) {
                    changePreview.textContent = formatPayMoney(Math.max(0, getEnteredAmount() - due));
                }
            }

            function ensurePaymentRowsHome() {
                var $rows = $('#payment_rows_div');
                if (!$rows.length) return;
                if (!paymentRowsHome) {
                    paymentRowsHome = {
                        parent: $rows.parent()[0],
                        next: $rows[0].nextSibling
                    };
                }
                var $change = $('#change_return_payment_data');
                if ($change.length && !changeReturnHome) {
                    changeReturnHome = {
                        parent: $change.parent()[0],
                        next: $change[0].nextSibling
                    };
                }
            }

            function restoreNode($node, home) {
                if (!$node.length || !home || !home.parent) return;
                if (home.next) {
                    home.parent.insertBefore($node[0], home.next);
                } else {
                    $(home.parent).append($node);
                }
            }

            function restorePaymentRows() {
                restoreNode($('#payment_rows_div'), paymentRowsHome);
                restoreNode($('#change_return_payment_data'), changeReturnHome);
                reinitSelect2InModal();
            }

            function reinitSelect2InPopup() {
                $('#vspos_mobile_pay_popup .select2').each(function () {
                    var $el = $(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({ dropdownParent: $('#vspos_mobile_pay_popup') });
                });
            }

            function reinitSelect2InModal() {
                $('#modal_payment .select2').each(function () {
                    var $el = $(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({ dropdownParent: $('#modal_payment') });
                });
            }

            function mountPaymentRowsInPopup() {
                ensurePaymentRowsHome();
                $('#payment_rows_div').appendTo('#vspos_multipay_rows_mount');
                $('#change_return_payment_data').appendTo('#vspos_multipay_rows_mount');
                reinitSelect2InPopup();
            }

            function syncMultipaySummary() {
                if (typeof calculate_balance_due === 'function') {
                    calculate_balance_due();
                }
                var total = getTotalPayable();
                if (typeof __currency_trans_from_en === 'function') {
                    $('#vspos_pay_view_multipay .vspos-mp-total').text(__currency_trans_from_en(total, true));
                }
                if (isTouchPayUi()) {
                    renderTouchPayList();
                }
            }

            function getTotalPayingAmt() {
                if (typeof __read_number === 'function' && $('#total_paying_input').length) {
                    return __read_number($('#total_paying_input')) || 0;
                }
                var sum = 0;
                $('#payment_rows_div .payment-amount').each(function () {
                    sum += (typeof __read_number === 'function' ? __read_number($(this)) : (parseFloat($(this).val()) || 0));
                });
                return sum;
            }

            function getRemainingDue() {
                return Math.max(0, getTotalPayable() - getTotalPayingAmt());
            }

            function formatPayMoney(n) {
                var val = Number(n) || 0;
                var formatted = val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                var sym = (typeof __currency_symbol !== 'undefined' && __currency_symbol) ? __currency_symbol : '';
                if (sym) {
                    if (typeof __currency_symbol_placement !== 'undefined' && __currency_symbol_placement === 'after') {
                        return formatted + ' ' + sym;
                    }
                    return sym + ' ' + formatted;
                }
                if (typeof __currency_trans_from_en === 'function') {
                    return String(__currency_trans_from_en(val, true)).replace(/<[^>]+>/g, '').trim();
                }
                return formatted;
            }

            function setAmountInput(val) {
                if (!amountInput) return;
                // Store plain number so keypad can replace cleanly (no "1,940.00" append)
                var n = Number(val);
                if (!isFinite(n) || n < 0) n = 0;
                amountInput.value = n > 0 ? String(n) : '';
                amountInput.setAttribute('data-replace-next', '1');
                if (typeof window.__vsposPayAmountMarkReplace === 'function') {
                    window.__vsposPayAmountMarkReplace();
                }
                updateChangePreview();
                setTimeout(function () {
                    try {
                        amountInput.focus();
                        amountInput.select();
                    } catch (err) {}
                }, 0);
            }

            function resetTouchPaymentRows() {
                var $rows = $('#payment_rows_div .payment_row');
                $rows.each(function (i) {
                    if (i > 0) $(this).remove();
                });
                var $first = $('#payment_rows_div .payment_row').first();
                if (!$first.length) return;
                var $amt = $first.find('.payment-amount').first();
                if (typeof __write_number === 'function') {
                    __write_number($amt, 0);
                } else {
                    $amt.val(0);
                }
                $amt.trigger('change');
                var $dd = $first.find('.payment_types_dropdown').first();
                if ($dd.length && $dd.find('option[value="cash"]').length) {
                    $dd.val('cash').trigger('change');
                }
                if (typeof calculate_balance_due === 'function') {
                    calculate_balance_due();
                }
            }

            /**
             * Load existing payment rows (edit invoice) into Touch Pay list.
             * Skips zero-amount placeholder rows used on create.
             */
            function readTouchPaymentsFromDom() {
                var list = [];
                paymentRowWrappers().each(function () {
                    var $wrap = $(this);
                    var $row = $wrap.hasClass('payment_row') ? $wrap : $wrap.find('.payment_row').first();
                    if (!$row.length) return;
                    if ($row.attr('id') === 'change_return_payment_data') return;

                    var $amt = $row.find('.payment-amount').first();
                    if (!$amt.length) return;
                    var amount = typeof __read_number === 'function'
                        ? (__read_number($amt) || 0)
                        : (parseFloat(String($amt.val()).replace(/,/g, '')) || 0);
                    if (!(amount > 0)) return;

                    var $dd = $row.find('.payment_types_dropdown').not(':disabled').first();
                    if (!$dd.length) {
                        $dd = $row.find('.payment_types_dropdown').first();
                    }
                    var method = $dd.length ? String($dd.val() || 'cash') : 'cash';
                    if (method === 'advance' && !(amount > 0)) return;

                    var label = touchMethodLabels[method] || method;
                    if ($dd.length) {
                        var optText = $.trim($dd.find('option:selected').text() || '');
                        if (optText) label = optText;
                    }

                    var $acc = $row.find('select.account-dropdown').first();
                    var accountId = $acc.length ? String($acc.val() || '') : '';
                    var $pid = $row.find('input[name*="[payment_id]"]').first();
                    var paymentId = $pid.length ? String($pid.val() || '') : '';

                    var entry = {
                        method: method,
                        label: label,
                        amount: amount,
                        account_id: accountId,
                        account_label: accountLabel(accountId),
                        payment_id: paymentId,
                        existing: !!paymentId
                    };

                    if (method === 'cheque') {
                        entry.cheque_name = $row.find('.cheque_name_input, input[name*="[cheque_name]"]').first().val() || '';
                        entry.cheque_number = $row.find('input[name*="[cheque_number]"]').first().val() || '';
                        entry.cheque_date = $row.find('.cheque_date_picker, input[name*="[cheque_date]"]').first().val() || '';
                        entry.cheque_bank_name = $row.find('select[name*="[cheque_bank_name]"], input[name*="[cheque_bank_name]"]').first().val() || '';
                    }

                    list.push(entry);
                });
                return list;
            }

            function cloneTouchPayments(list) {
                return (list || []).map(function (p) {
                    return $.extend(true, {}, p);
                });
            }

            function renderTouchPayList() {
                var $list = $('#vspos_touch_pay_list');
                if (!$list.length) return;
                var hasAccounts = !!Object.keys(getTouchAccountsMap()).length && touchAccountSelect().length;
                var html = '';
                for (var i = 0; i < touchPayments.length; i++) {
                    var p = touchPayments[i];
                    html += '<div class="vspos-touch-pay-line' + (p.existing ? ' vspos-touch-pay-line--existing' : '') + '" data-pay-index="' + i + '">';
                    html += '<div class="vspos-touch-pay-line-main">';
                    html += '<span class="vspos-touch-pay-line-method">' + $('<div>').text(p.label || p.method).html();
                    if (p.existing) {
                        html += ' <small class="vspos-touch-pay-existing-tag">Paid</small>';
                    }
                    html += '</span>';
                    html += '<span class="vspos-touch-pay-line-amt">' + formatPayMoney(p.amount) + '</span>';
                    html += '</div>';
                    if (p.method === 'cheque') {
                        var metaBits = [];
                        if (p.cheque_number) metaBits.push('#' + p.cheque_number);
                        if (p.cheque_name) metaBits.push(p.cheque_name);
                        if (p.cheque_bank_name) metaBits.push(p.cheque_bank_name);
                        if (p.cheque_date) metaBits.push(p.cheque_date);
                        if (metaBits.length) {
                            html += '<div class="vspos-touch-pay-line-meta">' + $('<div>').text(metaBits.join(' · ')).html() + '</div>';
                        }
                    }
                    if (hasAccounts) {
                        html += '<select class="vspos-touch-pay-line-account" data-pay-index="' + i + '" aria-label="Payment account">';
                        html += buildAccountOptionsHtml(p.account_id);
                        html += '</select>';
                    } else if (p.account_label) {
                        html += '<div class="vspos-touch-pay-line-account-label">' + $('<div>').text(p.account_label).html() + '</div>';
                    }
                    html += '<button type="button" class="vspos-touch-pay-line-remove" data-pay-index="' + i + '" aria-label="Remove">&times;</button>';
                    html += '</div>';
                }
                if (!html) {
                    html = '<div class="vspos-touch-pay-list-empty">No payments yet — enter amount &amp; tap a method</div>';
                }
                $list.html(html);
                var addAnother = document.getElementById('vspos_touch_add_another');
                if (addAnother) {
                    addAnother.classList.toggle('is-visible', touchPayments.length > 0);
                }
                updateTouchMeters();
                var $done = $('#vspos_multipay_finalize');
                if ($done.length) {
                    if (touchRemainingDue() < 0.5 && touchPayments.length) {
                        $done.addClass('is-ready').html('<i class="fi fi-rr-check pos-fi-icon"></i><span>Complete · Pay now</span>');
                    } else if (touchPayments.length && touchRemainingDue() >= 0.5) {
                        $done.removeClass('is-ready').html('<i class="fi fi-rr-check pos-fi-icon"></i><span>Complete · Due ' + formatPayMoney(touchRemainingDue()) + '</span>');
                    } else {
                        $done.removeClass('is-ready').html('<i class="fi fi-rr-check pos-fi-icon"></i><span>Complete</span>');
                    }
                }
            }

            function prepareTouchPayWorkspace() {
                if (!isTouchPayUi()) return;
                mountPaymentRowsInPopup();
                multipayViewActive = true;
                if (popup) popup.classList.add('vspos-mobile-pay-popup--multipay');

                // Keep real invoice payments on edit; ignore the default cash stub on new bills
                touchPayments = readTouchPaymentsFromDom();
                var hasExistingPaid = touchPayments.some(function (p) {
                    return !!p.payment_id || !!p.existing;
                });
                if (!hasExistingPaid) {
                    // POS/POP auto-fills cash = total on the hidden payment row — that is NOT paid yet
                    touchPayments = [];
                    $('#payment_rows_div .payment_row').each(function () {
                        var $row = $(this);
                        var $amt = $row.find('.payment-amount').first();
                        if (!$amt.length) return;
                        if (typeof __write_number === 'function') {
                            __write_number($amt, 0);
                        } else {
                            $amt.val(0);
                        }
                        try { $amt.trigger('change'); } catch (err) {}
                    });
                }

                touchPaymentsBaseline = cloneTouchPayments(touchPayments);

                setTouchAccountSelect(getDefaultAccountIdForMethod('cash'), 'cash', false);
                updateTouchAccountHint('cash');
                var due = touchRemainingDue();
                setAmountInput(due > 0 ? due : (touchPayments.length ? 0 : getTotalPayable()));
                renderTouchPayList();
            }

            function fillPaymentRow($row, method, amount, accountId, extra) {
                if (!$row || !$row.length) return;
                var $amt = $row.find('.payment-amount').first();
                var $dd = $row.find('.payment_types_dropdown').first();
                if ($dd.length) {
                    // Ensure option exists (location filter may have hidden it, not removed)
                    if (!$dd.find('option[value="' + method + '"]').length) {
                        $dd.append($('<option/>', { value: method, text: (touchMethodLabels[method] || method) }));
                    }
                    $dd.find('option[value="' + method + '"]').prop('disabled', false).removeClass('hide');
                    // Allow cheque sync even for Walk-In on Sell if we already collected cheque details
                    var prevAllow = window.__vspos_allow_walkin_cheque;
                    if (method === 'cheque') {
                        window.__vspos_allow_walkin_cheque = true;
                    }
                    $dd.val(method);
                    try { $dd.trigger('change'); } catch (err) {}
                    // Keep allow flag until after detail fields written
                    if (method !== 'cheque') {
                        window.__vspos_allow_walkin_cheque = prevAllow;
                    }
                    // Re-force method — walk-in/select2 handlers can reset it
                    $dd.val(method);
                    try {
                        if ($dd.hasClass('select2-hidden-accessible')) {
                            $dd.trigger('change.select2');
                        }
                    } catch (errSel) {}
                    $row.find('input[name*="[method]"]').val(method);
                }
                // Prefer explicit account, else location default (do not clear)
                var acc = accountId;
                if (acc === undefined || acc === null || acc === '') {
                    acc = getDefaultAccountIdForMethod(method);
                }
                if (acc) {
                    setPaymentRowAccount($row, acc);
                }
                if (typeof __write_number === 'function') {
                    __write_number($amt, amount);
                } else {
                    $amt.val(amount);
                }
                try { $amt.trigger('change'); } catch (err2) {}

                if (method === 'cheque' && extra) {
                    $row.find('.cheque_name_input').val(extra.cheque_name || '');
                    $row.find('input[name*="[cheque_number]"]').val(extra.cheque_number || '');
                    $row.find('.cheque_date_picker, input[name*="[cheque_date]"]').val(extra.cheque_date || '');
                    var $bank = $row.find('select[name*="[cheque_bank_name]"], input[name*="[cheque_bank_name]"]');
                    if ($bank.length) {
                        $bank.val(extra.cheque_bank_name || '');
                        try { $bank.trigger('change'); } catch (err3) {}
                    }
                    // Final lock: method must stay cheque after detail fields are written
                    if ($dd.length) {
                        window.__vspos_allow_walkin_cheque = true;
                        $dd.val('cheque');
                        try {
                            if ($dd.hasClass('select2-hidden-accessible')) {
                                $dd.trigger('change.select2');
                            }
                        } catch (errSel2) {}
                        $row.find('input[name*="[method]"]').val('cheque');
                    }
                    window.__vspos_allow_walkin_cheque = prevAllow;
                } else if ($dd.length && method === 'cheque') {
                    window.__vspos_allow_walkin_cheque = prevAllow;
                }
            }

            function paymentRowWrappers() {
                return $('#payment_rows_div').children().filter(function () {
                    return $(this).find('.payment_row').length > 0 || $(this).hasClass('payment_row');
                });
            }

            function paymentRowAt(index) {
                var $wrap = paymentRowWrappers().eq(index);
                if (!$wrap.length) return $();
                return $wrap.hasClass('payment_row') ? $wrap : $wrap.find('.payment_row').first();
            }

            function addPaymentRowAsync(cb) {
                var row_index = $('#payment_row_index').val() || 0;
                var location_id = $('input#location_id').val();
                $.ajax({
                    method: 'POST',
                    url: '/sells/pos/get_payment_row',
                    data: {
                        row_index: row_index,
                        location_id: location_id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'html',
                    success: function (result) {
                        if (!result) {
                            if (cb) cb(null);
                            return;
                        }
                        $('#payment_rows_div').append(result);
                        var $row = paymentRowAt(paymentRowWrappers().length - 1);
                        $('#payment_row_index').val(parseInt(row_index, 10) + 1);
                        try {
                            if (typeof __select2 === 'function') {
                                __select2($row.find('.select2'));
                            }
                        } catch (err) {}
                        if (cb) cb($row);
                    },
                    error: function (xhr) {
                        var msg = 'Could not add payment row';
                        if (xhr && xhr.status === 419) msg = 'Session expired — refresh the page';
                        if (window.toastr) toastr.error(msg);
                        if (cb) cb(null);
                    }
                });
            }

            function ensureTouchRowCount(count, cb) {
                var have = paymentRowWrappers().length;
                if (have >= count) {
                    cb(true);
                    return;
                }
                addPaymentRowAsync(function ($row) {
                    if (!$row || !$row.length) {
                        cb(false);
                        return;
                    }
                    ensureTouchRowCount(count, cb);
                });
            }

            function syncTouchPaymentsToDom(cb) {
                if (!touchPayments.length) {
                    if (cb) cb(false);
                    return;
                }
                // Hold walk-in cheque allow for entire DOM sync (prevents cash reset mid-loop)
                var prevAllowSync = window.__vspos_allow_walkin_cheque;
                var hasCheque = touchPayments.some(function (p) { return p && p.method === 'cheque'; });
                if (hasCheque) {
                    window.__vspos_allow_walkin_cheque = true;
                }
                ensureTouchRowCount(touchPayments.length, function (ok) {
                    if (!ok) {
                        window.__vspos_allow_walkin_cheque = prevAllowSync;
                        if (window.toastr) toastr.error('Could not prepare payment rows');
                        if (cb) cb(false);
                        return;
                    }
                    // Extra rows beyond list → zero/remove
                    paymentRowWrappers().each(function (i) {
                        if (i >= touchPayments.length) {
                            $(this).remove();
                        }
                    });
                    for (var i = 0; i < touchPayments.length; i++) {
                        var tp = touchPayments[i];
                        fillPaymentRow(
                            paymentRowAt(i),
                            tp.method,
                            tp.amount,
                            tp.account_id,
                            tp.method === 'cheque' ? {
                                cheque_name: tp.cheque_name,
                                cheque_number: tp.cheque_number,
                                cheque_date: tp.cheque_date,
                                cheque_bank_name: tp.cheque_bank_name
                            } : null
                        );
                    }
                    // Absolute last pass: force every cheque row method before submit
                    for (var j = 0; j < touchPayments.length; j++) {
                        if (!touchPayments[j] || touchPayments[j].method !== 'cheque') continue;
                        var $r = paymentRowAt(j);
                        var $d = $r.find('.payment_types_dropdown').first();
                        if ($d.length) {
                            $d.val('cheque');
                            $r.find('input[name*="[method]"]').val('cheque');
                        }
                    }
                    window.__vspos_allow_walkin_cheque = prevAllowSync;
                    if (typeof calculate_balance_due === 'function') {
                        try { calculate_balance_due(); } catch (err) {}
                    }
                    if (cb) cb(true);
                });
            }

            function touchAddPayment(method, chequeDetails) {
                if (touchAddPaymentBusy) return;
                if (!method) return;
                if (!hasProducts()) {
                    toastr.warning(LANG.no_products_added);
                    return;
                }
                if (!multipayViewActive) {
                    prepareTouchPayWorkspace();
                }

                // Cheque requires details sheet first
                if (method === 'cheque' && !chequeDetails) {
                    beginTouchChequePayment();
                    return;
                }

                var due = touchRemainingDue();
                var amount = getEnteredAmount();
                if (!amount || amount <= 0) {
                    amount = due > 0 ? due : getTotalPayable();
                }
                // Don't over-collect beyond remaining unless cash (change allowed)
                if (method !== 'cash' && due > 0 && amount > due + 0.0001) {
                    amount = due;
                }
                if (amount <= 0) {
                    toastr.warning('Nothing left to pay');
                    return;
                }

                touchAddPaymentBusy = true;
                try {
                    var accountId = resolveAccountForMethod(method);
                    var entry = {
                        method: method,
                        label: touchMethodLabels[method] || method,
                        amount: amount,
                        account_id: accountId,
                        account_label: accountLabel(accountId)
                    };
                    if (method === 'cheque' && chequeDetails) {
                        entry.cheque_name = chequeDetails.cheque_name || '';
                        entry.cheque_number = chequeDetails.cheque_number || '';
                        entry.cheque_date = chequeDetails.cheque_date || '';
                        entry.cheque_bank_name = chequeDetails.cheque_bank_name || '';
                    }
                    touchPayments.push(entry);
                    // After add, unlock select and show default for next payment
                    setTouchAccountSelect(getDefaultAccountIdForMethod(method), method, false);
                    closeTouchChequeSheet();
                    renderTouchPayList();
                    var rem = touchRemainingDue();
                    setAmountInput(rem > 0 ? rem : 0);
                    // Fully paid → finish sale
                    if (rem < 0.5) {
                        setTimeout(function () {
                            finalizeTouchPay();
                        }, 80);
                    }
                } finally {
                    touchAddPaymentBusy = false;
                }
            }

            function computeTouchChequeAmount() {
                var due = touchRemainingDue();
                var amount = getEnteredAmount();
                if (!amount || amount <= 0) {
                    amount = due > 0 ? due : getTotalPayable();
                }
                if (due > 0 && amount > due + 0.0001) {
                    amount = due;
                }
                return amount;
            }

            function openTouchChequeSheet(amount) {
                var sheet = document.getElementById('vspos_touch_cheque_sheet');
                if (!sheet) {
                    toastr.error('Cheque form not available');
                    return;
                }
                // Mount over the whole payment popup so fields are never clipped/hidden
                var popup = document.getElementById('vspos_mobile_pay_popup');
                if (popup && sheet.parentElement !== popup) {
                    popup.appendChild(sheet);
                }
                var $amtLabel = $('#vspos_touch_cheque_amount_label');
                if ($amtLabel.length) $amtLabel.text(formatPayMoney(amount));
                $('#vspos_touch_cheque_name').val('');
                $('#vspos_touch_cheque_number').val('');
                $('#vspos_touch_cheque_date').val('');
                // Prefill cheque name from selected customer / supplier when possible
                try {
                    var $cust = $('#customer_id').find(':selected');
                    var custName = ($cust.data('name') || $cust.text() || '').toString().trim();
                    if (custName && custName.toLowerCase().indexOf('walk') === -1) {
                        $('#vspos_touch_cheque_name').val(custName);
                    }
                } catch (err) {}
                sheet.hidden = false;
                sheet.removeAttribute('hidden');
                sheet.setAttribute('aria-hidden', 'false');
                sheet.classList.add('is-open');
                // Close any leftover datepicker so name / no / bank stay visible
                try {
                    $('.cheque_date_picker').datepicker('hide');
                    $('.datepicker').removeClass('vspos-dp--cheque').hide();
                } catch (errDp) {}
                if (typeof initChequeDatepicker === 'function') {
                    initChequeDatepicker($(sheet));
                }
                // Sri Lankan banks — searchable Select2
                try {
                    var $bank = $('#vspos_touch_cheque_bank');
                    if ($bank.length && $bank.is('select') && typeof $bank.select2 === 'function') {
                        if ($bank.hasClass('select2-hidden-accessible')) {
                            $bank.select2('destroy');
                        }
                        var $parent = $(sheet).find('.vspos-touch-cheque-sheet-card');
                        if (!$parent.length) {
                            $parent = $(sheet);
                        }
                        $bank.val(null).select2({
                            width: '100%',
                            placeholder: $bank.data('placeholder') || 'Select Bank',
                            allowClear: true,
                            dropdownParent: $parent,
                            minimumResultsForSearch: 0
                        });
                    } else if ($bank.length) {
                        $bank.val('');
                    }
                } catch (errBank) {
                    $('#vspos_touch_cheque_bank').val('');
                }
                setTimeout(function () {
                    var $name = $('#vspos_touch_cheque_name');
                    var $number = $('#vspos_touch_cheque_number');
                    if ($name.val()) {
                        $number.focus();
                    } else {
                        $name.focus();
                    }
                }, 50);
            }

            function closeTouchChequeSheet() {
                var sheet = document.getElementById('vspos_touch_cheque_sheet');
                if (!sheet) return;
                try {
                    var $bank = $('#vspos_touch_cheque_bank');
                    if ($bank.length && $bank.hasClass('select2-hidden-accessible')) {
                        $bank.select2('close');
                    }
                } catch (errCloseBank) {}
                sheet.hidden = true;
                sheet.setAttribute('hidden', 'hidden');
                sheet.setAttribute('aria-hidden', 'true');
                sheet.classList.remove('is-open');
            }

            function beginTouchChequePayment() {
                var isPop = $('#is_pop_mode').length > 0;
                // Sell POS: walk-in must pick a customer first (cheque needs a named party).
                // POP: supplier cheques are allowed even for walk-in / cash suppliers.
                if (!isPop && typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer()) {
                    if (typeof pos_swal_select_customer_for_cheque === 'function') {
                        pos_swal_select_customer_for_cheque(function (selected) {
                            if (selected) {
                                beginTouchChequePayment();
                            }
                        });
                    } else {
                        toastr.error((typeof LANG !== 'undefined' && LANG.cheque_not_allowed_walk_in)
                            ? LANG.cheque_not_allowed_walk_in
                            : 'Cheque is not allowed for Walk-In Customer. Please select a customer.');
                    }
                    return;
                }
                var due = touchRemainingDue();
                if (due < 0.5 && touchPayments.length) {
                    // Draft cash auto-line (not saved yet): swap to cheque instead of blocking
                    var onlyDraftCash = touchPayments.length === 1
                        && touchPayments[0].method === 'cash'
                        && !touchPayments[0].payment_id
                        && !touchPayments[0].existing;
                    if (onlyDraftCash) {
                        touchPayments = [];
                        renderTouchPayList();
                        due = touchRemainingDue();
                    } else {
                        toastr.warning('Already fully paid. Remove a payment first to pay by cheque.');
                        return;
                    }
                }
                var amount = computeTouchChequeAmount();
                if (amount <= 0) {
                    toastr.warning('Nothing left to pay');
                    return;
                }
                // Ensure cash default account updates to cheque default while sheet is open
                setTouchAccountSelect(getDefaultAccountIdForMethod('cheque'), 'cheque', false);
                openTouchChequeSheet(amount);
            }

            function confirmTouchChequePayment() {
                var name = String($('#vspos_touch_cheque_name').val() || '').trim();
                var number = String($('#vspos_touch_cheque_number').val() || '').trim();
                var date = String($('#vspos_touch_cheque_date').val() || '').trim();
                var bank = String($('#vspos_touch_cheque_bank').val() || '').trim();

                if (!name) {
                    toastr.error((typeof LANG !== 'undefined' && LANG.cheque_name_required)
                        ? LANG.cheque_name_required
                        : 'Please enter the name on the cheque.');
                    $('#vspos_touch_cheque_name').focus();
                    return;
                }
                if (!number) {
                    toastr.error((typeof LANG !== 'undefined' && LANG.cheque_number_required)
                        ? LANG.cheque_number_required
                        : 'Please enter the cheque number.');
                    $('#vspos_touch_cheque_number').focus();
                    return;
                }
                if (!date) {
                    toastr.error('Please enter the cheque due date.');
                    $('#vspos_touch_cheque_date').focus();
                    return;
                }
                if (!bank) {
                    toastr.error('Please select / enter the bank.');
                    $('#vspos_touch_cheque_bank').focus();
                    return;
                }

                touchAddPayment('cheque', {
                    cheque_name: name,
                    cheque_number: number,
                    cheque_date: date,
                    cheque_bank_name: bank
                });
            }

            function removeTouchPaymentAt(index) {
                if (index < 0 || index >= touchPayments.length) return;
                touchPayments.splice(index, 1);
                renderTouchPayList();
                setAmountInput(touchRemainingDue() || getTotalPayable());
            }

            function finalizeTouchPay() {
                if (!touchPayments.length) {
                    toastr.warning('Add at least one payment');
                    return;
                }
                var due = touchRemainingDue();
                if (due >= 0.5) {
                    if (!confirm(LANG.paid_amount_is_less_than_payable || 'Paid amount is less than payable. Continue?')) {
                        return;
                    }
                }
                syncTouchPaymentsToDom(function (ok) {
                    if (!ok) return;
                    finalizeMultipaySale();
                });
            }

            function showPayMainView() {
                multipayViewActive = false;
                restorePaymentRows();
                if (payViewMain) payViewMain.classList.remove('vspos-pay-view--hidden');
                if (payViewMultipay) payViewMultipay.classList.add('vspos-pay-view--hidden');
                if (popup) popup.classList.remove('vspos-mobile-pay-popup--multipay');
            }

            function showMultipayView() {
                if (!hasProducts()) {
                    toastr.warning(LANG.no_products_added);
                    return false;
                }
                if ($('#reward_point_enabled').length) {
                    var validate_rp = isValidatRewardPoint();
                    if (!validate_rp['is_valid']) {
                        toastr.error(validate_rp['msg']);
                        return false;
                    }
                    if (typeof getCustomerRewardPoints === 'function') {
                        getCustomerRewardPoints();
                    }
                }

                mountPaymentRowsInPopup();
                syncMultipaySummary();
                if (typeof vspos_apply_exchange_credit_note === 'function') {
                    vspos_apply_exchange_credit_note();
                }
                if (typeof ensureLoyaltyInPaymentDropdowns === 'function') {
                    ensureLoyaltyInPaymentDropdowns();
                }
                if (typeof getCustomerRewardPoints === 'function') {
                    getCustomerRewardPoints();
                }
                if (typeof refreshLoyaltyPayRows === 'function') {
                    refreshLoyaltyPayRows();
                }

                if (payViewMain) payViewMain.classList.add('vspos-pay-view--hidden');
                if (payViewMultipay) payViewMultipay.classList.remove('vspos-pay-view--hidden');
                if (popup) popup.classList.add('vspos-mobile-pay-popup--multipay');
                multipayViewActive = true;

                var $firstAmount = $('#vspos_multipay_rows_mount .payment-amount').first();
                if ($firstAmount.length) {
                    setTimeout(function () {
                        $firstAmount.focus().select();
                    }, 200);
                }
                return true;
            }

            function finalizeMultipaySale() {
                if (typeof isValidPosForm === 'function' && !isValidPosForm()) {
                    return false;
                }
                if ($('#reward_point_enabled').length) {
                    if (typeof vsposHasLoyaltyPayment === 'function' && vsposHasLoyaltyPayment()) {
                        if (typeof loyaltyRedeemAllowed === 'function' && !loyaltyRedeemAllowed()) {
                            toastr.error('Send OTP to customer and verify before redeeming loyalty points');
                            return false;
                        }
                    }
                    if (typeof vsposConvertLoyaltyPaymentsToRedeem === 'function') {
                        var conv = vsposConvertLoyaltyPaymentsToRedeem();
                        if (conv && conv.error) {
                            toastr.error(conv.error);
                            return false;
                        }
                    }
                    var validate_rp = isValidatRewardPoint();
                    if (!validate_rp['is_valid']) {
                        toastr.error(validate_rp['msg']);
                        return false;
                    }
                }
                var balDue = parseFloat($('#in_balance_due').val()) || 0;
                if (balDue >= 0.5) {
                    if (!confirm(LANG.paid_amount_is_less_than_payable)) {
                        return false;
                    }
                }
                var totalAdvance = 0;
                $('#payment_rows_div').find('select.payment_types_dropdown').each(function () {
                    if ($(this).val() === 'advance') totalAdvance++;
                });
                if (totalAdvance > 1) {
                    alert(LANG.advance_payment_cannot_be_more_than_once);
                    return false;
                }
                showPayMainView();
                closePayPopup();
                pos_form_obj.submit();
                return true;
            }

            function hasProducts() {
                return $('table#pos_table tbody').find('.product_row').length > 0;
            }

            function getTotalPayable() {
                return typeof __read_number === 'function'
                    ? __read_number($('input#final_total_input'))
                    : (parseFloat($('input#final_total_input').val()) || 0);
            }

            function syncPayAmountDefault() {
                if (!amountInput) return;
                var total = getTotalPayable();
                var paying = getTotalPayingAmt();
                var due = Math.max(0, total - paying);
                // Prefer remaining balance when invoice already has payments (edit)
                var val = due > 0 ? due : (paying > 0 ? 0 : total);
                if (typeof __write_number === 'function') {
                    __write_number($(amountInput), val);
                } else {
                    amountInput.value = val > 0 ? val : '';
                }
                updateChangePreview();
            }

            function getEnteredAmount() {
                if (!amountInput || amountInput.value === '') {
                    return getTotalPayable();
                }
                return typeof __read_number === 'function'
                    ? __read_number($(amountInput))
                    : (parseFloat(amountInput.value) || 0);
            }

            function updateChangePreview() {
                if (!changePreview) return;
                var total = getTotalPayable();
                var tendered = getEnteredAmount();
                var change = Math.max(0, tendered - total);
                if (typeof __currency_trans_from_en === 'function') {
                    changePreview.textContent = __currency_trans_from_en(change, true);
                } else {
                    changePreview.textContent = change.toFixed(2);
                }
            }

            function clearStuckOverlays() {
                // Leftover Bootstrap backdrops (e.g. loyalty popup) block pay clicks
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                var loyalty = document.getElementById('loyaltyCustomerModal');
                if (loyalty) {
                    $(loyalty).removeClass('in').css('display', 'none').attr('aria-hidden', 'true');
                }
            }

            function openPayPopup() {
                if (!popup) {
                    $('#modal_payment').modal('show');
                    return;
                }
                if (!hasProducts()) {
                    toastr.warning(LANG.no_products_added);
                    return;
                }
                clearStuckOverlays();
                if (isTouchPayUi()) {
                    if (payViewMain) payViewMain.classList.remove('vspos-pay-view--hidden');
                    if (payViewMultipay) payViewMultipay.classList.add('vspos-pay-view--hidden');
                    prepareTouchPayWorkspace();
                } else {
                    showPayMainView();
                    syncPayAmountDefault();
                }
                if (typeof set_payment_type_dropdown === 'function') {
                    set_payment_type_dropdown();
                }
                popup.classList.add('vspos-mobile-pay-popup--open');
                popup.setAttribute('aria-hidden', 'false');
                if (backdrop) {
                    backdrop.classList.add('vspos-mobile-pay-backdrop--open');
                    backdrop.setAttribute('aria-hidden', 'false');
                }
                document.body.classList.add('vspos-mobile-pay-open');
                if (amountInput) {
                    setTimeout(function () {
                        amountInput.focus();
                        amountInput.select();
                    }, 280);
                }
            }

            function closePayPopup() {
                if (!popup) return;
                closeTouchChequeSheet();
                showPayMainView();
                popup.classList.remove('vspos-mobile-pay-popup--open');
                popup.setAttribute('aria-hidden', 'true');
                if (backdrop) {
                    backdrop.classList.remove('vspos-mobile-pay-backdrop--open');
                    backdrop.setAttribute('aria-hidden', 'true');
                }
                document.body.classList.remove('vspos-mobile-pay-open');
            }

            function openMultiPayModal() {
                if (!popup) {
                    $('#modal_payment').modal('show');
                    return;
                }
                if (!popup.classList.contains('vspos-mobile-pay-popup--open')) {
                    openPayPopup();
                }
                showMultipayView();
            }

            function applyCashPayment(tendered, requireFull) {
                if (!hasProducts()) {
                    toastr.warning(LANG.no_products_added);
                    return false;
                }

                if ($('#reward_point_enabled').length) {
                    var validate_rp = isValidatRewardPoint();
                    if (!validate_rp['is_valid']) {
                        toastr.error(validate_rp['msg']);
                        return false;
                    }
                }

                var totalPayable = getTotalPayable();
                if (requireFull && tendered < totalPayable) {
                    toastr.warning(LANG.amount_less_than_total || 'Amount is less than total payable. Use Multi Pay for partial payments.');
                    return false;
                }

                if ($('#is_credit_sale').length) {
                    $('#is_credit_sale').val(0);
                }

                var payAmount = Math.max(tendered, totalPayable);

                var firstRow = $('#payment_rows_div').find('.payment-amount').first();
                if (!firstRow.length) {
                    toastr.error('Payment row not found.');
                    return false;
                }

                if (typeof __write_number === 'function') {
                    __write_number(firstRow, payAmount);
                } else {
                    firstRow.val(payAmount);
                }
                firstRow.trigger('change');

                var dropdown = $('#payment_rows_div').find('.payment_types_dropdown').first();
                dropdown.val('cash').trigger('change');

                // Always force location default cash account (or manual override if set)
                var cashAccountId = resolveAccountForMethod('cash') || getDefaultAccountIdForMethod('cash');
                var $cashRow = dropdown.closest('.payment_row');
                if (!$cashRow.length) {
                    $cashRow = firstRow.closest('.payment_row');
                }
                if (cashAccountId) {
                    setPaymentRowAccount($cashRow, cashAccountId);
                } else {
                    // Soft fallback: first account on the payment-row dropdown (sale still works without it)
                    var $accSel = $cashRow.find('select.account-dropdown').first();
                    var existing = $accSel.val();
                    if (!existing && $accSel.length) {
                        var firstReal = $accSel.find('option').filter(function () {
                            var v = $(this).val();
                            return v !== undefined && v !== null && String(v) !== '';
                        }).first().val();
                        if (firstReal) {
                            setPaymentRowAccount($cashRow, firstReal);
                            existing = firstReal;
                        }
                    }
                    if (!existing) {
                        toastr.warning('No default cash account set for this location. Link it in Business Locations.');
                    }
                }

                if (typeof pos_total_row === 'function') {
                    pos_total_row();
                }

                closePayPopup();
                pos_form_obj.submit();
                return true;
            }

            window.vsposOpenPayPopup = openPayPopup;
            window.vsposClosePayPopup = closePayPopup;
            window.vsposOpenMobilePay = openPayPopup;
            window.vsposOpenMultipay = openMultiPayModal;

            if (payBtn) {
                payBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openPayPopup();
                });
            }

            $(document).on('click', '.vspos-open-pay-popup', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openPayPopup();
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closePayPopup);
            }
            var payBackBtn = document.getElementById('vspos_mpos_pay_back');
            if (payBackBtn) {
                payBackBtn.addEventListener('click', closePayPopup);
            }
            var addAnotherBtn = document.getElementById('vspos_touch_add_another');
            if (addAnotherBtn) {
                addAnotherBtn.addEventListener('click', function () {
                    if (!amountInput) return;
                    amountInput.focus();
                    try {
                        amountInput.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    } catch (err) {}
                });
            }
            if (backdrop) {
                backdrop.addEventListener('click', closePayPopup);
            }
            if (detailsBtn) {
                detailsBtn.addEventListener('click', function () {
                    closePayPopup();
                    document.dispatchEvent(new CustomEvent('vspos:expand-order-sheet'));
                });
            }

            if (amountInput) {
                amountInput.addEventListener('input', updateChangePreview);
                amountInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (isTouchPayUi()) {
                            touchAddPayment('cash');
                        } else if (cashFullPayBtn) {
                            cashFullPayBtn.click();
                        }
                    }
                });
            }

            // F-keys while payment popup is open
            document.addEventListener('keydown', function (e) {
                if (!popup || !popup.classList.contains('vspos-mobile-pay-popup--open')) {
                    return;
                }
                if (multipayViewActive && !isTouchPayUi()) {
                    return;
                }
                var codeMap = { 112:'f1',113:'f2',114:'f3',115:'f4',116:'f5',117:'f6',118:'f7',119:'f8',120:'f9',121:'f10',122:'f11',123:'f12' };
                var pressed = (e.key || '').toLowerCase();
                if (e.keyCode && codeMap[e.keyCode]) {
                    pressed = codeMap[e.keyCode];
                }
                var keyCash = (popup.getAttribute('data-key-cash') || 'f1').toLowerCase();
                var keyCashAdd = (popup.getAttribute('data-key-cash-add') || 'f2').toLowerCase();
                var keyCard = (popup.getAttribute('data-key-card') || 'f5').toLowerCase();
                var keyCredit = (popup.getAttribute('data-key-credit') || 'f6').toLowerCase();
                var keyCheque = (popup.getAttribute('data-key-cheque') || 'f7').toLowerCase();
                var keyBank = (popup.getAttribute('data-key-bank') || 'f11').toLowerCase();
                var keySplit = (popup.getAttribute('data-key-split') || 'f12').toLowerCase();

                function clickTrigger(sel) {
                    var $btn = $(sel).first();
                    if ($btn.length) {
                        e.preventDefault();
                        e.stopPropagation();
                        $btn.trigger('click');
                        return true;
                    }
                    return false;
                }

                if (pressed === keyCash) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (cashFullPayBtn) cashFullPayBtn.click();
                    return;
                }
                if (isTouchPayUi()) {
                    if (pressed === keyCashAdd) {
                        e.preventDefault();
                        e.stopPropagation();
                        touchAddPayment('cash');
                        return;
                    }
                    if (pressed === keyCard) {
                        e.preventDefault();
                        e.stopPropagation();
                        touchAddPayment('card');
                        return;
                    }
                    if (pressed === keyBank) {
                        e.preventDefault();
                        e.stopPropagation();
                        touchAddPayment('bank_transfer');
                        return;
                    }
                    if (pressed === keyCheque) {
                        e.preventDefault();
                        e.stopPropagation();
                        if ($('[data-touch-pay-method="cheque"]:not(.hide)').length) {
                            touchAddPayment('cheque');
                        }
                        return;
                    }
                    if (pressed === keyCredit) {
                        clickTrigger('.vspos-touch-pay-method--credit');
                        return;
                    }
                    return;
                }
                if (pressed === keyCard) {
                    clickTrigger('.vspos-pay-v2-chip--card');
                    return;
                }
                if (pressed === keyCredit) {
                    clickTrigger('.vspos-pay-v2-chip--credit');
                    return;
                }
                if (pressed === keyBank) {
                    clickTrigger('.vspos-pay-v2-chip--bank');
                    return;
                }
                if (pressed === keySplit) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (multiPayBtn) multiPayBtn.click();
                }
            }, true);

            if (cashFullPayBtn) {
                cashFullPayBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    // Touch edit: keep existing paid lines, cash only the remaining due
                    if (isTouchPayUi()) {
                        if (!multipayViewActive) {
                            prepareTouchPayWorkspace();
                        }
                        var rem = touchRemainingDue();
                        if (rem < 0.5) {
                            finalizeTouchPay();
                            return;
                        }
                        setAmountInput(rem);
                        touchAddPayment('cash');
                        return;
                    }
                    // Classic: full cash checkout
                    applyCashPayment(getTotalPayable(), true);
                });
            }

            $(document).on('click', '[data-touch-pay-method]', function (e) {
                e.preventDefault();
                e.stopPropagation();
                touchAddPayment($(this).attr('data-touch-pay-method'));
            });

            $(document).on('click', '#vspos_touch_cheque_confirm', function (e) {
                e.preventDefault();
                confirmTouchChequePayment();
            });
            $(document).on('click', '#vspos_touch_cheque_cancel, #vspos_touch_cheque_back', function (e) {
                e.preventDefault();
                closeTouchChequeSheet();
            });
            $(document).on('keydown', '#vspos_touch_cheque_sheet input, #vspos_touch_cheque_sheet select', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmTouchChequePayment();
                }
            });

            $(document).on('click', '#vspos_touch_pay_list .vspos-touch-pay-line-remove', function (e) {
                e.preventDefault();
                var idx = parseInt($(this).attr('data-pay-index'), 10);
                if (!isNaN(idx)) removeTouchPaymentAt(idx);
            });

            $(document).on('change', '#vspos_touch_pay_account', function () {
                var $sel = $(this);
                $sel.attr('data-manual', '1');
                if (!$sel.attr('data-bound-method')) {
                    $sel.attr('data-bound-method', 'cash');
                }
            });

            $(document).on('change', '#vspos_touch_pay_list .vspos-touch-pay-line-account', function () {
                var idx = parseInt($(this).attr('data-pay-index'), 10);
                if (isNaN(idx) || !touchPayments[idx]) return;
                var val = $(this).val() || '';
                touchPayments[idx].account_id = val;
                touchPayments[idx].account_label = accountLabel(val);
            });

            // Preview location default for method (skipped when cashier overrode account)
            $(document).on('pointerdown', '[data-touch-pay-method]', function () {
                var method = $(this).attr('data-touch-pay-method');
                var $sel = touchAccountSelect();
                if (!$sel.length || !method) return;
                if ($sel.attr('data-manual') === '1') return;
                setTouchAccountSelect(getDefaultAccountIdForMethod(method), method, false);
            });

            if (touchPayClearBtn) {
                touchPayClearBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    // Restore original invoice payments (edit) instead of wiping paid amount
                    touchPayments = cloneTouchPayments(touchPaymentsBaseline);
                    if (!touchPayments.length) {
                        resetTouchPaymentRows();
                    } else {
                        syncTouchPaymentsToDom(function () {
                            setTouchAccountSelect(getDefaultAccountIdForMethod('cash'), 'cash', false);
                            var due = touchRemainingDue();
                            setAmountInput(due > 0 ? due : 0);
                            renderTouchPayList();
                        });
                        return;
                    }
                    setTouchAccountSelect(getDefaultAccountIdForMethod('cash'), 'cash', false);
                    var dueClear = touchRemainingDue();
                    setAmountInput(dueClear > 0 ? dueClear : getTotalPayable());
                    renderTouchPayList();
                });
            }

            // Exact on keypad = remaining due while touch pay is open
            $(document).on('click', '#vspos_touch_keypad [data-key="exact"]', function () {
                if (!popup || !popup.classList.contains('vspos-mobile-pay-popup--open')) return;
                if (!isTouchPayUi()) return;
                setTimeout(function () {
                    setAmountInput(touchRemainingDue() || getTotalPayable());
                }, 0);
            });

            if (multiPayBtn) {
                multiPayBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var tendered = getEnteredAmount();
                    var totalPayable = getTotalPayable();
                    if (amountInput && amountInput.value !== '' && tendered > 0 && tendered !== totalPayable) {
                        var firstRow = $('#payment_rows_div').find('.payment-amount').first();
                        if (typeof __write_number === 'function') {
                            __write_number(firstRow, tendered);
                        } else {
                            firstRow.val(tendered);
                        }
                        firstRow.trigger('change');
                    }
                    openMultiPayModal();
                });
            }

            if (multipayBackBtn) {
                multipayBackBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    showPayMainView();
                    syncPayAmountDefault();
                });
            }
            if (multipayCloseBtn) {
                multipayCloseBtn.addEventListener('click', closePayPopup);
            }
            if (multipayAddRowBtn) {
                multipayAddRowBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    $('button#add-payment-row').trigger('click');
                });
            }
            if (multipayFinalizeBtn) {
                multipayFinalizeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (isTouchPayUi()) {
                        finalizeTouchPay();
                    } else {
                        finalizeMultipaySale();
                    }
                });
            }

            $(document).ajaxComplete(function (_evt, _xhr, settings) {
                if (!multipayViewActive) return;
                if (settings && settings.url && settings.url.indexOf('get_payment_row') !== -1) {
                    setTimeout(function () {
                        reinitSelect2InPopup();
                        if (typeof refreshLoyaltyPayRows === 'function') {
                            refreshLoyaltyPayRows();
                        }
                    }, 50);
                }
            });

            $(document).on('click', '.vspos-mobile-pay-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!hasProducts()) {
                    toastr.warning(LANG.no_products_added);
                    return;
                }
                var sel = $(this).data('vspos-trigger');
                closePayPopup();
                if (sel) {
                    $(sel).first().trigger('click');
                }
            });

            $('#modal_payment').on('show.bs.modal', closePayPopup);
        })();


        /* ── Bill Discount popup modal ── */
        /* Pre-fill modal when it opens */
        $('#billDiscountModal').on('show.bs.modal', function() {
            var curType = $('#discount_type').val() || 'percentage';
            var curVal  = typeof __read_number === 'function' ? __read_number($('#discount_amount')) : (parseFloat($('#discount_amount').val()) || 0);
            $('#bd_modal_type').val(curType);
            $('#bd_modal_amount').val(curVal > 0 ? curVal : '');
        });
        $('#billDiscountModal').on('shown.bs.modal', function() {
            $('#bd_modal_amount').focus().select();
        });
        /* Apply button inside modal */
        function applyBillDiscount() {
            var val  = parseFloat($('#bd_modal_amount').val()) || 0;
            var type = $('#bd_modal_type').val() || 'percentage';
            /* Write to POS hidden fields using __write_number so __read_number parses correctly */
            $('#discount_type').val(type);
            if (typeof __write_number === 'function') {
                __write_number($('#discount_amount'), val);
            } else {
                $('#discount_amount').val(val);
            }
            /* Sync existing discount modal fields */
            $('#discount_type_modal').val(type).trigger('change');
            if (typeof __write_number === 'function') {
                __write_number($('#discount_amount_modal'), val);
            } else {
                $('#discount_amount_modal').val(val);
            }
            /* Sync bottom-bar inline editor */
            $('#vspos_disc_inline_amount').val(val > 0 ? val : '');
            if (type === 'percentage') {
                $('#vspos_disc_pct_btn').addClass('active').siblings().removeClass('active');
            } else {
                $('#vspos_disc_fixed_btn').addClass('active').siblings().removeClass('active');
            }
            if (typeof pos_total_row === 'function') pos_total_row();
        }
        $(document).on('click', '#bd_modal_apply', function() {
            applyBillDiscount();
            $('#billDiscountModal').modal('hide');
        });
        /* Enter key inside amount input */
        $(document).on('keydown', '#bd_modal_amount', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); applyBillDiscount(); $('#billDiscountModal').modal('hide'); }
        });
        /* Remove button */
        $(document).on('click', '#bd_modal_remove', function() {
            $('#bd_modal_amount').val('');
            $('#discount_type').val('percentage');
            if (typeof __write_number === 'function') {
                __write_number($('#discount_amount'), 0);
                __write_number($('#discount_amount_modal'), 0);
            } else {
                $('#discount_amount').val(0);
                $('#discount_amount_modal').val(0);
            }
            $('#vspos_disc_inline_amount').val('');
            if (typeof pos_total_row === 'function') pos_total_row();
            $('#billDiscountModal').modal('hide');
        });

        // Serial numbers — only on direct child row additions, no subtree
        var _serialBusy = false;
        function updateSerialNumbers() {
            if (_serialBusy) return;
            _serialBusy = true;
            var rows = document.querySelectorAll('#pos_table tbody tr:not(.pos-empty-state-row):not(.hide)');
            rows.forEach(function(row, idx) {
                var existing = row.querySelector('td.pos-td-serial');
                if (!existing) {
                    var td = document.createElement('td');
                    td.className = 'pos-td-serial';
                    td.textContent = idx + 1;
                    row.insertBefore(td, row.firstChild);
                } else {
                    existing.textContent = idx + 1;
                }
            });
            _serialBusy = false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Only watch direct children of tbody (childList only, NO subtree)
            var tbody = document.querySelector('#pos_table tbody');
            if (tbody) {
                new MutationObserver(function(mutations) {
                    var hasRowChange = mutations.some(function(m) {
                        return Array.from(m.addedNodes).some(function(n) { return n.tagName === 'TR'; }) ||
                               Array.from(m.removedNodes).some(function(n) { return n.tagName === 'TR'; });
                    });
                    if (hasRowChange) updateSerialNumbers();
                }).observe(tbody, { childList: true });
            }
            updateSerialNumbers();

        });
        /* ── Cash In / Out ── */
        $('#cash_in_out_modal').on('show.bs.modal', function(e) {
            var type  = $(e.relatedTarget).data('type') || 'cash_in';
            var isCashIn = type === 'cash_in';
            $('#cash_in_out_title').text(isCashIn ? 'Cash In' : 'Cash Out');
            $('#cash_io_submit_label').text(isCashIn ? 'Add Cash' : 'Remove Cash');
            $('#cash_io_submit').removeClass('btn-success btn-danger')
                                .addClass(isCashIn ? 'btn-success' : 'btn-danger');
            $(this).data('io_type', type);
            $('#cash_io_amount').val('');
            $('#cash_io_note').val('');
        });
        $('#cash_in_out_modal').on('shown.bs.modal', function() {
            $('#cash_io_amount').focus();
        });
        $('#cash_io_submit').on('click', function() {
            var amount = parseFloat($('#cash_io_amount').val());
            if (!amount || amount <= 0) {
                toastr.error('Please enter a valid amount.');
                $('#cash_io_amount').focus();
                return;
            }
            var type = $('#cash_in_out_modal').data('io_type');
            var btn  = $(this).prop('disabled', true);
            $.ajax({
                url: '{{ route("cash_register.cash_in_out") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    type:   type,
                    amount: amount,
                    note:   $('#cash_io_note').val()
                },
                success: function(res) {
                    if (res.success) {
                        toastr.success(type === 'cash_in' ? 'Cash added to drawer.' : 'Cash removed from drawer.');
                        $('#cash_in_out_modal').modal('hide');
                    } else {
                        toastr.error(res.msg || 'Error saving.');
                    }
                },
                error: function() {
                    toastr.error('Could not save. Please try again.');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
        $('#cash_io_amount').on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#cash_io_submit').trigger('click'); }
        });

        /* ── Sale Complete modal (replaces SweetAlert eBill prompt) ── */
        @if (empty($is_pop))
        @php
            $sc_wishes = [
                __('lang_v1.cd_thanks_wish_1'),
                __('lang_v1.cd_thanks_wish_2'),
                __('lang_v1.cd_thanks_wish_3'),
            ];
            $sc_msg = [
                'thankName' => __('lang_v1.cd_thank_you_name'),
                'thankGeneric' => __('lang_v1.sc_thank_you_shopping'),
                'noMobile' => __('lang_v1.sc_no_mobile_hint'),
                'selectChannel' => __('lang_v1.sc_select_channel'),
                'sending' => __('lang_v1.sc_sending'),
                'sendSms' => __('lang_v1.sc_send_sms'),
                'sendWhatsapp' => __('lang_v1.sc_send_whatsapp'),
                'sendEbill' => __('lang_v1.sc_send_ebill'),
            ];
        @endphp
        (function () {
            var SC_WISHES = @json($sc_wishes);
            var SC_MSG = @json($sc_msg);
            var scData = null;
            var scReceipt = null;
            var scNumpadVal = '';

            function scRandomWish() {
                return SC_WISHES[Math.floor(Math.random() * SC_WISHES.length)];
            }

            function scFormatName(data) {
                if (!data) return '';
                var prefix = (data.prefix || '').trim();
                var first = (data.first_name || '').trim();
                var name = (data.contact_name || '').trim();
                if (prefix && first) return prefix + ' ' + first;
                if (prefix && name) return prefix + ' ' + name;
                if (first) return first;
                return name;
            }

            function scHasMobile() {
                return !!($('#vspos_sc_mobile').val() || '').trim();
            }

            function scUpdateChannels() {
                var hasMobile = scHasMobile();
                $('#vspos_sc_channels input[type=checkbox]').prop('disabled', !hasMobile);
                if (hasMobile) {
                    $('#vspos_sc_mobile_hint').text('').addClass('is-hidden');
                } else {
                    $('#vspos_sc_mobile_hint').text(SC_MSG.noMobile).removeClass('is-hidden');
                }
            }

            function scBuildChannels(data) {
                var hasMobile = !!(data.mobile && data.mobile.trim());
                var html = '';
                if (data.has_sms) {
                    html += '<label class="vspos-sc-channel vspos-sc-channel--sms">' +
                        '<input type="checkbox" id="vspos_sc_send_sms" ' + (hasMobile ? 'checked' : 'disabled') + '>' +
                        '<i class="fas fa-sms"></i><span>' + SC_MSG.sendSms + '</span></label>';
                }
                if (data.has_whatsapp) {
                    html += '<label class="vspos-sc-channel vspos-sc-channel--wa">' +
                        '<input type="checkbox" id="vspos_sc_send_wa" ' + (hasMobile ? 'checked' : 'disabled') + '>' +
                        '<i class="fab fa-whatsapp"></i><span>' + SC_MSG.sendWhatsapp + '</span></label>';
                }
                $('#vspos_sc_channels').html(html);
            }

            function scOpenNumpad() {
                scNumpadVal = ($('#vspos_sc_mobile').val() || '').replace(/\D/g, '');
                $('#vspos_sc_numpad_display').text(scNumpadVal || '—');
                $('#vspos_sc_numpad').addClass('is-open').attr('aria-hidden', 'false');
            }

            function scCloseNumpad() {
                $('#vspos_sc_numpad').removeClass('is-open').attr('aria-hidden', 'true');
            }

            function scOpenModal(data, receipt) {
                scData = data;
                scReceipt = receipt;
                var displayName = scFormatName(data);
                if (displayName) {
                    $('#vspos_sc_customer_name').text(SC_MSG.thankName.replace(':name', displayName));
                } else {
                    $('#vspos_sc_customer_name').text(SC_MSG.thankGeneric);
                }
                $('#vspos_sc_wish').text(scRandomWish());
                $('#vspos_sc_mobile').val((data.mobile || '').trim());
                scBuildChannels(data);
                scUpdateChannels();

                var $sendBtn = $('#vspos_sc_btn_send');
                if (!data.has_sms && !data.has_whatsapp) {
                    $sendBtn.addClass('hide');
                } else {
                    $sendBtn.removeClass('hide');
                }

                $('#vspos_sale_complete_backdrop, #vspos_sale_complete_modal')
                    .addClass('is-open').attr('aria-hidden', 'false');
                document.body.classList.add('vspos-sc-open');
                if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                    PosReceiptReturn.markUi({ saleCompleteOpen: true });
                }
            }

            function scCloseModal() {
                scCloseNumpad();
                $('#vspos_sale_complete_backdrop, #vspos_sale_complete_modal')
                    .removeClass('is-open').attr('aria-hidden', 'true');
                document.body.classList.remove('vspos-sc-open');
                scData = null;
                scReceipt = null;
            }
            window.vsposCloseSaleComplete = scCloseModal;

            function scSendEbill() {
                if (!scData || !scData.transaction_id) return;
                var sendSms = $('#vspos_sc_send_sms').length && $('#vspos_sc_send_sms').is(':checked');
                var sendWa = $('#vspos_sc_send_wa').length && $('#vspos_sc_send_wa').is(':checked');
                if ((scData.has_sms || scData.has_whatsapp) && !sendSms && !sendWa) {
                    toastr.warning(SC_MSG.selectChannel);
                    return;
                }
                if ((sendSms || sendWa) && !scHasMobile()) {
                    toastr.warning(SC_MSG.noMobile);
                    scOpenNumpad();
                    return;
                }

                var $btn = $('#vspos_sc_btn_send');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + SC_MSG.sending);

                $.ajax({
                    url: '{{ url("ebill/send") }}/' + scData.transaction_id,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        send_sms: sendSms ? 1 : 0,
                        send_whatsapp: sendWa ? 1 : 0,
                        mobile: $('#vspos_sc_mobile').val().trim()
                    },
                    success: function (res) {
                        if (res.success) {
                            toastr.success(res.msg || 'Digital bill sent successfully.');
                            scCloseModal();
                            if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                                PosReceiptReturn.handleAction('send');
                            }
                        } else {
                            toastr.error(res.msg || 'Failed to send digital bill.');
                            if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                                PosReceiptReturn.handleAction('send-failed');
                            }
                        }
                    },
                    error: function () {
                        toastr.error('Could not send. Please try again.');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('<i class="fi fi-rr-paper-plane pos-fi-icon"></i> ' + SC_MSG.sendEbill);
                    }
                });
            }

            $(document).on('ebill_prompt_ready', function () {
                var data = window.__ebill_prompt_data;
                var receipt = window.__ebill_receipt;
                if (!data) return;
                scOpenModal(data, receipt);
            });

            $('#vspos_sc_close, #vspos_sc_btn_close, #vspos_sale_complete_backdrop').on('click', function () {
                scCloseModal();
                if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                    PosReceiptReturn.handleDismiss();
                }
            });
            $('#vspos_sc_btn_print').on('click', function () {
                var $btn = $(this);
                if ($btn.data('busy')) {
                    return;
                }
                $btn.data('busy', 1);
                var mode = false;
                if (scReceipt && (scReceipt.is_enabled || scReceipt.html_content || scReceipt.print_type === 'printer')) {
                    mode = pos_print(scReceipt);
                }
                if (mode === 'preview' || mode === 'async') {
                    $btn.data('busy', 0);
                    return;
                }
                if (scData && scData.transaction_id) {
                    $.ajax({
                        url: '/sells/' + scData.transaction_id + '/print?check_location=true',
                        type: 'GET',
                        dataType: 'json',
                        success: function (data) {
                            if (data.success == 1 && data.receipt && (data.receipt.is_enabled || data.receipt.html_content)) {
                                if (pos_print(data.receipt) === false) {
                                    toastr.warning(data.msg || 'Receipt not available.');
                                    if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                                        PosReceiptReturn.handleAction('print-failed');
                                    }
                                }
                            } else {
                                toastr.warning(data.msg || 'Receipt not available.');
                            }
                        },
                        error: function () {
                            toastr.error('Print failed. Please try again.');
                        },
                        complete: function () {
                            $btn.data('busy', 0);
                        }
                    });
                    return;
                }
                $btn.data('busy', 0);
                toastr.info('Print preview not available for this receipt.');
                if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                    PosReceiptReturn.handleAction('print-failed');
                }
            });
            $('#vspos_sc_btn_send').on('click', scSendEbill);
            $('#vspos_sc_mobile').on('click', function (e) {
                e.preventDefault();
                scOpenNumpad();
            });
            $('#vspos_sc_numpad_close').on('click', scCloseNumpad);
            $('#vspos_sc_numpad').on('click', '.sc-nk', function () {
                var key = $(this).data('key');
                if (key === 'back') {
                    scNumpadVal = scNumpadVal.slice(0, -1);
                } else if (key === 'done') {
                    $('#vspos_sc_mobile').val(scNumpadVal);
                    scCloseNumpad();
                    scUpdateChannels();
                    if (scNumpadVal) {
                        $('#vspos_sc_send_sms, #vspos_sc_send_wa').prop('checked', true);
                    }
                    return;
                } else if (scNumpadVal.length < 15) {
                    scNumpadVal += String(key);
                }
                $('#vspos_sc_numpad_display').text(scNumpadVal || '—');
            });
        })();
        @endif

        /* ── Resend eBill from Recent Transactions ── */
        $(document).on('click', '.resend-ebill-btn', function(e) {
            e.stopPropagation();
            var txnId = $(this).data('transaction-id');
            var name  = $(this).data('customer-name') || '';
            var mobile = $(this).data('customer-mobile') || '';
            if (!txnId) return;
            if (!mobile) {
                toastr.warning('Customer has no mobile number.');
                return;
            }

            swal({
                title: 'Resend eBill',
                text: 'Resend digital bill to ' + name + ' (' + mobile + ')?',
                icon: 'info',
                buttons: { cancel: 'Cancel', confirm: { text: 'Send', value: true, className: 'swal-button--send' } }
            }).then(function(confirmed) {
                if (!confirmed) return;
                $.ajax({
                    url: '{{ url("ebill/send") }}/' + txnId,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', send_sms: 1, send_whatsapp: 1 },
                    success: function(res) {
                        if (res.success) {
                            toastr.success('Digital bill resent successfully.');
                        } else {
                            toastr.error(res.msg || 'Failed to resend digital bill.');
                        }
                    },
                    error: function() {
                        toastr.error('Could not resend. Please try again.');
                    }
                });
            });
        });

        /* ── Mobile Camera Barcode Scanner ── */
        var barcodeScanner = null;
        var barcodeScanning = false;

        function startBarcodeScanner() {
            if (typeof Html5Qrcode === 'undefined') {
                $('#barcode-scanner-status').text('Scanner library not loaded');
                return;
            }
            if (barcodeScanning) return;
            barcodeScanning = true;

            $('#barcode-scanner-status').text('Starting camera...');

            var region = document.getElementById('barcode-scanner-region');
            region.innerHTML = '';

            barcodeScanner = new Html5Qrcode('barcode-scanner-region');

            var config = {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                aspectRatio: 1.0,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.ITF
                ]
            };

            barcodeScanner.start(
                { facingMode: 'environment' },
                config,
                function(decodedText, decodedResult) {
                    // Barcode detected
                    $('#barcode-scanner-status').text('Found: ' + decodedText);
                    $('#search_product').val(decodedText).focus();

                    // Trigger autocomplete search
                    var $input = $('#search_product');
                    if ($input.data('ui-autocomplete')) {
                        $input.autocomplete('search', decodedText);
                    }

                    // Stop scanner after successful scan
                    setTimeout(function() {
                        stopBarcodeScanner();
                        $('#barcodeScannerModal').modal('hide');
                    }, 500);
                },
                function(errorMessage) {
                    // Scanning in progress, ignore errors
                }
            ).catch(function(err) {
                barcodeScanning = false;
                $('#barcode-scanner-status').text('Camera error: ' + (err.message || err));
            });
        }

        function stopBarcodeScanner() {
            if (barcodeScanner && barcodeScanning) {
                barcodeScanner.stop().then(function() {
                    barcodeScanning = false;
                    $('#barcode-scanner-status').text('Point camera at barcode...');
                }).catch(function(err) {
                    barcodeScanning = false;
                });
            } else {
                barcodeScanning = false;
            }
        }

        $('#barcodeScannerModal').on('shown.bs.modal', function() {
            startBarcodeScanner();
        });

        $('#barcodeScannerModal').on('hidden.bs.modal', function() {
            stopBarcodeScanner();
        });

        $('#barcode_scanner_stop').on('click', function() {
            stopBarcodeScanner();
        });

    })();
    </script>
    <script src="{{ asset('js/offline-pos.js?v=' . config('constants.asset_version')) }}"></script>
    <script>
        $(document).ready(function() {
            @php
                $offlineBillingEnabled = !empty(session('business.common_settings')['enable_offline_billing'] ?? 0);
            @endphp
            @if($offlineBillingEnabled)
                OfflinePOS.init({
                    enabled: true,
                    manualSyncOnly: {{ !empty(session('business.common_settings')['offline_manual_sync_only'] ?? 0) ? 'true' : 'false' }},
                    posUrl: '{{ action([\App\Http\Controllers\SellPosController::class, 'store']) }}'
                });
            @endif
        });
    </script>
@endsection
