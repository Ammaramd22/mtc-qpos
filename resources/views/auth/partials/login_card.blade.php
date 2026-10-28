@inject('request', 'Illuminate\Http\Request')
@php
    $login_username = $login_username ?? old('username');
    $login_password = $login_password ?? null;
    $show_demo = $show_demo ?? (config('app.env') == 'demo');
    $compact = $compact ?? false;
    $loginBrand = $login_brand ?? [
        'app_name' => config('app.name', 'QPOS'),
        'logo' => asset('img/qpos-icon.png'),
        'shop_name' => null,
        'shop_tagline' => null,
        'theme' => 'qpos',
        'accent' => '#f5c518',
    ];
@endphp

<div class="vspos-login-wrap">
    <div class="vspos-login-card" id="vspos-login-card" data-theme="{{ $loginBrand['theme'] ?? 'qpos' }}">
        <div class="vspos-login-brand">
            <img src="{{ $loginBrand['logo'] ?? asset('img/qpos-icon.png') }}" alt="QPOS" class="vspos-login-logo">
            <div class="vspos-login-brand-text">
                <strong>QPOS</strong>
                <span id="vspos-login-shop-name">
                    @if(!empty($loginBrand['shop_name']))
                        {{ $loginBrand['shop_name'] }}
                    @else
                        Point of Sale
                    @endif
                </span>
            </div>
        </div>

        <div class="vspos-login-header">
            <h1>@lang('lang_v1.welcome_back')</h1>
            <p id="vspos-login-subtitle">
                @if(!empty($loginBrand['shop_name']))
                    Sign in to <strong id="vspos-login-shop-inline">{{ $loginBrand['shop_name'] }}</strong>
                @else
                    @lang('lang_v1.sign_in_to') QPOS
                @endif
            </p>
        </div>

        <div class="vspos-login-tabs" role="tablist">
            <button type="button" class="vspos-login-tab active" data-tab="password" role="tab" aria-selected="true">
                @lang('lang_v1.login_with_password')
            </button>
            <button type="button" class="vspos-login-tab" data-tab="pin" role="tab" aria-selected="false">
                @lang('lang_v1.login_with_pin')
            </button>
        </div>

        {{-- Password login --}}
        <div class="vspos-login-panel active" id="vspos-panel-password" role="tabpanel">
            <form method="POST" action="{{ route('login') }}" id="login-form" autocomplete="on">
                @csrf
                <div class="vspos-field {{ $errors->has('username') && ! $errors->has('pin') ? 'has-error' : '' }}">
                    <label for="username">@lang('lang_v1.username')</label>
                    <input type="text" name="username" id="username" value="{{ $login_username }}" required autofocus
                        placeholder="@lang('lang_v1.username')" autocomplete="username">
                    @if ($errors->has('username') && ! $errors->has('pin'))
                        <div class="help-block"><strong>{{ $errors->first('username') }}</strong></div>
                    @endif
                </div>

                <div class="vspos-field vspos-password-toggle {{ $errors->has('password') ? 'has-error' : '' }}">
                    <div class="vspos-field-row">
                        <label for="password">@lang('lang_v1.password')</label>
                        @if (config('app.env') != 'demo')
                            <a href="{{ route('password.request') }}" class="vspos-link" tabindex="-1">@lang('lang_v1.forgot_your_password')</a>
                        @endif
                    </div>
                    <input type="password" name="password" id="password" value="{{ $login_password }}" required
                        placeholder="@lang('lang_v1.password')" autocomplete="current-password">
                    <button type="button" class="vspos-toggle-btn" id="show_hide_icon" aria-label="Toggle password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                    </button>
                    @if ($errors->has('password'))
                        <div class="help-block"><strong>{{ $errors->first('password') }}</strong></div>
                    @endif
                </div>

                <div class="vspos-checkbox-row">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember"><span>@lang('lang_v1.remember_me')</span></label>
                </div>

                @if (config('constants.enable_recaptcha'))
                    <div class="vspos-field">
                        <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                        @if ($errors->has('g-recaptcha-response'))
                            <div class="text-danger">{{ $errors->first('g-recaptcha-response') }}</div>
                        @endif
                    </div>
                @endif

                <button type="submit" class="vspos-btn-primary">@lang('lang_v1.login')</button>
            </form>
        </div>

        {{-- PIN login --}}
        <div class="vspos-login-panel" id="vspos-panel-pin" role="tabpanel">
            <form method="POST" action="{{ route('login.pin') }}" id="pin-login-form" autocomplete="off">
                @csrf
                <div class="vspos-field {{ $errors->has('username') && $errors->has('pin') ? 'has-error' : '' }}">
                    <label for="pin_username">@lang('lang_v1.username')</label>
                    <input type="text" name="username" id="pin_username" value="{{ old('username') }}" required
                        placeholder="@lang('lang_v1.username')" autocomplete="username">
                </div>

                <p class="vspos-pin-hint">@lang('lang_v1.pin_login_hint')</p>

                <div class="vspos-pin-display" id="pin-display" aria-hidden="true">
                    <span class="vspos-pin-dot"></span>
                    <span class="vspos-pin-dot"></span>
                    <span class="vspos-pin-dot"></span>
                    <span class="vspos-pin-dot"></span>
                    <span class="vspos-pin-dot"></span>
                    <span class="vspos-pin-dot"></span>
                </div>

                <input type="hidden" name="pin" id="pin_value" value="">

                @if ($errors->has('pin'))
                    <div class="vspos-field">
                        <div class="help-block text-center"><strong>{{ $errors->first('pin') }}</strong></div>
                    </div>
                @endif

                <div class="vspos-pin-pad" id="pin-pad">
                    <button type="button" class="vspos-pin-key" data-digit="1">1</button>
                    <button type="button" class="vspos-pin-key" data-digit="2">2</button>
                    <button type="button" class="vspos-pin-key" data-digit="3">3</button>
                    <button type="button" class="vspos-pin-key" data-digit="4">4</button>
                    <button type="button" class="vspos-pin-key" data-digit="5">5</button>
                    <button type="button" class="vspos-pin-key" data-digit="6">6</button>
                    <button type="button" class="vspos-pin-key" data-digit="7">7</button>
                    <button type="button" class="vspos-pin-key" data-digit="8">8</button>
                    <button type="button" class="vspos-pin-key" data-digit="9">9</button>
                    <button type="button" class="vspos-pin-key action" data-action="clear">CLR</button>
                    <button type="button" class="vspos-pin-key" data-digit="0">0</button>
                    <button type="button" class="vspos-pin-key action" data-action="back">⌫</button>
                </div>

                <div class="vspos-checkbox-row">
                    <input type="checkbox" name="remember" id="pin_remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="pin_remember"><span>@lang('lang_v1.remember_me')</span></label>
                </div>

                <button type="submit" class="vspos-btn-primary" id="pin-submit-btn" disabled>@lang('lang_v1.login')</button>
            </form>
        </div>

        <button type="button" class="vspos-btn-ghost" id="open-setup-pin-modal">@lang('lang_v1.setup_login_pin')</button>

        @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
            @if (config('constants.allow_registration'))
                <div class="vspos-login-footer">
                    {{ __('business.not_yet_registered') }}
                    <a href="{{ route('business.getRegister') }}@if (!empty(request()->lang)){{ '?lang=' . request()->lang }}@endif">@lang('business.register_now')</a>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Setup PIN modal --}}
