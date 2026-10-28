@inject('request', 'Illuminate\Http\Request')
@php
    // Determine income masking state for connection indicator
    $isMaskMode = false;
    $business_id = session('user.business_id');
    $header_pos_settings = [];
    if ($business_id) {
        $biz = \App\Business::find($business_id);
        $cs = !empty($biz->common_settings) ? $biz->common_settings : [];
        $maskingEnabled = !empty($cs['enable_commission_masking']);
        $isAdmin = auth()->user() && (auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#'.$business_id));
        $icMode = session('ic_mode', false);
        if ($maskingEnabled && !$isAdmin && !$icMode) {
            $isMaskMode = true;
        }
        if (!empty($biz->pos_settings)) {
            $header_pos_settings = is_string($biz->pos_settings)
                ? (json_decode($biz->pos_settings, true) ?: [])
                : (array) $biz->pos_settings;
        }
    }
    $header_enabled_modules = (array) session('business.enabled_modules', []);
    $header_pop_enabled = !empty($header_pos_settings['enable_pop'])
        || in_array('pos_purchase', $header_enabled_modules);
@endphp
<!-- Main Header -->
<header class="nv-bar no-print">
    <div class="nv-inner">

        {{-- ── LEFT: toggle buttons ── --}}
        <div class="nv-left">
            @if (empty($is_pop_layout) && empty($pos_layout))
            <button type="button" class="small-view-button nv-icon-btn lg:tw-hidden" title="Menu">
                <i class="fi fi-rr-menu-burger nv-fi-icon"></i>
            </button>
            <button type="button" class="side-bar-collapse nv-icon-btn tw-hidden lg:tw-flex" title="Collapse Sidebar">
                <i class="fi fi-rr-apps nv-fi-icon"></i>
            </button>
            @elseif (!empty($is_pop_layout))
            <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}" class="nv-back-btn" title="{{ __('lang_v1.go_back') }}">
                <i class="fas fa-arrow-left"></i> <span class="tw-hidden md:tw-inline">{{ __('purchase.purchases') }}</span>
            </a>
            <span class="pos-status-pill pos-status-pill--ok tw-hidden sm:tw-inline-flex" style="margin-left:6px;">
                @lang('lang_v1.point_of_purchase')
            </span>
            @endif

            {{-- SaaS active subscription --}}
            @if(Module::has('Superadmin'))
                @includeIf('superadmin::layouts.partials.active_subscription')
            @endif

            {{-- Switch-back button --}}
            @if(!empty(session('previous_user_id')) && !empty(session('previous_username')))
                <a href="{{ route('sign-in-as-user', session('previous_user_id')) }}" class="nv-back-btn">
                    <i class="fas fa-undo"></i> @lang('lang_v1.back_to_username', ['username' => session('previous_username')])
                </a>
            @endif
        </div>

        {{-- ── RIGHT: action icons ── --}}
        <div class="nv-right">

            @if (Module::has('Essentials'))
                @includeIf('essentials::layouts.partials.header_part')
            @endif

            {{-- Quick actions dropdown --}}
            <details class="nv-dropdown-wrap">
                <summary class="nv-icon-btn" title="Quick Actions">
                    <i class="fi fi-rr-menu-burger nv-fi-icon"></i>
                </summary>
                <ul class="nv-dropdown">
                    <li class="nv-mobile-only">
                        <a href="#" id="mobile-open-calculator" class="nv-drop-item">
                            <i class="fi fi-rr-calculator-simple nv-drop-icon"></i> @lang('lang_v1.calculator')
                        </a>
                    </li>
                    @can('profit_loss_report.view')
                        <li class="nv-mobile-only">
                            <a href="#" id="mobile-open-profit" class="nv-drop-item">
                                <i class="fi fi-rr-chart-histogram nv-drop-icon"></i> {{ __('home.todays_profit') }}
                            </a>
                        </li>
                    @endcan
                    @if ($header_pop_enabled)
                        @can('purchase.create')
                            <li class="nv-mobile-only">
                                <a href="{{ action([\App\Http\Controllers\PurchasePosController::class, 'create']) }}" class="nv-drop-item">
                                    <i class="fas fa-cart-arrow-down nv-drop-icon"></i> @lang('lang_v1.point_of_purchase')
                                </a>
                            </li>
                        @endcan
                    @endif
                    @can('purchase.view')
                        <li class="nv-mobile-only">
                            <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}" class="nv-drop-item">
                                <i class="fas fa-truck-loading nv-drop-icon"></i> {{ __('purchase.purchases') }}
                            </a>
                        </li>
                    @endcan
                    <li>
                        <a href="{{ route('calendar') }}" class="nv-drop-item">
                            <i class="far fa-calendar-alt nv-drop-icon"></i> @lang('lang_v1.calendar')
                        </a>
                    </li>
                    @if (Module::has('Essentials'))
                        <li>
                            <a href="#" data-href="{{ action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create']) }}" data-container="#task_modal" class="btn-modal nv-drop-item">
                                <i class="fas fa-check-square nv-drop-icon"></i> @lang('essentials::lang.add_to_do')
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
                        <li>
                            <a href="#" id="start_tour" class="nv-drop-item">
                                <i class="fas fa-question-circle nv-drop-icon"></i> @lang('lang_v1.application_tour')
                            </a>
                        </li>
                    @endif
                    <li id="menu-install-app" style="display:none;">
                        <a href="#" onclick="triggerPWAInstall(); return false;" class="nv-drop-item">
                            <i class="fas fa-download nv-drop-icon"></i> @lang('lang_v1.install_app')
                        </a>
                    </li>
                </ul>
            </details>

            {{-- Calculator --}}
            <button id="btnCalculator" title="@lang('lang_v1.calculator')"
                data-content='@include("layouts.partials.calculator")'
                type="button" data-trigger="click" data-html="true" data-placement="bottom"
                class="nv-icon-btn tw-hidden md:tw-flex nv-desktop-action">
                <i class="fi fi-rr-calculator-simple nv-fi-icon"></i>
            </button>

            {{-- POS Sale button --}}
            @if (in_array('pos_sale', $enabled_modules))
                @can('sell.create')
                    <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}" class="nv-pos-btn" title="@lang('sale.pos_sale')">
                        <i class="fi fi-rr-apps-add tw-hidden md:tw-inline-block nv-fi-icon"></i>
                        @lang('sale.pos_sale')
                    </a>
                @endcan
            @endif

            {{-- Purchase / POP shortcuts --}}
            @if ($header_pop_enabled)
                @can('purchase.create')
                    <a href="{{ action([\App\Http\Controllers\PurchasePosController::class, 'create']) }}"
                        class="nv-pop-btn"
                        title="@lang('lang_v1.point_of_purchase')">
                        <i class="fas fa-cart-arrow-down tw-hidden md:tw-inline-block"></i>
                        @lang('lang_v1.pop_short')
                    </a>
                @endcan
            @endif
            @can('purchase.view')
                <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}"
                    class="nv-icon-btn nv-desktop-action tw-hidden md:tw-flex"
                    title="{{ __('purchase.purchases') }}">
                    <i class="fas fa-truck-loading nv-fi-icon"></i>
                </a>
            @else
                @can('view_own_purchase')
                    <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}"
                        class="nv-icon-btn nv-desktop-action tw-hidden md:tw-flex"
                        title="{{ __('purchase.purchases') }}">
                        <i class="fas fa-truck-loading nv-fi-icon"></i>
                    </a>
                @endcan
            @endcan

            @if (Module::has('Repair'))
                @includeIf('repair::layouts.partials.header')
            @endif

            {{-- Today's Profit --}}
            @can('profit_loss_report.view')
                <button type="button" id="view_todays_profit"
                    title="{{ __('home.todays_profit') }}" data-toggle="tooltip" data-placement="bottom"
                    class="nv-icon-btn tw-hidden sm:tw-flex nv-desktop-action">
                    <i class="fi fi-rr-chart-histogram nv-fi-icon"></i>
                </button>
            @endcan

            {{-- Connection status indicator --}}
            @if ($isMaskMode)
                <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-white tw-text-xs tw-font-bold tw-px-2 tw-py-1 tw-rounded-full nv-desktop-action" style="background-color:#ff5722;" title="Income masking active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4"/><path d="M18 12h4"/><path d="M6 12a6 6 0 0 1 12 0"/></svg>
                    LOW
                </span>
            @else
                <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-white tw-text-xs tw-font-bold tw-px-2 tw-py-1 tw-rounded-full nv-desktop-action" style="background-color:#4caf50;" title="Real values showing">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22h20"/><path d="M6 12a8 8 0 0 1 16 0"/><path d="M10 6a6 6 0 0 1 12 0"/><path d="M14 2a2 2 0 0 1 4 0"/></svg>
                    HIGH
                </span>
            @endif

            <button type="button" class="qpos-theme-toggle nv-icon-btn" title="Switch to light theme" aria-label="Switch to light theme">
                <i class="fi fi-rr-sun qpos-theme-toggle-icon nv-fi-icon"></i>
            </button>

            {{-- Date chip --}}
            <span class="nv-date-chip tw-hidden lg:tw-flex">{{ @format_date('now') }}</span>

            {{-- Notifications bell --}}
            <div>
                @include('layouts.partials.header-notifications')
            </div>

            {{-- Divider --}}
            <div class="nv-divider tw-hidden sm:tw-block"></div>

            {{-- User menu --}}
            <details class="nv-dropdown-wrap">
                <summary class="nv-user-btn" title="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}">
                    <span class="nv-avatar">{{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name, 0, 1)) }}</span>
                    <span class="nv-username tw-hidden md:tw-block">{{ Auth::user()->first_name }}</span>
                    <svg class="nv-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </summary>
                <ul class="nv-dropdown nv-dropdown-right">
                    <li class="nv-drop-header">
                        <div class="nv-drop-avatar">{{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name, 0, 1)) }}</div>
                        <div>
                            <div class="nv-drop-name">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                            <div class="nv-drop-role">@lang('lang_v1.signed_in_as')</div>
                        </div>
                    </li>
                    <li class="nv-drop-sep"></li>
                    <li>
                        <a href="{{ action([\App\Http\Controllers\UserController::class, 'getProfile']) }}" class="nv-drop-item">
                            <i class="fas fa-user-circle nv-drop-icon"></i> @lang('lang_v1.profile')
                        </a>
                    </li>
                    <li>
                        <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'logout']) }}" class="nv-drop-item nv-drop-item-danger">
                            <i class="fas fa-sign-out-alt nv-drop-icon"></i> @lang('lang_v1.sign_out')
                        </a>
                    </li>
                </ul>
            </details>

        </div>{{-- /.nv-right --}}
    </div>{{-- /.nv-inner --}}
