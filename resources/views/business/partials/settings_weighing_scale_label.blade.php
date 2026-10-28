<div class="pos-tab-content" id="settings-weighing-scale-label" data-settings-tab="weighing_scale_label">
    <h4 class="tw-text-lg tw-font-semibold tw-text-black">@lang('lang_v1.weighing_scale_label_settings')</h4>
    <p class="tw-text-sm tw-text-gray-500 tw-mb-4">@lang('lang_v1.weighing_scale_label_settings_help')</p>

    {{-- Enable / Disable Toggle --}}
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.enable_weighing_scale_label')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.enable_weighing_scale_label_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="bartender-switch" aria-label="@lang('lang_v1.enable_weighing_scale_label')">
                            <input type="hidden" name="common_settings[enable_weighing_scale_label]" value="0">
                            <input type="checkbox" name="common_settings[enable_weighing_scale_label]" value="1" id="toggle_weighing_scale_label"
                                @if(!empty($common_settings['enable_weighing_scale_label'])) checked @endif>
                            <span class="bartender-slider"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="weighing_scale_label_toggle_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($common_settings['enable_weighing_scale_label']) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings only visible when enabled --}}
    <div id="weighing_scale_label_settings_container" style="{{ !empty($common_settings['enable_weighing_scale_label']) ? '' : 'opacity:0.5; pointer-events:none; filter:grayscale(80%);' }}">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('common_settings[weighing_scale_label_prefix]', __('lang_v1.weighing_barcode_prefix') . ':') !!}
                    {!! Form::text('common_settings[weighing_scale_label_prefix]', !empty($common_settings['weighing_scale_label_prefix']) ? $common_settings['weighing_scale_label_prefix'] : '0000', ['class' => 'form-control']); !!}
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.weighing_scale_label_prefix_help')</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('common_settings[weighing_scale_label_plu_length]', __('lang_v1.weighing_product_sku_length') . ':') !!}
                    {!! Form::select('common_settings[weighing_scale_label_plu_length]', [3 => '3 digits', 4 => '4 digits', 5 => '5 digits'], !empty($common_settings['weighing_scale_label_plu_length']) ? $common_settings['weighing_scale_label_plu_length'] : 5, ['class' => 'form-control select2']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('common_settings[weighing_scale_label_weight_digits]', __('lang_v1.weighing_qty_integer_part_length') . ':') !!}
                    {!! Form::select('common_settings[weighing_scale_label_weight_digits]', [3 => '3 digits (kg)', 4 => '4 digits (grams)'], !empty($common_settings['weighing_scale_label_weight_digits']) ? $common_settings['weighing_scale_label_weight_digits'] : 4, ['class' => 'form-control select2']); !!}
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.weighing_scale_label_weight_digits_help')</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('common_settings[weighing_scale_label_width]', __('lang_v1.label_width') . ':') !!}
                    {!! Form::text('common_settings[weighing_scale_label_width]', !empty($common_settings['weighing_scale_label_width']) ? $common_settings['weighing_scale_label_width'] : '60', ['class' => 'form-control']); !!}
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.label_width_help')</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('common_settings[weighing_scale_label_height]', __('lang_v1.label_height') . ':') !!}
                    {!! Form::text('common_settings[weighing_scale_label_height]', !empty($common_settings['weighing_scale_label_height']) ? $common_settings['weighing_scale_label_height'] : '40', ['class' => 'form-control']); !!}
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.label_height_help')</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bartender-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .bartender-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .bartender-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .bartender-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .bartender-slider {
            background-color: #2196F3;
        }
        input:checked + .bartender-slider:before {
            transform: translateX(24px);
        }
    </style>

    <script>
        $(document).ready(function() {
            function updateWeighingScaleLabelToggle() {
                var isChecked = $('#toggle_weighing_scale_label').is(':checked');
                var $container = $('#weighing_scale_label_settings_container');
                var $label = $('#weighing_scale_label_toggle_label');
                if (isChecked) {
                    $container.css({
                        'opacity': '1',
                        'pointer-events': 'auto',
                        'filter': 'none'
                    });
                    $label.text('{{ __("lang_v1.enabled") }}');
                } else {
                    $container.css({
                        'opacity': '0.5',
                        'pointer-events': 'none',
                        'filter': 'grayscale(80%)'
                    });
                    $label.text('{{ __("lang_v1.disabled") }}');
                }
            }

            $('#toggle_weighing_scale_label').on('change', function() {
                updateWeighingScaleLabelToggle();
            });

            // Initialize on page load
            updateWeighingScaleLabelToggle();
        });
    </script>
</div>