<div class="vspos-modal-overlay" id="setup-pin-modal" aria-hidden="true">
    <div class="vspos-modal">
        <div class="vspos-modal-inner">
            <button type="button" class="vspos-modal-close" id="close-setup-pin-modal" aria-label="Close">&times;</button>
            <h3>@lang('lang_v1.setup_login_pin')</h3>
            <p>@lang('lang_v1.setup_pin_hint')</p>
            <form method="POST" action="{{ route('login.setupPin') }}" id="setup-pin-form">
                @csrf
                <div class="vspos-field">
                    <label for="setup_username">@lang('lang_v1.username')</label>
                    <input type="text" name="username" id="setup_username" value="{{ old('username') }}" required>
                </div>
                <div class="vspos-field vspos-password-toggle">
                    <label for="setup_password">@lang('lang_v1.password')</label>
                    <input type="password" name="password" id="setup_password" required autocomplete="current-password">
                </div>
                <div class="vspos-field">
                    <label for="setup_pin">@lang('lang_v1.new_pin')</label>
                    <input type="password" name="pin" id="setup_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required placeholder="4–6 digits">
                </div>
                <div class="vspos-field">
                    <label for="setup_pin_confirmation">@lang('lang_v1.confirm_pin')</label>
                    <input type="password" name="pin_confirmation" id="setup_pin_confirmation" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
                </div>
                @if ($errors->has('pin') && old('_token'))
                    <div class="help-block text-danger" style="margin-bottom:12px;">{{ $errors->first('pin') }}</div>
                @endif
                @if ($errors->has('password') && old('_token'))
                    <div class="help-block text-danger" style="margin-bottom:12px;">{{ $errors->first('password') }}</div>
                @endif
                <button type="submit" class="vspos-btn-primary">@lang('lang_v1.setup_pin')</button>
            </form>
        </div>
    </div>
</div>
