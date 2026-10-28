<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name', 'POS') }}</title>
    @include('layouts.partials.css')
    @include('layouts.partials.extracss_auth')
    <link href="{{ asset('css/auth-theme.css?v=' . ($asset_v ?? config('constants.asset_version', 1))) }}" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    @if(config('constants.enable_recaptcha'))
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
    @endif
</head>

<body class="auth-body @if(request()->routeIs('login') || request()->is('login')) auth-login-page @endif"
    @if(!empty($login_brand))
        data-auth-theme="{{ $login_brand['theme'] ?? 'qpos' }}"
        style="--vspos-auth-shop-accent: {{ $login_brand['accent'] ?? '#f5c518' }};"
    @endif
>
    @inject('request', 'Illuminate\Http\Request')
    @if (session('status'))
        <input type="hidden" id="status_span"
            data-status="{{ (int) data_get(session('status'), 'success', 0) }}"
            data-msg="{{ data_get(session('status'), 'msg', '') }}">
    @endif

    @php
        $qposLogo = file_exists(public_path('img/qpos-icon.png'))
            ? asset('img/qpos-icon.png')
            : (file_exists(public_path('uploads/logo.png')) ? asset('uploads/logo.png') : null);
        $loginBrand = $login_brand ?? [
            'app_name' => config('app.name', 'QPOS'),
            'logo' => $qposLogo,
            'shop_name' => null,
            'shop_tagline' => null,
            'theme' => 'qpos',
            'accent' => '#f5c518',
            'background_url' => null,
        ];
        if (empty($loginBrand['logo'])) {
            $loginBrand['logo'] = $qposLogo;
        }
        if (!array_key_exists('background_url', $loginBrand)) {
            $loginBrand['background_url'] = \App\Utils\SoftwareOwnerUtil::loginBackgroundUrl();
        }
    @endphp

    <div class="auth-layout" id="auth-layout" data-theme="{{ $loginBrand['theme'] ?? 'qpos' }}">

        {{-- ── LEFT BRAND PANEL ── --}}
        <div class="auth-brand-panel @if(!empty($loginBrand['background_url'])) has-custom-bg @endif"
            @if(!empty($loginBrand['background_url']))
                style="--auth-login-bg-image: url('{{ $loginBrand['background_url'] }}');"
            @endif>
            <div class="auth-brand-inner">
                {{-- QPOS logo mark --}}
                <div class="auth-logo-mark">
                    @if(!empty($loginBrand['logo']))
                        <img src="{{ $loginBrand['logo'] }}" alt="QPOS" class="auth-qpos-logo">
                    @else
                        <span class="auth-qpos-fallback">Q</span>
                    @endif
                    <div class="auth-logo-text">
                        <span class="auth-logo-name">QPOS</span>
                        <span class="auth-logo-sub" id="auth-brand-shop-label">
                            @if(!empty($loginBrand['shop_name']))
                                {{ $loginBrand['shop_name'] }}
                            @else
                                Smart Point of Sale
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Headline --}}
                <div class="auth-brand-headline">
                    <h1 id="auth-brand-headline">
                        @if(($loginBrand['theme'] ?? '') === 'nutmeg')
                            Nutmeg trading,<br><span>made simple.</span>
                        @else
                            Run your business<br><span>smarter &amp; faster.</span>
                        @endif
                    </h1>
                    <p id="auth-brand-copy">
                        @if(!empty($loginBrand['shop_tagline']))
                            {{ $loginBrand['shop_name'] ?? 'Your shop' }} — {{ $loginBrand['shop_tagline'] }}. Powered by QPOS.
                        @else
                            The all-in-one POS &amp; inventory management platform built for modern businesses.
                        @endif
                    </p>
                </div>

                {{-- Demo shops on login / welcome; feature pills on other auth pages --}}
                @if (request()->routeIs('login') || request()->is('login') || request()->is('/'))
                    <div class="auth-brand-extra">
                        @include('auth.partials.demo_shops', [
                            'demo_shops' => $demo_shops ?? [],
                            'demo_types' => $demo_types ?? [],
                            'panel_class' => 'vspos-demo-panel-brand',
                            'active_shop' => $loginBrand['shop_key'] ?? null,
                        ])
                    </div>
                @else
                    <div class="auth-feature-list">
                        <div class="auth-feature"><span class="auth-feature-icon">📦</span> Inventory & stock alerts</div>
                        <div class="auth-feature"><span class="auth-feature-icon">💳</span> Sales, purchases & payments</div>
                        <div class="auth-feature"><span class="auth-feature-icon">📊</span> Real-time reports & analytics</div>
                        <div class="auth-feature"><span class="auth-feature-icon">🏪</span> Multi-location support</div>
                    </div>
                @endif

                {{-- Decorative circles --}}
                <div class="auth-deco-circle auth-deco-1"></div>
                <div class="auth-deco-circle auth-deco-2"></div>
            </div>
        </div>

        {{-- ── RIGHT FORM PANEL ── --}}
        <div class="auth-form-panel">
            {{-- Top nav --}}
            <div class="auth-form-topnav">
                <div class="auth-form-topnav-brand">
                    @if(!empty($loginBrand['logo']))
                        <img src="{{ $loginBrand['logo'] }}" alt="QPOS" class="auth-topnav-logo">
                    @endif
                    <span>QPOS</span>
                </div>
                <div class="auth-form-topnav-links">
                    @if(config('constants.SHOW_REPAIR_STATUS_LOGIN_SCREEN') && Route::has('repair-status'))
                        <a href="{{ action([\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index']) }}" class="auth-topnav-link">Repair Status</a>
                    @endif
                    @if(Route::has('member_scanner'))
                        <a href="{{ action([\Modules\Gym\Http\Controllers\MemberController::class, 'member_scanner']) }}" class="auth-topnav-link">@lang('gym::lang.gym_member_profile')</a>
                    @endif
                    @if ($request->segment(1) != 'login')
                        <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'login']) }}@if(!empty(request()->lang)){{'?lang='.request()->lang}}@endif" class="auth-topnav-link">@lang('business.sign_in')</a>
                    @endif
                    @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
                        @if (config('constants.allow_registration'))
                            <a href="{{ route('business.getRegister') }}@if(!empty(request()->lang)){{'?lang='.request()->lang}}@endif" class="auth-topnav-btn">@lang('business.register')</a>
                            @if (Route::has('pricing') && config('app.env') != 'demo' && $request->segment(1) != 'pricing')
                                <a href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}" class="auth-topnav-link">@lang('superadmin::lang.pricing')</a>
                            @endif
                        @endif
                    @endif
                    @include('layouts.partials.language_btn')
                </div>
            </div>

            {{-- Content --}}
            <div class="auth-form-content">
                @yield('content')
            </div>
        </div>

    </div>

    @include('layouts.partials.javascripts')
    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>
    @yield('javascript')

    <script>
        $(document).ready(function() { $('.select2_register').select2(); });
    </script>
</body>
</html>
