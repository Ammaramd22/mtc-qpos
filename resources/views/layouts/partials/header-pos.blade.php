<!-- default value -->
@php
    $is_pop_layout = !empty($is_pop_layout) || (!empty($is_pop)) || (request()->segment(1) === 'purchase-pos');
    $go_back_url = $is_pop_layout
        ? action([\App\Http\Controllers\PurchaseController::class, 'index'])
        : action([\App\Http\Controllers\SellPosController::class, 'index']);
    $transaction_sub_type = '';
    $view_suspended_sell_url = action([\App\Http\Controllers\SellController::class, 'index']) . '?suspended=1';
    $pos_redirect_url = $is_pop_layout
        ? action([\App\Http\Controllers\PurchasePosController::class, 'create'])
        : action([\App\Http\Controllers\SellPosController::class, 'create']);

    // Determine income masking state for connection indicator
    $isMaskMode = false;
    $business_id = session('user.business_id');
    if ($business_id) {
        $biz = \App\Business::find($business_id);
        $cs = !empty($biz->common_settings) ? $biz->common_settings : [];
        $maskingEnabled = !empty($cs['enable_commission_masking']);
        $isAdmin = auth()->user() && (auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#'.$business_id));
        $icMode = session('ic_mode', false);
        if ($maskingEnabled && !$isAdmin && !$icMode) {
            $isMaskMode = true;
        }
    }
@endphp

@if (!empty($pos_module_data))
    @foreach ($pos_module_data as $key => $value)
        @php
            if (!empty($value['go_back_url'])) {
                $go_back_url = $value['go_back_url'];
            }

            if (!empty($value['transaction_sub_type'])) {
                $transaction_sub_type = $value['transaction_sub_type'];
                $view_suspended_sell_url .= '&transaction_sub_type=' . $transaction_sub_type;
                $pos_redirect_url .= '?sub_type=' . $transaction_sub_type;
            }
        @endphp
    @endforeach
@endif
<input type="hidden" name="transaction_sub_type" id="transaction_sub_type" value="{{ $transaction_sub_type }}">
@inject('request', 'Illuminate\Http\Request')
<div class="col-md-12 no-print pos-header">
    <button type="button" id="pos_mnav_backdrop" class="pos-mnav-backdrop" hidden aria-label="Close menu"></button>
    <input type="hidden" id="pos_redirect_url" value="{{ $pos_redirect_url }}">
    <div
        class="pos-header-shell tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-between tw-rounded-xl tw-mx-0 tw-mt-1 tw-mb-0 md:tw-mb-0" style="padding: 4px 12px !important; min-height: 0;">
        <div class="tw-w-full md:tw-w-auto md:tw-flex-1 tw-min-w-0">
            <div class="pos-header-meta tw-flex tw-items-center tw-gap-2">
                {{-- Brand mark (desktop + mobile) --}}
                <a href="{{ route('home') }}" class="pos-brand-chip tw-flex tw-items-center tw-gap-2 tw-shrink-0" title="QPOS Home">
                    <img src="{{ asset('img/qpos-icon.png') }}" alt="QPOS" class="pos-mobile-logo">
                    <span class="pos-brand-copy">
                        <span class="pos-brand-text tw-hidden md:tw-inline">{{ $is_pop_layout ? 'POP' : 'QPOS' }}</span>
                        <span class="pos-brand-sub">{{ $is_pop_layout ? __('lang_v1.point_of_purchase') : 'Retail POS' }}</span>
                    </span>
                </a>
                @if ($is_pop_layout)
                    <span class="pos-status-pill pos-status-pill--ok tw-hidden md:tw-inline-flex" title="@lang('lang_v1.point_of_purchase')">
                        @lang('lang_v1.point_of_purchase')
                    </span>
                @endif

                {{-- Location selector only when switching is needed (hidden otherwise) --}}
                @if (empty($transaction->location_id))
                    @if (count($business_locations) > 1)
                        <div class="pos-location-wrap pos-location-wrap--compact">
                            {!! Form::select(
                                'select_location_id',
                                $business_locations,
                                $default_location->id ?? null,
                                ['class' => 'form-control input-sm', 'id' => 'select_location_id', 'required', 'autofocus', 'title' => __('sale.location')],
                                $bl_attributes,
                            ) !!}
                        </div>
                    @else
                        @php
                            $__loc_default_accounts = $default_location->default_payment_accounts ?? '';
                            if (is_array($__loc_default_accounts)) {
                                $__loc_default_accounts = json_encode($__loc_default_accounts);
                            }
                        @endphp
                        <select id="select_location_id" name="select_location_id" class="hide" required>
                            <option value="{{ $default_location->id ?? '' }}"
                                selected
                                data-receipt_printer_type="{{ !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser' }}"
                                data-default_payment_accounts="{{ $__loc_default_accounts }}"
                                data-default_price_group="{{ $default_location->selling_price_group_id ?? '' }}">
                                {{ $default_location->name ?? '' }}
                            </option>
                        </select>
                    @endif
                @endif

                <div class="pos-clock-chip tw-hidden md:tw-flex tw-items-center tw-gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    <span class="curr_datetime">{{ @format_datetime('now') }}</span>
                    <button type="button" class="pos-kbd-hint hover-q" aria-label="Keyboard shortcuts"
                        data-container="body" data-toggle="popover" data-placement="bottom"
                        data-content="@include('sale_pos.partials.keyboard_shortcuts_details')"
                        data-html="true" data-trigger="hover" title="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M2 6m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z"/><path d="M6 10l0 .01"/><path d="M10 10l0 .01"/><path d="M14 10l0 .01"/><path d="M18 10l0 .01"/><path d="M6 14l0 .01"/><path d="M18 14l0 .01"/><path d="M10 14l4 0"/></svg>
                    </button>
                </div>

                <div class="pos-status-cluster tw-flex tw-items-center tw-gap-1.5">
                    @if ($isMaskMode)
                        <span class="pos-status-pill pos-status-pill--warn tw-hidden md:tw-inline-flex" title="Income masking active">
                            <span class="pos-status-dot"></span>
                            LOW
                        </span>
                    @else
                        <span class="pos-status-pill pos-status-pill--ok tw-hidden md:tw-inline-flex" title="Real values showing">
                            <span class="pos-status-dot"></span>
                            HIGH
                        </span>
                    @endif

                    @php
                        if (!isset($wholesale_enabled)) {
                            $wholesale_enabled = class_exists(\App\Utils\WholesaleUtil::class)
                                ? \App\Utils\WholesaleUtil::isEnabled($business_id)
                                : false;
                        }
                        if ($wholesale_enabled && empty($wholesale_price_group_id) && !empty($business_id)
                            && class_exists(\App\Utils\WholesaleUtil::class)) {
                            $wholesale_price_group_id = \App\Utils\WholesaleUtil::getPriceGroupId((int) $business_id, true);
                        }
                        $__wh_pg = !empty($wholesale_price_group_id) ? (int) $wholesale_price_group_id : null;
                        $__wh_on = false;
                        if ($wholesale_enabled && $__wh_pg) {
                            if (!empty($transaction->selling_price_group_id)) {
                                $__wh_on = (int) $transaction->selling_price_group_id === $__wh_pg;
                            } elseif (!empty($default_price_group_id)) {
                                $__wh_on = (int) $default_price_group_id === $__wh_pg;
                            }
                        }
                    @endphp
                    @if(!$is_pop_layout && $wholesale_enabled && $__wh_pg)
                        <label class="pos-wholesale-switch {{ $__wh_on ? 'is-on' : '' }}" title="@lang('lang_v1.retail_wholesale_toggle_help')">
                            <span class="pos-wholesale-switch-label" id="pos_wholesale_label">
                                {{ $__wh_on ? __('lang_v1.wholesale') : __('lang_v1.retail') }}
                            </span>
                            <input type="checkbox"
                                id="pos_wholesale_switch"
                                class="pos-wholesale-toggle-input"
                                data-wholesale-group="{{ $__wh_pg }}"
                                data-label-retail="@lang('lang_v1.retail')"
                                data-label-wholesale="@lang('lang_v1.wholesale')"
                                @if($__wh_on) checked @endif>
                            <span class="pos-wholesale-switch-track" aria-hidden="true">
                                <span class="pos-wholesale-switch-knob"></span>
                            </span>
                        </label>
                    @endif

                    @php
                        $offlineEnabled = !empty($biz) && !empty($biz->common_settings['enable_offline_billing'] ?? 0);
                    @endphp
                    @if($offlineEnabled)
                        <span id="offline-status-indicator" class="online pos-status-pill pos-status-pill--live tw-cursor-pointer" title="Online">
                            <span class="pos-status-dot"></span>
                            Online
                        </span>
                        <span id="offline-pending-badge" class="pos-pending-badge tw-hidden md:tw-inline-flex" style="display:none;"></span>
                        <button type="button" id="vspos_offline_sync_open" title="@lang('lang_v1.offline_sync_title')"
                            class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-gap-1 tw-rounded-md tw-h-10 tw-px-2 tw-text-gray-600 active:tw-scale-95 tw-transition-transform">
                            <i class="fa fa-cloud-upload-alt tw-text-[#2563eb]"></i>
                            <span class="tw-hidden md:tw-inline tw-text-xs tw-font-semibold">@lang('lang_v1.offline_sync_btn')</span>
                            <span class="vspos-offline-sync-count label label-warning" style="display:none;margin:0;"></span>
                        </button>
                    @endif
                </div>

                @if (empty($pos_settings['hide_product_suggestion']))
                    <button type="button" title="{{ __('lang_v1.view_products') }}" data-placement="bottom"
                        class="pos-mobile-icon-btn tw-cursor-pointer tw-flex tw-items-center tw-justify-center tw-rounded-md tw-w-10 tw-h-10 btn-modal tw-ml-auto md:tw-hidden active:tw-scale-95 tw-transition-transform"
                        data-toggle="modal" data-target="#mobile_product_suggestion_modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shopping-bag" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"/><path d="M9 11v-5a3 3 0 0 1 6 0v5"/></svg>
                    </button>
                @endif

                <button type="button" class="qpos-theme-toggle pos-mobile-icon-btn tw-flex tw-items-center tw-justify-center md:tw-hidden" title="Switch to light theme" aria-label="Switch to light theme">
                    <i class="fi fi-rr-sun qpos-theme-toggle-icon"></i>
                </button>

                <button type="button" class="vspos-mpos-header-cart" id="vspos_mpos_header_cart" aria-label="Open cart">
                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                    <span class="vspos-mpos-header-badge vspos-mpos-line-count">0</span>
                </button>

                <button type="button" class="pos-mobile-icon-btn pos-hamburger tw-flex tw-items-center tw-justify-center md:tw-hidden @if(empty($pos_settings['hide_product_suggestion'])) tw-ml-0 @else tw-ml-auto @endif"
                    aria-label="Menu" aria-expanded="false" aria-controls="pos_header_more_options"
                    onclick="window.posMnavSet && posMnavSet(!document.getElementById('pos_header_more_options').classList.contains('pos-more-open'))">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-menu-2" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6l16 0"/><path d="M4 12l16 0"/><path d="M4 18l16 0"/></svg>
                </button>

            </div>
        </div>

        <style>
            .pos-brand-copy { display: contents; }
            .pos-brand-sub { display: none; }
            .vspos-mpos-header-cart { display: none; }
            @media (min-width: 768px) {
                #pos_header_more_options {
                    gap: 4px !important;
                    flex-wrap: nowrap !important;
                    overflow: visible !important;
                    max-width: 100% !important;
                    justify-content: flex-end !important;
                }
                .pos-header-shell {
                    overflow: visible !important;
                }
                .vspos-header-last-bill {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 4px 8px 4px 10px;
                    border-radius: 10px;
                    background: rgba(245, 197, 24, 0.08);
                    border: 1px solid rgba(245, 197, 24, 0.28);
                    max-width: 260px;
                }
                .vspos-hlb-meta {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                    line-height: 1.15;
                }
                .vspos-hlb-kicker {
                    font-size: 9px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: .04em;
                    color: #a3a3a3;
                }
                .vspos-hlb-invoice {
                    font-size: 11px;
                    font-weight: 700;
                    color: #f5f5f5;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .vspos-hlb-amount {
                    font-size: 13px;
                    font-weight: 800;
                    color: #f5c518;
                    font-variant-numeric: tabular-nums;
                }
                #pos_header_more_options > a.tw-h-10,
                #pos_header_more_options > button.tw-h-10,
                #pos_header_more_options > a.pos-header-action,
                #pos_header_more_options > button.pos-header-action {
                    width: 40px !important;
                    height: 40px !important;
                    min-width: 40px !important;
                    max-width: 40px !important;
                    padding: 0 !important;
                    flex-shrink: 0 !important;
                    overflow: hidden !important;
                    box-sizing: border-box !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                #pos_header_more_options > a.tw-h-10 strong,
                #pos_header_more_options > button.tw-h-10 strong,
                #pos_header_more_options > a.pos-header-action strong,
                #pos_header_more_options > button.pos-header-action strong {
                    margin: 0 !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                #pos_header_more_options > a.tw-h-auto,
                #pos_header_more_options > button.tw-h-auto {
                    width: auto !important;
                    min-width: auto !important;
                    max-width: none !important;
                    padding: 0 14px !important;
                    height: 40px !important;
                    white-space: nowrap !important;
                    flex-shrink: 0 !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
            }
        </style>
        <div class="tw-w-full md:tw-w-auto md:tw-flex-1 md:tw-min-w-0 !tw-p-0 tw-flex tw-items-center md:tw-justify-end tw-gap-2 md:tw-gap-1 tw-flex-col md:tw-flex-row tw-hidden md:tw-flex"
            id="pos_header_more_options">
            @php
                $mnavUser = auth()->user();
                $mnavInitials = strtoupper(mb_substr((string) ($mnavUser->first_name ?? ''), 0, 1).mb_substr((string) ($mnavUser->last_name ?? ''), 0, 1));
                $mnavName = trim(($mnavUser->first_name ?? '').' '.($mnavUser->last_name ?? ''));
                $mnavRole = method_exists($mnavUser, 'getRoleNameAttribute') ? $mnavUser->role_name : '';
                $mnavCanSettings = $mnavUser->can('business_settings.access')
                    || (method_exists($mnavUser, 'isSoftwareOwner') && $mnavUser->isSoftwareOwner());
            @endphp
            <div class="pos-mnav-only pos-mnav-head">
                <a href="{{ route('home') }}" class="pos-mnav-brand">
                    <img src="{{ asset('img/qpos-icon.png') }}" alt="QPOS">
                    <span>
                        <span class="pos-mnav-brand-name">{{ $is_pop_layout ? 'POP' : 'QPOS' }}</span>
                        <span class="pos-mnav-brand-sub">{{ $is_pop_layout ? __('lang_v1.point_of_purchase') : 'Retail POS' }}</span>
                    </span>
                </a>
                <button type="button" class="pos-mnav-close" aria-label="Close" onclick="window.posMnavSet && posMnavSet(false)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                </button>
            </div>
            <a href="{{ $pos_redirect_url }}" class="pos-mnav-only pos-mnav-pos pos-mnav-highlight" title="POS">
                <strong>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tw-text-[#F5C518]"><path d="M5 12l-2 0l9-9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/><path d="M10 12h4v4h-4z"/></svg>
                    <span class="pos-mnav-copy">
                        <span class="pos-mnav-title">{{ $is_pop_layout ? 'POP' : 'POS' }}</span>
                        <span class="pos-mnav-sub">{{ $is_pop_layout ? 'Start a purchase' : 'Start a new sale' }}</span>
                    </span>
                    <svg class="pos-mnav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6l-6 6"/></svg>
                </strong>
            </a>
            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-sales">Sales</div>
            <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
            <button type="button" class="qpos-theme-toggle pos-header-action tw-hidden md:tw-flex tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-h-10" title="Switch to light theme" aria-label="Switch to light theme">
                <i class="fi fi-rr-sun qpos-theme-toggle-icon pos-fi-icon"></i>
            </button>
            {{-- ===== Navigation ===== --}}
            <a href="{{ $go_back_url }}" id="pos_go_back_btn" title="{{ __('lang_v1.go_back') }}"
                class="pos-header-action tw-cursor-pointer tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 active:tw-scale-95 tw-transition-transform pull-right">
                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-left tw-text-[#009EE4] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-9 6l9 6"/></svg>
                    <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('lang_v1.go_back') }}</span><span class="pos-mnav-sub">Return to previous screen</span></span>
                </strong>
            </a>
            <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>

            @include('sale_pos.partials.mobile_shop_header_buttons')
            @include('sale_pos.partials.vehicle_history_header_buttons')
            @include('sale_pos.partials.communication_header_buttons')
            @include('sale_pos.partials.pos_stock_alert_dropdown')

            @if ($is_pop_layout)
                {{-- ===== Purchase / POP actions (mirror POS bills hub) ===== --}}
                <div class="vspos-header-last-bill tw-hidden sm:tw-flex tw-items-center tw-gap-2 tw-mr-1" id="vspos_header_last_bill">
                    <div class="vspos-hlb-meta">
                        <span class="vspos-hlb-kicker">Last purchase</span>
                        @if(!empty($last_sell))
                            <span class="vspos-hlb-invoice vspos-hub-invoice"># {{ $last_sell->invoice_no }}</span>
                            <span class="vspos-hlb-amount vspos-hub-amount">@format_currency($last_sell->final_total)</span>
                        @else
                            <span class="vspos-hlb-invoice vspos-hub-invoice">—</span>
                            <span class="vspos-hlb-amount vspos-hub-amount"></span>
                        @endif
                    </div>
                    <button type="button"
                        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md tw-h-10 tw-px-3 tw-text-gray-600 active:tw-scale-95 tw-transition-transform js-print-last-bill"
                        data-id="{{ !empty($last_sell) ? $last_sell->id : '' }}"
                        @if(empty($last_sell)) disabled @endif
                        title="Reprint last purchase">
                        <strong class="tw-inline-flex tw-items-center tw-gap-1.5 !tw-m-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer tw-text-[#00935F]" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/></svg>
                            <span class="tw-hidden lg:tw-inline">Reprint</span>
                        </strong>
                    </button>
                </div>
                <button type="button"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
                    data-toggle="modal" data-target="#recent_transactions_modal"
                    id="recent-transactions"
                    title="Purchase list — last amount, reprint &amp; details">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-2 tw-text-[#F5C518] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/><path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"/><path d="M12 7v1m0 8v1"/></svg>
                        <span class="tw-inline md:tw-hidden">Purchases</span>
                    </strong>
                </button>
                <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}"
                    title="{{ __('purchase.purchases') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right">
                    <strong class="!tw-m-3">
                        <i class="fas fa-list tw-text-[#00935F]"></i>
                        <span class="tw-inline md:tw-hidden">{{ __('purchase.list_purchase') }}</span>
                    </strong>
                </a>
                @can('sell.create')
                    <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}"
                        title="@lang('sale.pos_sale')"
                        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right">
                        <strong class="!tw-m-3">
                            <i class="fas fa-cash-register tw-text-[#009EE4]"></i>
                            <span class="tw-inline md:tw-hidden">@lang('sale.pos_sale')</span>
                        </strong>
                    </a>
                @endcan
            @else
            {{-- ===== Sale Operations ===== --}}
            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-returns">Returns &amp; Exchanges</div>
            @if (auth()->user()->can('access_sell_return') || auth()->user()->can('access_own_sell_return'))
                <a href="{{ action([\App\Http\Controllers\SellReturnController::class, 'standaloneCreate']) }}"
                    id="standalone_return" title="@lang('lang_v1.standalone_return')"
                    onclick="window.open(this.href, 'standalone_return', 'width='+screen.width+',height='+screen.height+',top=0,left=0'); return false;"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-refund tw-text-[#00935F] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/><path d="M15 14v-2a2 2 0 0 0 -2 -2h-4l2 -2m0 4l-2 -2"/></svg>
                        <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('lang_v1.standalone_return') }}</span><span class="pos-mnav-sub">Return without sale</span></span>
                    </strong>
                </a>
            @endif

            <button type="button"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right pull-right popover-default"
                id="return_sale" title="@lang('lang_v1.sell_return')" data-toggle="popover" data-trigger="click"
                data-content='<div class="vspos-header-pop"><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_sell_return_invoice_no"></div><div class="w-100 text-center" style="margin-top:8px;"><button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm" id="send_for_sell_return">@lang('lang_v1.send')</button></div>'
                data-html="true" data-placement="bottom">
                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-back-up tw-text-[#EF4B53] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>
                    <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('lang_v1.sell_return') }}</span><span class="pos-mnav-sub">Process sales return</span></span>
                </strong>
            </button>

            @if (auth()->user()->can('access_sell_return') || auth()->user()->can('access_own_sell_return'))
            <button type="button"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right popover-default"
                id="pos_exchange" title="@lang('lang_v1.pos_exchange')" data-toggle="popover" data-trigger="click"
                data-content='<div class="vspos-header-pop"><p class="vspos-pop-help" style="font-size:11px;margin:0 0 8px;line-height:1.35;">@lang('lang_v1.pos_exchange_help_short')</p><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_pos_exchange_invoice_no"></div><div class="w-100 text-center" style="margin-top:8px;"><button type="button" class="tw-dw-btn tw-dw-btn-warning tw-dw-btn-sm" id="send_for_pos_exchange">@lang('lang_v1.continue')</button></div>'
                data-html="true" data-placement="bottom">
                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-replace tw-text-[#F59E0B] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 9 -9a9.75 9.75 0 0 0 -6.74 2.74l-2.26 2.26"/><path d="M3 5v4h4"/><path d="M21 12a9 9 0 1 0 -9 9a9.75 9.75 0 0 0 6.74 -2.74l2.26 -2.26"/><path d="M21 19v-4h-4"/></svg>
                    <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('lang_v1.pos_exchange') }}</span><span class="pos-mnav-sub">Exchange products</span></span>
                </strong>
            </button>
            @endif

            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-tx">Transactions</div>
            <button type="button" id="view_suspended_sales" title="{{ __('lang_v1.view_suspended_sales') }}"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform btn-modal pull-right"
                data-container=".view_modal" data-href="{{ $view_suspended_sell_url }}">
                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-player-pause tw-text-[#A5ADBB] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"/><path d="M14 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"/></svg>
                    <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('lang_v1.view_suspended_sales') }}</span><span class="pos-mnav-sub">Resume suspended bills</span></span>
                </strong>
            </button>

            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                <button type="button"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
                    data-toggle="modal" data-target="#recent_transactions_modal"
                    id="recent-transactions"
                    title="Bills & Reprint — last bill, reprint, recent transactions">
                        <strong class="!tw-m-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer tw-text-[#00935F] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/></svg>
                            <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">Bills &amp; Reprint</span><span class="pos-mnav-sub">Reprint invoices &amp; bills</span></span>
                        </strong>
                </button>
            @endif
            @endif

            @if (auth()->user()->can('view_cash_register') || auth()->user()->can('close_cash_register'))
                <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
            @endif

            {{-- ===== Cash Register ===== --}}
            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-cash">Cash Management</div>
            @can('view_cash_register')
                <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform btn-modal pull-right"
                    data-container=".register_details_modal"
                    data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails']) }}">

                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-briefcase tw-text-[#00935F] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/><path d="M12 12l0 .01"/><path d="M3 13a20 20 0 0 0 18 0"/></svg>
                        <span class="tw-inline md:tw-hidden pos-mnav-copy"><span class="pos-mnav-title">{{ __('cash_register.register_details') }}</span><span class="pos-mnav-sub">View cash register details</span></span>
                    </strong>
                </button>
            @endcan

            @can('close_cash_register')
                <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform btn-modal pull-right"
                    data-container=".close_register_modal"
                    data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister']) }}">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-square-rounded-x tw-text-[#EF4B53] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10l4 4m0 -4l-4 4"/><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z"/></svg>
                        <span class="tw-inline md:tw-hidden">{{ __('cash_register.close_register') }}</span>
                    </strong>
                </button>
            @endcan

            @if (!$is_pop_layout)
            @can('expense.add')
                <button type="button" title="{{ __('expense.add_expense') }}" data-placement="bottom"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 active:tw-scale-95 tw-transition-transform tw-rounded-md btn-modal pull-right"
                    id="add_expense">
                    <strong class="tw-inline-flex tw-items-center tw-gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-minus" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M9 12l6 0"/></svg>@lang('expense.add_expense')</strong>
                </button>
            @endcan
            @endif

            @if (!$is_pop_layout)
            @if (!empty($pos_settings['inline_service_staff']) || in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules))
                <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
            @endif

            {{-- ===== Service Staff ===== --}}
            @if (!empty($pos_settings['inline_service_staff']))
                <button type="button" id="show_service_staff_availability"
                    title="{{ __('lang_v1.service_staff_availability') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
                    data-container=".view_modal"
                    data-href="{{ action([\App\Http\Controllers\SellPosController::class, 'showServiceStaffAvailibility']) }}">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users tw-text-[#646EE4] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                        <span class="tw-inline md:tw-hidden">{{ __('lang_v1.service_staff_availability') }}</span>
                    </strong>
                </button>
            @endif

            @if (
                !empty($pos_settings['inline_service_staff']) ||
                    (in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules)))
                <button type="button"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right popover-default"
                    id="service_staff_replacement" title="{{ __('restaurant.service_staff_replacement') }}"
                    data-toggle="popover" data-trigger="click"
                    data-content='<div class="m-8"><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_sell_service_staff_invoice_no"></div><div class="w-100 text-center"><button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error" id="send_for_sercice_staff_replacement">@lang('lang_v1.send')</button></div>'
                    data-html="true" data-placement="bottom">

                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-plus tw-text-[#646EE4] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M16 19h6"/><path d="M19 16v6"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/></svg>
                        <span class="tw-inline md:tw-hidden">{{ __('restaurant.service_staff_replacement') }}</span>
                    </strong>
                </button>
            @endif
            @endif

            <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>

            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-more">More</div>
            {{-- ===== Tools ===== --}}
            <button title="@lang('lang_v1.calculator')" id="btnCalculator" type="button"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right popover-default"
                data-toggle="popover" data-trigger="click" data-content='@include('layouts.partials.calculator')' data-html="true"
                data-placement="bottom">


                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calculator tw-text-[#00935F] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M8 14l0 .01"/><path d="M12 14l0 .01"/><path d="M16 14l0 .01"/><path d="M8 17l0 .01"/><path d="M12 17l0 .01"/><path d="M16 17l0 .01"/></svg>
                    <span class="tw-inline md:tw-hidden">{{ __('lang_v1.calculator') }}</span>
                </strong>
            </button>

            <button type="button" title="{{ __('lang_v1.full_screen') }}"
                class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
                id="full_screen">
                <strong class="!tw-m-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-maximize tw-text-[#646EE4] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8v-2a2 2 0 0 1 2 -2h2"/><path d="M4 16v2a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v2"/><path d="M16 20h2a2 2 0 0 0 2 -2v-2"/></svg>
                    <span class="tw-inline md:tw-hidden">Full Screen</span>
                </strong>
            </button>

            @if (!$is_pop_layout && !empty($pos_settings['customer_display_screen']))
                <a href="{{route('pos_display')}}" id="customer_display_screen" rel="noopener"
                    onclick="(function(a){ try { if (window.name === 'customer_display') { window.name = 'vspos_pos'; } } catch(e) {} window.vsposCustomerDisplay = window.open(a.href, 'customer_display'); try { sessionStorage.setItem('vspos_cd_open', '1'); localStorage.setItem('vspos_cd_open', '1'); } catch(e) {} })(this); return false;"
                    title="{{ __('lang_v1.customer_display_screen') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-desktop tw-text-[#646EE4] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1v-10z"/><path d="M7 20h10"/><path d="M9 16v4"/><path d="M15 16v4"/></svg>
                        <span class="tw-inline md:tw-hidden">{{ __('lang_v1.customer_display_screen') }}</span>
                    </strong>
                </a>
            @endif

            @if (!$is_pop_layout && !empty($pos_settings['enable_vfd_customer_display']))
                <button type="button" id="vfd_customer_display_btn"
                    title="{{ __('lang_v1.vfd_connect') }}"
                    class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right">
                    <strong class="!tw-m-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-analytics tw-text-[#f5c518] tw-inline-block" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z"/><path d="M7 20l10 0"/><path d="M9 16l0 4"/><path d="M15 16l0 4"/><path d="M8 12l3 -3l2 2l3 -3"/></svg>
                        <span class="tw-inline md:tw-hidden vfd-btn-label">{{ __('lang_v1.vfd_connect') }}</span>
                    </strong>
                </button>
                <span id="vfd_display_config" class="hide"
                    data-enabled="1"
                    data-protocol="{{ $pos_settings['vfd_display_protocol'] ?? 'numeric' }}"
                    data-baud="{{ $pos_settings['vfd_display_baud'] ?? '9600' }}"
                    data-chars="{{ $pos_settings['vfd_display_chars'] ?? '8' }}"></span>
            @endif


            @if (!$is_pop_layout && ((Module::has('Repair') && $transaction_sub_type != 'repair') || (in_array('pos_sale', $enabled_modules) && !empty($transaction_sub_type) && auth()->user()->can('sell.create'))))
                <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
            @endif

            {{-- ===== Actions ===== --}}
            @if (!$is_pop_layout && Module::has('Repair') && $transaction_sub_type != 'repair')
                @include('repair::layouts.partials.pos_header')
            @endif

            @if (!$is_pop_layout && in_array('pos_sale', $enabled_modules) && !empty($transaction_sub_type))
                @can('sell.create')
                    <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}"
                        title="@lang('sale.pos_sale')"
                        class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-w-auto tw-h-auto tw-py-1 tw-px-4 active:tw-scale-95 tw-transition-transform tw-rounded-md pull-right">
                        <strong class="tw-inline-flex tw-items-center tw-gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-layout-grid tw-text-[#00935F]" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/></svg>@lang('sale.pos_sale')</strong>
                    </a>
                @endcan
            @endif

            <div class="pos-mnav-only pos-mnav-label pos-mnav-label-account">Account</div>
            @if($mnavCanSettings)
                <a href="{{ action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) }}" class="pos-mnav-only pos-mnav-settings" title="@lang('business.settings')">
                    <strong>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tw-text-[#A5ADBB]"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94-1.543.826-3.31 2.37-2.37c1 .608 2.296.07 2.572-1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/></svg>
                        <span class="pos-mnav-copy"><span class="pos-mnav-title">Settings</span></span>
                    </strong>
                </a>
            @endif
            <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'logout']) }}" class="pos-mnav-only pos-mnav-logout" title="@lang('lang_v1.sign_out')">
                <strong>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tw-text-[#EF4B53]"><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/></svg>
                    <span class="pos-mnav-copy"><span class="pos-mnav-title">Logout</span></span>
                </strong>
            </a>
            <a href="{{ action([\App\Http\Controllers\UserController::class, 'getProfile']) }}" class="pos-mnav-only pos-mnav-profile pos-mnav-highlight" title="{{ $mnavName }}">
                <strong>
                    <span class="pos-mnav-avatar">{{ $mnavInitials !== '' ? $mnavInitials : 'U' }}</span>
                    <span class="pos-mnav-copy">
                        <span class="pos-mnav-title">{{ $mnavName !== '' ? $mnavName : $mnavUser->username }}</span>
                        @if($mnavRole !== '')
                            <span class="pos-mnav-sub">{{ $mnavRole }}</span>
                        @endif
                    </span>
                    <svg class="pos-mnav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6l-6 6"/></svg>
                </strong>
            </a>

        </div>
    </div>
</div>

<div class="modal fade" id="service_staff_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>
<script>
window.posMnavSet = function (open) {
    var menu = document.getElementById('pos_header_more_options');
    var button = document.querySelector('.pos-hamburger');
    var backdrop = document.getElementById('pos_mnav_backdrop');
    if (!menu || window.matchMedia('(min-width: 768px)').matches) {
        return;
    }
    menu.classList.toggle('tw-hidden', !open);
    menu.classList.toggle('pos-more-open', open);
    if (button) {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.classList.toggle('is-open', open);
    }
    if (backdrop) {
        backdrop.hidden = !open;
        backdrop.classList.toggle('is-open', open);
    }
    document.body.classList.toggle('pos-mnav-open', open);
};
document.addEventListener('click', function (event) {
    var backdrop = document.getElementById('pos_mnav_backdrop');
    if (backdrop && event.target === backdrop) {
        window.posMnavSet(false);
    }
});
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        window.posMnavSet(false);
    }
});
document.addEventListener('click', function (event) {
    var menu = document.getElementById('pos_header_more_options');
    if (!menu || !menu.classList.contains('pos-more-open')) {
        return;
    }
    var item = event.target.closest('#pos_header_more_options > a, #pos_header_more_options > button, #pos_header_more_options .btn-modal');
    if (!item || item.closest('.popover') || item.getAttribute('data-toggle') === 'popover' || item.classList.contains('popover-default') || item.id === 'vspos_stock_alert_btn') {
        return;
    }
    window.posMnavSet(false);
});
</script>
