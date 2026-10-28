@inject('request', 'Illuminate\Http\Request')

@if (
    ($request->segment(1) == 'pos' &&
        ($request->segment(2) == 'create' || $request->segment(3) == 'edit' || $request->segment(2) == 'payment'))
    || ($request->segment(1) == 'purchase-pos' && $request->segment(2) == 'create')
)
    @php
        $pos_layout = true;
        $is_pop_layout = $request->segment(1) == 'purchase-pos';
    @endphp
@else
    @php
        $pos_layout = false;
        $is_pop_layout = false;
    @endphp
@endif

@php
    $whitelist = ['127.0.0.1', '::1'];
@endphp

<!DOCTYPE html>
<html class="@if($pos_layout) tw-scroll-smooth @else tw-bg-slate-950 tw-scroll-smooth @endif" lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">
<head>
    <script>
        (function () {
            var id = '{{ auth()->id() ?? 0 }}';
            var theme = 'dark';
            try {
                var saved = localStorage.getItem('vspos_theme_u_' + id);
                if (saved === 'light' || saved === 'dark') {
                    theme = saved;
                }
            } catch (e) {}
            document.documentElement.setAttribute('data-user-id', id);
            document.documentElement.setAttribute('data-vspos-theme', theme);
        })();
    </script>
    <!-- Tell the browser to be responsive to screen width -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover"
        name="viewport">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title') - {{ Session::get('business.name') }}</title>

    @php
        $pwaEnabled = !empty(session('business.common_settings')['enable_pwa'] ?? 0);
        $pwaCs = !empty(session('business.common_settings')) ? session('business.common_settings') : [];
        $pwaThemeColor = !empty($pwaCs['pwa_theme_color']) ? $pwaCs['pwa_theme_color'] : '#f5c518';
        $pwaIcon = !empty($pwaCs['pwa_icon']) ? asset('uploads/pwa/' . $pwaCs['pwa_icon']) : asset('img/qpos-icon.png');
        $pwaName = !empty($pwaCs['pwa_name']) ? $pwaCs['pwa_name'] : 'QPOS';
        if (stripos($pwaName, 'vsoft') !== false) {
            $pwaName = 'QPOS';
        }
        // Splash always uses QPOS brand (not legacy Vsoft assets)
        $splashIcon = asset('img/qpos-avenque-logo.png');
        $splashName = 'QPOS';
        $splashBg = $pos_layout ? '#0a0a0a' : (!empty($pwaCs['pwa_bg_color']) && stripos($pwaCs['pwa_bg_color'], 'fff') === false ? $pwaCs['pwa_bg_color'] : '#0a0a0a');
    @endphp
    @if($pwaEnabled)
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="{{ $pwaThemeColor }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $pwaName }}">
    <link rel="apple-touch-icon" href="{{ $pwaIcon }}">
    @endif

    @include('layouts.partials.css')
    

    @include('layouts.partials.extracss')

    @yield('css')

    @if($pos_layout)
    <link rel="stylesheet" href="{{ asset('css/pos-theme.css?v='.$asset_v) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/print-preview.css?v='.$asset_v) }}">
    <link rel="stylesheet" href="{{ asset('css/qpos-light.css?v='.$asset_v) }}">

    {{-- jQuery in <head> so $ exists before any body/POS scripts (vendor.js can 500 on some hosts). --}}
    <script src="{{ asset('js/jquery-3.7.1.min.js?v=' . $asset_v) }}"></script>
    <script>
        if (typeof window.jQuery !== 'undefined') {
            window.$ = window.jQuery;
        }
    </script>

