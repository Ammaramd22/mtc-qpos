@php
    $sym = trim((string) ($currency_symbol ?? ''));
    if ($sym === '') {
        $sym = 'Rs';
    }
    $priceText = trim((string) ($sticker['price_text'] ?? ''));
    if ($priceText === '') {
        $amount = number_format(
            (float) ($sticker['price'] ?? 0),
            session('business.currency_precision', 2),
            session('currency')['decimal_separator'] ?? '.',
            session('currency')['thousand_separator'] ?? ','
        );
        $priceText = rtrim($sym).' '.$amount;
    }
    $cc = trim((string) ($sticker['costcode'] ?? ''));
    $sizeText = trim((string) ($sticker['size'] ?? ''));
    $colorText = trim((string) ($sticker['color'] ?? ''));
    $brandText = trim((string) ($sticker['brand'] ?? ''));
    $categoryText = trim((string) ($sticker['category'] ?? ''));
    $nameText = trim((string) ($sticker['name'] ?? ''));
    $skuText = trim((string) ($sticker['sku'] ?? ''));
    $barcodeValue = trim((string) ($sticker['barcode_value'] ?? $skuText));
    $sizeOptions = array_values($sticker['size_options'] ?? []);
    $colorOptions = array_values($sticker['color_options'] ?? []);
    $sizeOn = ! empty($opts['show_size']) && $sizeText !== '';
    $colorOn = ! empty($opts['show_color']) && $colorText !== '';
    $rotClass = in_array((string) ($printRotate ?? $opts['print_rotate'] ?? '0'), ['0', '90', '180', '270'], true)
        ? (string) ($printRotate ?? $opts['print_rotate'] ?? '0')
        : '0';
    $elementLayout = [];
    $rawElements = $opts['element_layout'] ?? '{}';
    if (is_array($rawElements)) {
        $elementLayout = $rawElements;
    } elseif (is_string($rawElements) && $rawElements !== '') {
        $decodedElements = json_decode($rawElements, true);
        if (is_array($decodedElements)) {
            $elementLayout = $decodedElements;
        }
    }
    $elStyle = function (string $key, bool $hidden) use ($elementLayout): string {
        $item = $elementLayout[$key] ?? null;
        $css = [];
        if (is_array($item)) {
            $hasX = isset($item['x']) && $item['x'] !== '' && is_numeric($item['x']);
            $hasY = isset($item['y']) && $item['y'] !== '' && is_numeric($item['y']);
            if ($hasX || $hasY) {
                $left = round((float) ($hasX ? $item['x'] : 0), 2);
                $css[] = 'position:absolute';
                $css[] = 'left:'.$left.'%';
                $css[] = 'top:'.round((float) ($hasY ? $item['y'] : 0), 2).'%';
                $css[] = 'margin:0';
                $css[] = 'z-index:2';
                $css[] = 'overflow:hidden';
                $css[] = 'max-width:'.max(8, round(100 - $left, 2)).'%';
            }
            if (isset($item['size']) && $item['size'] !== '' && is_numeric($item['size'])) {
                $css[] = 'font-size:'.(int) $item['size'].'px';
            }
            if (! empty($item['weight'])) {
                $css[] = 'font-weight:'.(int) $item['weight'];
            }
            if (! empty($item['align']) && in_array($item['align'], ['left', 'center', 'right'], true)) {
                $css[] = 'text-align:'.$item['align'];
            }
        }
        if ($hidden) {
            $css[] = 'display:none';
        }

        return implode(';', $css);
    };
    $elPlaced = function (string $key) use ($elementLayout): bool {
        $item = $elementLayout[$key] ?? null;

        return is_array($item) && (
            (isset($item['x']) && $item['x'] !== '' && is_numeric($item['x']))
            || (isset($item['y']) && $item['y'] !== '' && is_numeric($item['y']))
        );
    };
    $priceCss = $elStyle('price', false);
    $costcodeCss = $elStyle('costcode', false);
    if (empty($opts['show_price'])) {
        $priceCss = ($priceCss !== '' ? $priceCss.';' : '').'visibility:hidden';
    }
    if (empty($opts['show_costcode'])) {
        $costcodeCss = ($costcodeCss !== '' ? $costcodeCss.';' : '').'visibility:hidden';
    }
