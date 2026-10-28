@php
    $amount_name = $amount_name ?? 'amount';
    $presets = $presets ?? [100, 200, 500, 1000, 2000, 5000];
    $default = $default ?? null;
    $custom_only = !empty($custom_only);
@endphp
<div class="form-group vspos-bill-amount-wrap" data-amount-group="{{ $amount_name }}">
    {!! Form::label($amount_name, __('sale.amount') . ' (Rs):') !!}
    @if(!$custom_only)
    <div class="vspos-bill-amount-chips">
        @foreach($presets as $preset)
            <button type="button"
                class="vspos-bill-amount-chip @if($default == $preset) is-active @endif"
                data-amount="{{ $preset }}">
                Rs {{ number_format($preset) }}
            </button>
        @endforeach
        <button type="button" class="vspos-bill-amount-chip vspos-bill-amount-chip--custom @if(empty($default)) is-active @endif" data-amount="custom">
            @lang('lang_v1.custom')
        </button>
    </div>
    @endif
    <div class="vspos-bill-amount-input-wrap @if(!$custom_only && !empty($default)) hide @endif">
        <input type="number"
            class="form-control input-lg vspos-bill-amount-input"
            min="50"
            step="1"
            placeholder="@lang('lang_v1.enter_custom_amount')"
            value="{{ empty($default) && $custom_only ? '' : ($default ?? '') }}"
            data-amount-group="{{ $amount_name }}">
    </div>
</div>