</head>
<body
    class="tw-font-sans tw-antialiased @if ($pos_layout) hold-transition lockscreen tw-text-gray-100 @else tw-text-gray-100 tw-bg-slate-950 hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif @if (!is_tax_enabled()) tax-system-disabled @endif" >

    {{-- QPOS Splash Screen --}}
    @if($pwaEnabled)
    <div id="pwa-splash-screen" class="vspos-splash" aria-hidden="true">
        <div class="vspos-splash-inner">
            <div class="vspos-splash-logo-wrap">
                <img src="{{ $splashIcon }}" alt="{{ $splashName }}" class="vspos-splash-logo">
            </div>
            <h1 class="vspos-splash-title">{{ $splashName }}</h1>
            <p class="vspos-splash-sub">BY AVENQUE</p>
            <p class="vspos-splash-loading">Loading...</p>
            <div class="vspos-splash-spinner" style="--splash-accent: {{ $pwaThemeColor }};"></div>
        </div>
    </div>
    <style>
    .vspos-splash {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(ellipse 70% 50% at 50% 35%, rgba(245, 197, 24, 0.12), transparent 60%),
            {{ $splashBg }};
        transition: opacity 0.55s ease;
    }
    .vspos-splash-inner {
        text-align: center;
        padding: 24px;
    }
    .vspos-splash-logo-wrap {
        width: 148px;
        height: 148px;
        margin: 0 auto 20px;
        border-radius: 28px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(245, 197, 24, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .vspos-splash-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .vspos-splash-title {
        margin: 0 0 4px;
        font-size: 32px;
        font-weight: 900;
        letter-spacing: 0.08em;
        color: #fff;
        font-family: var(--qpos-font, 'Plus Jakarta Sans', system-ui, sans-serif);
    }
    .vspos-splash-sub {
        margin: 0 0 18px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.22em;
        color: #f5c518;
        font-family: var(--qpos-font, 'Plus Jakarta Sans', system-ui, sans-serif);
    }
    .vspos-splash-loading {
        margin: 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.45);
        font-family: var(--qpos-font, 'Plus Jakarta Sans', system-ui, sans-serif);
    }
    .vspos-splash-spinner {
        width: 36px;
        height: 36px;
        margin: 22px auto 0;
        border: 3px solid rgba(255, 255, 255, 0.12);
        border-top-color: var(--splash-accent, #f5c518);
        border-radius: 50%;
        animation: pwaSpin 0.85s linear infinite;
    }
    @keyframes pwaSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <script>
        (function() {
            function hideSplash() {
                var splash = document.getElementById('pwa-splash-screen');
                if (splash) {
                    splash.style.opacity = '0';
                    setTimeout(function() {
                        splash.style.display = 'none';
                    }, 550);
                }
            }
            if (document.readyState === 'complete') {
                hideSplash();
            } else {
                window.addEventListener('load', hideSplash);
            }
        })();
    </script>
    @endif

    <div class="tw-flex thetop">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }
        </script>
        @if (!$pos_layout && $request->segment(1) != 'customer-display')
            @include('layouts.partials.sidebar')
        @endif

        @if (in_array($_SERVER['REMOTE_ADDR'], $whitelist))
            <input type="hidden" id="__is_localhost" value="true">
        @endif

        <!-- Add currency related field-->
        <input type="hidden" id="__code" value="{{ session('currency')['code'] }}">
        <input type="hidden" id="__symbol" value="{{ session('currency')['symbol'] }}">
        <input type="hidden" id="__thousand" value="{{ session('currency')['thousand_separator'] }}">
        <input type="hidden" id="__decimal" value="{{ session('currency')['decimal_separator'] }}">
        <input type="hidden" id="__symbol_placement" value="{{ session('business.currency_symbol_placement') }}">
        <input type="hidden" id="__precision" value="{{ session('business.currency_precision', 2) }}">
        <input type="hidden" id="__quantity_precision" value="{{ session('business.quantity_precision', 2) }}">
        <!-- End of currency related field-->
        @can('view_export_buttons')
            <input type="hidden" id="view_export_buttons">
        @endcan
        @if (isMobile())
            <input type="hidden" id="__is_mobile">
        @endif
        @if (session('status'))
            <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
                data-msg="{{ session('status.msg') }}">
        @endif
        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0 @if(!$pos_layout) @endif" style="@if(!$pos_layout) background:#050505; @endif">
            @if($request->segment(1) != 'customer-display' && !$pos_layout)
                @include('layouts.partials.header')
            @elseif($request->segment(1) != 'customer-display')
                {{-- POS + POP: same action header (bills/purchases reprint hub, register, etc.) --}}
                @include('layouts.partials.header-pos')
            @endif
            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>
            <div class="tw-flex-1 tw-overflow-y-auto tw-h-screen" id="scrollable-container">
                @yield('content')
                @if (!$pos_layout)
                
                    @include('layouts.partials.footer')
                @else
                    @include('layouts.partials.footer_pos')
                @endif
            </div>
            <div class='scrolltop no-print'>
                <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
            </div>

            @if (!$pos_layout && $request->segment(1) != 'customer-display')
                @include('layouts.partials.mobile_bottom_nav')
            @endif

            @if (config('constants.iraqi_selling_price_adjustment'))
                <input type="hidden" id="iraqi_selling_price_adjustment">
            @endif

            <!-- This will be printed -->
            <section class="invoice print_section" id="receipt_section">
            </section>
            @include('sale_pos.partials.print_preview_modal')
        </main>

        @include('home.todays_profit_modal')
        <!-- /.content-wrapper -->



        <audio id="success-audio">
            <source src="{{ asset('/audio/success.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/success.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="error-audio">
            <source src="{{ asset('/audio/error.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/error.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="warning-audio">
            <source src="{{ asset('/audio/warning.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/warning.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        @php
            $__mute_sound = false;
            try {
                $__ps = session('business.pos_settings');
                if (is_string($__ps)) {
                    $__ps = json_decode($__ps, true) ?: [];
                }
                $__mute_sound = !empty($__ps['mute_sound']);
            } catch (\Throwable $e) {
                $__mute_sound = false;
            }
        @endphp
        <script>window.QPOS_MUTE_SOUND = {{ $__mute_sound ? 'true' : 'false' }};</script>

        @if (!empty($__additional_html))
            {!! $__additional_html !!}
        @endif

        {{-- Re-assert $ before bundle scripts (head load can be skipped by aggressive SW caches). --}}
        <script src="{{ asset('js/jquery-3.7.1.min.js?v=' . $asset_v) }}"></script>
        <script>
            if (typeof window.jQuery !== 'undefined') {
                window.$ = window.jQuery;
            }
        </script>

        @include('layouts.partials.javascripts')

        {{-- PWA Service Worker — cache-bust so clients pick up sw.js fixes --}}
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset("sw.js") }}?v=10')
                    .then(function(registration) {
                        // Force update check so old HTML-caching SW is replaced
                        registration.update();
                    })
                    .catch(function() {});
            });
        }
        </script>

        @if($pwaEnabled)
        {{-- Install App Floating Button --}}
        <div id="pwa-install-btn" class="no-print" style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999;">
            <button type="button" onclick="triggerPWAInstall()"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; border-radius:50px; padding:10px 20px; font-size:13px; font-weight:600; box-shadow:0 4px 16px rgba(99,102,241,0.4); display:flex; align-items:center; gap:8px; cursor:pointer;">
                <i class="fas fa-download"></i> Install App
            </button>
        </div>
        <script>
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            var btn = document.getElementById('pwa-install-btn');
            if (btn) btn.style.display = 'block';
            var menuItem = document.getElementById('menu-install-app');
            if (menuItem) menuItem.style.display = 'block';
        });
        function triggerPWAInstall() {
            if (!deferredPrompt) {
                alert('App install is not available in this browser. Please use Chrome/Edge on Android or Safari on iOS.');
                return;
            }
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    var btn = document.getElementById('pwa-install-btn');
                    if (btn) btn.style.display = 'none';
                    var menuItem = document.getElementById('menu-install-app');
                    if (menuItem) menuItem.style.display = 'none';
                }
                deferredPrompt = null;
            });
        }
        window.addEventListener('appinstalled', function() {
            var btn = document.getElementById('pwa-install-btn');
            if (btn) btn.style.display = 'none';
            var menuItem = document.getElementById('menu-install-app');
            if (menuItem) menuItem.style.display = 'none';
            deferredPrompt = null;
        });
        </script>
        @endif

        {{-- Module JS --}}
        @include('layouts.module-assets')
        <div class="modal fade view_modal @if($pos_layout) vspos-pos-modal @endif" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (!empty($__additional_views) && is_array($__additional_views))
            @foreach ($__additional_views as $additional_view)
                @includeIf($additional_view)
            @endforeach
        @endif
        <div>

            <div class="overlay tw-hidden"></div>
        </div>
        @if(in_array('ai_assistant', session('business.enabled_modules', [])))
            @includeIf('ai.chat_widget')
        @endif
