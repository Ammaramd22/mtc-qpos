<div class="pos-tab-content" id="settings-weighing-scale-barcode" data-settings-tab="weighing_scale_barcode_scan">
    <h4 class="tw-text-lg tw-font-semibold tw-text-black">@lang('lang_v1.weighing_scale_setting')</h4>
    <p class="tw-text-sm tw-text-gray-500 tw-mb-4">@lang('lang_v1.weighing_scale_setting_help')</p>

@php
    $csStr = function ($key, $default = '') use ($common_settings) {
        $v = $common_settings[$key] ?? $default;
        if (is_array($v)) {
            $flat = null;
            array_walk_recursive($v, function ($item) use (&$flat) {
                if (! is_array($item)) {
                    $flat = $item;
                }
            });
            return $flat ?? $default;
        }
        return $v;
    };
@endphp

    <div class="row">
        {{-- Enable Weighing Scale Barcode Scanning --}}
        <div class="col-sm-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50 tw-mb-4">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.enable_weighing_scale_scan')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.enable_weighing_scale_scan_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="bartender-switch" aria-label="@lang('lang_v1.enable_weighing_scale_scan')">
                            <input type="checkbox" name="common_settings[enable_weighing_scale_scan]" value="1" id="toggle_weighing_scale_scan"
                                @if(!empty($csStr('enable_weighing_scale_scan'))) checked @endif>
                            <span class="bartender-slider"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="weighing_scale_scan_toggle_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($csStr('enable_weighing_scale_scan')) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SKU + Weight Format Settings --}}
        <div id="weighing_scale_scan_settings" class="col-sm-12" style="{{ !empty($csStr('enable_weighing_scale_scan')) ? '' : 'opacity:0.5; pointer-events:none; filter:grayscale(80%);' }}">
            <div class="row">
                <div class="col-sm-12">
                    <h5 class="tw-font-semibold tw-text-gray-700">@lang('lang_v1.weighing_scale_scan_format_settings')</h5>
                    <p class="tw-text-sm tw-text-gray-500 tw-mb-3">@lang('lang_v1.weighing_scale_scan_format_help')</p>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('common_settings[weighing_scale_sku_length]', __('lang_v1.weighing_scale_sku_length') . ':') !!}
                        {!! Form::select('common_settings[weighing_scale_sku_length]', [4 => '4 digits', 5 => '5 digits', 6 => '6 digits', 7 => '7 digits', 8 => '8 digits'], $csStr('weighing_scale_sku_length', 6), ['class' => 'form-control select2', 'id' => 'weighing_scale_sku_length']); !!}
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.weighing_scale_sku_length_help')</p>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('common_settings[weighing_scale_weight_digits]', __('lang_v1.weighing_scale_weight_digits') . ':') !!}
                        {!! Form::select('common_settings[weighing_scale_weight_digits]', [3 => '3 digits', 4 => '4 digits', 5 => '5 digits'], $csStr('weighing_scale_weight_digits', 4), ['class' => 'form-control select2', 'id' => 'weighing_scale_weight_digits']); !!}
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.weighing_scale_weight_digits_help')</p>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('common_settings[weighing_scale_barcode_prefix]', __('lang_v1.weighing_barcode_prefix') . ':') !!}
                        {!! Form::text('common_settings[weighing_scale_barcode_prefix]', (string) $csStr('weighing_scale_barcode_prefix', ''), ['class' => 'form-control', 'id' => 'weighing_scale_barcode_prefix']); !!}
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">@lang('lang_v1.weighing_scale_barcode_prefix_help')</p>
                    </div>
                </div>

                <div class="col-sm-12"><hr class="tw-my-2"></div>

                <div class="col-sm-12">
                    <h5 class="tw-font-semibold tw-text-gray-700">@lang('lang_v1.weighing_scale_legacy_format_settings')</h5>
                    <p class="tw-text-sm tw-text-gray-500 tw-mb-3">@lang('lang_v1.weighing_scale_legacy_format_help')</p>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('label_prefix', __('lang_v1.weighing_barcode_prefix') . ':') !!}
                        {!! Form::text('weighing_scale_setting[label_prefix]', isset($weighing_scale_setting['label_prefix']) && !is_array($weighing_scale_setting['label_prefix']) ? $weighing_scale_setting['label_prefix'] : null, ['class' => 'form-control', 'id' => 'label_prefix']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('product_sku_length', __('lang_v1.weighing_product_sku_length') . ':') !!}
                        {!! Form::select('weighing_scale_setting[product_sku_length]', [1,2,3,4,5,6,7,8,9], isset($weighing_scale_setting['product_sku_length']) && !is_array($weighing_scale_setting['product_sku_length']) ? $weighing_scale_setting['product_sku_length'] : 4, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'product_sku_length']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('qty_length', __('lang_v1.weighing_qty_integer_part_length') . ':') !!}
                        {!! Form::select('weighing_scale_setting[qty_length]', [1,2,3,4,5], isset($weighing_scale_setting['qty_length']) && !is_array($weighing_scale_setting['qty_length']) ? $weighing_scale_setting['qty_length'] : 3, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'qty_length']); !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('qty_length_decimal', __('lang_v1.weighing_qty_fractional_part_length') . ':') !!}
                        {!! Form::select('weighing_scale_setting[qty_length_decimal]', [1,2,3,4], isset($weighing_scale_setting['qty_length_decimal']) && !is_array($weighing_scale_setting['qty_length_decimal']) ? $weighing_scale_setting['qty_length_decimal'] : 2, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'qty_length_decimal']); !!}
                    </div>
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
            function updateWeighingScaleScanToggle() {
                var isChecked = $('#toggle_weighing_scale_scan').is(':checked');
                var $container = $('#weighing_scale_scan_settings');
                var $label = $('#weighing_scale_scan_toggle_label');
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

            $('#toggle_weighing_scale_scan').on('change', function() {
                updateWeighingScaleScanToggle();
            });

            updateWeighingScaleScanToggle();
        });
    </script>
</div>
