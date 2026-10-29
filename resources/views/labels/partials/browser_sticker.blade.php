@php
    $sym = trim((string) ($currency_symbol ?? ''));
    if ($sym === '') {
        $sym = 'Rs';
    }
    $amount = number_format(
        (float) ($sticker['price'] ?? 0),
        session('business.currency_precision', 2),
        session('currency')['decimal_separator'] ?? '.',
        session('currency')['thousand_separator'] ?? ','
    );
    $priceText = rtrim($sym).' '.$amount;
    $cc = $sticker['costcode'] ?? '';
    $rotClass = in_array((string) ($printRotate ?? '0'), ['0', '90', '180', '270'], true)
        ? (string) $printRotate
        : '0';
@endphp
<div class="label bc-{{ $bcPos }} rot-{{ $rotClass }}" data-sku="{{ $sticker['sku'] }}">
    <div class="label-shift">
        <div class="label-inner">
            <div class="head">
                @if($opts['show_business_name'])
                    <div class="biz">{{ $business_name }}</div>
                @endif
                @if($opts['show_brand'] && !empty($sticker['brand']))
                    <div class="meta brand">{{ $sticker['brand'] }}</div>
                @endif
                @if($opts['show_category'] && !empty($sticker['category']))
                    <div class="meta category">{{ $sticker['category'] }}</div>
                @endif
                @if($opts['show_name'])
                    <div class="pname">{{ $sticker['name'] }}</div>
                @endif
            </div>
            <div class="body">
                @if($opts['show_barcode'] && !empty($sticker['barcode_png']))
                    <div class="barcode-wrap">
                        <img class="js-barcode" src="data:image/png;base64,{{ $sticker['barcode_png'] }}" alt="{{ $sticker['sku'] }}">
                    </div>
                @endif
                @if($opts['show_sku'])
                    <div class="sku">{{ $sticker['sku'] }}</div>
                @endif
            </div>
            <div class="footer">
                <div class="cc">@if($opts['show_costcode'] && $cc !== ''){{ $cc }}@endif</div>
                <div class="price">@if($opts['show_price']){{ $priceText }}@endif</div>
            </div>
        </div>
    </div>
</div>
