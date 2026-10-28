@extends('errors.layout')
@section('title', '419 Page Expired')

@section('content')
    <span class="error-icon">⏱️</span>
    <div class="error-code">419</div>
    <h1 class="error-title">Page Expired</h1>
    <p class="error-desc">Your session has expired due to inactivity or a security token mismatch. This usually happens when you stay on a form too long or your browser cookies were cleared. Please refresh the page and try again.</p>
    <div class="error-actions" style="flex-direction:column;gap:12px;">
        <a href="{{ route('login') }}" class="btn-primary">Go to Login</a>
        <button onclick="clearCacheAndReload()" class="btn-secondary" style="cursor:pointer;">🧹 Clear Cache & Hard Reload</button>
    </div>
    <div style="margin-top:20px;padding:14px 18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;text-align:left;">
        <p style="font-size:0.8rem;color:#64748b;margin:0 0 8px;font-weight:600;">If the button doesn't work, try manually:</p>
        <ol style="font-size:0.8rem;color:#64748b;margin:0;padding-left:18px;line-height:1.8;">
            <li>Press <b>Ctrl+Shift+Delete</b> (or <b>Cmd+Shift+Delete</b> on Mac)</li>
            <li>Select <b>"Cached images and files"</b> and <b>"Cookies"</b></li>
            <li>Choose <b>"All time"</b> → Click <b>Clear data</b></li>
            <li>Close browser completely and reopen</li>
            <li>Visit <b>{{ url('/login') }}</b> again</li>
        </ol>
    </div>

    <script>
        function clearCacheAndReload() {
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) caches.delete(name);
                });
            }
            location.reload(true);
        }
    </script>
@endsection
