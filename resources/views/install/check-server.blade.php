@extends('layouts.install', ['no_header' => 1])
@section('title', 'Server Requirements')

@section('content')
<div class="install-card">
    <div class="install-header">
        <img src="{{ asset('img/vsoft_logo.png') }}" alt="QPOS" onerror="this.style.display='none'">
        <div>
            <h1>QPOS Installation</h1>
            <p>Checking server requirements</p>
        </div>
    </div>

    <div class="install-body-inner">
        <div class="install-nav">
            <div class="install-nav-item completed">Welcome</div>
            <div class="install-nav-item active">Server Check</div>
            <div class="install-nav-item">Configuration</div>
            <div class="install-nav-item">Complete</div>
        </div>

        <div style="margin-bottom:16px;">
            <div style="font-weight:600; font-size:14px; color:#111827; margin-bottom:12px;">PHP Extensions</div>
            <div class="install-check-item {{ $output['php'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>PHP >= 8.1 (Current: {{ $output['php_version'] }})</span>
                <i class="fas {{ $output['php'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['openssl'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>OpenSSL Extension</span>
                <i class="fas {{ $output['openssl'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['pdo'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>PDO Extension</span>
                <i class="fas {{ $output['pdo'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['mbstring'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>Mbstring Extension</span>
                <i class="fas {{ $output['mbstring'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['tokenizer'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>Tokenizer Extension</span>
                <i class="fas {{ $output['tokenizer'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['xml'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>XML Extension</span>
                <i class="fas {{ $output['xml'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['curl'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>cURL Extension</span>
                <i class="fas {{ $output['curl'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['zip'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>ZIP Extension</span>
                <i class="fas {{ $output['zip'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['gd'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>GD Extension</span>
                <i class="fas {{ $output['gd'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
        </div>

        <div style="margin-bottom:24px;">
            <div style="font-weight:600; font-size:14px; color:#111827; margin-bottom:12px;">Directory Permissions</div>
            <div class="install-check-item {{ $output['storage_writable'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>{{ storage_path() }} is writable</span>
                <i class="fas {{ $output['storage_writable'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
            <div class="install-check-item {{ $output['cache_writable'] ? 'install-check-pass' : 'install-check-fail' }}">
                <span>{{ base_path('bootstrap/cache') }} is writable</span>
                <i class="fas {{ $output['cache_writable'] ? 'fa-check-circle' : 'fa-times-circle' }} install-check-icon"></i>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px solid #e5e7eb;">
            <a href="{{ route('install.index') }}" class="install-btn install-btn-secondary" style="text-decoration:none;">
                <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Back
            </a>
            @if($output['next'])
                <a href="{{ route('install.details') }}" class="install-btn" style="text-decoration:none;">
                    Continue <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
                </a>
            @else
                <button class="install-btn" disabled style="opacity:0.5; cursor:not-allowed;">
                    Please fix errors above <i class="fas fa-exclamation-circle" style="margin-left:6px;"></i>
                </button>
            @endif
        </div>
    </div>

    <div class="install-footer">
        QPOS &mdash; Smart Point of Sale System
    </div>
</div>
@endsection