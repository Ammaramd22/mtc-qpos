@php
    $settings_visibility = $settings_visibility ?? \App\Utils\SoftwareOwnerUtil::getVisibility();
    $settings_option_defs = $settings_option_defs ?? \App\Utils\SoftwareOwnerUtil::optionDefinitions();
    $feature_visibility = $feature_visibility ?? \App\Utils\SoftwareOwnerUtil::getFeatures();
    $feature_defs = $feature_defs ?? \App\Utils\SoftwareOwnerUtil::featureDefinitions();
    $feature_groups = [
        'core' => __('lang_v1.feature_group_core'),
        'inventory' => __('lang_v1.feature_group_inventory'),
        'ops' => __('lang_v1.feature_group_ops'),
        'finance' => __('lang_v1.feature_group_finance'),
        'settings' => __('lang_v1.feature_group_settings'),
        'system' => __('lang_v1.feature_group_system'),
        'extra' => __('lang_v1.feature_group_extra'),
    ];
@endphp

@php
    $login_bg_url = $login_bg_url ?? \App\Utils\SoftwareOwnerUtil::loginBackgroundUrl();
    $login_bg_file = $login_bg_file ?? \App\Utils\SoftwareOwnerUtil::loginBackgroundFilename();
@endphp

<div class="alert alert-warning" style="margin-bottom: 16px;">
    <i class="fa fa-lock"></i>
    @lang('lang_v1.software_owner_panel_help')
    <br>
    <small>
        .env: <code>ADMINISTRATOR_USERNAMES={{ auth()->user()->username }}</code>
        &nbsp;·&nbsp; Only this login sees this panel.
    </small>
</div>

@include('business.partials.settings_shop_mode')

<h3 style="margin-top:0;"><i class="fa fa-image"></i> @lang('lang_v1.login_background')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.login_background_help')</p>

{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postLoginBackground']), 'method' => 'post', 'files' => true, 'style' => 'margin-bottom: 24px;']) !!}
<div class="row">
    <div class="col-md-5">
        <div style="border:1px solid #2a2a2a;border-radius:8px;overflow:hidden;background:#111;min-height:160px;">
            @if(!empty($login_bg_url))
                <img src="{{ $login_bg_url }}" alt="Login background" style="width:100%;height:180px;object-fit:cover;display:block;">
            @else
                <div style="height:180px;display:flex;align-items:center;justify-content:center;color:#888;padding:16px;text-align:center;">
                    @lang('lang_v1.login_bg_default_preview')
                </div>
            @endif
        </div>
        @if(!empty($login_bg_file))
            <small class="text-muted">{{ $login_bg_file }}</small>
        @endif
    </div>
    <div class="col-md-7">
        <div class="form-group">
            {!! Form::label('login_bg_image', __('lang_v1.upload_login_background') . ':') !!}
            {!! Form::file('login_bg_image', ['accept' => 'image/jpeg,image/png,image/webp,image/gif', 'class' => 'form-control', 'style' => 'padding-top:6px;']) !!}
            <p class="help-block">@lang('lang_v1.login_bg_upload_hint')</p>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> @lang('lang_v1.save_login_background')
        </button>
        @if(!empty($login_bg_file))
            <button type="submit" name="remove_login_bg" value="1" class="btn btn-default"
                onclick="return confirm(@json(__('lang_v1.login_bg_remove_confirm')));">
                <i class="fa fa-trash"></i> @lang('lang_v1.remove_login_background')
            </button>
        @endif
    </div>
</div>
{!! Form::close() !!}

<hr>

{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postSettingsMenuVisibility']), 'method' => 'post']) !!}

@php
    $soft_owner_modules = $soft_owner_modules ?? \App\Utils\SoftwareOwnerUtil::getSoftOwnerModulePrefs();
    $soft_owner_module_defs = $soft_owner_module_defs ?? \App\Utils\SoftwareOwnerUtil::softOwnerModuleDefinitions();
@endphp

{{-- ── 1) Soft Owner login only ── --}}
<div class="alert alert-info" style="margin-bottom: 12px;">
    <strong>1. Soft Owner modules</strong> — @lang('lang_v1.soft_owner_modules_help')
</div>
<h3 style="margin-top:0;"><i class="fa fa-user-shield"></i> @lang('lang_v1.soft_owner_modules')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.soft_owner_modules_help_detail')</p>
<div class="row" style="margin-bottom: 18px;">
    @foreach($soft_owner_module_defs as $key => $label)
        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 8px;">
            <label style="font-weight:normal;">
                <input type="hidden" name="soft_owner_modules[{{ $key }}]" value="0">
                <input type="checkbox"
                    class="input-icheck"
                    name="soft_owner_modules[{{ $key }}]"
                    value="1"
                    @if(!empty($soft_owner_modules[$key])) checked @endif>
                {{ $label }}
            </label>
        </div>
    @endforeach
</div>

<hr>

{{-- ── 2) Modules for other users (Admin etc.) — Soft Owner permissions ── --}}
@php
    $shop_modules = $shop_modules ?? \App\Utils\SoftwareOwnerUtil::getShopModulesForBusiness((int) (session('user.business_id') ?: auth()->user()->business_id));
    $shop_module_defs = $shop_module_defs ?? \App\Utils\SoftwareOwnerUtil::shopModuleDefinitions();
@endphp
<div class="alert alert-success" style="margin-bottom: 12px;">
    <strong>2. Modules for other users</strong> — @lang('lang_v1.shop_modules_help')
