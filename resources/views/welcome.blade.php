@extends('layouts.auth2')
@section('title', config('app.name', 'QPOS'))

{{-- Keep welcome in sync with login (demos + branding). Prefer /login. --}}
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
    }
@endphp

@section('content')
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
@endsection

@section('javascript')
    <script type="text/javascript">
        window.VSPOS_LOGIN_DEMO_PASSWORD = @json($show_saas_demos || $show_local_demos ? '123456' : null);
        window.VSPOS_LOGIN_BRAND = @json($login_brand);
    </script>
    <script src="{{ asset('js/auth-login.js?v=' . ($asset_v ?? config('constants.asset_version', 1))) }}"></script>
@endsection
