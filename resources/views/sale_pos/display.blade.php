@extends('layouts.app')

@section('title', __('lang_v1.customer_display_screen'))

@section('content')
@php
    $vsoft_logo_path = asset('img/vsoft_logo.png');
    $vsoft_footer_text = config('app.brand_footer_html');
    $biz_name = $business_name ?? session('business.name', '');
    $biz_logo = !empty($business_logo) ? asset('uploads/business_logos/' . $business_logo) : null;
    $app_brand = config('app.name', 'QPOS');
    $legacy_brand_names = ['vsoftpos', 'vsoft pos', 'vsoft'];
    $normalized_biz = strtolower(trim($biz_name));
    $is_legacy_brand = in_array($normalized_biz, $legacy_brand_names, true);
    $cd_subtitle = !empty($pos_settings['display_screen_heading'])
        ? $pos_settings['display_screen_heading']
        : ((!$is_legacy_brand && strcasecmp(trim($biz_name), $app_brand) !== 0) ? $biz_name : '');
    $display_view = ($display_view ?? request()->get('view')) === 'marketing' ? 'marketing' : 'cart';
    $is_marketing_view = $display_view === 'marketing';

    $carousel_images = [];
    for ($i = 1; $i <= 10; $i++) {
        if (!empty($pos_settings['carousel_image_' . $i])) {
            $carousel_images[] = $pos_settings['carousel_image_' . $i];
        }
    }
@endphp

<script>document.documentElement.setAttribute('data-vspos-cd', 'dark');</script>
<input type="hidden" id="cd_default_customer_id" value="{{ $walk_in_customer['id'] ?? '' }}">

