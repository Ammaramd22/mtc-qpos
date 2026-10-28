<div class="pos-tab-content" id="settings-costcode" data-settings-tab="costcode">
    <h4 class="tw-text-lg tw-font-semibold tw-text-black">@lang('lang_v1.costcode_settings')</h4>
    <p class="tw-text-sm tw-text-gray-500 tw-mb-4">@lang('lang_v1.costcode_settings_help')</p>

    {{-- Enable / Disable Toggle --}}
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="tw-flex tw-items-center tw-gap-4 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50">
                    <div class="tw-flex-1">
                        <div class="tw-font-semibold tw-text-gray-900">@lang('lang_v1.enable_costcode_generation')</div>
                        <div class="tw-text-sm tw-text-gray-500">@lang('lang_v1.enable_costcode_generation_help')</div>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <label class="bartender-switch" aria-label="@lang('lang_v1.enable_costcode_generation')">
                            <input type="hidden" name="common_settings[enable_costcode_generation]" value="0">
                            <input type="checkbox" name="common_settings[enable_costcode_generation]" value="1" id="toggle_costcode"
                                @if(!empty($common_settings['enable_costcode_generation'])) checked @endif>
                            <span class="bartender-slider"></span>
                        </label>
                        <span class="tw-text-sm tw-font-medium tw-text-gray-900" id="costcode_toggle_label" style="min-width:60px;display:inline-block;">
                            {{ !empty($common_settings['enable_costcode_generation']) ? __('lang_v1.enabled') : __('lang_v1.disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="costcode_settings_container" style="{{ !empty($common_settings['enable_costcode_generation']) ? '' : 'opacity:0.5; pointer-events:none; filter:grayscale(80%);' }}">
        <div class="row">
            <div class="col-md-12">
                <h5 class="tw-font-semibold tw-mt-4 tw-mb-2">
                    @lang('lang_v1.digit_letter_mapping')
                    <span style="display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;background:#166534;color:#dcfce7;font-size:10px;font-weight:800;vertical-align:middle;">V10</span>
                </h5>
                <p class="tw-text-sm tw-text-gray-500 tw-mb-3">@lang('lang_v1.digit_letter_mapping_help')</p>
            </div>
        </div>
        <div class="row">
            @php
                $storedMapping = !empty($common_settings['costcode_mapping']) ? $common_settings['costcode_mapping'] : [];
                if (! is_array($storedMapping)) {
                    $storedMapping = [];
                }
                $storedKeyword = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_keyword'] ?? '')));
                if (strlen($storedKeyword) !== 10 || count(array_unique(str_split($storedKeyword))) !== 10) {
                    $orderedMapping = $storedMapping;
                    ksort($orderedMapping);
                    $mappingWord = strtoupper(implode('', array_values($orderedMapping)));
                    $storedKeyword = strlen($mappingWord) === 10 && count(array_unique(str_split($mappingWord))) === 10
                        ? $mappingWord
                        : 'PROFITABLE';
                }
                $defaultLetters = str_split($storedKeyword);
                $storedRepeatZero = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_repeat_zero_letter'] ?? '')));
                if ($storedRepeatZero === '' && ! empty($common_settings['costcode_repeat_keyword'])) {
                    $storedRepeatZero = strtoupper(substr(preg_replace('/[^A-Z]/i', '', (string) $common_settings['costcode_repeat_keyword']), 0, 1));
                }
                if ($storedRepeatZero === '' || in_array($storedRepeatZero[0] ?? '', $defaultLetters, true)) {
                    $unusedLetters = array_values(array_diff(range('A', 'Z'), $defaultLetters));
                    $storedRepeatZero = $unusedLetters[0] ?? 'X';
                } else {
                    $storedRepeatZero = $storedRepeatZero[0];
                }
                $reservedSpecial = array_values(array_unique(array_merge($defaultLetters, [$storedRepeatZero])));
                $storedRepeat2 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_repeat2_letter'] ?? '')));
                if ($storedRepeat2 === '' || in_array($storedRepeat2[0] ?? '', $reservedSpecial, true)) {
                    $unusedLetters = array_values(array_diff(range('A', 'Z'), $reservedSpecial));
                    $storedRepeat2 = $unusedLetters[0] ?? 'Y';
                } else {
                    $storedRepeat2 = $storedRepeat2[0];
                }
                $reservedSpecial = array_values(array_unique(array_merge($reservedSpecial, [$storedRepeat2])));
                $storedShortCost = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_short_cost_letter'] ?? '')));
                if ($storedShortCost === '' || in_array($storedShortCost[0] ?? '', $reservedSpecial, true)) {
                    $unusedLetters = array_values(array_diff(range('A', 'Z'), $reservedSpecial));
                    $storedShortCost = $unusedLetters[0] ?? 'W';
                } else {
                    $storedShortCost = $storedShortCost[0];
                }
                $reservedSpecial = array_values(array_unique(array_merge($reservedSpecial, [$storedShortCost])));
                $storedDecimal = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_decimal_letter'] ?? '')));
                if ($storedDecimal === '' || in_array($storedDecimal[0] ?? '', $reservedSpecial, true)) {
                    $unusedLetters = array_values(array_diff(range('A', 'Z'), $reservedSpecial));
                    $storedDecimal = $unusedLetters[0] ?? 'D';
                } else {
                    $storedDecimal = $storedDecimal[0];
                }
            @endphp
            <div class="col-md-7">
                <div class="form-group">
                    <label for="costcode_keyword">@lang('lang_v1.costcode_secret_word')</label>
                    <input type="text"
                        id="costcode_keyword"
                        name="common_settings[costcode_keyword]"
                        class="form-control"
                        value="{{ $storedKeyword }}"
                        maxlength="10"
                        autocomplete="off"
                        spellcheck="false"
                        style="text-transform:uppercase;font-size:18px;font-weight:800;letter-spacing:.18em;">
                    <small class="help-block">@lang('lang_v1.costcode_secret_word_help')</small>
                    <div id="costcode_keyword_status" class="tw-text-sm tw-font-semibold"></div>
                </div>
            </div>
        </div>
        <div class="row">
            @for($digit = 0; $digit <= 9; $digit++)
                @php
                    $letter = !empty($storedMapping[$digit]) ? $storedMapping[$digit] : (!empty($storedMapping[(string)$digit]) ? $storedMapping[(string)$digit] : $defaultLetters[$digit]);
                    if (is_array($letter)) {
                        $letter = reset($letter);
                    }
                @endphp
                <div class="col-md-2 col-sm-3 col-xs-4">
                    <div class="form-group">
                        {!! Form::label("common_settings[costcode_mapping][{$digit}]", $digit . ' =', ['class' => 'tw-text-sm']) !!}
                        {!! Form::text("common_settings[costcode_mapping][{$digit}]", $letter, ['class' => 'form-control costcode-map-input', 'data-digit' => $digit, 'maxlength' => 1, 'readonly' => true, 'style' => 'text-transform:uppercase; text-align:center; font-weight:800;']); !!}
                    </div>
                </div>
            @endfor
        </div>

        <div class="costcode-repeat-panel tw-p-4 tw-rounded-lg tw-border" style="margin:10px 0 18px;">
            <h5 class="tw-font-semibold tw-m-0 tw-mb-2">@lang('lang_v1.costcode_special_letters_title')</h5>
            <p class="costcode-repeat-description tw-text-sm tw-mb-3">@lang('lang_v1.costcode_special_letters_help')</p>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="costcode_repeat_zero_letter">@lang('lang_v1.costcode_repeat_zero_label')</label>
                        <input type="text"
                            id="costcode_repeat_zero_letter"
                            name="common_settings[costcode_repeat_zero_letter]"
                            class="form-control"
                            value="{{ $storedRepeatZero }}"
                            maxlength="1"
                            autocomplete="off"
                            spellcheck="false"
                            style="text-transform:uppercase;font-size:28px;font-weight:800;letter-spacing:.08em;text-align:center;max-width:90px;">
                        <small class="help-block">@lang('lang_v1.costcode_repeat_zero_input_help')</small>
                        <div id="costcode_repeat_zero_status" class="tw-text-sm tw-font-semibold"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="costcode_repeat2_letter">@lang('lang_v1.costcode_repeat2_label')</label>
                        <input type="text"
                            id="costcode_repeat2_letter"
                            name="common_settings[costcode_repeat2_letter]"
                            class="form-control"
                            value="{{ $storedRepeat2 }}"
                            maxlength="1"
                            autocomplete="off"
                            spellcheck="false"
                            style="text-transform:uppercase;font-size:28px;font-weight:800;letter-spacing:.08em;text-align:center;max-width:90px;">
                        <small class="help-block">@lang('lang_v1.costcode_repeat2_input_help')</small>
                        <div id="costcode_repeat2_status" class="tw-text-sm tw-font-semibold"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="costcode_short_cost_letter">@lang('lang_v1.costcode_short_cost_label')</label>
                        <input type="text"
                            id="costcode_short_cost_letter"
                            name="common_settings[costcode_short_cost_letter]"
                            class="form-control"
                            value="{{ $storedShortCost }}"
                            maxlength="1"
                            autocomplete="off"
                            spellcheck="false"
                            style="text-transform:uppercase;font-size:28px;font-weight:800;letter-spacing:.08em;text-align:center;max-width:90px;">
                        <small class="help-block">@lang('lang_v1.costcode_short_cost_input_help')</small>
                        <div id="costcode_short_cost_status" class="tw-text-sm tw-font-semibold"></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="costcode_decimal_letter">@lang('lang_v1.costcode_decimal_label')</label>
                        <input type="text"
                            id="costcode_decimal_letter"
                            name="common_settings[costcode_decimal_letter]"
                            class="form-control"
                            value="{{ $storedDecimal }}"
                            maxlength="1"
                            autocomplete="off"
                            spellcheck="false"
                            style="text-transform:uppercase;font-size:28px;font-weight:800;letter-spacing:.08em;text-align:center;max-width:90px;">
                        <small class="help-block">@lang('lang_v1.costcode_decimal_input_help')</small>
                        <div id="costcode_decimal_status" class="tw-text-sm tw-font-semibold"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="costcode-example-panel tw-p-3 tw-rounded-lg tw-border tw-text-sm">
                    <b>@lang('lang_v1.costcode_example'):</b><br>
                    <strong id="costcode_live_example"></strong><br>
                    @lang('lang_v1.costcode_example_text')
                </div>
            </div>
        </div>

        <div class="row" style="margin-top:16px;">
            <div class="col-md-12">
                <h5 class="tw-font-semibold tw-mb-2">@lang('lang_v1.regenerate_costcodes')</h5>
                <p class="tw-text-sm tw-text-gray-500 tw-mb-3">@lang('lang_v1.regenerate_costcodes_help')</p>
                <button type="button" class="btn btn-primary" id="btn_regenerate_costcodes">
                    <i class="fa fa-magic"></i> @lang('lang_v1.regenerate_costcodes_now')
                </button>
                <span id="regenerate_costcodes_status" style="margin-left:10px;"></span>
            </div>
        </div>
    </div>
</div>

<style>
    #settings-costcode .costcode-repeat-panel {
        background: #111827 !important;
        border: 1px solid #374151 !important;
        color: #f8fafc !important;
        box-shadow: 0 10px 24px rgba(0, 0, 0, .22);
    }
    #settings-costcode .costcode-repeat-panel h5,
    #settings-costcode .costcode-repeat-panel label {
        color: #f8fafc !important;
    }
    #settings-costcode .costcode-repeat-panel .costcode-repeat-description,
    #settings-costcode .costcode-repeat-panel .help-block {
        color: #cbd5e1 !important;
    }
    #settings-costcode .costcode-repeat-panel .form-control {
        background: #080d16 !important;
        border: 1px solid #4b5563 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }
    #settings-costcode .costcode-repeat-panel .form-control:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, .2) !important;
    }
    #settings-costcode .costcode-example-panel {
        background: #0c1b31 !important;
        border: 1px solid #264a75 !important;
        color: #dbeafe !important;
    }
    #settings-costcode .costcode-example-panel strong,
    #settings-costcode .costcode-example-panel b {
        color: #ffffff !important;
    }
