@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')

@php
    $login_username = old('username');
    $login_password = null;
    $demo_shops = $demo_shops ?? [];
    $demos_enabled = (bool) config('app.show_demo_shops');
    $show_local_demos = $demos_enabled && count($demo_shops) > 0;
    $show_saas_demos = $demos_enabled && config('app.env') == 'demo';
    $demo_types = [];
    $login_brand = $login_brand ?? [
        'app_name' => config('app.name', 'QPOS'),
        'theme' => 'qpos',
        'accent' => '#f5c518',
        'shop_name' => null,
        'shop_tagline' => null,
        'background_url' => null,
    ];

    if ($show_saas_demos) {
        $login_username = 'admin';
        $login_password = '123456';
        $demo_types = [
            'all_in_one' => 'admin',
            'super_market' => 'admin',
            'pharmacy' => 'admin-pharmacy',
            'electronics' => 'admin-electronics',
            'services' => 'admin-services',
            'restaurant' => 'admin-restaurant',
            'superadmin' => 'superadmin',
            'woocommerce' => 'woocommerce_user',
            'essentials' => 'admin-essentials',
            'manufacturing' => 'manufacturer-demo',
        ];
        if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
            $login_username = $demo_types[$_GET['demo_type']];
        }
    }
@endphp

@section('content')
    {{-- Mobile only: demos above login card (desktop uses left brand panel) --}}
    <div class="vspos-demo-mobile vspos-demo-above-form">
        @include('auth.partials.demo_shops', [
            'demo_shops' => $demo_shops,
            'demo_types' => $demo_types,
            'panel_class' => 'vspos-demo-panel-mobile',
        ])
    </div>

    <div class="vspos-login-page">
        @include('auth.partials.login_card', [
            'login_username' => $login_username,
            'login_password' => $login_password,
            'login_brand' => $login_brand,
        ])
    </div>
@stop

@section('javascript')
    <script type="text/javascript">
        window.VSPOS_LOGIN_DEMO_PASSWORD = @json($show_saas_demos || $show_local_demos ? '123456' : null);
        window.VSPOS_LOGIN_BRAND = @json($login_brand);
    </script>
    <script src="{{ asset('js/auth-login.js?v=' . ($asset_v ?? config('constants.asset_version', 1))) }}"></script>
@endsection