</body>
<style>
    html:not([data-vspos-theme]) body,
    html:not([data-vspos-theme]) body .thetop,
    html:not([data-vspos-theme]) body #scrollable-container,
    html:not([data-vspos-theme]) body main {
        background: #050505 !important;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) .thetop,
    html[data-vspos-theme="dark"] body:not(.lockscreen) #scrollable-container,
    html[data-vspos-theme="dark"] body:not(.lockscreen) main {
        background: #050505 !important;
    }
    html[data-vspos-theme="dark"] body:not(.lockscreen) .content-wrapper,
    html[data-vspos-theme="dark"] body:not(.lockscreen) .right-side,
    html[data-vspos-theme="dark"] body:not(.lockscreen) .wrapper,
    html[data-vspos-theme="dark"] body:not(.lockscreen) section.content {
        background: #050505 !important;
    }
    @media print {
        #scrollable-container {
            overflow: visible !important;
            height: auto !important;
        }
        
        /* Hide side menu */
        .side-bar,
        .thetop > aside {
            display: none !important;
        }

        /* Force light paper for admin/report prints (dark theme override) */
        html,
        body:not(.lockscreen),
        body:not(.lockscreen) .thetop,
        body:not(.lockscreen) #scrollable-container,
        body:not(.lockscreen) main,
        body:not(.lockscreen) .content-wrapper,
        body:not(.lockscreen) .right-side,
        body:not(.lockscreen) .wrapper,
        body:not(.lockscreen) section.content,
        body:not(.lockscreen) .box,
        body:not(.lockscreen) .box-body,
        body:not(.lockscreen) .box-header {
            background: #fff !important;
            color: #000 !important;
        }
        body:not(.lockscreen) .box-title,
        body:not(.lockscreen) .content-header h1,
        body:not(.lockscreen) .table,
        body:not(.lockscreen) .table th,
        body:not(.lockscreen) .table td,
        body:not(.lockscreen) .tw-text-black,
        body:not(.lockscreen) .tw-text-gray-100 {
            color: #000 !important;
        }
        body:not(.lockscreen) .bg-gray {
            background: #eee !important;
            color: #000 !important;
        }
    }
