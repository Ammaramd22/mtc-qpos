<div class="pos-tab-content" data-settings-tab="offline_billing">
    <div class="row">
        <div class="col-md-12">
            <h4 class="tw-font-semibold tw-text-lg tw-mb-2">@lang('lang_v1.offline_billing')</h4>
            <p class="tw-text-sm tw-text-gray-500 tw-mb-4">@lang('lang_v1.offline_billing_help')</p>
        </div>
    </div>

    {{-- Enable / Disable Toggle --}}
    <div class="row tw-mb-4">
        <div class="col-md-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.enable_offline_billing')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.enable_offline_billing_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="offline-switch" aria-label="@lang('lang_v1.enable_offline_billing')">
                            <input type="checkbox" name="common_settings[enable_offline_billing]" value="1" id="toggle_offline_billing"
                                @if(!empty($common_settings['enable_offline_billing'])) checked @endif>
                            <span class="offline-slider"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="offline_toggle_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($common_settings['enable_offline_billing']) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual sync only --}}
    <div class="row tw-mb-4" id="offline_manual_sync_row" @if(empty($common_settings['enable_offline_billing'])) style="display:none;" @endif>
        <div class="col-md-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-amber-200 tw-bg-amber-50">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.offline_manual_sync_only')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.offline_manual_sync_only_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="offline-switch" aria-label="@lang('lang_v1.offline_manual_sync_only')">
                            <input type="checkbox" name="common_settings[offline_manual_sync_only]" value="1" id="toggle_offline_manual_sync"
                                @if(!empty($common_settings['offline_manual_sync_only'])) checked @endif>
                            <span class="offline-slider offline-slider--amber"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="offline_manual_sync_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($common_settings['offline_manual_sync_only']) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
.offline-switch { position: relative; display: inline-block; width: 50px; height: 26px; margin: 0; }
.offline-switch input { opacity: 0; width: 0; height: 0; }
.offline-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #d1d5db; border-radius: 26px; transition: .3s; }
.offline-slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.offline-switch input:checked + .offline-slider { background: #2563eb; }
.offline-switch input:checked + .offline-slider.offline-slider--amber { background: #d97706; }
.offline-switch input:checked + .offline-slider:before { transform: translateX(24px); }
.offline-switch input:focus + .offline-slider { box-shadow: 0 0 0 3px rgba(37,99,235,.25); }
</style>

<script>
    $(document).ready(function() {
        function refreshOfflineLabels() {
            var on = $('#toggle_offline_billing').is(':checked');
            $('#offline_toggle_label').text(on ? '{{ __('lang_v1.enabled') }}' : '{{ __('lang_v1.disabled') }}');
            $('#offline_manual_sync_row').toggle(on);
            var man = $('#toggle_offline_manual_sync').is(':checked');
            $('#offline_manual_sync_label').text(man ? '{{ __('lang_v1.enabled') }}' : '{{ __('lang_v1.disabled') }}');
        }
        $('#toggle_offline_billing, #toggle_offline_manual_sync').on('change', refreshOfflineLabels);
    });
</script>

    <div class="row tw-mb-4">
        <div class="col-md-12">
            <div class="tw-p-3 tw-rounded-lg tw-bg-blue-50 tw-border tw-border-blue-100 tw-text-sm tw-text-blue-800">
                <b>@lang('lang_v1.how_it_works'):</b><br>
                @lang('lang_v1.offline_billing_how_it_works')
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tw-p-3 tw-rounded-lg tw-bg-slate-50 tw-border tw-border-slate-200 tw-text-sm tw-text-slate-700">
                <b>@lang('lang_v1.offline_billing_two_modes'):</b>
                <ul class="tw-mt-2 tw-mb-0 tw-pl-5">
                    <li>@lang('lang_v1.offline_billing_mode_browser')</li>
                    <li>@lang('lang_v1.offline_billing_mode_desktop')</li>
                </ul>
            </div>
        </div>
    </div>
</div>
