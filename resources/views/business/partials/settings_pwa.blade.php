<div class="pos-tab-content" id="settings-pwa" data-settings-tab="pwa">
    <h4 class="tw-text-lg tw-font-semibold tw-text-black">@lang('lang_v1.pwa_settings')</h4>
    <p class="tw-text-sm tw-text-gray-500 tw-mb-4">@lang('lang_v1.pwa_settings_help')</p>

    {{-- Enable / Disable Toggle --}}
    <div class="row tw-mb-4">
        <div class="col-md-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.enable_pwa')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.enable_pwa_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="pwa-switch" aria-label="@lang('lang_v1.enable_pwa')">
                            <input type="checkbox" name="common_settings[enable_pwa]" value="1" id="toggle_pwa"
                                @if(!empty($common_settings['enable_pwa'])) checked @endif>
                            <span class="pwa-slider"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="pwa_toggle_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($common_settings['enable_pwa']) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PWA Name --}}
    <div class="row tw-mb-4">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_name]', __('lang_v1.pwa_name'), ['class' => 'tw-font-semibold']) !!}
                {!! Form::text('common_settings[pwa_name]', !empty($common_settings['pwa_name']) ? $common_settings['pwa_name'] : 'QPOS', ['class' => 'form-control', 'placeholder' => 'QPOS']) !!}
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_name_help')</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_short_name]', __('lang_v1.pwa_short_name'), ['class' => 'tw-font-semibold']) !!}
                {!! Form::text('common_settings[pwa_short_name]', !empty($common_settings['pwa_short_name']) ? $common_settings['pwa_short_name'] : 'POS', ['class' => 'form-control', 'placeholder' => 'POS']) !!}
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_short_name_help')</p>
            </div>
        </div>
    </div>

    {{-- Theme Color and Background Color --}}
    <div class="row tw-mb-4">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_theme_color]', __('lang_v1.pwa_theme_color'), ['class' => 'tw-font-semibold']) !!}
                <div class="tw-flex tw-items-center tw-gap-3">
                    {!! Form::color('common_settings[pwa_theme_color]', !empty($common_settings['pwa_theme_color']) ? $common_settings['pwa_theme_color'] : '#00935F', ['class' => 'form-control', 'style' => 'width:60px;height:40px;padding:2px;']) !!}
                    {!! Form::text('common_settings[pwa_theme_color_text]', !empty($common_settings['pwa_theme_color']) ? $common_settings['pwa_theme_color'] : '#00935F', ['class' => 'form-control', 'style' => 'width:120px;', 'readonly' => 'readonly']) !!}
                </div>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_theme_color_help')</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_bg_color]', __('lang_v1.pwa_bg_color'), ['class' => 'tw-font-semibold']) !!}
                <div class="tw-flex tw-items-center tw-gap-3">
                    {!! Form::color('common_settings[pwa_bg_color]', !empty($common_settings['pwa_bg_color']) ? $common_settings['pwa_bg_color'] : '#ffffff', ['class' => 'form-control', 'style' => 'width:60px;height:40px;padding:2px;']) !!}
                    {!! Form::text('common_settings[pwa_bg_color_text]', !empty($common_settings['pwa_bg_color']) ? $common_settings['pwa_bg_color'] : '#ffffff', ['class' => 'form-control', 'style' => 'width:120px;', 'readonly' => 'readonly']) !!}
                </div>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_bg_color_help')</p>
            </div>
        </div>
    </div>

    {{-- Start URL --}}
    <div class="row tw-mb-4">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_start_url]', __('lang_v1.pwa_start_url'), ['class' => 'tw-font-semibold']) !!}
                {!! Form::select('common_settings[pwa_start_url]',
                    [
                        'pos' => 'POS',
                        'dashboard' => 'Dashboard',
                        'products' => 'Products',
                    ],
                    !empty($common_settings['pwa_start_url']) ? $common_settings['pwa_start_url'] : 'pos',
                    ['class' => 'form-control select2']
                ) !!}
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_start_url_help')</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('common_settings[pwa_display]', __('lang_v1.pwa_display'), ['class' => 'tw-font-semibold']) !!}
                {!! Form::select('common_settings[pwa_display]',
                    [
                        'standalone' => 'Standalone (Mobile App Mode)',
                        'fullscreen' => 'Fullscreen',
                        'minimal-ui' => 'Minimal UI',
                        'browser' => 'Browser',
                    ],
                    !empty($common_settings['pwa_display']) ? $common_settings['pwa_display'] : 'standalone',
                    ['class' => 'form-control select2']
                ) !!}
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_display_help')</p>
            </div>
        </div>
    </div>

    {{-- PWA Icon Upload --}}
    <div class="row tw-mb-4">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('pwa_icon', __('lang_v1.pwa_icon'), ['class' => 'tw-font-semibold']) !!}
                {!! Form::file('pwa_icon', ['class' => 'form-control', 'accept' => 'image/png,image/jpeg,image/jpg']) !!}
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.pwa_icon_help')</p>
                @if(!empty($common_settings['pwa_icon']))
                    <div class="tw-mt-2">
                        <img src="{{ asset('uploads/pwa/' . $common_settings['pwa_icon']) }}" alt="PWA Icon" style="width:96px;height:96px;border-radius:8px;border:1px solid #ddd;">
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">Current icon</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="row">
        <div class="col-md-12">
            <div class="tw-p-3 tw-rounded-lg tw-bg-blue-50 tw-border tw-border-blue-100 tw-text-sm tw-text-blue-800">
                <b>@lang('lang_v1.how_it_works'):</b><br>
                @lang('lang_v1.pwa_how_it_works')
            </div>
        </div>
    </div>
</div>

<style>
.pwa-switch { position: relative; display: inline-block; width: 50px; height: 26px; margin: 0; }
.pwa-switch input { opacity: 0; width: 0; height: 0; }
.pwa-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #d1d5db; border-radius: 26px; transition: .3s; }
.pwa-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.pwa-switch input:checked + .pwa-slider { background: #2563eb; }
.pwa-switch input:checked + .pwa-slider:before { transform: translateX(24px); }
.pwa-switch input:focus + .pwa-slider { box-shadow: 0 0 0 3px rgba(37,99,235,.25); }
</style>

<script>
    $(document).ready(function() {
        $('#toggle_pwa').on('change', function() {
            var label = $(this).is(':checked') ? '{{ __('lang_v1.enabled') }}' : '{{ __('lang_v1.disabled') }}';
            $('#pwa_toggle_label').text(label);
        });

        $('input[name="common_settings[pwa_theme_color]"]').on('input', function() {
            $('input[name="common_settings[pwa_theme_color_text]"]').val($(this).val());
        });

        $('input[name="common_settings[pwa_bg_color]"]').on('input', function() {
            $('input[name="common_settings[pwa_bg_color_text]"]').val($(this).val());
        });
    });
</script>
