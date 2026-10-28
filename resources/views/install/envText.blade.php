@extends('layouts.install', ['no_header' => 1])
@section('title', 'Manual Configuration')

@section('content')
<div class="install-card">
    <div class="install-header">
        <img src="{{ asset('img/vsoft_logo.png') }}" alt="QPOS">
        <h1>QPOS Installation</h1>
        <p>One more step needed</p>
    </div>

    <div class="install-body-inner">
        <div class="install-nav">
            <div class="install-nav-item completed">Welcome</div>
            <div class="install-nav-item completed">Configuration</div>
            <div class="install-nav-item active">Complete</div>
        </div>

        @if(!empty($autoError))
            <div class="install-alert install-alert-danger">
                <b>Could not finish automatically:</b> {{ $autoError }}
            </div>
        @endif

        @if(session('error'))
            <div class="install-alert install-alert-danger">
                {{ session('error') }}
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

        <div id="manual_step">
            <div style="background:#fefce8; border:1px solid #fde047; border-radius:8px; padding:16px; margin-bottom:20px;">
                <div style="font-weight:600; font-size:13px; color:#854d0e; margin-bottom:6px;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i> Manual step required</div>
                <p style="font-size:13px; color:#a16207; margin:0;">
                    The installer was unable to write the <code>.env</code> file automatically. Please create a file named <strong>.env</strong> at <code>{{ $envPath }}</code> with read &amp; write permissions, paste the content below, then click Install.
                </p>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:500; font-size:13px; color:#374151; margin-bottom:6px; display:block;">Environment File Content</label>
                <textarea id="env_textarea" readonly style="width:100%; height:320px; font-family: monospace; font-size:12px; padding:12px; border:1px solid #d1d5db; border-radius:8px; background:#f9fafb; resize:vertical;">{{ $envContent }}</textarea>
            </div>

            <button type="button" onclick="copyEnvContent()" class="install-btn install-btn-secondary" style="margin-bottom:16px;">
                <i class="fas fa-copy" style="margin-right:6px;"></i> Copy to Clipboard
            </button>
        </div>

        <form method="post" action="{{ route('install.installAlternate') }}" id="env_details_form" style="margin-top:8px;">
            {{ csrf_field() }}
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" id="install_button" class="install-btn">
                    <i class="fas fa-download" style="margin-right:6px;"></i> Install Now
                </button>
            </div>
        </form>

        <div id="install_msg" style="display:none; text-align:center; margin-top:16px; padding:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; color:#1e40af; font-size:13px; font-weight:500;">
            <i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Installation in progress, please do not refresh or close this window.
        </div>
    </div>

    <div class="install-footer">
        QPOS &mdash; Smart Point of Sale System
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
    function copyEnvContent() {
        var textarea = document.getElementById('env_textarea');
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        document.execCommand('copy');
        alert('Environment content copied to clipboard!');
    }

    $(document).ready(function(){
        $('form#env_details_form').submit(function(){
            $('button#install_button').attr('disabled', true).html('<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Installing...');
            $('#manual_step').hide();
            $('#install_msg').show();
        });
    });
</script>
@endsection