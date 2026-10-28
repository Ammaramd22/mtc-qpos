{{-- Mobile / PWA bottom nav — boxed icons for main destinations --}}
@php
    $seg1 = request()->segment(1);
    $can_pos = auth()->user()->can('sell.create');
    $can_products = auth()->user()->can('product.view') || auth()->user()->can('product.create');
    $can_invoices = auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view') || auth()->user()->can('direct_sell.access') || auth()->user()->can('view_own_sell_only');
    $can_reports = auth()->user()->can('purchase_n_sell_report.view') || auth()->user()->can('expense_report.view') || auth()->user()->can('stock_report.view');
    $pos_url = action([\App\Http\Controllers\SellPosController::class, 'create']);
    $products_url = action([\App\Http\Controllers\ProductController::class, 'index']);
    $invoices_url = action([\App\Http\Controllers\SellController::class, 'index']);
    $home_url = route('home');
    $reports_url = route('reports.hub');
    $home_active = $seg1 === 'home' || $seg1 === null || $seg1 === '';
    $products_active = $seg1 === 'products';
    $pos_active = $seg1 === 'pos';
    $invoices_active = $seg1 === 'sells';
    $reports_active = $seg1 === 'reports'
        || ($seg1 === 'cheque' && request()->segment(2) === 'report')
        || ($seg1 === 'nutmeg' && request()->segment(2) === 'reports');
@endphp

<nav class="qpos-bottom-nav no-print" aria-label="Main menu" id="qpos_bottom_nav">
    <a href="{{ $home_url }}" class="qbn-item {{ $home_active ? 'is-active' : '' }}" @if($home_active) aria-current="page" @endif>
        <span class="qbn-icon qbn-home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l-2 0l9-9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/><path d="M10 12h4v4h-4z"/></svg>
        </span>
        <span class="qbn-label">{{ __('home.home') }}</span>
    </a>

    @if($can_products)
    <a href="{{ $products_url }}" class="qbn-item {{ $products_active ? 'is-active' : '' }}" @if($products_active) aria-current="page" @endif>
        <span class="qbn-icon qbn-products">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12L4 7.5"/></svg>
        </span>
        <span class="qbn-label">{{ __('sale.products') }}</span>
    </a>
    @endif

    @if($can_pos)
    <a href="{{ $pos_url }}" class="qbn-item qbn-pos {{ $pos_active ? 'is-active' : '' }}" @if($pos_active) aria-current="page" @endif>
        <span class="qbn-icon qbn-pos-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
        </span>
        <span class="qbn-label">POS</span>
    </a>
    @endif

    @if($can_invoices)
    <a href="{{ $invoices_url }}" class="qbn-item {{ $invoices_active ? 'is-active' : '' }}" @if($invoices_active) aria-current="page" @endif>
        <span class="qbn-icon qbn-invoices">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14l3-1.5L12 19l4-1.5 3 1.5V8z"/><path d="M14 3v5h5"/><path d="M9 11h6"/><path d="M9 14h4"/></svg>
        </span>
        <span class="qbn-label">{{ __('sale.sale') }}</span>
    </a>
    @endif

    @if($can_reports)
    <a href="{{ $reports_url }}" class="qbn-item {{ $reports_active ? 'is-active' : '' }}" @if($reports_active) aria-current="page" @endif>
        <span class="qbn-icon qbn-reports">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16"/><path d="M6 15l4-4 3 3 5-6"/><path d="M18 8h-3"/></svg>
        </span>
        <span class="qbn-label">{{ __('report.reports') }}</span>
    </a>
    @endif
</nav>

@once
<style>
/* Dark theme is the default. Light theme overrides sit below and only apply
   when html[data-vspos-theme="light"] is set by the existing theme switch. */
