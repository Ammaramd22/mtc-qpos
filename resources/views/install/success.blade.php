@extends('layouts.install', ['no_header' => 1])
@section('title', 'Installation Complete')

@section('content')
<div class="install-card">
    <div class="install-header">
        <img src="{{ asset('img/vsoft_logo.png') }}" alt="QPOS" onerror="this.style.display='none'">
        <div>
            <h1>QPOS Installation</h1>
            <p>All set and ready to go</p>
        </div>
    </div>

    <div class="install-body-inner" style="text-align:center; padding:48px 32px;">
        <div class="install-nav">
            <div class="install-nav-item completed">Welcome</div>
            <div class="install-nav-item completed">Configuration</div>
            <div class="install-nav-item completed">Complete</div>
        </div>

        <div style="margin-bottom:24px;">
            <div style="width:80px; height:80px; background:#f0fdf4; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px auto;">
                <i class="fas fa-check" style="font-size:36px; color:#22c55e;"></i>
            </div>
            <h2 style="font-size:22px; font-weight:700; color:#111827; margin-bottom:8px;">Installation Successful!</h2>
            <p style="color:#6b7280; font-size:14px; max-width:420px; margin:0 auto 24px auto;">
                QPOS has been installed successfully. Your configuration is saved in the <strong>.env</strong> file and can be updated anytime.
            </p>
        </div>

        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:20px; margin-bottom:24px; text-align:left; max-width:460px; margin-left:auto; margin-right:auto;">
            <div style="font-weight:600; font-size:13px; color:#111827; margin-bottom:12px;">What's next?</div>
            <ul style="font-size:13px; color:#4b5563; margin:0; padding-left:18px; line-height:2;">
                <li>Register your business and admin account</li>
                <li>Set up your store locations and products</li>
                <li>Configure tax rates and payment methods</li>
                <li>Start billing with your new POS</li>
            </ul>
            @if(!empty($selected_pack_labels))
                <div style="font-weight:600; font-size:13px; color:#111827; margin:16px 0 8px 0;">Modules selected at install</div>
                <ul style="font-size:13px; color:#4b5563; margin:0; padding-left:18px; line-height:1.8;">
                    @foreach($selected_pack_labels as $label)
                        <li>{{ $label }}</li>
                    @endforeach
                </ul>
                <p style="font-size:12px; color:#6b7280; margin:10px 0 0 0;">These will be enabled automatically when you register your first business.</p>
            @endif
        </div>

        <a href="{{ route('business.getRegister') }}" class="install-btn" style="text-decoration:none;">
            Register Your Business <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
        </a>
    </div>

    <div class="install-footer">
        QPOS &mdash; Smart Point of Sale System
    </div>
</div>
@endsection
