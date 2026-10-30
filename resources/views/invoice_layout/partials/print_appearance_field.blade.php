@php
    $qposPrintAppearance = isset($invoice_layout)
        ? \App\Utils\InvoicePrintAppearance::fromLayout($invoice_layout)
        : \App\Utils\InvoicePrintAppearance::COLOUR;
@endphp
<div class="form-group qpos-print-appearance">
    <label class="qpos-print-appearance-label">@lang('invoice.print_appearance')</label>
    <div class="qpos-mode-toggle" role="radiogroup" aria-label="@lang('invoice.print_appearance')">
        <label class="qpos-mode-option {{ $qposPrintAppearance !== 'black_white' ? 'is-selected' : '' }}">
            <input type="radio" name="common_settings[print_appearance]" value="colour" {{ $qposPrintAppearance !== 'black_white' ? 'checked' : '' }}>
            <span class="qpos-mode-title">@lang('invoice.print_appearance_colour')</span>
            <small>@lang('invoice.print_appearance_colour_hint')</small>
        </label>
        <label class="qpos-mode-option {{ $qposPrintAppearance === 'black_white' ? 'is-selected' : '' }}">
            <input type="radio" name="common_settings[print_appearance]" value="black_white" {{ $qposPrintAppearance === 'black_white' ? 'checked' : '' }}>
            <span class="qpos-mode-title">@lang('invoice.print_appearance_bw')</span>
            <small>@lang('invoice.print_appearance_bw_hint')</small>
        </label>
    </div>
    <span class="help-block">@lang('invoice.print_appearance_help')</span>
    <button type="button" class="btn btn-default btn-xs" id="qposResetPrintAppearance">@lang('invoice.print_appearance_reset')</button>
</div>