body:not(.lockscreen) {
    --qbn-clearance: calc(96px + env(safe-area-inset-bottom, 0px));
}
.qpos-bottom-nav {
    display: none;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1040;
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
    align-items: flex-end;
    justify-content: space-around;
    gap: 2px;
    padding: 8px 6px calc(8px + env(safe-area-inset-bottom, 0px));
    background: linear-gradient(180deg, #111111 0%, #0a0a0a 100%);
    border-top: 1px solid #222;
    box-shadow: 0 -8px 28px rgba(0, 0, 0, 0.45);
}
.qbn-item {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    padding: 4px 2px;
    text-decoration: none !important;
    color: #a3a3a3 !important;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    border-radius: 12px;
}
button.qbn-item {
    background: transparent;
    border: 0;
    margin: 0;
    font: inherit;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}
.qbn-item:focus {
    outline: none;
}
.qbn-item:focus-visible {
    outline: 2px solid #f5c518;
    outline-offset: 2px;
}
.qbn-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.04);
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.14);
    transition: background .15s, box-shadow .15s, transform .15s, color .15s;
}
.qbn-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}
.qbn-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    text-align: center;
}
.qbn-home { color: #d4d4d4; }
.qbn-products { color: #60a5fa; background: rgba(96, 165, 250, 0.12); }
.qbn-invoices { color: #f5c518; background: rgba(245, 197, 24, 0.14); }
.qbn-reports { color: #4ade80; background: rgba(74, 222, 128, 0.12); }
.qbn-pos-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    color: #111 !important;
    background: linear-gradient(145deg, #f5c518 0%, #e6b800 55%, #c4920a 100%) !important;
    box-shadow: 0 6px 16px rgba(245, 197, 24, 0.35), inset 0 0 0 1px rgba(255, 255, 255, 0.2);
    margin-top: -10px;
}
.qbn-pos .qbn-label {
    color: #f5c518 !important;
    font-weight: 800;
}
.qbn-item.is-active,
.qbn-item.is-open {
    color: #f5f5f5 !important;
}
.qbn-item.is-active .qbn-icon:not(.qbn-pos-icon),
.qbn-item.is-open .qbn-icon:not(.qbn-pos-icon) {
    box-shadow: inset 0 0 0 1px rgba(245, 197, 24, 0.45), 0 0 0 3px rgba(245, 197, 24, 0.12);
    transform: translateY(-1px);
}
.qbn-item.is-active .qbn-label,
.qbn-item.is-open .qbn-label {
    color: #f5c518 !important;
}
.qbn-item:hover:not(.is-active) .qbn-icon:not(.qbn-pos-icon) {
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.32);
}
.qbn-pos:hover .qbn-pos-icon,
.qbn-pos:focus-visible .qbn-pos-icon {
    box-shadow: 0 8px 18px rgba(245, 197, 24, 0.45), inset 0 0 0 1px rgba(255, 255, 255, 0.28);
}
.qbn-item:active .qbn-icon {
    transform: scale(0.94);
}

html[data-vspos-theme="light"] .qpos-bottom-nav {
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
    box-shadow: 0 -6px 18px rgba(15, 23, 42, 0.06);
}
html[data-vspos-theme="light"] .qbn-item {
    color: #4b5563 !important;
}
html[data-vspos-theme="light"] .qbn-icon:not(.qbn-pos-icon) {
    background: #f3f4f6;
    box-shadow: inset 0 0 0 1px #e5e7eb;
}
html[data-vspos-theme="light"] .qbn-home {
    color: #374151;
    background: #f3f4f6;
}
html[data-vspos-theme="light"] .qbn-products {
    color: #1d4ed8;
    background: #dbeafe;
}
html[data-vspos-theme="light"] .qbn-invoices {
    color: #b45309;
    background: #fef3c7;
}
html[data-vspos-theme="light"] .qbn-reports {
    color: #15803d;
    background: #dcfce7;
}
html[data-vspos-theme="light"] .qbn-pos .qbn-label {
    color: #a16207 !important;
}
html[data-vspos-theme="light"] .qbn-item.is-active,
html[data-vspos-theme="light"] .qbn-item.is-open {
    color: #111827 !important;
}
html[data-vspos-theme="light"] .qbn-item.is-active .qbn-icon:not(.qbn-pos-icon),
html[data-vspos-theme="light"] .qbn-item.is-open .qbn-icon:not(.qbn-pos-icon) {
    background: #fff7d6;
    color: #92400e;
    box-shadow: inset 0 0 0 1.5px #e6b800, 0 0 0 3px rgba(245, 197, 24, 0.28);
}
html[data-vspos-theme="light"] .qbn-item.is-active .qbn-label,
html[data-vspos-theme="light"] .qbn-item.is-open .qbn-label,
html[data-vspos-theme="light"] .qbn-pos.is-active .qbn-label {
    color: #b45309 !important;
}
html[data-vspos-theme="light"] .qbn-item:hover {
    color: #111827 !important;
}
html[data-vspos-theme="light"] .qbn-item:hover:not(.is-active) .qbn-icon:not(.qbn-pos-icon) {
    box-shadow: inset 0 0 0 1px #d1d5db;
}
html[data-vspos-theme="light"] .qbn-item:focus-visible {
    outline-color: #d97706;
}

@media (max-width: 991.98px) {
    body:not(.lockscreen) {
        height: 100vh;
        height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
    }
    body:not(.lockscreen) .thetop {
        height: 100%;
        max-height: 100%;
        overflow: hidden;
    }
    body:not(.lockscreen) .thetop > main {
        min-height: 0;
        height: 100%;
        max-height: 100%;
        overflow: hidden;
    }
    body:not(.lockscreen) .qpos-bottom-nav {
        display: flex;
    }
    body:not(.lockscreen) #scrollable-container {
        height: auto !important;
        max-height: none !important;
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: var(--qbn-clearance, calc(96px + env(safe-area-inset-bottom, 0px))) !important;
        scroll-padding-bottom: var(--qbn-clearance, calc(96px + env(safe-area-inset-bottom, 0px)));
    }
    /* app.css sets height:100vh and z-index:1040 with !important on .side-bar.
       Those beat a non-important override, so the drawer stays full-screen,
       the bottom bar covers the tail, and #side-bar grows instead of scrolling.
       !important here wins that cascade up to the same 992px breakpoint as the bar. */
    body:not(.lockscreen) .small-view-side-active {
        z-index: 1050 !important;
        top: 0 !important;
        bottom: var(--qbn-clearance, calc(96px + env(safe-area-inset-bottom, 0px))) !important;
        height: auto !important;
        max-height: none !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
        padding-bottom: 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }
    body:not(.lockscreen) .small-view-side-active .sb-brand,
    body:not(.lockscreen) .small-view-side-active .sb-search-wrap {
        flex: 0 0 auto !important;
    }
    body:not(.lockscreen) .small-view-side-active #side-bar {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        height: auto !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
        padding-bottom: 16px !important;
    }
    /* Report names stay readable when the reused sidebar is a narrow drawer. */
    body:not(.lockscreen) .small-view-side-active #tour_step8 + .chiled a,
    body:not(.lockscreen) .small-view-side-active #tour_step8 + .chiled a span {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        line-height: 1.35;
    }
    body:not(.lockscreen) .scrolltop {
        bottom: calc(108px + env(safe-area-inset-bottom, 0px)) !important;
    }
    body:not(.lockscreen) .vspos-ai-widget,
    body:not(.lockscreen) .ai-chat-widget,
    body:not(.lockscreen) #pwa-install-btn {
        bottom: calc(108px + env(safe-area-inset-bottom, 0px)) !important;
    }
}
@media (max-width: 380px) {
    .qbn-label {
        font-size: 9px;
        letter-spacing: 0;
    }
    .qbn-icon {
        width: 36px;
        height: 36px;
        border-radius: 11px;
    }
    .qbn-icon svg {
        width: 18px;
        height: 18px;
    }
    .qbn-pos-icon {
        width: 44px;
        height: 44px;
        border-radius: 13px;
    }
}
@media print {
    .qpos-bottom-nav { display: none !important; }
}
</style>
@endonce
