@php
    $catalogSize = trim((string) ($product->catalog_size ?? ''));
    $catalogColor = trim((string) ($product->catalog_color ?? ''));
    $sizeOptions = $catalogSize !== '' ? \App\Utils\ProductSizeColor::splitList($catalogSize) : [];
    $colorOptions = $catalogColor !== '' ? \App\Utils\ProductSizeColor::splitList($catalogColor) : [];
    $pickedSize = trim((string) ($product->line_size ?? ''));
    $pickedColor = trim((string) ($product->line_color ?? ''));
    if ($pickedSize === '' && count($sizeOptions) === 1) {
        $pickedSize = $sizeOptions[0];
    }
    if ($pickedColor === '' && count($colorOptions) === 1) {
        $pickedColor = $colorOptions[0];
    }
@endphp
@if(count($sizeOptions) || count($colorOptions))
    <div class="qpos-line-attrs">
        @if(count($sizeOptions) > 1)
            <select name="products[{{ $row_count }}][line_size]" class="qpos-line-attr-select" aria-label="@lang('product.size')">
                <option value="">@lang('product.size')</option>
                @foreach($sizeOptions as $opt)
                    <option value="{{ $opt }}" @if($pickedSize === $opt) selected @endif>{{ $opt }}</option>
                @endforeach
            </select>
        @elseif(count($sizeOptions) === 1)
            <span class="qpos-line-attr-chip">@lang('product.size'): {{ $sizeOptions[0] }}</span>
            <input type="hidden" name="products[{{ $row_count }}][line_size]" value="{{ $sizeOptions[0] }}">
        @endif

        @if(count($colorOptions) > 1)
            <select name="products[{{ $row_count }}][line_color]" class="qpos-line-attr-select" aria-label="@lang('product.color')">
                <option value="">@lang('product.color')</option>
                @foreach($colorOptions as $opt)
                    <option value="{{ $opt }}" @if($pickedColor === $opt) selected @endif>{{ $opt }}</option>
                @endforeach
            </select>
        @elseif(count($colorOptions) === 1)
            <span class="qpos-line-attr-chip">@lang('product.color'): {{ $colorOptions[0] }}</span>
            <input type="hidden" name="products[{{ $row_count }}][line_color]" value="{{ $colorOptions[0] }}">
        @endif
    </div>
@endif