</header>

<style>
/* ── Navbar ── */
.nv-bar {
    position: sticky;
    top: 0;
    z-index: 200;
    background: linear-gradient(180deg, #141414 0%, #111111 100%);
    border-bottom: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: 0 8px 24px rgba(2, 6, 23, .45);
    flex-shrink: 0;
}
.nv-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    height: 56px;
    gap: 12px;
}
.nv-left, .nv-right {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Icon buttons ── */
.nv-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    border-radius: 10px;
    background: rgba(30, 41, 59, .9);
    border: none;
    color: #f8fafc;
    cursor: pointer;
    transition: background .15s, color .15s, box-shadow .15s;
    flex-shrink: 0;
}
.nv-icon-btn:hover {
    background: rgba(51, 65, 85, .95);
    color: #f8fafc;
}
.nv-icon { width: 18px; height: 18px; }
.nv-fi-icon {
    font-size: 16px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.nv-icon-btn .fas,
.nv-icon-btn .far,
.nv-icon-btn i {
    color: #f8fafc !important;
}

/* ── POS button ── */
.nv-pos-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5c518, #d4a012);
    color: #fff !important;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: box-shadow .15s, transform .15s;
    box-shadow: 0 2px 8px rgba(37,99,235,.35);
    white-space: nowrap;
}
.nv-pos-btn:hover {
    box-shadow: 0 4px 16px rgba(37,99,235,.45);
    transform: translateY(-1px);
    color: #fff !important;
    text-decoration: none;
}

