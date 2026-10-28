@extends('layouts.install', ['no_header' => 1])
@section('title', 'Configuration')

@section('content')
<div class="install-card">
    <div class="install-header">
        <img src="{{ asset('img/vsoft_logo.png') }}" alt="QPOS" onerror="this.style.display='none'">
        <div>
            <h1>QPOS Installation</h1>
            <p>Database, modules &amp; mail — full screen setup</p>
        </div>
    </div>

    <div class="install-body-inner">
        <div class="install-nav">
            <div class="install-nav-item completed">Welcome</div>
            <div class="install-nav-item active">Configuration</div>
            <div class="install-nav-item">Complete</div>
        </div>

        @if(session('error'))
            <div class="install-alert install-alert-danger">
                {!! session('error') !!}
            </div>
        @endif

        @if ($errors->any())
            <div class="install-alert install-alert-danger">
                <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                </ul>
            </div>
        @endif

        <form class="install-form" id="details_form" method="post" action="{{ route('install.postDetails') }}">
            {{ csrf_field() }}

            <div class="install-section-title"><i class="fas fa-info-circle" style="margin-right:6px;"></i> Application Details</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label for="app_name">Application Name *</label>
                    <input type="text" class="form-control" name="APP_NAME" id="app_name" placeholder="My Store POS" value="{{ old('APP_NAME', 'QPOS') }}" required>
                </div>
                <div>
                    <label for="app_title">Application Title</label>
                    <input type="text" name="APP_TITLE" class="form-control" id="app_title" placeholder="Optional subtitle" value="{{ old('APP_TITLE') }}">
                </div>
            </div>

            @if($activation_key)
            <div style="margin-top:16px;">
                <label for="activation_licence_code">Activation Licence Code *</label>
                <input type="password" name="MAC_LICENCE_CODE" required class="form-control" id="activation_licence_code">
            </div>
            @endif

            <div class="install-section-title"><i class="fas fa-database" style="margin-right:6px;"></i> Database Details</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                <div>
                    <label for="db_host">Database Host *</label>
                    <input type="text" class="form-control" id="db_host" name="DB_HOST" required placeholder="localhost" value="{{ old('DB_HOST', 'localhost') }}">
                </div>
                <div>
                    <label for="db_port">Database Port *</label>
                    <input type="text" class="form-control" id="db_port" name="DB_PORT" required value="{{ old('DB_PORT', '3306') }}">
                </div>
                <div>
                    <label for="db_database">Database Name *</label>
                    <input type="text" class="form-control" id="db_database" name="DB_DATABASE" required value="{{ old('DB_DATABASE') }}">
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
                <div>
                    <label for="db_username">Database Username *</label>
                    <input type="text" class="form-control" id="db_username" name="DB_USERNAME" required value="{{ old('DB_USERNAME') }}">
                </div>
                <div>
                    <label for="db_password">Database Password</label>
                    <input type="password" class="form-control" id="db_password" name="DB_PASSWORD" value="{{ old('DB_PASSWORD') }}" placeholder="Leave blank if none" autocomplete="new-password">
                    <small style="color:#6b7280; font-size:12px;">Optional — leave empty if MySQL user has no password</small>
                </div>
            </div>

            <div class="install-section-title"><i class="fas fa-store" style="margin-right:6px;"></i> Business type / modules</div>
            <p style="font-size:13px; color:#6b7280; margin:-4px 0 14px 0;">
                Choose which specialty modules to enable. General retail is always included.
            </p>
            <div class="install-module-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:8px;">
                @foreach($business_packs as $pack_id => $pack)
                    @php
                        $always = !empty($pack['always']);
                        $oldPacks = old('business_packs', $selected_packs);
                        $checked = $always || in_array($pack_id, (array) $oldPacks, true);
                    @endphp
                    <label class="install-module-pack" style="display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid {{ $checked ? '#86efac' : '#e5e7eb' }}; border-radius:10px; background:{{ $always ? '#f0fdf4' : '#fff' }}; cursor:{{ $always ? 'default' : 'pointer' }};">
                        <input type="checkbox"
                               name="business_packs[]"
                               value="{{ $pack_id }}"
                               style="margin-top:3px;"
                               {{ $checked ? 'checked' : '' }}
                               {{ $always ? 'disabled' : '' }}>
                        @if($always)
                            <input type="hidden" name="business_packs[]" value="{{ $pack_id }}">
                        @endif
                        <span>
                            <span style="display:block; font-weight:600; font-size:13px; color:#111827;">{{ $pack['label'] }}</span>
                            <span style="display:block; font-size:12px; color:#6b7280; margin-top:2px; line-height:1.4;">{{ $pack['help'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <style>
                @media (max-width: 640px) {
                    .install-module-grid { grid-template-columns: 1fr !important; }
                }
            </style>

            <div class="install-section-title"><i class="fas fa-envelope" style="margin-right:6px;"></i> Email Configuration</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label for="MAIL_MAILER">Send mails using</label>
                    <select class="form-control" name="MAIL_MAILER" id="MAIL_MAILER">
                        <option value="sendmail" @selected(old('MAIL_MAILER', 'sendmail') === 'sendmail')>PHP Mail</option>
                        <option value="smtp" @selected(old('MAIL_MAILER') === 'smtp')>SMTP</option>
                    </select>
                </div>
                <div>
                    <label for="MAIL_FROM_ADDRESS">Default from address</label>
                    <input type="email" class="form-control" id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" placeholder="hello@yourstore.com" value="{{ old('MAIL_FROM_ADDRESS') }}">
                </div>
            </div>
            <div style="margin-top:16px;">
                <label for="MAIL_FROM_NAME">Default from name</label>
                <input type="text" class="form-control" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" placeholder="My Store POS (Optional)" value="{{ old('MAIL_FROM_NAME') }}">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-top:16px;" class="smtp hide">
                <div>
                    <label for="MAIL_HOST">SMTP Host</label>
                    <input type="text" class="form-control smtp_input" id="MAIL_HOST" name="MAIL_HOST" disabled value="{{ old('MAIL_HOST') }}">
                </div>
                <div>
                    <label for="MAIL_PORT">SMTP Port</label>
                    <input type="text" class="form-control smtp_input" id="MAIL_PORT" name="MAIL_PORT" disabled placeholder="587" value="{{ old('MAIL_PORT') }}">
                </div>
                <div>
                    <label for="MAIL_ENCRYPTION">Encryption</label>
                    <input type="text" class="form-control smtp_input" id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION" disabled placeholder="tls or ssl" value="{{ old('MAIL_ENCRYPTION') }}">
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;" class="smtp hide">
                <div>
                    <label for="MAIL_USERNAME">SMTP Username</label>
                    <input type="text" class="form-control smtp_input" id="MAIL_USERNAME" name="MAIL_USERNAME" disabled value="{{ old('MAIL_USERNAME') }}">
                </div>
                <div>
                    <label for="MAIL_PASSWORD">SMTP Password</label>
                    <input type="password" class="form-control smtp_input" id="MAIL_PASSWORD" name="MAIL_PASSWORD" disabled>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:32px; padding-top:16px; border-top:1px solid #e5e7eb;">
                <a href="{{ route('install.checkServer') }}" class="install-btn install-btn-secondary" style="text-decoration:none;">
                    <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Back
                </a>
                <button type="submit" id="install_button" class="install-btn">
                    <i class="fas fa-download" style="margin-right:6px;"></i> Install Now
                </button>
            </div>

            <div id="install_msg" style="display:none; text-align:center; margin-top:16px; padding:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; color:#1e40af; font-size:13px; font-weight:500;">
                <i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Installation in progress — writing .env and creating tables. Do not close this window.
            </div>
        </form>
    </div>

    <div class="install-footer">
        QPOS &mdash; Smart Point of Sale System
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('select#MAIL_MAILER').change(function(){
            var driver = $(this).val();
            if(driver == 'smtp'){
                $('div.smtp').removeClass('hide');
                $('input.smtp_input').attr('disabled', false);
            } else {
                $('div.smtp').addClass('hide');
                $('input.smtp_input').attr('disabled', true);
            }
        }).trigger('change');

        $('form#details_form').on('submit', function(){
            $('button#install_button').attr('disabled', true).html('<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Installing...');
            $('#install_msg').show();
        });
    });
</script>
@endsection
