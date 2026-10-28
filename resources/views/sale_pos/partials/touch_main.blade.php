{{-- Touch POS main layout: Categories | Product photos | Cart --}}
<script>window.__vspos_touch_ui = 1;</script>
@php
    $is_admin = auth()->user()->hasRole('Admin#'.session('user.business_id'));
    $can_sell = auth()->user()->can('sell.create') || auth()->user()->can('direct_sell.access');
@endphp
<div class="vspos-touch-shell" id="vspos_touch_shell"
     data-ask-qty="{{ (!empty($is_pop) || !empty($pos_settings['touch_ask_qty'])) ? '1' : '0' }}">

    {{-- Top: customer spans above products + cart; comm buttons in left spacer --}}
    @php
        $__touch_comm = in_array('communication', $enabled_modules ?? session('business.enabled_modules', []), true)
            && (! function_exists('feature_enabled') || feature_enabled('communication'));
    @endphp
    <div class="vspos-touch-top">
        <div class="vspos-touch-top-spacer">
            @if($__touch_comm)
                <div class="vspos-touch-mobile-actions vspos-touch-comm-actions" id="vspos_touch_comm_bar">
                    <button type="button"
                        class="vspos-touch-comm-btn vspos-touch-comm-btn--copy"
                        id="vspos_touch_photocopy_btn"
                        data-comm-service="photocopy"
                        title="@lang('lang_v1.photocopy')">
                        <i class="fa fa-copy"></i>
                        <span>@lang('lang_v1.photocopy')</span>
                    </button>
                    <button type="button"
                        class="vspos-touch-comm-btn vspos-touch-comm-btn--print"
                        id="vspos_touch_printout_btn"
                        data-comm-service="printout"
                        title="@lang('lang_v1.printout')">
                        <i class="fa fa-print"></i>
                        <span>@lang('lang_v1.printout')</span>
                    </button>
                </div>
                <script>
                (function () {
                    function openComm(service) {
                        try {
                            if (typeof window.vsposOpenCommunication === 'function') {
                                window.vsposOpenCommunication(service);
                                return;
                            }
                            var $ = window.jQuery;
                            if (!$ ) {
                                alert('jQuery missing');
                                return;
                            }
                            var $m = $('#vspos_communication_modal');
                            if (!$m.length) {
                                alert('Communication popup missing — hard refresh POS (Cmd+Shift+R)');
                                return;
                            }
                            if ($m.parent()[0] !== document.body) {
                                $m.appendTo(document.body);
                            }
                            $('#vspos_comm_service').val(service === 'printout' ? 'printout' : 'photocopy');
                            $('#vspos_comm_title_text').text(service === 'printout' ? 'Printout' : 'Photocopy');
                            $m.css('z-index', 30000).modal('show');
                        } catch (err) {
                            console.error('Communication open failed', err);
                            alert('Communication open failed: ' + (err && err.message ? err.message : err));
                        }
                    }
                    window.vsposOpenCommunicationFallback = openComm;
                    document.addEventListener('click', function (e) {
                        var btn = e.target && e.target.closest
                            ? e.target.closest('#vspos_touch_photocopy_btn, #vspos_touch_printout_btn, .vspos-touch-comm-btn')
                            : null;
                        if (!btn) return;
                        e.preventDefault();
                        e.stopPropagation();
                        var svc = btn.getAttribute('data-comm-service')
                            || (btn.id === 'vspos_touch_printout_btn' ? 'printout' : 'photocopy');
                        openComm(svc);
                    }, true);
                })();
                </script>
            @endif
        </div>
        <div class="vspos-touch-top-main">
            @include('sale_pos.partials.touch_customer_bar')
        </div>
    </div>

    <div class="vspos-touch-layout">

        {{-- LEFT: Categories --}}
        @include('sale_pos.partials.touch_categories')

        {{-- MIDDLE: Product photo grid --}}
        <section class="vspos-touch-products" aria-label="Products">
            <div class="vspos-touch-products-toolbar">
                <div class="vspos-touch-search">
                    <i class="fas fa-barcode vspos-touch-search-ico-barcode" aria-hidden="true"></i>
                    <i class="fas fa-search vspos-touch-search-ico-search" aria-hidden="true"></i>
                    <input type="text" id="search_product" class="form-control mousetrap"
                        placeholder="{{ class_exists(\App\Utils\ShopModeUtil::class) ? \App\Utils\ShopModeUtil::posSearchPlaceholder() : __('lang_v1.search_product_placeholder') }}"
                        autocomplete="off"
                        autofocus>
                    <button type="button" class="vspos-touch-search-btn vspos-touch-scan-btn" data-toggle="modal" data-target="#barcodeScannerModal" title="Scan barcode">
                        <i class="fas fa-barcode" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="vspos-touch-search-btn vspos-open-bulk-search" id="vspos_bulk_search_btn" title="Product search (F8)">
                        <i class="fi fi-rr-apps pos-fi-icon"></i>
                    </button>
                </div>
                @can('product.create')
                <button type="button"
                    class="btn vspos-touch-add-product pos_add_quick_product"
                    data-href="{{ action([\App\Http\Controllers\ProductController::class, 'quickAdd']) }}"
                    data-container=".quick_add_product_modal"
                    title="@lang('lang_v1.add_new_product')">
                    <i class="fa fa-plus"></i>
                    <span>@lang('lang_v1.add_new_product')</span>
                </button>
                @endcan
                <div class="vspos-touch-products-title" id="vspos_touch_cat_title">@lang('lang_v1.all_category')</div>
            </div>

            {{-- Tyre shop quick services — only when Tyre Shop module is enabled --}}
            @if(function_exists('tyre_shop_enabled') && tyre_shop_enabled())
            <div class="vspos-tyre-svc-bar" role="group" aria-label="Quick services">
                <span class="vspos-tyre-svc-label">Quick Services</span>
                <button type="button" class="vspos-tyre-svc-btn" data-service="air_fill">Air Fill</button>
                <button type="button" class="vspos-tyre-svc-btn" data-service="nitrogen_fill">Nitrogen Fill</button>
                <button type="button" class="vspos-tyre-svc-btn" data-service="tyre_fix">Tyre Fix</button>
                <button type="button" class="vspos-tyre-svc-btn vspos-tyre-svc-btn--addon" data-service="addons">Addons</button>
            </div>
            @endif
            @if(function_exists('vehicle_modification_enabled') && vehicle_modification_enabled())
            <div class="vspos-tyre-svc-bar" role="group" aria-label="Workshop services">
                <span class="vspos-tyre-svc-label">@lang('lang_v1.workshop_services')</span>
                @if(auth()->check() && (auth()->user()->can('vehicle_modification.view_history') || (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())))
                <button type="button" class="vspos-tyre-svc-btn" data-toggle="modal" data-target="#pos_vehicle_history_modal">
                    @lang('lang_v1.vehicle_history')
                </button>
                @endif
                <button type="button" class="vspos-tyre-svc-btn vspos-mod-svc-btn" data-service="installation">@lang('lang_v1.mod_svc_installation')</button>
                <button type="button" class="vspos-tyre-svc-btn vspos-mod-svc-btn" data-service="custom_fitting">@lang('lang_v1.mod_svc_fitting')</button>
                <button type="button" class="vspos-tyre-svc-btn vspos-mod-svc-btn" data-service="lighting">@lang('lang_v1.mod_svc_lighting')</button>
                <button type="button" class="vspos-tyre-svc-btn vspos-mod-svc-btn vspos-tyre-svc-btn--addon" data-service="custom">@lang('lang_v1.mod_svc_custom')</button>
            </div>
            @endif

            <input type="hidden" id="suggestion_page" value="1">
            <div id="product_list_body" class="vspos-touch-product-grid eq-height-row">
                <div id="featured_products_box" style="display:none;"></div>
                <div id="featured_empty_state_template" style="display:none;"></div>
                <div id="product_list_items" class="vspos-touch-product-items tw-w-full"></div>
            </div>
            <div class="text-center" id="suggestion_page_loader" style="display:none;">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
        </section>

        {{-- RIGHT: Cart + pay --}}
        <aside class="vspos-touch-cart" aria-label="Cart">
            <div class="vspos-mpos-cart-head">
                <button type="button" class="vspos-mpos-cart-back" id="vspos_mpos_cart_back" aria-label="Back to products">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                </button>
                <strong class="vspos-mpos-cart-title">Cart (<span class="vspos-mpos-line-count">0</span> <span class="vspos-mpos-item-word">items</span>)</strong>
                <button type="button" class="vspos-mpos-cart-clear" id="vspos_mpos_cart_clear">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                    <span>Clear</span>
                </button>
            </div>
            <div class="vspos-touch-cart-inner box-body pb-0">
                {!! Form::hidden('location_id', optional($pos_location ?? $default_location)->id, [
                    'id' => 'location_id',
                    'data-receipt_printer_type' => !empty(($pos_location ?? $default_location)->receipt_printer_type)
                        ? ($pos_location ?? $default_location)->receipt_printer_type
                        : (!empty($location_printer_type) ? $location_printer_type : 'browser'),
                    'data-default_payment_accounts' => ($pos_location ?? $default_location)->default_payment_accounts ?? '',
                ]) !!}
                {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                <input type="hidden" id="item_addition_method" value="{{ !empty($is_pop) ? 0 : $business_details->item_addition_method }}">
                <input type="hidden" name="is_credit_sale" id="is_credit_sale" value="0">
                @if(!empty($mobile_repair_checkout))
                    {!! Form::hidden('mobile_repair_id', $mobile_repair_checkout['repair_id'], ['id' => 'mobile_repair_id']) !!}
                @endif
                @if(!empty($workshop_job_checkout))
                    {!! Form::hidden('workshop_job_id', $workshop_job_checkout['job_id'], ['id' => 'workshop_job_id']) !!}
                @endif
                @if(!empty($edit) && !empty($transaction))
                    <p class="vspos-edit-invoice-no" style="margin:0 0 6px;font-size:11px;opacity:.7;">
                        <strong>@lang('sale.invoice_no'):</strong> {{ $transaction->invoice_no }}
                    </p>
                @endif
                @include('sale_pos.partials.pos_form', [
                    'hide_quick_entry' => true,
                    'hide_customer_toolbar' => true,
                    'edit' => !empty($edit),
                ])
                {{-- Totals markup kept for JS (#total_payable etc.) but visually hidden via CSS; compact footer shows totals --}}
                @include('sale_pos.partials.pos_form_totals', ['edit' => !empty($edit)])
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

            <div class="vspos-touch-cart-footer">
                @if(!empty($is_pop) || request()->is('purchase-pos*'))
                <div class="vspos-touch-last-purchase" id="vspos_touch_last_purchase">
                    <div class="vspos-tlp-info">
                        <span class="vspos-tlp-kicker">Last purchase</span>
                        @if(!empty($last_sell))
                            <span class="vspos-tlp-invoice vspos-hub-invoice"># {{ $last_sell->invoice_no }}</span>
                            <strong class="vspos-tlp-amount vspos-hub-amount">@format_currency($last_sell->final_total)</strong>
                        @else
                            <span class="vspos-tlp-invoice vspos-hub-invoice">No recent purchase</span>
                            <strong class="vspos-tlp-amount vspos-hub-amount"></strong>
                        @endif
                    </div>
                    <div class="vspos-tlp-actions">
                        <button type="button" class="vspos-tlp-btn js-print-last-bill"
                            data-id="{{ !empty($last_sell) ? $last_sell->id : '' }}"
                            @if(empty($last_sell)) disabled @endif
                            title="Reprint">
                            <i class="fi fi-rr-print pos-fi-icon"></i>
                        </button>
                        <button type="button" class="vspos-tlp-btn"
                            data-toggle="modal" data-target="#recent_transactions_modal"
                            title="Purchase list">
                            <i class="fi fi-rr-time-past pos-fi-icon"></i>
                        </button>
                    </div>
                </div>
                @endif
                <div class="vspos-touch-compact-summary">
                    <div class="vspos-touch-compact-row">
                        <span>Items <strong class="total_quantity_mirror">0</strong></span>
                        <span><span class="vspos-sum-short">Sub</span><span class="vspos-sum-long">Sub Total</span> <strong class="price_total_mirror">0.00</strong></span>
                        <button type="button" class="vspos-touch-mini-btn" data-toggle="modal" data-target="#billDiscountModal" title="Discount">
                            <span class="vspos-sum-short">Disc</span><span class="vspos-sum-long">Discount</span> <strong class="total_discount_mirror">0.00</strong>
                        </button>
                        <button type="button" class="vspos-touch-mini-btn" data-toggle="modal" data-target="#posShippingModal" title="Shipping">
                            <span class="vspos-sum-short">Ship</span><span class="vspos-sum-long">Shipping</span> <strong class="shipping_charges_mirror" id="shipping_charges_mirror">0.00</strong>
                        </button>
                    </div>
                    <div class="vspos-touch-compact-total">
                        <span>@lang('sale.total_payable')</span>
                        <strong class="grand_total_mirror vspos-touch-total">0.00</strong>
                    </div>
                </div>
                @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || $is_admin || $can_sell)
                    <button type="button"
                        class="vspos-touch-pay-btn vspos-open-pay-popup @if(!empty($pos_settings['disable_pay_checkout']) && $pos_settings['disable_pay_checkout'] != 0) hide @endif">
                        <i class="fi fi-rr-wallet pos-fi-icon"></i>
                        <span>@lang('lang_v1.pay')</span>
                        @if(!empty($shortcuts['pos']['pay_n_ckeckout']))
                            <kbd class="vspos-touch-pay-kbd">{{ strtoupper($shortcuts['pos']['pay_n_ckeckout']) }}</kbd>
                        @endif
                    </button>
                @endif
            </div>
        </aside>
    </div>

    <button type="button" class="vspos-mpos-dock" id="vspos_mpos_view_cart">
        <span class="vspos-mpos-dock-cart" aria-hidden="true">
            <i class="fa fa-shopping-cart"></i>
            <span class="vspos-mpos-dock-badge vspos-mpos-line-count">0</span>
        </span>
        <span class="vspos-mpos-dock-copy">
            <span class="vspos-mpos-dock-label">View Cart</span>
            <span class="vspos-mpos-dock-meta">
                <span class="vspos-mpos-line-count">0</span>
                <span class="vspos-mpos-item-word">items</span>
                <span aria-hidden="true">·</span>
                <strong class="grand_total_mirror">0.00</strong>
            </span>
        </span>
        <i class="fa fa-chevron-right vspos-mpos-dock-chevron" aria-hidden="true"></i>
    </button>

    {{-- Hidden order panel: express pay triggers for payment popup --}}
    <div class="vspos-order-panel hide" id="vspos_order_panel" aria-hidden="true">
        <div class="vspos-order-panel-body" id="vspos_order_panel_body">
            <button type="button" class="hide" id="vspos_pos_finalize_trigger" tabindex="-1" aria-hidden="true"></button>
            <button type="button" class="hide" id="pos-draft"></button>
            <button type="button" class="hide" id="pos-quotation"></button>
            <button type="button" class="hide js-pos-cancel"></button>
            @if(!empty($edit) && !empty($transaction))
                <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$transaction->id]) }}"
                    class="hide delete-sale" id="pos-delete"></a>
            @endif
            <div class="hide vspos-op-pay-extra">
                @if (!empty($payment_types) && array_key_exists('cash', $payment_types))
                    <button type="button" class="vspos-pay-quick-btn vspos-pay-quick--cash pos-express-finalize" data-pay_method="cash"></button>
                @endif
                @if (!empty($payment_types) && array_key_exists('card', $payment_types))
                    <button type="button" class="vspos-pay-quick-btn vspos-pay-quick--card pos-express-finalize" data-pay_method="card"></button>
                @endif
                @if (empty($pos_settings['disable_credit_sale_button']))
                    <button type="button" class="vspos-pay-quick-btn vspos-pay-quick--credit pos-express-finalize" data-pay_method="credit_sale"></button>
                @endif
                @if (!empty($payment_types) && array_key_exists('bank_transfer', $payment_types))
                    <button type="button" class="vspos-pay-quick-btn vspos-pay-quick--bank pos-express-finalize" data-pay_method="bank_transfer"></button>
                @endif
            </div>
        </div>
    </div>
</div>

@php
    $__comm_modules_touch = $enabled_modules ?? session('business.enabled_modules', []);
    if (! is_array($__comm_modules_touch)) {
        $__comm_modules_touch = [];
    }
    // Keep in sync with top-bar buttons (same flag as $__touch_comm above)
    $__touch_comm_modals = !empty($__touch_comm)
        || in_array('communication', $__comm_modules_touch, true);
@endphp
@if(in_array('mobile_shop', $__comm_modules_touch, true))
    @include('sale_pos.partials.mobile_reload_modal')
    @include('sale_pos.partials.mobile_repair_payment_modal')
@endif
@if($__touch_comm_modals)
    @include('sale_pos.partials.communication_modal')
@endif

@include('sale_pos.partials.touch_qty_modal')