<div class="vspos-cd-wrapper pos_sell {{ $is_marketing_view ? 'is-marketing' : 'is-cart' }}">

    <header class="vspos-cd-header">
        <div class="vspos-cd-brand">
            @if($biz_logo)
                <img src="{{ $biz_logo }}" alt="{{ $biz_name }}">
            @endif
            <div>
                <div class="vspos-cd-brand-name">{{ $app_brand }}</div>
                @if(!empty($cd_subtitle))
                    <div class="vspos-cd-heading">{!! $cd_subtitle !!}</div>
                @endif
            </div>
        </div>
        <div class="vspos-cd-header-meta">
            <span class="vspos-cd-live">@lang('lang_v1.live')</span>
            <span class="vspos-cd-clock" id="cd_clock"></span>
            <button type="button" class="vspos-cd-fs-btn" id="full_screen">
                <i class="fa fa-expand"></i> @lang('lang_v1.fullscreen')
            </button>
        </div>
    </header>

    @php
        $enable_cd_enter_number = ! $is_marketing_view
            && (!isset($pos_settings['enable_cd_enter_number']) || !empty($pos_settings['enable_cd_enter_number']));
    @endphp

    @if($enable_cd_enter_number)
    <div class="vspos-cd-membership">
        <div class="vspos-cd-membership-text">
            <strong>@lang('lang_v1.cd_link_customer_title')</strong>
            <span>@lang('lang_v1.cd_link_customer_hint')</span>
        </div>
        <div class="vspos-cd-phone-form">
            <input type="tel" id="cd_customer_phone" inputmode="numeric" autocomplete="tel"
                placeholder="@lang('lang_v1.cd_phone_placeholder')" maxlength="20" readonly>
            <button type="button" class="vspos-cd-link-btn" id="cd_link_customer">
                @lang('lang_v1.cd_enter_number')
            </button>
        </div>
        <div class="vspos-cd-link-status is-info" id="cd_link_status"></div>
    </div>
    @endif

    <div class="vspos-cd-body">

        @if(! $is_marketing_view)
        <div class="vspos-cd-main">
            <div class="vspos-cd-customer-bar">
                <i class="fas fa-user-circle"></i>
                <div class="customer_details">Walk-in Customer</div>
            </div>

            <div class="vspos-cd-stats">
                <div class="vspos-cd-stat">
                    <div class="vspos-cd-stat-lbl">@lang('sale.subtotal')</div>
                    <div class="vspos-cd-stat-sub"><span class="total_quantity">0</span> @lang('lang_v1.items')</div>
                    <div class="vspos-cd-stat-val price_total">0.00</div>
                </div>
                <div class="vspos-cd-stat vspos-cd-stat-savings">
                    <div class="vspos-cd-stat-lbl">@lang('lang_v1.cd_savings')</div>
                    <div class="vspos-cd-stat-sub">@lang('lang_v1.cd_discounts_offers')</div>
                    <div class="vspos-cd-stat-val" id="total_discount">0.00</div>
                </div>
                <div class="vspos-cd-stat vspos-cd-stat-total">
                    <div class="vspos-cd-stat-lbl">@lang('lang_v1.cd_total_payable')</div>
                    <div class="vspos-cd-stat-sub">&nbsp;</div>
                    <div class="vspos-cd-stat-val" id="total_payable">0.00</div>
                </div>
            </div>

            <span id="order_tax" style="display:none">0.00</span>
            <span id="shipping_charges_amount" style="display:none">0.00</span>

            <div class="vspos-cd-items-head">@lang('lang_v1.cd_scanned_items')</div>

            <div class="cd-table-wrap">
                <table id="pos_table">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('sale.qty')</th>
                            <th>@lang('sale.price_inc_tax')</th>
                            <th>@lang('sale.subtotal')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="vspos-cd-pay-strip">
                <div class="vspos-cd-pay-item">
                    <div class="lbl">@lang('lang_v1.paying')</div>
                    <div class="val total_paying display_currency" data-currency_symbol="true">0</div>
                </div>
                <div class="vspos-cd-pay-item">
                    <div class="lbl">@lang('lang_v1.change_return')</div>
                    <div class="val change_return_span display_currency" data-currency_symbol="true">0</div>
                </div>
                <div class="vspos-cd-pay-item">
                    <div class="lbl">@lang('lang_v1.balance')</div>
                    <div class="val balance_due balance-due display_currency" data-currency_symbol="true">0</div>
                </div>
            </div>
        </div>
        @endif

        <div class="vspos-cd-promo">
            @if(count($carousel_images))
                <div id="myCarousel" class="carousel slide" data-ride="carousel" data-interval="5000" style="width:100%;height:100%">
                    @if(count($carousel_images) > 1)
                        <ol class="carousel-indicators">
                            @foreach($carousel_images as $idx => $img)
                                <li data-target="#myCarousel" data-slide-to="{{ $idx }}"
                                    class="{{ $idx === 0 ? 'active' : '' }}"></li>
                            @endforeach
                        </ol>
                    @endif
                    <div class="carousel-inner" style="height:100%">
                        @foreach($carousel_images as $idx => $img)
                            <div class="item {{ $idx === 0 ? 'active' : '' }}" style="height:100%">
                                <img src="{{ url('uploads/carousel_images/' . $img) }}" alt="@lang('lang_v1.cd_promotion')">
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="vspos-cd-promo-empty">
                    <i class="fas fa-image"></i>
                    <p>@lang('lang_v1.cd_no_promo_images')</p>
                </div>
            @endif

            <div class="vspos-cd-just-scanned is-hidden" id="cd_just_scanned">
                <div class="js-label">@lang('lang_v1.cd_just_scanned')</div>
                <img id="cd_js_image" src="" alt="">
                <div class="js-name" id="cd_js_name"></div>
                <div class="js-price" id="cd_js_price"></div>
            </div>
        </div>

    </div>

    <footer class="vspos-cd-footer">
        <img src="{{ $vsoft_logo_path }}" alt="QPOS" onerror="this.style.display='none'">
        <span>{!! $vsoft_footer_text !!}</span>
    </footer>

    @if($enable_cd_enter_number)
    {{-- Touch numpad for phone entry --}}
    <div class="vspos-cd-numpad-backdrop" id="cd_numpad_backdrop" aria-hidden="true"></div>
    <div class="vspos-cd-numpad" id="cd_numpad" aria-hidden="true">
        <div class="vspos-cd-numpad-head">
            <span>@lang('lang_v1.cd_enter_phone_first')</span>
            <button type="button" class="vspos-cd-numpad-close" id="cd_numpad_close" aria-label="Close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="vspos-cd-numpad-display" id="cd_numpad_display">—</div>
        <div class="vspos-cd-numpad-grid">
            <button type="button" class="cd-nk" data-key="1">1</button>
            <button type="button" class="cd-nk" data-key="2">2</button>
            <button type="button" class="cd-nk" data-key="3">3</button>
            <button type="button" class="cd-nk" data-key="4">4</button>
            <button type="button" class="cd-nk" data-key="5">5</button>
            <button type="button" class="cd-nk" data-key="6">6</button>
            <button type="button" class="cd-nk" data-key="7">7</button>
            <button type="button" class="cd-nk" data-key="8">8</button>
            <button type="button" class="cd-nk" data-key="9">9</button>
            <button type="button" class="cd-nk cd-nk--action" data-key="back"><i class="fa fa-backspace"></i></button>
            <button type="button" class="cd-nk" data-key="0">0</button>
            <button type="button" class="cd-nk cd-nk--done" data-key="done"><i class="fa fa-check"></i></button>
        </div>
    </div>
    @endif

    {{-- Welcome popup when customer is found --}}
    <div class="vspos-cd-welcome-overlay" id="cd_welcome_overlay" aria-hidden="true">
        <div class="vspos-cd-welcome-card">
            <div class="vspos-cd-welcome-icon"><i class="fas fa-hand-sparkles"></i></div>
            <div class="vspos-cd-welcome-label">@lang('lang_v1.cd_welcome')</div>
            <div class="vspos-cd-welcome-name" id="cd_welcome_name"></div>
            <div class="vspos-cd-welcome-wish" id="cd_welcome_wish"></div>
        </div>
    </div>

    {{-- Sale complete (matches POS thank-you screen) --}}
    <div class="vspos-cd-sc-backdrop" id="cd_sale_complete_backdrop" aria-hidden="true"></div>
    <div class="vspos-cd-sc-modal" id="cd_sale_complete_modal" role="dialog" aria-labelledby="cd_sc_title" aria-hidden="true">
        <div class="vspos-cd-sc-card">
            <div class="vspos-cd-sc-icon-wrap">
                <div class="vspos-cd-sc-icon"><i class="fas fa-check"></i></div>
            </div>
            <div class="vspos-cd-sc-title" id="cd_sc_title">@lang('lang_v1.sc_sale_complete')</div>
            <div class="vspos-cd-sc-customer" id="cd_sc_customer_name"></div>
            <div class="vspos-cd-sc-wish" id="cd_sc_wish"></div>
        </div>
    </div>

    {{-- Thank you + emoji feedback after sale --}}
    <div class="vspos-cd-feedback-overlay" id="cd_feedback_overlay" aria-hidden="true">
        <div class="vspos-cd-feedback-card">
            <div class="vspos-cd-feedback-emoji-big">🛍️</div>
            <div class="vspos-cd-feedback-title" id="cd_thanks_title">@lang('lang_v1.cd_thank_you')</div>
            <div class="vspos-cd-feedback-name" id="cd_thanks_name"></div>
            <div class="vspos-cd-feedback-wish" id="cd_thanks_wish"></div>
            <div class="vspos-cd-feedback-prompt">@lang('lang_v1.cd_rate_experience')</div>
            <div class="vspos-cd-emoji-row" id="cd_emoji_row">
                <button type="button" class="cd-emoji-btn" data-emoji="😍" aria-label="Love it">😍</button>
                <button type="button" class="cd-emoji-btn" data-emoji="😊" aria-label="Happy">😊</button>
                <button type="button" class="cd-emoji-btn" data-emoji="👍" aria-label="Good">👍</button>
                <button type="button" class="cd-emoji-btn" data-emoji="😐" aria-label="Okay">😐</button>
                <button type="button" class="cd-emoji-btn" data-emoji="👎" aria-label="Poor">👎</button>
            </div>
        </div>
    </div>

    {{-- Next customer banner --}}
    <div class="vspos-cd-next-customer" id="cd_next_customer" aria-hidden="true">
        <i class="fas fa-arrow-down"></i>
        <span>@lang('lang_v1.cd_next_customer_please')</span>
        <i class="fas fa-arrow-down"></i>
    </div>

</div>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('css/customer-display.css?v=' . ($asset_v ?? config('constants.asset_version', time()))) }}">
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop

