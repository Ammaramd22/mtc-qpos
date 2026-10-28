@php
    $reload = ! empty($common_settings['reload']) && is_array($common_settings['reload'])
        ? $common_settings['reload']
        : [];
    // Migrate older mobile_shop MyReload keys if reload settings are empty.
    $legacy = ! empty($common_settings['mobile_shop']) && is_array($common_settings['mobile_shop'])
        ? $common_settings['mobile_shop']
        : [];

    // Per-business only — never auto-fill another customer's / shared demo key.
    $hasBusinessCreds = ! empty($reload['myreload_api_username'])
        || ! empty($reload['myreload_api_key'])
        || ! empty($reload['myreload_merchant_id'])
        || ! empty($legacy['myreload_merchant_id'])
        || ! empty($legacy['myreload_api_key']);

    $apiUsername = $reload['myreload_api_username']
        ?? $reload['myreload_merchant_id']
        ?? $legacy['myreload_merchant_id']
        ?? '';
    $apiKey = $reload['myreload_api_key']
        ?? $legacy['myreload_api_key']
        ?? '';
    $apiUrl = $reload['myreload_api_url']
        ?? $legacy['myreload_api_url']
        ?? config('myreload.api_url');
    $callbackUrl = $reload['myreload_callback_url'] ?? '';
    $enabled = array_key_exists('myreload_enabled', $reload)
        ? ! empty($reload['myreload_enabled'])
        : ! empty($legacy['myreload_enabled']);

    $demoUsername = config('myreload.demo_api_username', 'Demo');
    $demoApiKey = config('myreload.demo_api_key', '316bacdc0b01ab3eed57620914ab0eb7');
    $businessName = session('business.name') ?? ($business->name ?? '');
@endphp

<div class="pos-tab-content" id="settings-reload" data-settings-tab="reload">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-bolt"></i> @lang('lang_v1.reload_module') — MyReload API</h4>
            <p class="help-block">@lang('lang_v1.reload_module_settings_help')</p>
            <div class="alert alert-warning" style="border-left:4px solid #f59e0b;">
                <strong>@lang('lang_v1.myreload_per_business_title')</strong>
                @lang('lang_v1.myreload_per_business_help', ['business' => $businessName])
            </div>
        </div>
    </div>

    <div class="box box-solid" style="border:1px solid #e5e7eb;border-radius:10px;padding:18px 16px;background:#fff;">
        <h5 style="margin-top:0;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#111827;">
            @lang('lang_v1.myreload_api_credentials')
            @if($businessName !== '')
                <small style="text-transform:none;font-weight:600;color:#64748b;letter-spacing:0;">
                    — {{ $businessName }}
                </small>
            @endif
        </h5>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('common_settings[reload][myreload_enabled]', __('lang_v1.enable_myreload') . ':') !!}
                    {!! Form::select('common_settings[reload][myreload_enabled]', ['0' => __('messages.no'), '1' => __('messages.yes')], $enabled ? '1' : '0', ['class' => 'form-control select2', 'id' => 'myreload_enabled']) !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('common_settings[reload][myreload_api_username]', __('lang_v1.myreload_api_username') . ':') !!}
                    {!! Form::text('common_settings[reload][myreload_api_username]', $apiUsername, [
                        'class' => 'form-control',
                        'id' => 'myreload_api_username',
                        'placeholder' => __('lang_v1.myreload_api_username_placeholder'),
                        'autocomplete' => 'off',
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.myreload_api_username_help')</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('common_settings[reload][myreload_api_key]', __('lang_v1.myreload_api_key') . ':') !!}
                    {!! Form::text('common_settings[reload][myreload_api_key]', $apiKey, [
                        'class' => 'form-control',
                        'id' => 'myreload_api_key',
                        'placeholder' => __('lang_v1.myreload_api_key_placeholder'),
                        'autocomplete' => 'off',
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.myreload_api_key_help')</p>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    {!! Form::label('common_settings[reload][myreload_callback_url]', __('lang_v1.myreload_callback_url') . ':') !!}
                    {!! Form::text('common_settings[reload][myreload_callback_url]', $callbackUrl, ['class' => 'form-control', 'placeholder' => __('lang_v1.myreload_callback_url_placeholder')]) !!}
                    <p class="help-block">@lang('lang_v1.myreload_callback_url_help')</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('common_settings[reload][myreload_api_url]', __('lang_v1.myreload_api_url') . ':') !!}
                    {!! Form::text('common_settings[reload][myreload_api_url]', $apiUrl, ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2" style="margin-bottom:12px;">
            <button type="button" class="btn btn-default btn-sm" id="btn_fill_myreload_demo">
                <i class="fa fa-flask"></i> @lang('lang_v1.myreload_fill_demo')
            </button>
            @if($hasBusinessCreds)
                <span class="label label-success" style="font-size:12px;padding:6px 10px;">
                    @lang('lang_v1.myreload_credentials_saved_for_business')
                </span>
            @else
                <span class="label label-default" style="font-size:12px;padding:6px 10px;">
                    @lang('lang_v1.myreload_credentials_missing')
                </span>
            @endif
        </div>

        <div class="alert alert-info" style="margin-bottom:0;">
            <strong>@lang('lang_v1.myreload_demo_credentials_title'):</strong>
            @lang('lang_v1.myreload_demo_credentials_help')
            <br>
            <code>API User Name = {{ $demoUsername }}</code>
            &nbsp;·&nbsp;
            <code>API Key = {{ $demoApiKey }}</code>
            <br>
            <a href="https://myreload.lk/" target="_blank" rel="noopener">myreload.lk</a>
            · 0767 223 223
        </div>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('btn_fill_myreload_demo');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var user = document.getElementById('myreload_api_username');
        var key = document.getElementById('myreload_api_key');
        if (user) user.value = @json($demoUsername);
        if (key) key.value = @json($demoApiKey);
        if (typeof toastr !== 'undefined') {
            toastr.info(@json(__('lang_v1.myreload_fill_demo_done')));
        }
    });
})();
</script>