@endphp
<div class="label bc-{{ $bcPos ?? ($opts['barcode_position'] ?? 'middle') }} rot-{{ $rotClass }}"
    data-row="{{ (int) ($sticker['row'] ?? 0) }}"
    data-sku="{{ $skuText }}"
    data-barcode="{{ $barcodeValue }}"
    data-size="{{ $sizeText }}"
    data-color="{{ $colorText }}"
    data-sizes="{{ json_encode($sizeOptions, JSON_UNESCAPED_UNICODE) }}"
    data-colors="{{ json_encode($colorOptions, JSON_UNESCAPED_UNICODE) }}">
    <div class="label-shift">
        <div class="label-inner">
            <div class="head">
                <div class="biz" data-el="biz" @if($elPlaced('biz')) data-placed="1" @endif @if($elStyle('biz', empty($opts['show_business_name'])) !== '') style="{{ $elStyle('biz', empty($opts['show_business_name'])) }}" @endif>{{ $business_name }}</div>
                <div class="meta brand" data-el="brand" @if($elPlaced('brand')) data-placed="1" @endif @if($elStyle('brand', empty($opts['show_brand']) || $brandText === '') !== '') style="{{ $elStyle('brand', empty($opts['show_brand']) || $brandText === '') }}" @endif>{{ $brandText }}</div>
                <div class="meta category" data-el="category" @if($elPlaced('category')) data-placed="1" @endif @if($elStyle('category', empty($opts['show_category']) || $categoryText === '') !== '') style="{{ $elStyle('category', empty($opts['show_category']) || $categoryText === '') }}" @endif>{{ $categoryText }}</div>
                <div class="pname" data-el="name" @if($elPlaced('name')) data-placed="1" @endif @if($elStyle('name', empty($opts['show_name'])) !== '') style="{{ $elStyle('name', empty($opts['show_name'])) }}" @endif>{{ $nameText }}</div>
                <div class="attr-line" @if(! $sizeOn && ! $colorOn) style="display:none" @endif>
                    <span class="label-size" data-el="size" @if($elPlaced('size')) data-placed="1" @endif @if($elStyle('size', ! $sizeOn) !== '') style="{{ $elStyle('size', ! $sizeOn) }}" @endif>@if($sizeText !== ''){{ __('product.size') }}: {{ $sizeText }}@endif</span>
                    <span class="label-color" data-el="color" @if($elPlaced('color')) data-placed="1" @endif @if($elStyle('color', ! $colorOn) !== '') style="{{ $elStyle('color', ! $colorOn) }}" @endif>@if($colorText !== ''){{ __('product.color') }}: {{ $colorText }}@endif</span>
                </div>
            </div>
            <div class="body">
                <div class="barcode-wrap" data-el="barcode" @if($elPlaced('barcode')) data-placed="1" @endif @if($elStyle('barcode', empty($opts['show_barcode']) || empty($sticker['barcode_png'])) !== '') style="{{ $elStyle('barcode', empty($opts['show_barcode']) || empty($sticker['barcode_png'])) }}" @endif>
                    @if(!empty($sticker['barcode_png']))
                        <img class="js-barcode" src="data:image/png;base64,{{ $sticker['barcode_png'] }}" alt="{{ $barcodeValue }}">
                    @endif
                </div>
                <div class="sku" data-el="sku" @if($elPlaced('sku')) data-placed="1" @endif @if($elStyle('sku', empty($opts['show_sku'])) !== '') style="{{ $elStyle('sku', empty($opts['show_sku'])) }}" @endif>{{ $skuText }}</div>
            </div>
            <div class="footer">
                <div class="cc" data-el="costcode" @if($elPlaced('costcode')) data-placed="1" @endif @if($costcodeCss !== '') style="{{ $costcodeCss }}" @endif>{{ $cc }}</div>
                <div class="price" data-el="price" @if($elPlaced('price')) data-placed="1" @endif @if($priceCss !== '') style="{{ $priceCss }}" @endif>{{ $priceText }}</div>
            </div>
        </div>
    </div>
</div>