@section('javascript')
    @php
        $cd_welcome_wishes = [
            __('lang_v1.cd_wish_1'),
            __('lang_v1.cd_wish_2'),
            __('lang_v1.cd_wish_3'),
            __('lang_v1.cd_wish_4'),
        ];
        $cd_thanks_wishes = [
            __('lang_v1.cd_thanks_wish_1'),
            __('lang_v1.cd_thanks_wish_2'),
            __('lang_v1.cd_thanks_wish_3'),
        ];
    @endphp
    <script>
        (function() {
            var lastSaleTs = 0;

            function queueSaleComplete(data) {
                if (!data || !data.ts || data.ts <= lastSaleTs) {
                    return;
                }
                lastSaleTs = data.ts;
                window.__vsposPendingSaleComplete = data;
                if (typeof window.__vsposHandleSaleComplete === 'function') {
                    window.__vsposHandleSaleComplete(data);
                }
            }

            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    var cdSaleChannel = new BroadcastChannel('vspos_customer_display');
                    cdSaleChannel.addEventListener('message', function(ev) {
                        if (ev.data && ev.data.type === 'sale_complete' && ev.data.data) {
                            queueSaleComplete(ev.data.data);
                        }
                    });
                }
            } catch (e) {}

            window.addEventListener('storage', function(e) {
                if (e.key === 'pos_display_sale_completed' && e.newValue) {
                    try {
                        queueSaleComplete(JSON.parse(e.newValue));
                    } catch (err) {}
                }
            });
        })();
    </script>
    <script>
        $(document).ready(function() {
            let storageUpdateTimer = null;
            let lastJustScannedKey = null;
            const productsCache = new Map();

            const CD_MSG = {
                searching: @json(__('lang_v1.cd_searching')),
                linked: @json(__('lang_v1.cd_customer_linked')),
                notFound: @json(__('lang_v1.cd_customer_not_found')),
                enterPhone: @json(__('lang_v1.cd_enter_phone_first')),
                thankYou: @json(__('lang_v1.cd_thank_you')),
                thankYouName: @json(__('lang_v1.cd_thank_you_name')),
            };

            const CD_WELCOME_WISHES = @json($cd_welcome_wishes);

            const CD_THANKS_WISHES = @json($cd_thanks_wishes);

            let numpadValue = '';
            let saleCompleteTimer = null;
            let nextCustomerTimer = null;
            let linkedCustomerCache = null;
            let cdKnownPosSession = null;
            let cdLastCartTs = 0;
            let cdAwaitingPosSync = true;
            let cdLastAutoReloadAt = 0;
            let cdSaleCompleteShowing = false;
            let cdLastHandledSaleTs = 0;
            const CD_DEFAULT_CUSTOMER_ID = String($('#cd_default_customer_id').val() || '');

            try {
                window.name = 'customer_display';
                localStorage.setItem('vspos_cd_open', '1');
                sessionStorage.setItem('vspos_cd_open', '1');
            } catch (e) {}

            function triggerDisplayReload() {
                if (cdSaleCompleteShowing || hasPendingSaleComplete()) {
                    return;
                }
                const now = Date.now();
                if (now - cdLastAutoReloadAt < 1500) {
                    return;
                }
                cdLastAutoReloadAt = now;
                window.location.reload();
            }

            function hasPendingSaleComplete() {
                try {
                    return !!localStorage.getItem('pos_display_sale_completed');
                } catch (e) {
                    return false;
                }
            }

            function showSaleCompleteModal(displayName, wishText) {
                $('#cd_sc_customer_name').text(
                    displayName ? CD_MSG.thankYouName.replace(':name', displayName) : CD_MSG.thankYou
                );
                $('#cd_sc_wish').text(wishText || randomFrom(CD_THANKS_WISHES));
                $('#cd_sale_complete_backdrop, #cd_sale_complete_modal')
                    .addClass('is-open')
                    .attr('aria-hidden', 'false');
            }

            function hideSaleCompleteModal() {
                $('#cd_sale_complete_backdrop, #cd_sale_complete_modal')
                    .removeClass('is-open')
                    .attr('aria-hidden', 'true');
            }

            const CD_SYNC_STORAGE_KEYS = [
                'pos_form_data_array',
                'pos_display_customer_select',
                'pos_display_cart_ts',
                'pos_display_ping',
            ];

            try {
                cdLastCartTs = parseInt(localStorage.getItem('pos_display_cart_ts') || '0', 10) || 0;
            } catch (e) {
                cdLastCartTs = 0;
            }

            function hardClearDisplayCache(opts) {
                opts = opts || {};
                if (cdSaleCompleteShowing || hasPendingSaleComplete()) {
                    return;
                }
                linkedCustomerCache = null;
                lastJustScannedKey = null;
                productsCache.clear();

                CD_SYNC_STORAGE_KEYS.forEach(function(key) {
                    try {
                        localStorage.removeItem(key);
                    } catch (e) {}
                });

                closeNumpad();
                // Keep welcome visible if it just opened (empty cart sync can race with select)
                var welcomeJustOpened = cdLastWelcomeTs && (Date.now() - cdLastWelcomeTs) < 4500;
                if (!welcomeJustOpened) {
                    clearTimeout(window._cdWelcomeTimer);
                    $('#cd_welcome_overlay')
                        .removeClass('is-open')
                        .attr('aria-hidden', 'true')
                        .css('display', '');
                    $('#cd_feedback_overlay')
                        .removeClass('is-open')
                        .attr('aria-hidden', 'true');
                    $('#cd_next_customer')
                        .removeClass('is-visible')
                        .attr('aria-hidden', 'true');
                    clearTimeout(saleCompleteTimer);
                    clearTimeout(nextCustomerTimer);
                    resetCustomerDisplayForNext();
                    cdBarContactInitialized = false;
                    cdLastBarContactId = null;
                    cdLastWelcomedId = null;
                    cdLastWelcomeTs = 0;
                }
                clearDisplayCart();
                cdAwaitingPosSync = true;
                cdLastCartTs = 0;
            }

            function requestFreshCartFromPos() {
                cdAwaitingPosSync = true;
                try {
                    localStorage.setItem('pos_display_ping', String(Date.now()));
                } catch (e) {}
                try {
                    if (typeof BroadcastChannel !== 'undefined') {
                        var req = new BroadcastChannel('vspos_customer_display');
                        req.postMessage({ type: 'cart_request' });
                        req.close();
                    }
                } catch (e) {}
            }

            function watchPosSession() {
                const session = getPosSessionId();
                if (session && cdKnownPosSession && session !== cdKnownPosSession) {
                    rememberPosSession(session);
                    triggerDisplayReload();
                }
            }

            function getPosSessionId() {
                try {
                    return localStorage.getItem('pos_display_session') || '';
                } catch (e) {
                    return '';
                }
            }

            function rememberPosSession(sessionId) {
                if (!sessionId) {
                    return;
                }
                cdKnownPosSession = sessionId;
                try {
                    sessionStorage.setItem('cd_pos_session', sessionId);
                } catch (e) {}
            }

            function applyPosSessionReset(sessionId, force) {
                if (!sessionId) {
                    sessionId = getPosSessionId();
                }
                if (!force && sessionId && sessionId === cdKnownPosSession) {
                    return false;
                }

                hardClearDisplayCache();
                rememberPosSession(sessionId);
                requestFreshCartFromPos();
                return true;
            }

            function bootstrapPosSession() {
                const currentSession = getPosSessionId();
                let lastSession = '';
                try {
                    lastSession = sessionStorage.getItem('cd_pos_session') || '';
                } catch (e) {}

                if (lastSession && currentSession && lastSession !== currentSession) {
                    return applyPosSessionReset(currentSession, true);
                }

                if (currentSession) {
                    rememberPosSession(currentSession);
                }
                return false;
            }

            function isWalkInContactId(id) {
                if (!id) {
                    return true;
                }
                return CD_DEFAULT_CUSTOMER_ID && String(id) === CD_DEFAULT_CUSTOMER_ID;
            }

            function randomFrom(arr) {
                return arr[Math.floor(Math.random() * arr.length)];
            }

            function formatDisplayName(customer) {
                if (!customer) return '';
                const prefix = (customer.prefix || '').trim();
                const first = (customer.first_name || '').trim();
                const full = (customer.name || customer.text || '').trim();
                if (prefix && first) return prefix + ' ' + first;
                if (prefix && full) return prefix + ' ' + full.replace(/\s*\([^)]*\)\s*$/, '').trim();
                if (first) return first;
                return full.replace(/\s*\([^)]*\)\s*$/, '').trim();
            }

            function updateNumpadDisplay() {
                $('#cd_numpad_display').text(numpadValue || '—');
                $('#cd_customer_phone').val(numpadValue);
            }

            function openNumpad() {
                numpadValue = $('#cd_customer_phone').val().replace(/\D/g, '');
                updateNumpadDisplay();
                $('#cd_numpad, #cd_numpad_backdrop').addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeNumpad() {
                $('#cd_numpad, #cd_numpad_backdrop').removeClass('is-open').attr('aria-hidden', 'true');
            }

            function showWelcomePopup(customer) {
                if (!customer) return;
                const displayName = formatDisplayName(customer)
                    || (customer.text || '').replace(/\s*\([^)]*\)\s*$/, '').trim()
                    || (customer.name || '').trim();
                if (!displayName) return;

                // Close other overlays so welcome is visible
                $('#cd_feedback_overlay, #cd_sale_complete_modal, #cd_sale_complete_backdrop')
                    .removeClass('is-open')
                    .attr('aria-hidden', 'true');
                closeNumpad();

                $('#cd_welcome_name').text(displayName);
                $('#cd_welcome_wish').text(randomFrom(CD_WELCOME_WISHES));
                const $overlay = $('#cd_welcome_overlay');
                $overlay.css('display', 'flex').addClass('is-open').attr('aria-hidden', 'false');

                clearTimeout(window._cdWelcomeTimer);
                window._cdWelcomeTimer = setTimeout(function() {
                    $overlay.removeClass('is-open').attr('aria-hidden', 'true');
                    $overlay.css('display', '');
                }, 4000);
            }

            var cdLastWelcomedId = null;
            var cdLastWelcomeTs = 0;
            var cdBarContactInitialized = false;
            var cdLastBarContactId = null;

            function handleCustomerWelcome(customer) {
                if (!customer || !customer.id || isWalkInContactId(customer.id)) {
                    return;
                }

                var id = String(customer.id);
                var ts = parseInt(customer.ts || 0, 10) || Date.now();

                // Ignore exact duplicate welcome only (same id + same/older ts)
                if (id === String(cdLastWelcomedId || '') && ts <= cdLastWelcomeTs) {
                    return;
                }

                // Allow welcome again for same customer if POS re-selected (new ts)
                if (id === String(cdLastWelcomedId || '') && (ts - cdLastWelcomeTs) < 1500) {
                    return;
                }

                applyCustomerToBar(customer);
                showWelcomePopup(customer);
                cdLastWelcomedId = id;
                cdLastWelcomeTs = ts;
                cdLastBarContactId = id;
                cdBarContactInitialized = true;

                try {
                    localStorage.removeItem('pos_display_customer_welcome');
                } catch (e) {}
            }

            /**
             * When POS cart sync changes contact_id, show welcome.
             * This is the reliable path — name already updates this way.
             */
            function maybeWelcomeFromBarChange(customer) {
                if (!customer || !customer.id || isWalkInContactId(customer.id)) {
                    if (cdBarContactInitialized) {
                        cdLastBarContactId = null;
                    }
                    cdBarContactInitialized = true;
                    return;
                }

                var newId = String(customer.id);
                var prevId = cdLastBarContactId != null ? String(cdLastBarContactId) : null;
                var isChange = cdBarContactInitialized && newId !== prevId;

                cdLastBarContactId = newId;
                cdBarContactInitialized = true;

                if (!isChange) {
                    return;
                }

                handleCustomerWelcome({
                    id: customer.id,
                    text: customer.text || customer.name || '',
                    name: customer.name || customer.text || '',
                    prefix: customer.prefix || '',
                    first_name: customer.first_name || '',
                    ts: Date.now(),
                });
            }

            function pollCustomerWelcome() {
                try {
                    var raw = localStorage.getItem('pos_display_customer_welcome');
                    if (!raw) return;
                    var data = JSON.parse(raw);
                    if (data && data.id) {
                        handleCustomerWelcome(data);
                    }
                } catch (e) {}
            }

            function handleIncomingCustomerSelect(customer) {
                if (!customer || !customer.id) {
                    return;
                }

                if (isWalkInContactId(customer.id)) {
                    clearCustomerFromDisplay();
                    cdLastWelcomedId = null;
                    return;
                }

                applyCustomerToBar(customer);

                if (customer.show_welcome && !customer.from_display) {
                    handleCustomerWelcome(customer);
                }
            }

            function isPosAlive() {
                try {
                    var hb = parseInt(localStorage.getItem('pos_display_heartbeat') || '0', 10);
                    return hb > 0 && (Date.now() - hb < 6000);
                } catch (e) {
                    return false;
                }
            }

            function resetCustomerDisplayForNext() {
                $('#cd_customer_phone').val('');
                numpadValue = '';
                linkedCustomerCache = null;
                $('.customer_details').text('Walk-in Customer');
                setLinkStatus('', 'info');
                $('#cd_emoji_row .cd-emoji-btn').removeClass('is-selected');
            }

            function applyCustomerToBar(customer) {
                if (!customer) {
                    $('.customer_details').text('Walk-in Customer');
                    return;
                }
                linkedCustomerCache = customer;
                const name = formatDisplayName(customer) || customer.text || customer.name || '';
                $('.customer_details').text(name || 'Walk-in Customer');
            }

            async function updateCustomerBarFromSources(storedArrayData) {
                const formContactIdObj = storedArrayData && storedArrayData.find(function(item) {
                    return item.name === 'contact_id';
                });
                const formContactId = formContactIdObj ? String(formContactIdObj.value || '').trim() : '';

                if (storedArrayData && formContactId && isWalkInContactId(formContactId)) {
                    linkedCustomerCache = null;
                    $('.customer_details').text('Walk-in Customer');
                    maybeWelcomeFromBarChange(null);
                    return;
                }

                if (formContactId && !isWalkInContactId(formContactId)) {
                    const customer = await getCustomerById(formContactId);
                    if (customer) {
                        applyCustomerToBar(customer);
                        maybeWelcomeFromBarChange(customer);
                        return;
                    }
                }

                if (linkedCustomerCache && linkedCustomerCache.id && !isWalkInContactId(linkedCustomerCache.id)) {
                    applyCustomerToBar(linkedCustomerCache);
                    maybeWelcomeFromBarChange(linkedCustomerCache);
                    return;
                }

                try {
                    const raw = localStorage.getItem('pos_display_customer_select');
                    if (raw) {
                        const data = JSON.parse(raw);
                        if (data && data.id && !isWalkInContactId(data.id)) {
                            if (!formContactId || isWalkInContactId(formContactId) || String(data.id) === formContactId) {
                                applyCustomerToBar(data);
                                maybeWelcomeFromBarChange(data);
                                return;
                            }
                        }
                    }
                } catch (e) {}

                $('.customer_details').text('Walk-in Customer');
                maybeWelcomeFromBarChange(null);
            }

            function clearDisplayCart() {
                $('#pos_table tbody').empty();
                $('#total_payable').text(__currency_trans_from_en(0));
                $('.price_total').text(__currency_trans_from_en(0));
                $('#total_discount').text(__currency_trans_from_en(0));
                $('.total_paying').text('0');
                $('.change_return_span').text('0');
                $('.balance_due').text('0');
                $('.total_quantity').text('0');
                $('#cd_just_scanned').addClass('is-hidden');
                lastJustScannedKey = null;
            }

            function clearCustomerFromDisplay() {
                linkedCustomerCache = null;
                $('.customer_details').text('Walk-in Customer');
                setLinkStatus('', 'info');
            }

            function tryLiveSyncFromPos() {
                loadTableData();
            }

            function parseFormArrayFromStorage() {
                try {
                    const raw = localStorage.getItem('pos_form_data_array');
                    if (!raw) {
                        return null;
                    }
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : null;
                } catch (e) {
                    return null;
                }
            }

            function extractProductsFromFormArray(storedArrayData) {
                const formattedData = {};
                storedArrayData.forEach(function(item) {
                    const match = item.name.match(/^products\[(\d+)\]\[([^[\]]+)\]$/);
                    if (match) {
                        const index = match[1];
                        const key = match[2];
                        if (!formattedData[index]) {
                            formattedData[index] = {};
                        }
                        formattedData[index][key] = item.value;
                    }
                });

                return Object.values(formattedData).filter(function(prod) {
                    return prod && prod.variation_id;
                }).reverse();
            }

            function handleSaleComplete(data) {
                if (!data || !data.ts) {
                    return;
                }
                if (data.ts <= cdLastHandledSaleTs) {
                    return;
                }
                cdLastHandledSaleTs = data.ts;

                clearTimeout(saleCompleteTimer);
                clearTimeout(nextCustomerTimer);

                cdSaleCompleteShowing = true;
                cdAwaitingPosSync = true;

                const displayName = formatDisplayName({
                    prefix: data.prefix,
                    first_name: data.first_name,
                    name: data.customerName,
                    text: data.customerName,
                }) || (linkedCustomerCache ? formatDisplayName(linkedCustomerCache) : '');

                const wishText = randomFrom(CD_THANKS_WISHES);
                showSaleCompleteModal(displayName, wishText);
                clearDisplayCart();

                try {
                    localStorage.removeItem('pos_display_sale_completed');
                } catch (e) {}

                saleCompleteTimer = setTimeout(function() {
                    hideSaleCompleteModal();
                    cdSaleCompleteShowing = false;
                    resetCustomerDisplayForNext();
                    requestFreshCartFromPos();

                    const $next = $('#cd_next_customer');
                    $next.addClass('is-visible').attr('aria-hidden', 'false');
                    nextCustomerTimer = setTimeout(function() {
                        $next.removeClass('is-visible').attr('aria-hidden', 'true');
                    }, 8000);
                }, 6000);
            }

            window.__vsposHandleSaleComplete = handleSaleComplete;

            function checkPendingSaleComplete() {
                try {
                    const raw = localStorage.getItem('pos_display_sale_completed');
                    if (raw) {
                        handleSaleComplete(JSON.parse(raw));
                    }
                } catch (e) {}
            }

            function pollSaleComplete() {
                if (cdSaleCompleteShowing) {
                    return;
                }
                checkPendingSaleComplete();
            }

            function makeProductKey(variationId, locationId) {
                return variationId + '__' + locationId;
            }

            function setLinkStatus(msg, type) {
                const $el = $('#cd_link_status');
                $el.removeClass('is-success is-error is-info').addClass('is-' + (type || 'info'));
                $el.text(msg || '');
            }

            function updateClock() {
                const now = new Date();
                const h = now.getHours();
                const m = String(now.getMinutes()).padStart(2, '0');
                const ampm = h >= 12 ? 'PM' : 'AM';
                const hr = h % 12 || 12;
                $('#cd_clock').text(hr + ':' + m + ' ' + ampm);
            }
            updateClock();
            setInterval(updateClock, 30000);

            async function getCustomerById(id) {
                try {
                    const response = await $.ajax({
                        url: '/contacts/customers',
                        method: 'GET',
                        data: { id: id },
                        dataType: 'json',
                    });
                    if (Array.isArray(response) && response.length) {
                        return response[0];
                    }
                    return null;
                } catch (e) {
                    return null;
                }
            }

            async function searchCustomerByMobile(mobile) {
                const response = await $.ajax({
                    url: '/contacts/customers',
                    method: 'GET',
                    data: { q: mobile },
                    dataType: 'json',
                });
                const digits = String(mobile).replace(/\D/g, '');
                if (!response || !response.length) return null;

                let exact = response.find(function(c) {
                    return String(c.mobile || '').replace(/\D/g, '') === digits;
                });
                if (exact) return exact;

                if (response.length === 1) return response[0];

                return response.find(function(c) {
                    return String(c.mobile || '').indexOf(mobile) !== -1;
                }) || null;
            }

            function sendCustomerToPosWindow(payload) {
                var delivered = false;
                try {
                    if (window.opener && !window.opener.closed) {
                        if (typeof window.opener.pos_select_customer_from_display === 'function') {
                            delivered = !!window.opener.pos_select_customer_from_display(payload);
                        }
                        window.opener.postMessage({
                            type: 'vspos_customer_select',
                            customer: payload,
                        }, window.location.origin);
                        delivered = true;
                    }
                } catch (e) {}
                return delivered;
            }

            var vsposCdOutChannel = null;
            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    vsposCdOutChannel = new BroadcastChannel('vspos_customer_display');
                }
            } catch (e) {}

            function linkCustomerToPos(customer) {
                const payload = {
                    id: customer.id,
                    text: customer.text,
                    name: customer.name,
                    prefix: customer.prefix,
                    first_name: customer.first_name,
                    mobile: customer.mobile,
                    balance: customer.balance,
                    shipping_address: customer.shipping_address,
                    pay_term_number: customer.pay_term_number,
                    pay_term_type: customer.pay_term_type,
                    price_calculation_type: customer.price_calculation_type,
                    selling_price_group_id: customer.selling_price_group_id,
                    ts: Date.now(),
                    from_display: true,
                    show_welcome: false,
                };

                // Push to POS immediately (don't wait for server)
                deliverCustomerToPos(payload);

                // Also persist via server session for reliability
                $.ajax({
                    url: '/pos/display/link-customer',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        customer_id: customer.id,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                }).done(function(res) {
                    if (res && res.customer) {
                        payload.ts = res.customer.ts || payload.ts;
                        Object.assign(payload, res.customer);
                    }
                    deliverCustomerToPos(payload);
                }).fail(function() {
                    deliverCustomerToPos(payload);
                });
            }

            function deliverCustomerToPos(payload) {
                if (!sendCustomerToPosWindow(payload)) {
                    var tries = 0;
                    var retryTimer = setInterval(function() {
                        tries++;
                        if (sendCustomerToPosWindow(payload) || tries >= 15) {
                            clearInterval(retryTimer);
                        }
                    }, 400);
                }

                try {
                    localStorage.setItem('pos_display_customer_select', JSON.stringify(payload));
                } catch (e) {}

                try {
                    if (vsposCdOutChannel) {
                        vsposCdOutChannel.postMessage({ type: 'customer_select', customer: payload });
                    }
                } catch (e) {}
            }

            async function handlePhoneLink() {
                const mobile = $('#cd_customer_phone').val().trim();
                if (!mobile || mobile.length < 3) {
                    setLinkStatus(CD_MSG.enterPhone, 'error');
                    return;
                }

                const $btn = $('#cd_link_customer');
                $btn.prop('disabled', true);
                setLinkStatus(CD_MSG.searching, 'info');

                try {
                    const customer = await searchCustomerByMobile(mobile);
                    if (!customer) {
                        setLinkStatus(CD_MSG.notFound, 'error');
                        return;
                    }

                    linkedCustomerCache = customer;
                    applyCustomerToBar(customer);
                    linkCustomerToPos(customer);
                    const name = formatDisplayName(customer) || customer.text || customer.name || '';
                    setLinkStatus(CD_MSG.linked + ': ' + name, 'success');
                    closeNumpad();
                    showWelcomePopup(customer);
                    cdLastWelcomedId = String(customer.id);
                    cdLastWelcomeTs = Date.now();
                } catch (e) {
                    setLinkStatus(CD_MSG.notFound, 'error');
                } finally {
                    $btn.prop('disabled', false);
                }
            }

            if ($('#cd_link_customer').length) {
                $('#cd_link_customer').on('click', function(e) {
                    e.preventDefault();
                    if (!$('#cd_customer_phone').val().trim()) {
                        openNumpad();
                    } else {
                        handlePhoneLink();
                    }
                });
                $('#cd_customer_phone').on('click', function(e) {
                    e.preventDefault();
                    openNumpad();
                });
                $('#cd_numpad_close, #cd_numpad_backdrop').on('click', closeNumpad);
                $('#cd_numpad').on('click', '.cd-nk', function() {
                    const key = $(this).data('key');
                    if (key === 'back') {
                        numpadValue = numpadValue.slice(0, -1);
                    } else if (key === 'done') {
                        updateNumpadDisplay();
                        closeNumpad();
                        if (numpadValue.length >= 3) {
                            handlePhoneLink();
                        }
                        return;
                    } else {
                        if (numpadValue.length < 15) {
                            numpadValue += String(key);
                        }
                    }
                    updateNumpadDisplay();
                });
            }
            $('#cd_emoji_row').on('click', '.cd-emoji-btn', function() {
                $('#cd_emoji_row .cd-emoji-btn').removeClass('is-selected');
                $(this).addClass('is-selected');
            });

            function setProductInCache(variationId, locationId, product) {
                productsCache.set(makeProductKey(variationId, locationId), product);
            }

            async function fetchProductsBulkOnce(variationIds, locationId) {
                const missing = [];
                for (let i = 0; i < variationIds.length; i++) {
                    const key = makeProductKey(variationIds[i], locationId);
                    if (!productsCache.has(key)) missing.push(variationIds[i]);
                }
                if (!missing.length) return;

                const qs = encodeURIComponent(missing.join(','));
                const url = '/pos/variations/bulk?ids=' + qs + '&location_id=' + encodeURIComponent(locationId);
                try {
                    const data = await $.ajax({ url: url, method: 'GET', dataType: 'json' });
                    if (data && typeof data === 'object') {
                        Object.keys(data).forEach(function(id) {
                            setProductInCache(id, locationId, data[id]);
                        });
                    }
                } catch (e) {}
            }

            function showJustScanned(product, singleProduct, unitPrice) {
                if (!product || !product.variation_id) return;

                const key = product.variation_id + '_' + (product.quantity || '');
                if (key === lastJustScannedKey) return;
                lastJustScannedKey = key;

                let imageUrl = base_path + '/img/default.png';
                if (singleProduct && singleProduct.media && singleProduct.media.length) {
                    imageUrl = singleProduct.media[0].display_url;
                } else if (singleProduct && singleProduct.product_image) {
                    imageUrl = base_path + '/uploads/img/' + encodeURIComponent(singleProduct.product_image);
                }

                const name = singleProduct ? singleProduct.product_name : '-';
                const qty = product.quantity || '1';
                const price = product.unit_price_inc_tax || '0.00';

                $('#cd_js_image').attr('src', imageUrl);
                $('#cd_js_name').text(name + ' — ' + qty + ' x ' + price);
                $('#cd_js_price').text(__currency_trans_from_en(unitPrice, true));
                $('#cd_just_scanned').removeClass('is-hidden');
            }

            let isLoadingTableData = false;
            let pendingTableReload = false;

            function finishLoadTableData() {
                isLoadingTableData = false;
                try {
                    const ts = parseInt(localStorage.getItem('pos_display_cart_ts') || '0', 10);
                    if (ts) {
                        cdLastCartTs = ts;
                    }
                } catch (e) {}
                if (pendingTableReload) {
                    pendingTableReload = false;
                    loadTableData();
                }
            }

            async function loadTableData(storedArrayData) {
                if (isLoadingTableData) {
                    pendingTableReload = true;
                    return;
                }

                if (!storedArrayData) {
                    if (cdAwaitingPosSync) {
                        return;
                    }
                    storedArrayData = parseFormArrayFromStorage();
                }

                isLoadingTableData = true;

                if (!storedArrayData) {
                    if (!isPosAlive()) {
                        clearDisplayCart();
                        await updateCustomerBarFromSources(null);
                    }
                    finishLoadTableData();
                    return;
                }

                await updateCustomerBarFromSources(storedArrayData);

                const locationIdObj = storedArrayData.find(function(item) { return item.name === 'location_id'; });
                const location_id = locationIdObj ? locationIdObj.value : null;

                const resultArray = extractProductsFromFormArray(storedArrayData);

                const final_total_item = storedArrayData.find(function(item) { return item.name === 'final_total'; });
                const final_total = final_total_item ? final_total_item.value : null;
                $('#total_payable').text(__currency_trans_from_en(__num_uf(final_total)));

                const discount_type_modal_item = storedArrayData.find(function(item) { return item.name === 'discount_type'; })
                    || storedArrayData.find(function(item) { return item.name === 'discount_type_modal'; });
                const discount_type_modal = discount_type_modal_item ? discount_type_modal_item.value : null;

                const discount_amount_modal_item = storedArrayData.find(function(item) { return item.name === 'discount_amount'; })
                    || storedArrayData.find(function(item) { return item.name === 'discount_amount_modal'; });
                const discount_amount_modal = discount_amount_modal_item ? discount_amount_modal_item.value : null;

                const price_total_item = storedArrayData.find(function(item) { return item.name === 'price_total'; });
                const price_total = price_total_item ? price_total_item.value : null;
                $('.price_total').text(__currency_trans_from_en(price_total));

                const computed_discount = __calculate_amount(
                    discount_type_modal,
                    __num_uf(discount_amount_modal),
                    __num_uf(price_total)
                );
                $('#total_discount').text(__currency_trans_from_en(computed_discount));

                const total_paying_input_item = storedArrayData.find(function(item) { return item.name === 'total_paying_input'; });
                $('.total_paying').text(__num_uf(total_paying_input_item ? total_paying_input_item.value : 0));

                const change_return_item = storedArrayData.find(function(item) { return item.name === 'change_return'; });
                $('.change_return_span').text(__num_uf(change_return_item ? change_return_item.value : 0));

                const in_balance_due_item = storedArrayData.find(function(item) { return item.name === 'in_balance_due'; });
                $('.balance_due').text(__num_uf(in_balance_due_item ? in_balance_due_item.value : 0));

                const tableBody = $('#pos_table tbody');
                tableBody.empty();

                if (!resultArray.length) {
                    clearDisplayCart();
                    __currency_convert_recursively($('.pos_sell'));
                    finishLoadTableData();
                    return;
                }

                const neededVariationIds = [];
                resultArray.forEach(function(prod) {
                    if (prod && prod.variation_id) neededVariationIds.push(prod.variation_id);
                });
                await fetchProductsBulkOnce(neededVariationIds, location_id);

                let totalQuantity = 0;

                for (let i = 0; i < resultArray.length; i++) {
                    const product = resultArray[i];
                    const single_product = productsCache.get(makeProductKey(product.variation_id, location_id)) || null;

                    let imageUrl = base_path + '/img/default.png';
                    if (single_product && single_product.media && single_product.media.length) {
                        imageUrl = single_product.media[0].display_url;
                    } else if (single_product && single_product.product_image) {
                        imageUrl = base_path + '/uploads/img/' + encodeURIComponent(single_product.product_image);
                    }

                    const quantity = parseFloat(product.quantity) || 0;
                    totalQuantity += quantity;
                    const unitPrice = __num_uf(product.unit_price_inc_tax);
                    const name = single_product ? single_product.product_name : '-';

                    if (i === 0) {
                        showJustScanned(product, single_product, unitPrice);
                    }

                    const rowHtml = '<tr>' +
                        '<td><img loading="lazy" src="' + imageUrl + '" alt="">' +
                        '<div><div class="cd-item-name">' + name + '</div>' +
                        '<div class="cd-item-meta">' + (product.quantity || '0') + ' x ' + (product.unit_price_inc_tax || '0.00') + '</div></div></td>' +
                        '<td style="display:none">' + (product.quantity || '0') + '</td>' +
                        '<td style="display:none">' + (product.unit_price_inc_tax || '0.00') + '</td>' +
                        '<td>' + (__currency_trans_from_en(unitPrice, true) || '0.00') + '</td>' +
                        '</tr>';

                    tableBody.append(rowHtml);
                }

                $('.total_quantity').text(totalQuantity);
                __currency_convert_recursively($('.pos_sell'));
                finishLoadTableData();
            }

            function requestCartSyncFromPos() {
                loadTableData();
            }

            // Start empty — never read stale localStorage on load; wait for POS cart_sync
            clearDisplayCart();
            resetCustomerDisplayForNext();
            bootstrapPosSession();
            checkPendingSaleComplete();
            if (window.__vsposPendingSaleComplete) {
                handleSaleComplete(window.__vsposPendingSaleComplete);
                window.__vsposPendingSaleComplete = null;
            }

            function debounceStorageUpdate() {
                clearTimeout(storageUpdateTimer);
                storageUpdateTimer = setTimeout(function() {
                    cdAwaitingPosSync = false;
                    loadTableData();
                }, 50);
            }

            function syncCartNow(formData) {
                clearTimeout(storageUpdateTimer);
                cdAwaitingPosSync = false;

                if (formData && Array.isArray(formData) && formData.length === 0) {
                    if (cdSaleCompleteShowing || hasPendingSaleComplete()) {
                        return;
                    }
                    clearDisplayCart();
                    hardClearDisplayCache();
                    return;
                }

                try {
                    cdLastCartTs = parseInt(localStorage.getItem('pos_display_cart_ts') || '0', 10) || cdLastCartTs;
                } catch (e) {}

                loadTableData(formData || null);
            }

            function pollCartIfChanged() {
                if (cdAwaitingPosSync) {
                    return;
                }

                let ts = 0;
                try {
                    ts = parseInt(localStorage.getItem('pos_display_cart_ts') || '0', 10) || 0;
                } catch (e) {}

                if (ts > 0 && ts !== cdLastCartTs) {
                    cdLastCartTs = ts;
                    loadTableData();
                }
            }

            window.addEventListener('beforeunload', function() {
                try {
                    if (window.opener && window.opener.closed) {
                        sessionStorage.removeItem('vspos_cd_open');
                    }
                } catch (e) {}
            });

            window.addEventListener('storage', function(e) {
                if (e.key === 'pos_display_force_reload' && e.newValue) {
                    triggerDisplayReload();
                    return;
                }
                if (e.key === 'pos_display_refresh' || e.key === 'pos_display_session') {
                    triggerDisplayReload();
                    return;
                }
                if (e.key === 'pos_display_customer_select') {
                    if (!e.newValue) {
                        clearCustomerFromDisplay();
                        cdLastWelcomedId = null;
                        return;
                    }
                    try {
                        const customer = JSON.parse(e.newValue);
                        handleIncomingCustomerSelect(customer);
                    } catch (err) {}
                    return;
                }
                if (e.key === 'pos_display_customer_welcome' && e.newValue) {
                    try {
                        handleCustomerWelcome(JSON.parse(e.newValue));
                    } catch (err) {}
                    return;
                }
                if (e.key === 'pos_form_data_array' || e.key === 'pos_display_cart_ts') {
                    try {
                        cdLastCartTs = parseInt(localStorage.getItem('pos_display_cart_ts') || '0', 10) || cdLastCartTs;
                    } catch (err) {}
                    debounceStorageUpdate();
                }
                if (e.key === 'pos_display_sale_completed' && e.newValue) {
                    try {
                        handleSaleComplete(JSON.parse(e.newValue));
                    } catch (err) {}
                }
            });

            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    var cdChannel = new BroadcastChannel('vspos_customer_display');
                    cdChannel.onmessage = function(ev) {
                        if (!ev.data) {
                            return;
                        }
                        if (ev.data.type === 'sale_complete' && ev.data.data) {
                            handleSaleComplete(ev.data.data);
                            return;
                        }
                        if (ev.data.type === 'display_reload') {
                            triggerDisplayReload();
                            return;
                        }
                        if (ev.data.type === 'cache_clear') {
                            triggerDisplayReload();
                            return;
                        }
                        if (ev.data.type === 'pos_reset' || ev.data.type === 'customer_clear') {
                            applyPosSessionReset(ev.data.session || getPosSessionId(), true);
                            return;
                        }
                        if (ev.data.type === 'customer_select' && ev.data.customer) {
                            handleIncomingCustomerSelect(ev.data.customer);
                            return;
                        }
                        if (ev.data.type === 'customer_welcome' && ev.data.customer) {
                            handleCustomerWelcome(ev.data.customer);
                            return;
                        }
                        if (ev.data.type === 'cart_sync') {
                            syncCartNow(ev.data.form || null);
                        }
                    };
                }
            } catch (e) {}

            // Always ask POS for live state — never restore stale localStorage on load
            requestFreshCartFromPos();
            setTimeout(requestFreshCartFromPos, 400);
            setTimeout(requestFreshCartFromPos, 1200);
            setTimeout(function() {
                cdAwaitingPosSync = false;
                pollCartIfChanged();
            }, 1800);
            setInterval(pollCartIfChanged, 500);
            setInterval(pollSaleComplete, 400);
            setInterval(pollCustomerWelcome, 400);
            setInterval(watchPosSession, 800);
            // Catch welcome that arrived while display was loading
            pollCustomerWelcome();
            setTimeout(pollCustomerWelcome, 600);
            setTimeout(pollCustomerWelcome, 1500);
        });
    </script>
@endsection
