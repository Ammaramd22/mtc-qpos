{{-- Dashboard quick menu — main destinations as boxed-icon buttons --}}
@php
    $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
    $quick_items = [];

    if (auth()->user()->can('sell.create')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\SellPosController::class, 'create']),
            'label' => __('sale.pos_sale'),
            'sub' => 'Open checkout',
            'tone' => 'gold',
            'icon' => '<path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/><path d="M8 11h8"/><path d="M8 15h5"/>',
        ];
    }

    if (auth()->user()->can('product.view') || auth()->user()->can('product.create')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\ProductController::class, 'index']),
            'label' => __('sale.products'),
            'sub' => __('lang_v1.manage_products'),
            'tone' => 'blue',
            'icon' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12L4 7.5"/>',
        ];
    }

    if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view') || auth()->user()->can('direct_sell.access') || auth()->user()->can('view_own_sell_only')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\SellController::class, 'index']),
            'label' => __('sale.sale'),
            'sub' => __('sale.list_sale'),
            'tone' => 'amber',
            'icon' => '<path d="M14 3H7a2 2 0 0 0-2 2v14l3-1.5L12 19l4-1.5 3 1.5V8z"/><path d="M14 3v5h5"/><path d="M9 11h6"/><path d="M9 14h4"/>',
        ];
    }

    if (in_array('purchases', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create') || auth()->user()->can('view_own_purchase'))) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\PurchaseController::class, 'index']),
            'label' => __('purchase.purchases'),
            'sub' => __('purchase.list_purchase'),
            'tone' => 'cyan',
            'icon' => '<path d="M3 6h18"/><path d="M5 6v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6"/><path d="M8 10h8"/><path d="M8 14h5"/>',
        ];
    }

    if (auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\ContactController::class, 'index'], ['type' => 'customer']),
            'label' => __('contact.contacts'),
            'sub' => __('lang_v1.customers'),
            'tone' => 'green',
            'icon' => '<path d="M9 7a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4-4h4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/>',
        ];
    }

    if (in_array('expenses', $enabled_modules) && (auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense'))) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\ExpenseController::class, 'index']),
            'label' => __('expense.expenses'),
            'sub' => __('lang_v1.list_expenses'),
            'tone' => 'red',
            'icon' => '<path d="M12 3v18"/><path d="M16 7H9.5a2.5 2.5 0 0 0 0 5h5a2.5 2.5 0 0 1 0 5H8"/>',
        ];
    }

    if (auth()->user()->can('purchase_n_sell_report.view') || auth()->user()->can('expense_report.view') || auth()->user()->can('stock_report.view')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\ReportController::class, 'getProfitLoss']),
            'label' => __('report.reports'),
            'sub' => __('report.profit_loss'),
            'tone' => 'purple',
            'icon' => '<path d="M4 19h16"/><path d="M6 15l4-4 3 3 5-6"/><path d="M18 8h-3"/>',
        ];
    }

    if (auth()->user()->can('stock_report.view')) {
        $quick_items[] = [
            'url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']),
            'label' => __('report.stock_report'),
            'sub' => __('report.closing_stock'),
            'tone' => 'yellow',
            'icon' => '<path d="M3 7l9-4 9 4-9 4-9-4"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/>',
        ];
    }

    if (class_exists(\App\Utils\ShopModeUtil::class)) {
        $industry_home = \App\Utils\ShopModeUtil::dashboardLinks()[0] ?? null;
        if ($industry_home) {
            $quick_items[] = [
                'url' => url($industry_home['url']),
                'label' => __($industry_home['label']),
                'sub' => __('lang_v1.shop_mode'),
                'tone' => 'gold',
                'icon' => '<path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/><path d="M8 11h8"/>',
            ];
        }
    }
@endphp

@if(!empty($quick_items))
<div class="db-quick-menu">
    <div class="db-quick-menu-head">
        <span class="db-quick-menu-title">Menu</span>
        <span class="db-quick-menu-sub">Main shortcuts</span>
    </div>
    <div class="db-quick-grid">
        @foreach($quick_items as $item)
            <a href="{{ $item['url'] }}" class="db-quick-btn">
                <span class="db-stat-icon {{ $item['tone'] }}">
                    <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                </span>
                <span class="db-quick-text">
                    <span class="db-quick-label">{{ $item['label'] }}</span>
                    <span class="db-quick-sub">{{ $item['sub'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>

@once
<style>
.db-quick-menu {
    --db-quick-bg: #111111;
    --db-quick-line: #2a2a2a;
    --db-quick-btn: #0a0a0a;
    --db-quick-btn-hover: #0f0f0f;
    --db-quick-title: #f5f5f5;
    --db-quick-sub: #a3a3a3;
    margin: 0 20px 14px;
    padding: 14px 16px 16px;
    background: var(--db-quick-bg);
    border: 1px solid var(--db-quick-line);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,.28);
}
.db-quick-menu-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}
.db-quick-menu-title {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--db-quick-title);
}
.db-quick-menu-sub {
    font-size: 0.72rem;
    color: var(--db-quick-sub);
    font-weight: 600;
}
.db-quick-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.db-quick-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    background: var(--db-quick-btn);
    border: 1px solid var(--db-quick-line);
    text-decoration: none !important;
    color: inherit !important;
    transition: transform .15s, border-color .15s, box-shadow .15s, background .15s;
    min-height: 68px;
}
.db-quick-btn:hover,
.db-quick-btn:focus {
    border-color: rgba(245, 197, 24, 0.35);
    box-shadow: 0 8px 20px rgba(0,0,0,.35);
    transform: translateY(-2px);
    background: var(--db-quick-btn-hover);
    outline: none;
}
.db-quick-btn:active {
    transform: scale(0.98);
}
.db-quick-text {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.db-quick-label {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--db-quick-title);
    line-height: 1.15;
}
.db-quick-sub {
    font-size: 0.7rem;
    color: var(--db-quick-sub);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-stat-icon.gold {
    background: rgba(245, 197, 24, 0.18);
    color: #f5c518;
}
html[data-vspos-theme="light"] .db-quick-menu {
    --db-quick-bg: #ffffff;
    --db-quick-line: #e5e7eb;
    --db-quick-btn: #f8fafc;
    --db-quick-btn-hover: #f3f4f6;
    --db-quick-title: #111827;
    --db-quick-sub: #4b5563;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
html[data-vspos-theme="light"] .db-stat-icon.gold {
    background: #fef3c7;
    color: #b45309 !important;
}
@media (max-width: 1100px) {
    .db-quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 600px) {
    .db-quick-menu { margin: 0 14px 12px; padding: 12px; border-radius: 14px; }
    .db-quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .db-quick-btn { padding: 10px 12px; min-height: 62px; gap: 10px; }
    .db-quick-label { font-size: 0.82rem; }
}
</style>
@endonce
@endif
