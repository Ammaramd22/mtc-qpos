@extends('layouts.install', ['no_header' => 1])
@section('title', 'Welcome')

@section('content')
<div class="install-card">
    <div class="install-header">
        <img src="{{ asset('img/vsoft_logo.png') }}" alt="QPOS" onerror="this.style.display='none'">
        <div>
            <h1>QPOS Installation</h1>
            <p>Welcome to the setup wizard</p>
        </div>
    </div>

    <div class="install-body-inner">
        <div class="install-nav">
            <div class="install-nav-item active">Welcome</div>
            <div class="install-nav-item">Configuration</div>
            <div class="install-nav-item">Complete</div>
        </div>

        <div style="text-align:center; padding: 20px 0;">
            <div class="install-welcome-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h3 style="font-size:20px; font-weight:600; color:#111827; margin-bottom:8px;">Get Started with QPOS</h3>
            <p style="color:#6b7280; font-size:14px; margin-bottom:24px; max-width:460px; margin-left:auto; margin-right:auto;">
                Follow the simple steps to install your Point of Sale system. Before you begin, please make sure you have the following ready.
            </p>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:24px;">
            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px;">
                <div style="color:#00935F; font-size:20px; margin-bottom:8px;"><i class="fas fa-database"></i></div>
                <div style="font-weight:600; font-size:13px; color:#111827; margin-bottom:4px;">Database Details</div>
                <div style="font-size:12px; color:#6b7280;">Host, Port, Name, Username &amp; Password</div>
            </div>
            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px;">
                <div style="color:#00935F; font-size:20px; margin-bottom:8px;"><i class="fas fa-server"></i></div>
                <div style="font-weight:600; font-size:13px; color:#111827; margin-bottom:4px;">Server Requirements</div>
                <div style="font-size:12px; color:#6b7280;">PHP 8.1+, MySQL, required extensions</div>
            </div>
            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px;">
                <div style="color:#00935F; font-size:20px; margin-bottom:8px;"><i class="fas fa-envelope"></i></div>
                <div style="font-weight:600; font-size:13px; color:#111827; margin-bottom:4px;">Mail Configuration</div>
                <div style="font-size:12px; color:#6b7280;">SMTP details (optional but recommended)</div>
            </div>
            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px;">
                <div style="color:#00935F; font-size:20px; margin-bottom:8px;"><i class="fas fa-puzzle-piece"></i></div>
                <div style="font-weight:600; font-size:13px; color:#111827; margin-bottom:4px;">Business Modules</div>
                <div style="font-size:12px; color:#6b7280;">Nutmeg, Mobile shop, Restaurant &amp; more</div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end;">
            <a href="{{ route('install.checkServer') }}" class="install-btn" style="text-decoration:none;">
                Let's Get Started <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
            </a>
        </div>
    </div>

    <div class="install-footer">
        QPOS &mdash; Smart Point of Sale System
    </div>
</div>
@endsection