</div>
<h3 style="margin-top:0;"><i class="fa fa-puzzle-piece"></i> @lang('lang_v1.shop_modules')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.shop_modules_help_detail')</p>
<div class="row" style="margin-bottom: 18px;">
    @php
        $industry_module_keys = class_exists(\App\Utils\ShopModeUtil::class)
            ? \App\Utils\ShopModeUtil::industryModuleKeys()
            : [];
    @endphp
    @foreach($industry_module_keys as $ind_key)
        @if(!empty($shop_modules[$ind_key]))
            <input type="hidden" name="shop_modules[{{ $ind_key }}]" value="1">
        @endif
    @endforeach
    @foreach($shop_module_defs as $key => $label)
        @if(in_array($key, $industry_module_keys, true))
            @continue
        @endif
        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 8px;">
            <label style="font-weight:normal;">
                <input type="hidden" name="shop_modules[{{ $key }}]" value="0">
                <input type="checkbox"
                    class="input-icheck"
                    name="shop_modules[{{ $key }}]"
                    value="1"
                    @if(!empty($shop_modules[$key])) checked @endif>
                {{ $label }}
            </label>
        </div>
    @endforeach
</div>

<hr>

{{-- ── Reports menu items for other users ── --}}
@php
    $report_visibility = $report_visibility ?? \App\Utils\SoftwareOwnerUtil::getReports();
    $report_defs = $report_defs ?? \App\Utils\SoftwareOwnerUtil::reportDefinitions();
@endphp
<div class="alert alert-info" style="margin-bottom: 12px;">
    <strong>Reports access</strong> — @lang('lang_v1.report_access_help')
</div>
<h3 style="margin-top:0;"><i class="fa fa-chart-bar"></i> @lang('lang_v1.report_access')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.report_access_help_detail')</p>
<div class="row" style="margin-bottom: 18px;">
    @foreach($report_defs as $key => $label)
        <div class="col-md-4 col-sm-6" style="margin-bottom: 8px;">
            <label style="font-weight:normal;">
                <input type="hidden" name="report_visibility[{{ $key }}]" value="0">
                <input type="checkbox"
                    class="input-icheck"
                    name="report_visibility[{{ $key }}]"
                    value="1"
                    @if(!empty($report_visibility[$key])) checked @endif>
                {{ $label }}
            </label>
        </div>
    @endforeach
</div>

<hr>

{{-- ── 3) App feature / settings tab access for other users ── --}}
<div class="alert alert-warning" style="margin-bottom: 12px;">
    <strong>3. App access for other users</strong> — @lang('lang_v1.app_features_for_others_help')
</div>
<h3 style="margin-top:0;"><i class="fa fa-users"></i> @lang('lang_v1.app_features_for_others')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.app_features_help')</p>

<div class="row">
    @foreach($feature_groups as $group => $groupLabel)
        @php
            $groupItems = array_filter($feature_defs, function ($def) use ($group) {
                return ($def['group'] ?? '') === $group;
            });
        @endphp
        @if(empty($groupItems))
            @continue
        @endif
        <div class="col-md-6" style="margin-bottom: 14px;">
            <div class="box box-solid" style="border:1px solid #2a2a2a;">
                <div class="box-header with-border" style="padding:8px 12px;">
                    <h4 class="box-title" style="font-size:14px;margin:0;">{{ $groupLabel }}</h4>
                </div>
                <div class="box-body" style="padding:0;">
                    <table class="table table-condensed table-striped" style="margin:0;">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.option')</th>
                                <th class="text-center" style="width:110px;">Enable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupItems as $key => $def)
                                <tr>
                                    <td>{{ $def['label'] }}</td>
                                    <td class="text-center">
                                        <input type="hidden" name="feature_visibility[{{ $key }}]" value="0">
                                        <input type="checkbox"
                                            class="input-icheck"
                                            name="feature_visibility[{{ $key }}]"
                                            value="1"
                                            @if(!empty($feature_visibility[$key])) checked @endif>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<hr>
<h3><i class="fa fa-cog"></i> @lang('lang_v1.settings_options_for_others')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.software_owner_access_help')</p>
<p class="help-block">@lang('lang_v1.shop_modules_settings_hint')</p>

<div class="row">
    @foreach(['menu' => __('lang_v1.settings_menu_pages'), 'tab' => __('lang_v1.settings_tabs')] as $group => $groupLabel)
        <div class="col-md-6">
            <h4 style="margin-top: 0;">{{ $groupLabel }}</h4>
            <div class="table-responsive">
                <table class="table table-condensed table-striped">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.option')</th>
                            <th class="text-center" style="width:110px;">Enable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($settings_option_defs as $key => $def)
                            @if(($def['group'] ?? '') !== $group)
                                @continue
                            @endif
                            {{-- Modules tab is Soft Owner only — skip from shop-user toggles --}}
                            @if($key === 'modules')
                                @continue
                            @endif
                            <tr>
                                <td>{{ $def['label'] }}</td>
                                <td class="text-center">
                                    <input type="hidden" name="settings_visibility[{{ $key }}]" value="0">
                                    <input type="checkbox"
                                        class="input-icheck"
                                        name="settings_visibility[{{ $key }}]"
                                        value="1"
                                        @if(!empty($settings_visibility[$key])) checked @endif>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-12 text-center" style="margin-top: 10px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa fa-save"></i> @lang('lang_v1.save_settings_access')
        </button>
    </div>
</div>
{!! Form::close() !!}