/* ── POP button (purchase) ── */
.nv-pop-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: #fff !important;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    transition: box-shadow .15s, transform .15s;
    box-shadow: 0 2px 8px rgba(13, 148, 136, .35);
    white-space: nowrap;
    letter-spacing: .02em;
}
.nv-pop-btn:hover {
    box-shadow: 0 4px 16px rgba(13, 148, 136, .45);
    transform: translateY(-1px);
    color: #fff !important;
    text-decoration: none;
}

/* ── Date chip ── */
.nv-date-chip {
    display: inline-flex;
    align-items: center;
    padding: 0 12px;
    height: 32px;
    border-radius: 8px;
    background: rgba(15, 23, 42, .95);
    border: 1px solid rgba(148, 163, 184, .24);
    font-size: 0.78rem;
    font-weight: 600;
    color: #cbd5e1;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

/* ── Back button (switch user) ── */
.nv-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 32px;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626 !important;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
}
.nv-back-btn:hover { background: #fecaca; text-decoration: none; }

/* ── Divider ── */
.nv-divider {
    width: 1px; height: 24px;
    background: rgba(148, 163, 184, .3);
    margin: 0 4px;
}

/* ── User button ── */
.nv-user-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px 4px 4px;
    height: 40px;
    border-radius: 12px;
    background: rgba(15, 23, 42, .95);
    border: 1px solid rgba(148, 163, 184, .28);
    cursor: pointer;
    list-style: none;
    transition: background .15s, border-color .15s;
}
.nv-user-btn:hover { background: #1f1f1f; border-color: #444; }
.nv-avatar {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #f5c518, #d4a012);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.nv-username {
    font-size: 0.82rem;
    font-weight: 600;
    color: #f8fafc;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.nv-chevron { width: 14px; height: 14px; color: #cbd5e1; }

/* ── Dropdown ── */
.nv-dropdown-wrap { position: relative; display: inline-block; }
.nv-dropdown-wrap > summary { list-style: none; }
.nv-dropdown-wrap > summary::-webkit-details-marker { display: none; }
.nv-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 200px;
    background: linear-gradient(180deg, #0b1220 0%, #0a1630 100%);
    border: 1px solid rgba(148, 163, 184, .28);
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(2, 6, 23, .6);
    padding: 6px;
    z-index: 9999;
    list-style: none;
    margin: 0;
}
.nv-dropdown-right { left: auto; right: 0; }
.nv-drop-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 12px;
    border-radius: 9px;
    font-size: 0.84rem;
    font-weight: 500;
    color: #ffffff;
    text-decoration: none !important;
    transition: background .12s, color .12s;
    cursor: pointer;
}
.nv-drop-item:hover { background: rgba(59, 130, 246, .26); color: #ffffff; }
.nv-drop-item-danger { color: #dc2626 !important; }
.nv-drop-item-danger:hover { background: #fee2e2 !important; color: #b91c1c !important; }
.nv-drop-icon { width: 16px; text-align: center; color: #eff6ff; font-size: 0.85rem; flex-shrink: 0; }
.nv-drop-item:hover .nv-drop-icon { color: #93c5fd; }
.nv-drop-item-danger:hover .nv-drop-icon { color: #dc2626 !important; }
.nv-drop-sep { height: 1px; background: rgba(148, 163, 184, .32); margin: 4px 6px; }
.nv-drop-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px 8px;
}
.nv-drop-avatar {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5c518, #d4a012);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.nv-drop-name { font-size: 0.92rem; font-weight: 800; color: #ffffff; }
.nv-drop-role { font-size: 0.76rem; color: #f1f5f9; margin-top: 1px; font-weight: 600; }
.nv-drop-header {
    background: rgba(30, 58, 138, .16);
    border: 1px solid rgba(96, 165, 250, .22);
    border-radius: 12px;
    margin: 2px 4px 6px;
}
.nv-dropdown a,
.nv-dropdown li,
.nv-dropdown span {
    color: #f8fafc !important;
}
.nv-dropdown .nv-drop-item-danger,
.nv-dropdown .nv-drop-item-danger i {
    color: #ff4d4f !important;
}

/* ── Notifications bell inherits nv-icon-btn look ── */
.notifications-menu > a.dropdown-toggle {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 36px !important; height: 36px !important;
    border-radius: 10px !important;
    background: rgba(30, 41, 59, .9) !important;
    border: none !important;
    color: #f8fafc !important;
    transition: background .15s, color .15s !important;
    padding: 0 !important;
    position: relative;
}
.notifications-menu > a.dropdown-toggle:hover {
    background: rgba(51, 65, 85, .95) !important;
    color: #fff !important;
}
.notifications-menu > a.dropdown-toggle i,
.notifications-menu > a.dropdown-toggle .fa,
.notifications-menu > a.dropdown-toggle .fas {
    color: #f8fafc !important;
}
.notifications-menu > a.dropdown-toggle svg {
    width: 18px; height: 18px;
}

/* keep dark style from being overridden by older classes */
.nv-bar *,
.nv-bar a,
.nv-bar button,
.nv-bar summary {
    color: inherit;
}
.notifications-menu > a .label {
    position: absolute;
    top: -4px; right: -4px;
    min-width: 18px; height: 18px;
    padding: 0 4px;
    border-radius: 9px;
    background: #ef4444 !important;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
    border: 2px solid #fff;
}

@media (max-width: 640px) {
    .nv-inner { padding: 0 12px; height: 52px; }
    .nv-date-chip, .nv-divider { display: none !important; }
}
.nv-mobile-only {
    display: none;
}
@media (max-width: 991px) {
    .nv-mobile-only {
        display: list-item;
    }
    .nv-desktop-action {
        display: none !important;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calcLink = document.getElementById('mobile-open-calculator');
    var profitLink = document.getElementById('mobile-open-profit');
    var calcBtn = document.getElementById('btnCalculator');
    var profitBtn = document.getElementById('view_todays_profit');

    if (calcLink && calcBtn) {
        calcLink.addEventListener('click', function (e) {
            e.preventDefault();
            calcBtn.click();
        });
    }
    if (profitLink && profitBtn) {
        profitLink.addEventListener('click', function (e) {
            e.preventDefault();
            profitBtn.click();
        });
    }
});
</script>