</style>
<style>
    .small-view-side-active {
        display: flex !important;
        flex-direction: column;
        z-index: 1000;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: min(84vw, 320px) !important;
        background: #111111 !important;
        border-right: 1px solid #2a2a2a !important;
        box-shadow: 0 16px 48px rgba(2, 6, 23, .6);
    }
    .overlay {
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        position: fixed;
        top: 0;
        left: 0;
        display: none;
        z-index: 20;
    }

    .tw-dw-btn.tw-dw-btn-xs.tw-dw-btn-outline {
        width: max-content;
        margin: 2px;
    }

    #scrollable-container{
        position:relative;
    }
    



</style>
<style>
/* Final dark-mode force overrides (loaded last) */
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"],
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"],
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters.box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters.box {
    background: #111111 !important;
    color: #e2e8f0 !important;
    border: 1px solid #2a2a2a !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"] > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"] > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .box-header {
    background: #141414 !important;
    color: #f8fafc !important;
    border-bottom: 1px solid #2a2a2a !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters:hover > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"]:hover > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"]:hover > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .box-header:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .box-header:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters.collapsed-box > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"].collapsed-box > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"].collapsed-box > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .btn-box-tool:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .btn-box-tool:hover {
    background: #141414 !important;
    color: #f8fafc !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters > .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"] > .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"] > .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .box-body {
    background: #111111 !important;
    color: #e2e8f0 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) #filters label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"] label {
    color: #cbd5e1 !important;
}

