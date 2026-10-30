@php
    $qposDesignerSettings = [];
    if (isset($invoice_layout) && ! empty($invoice_layout->common_settings)) {
        $qposDesignerSettings = $invoice_layout->common_settings;
        if (is_string($qposDesignerSettings)) {
            $qposDesignerSettings = json_decode($qposDesignerSettings, true) ?: [];
        }
    }
    if (! is_array($qposDesignerSettings)) {
        $qposDesignerSettings = [];
    }
    $skin = \App\Utils\InvoicePrintAppearance::normalizeSkin($qposDesignerSettings['designer'] ?? []);
    $fonts = \App\Utils\InvoicePrintAppearance::fontChoices();
    $picker = function ($key, $fallback) use ($skin) {
        return $skin[$key] !== '' ? $skin[$key] : $fallback;
    };
@endphp
<div class="box box-solid qpos-designer" id="qposDesigner">
    <div class="box-body">
        <h4 style="margin-top:0;">@lang('invoice.designer_title')</h4>
        <p class="help-block">@lang('invoice.designer_help')</p>

        <div class="qpos-designer-presets">
            <span>@lang('invoice.designer_presets')</span>
            <button type="button" class="btn btn-default btn-xs" data-qpos-preset="classic">@lang('invoice.designer_preset_classic')</button>
            <button type="button" class="btn btn-default btn-xs" data-qpos-preset="slate">@lang('invoice.designer_preset_slate')</button>
            <button type="button" class="btn btn-default btn-xs" data-qpos-preset="forest">@lang('invoice.designer_preset_forest')</button>
            <button type="button" class="btn btn-default btn-xs" id="qposResetDesigner">@lang('invoice.designer_restore')</button>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="qposFontFamily">@lang('invoice.designer_font')</label>
                    <select name="common_settings[designer][font_family]" id="qposFontFamily" class="form-control input-sm">
                        @foreach($fonts as $family => $label)
                            <option value="{{ $family }}" @if($skin['font_family'] === $family) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label>@lang('invoice.designer_alignment')</label>
                    <div class="row">
                        <div class="col-xs-4">
                            <select name="common_settings[designer][align_business]" class="form-control input-sm" aria-label="@lang('invoice.designer_align_business')">
                                <option value="">@lang('invoice.designer_align_business')</option>
                                <option value="left" @if($skin['align_business'] === 'left') selected @endif>@lang('invoice.designer_left')</option>
                                <option value="center" @if($skin['align_business'] === 'center') selected @endif>@lang('invoice.designer_center')</option>
                                <option value="right" @if($skin['align_business'] === 'right') selected @endif>@lang('invoice.designer_right')</option>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <select name="common_settings[designer][align_heading]" class="form-control input-sm" aria-label="@lang('invoice.designer_align_heading')">
                                <option value="">@lang('invoice.designer_align_heading')</option>
                                <option value="left" @if($skin['align_heading'] === 'left') selected @endif>@lang('invoice.designer_left')</option>
                                <option value="center" @if($skin['align_heading'] === 'center') selected @endif>@lang('invoice.designer_center')</option>
                                <option value="right" @if($skin['align_heading'] === 'right') selected @endif>@lang('invoice.designer_right')</option>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <select name="common_settings[designer][align_footer]" class="form-control input-sm" aria-label="@lang('invoice.designer_align_footer')">
                                <option value="">@lang('invoice.designer_align_footer')</option>
                                <option value="left" @if($skin['align_footer'] === 'left') selected @endif>@lang('invoice.designer_left')</option>
                                <option value="center" @if($skin['align_footer'] === 'center') selected @endif>@lang('invoice.designer_center')</option>
                                <option value="right" @if($skin['align_footer'] === 'right') selected @endif>@lang('invoice.designer_right')</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach([
                'business_size' => __('invoice.designer_size_business'),
                'heading_size' => __('invoice.designer_size_heading'),
                'table_size' => __('invoice.designer_size_table'),
                'totals_size' => __('invoice.designer_size_totals'),
                'footer_size' => __('invoice.designer_size_footer'),
            ] as $sizeKey => $sizeLabel)
                <div class="col-sm-2 col-xs-6">
                    <div class="form-group">
                        <label for="qpos-{{ $sizeKey }}">{{ $sizeLabel }}</label>
                        <input type="number" min="0" max="32" class="form-control input-sm" id="qpos-{{ $sizeKey }}" name="common_settings[designer][{{ $sizeKey }}]" value="{{ $skin[$sizeKey] > 0 ? $skin[$sizeKey] : '' }}" placeholder="@lang('invoice.designer_default')">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="checkbox" style="margin-top:0;">
            <label>
                <input type="checkbox" name="common_settings[designer][use_colours]" value="1" id="qposUseColours" @if(!empty($skin['use_colours'])) checked @endif>
                @lang('invoice.designer_use_colours')
            </label>
        </div>
        <div class="row qpos-colour-fields">
            @foreach([
                'primary' => [__('invoice.designer_colour_primary'), '#1a3a4a'],
                'text' => [__('invoice.designer_colour_text'), '#111111'],
                'table_header_bg' => [__('invoice.designer_colour_header'), '#1a3a4a'],
                'table_header_text' => [__('invoice.designer_colour_header_text'), '#ffffff'],
                'border' => [__('invoice.designer_colour_border'), '#cbd5e1'],
                'total_highlight' => [__('invoice.designer_colour_total'), '#1a3a4a'],
                'footer_color' => [__('invoice.designer_colour_footer'), '#334155'],
            ] as $colourKey => $colourMeta)
                <div class="col-sm-3 col-xs-6">
                    <div class="form-group">
                        <label for="qpos-{{ $colourKey }}">{{ $colourMeta[0] }}</label>
                        <input type="color" class="form-control input-sm qpos-colour" id="qpos-{{ $colourKey }}" name="common_settings[designer][{{ $colourKey }}]" value="{{ $picker($colourKey, $colourMeta[1]) }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            @foreach([
                'logo_mm' => __('invoice.designer_logo_height'),
                'section_gap' => __('invoice.designer_section_gap'),
                'cell_pad' => __('invoice.designer_cell_pad'),
                'border_width' => __('invoice.designer_border_width'),
                'page_margin' => __('invoice.designer_page_margin'),
            ] as $gapKey => $gapLabel)
                <div class="col-sm-2 col-xs-6">
                    <div class="form-group">
                        <label for="qpos-{{ $gapKey }}">{{ $gapLabel }}</label>
                        <input type="number" min="0" max="40" class="form-control input-sm" id="qpos-{{ $gapKey }}" name="common_settings[designer][{{ $gapKey }}]" value="{{ $skin[$gapKey] > 0 ? $skin[$gapKey] : '' }}" placeholder="@lang('invoice.designer_default')">
                    </div>
                </div>
            @endforeach
        </div>
        <p class="help-block">@lang('invoice.designer_sections_help')</p>
    </div>
</div>
