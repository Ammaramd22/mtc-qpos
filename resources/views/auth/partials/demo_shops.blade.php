@php
    $demo_shops = $demo_shops ?? [];
    $demos_enabled = (bool) config('app.show_demo_shops');
    $show_local_demos = $demos_enabled && count($demo_shops) > 0;
    $show_saas_demos = $demos_enabled && config('app.env') == 'demo';
    $demo_types = $demo_types ?? [];
    $active_shop = $active_shop ?? null;
@endphp

@if ($show_local_demos || $show_saas_demos)
<div class="vspos-demo-panel {{ $panel_class ?? '' }}">
    <div class="vspos-demo-panel-head">
        <h2>@lang('lang_v1.demo_shops')</h2>
        <p>@lang('lang_v1.demo_shops_help')</p>
    </div>
    <div class="vspos-demo-grid">
        @if ($show_local_demos)
            @foreach ($demo_shops as $shop)
                <button type="button"
                    class="vspos-demo-shop demo-login {{ ($active_shop === ($shop['username'] ?? null)) ? 'is-active' : '' }}"
                    data-admin="{{ $shop['username'] }}"
                    data-password="123456"
                    data-shop-name="{{ $shop['business_name'] ?? $shop['name'] }}"
                    data-shop-tagline="{{ $shop['tagline'] }}"
                    data-shop-theme="{{ $shop['theme'] ?? $shop['username'] }}"
                    data-shop-accent="{{ $shop['accent'] }}"
                    style="--demo-accent: {{ $shop['accent'] }};"
                    title="{{ $shop['business_name'] ?? $shop['name'] }}">
                    <span class="vspos-demo-shop-icon">
                        <i class="fas {{ $shop['icon'] }}"></i>
                    </span>
                    <span class="vspos-demo-shop-text">
                        <strong>{{ $shop['name'] }}</strong>
                        <small>{{ $shop['tagline'] }}</small>
                    </span>
                    <span class="vspos-demo-shop-go">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </button>
            @endforeach
        @elseif ($show_saas_demos)
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['all_in_one'] ?? 'admin' }}" data-password="123456" data-shop-name="All In One" data-shop-tagline="Full features" data-shop-theme="qpos" data-shop-accent="#3d9970" style="--demo-accent:#3d9970;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-star"></i></span>
                <span class="vspos-demo-shop-text"><strong>All In One</strong><small>Full features</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['pharmacy'] ?? 'admin-pharmacy' }}" data-password="123456" data-shop-name="Pharmacy" data-shop-tagline="Expiry products" data-shop-theme="pharmacy" data-shop-accent="#d81b60" style="--demo-accent:#d81b60;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-medkit"></i></span>
                <span class="vspos-demo-shop-text"><strong>Pharmacy</strong><small>Expiry products</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['super_market'] ?? 'admin' }}" data-password="123456" data-shop-name="Super Market" data-shop-tagline="Retail" data-shop-theme="supermarket" data-shop-accent="#4a6fa5" style="--demo-accent:#4a6fa5;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-shopping-cart"></i></span>
                <span class="vspos-demo-shop-text"><strong>Super Market</strong><small>Retail</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['electronics'] ?? 'admin-electronics' }}" data-password="123456" data-shop-name="Electronics" data-shop-tagline="IMEI / Serial" data-shop-theme="electronics" data-shop-accent="#605ca8" style="--demo-accent:#605ca8;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-laptop"></i></span>
                <span class="vspos-demo-shop-text"><strong>Electronics</strong><small>IMEI / Serial</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['restaurant'] ?? 'admin-restaurant' }}" data-password="123456" data-shop-name="Restaurant" data-shop-tagline="Tables & kitchen" data-shop-theme="restaurant" data-shop-accent="#dd4b39" style="--demo-accent:#dd4b39;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-utensils"></i></span>
                <span class="vspos-demo-shop-text"><strong>Restaurant</strong><small>Tables & kitchen</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
            <button type="button" class="vspos-demo-shop demo-login" data-admin="{{ $demo_types['services'] ?? 'admin-services' }}" data-password="123456" data-shop-name="Services" data-shop-tagline="Multi-service" data-shop-theme="services" data-shop-accent="#ff851b" style="--demo-accent:#ff851b;">
                <span class="vspos-demo-shop-icon"><i class="fas fa-wrench"></i></span>
                <span class="vspos-demo-shop-text"><strong>Services</strong><small>Multi-service</small></span>
                <span class="vspos-demo-shop-go"><i class="fas fa-arrow-right"></i></span>
            </button>
        @endif
    </div>
</div>
@endif