</style>

<script>
    $(document).ready(function() {
        function updateCostcodeToggle() {
            var isChecked = $('#toggle_costcode').is(':checked');
            var $container = $('#costcode_settings_container');
            var $label = $('#costcode_toggle_label');
            if (isChecked) {
                $container.css({
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'filter': 'none'
                });
                $label.text(@json(__('lang_v1.enabled')));
            } else {
                $container.css({
                    'opacity': '0.5',
                    'pointer-events': 'none',
                    'filter': 'grayscale(80%)'
                });
                $label.text(@json(__('lang_v1.disabled')));
            }
        }
        $('#toggle_costcode').on('change', updateCostcodeToggle);
        updateCostcodeToggle();

        function updateCostcodeExample() {
            var mapping = [];
            $('.costcode-map-input').each(function () {
                var digit = parseInt($(this).data('digit'), 10);
                var value = String($(this).val() || '').trim().toUpperCase();
                mapping[digit] = /^[A-Z]$/.test(value) ? value : digit;
            });
            var repeat1 = String($('#costcode_repeat_zero_letter').val() || '').trim().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1) || 'X';
            var repeat2 = String($('#costcode_repeat2_letter').val() || '').trim().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1) || 'Y';
            var shortCost = String($('#costcode_short_cost_letter').val() || '').trim().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1) || 'W';
            var decimalLetter = String($('#costcode_decimal_letter').val() || '').trim().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1) || 'D';
            function encodeCost(sample) {
                var occurrences = {};
                var code = '';
                String(sample).split('').forEach(function (ch) {
                    if (ch === '.') {
                        code += decimalLetter;
                        return;
                    }
                    var digit = ch;
                    var occurrence = occurrences[digit] || 0;
                    occurrences[digit] = occurrence + 1;
                    if (occurrence === 0) {
                        code += mapping[parseInt(digit, 10)] || digit;
                    } else if (occurrence === 1) {
                        code += repeat1;
                    } else {
                        code += repeat2;
                    }
                });
                return code;
            }
            function digitCount(sample) {
                return String(sample).replace(/[^0-9]/g, '').length;
            }
            function encodeWithSell(cost, sell) {
                var code = encodeCost(cost);
                var pad = Math.max(0, digitCount(sell) - digitCount(cost));
                if (pad > 0) {
                    code = shortCost.repeat(pad) + code;
                }
                return cost + (sell ? '/' + sell : '') + ' → ' + code;
            }
            var c5000 = encodeCost('5000');
            var c5000b = c5000.length >= 2
                ? c5000.slice(0, -2) + c5000.slice(-1) + c5000.slice(-2, -1)
                : c5000;
            $('#costcode_live_example').text(
                encodeWithSell('500', '1450') + '   |   ' +
                encodeWithSell('55.50', '') + '   |   ' +
                encodeWithSell('5000', '') + ' / 2nd product → ' + c5000b
            );
        }

        function applyCostcodeKeyword() {
            var $input = $('#costcode_keyword');
            var keyword = String($input.val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 10);
            $input.val(keyword);
            var uniqueCount = (new Set(keyword.split(''))).size;
            var valid = keyword.length === 10 && uniqueCount === 10;
            var $status = $('#costcode_keyword_status');

            if (valid) {
                $('.costcode-map-input').each(function () {
                    $(this).val(keyword.charAt(parseInt($(this).data('digit'), 10)));
                });
                $status.css('color', '#16a34a').text(@json(__('lang_v1.costcode_secret_word_valid')));
            } else {
                $status.css('color', '#dc2626').text(
                    keyword.length < 10
                        ? @json(__('lang_v1.costcode_secret_word_length_error'))
                        : @json(__('lang_v1.costcode_secret_word_unique_error'))
                );
            }
            applyCostcodeSpecialLetters();
        }

        function applyCostcodeSpecialLetters() {
            var mainKeyword = String($('#costcode_keyword').val() || '').toUpperCase();
            var $repeat = $('#costcode_repeat_zero_letter');
            var $repeat2 = $('#costcode_repeat2_letter');
            var $short = $('#costcode_short_cost_letter');
            var $decimal = $('#costcode_decimal_letter');
            var repeatLetter = String($repeat.val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1);
            var repeat2Letter = String($repeat2.val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1);
            var shortLetter = String($short.val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1);
            var decimalLetter = String($decimal.val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 1);
            $repeat.val(repeatLetter);
            $repeat2.val(repeat2Letter);
            $short.val(shortLetter);
            $decimal.val(decimalLetter);

            var $repeatStatus = $('#costcode_repeat_zero_status');
            if (!repeatLetter) {
                $repeatStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_repeat_zero_required')));
            } else if (mainKeyword.indexOf(repeatLetter) !== -1) {
                $repeatStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_repeat_zero_overlap_error')));
            } else {
                $repeatStatus.css('color', '#16a34a').text(@json(__('lang_v1.costcode_repeat_zero_valid')));
            }

            var $repeat2Status = $('#costcode_repeat2_status');
            if (!repeat2Letter) {
                $repeat2Status.css('color', '#dc2626').text(@json(__('lang_v1.costcode_repeat2_required')));
            } else if (mainKeyword.indexOf(repeat2Letter) !== -1 || repeat2Letter === repeatLetter) {
                $repeat2Status.css('color', '#dc2626').text(@json(__('lang_v1.costcode_repeat2_overlap_error')));
            } else {
                $repeat2Status.css('color', '#16a34a').text(@json(__('lang_v1.costcode_repeat2_valid')));
            }

            var $shortStatus = $('#costcode_short_cost_status');
            if (!shortLetter) {
                $shortStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_short_cost_required')));
            } else if (mainKeyword.indexOf(shortLetter) !== -1 || shortLetter === repeatLetter || shortLetter === repeat2Letter) {
                $shortStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_short_cost_overlap_error')));
            } else {
                $shortStatus.css('color', '#16a34a').text(@json(__('lang_v1.costcode_short_cost_valid')));
            }

            var $decimalStatus = $('#costcode_decimal_status');
            if (!decimalLetter) {
                $decimalStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_decimal_required')));
            } else if (mainKeyword.indexOf(decimalLetter) !== -1 || decimalLetter === repeatLetter || decimalLetter === repeat2Letter || decimalLetter === shortLetter) {
                $decimalStatus.css('color', '#dc2626').text(@json(__('lang_v1.costcode_decimal_overlap_error')));
            } else {
                $decimalStatus.css('color', '#16a34a').text(@json(__('lang_v1.costcode_decimal_valid')));
            }
            updateCostcodeExample();
        }

        $(document).on('input change', '#costcode_keyword', applyCostcodeKeyword);
        $(document).on('input change', '#costcode_repeat_zero_letter, #costcode_repeat2_letter, #costcode_short_cost_letter, #costcode_decimal_letter', applyCostcodeSpecialLetters);
        applyCostcodeKeyword();

        $(document).on('click', '#btn_regenerate_costcodes', function (e) {
            e.preventDefault();
            if (!window.confirm(@json(__('lang_v1.regenerate_costcodes_confirm')))) {
                return;
            }
            var $btn = $(this);
            var $status = $('#regenerate_costcodes_status');
            var currentMapping = {};
            $('[name^="common_settings[costcode_mapping]"]').each(function () {
                var match = this.name.match(/\[(\d)\]$/);
                if (match) {
                    currentMapping[match[1]] = $(this).val();
                }
            });
            $btn.prop('disabled', true);
            $status.css('color', '#94a3b8').text(@json(__('lang_v1.please_wait')));
            $.ajax({
                method: 'POST',
                url: "{{ url('/products/regenerate-cost-codes') }}",
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    enable_costcode_generation: $('#toggle_costcode').is(':checked') ? 1 : 0,
                    costcode_keyword: $('#costcode_keyword').val(),
                    costcode_mapping: currentMapping,
                    costcode_repeat_zero_letter: $('#costcode_repeat_zero_letter').val(),
                    costcode_repeat2_letter: $('#costcode_repeat2_letter').val(),
                    costcode_short_cost_letter: $('#costcode_short_cost_letter').val(),
                    costcode_decimal_letter: $('#costcode_decimal_letter').val()
                },
                success: function (res) {
                    if (res && res.success) {
                        if (typeof toastr !== 'undefined') toastr.success(res.msg);
                        $status.css('color', '#16a34a').text(res.msg || 'Done');
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error((res && res.msg) || 'Failed');
                        $status.css('color', '#dc2626').text((res && res.msg) || 'Failed');
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed';
                    if (typeof toastr !== 'undefined') toastr.error(msg);
                    $status.css('color', '#dc2626').text(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