/* Reinforce dark theme after extracss structural overrides */
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_filter input,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_length select {
    background: #141414 !important;
    border-color: #333333 !important;
    color: #f8fafc !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_info {
    color: #94a3b8 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li.paginate_button {
    display: inline-flex !important;
    float: none !important;
    background: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    min-width: 0 !important;
    min-height: 0 !important;
    color: inherit !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > span,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li > span,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate > a.paginate_button,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate > span > a.paginate_button {
    float: none !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    background: #141414 !important;
    border: 1px solid #333333 !important;
    color: #e2e8f0 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > span:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li:not(.disabled):not(.active) > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate > a.paginate_button:hover:not(.current):not(.disabled) {
    background: #1f1f1f !important;
    border-color: #444444 !important;
    color: #f8fafc !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > span,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > span:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > a:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > span:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li.active > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li.active > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate > a.paginate_button.current,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate > span > a.paginate_button.current {
    background: #f5c518 !important;
    border-color: #f5c518 !important;
    color: #111111 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > span,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > span:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > a:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > span:focus {
    background: #141414 !important;
    border-color: #2a2a2a !important;
    color: #64748b !important;
    cursor: not-allowed !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--single {
    background: #141414 !important;
    border-color: #333333 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs {
    border-bottom-color: #2a2a2a !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs > li > a {
    color: #cbd5e1 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs > li.active > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs > li.active > a:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs > li.active > a:hover {
    color: #93c5fd !important;
    border-bottom-color: #3b82f6 !important;
}

/* Filter panel + select2 hover — prevent light flash */
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--single:hover {
    border-color: #444444 !important;
    background: #141414 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default.select2-container--focus .select2-selection--single,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default.select2-container--open .select2-selection--single {
    background: #141414 !important;
    border-color: #3b82f6 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown {
    background: #111111 !important;
    border-color: #333333 !important;
    color: #e2e8f0 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown {
    background: #111111 !important;
    padding: 8px !important;
    border-bottom: 1px solid #2a2a2a !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown .select2-search__field,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown .select2-search__field,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-search--dropdown .select2-search__field {
    background: #1a1a1a !important;
    background-color: #1a1a1a !important;
    border: 1px solid #333333 !important;
    color: #f5f5f5 !important;
    border-radius: 8px !important;
    box-shadow: none !important;
    outline: none !important;
    -webkit-text-fill-color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown .select2-search__field:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown .select2-search__field:focus,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-search--dropdown .select2-search__field:focus {
    border-color: #f5c518 !important;
    box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.12) !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown .select2-search__field::placeholder,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown .select2-search__field::placeholder {
    color: #737373 !important;
    opacity: 1 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option {
    color: #e2e8f0 !important;
    background: transparent !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option.select2-results__message,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__message {
    color: #a3a3a3 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option--highlighted[aria-selected] {
    background: rgba(37, 99, 235, .45) !important;
    color: #fff !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option[aria-selected=true] {
    background: rgba(30, 41, 59, .95) !important;
    color: #f8fafc !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pf-card {
    background: #111111 !important;
    border: 1px solid #2a2a2a !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pf-btn:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pf-btn:focus {
    background: #141414 !important;
}
html[data-vspos-theme="dark"] body:not(.lockscreen) .pf-card .form-control:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pf-card .form-control:focus {
    background: rgba(15, 23, 42, .95) !important;
    border-color: rgba(96, 165, 250, .5) !important;
    color: #f8fafc !important;
}

/* SweetAlert — Vsoft dark theme (Cancel / confirm dialogs) */
html[data-vspos-theme="dark"] .swal-overlay {
    background: rgba(0, 0, 0, 0.82) !important;
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
}
html[data-vspos-theme="dark"] .swal-modal {
    background: #141414 !important;
    border: 1px solid #2a2a2a !important;
    border-radius: 16px !important;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.75) !important;
    color: #f5f5f5 !important;
    padding-bottom: 0 !important;
    overflow: hidden;
}
html[data-vspos-theme="dark"] .swal-title {
    color: #f5f5f5 !important;
    font-weight: 800 !important;
    font-size: 20px !important;
}
html[data-vspos-theme="dark"] .swal-text {
    color: #a3a3a3 !important;
}
html[data-vspos-theme="dark"] .swal-footer {
    background: #111111 !important;
    border-top: 1px solid #2a2a2a !important;
    border-radius: 0 0 16px 16px !important;
    margin-top: 16px !important;
    padding: 14px 16px !important;
    text-align: right !important;
}
html[data-vspos-theme="dark"] .swal-button {
    border-radius: 10px !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    padding: 10px 20px !important;
    box-shadow: none !important;
}
html[data-vspos-theme="dark"] .swal-button:focus {
    box-shadow: 0 0 0 2px rgba(245, 197, 24, 0.35) !important;
}

/* Cheque → change customer picker inside SweetAlert */
html[data-vspos-theme="dark"] .vspos-swal-customer .swal-modal {
    width: 460px !important;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-wrap {
    text-align: left;
    padding: 0 4px 4px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-help {
    color: #a3a3a3 !important;
    font-size: 13px;
    line-height: 1.45;
    margin: 0 0 12px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #d4d4d4;
    margin: 0 0 6px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-search {
    position: relative;
    margin-bottom: 8px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-search > i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #f5c518;
    font-size: 13px;
    pointer-events: none;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-search .form-control {
    height: 40px !important;
    padding-left: 36px !important;
    background: #111 !important;
    border: 1px solid #2a2a2a !important;
    border-radius: 8px !important;
    color: #f5f5f5 !important;
    box-shadow: none !important;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-search .form-control:focus {
    border-color: #f5c518 !important;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-results {
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    background: #111;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-empty {
    padding: 16px 12px;
    text-align: center;
    color: #737373;
    font-size: 12px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    border-bottom: 1px solid #1f1f1f;
    background: transparent;
    color: #f5f5f5;
    padding: 10px 12px;
    cursor: pointer;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-item:last-child {
    border-bottom: 0;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-item:hover,
html[data-vspos-theme="dark"] .vspos-swal-customer-item.is-selected {
    background: rgba(245, 197, 24, 0.12);
}
html[data-vspos-theme="dark"] .vspos-swal-customer-biz {
    display: block;
    font-size: 11px;
    color: #a3a3a3;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-name {
    display: block;
    font-weight: 700;
    font-size: 13px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-mobile {
    display: block;
    font-size: 11px;
    color: #a3a3a3;
    margin-top: 2px;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-picked {
    margin-top: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #86efac;
    font-size: 12px;
    font-weight: 600;
}
html[data-vspos-theme="dark"] .vspos-swal-customer-picked i {
    margin-right: 6px;
}
html[data-vspos-theme="dark"] .swal-button--cancel {
    background: #1a1a1a !important;
    color: #f5f5f5 !important;
    border: 1px solid #333333 !important;
}
html[data-vspos-theme="dark"] .swal-button--cancel:hover {
    background: #262626 !important;
    color: #ffffff !important;
    border-color: #444444 !important;
}
html[data-vspos-theme="dark"] .swal-button--confirm:not(.swal-button--cancel):not(.swal-button--danger),
html[data-vspos-theme="dark"] .swal-button:not(.swal-button--cancel):not(.swal-button--danger) {
    background: linear-gradient(135deg, #f5c518, #d4a012) !important;
    color: #111111 !important;
}
html[data-vspos-theme="dark"] .swal-button--confirm:not(.swal-button--cancel):not(.swal-button--danger):hover,
html[data-vspos-theme="dark"] .swal-button:not(.swal-button--cancel):not(.swal-button--danger):hover {
    filter: brightness(1.06);
}
html[data-vspos-theme="dark"] .swal-button--danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    color: #ffffff !important;
}
html[data-vspos-theme="dark"] .swal-button--danger:hover {
    filter: brightness(1.08);
}
/* Warning icon */
html[data-vspos-theme="dark"] .swal-icon--warning {
    border-color: #f5c518 !important;
}
html[data-vspos-theme="dark"] .swal-icon--warning__body,
html[data-vspos-theme="dark"] .swal-icon--warning__dot {
    background-color: #f5c518 !important;
}
/* Success icon */
html[data-vspos-theme="dark"] .swal-icon--success__ring {
    border-color: rgba(34, 197, 94, 0.3) !important;
}
html[data-vspos-theme="dark"] .swal-icon--success__line {
    background-color: #22c55e !important;
}
html[data-vspos-theme="dark"] .swal-icon--success {
    border-color: #22c55e !important;
}
/* Masks must match dark modal bg — white masks show as a slash on dark theme */
html[data-vspos-theme="dark"] .swal-icon--success:before,
html[data-vspos-theme="dark"] .swal-icon--success:after {
    background-color: #141414 !important;
    background: #141414 !important;
}
html[data-vspos-theme="dark"] .swal-icon--success__hide-corners {
    background-color: #141414 !important;
}
/* Error icon */
html[data-vspos-theme="dark"] .swal-icon--error {
    border-color: #ef4444 !important;
}
html[data-vspos-theme="dark"] .swal-icon--error__line {
    background-color: #ef4444 !important;
}
</style>

@if($pos_layout)
<style id="vspos-select2-dropdown-theme">
/* POS Select2 open panel — loaded last; hard-coded (no CSS vars) */
html[data-vspos-theme="dark"] .select2-dropdown,
html[data-vspos-theme="dark"] .select2-container--default .select2-dropdown,
html[data-vspos-theme="dark"] .vspos-select2-dropdown {
    background-color: #111111 !important;
    border: 1px solid #2a2a2a !important;
    color: #f5f5f5 !important;
    border-radius: 10px !important;
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.6) !important;
}
html[data-vspos-theme="dark"] .select2-container--default .select2-search--dropdown {
    background-color: #111111 !important;
    padding: 8px !important;
    border-bottom: 1px solid #2a2a2a !important;
}
html[data-vspos-theme="dark"] .select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #1a1a1a !important;
    border: 1px solid #333333 !important;
    color: #f5f5f5 !important;
    border-radius: 8px !important;
}
html[data-vspos-theme="dark"] .select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: #f5c518 !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.12) !important;
}
html[data-vspos-theme="dark"] .select2-results,
html[data-vspos-theme="dark"] .select2-results__options {
    background-color: #111111 !important;
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .select2-results__option {
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .select2-results__option.select2-results__message,
html[data-vspos-theme="dark"] .select2-results__message {
    color: #a3a3a3 !important;
}
html[data-vspos-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: rgba(245, 197, 24, 0.2) !important;
    color: #f5c518 !important;
}
html[data-vspos-theme="dark"] .select2-container--default .select2-results__option[aria-selected="true"] {
    background-color: rgba(245, 197, 24, 0.15) !important;
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .select2-dropdown .add_new_customer,
html[data-vspos-theme="dark"] .select2-dropdown .btn-link {
    color: #f5c518 !important;
}

/* POS datetime picker (bootstrap-datetimepicker) */
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget.dropdown-menu {
    background-color: #111111 !important;
    border: 1px solid #2a2a2a !important;
    color: #f5f5f5 !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.6) !important;
    padding: 8px !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget.dropdown-menu.bottom:after {
    border-bottom-color: #111111 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget.dropdown-menu.bottom:before {
    border-bottom-color: #2a2a2a !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget.dropdown-menu.top:after {
    border-top-color: #111111 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget.dropdown-menu.top:before {
    border-top-color: #2a2a2a !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table th,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .picker-switch td span {
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table thead tr:first-child th:hover {
    background-color: #1a1a1a !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table th.dow {
    color: #a3a3a3 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.day:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.hour:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.minute:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.second:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td span:hover {
    background-color: #222222 !important;
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.old,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.new,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td span.old {
    color: #525252 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.active,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.active:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td span.active {
    background-color: #f5c518 !important;
    color: #050505 !important;
    text-shadow: none !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.today:before {
    border-bottom-color: #f5c518 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.active.today:before {
    border-bottom-color: #050505 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.disabled,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table td.disabled:hover,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table th.disabled,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget table th.disabled:hover {
    color: #404040 !important;
    background: transparent !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget a[data-action],
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .timepicker-hour,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .timepicker-minute,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .timepicker-second {
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .glyphicon,
html[data-vspos-theme="dark"] .bootstrap-datetimepicker-widget .fa {
    color: #f5f5f5 !important;
}

/* POS jQuery UI datepicker (cheque dates, etc.) */
html[data-vspos-theme="dark"] #ui-datepicker-div,
html[data-vspos-theme="dark"] .ui-datepicker {
    background-color: #111111 !important;
    border: 1px solid #2a2a2a !important;
    color: #f5f5f5 !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.6) !important;
    padding: 8px !important;
}
html[data-vspos-theme="dark"] .ui-datepicker-header {
    background: #141414 !important;
    border: none !important;
    color: #f5f5f5 !important;
    border-radius: 8px !important;
}
html[data-vspos-theme="dark"] .ui-datepicker-title,
html[data-vspos-theme="dark"] .ui-datepicker th,
html[data-vspos-theme="dark"] .ui-datepicker td a,
html[data-vspos-theme="dark"] .ui-datepicker td span {
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .ui-datepicker th {
    color: #a3a3a3 !important;
}
html[data-vspos-theme="dark"] .ui-datepicker td a:hover,
html[data-vspos-theme="dark"] .ui-datepicker .ui-state-hover {
    background: #222222 !important;
    color: #f5f5f5 !important;
    border-color: #333333 !important;
}
html[data-vspos-theme="dark"] .ui-datepicker td .ui-state-active,
html[data-vspos-theme="dark"] .ui-datepicker td .ui-state-active:hover {
    background: #f5c518 !important;
    color: #050505 !important;
    border-color: #f5c518 !important;
}
html[data-vspos-theme="dark"] .ui-datepicker td .ui-state-highlight {
    background: rgba(245, 197, 24, 0.15) !important;
    color: #f5c518 !important;
    border-color: rgba(245, 197, 24, 0.3) !important;
}
html[data-vspos-theme="dark"] .ui-datepicker .ui-state-default {
    background: transparent !important;
    border-color: transparent !important;
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] .ui-datepicker .ui-datepicker-prev,
html[data-vspos-theme="dark"] .ui-datepicker .ui-datepicker-next {
    background: #1a1a1a !important;
    border: 1px solid #333333 !important;
    border-radius: 6px !important;
}
html[data-vspos-theme="dark"] .ui-datepicker .ui-datepicker-prev span,
html[data-vspos-theme="dark"] .ui-datepicker .ui-datepicker-next span {
    filter: invert(1) brightness(0.9);
}

/* Force dark on jQuery UI widget chrome (fixes white calendar over payment modal) */
html[data-vspos-theme="dark"] #ui-datepicker-div.ui-widget-content,
html[data-vspos-theme="dark"] #ui-datepicker-div .ui-widget-content,
html[data-vspos-theme="dark"] #ui-datepicker-div .ui-widget-header {
    background: #111111 !important;
    background-image: none !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;
}
html[data-vspos-theme="dark"] #ui-datepicker-div .ui-state-default {
    background: #1a1a1a !important;
    background-image: none !important;
    border-color: #2a2a2a !important;
    color: #f5f5f5 !important;
}
</style>
@endif

{{-- Print overrides MUST load last; match dark-theme specificity (body:not(.lockscreen)) --}}
<style id="vspos-print-overrides">
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    html,
    html.tw-bg-slate-950,
    html[data-vspos-theme="dark"],
    body,
    body.tw-bg-slate-950,
    body.tw-text-gray-100,
    body:not(.lockscreen),
    body:not(.lockscreen).tw-bg-slate-950,
    body:not(.lockscreen) .thetop,
    body:not(.lockscreen) #scrollable-container,
    body:not(.lockscreen) main,
    body:not(.lockscreen) .content-wrapper,
    body:not(.lockscreen) .right-side,
    body:not(.lockscreen) .wrapper,
    body:not(.lockscreen) .content,
    body:not(.lockscreen) section.content,
    body:not(.lockscreen) .content-header,
    body:not(.lockscreen) .container,
    body:not(.lockscreen) .container-fluid,
    body:not(.lockscreen) .row,
    body:not(.lockscreen) [class*="col-"],
    body:not(.lockscreen) .box,
    body:not(.lockscreen) .box-solid,
    body:not(.lockscreen) .box-primary,
    body:not(.lockscreen) .box-default,
    body:not(.lockscreen) .box-info,
    body:not(.lockscreen) .box-body,
    body:not(.lockscreen) .box-header,
    body:not(.lockscreen) .box-footer,
    body:not(.lockscreen) .panel,
    body:not(.lockscreen) .panel-default,
    body:not(.lockscreen) .well,
    body:not(.lockscreen) .table,
    body:not(.lockscreen) .table > thead > tr > th,
    body:not(.lockscreen) .table > tbody > tr > th,
    body:not(.lockscreen) .table > tbody > tr > td,
    body:not(.lockscreen) .table > tfoot > tr > th,
    body:not(.lockscreen) .table > tfoot > tr > td,
    body:not(.lockscreen) .bg-gray,
    body:not(.lockscreen) .callout,
    body:not(.lockscreen) .info-box,
    body:not(.lockscreen) .small-box,
    body:not(.lockscreen) .printable_report {
        background: #ffffff !important;
        background-color: #ffffff !important;
        background-image: none !important;
        color: #000000 !important;
        box-shadow: none !important;
        text-shadow: none !important;
        border-color: #cccccc !important;
    }

    body:not(.lockscreen) .box-title,
    body:not(.lockscreen) .content-header > h1,
    body:not(.lockscreen) .content-header > h1 small,
    body:not(.lockscreen) .box-body h1,
    body:not(.lockscreen) .box-body h2,
    body:not(.lockscreen) .box-body h3,
    body:not(.lockscreen) .box-body h4,
    body:not(.lockscreen) .box-body h5,
    body:not(.lockscreen) .box-body h6,
    body:not(.lockscreen) .box-body p,
    body:not(.lockscreen) .box-body label,
    body:not(.lockscreen) .tw-text-black,
    body:not(.lockscreen) .tw-text-gray-100,
    body:not(.lockscreen) .tw-text-white,
    body:not(.lockscreen) h1,
    body:not(.lockscreen) h2,
    body:not(.lockscreen) h3,
    body:not(.lockscreen) h4,
    body:not(.lockscreen) h5,
    body:not(.lockscreen) h6,
    body:not(.lockscreen) label,
    body:not(.lockscreen) p,
    body:not(.lockscreen) span,
    body:not(.lockscreen) td,
    body:not(.lockscreen) th,
    body:not(.lockscreen) a {
        color: #000000 !important;
    }

    body:not(.lockscreen) .bg-gray,
    body:not(.lockscreen) tfoot .bg-gray,
    body:not(.lockscreen) .table .bg-gray {
        background: #e5e5e5 !important;
        background-color: #e5e5e5 !important;
        color: #000000 !important;
    }

    body:not(.lockscreen) .side-bar,
    body:not(.lockscreen) .thetop > aside,
    body:not(.lockscreen) .main-header,
    body:not(.lockscreen) .main-sidebar,
    body:not(.lockscreen) .navbar,
    body:not(.lockscreen) .overlay,
    body:not(.lockscreen) .no-print,
    body:not(.lockscreen) .vspos-ai-widget,
    body:not(.lockscreen) .ai-chat-widget {
        display: none !important;
        visibility: hidden !important;
    }

    body:not(.lockscreen) #scrollable-container {
        overflow: visible !important;
        height: auto !important;
        position: static !important;
    }
}
</style>

</html>
