<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        (function () {
            var theme = 'dark';
            try {
                var q = new URLSearchParams(window.location.search).get('theme');
                if (q === 'light' || q === 'dark') {
                    theme = q;
                } else {
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key && key.indexOf('vspos_theme_u_') === 0) {
                            var saved = localStorage.getItem(key);
                            if (saved === 'light' || saved === 'dark') {
                                theme = saved;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {}
            document.documentElement.setAttribute('data-vspos-theme', theme);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('barcode.print_labels') }} — {{ $opts['label_w'] }}×{{ $opts['label_h'] }}mm</title>
    <style>
        :root {
            --label-w: {{ $opts['label_w'] }}mm;
            --label-h: {{ $opts['label_h'] }}mm;
            --biz-size: {{ $opts['business_name_size'] }}px;
            --name-size: {{ $opts['name_size'] }}px;
            --sku-size: {{ $opts['sku_size'] }}px;
            --price-size: {{ $opts['price_size'] }}px;
            --costcode-size: {{ $opts['costcode_size'] }}px;
            --barcode-w: {{ $opts['barcode_width'] }}%;
            --barcode-h: {{ $opts['barcode_height'] }}px;
            --ink: #000;
            --pad-t: {{ $opts['margin_top'] }}mm;
            --pad-r: {{ $opts['margin_right'] }}mm;
            --pad-b: {{ $opts['margin_bottom'] }}mm;
            --pad-l: {{ $opts['margin_left'] }}mm;
            --shift-x: {{ $opts['offset_x'] }}mm;
            --shift-y: {{ $opts['offset_y'] }}mm;
            --quiet: {{ $opts['quiet_zone'] }}mm;
            --name-weight: {{ (int) $opts['name_weight'] }};
            --price-weight: {{ (int) $opts['price_weight'] }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "{{ $opts['font_family'] }}", Helvetica, sans-serif;
            background: #1e293b;
            color: #e2e8f0;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            padding: 14px 16px 16px;
        }
        .toolbar h1 {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
        }
        .hint {
            margin: 0 0 12px;
            font-size: 12px;
            color: #94a3b8;
        }
        .panel-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
        }
        .panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 10px 12px;
            min-width: 200px;
            flex: 1 1 220px;
        }
        .panel h2 {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #38bdf8;
        }
        .panel.fonts h2 { color: #fbbf24; }
        .panel.barcode h2 { color: #4ade80; }
        .panel.show h2 { color: #c084fc; }
        .panel.preview h2 { color: #f8fafc; }
        .live-preview-stage {
            background: #334155;
            padding: 12px;
            border-radius: 8px;
            display: block;
            overflow: hidden;
            max-width: 100%;
        }
        .live-preview-frame {
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }
        .live-preview-zoom {
            width: var(--label-w);
            height: var(--label-h);
            transform-origin: top left;
        }
        .print-orient-note {
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.35;
            color: #fde68a;
            max-width: 280px;
        }
        .live-preview-meta {
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.35;
            color: #e2e8f0;
            max-width: 280px;
        }
        .batch-title {
            margin: 0;
            padding: 14px 20px 0;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .fields {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 12px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 110px;
        }
        .field span {
            font-size: 12px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .field small {
            font-size: 10px;
            color: #94a3b8;
        }
        .field input[type="number"],
        .field input[type="text"],
        .field select {
            width: 100%;
            max-width: 130px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f8fafc;
            font-size: 14px;
            font-weight: 700;
        }
        .checks {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            align-items: center;
        }
        .checks label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #e2e8f0;
            cursor: pointer;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
        }
        .actions button {
            border: 0;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print { background: #22c55e; color: #052e16; }
        .btn-apply { background: #38bdf8; color: #0c4a6e; }
        .btn-save { background: #a78bfa; color: #1e1b4b; }
        .btn-close { background: #475569; color: #f8fafc; }
        .save-status {
            font-size: 12px;
            color: #86efac;
            margin-left: 4px;
        }
        .live-note {
            font-size: 11px;
            color: #86efac;
            margin-left: 4px;
        }
        .sheet {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .sheet-page {
            display: grid;
            grid-template-columns: repeat({{ (int) $pageBox['columns'] }}, var(--label-w));
            grid-auto-rows: var(--label-h);
            column-gap: var(--screen-gap-x, 10px);
            row-gap: var(--screen-gap-y, 10px);
            width: max-content;
        }

        /* ===== Sticker =====
           The dashed frame is one physical sticker: width across the roll, height along the feed.
           The print page uses those same millimetres. Rotation turns the artwork inside that box. */
        .label {
            width: var(--label-w);
            height: var(--label-h);
            max-width: var(--label-w);
            max-height: var(--label-h);
            background: #fff;
            color: var(--ink);
            border: 0;
            outline: 1px dashed #64748b;
            outline-offset: -1px;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .label-inner {
            width: 100%;
            height: 100%;
            padding: var(--pad-t) var(--pad-r) var(--pad-b) var(--pad-l);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-sizing: border-box;
            min-height: 0;
        }
        .label-shift {
            position: relative;
            width: 100%;
            height: 100%;
            transform: translate(var(--shift-x), var(--shift-y));
        }
        /* Rotation turns the artwork inside the fixed sticker. The page size stays width × height. */
        .label.rot-0 .label-inner,
        .label.rot-180 .label-inner {
            position: relative;
            width: 100%;
            height: 100%;
        }
        .label.rot-180 .label-inner {
            transform: rotate(180deg);
            transform-origin: center center;
        }
        .label.rot-90 .label-inner,
        .label.rot-270 .label-inner {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            max-height: 100%;
            transform-origin: center center;
        }
        .label.rot-90 .label-inner { transform: rotate(90deg) scale(calc(var(--label-h) / var(--label-w))); }
        .label.rot-270 .label-inner { transform: rotate(-90deg) scale(calc(var(--label-h) / var(--label-w))); }
        @media print {
            .label, .label-shift, .label-inner {
                border: 0 !important;
                outline: none !important;
                box-shadow: none !important;
            }
            .label.rot-0 .label-inner,
            .label.rot-90 .label-inner,
            .label.rot-180 .label-inner,
            .label.rot-270 .label-inner {
                position: absolute !important;
                inset: 0 !important;
                width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
            }
            .label.rot-0 .label-inner { transform: none !important; }
            .label.rot-180 .label-inner { transform: rotate(180deg) !important; transform-origin: center center !important; }
            .label.rot-90 .label-inner { transform: rotate(90deg) scale(calc(var(--label-h) / var(--label-w))) !important; transform-origin: center center !important; }
            .label.rot-270 .label-inner { transform: rotate(-90deg) scale(calc(var(--label-h) / var(--label-w))) !important; transform-origin: center center !important; }
        }
        .label .head {
            flex: 0 0 auto;
            text-align: {{ $opts['align'] }};
            line-height: 1.05;
        }
        .label .biz {
            font-size: var(--biz-size);
            font-weight: {{ (int) $opts['business_name_weight'] }};
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label .meta {
            font-size: var(--brand-size, 7px);
            font-weight: 700;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label .pname {
            font-size: var(--name-size);
            font-weight: var(--name-weight);
            color: #000;
            max-height: 2.2em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.1;
            margin-top: 0.7mm; /* sit a little below business name */
        }
        .label .attr-line {
            margin-top: 0.2mm;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            line-height: 1.05;
            font-size: var(--sku-size, 8px);
            font-weight: 700;
            color: #000;
            flex: 0 0 auto;
        }
        .label .attr-line .label-size,
        .label .attr-line .label-color {
            display: inline;
            font-size: inherit;
            font-weight: inherit;
            color: #000;
        }
        .label .attr-line .label-size + .label-color {
            margin-left: 1.2mm;
        }
        .label .attr-line .label-size[style*="display:none"] + .label-color,
        .label .attr-line .label-size[style*="display: none"] + .label-color {
            margin-left: 0;
        }
        .label .body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 0;
            width: 100%;
            overflow: hidden;
        }
        /* Barcode position */
        .label.bc-top .body { justify-content: flex-start; }
        .label.bc-middle .body { justify-content: center; }
        .label.bc-bottom .body { justify-content: flex-end; padding-bottom: 0.4mm; }

        .label .barcode-wrap {
            width: var(--barcode-w);
            max-width: 100%;
            max-height: var(--barcode-h);
            line-height: 0;
            flex: 1 1 0;
            min-height: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: var(--quiet);
            padding-right: var(--quiet);
            box-sizing: border-box;
        }
        .label .barcode-wrap img {
            display: block;
            width: auto;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            object-position: center;
            image-rendering: pixelated;
            filter: contrast(1.45) brightness(0.88);
            -webkit-filter: contrast(1.45) brightness(0.88);
        }
        .label .sku {
            flex: 0 0 auto;
            max-width: 100%;
            font-size: var(--sku-size);
            font-weight: 800;
            color: #000;
            letter-spacing: .03em;
            line-height: 1.05;
            margin-top: 0.2mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Footer: Cost Code (left) · Price (right) — no divider line */
        .label .footer {
            flex: 0 0 auto;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1mm;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
            margin-top: 0.3mm;
            padding-top: 0;
            border-top: none;
            min-height: 0;
        }
        .label .footer .cc {
            flex: 1 1 auto;
            min-width: 0;
            font-size: var(--costcode-size);
            font-weight: 900;
            color: #000;
            line-height: 1.05;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
        }
        html[data-vspos-theme="light"] body {
            background: #f1f5f9;
            color: #111827;
        }
        html[data-vspos-theme="light"] .toolbar,
        html[data-vspos-theme="light"] .panel {
            background: #ffffff;
            border-color: #e5e7eb;
            color: #111827;
        }
        html[data-vspos-theme="light"] .toolbar h1,
        html[data-vspos-theme="light"] .field span,
        html[data-vspos-theme="light"] .checks label,
        html[data-vspos-theme="light"] .panel h2,
        html[data-vspos-theme="light"] .panel.preview h2 { color: #111827; }
        html[data-vspos-theme="light"] .hint,
        html[data-vspos-theme="light"] .field small,
        html[data-vspos-theme="light"] .batch-title,
        html[data-vspos-theme="light"] .live-preview-meta { color: #4b5563; }
        html[data-vspos-theme="light"] .print-orient-note { color: #92400e; }
        html[data-vspos-theme="light"] .field input[type="number"],
        html[data-vspos-theme="light"] .field input[type="text"],
        html[data-vspos-theme="light"] .field select {
            background: #ffffff;
            color: #111827;
            border-color: #d1d5db;
        }
        html[data-vspos-theme="light"] .save-status,
        html[data-vspos-theme="light"] .live-note { color: #15803d; }
        .label .footer .price {
            flex: 0 1 auto;
            min-width: 0;
            max-width: 100%;
            text-align: right;
            font-size: var(--price-size);
            font-weight: var(--price-weight);
            color: #000;
            line-height: 1.05;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0;
        }
    </style>
    @php
        $printRotate = (string) ($pageBox['rotate'] ?? '0');
        $pageW = $pageBox['page_w'];
        $pageH = $pageBox['page_h'];
        $cols = (int) $pageBox['columns'];
        $gapX = $pageBox['gap_x'];
        $gapY = $pageBox['gap_y'];
    @endphp
    <style id="label-print-css">
        @page { size: {{ $pageW }}mm {{ $pageH }}mm; margin: 0; @if(($opts['media'] ?? 'roll') !== 'sheet' && (float) $pageW > (float) $pageH) page-orientation: rotate-right; @endif }
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                width: {{ $pageW }}mm !important;
                height: auto !important;
            }
            .toolbar, .no-print { display: none !important; }
            .sheet { display: block !important; padding: 0 !important; margin: 0 !important; gap: 0 !important; width: {{ $pageW }}mm !important; }
            .sheet-page {
                width: {{ $pageW }}mm !important;
                height: {{ $pageH }}mm !important;
                margin: 0 !important;
                padding: 0 !important;
                display: grid !important;
                grid-template-columns: repeat({{ $cols }}, {{ $pageBox['label_w'] }}mm);
                grid-auto-rows: {{ $pageBox['label_h'] }}mm;
                column-gap: {{ $gapX }}mm;
                row-gap: {{ $gapY }}mm;
                align-content: start;
                page-break-after: always;
                break-after: page;
                overflow: hidden !important;
            }
            .sheet-page:last-child { page-break-after: auto; break-after: auto; }
            .label {
                width: {{ $pageBox['label_w'] }}mm !important;
                height: {{ $pageBox['label_h'] }}mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                background: #fff !important;
                overflow: hidden !important;
                position: relative !important;
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .label-shift {
                width: 100%;
                height: 100%;
                transform: translate(var(--shift-x), var(--shift-y));
            }
            .label-inner {
                box-sizing: border-box !important;
                background: #fff !important;
                overflow: hidden !important;
                padding: var(--pad-t) var(--pad-r) var(--pad-b) var(--pad-l) !important;
            }
            .label .barcode-wrap img {
                filter: contrast(1.35) brightness(0.9);
                -webkit-filter: contrast(1.35) brightness(0.9);
            }
            .label img.label-shot {
                width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
                object-fit: fill;
            }
        }
    </style>
</head>
<body>
@php
    $bcPos = $opts['barcode_position'] ?? 'bottom';
    if (! in_array($bcPos, ['top', 'middle', 'bottom'], true)) {
        $bcPos = 'bottom';
    }
@endphp
    <form class="toolbar no-print" method="post" action="{{ url('/labels/browser-print') }}" id="browser_label_opts">
        @csrf
        <h1>@lang('lang_v1.browser_print_labels') — {{ count($stickers) }} @lang('barcode.labels')</h1>
        <p class="hint">@lang('lang_v1.browser_print_labels_hint_v4')</p>

        @if(!empty($printId))
            <input type="hidden" name="print_id" value="{{ $printId }}">
        @else
            @foreach(request('products', []) as $i => $p)
                <input type="hidden" name="products[{{ $i }}][product_id]" value="{{ $p['product_id'] ?? '' }}">
                <input type="hidden" name="products[{{ $i }}][variation_id]" value="{{ $p['variation_id'] ?? '' }}">
                <input type="hidden" name="products[{{ $i }}][quantity]" value="{{ $p['quantity'] ?? 1 }}">
                @if(!empty($p['lot_number']))
                    <input type="hidden" name="products[{{ $i }}][lot_number]" value="{{ $p['lot_number'] }}">
                @endif
                @if(!empty($p['exp_date']))
                    <input type="hidden" name="products[{{ $i }}][exp_date]" value="{{ $p['exp_date'] }}">
                @endif
            @endforeach
        @endif

        <div class="panel-row">
            <div class="panel preview">
                <h2>@lang('lang_v1.label_live_preview')</h2>
                    <div class="live-preview-stage">
                    <div class="live-preview-frame">
                    <div class="live-preview-zoom">
                        @include('labels.partials.browser_sticker', ['sticker' => $stickers[0]])
                    </div>
                    </div>
                </div>
                <p class="live-preview-meta" id="live_preview_meta"></p>
                <p class="print-orient-note" id="print_orient_note"></p>
            </div>
            <div class="panel">
                <h2>@lang('lang_v1.label_size')</h2>
                <div class="fields">
                    <label class="field">
                        <span>@lang('lang_v1.label_width_mm')</span>
                        <input type="number" name="label_w" id="opt_label_w" value="{{ $opts['label_w'] }}" min="20" max="100" step="0.5">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_height_mm')</span>
                        <input type="number" name="label_h" id="opt_label_h" value="{{ $opts['label_h'] }}" min="15" max="80" step="0.5">
                    </label>
                    <label class="field" style="min-width:180px;">
                        <span>@lang('lang_v1.label_print_rotation')</span>
                        <small>@lang('lang_v1.label_print_rotation_help')</small>
                        <select name="print_rotate" id="opt_print_rotate" style="max-width:220px;">
                            <option value="0" @if($printRotate === '0') selected @endif>@lang('lang_v1.label_rotate_0')</option>
                            <option value="90" @if($printRotate === '90') selected @endif>@lang('lang_v1.label_rotate_90')</option>
                            <option value="180" @if($printRotate === '180') selected @endif>@lang('lang_v1.label_rotate_180')</option>
                            <option value="270" @if($printRotate === '270') selected @endif>@lang('lang_v1.label_rotate_270')</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_media')</span>
                        <select name="media" id="opt_media">
                            <option value="roll" @if(($opts['media'] ?? 'roll') === 'roll') selected @endif>@lang('lang_v1.label_media_roll')</option>
                            <option value="sheet" @if(($opts['media'] ?? '') === 'sheet') selected @endif>@lang('lang_v1.label_media_sheet')</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_columns')</span>
                        <input type="number" name="columns" id="opt_columns" value="{{ $opts['columns'] }}" min="1" max="6">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_rows')</span>
                        <input type="number" name="rows" id="opt_rows" value="{{ $opts['rows'] }}" min="1" max="20">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_gap_x')</span>
                        <input type="number" name="gap_x" id="opt_gap_x" value="{{ $opts['gap_x'] }}" min="0" max="20" step="0.5">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_gap_y')</span>
                        <input type="number" name="gap_y" id="opt_gap_y" value="{{ $opts['gap_y'] }}" min="0" max="20" step="0.5">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_offset_x')</span>
                        <input type="number" name="offset_x" id="opt_offset_x" class="live-mm" data-var="--shift-x" value="{{ $opts['offset_x'] }}" min="-15" max="15" step="0.5">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_offset_y')</span>
                        <input type="number" name="offset_y" id="opt_offset_y" class="live-mm" data-var="--shift-y" value="{{ $opts['offset_y'] }}" min="-15" max="15" step="0.5">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_margin_top')</span>
                        <input type="number" name="margin_top" id="opt_margin_top" class="live-mm" data-var="--pad-t" value="{{ $opts['margin_top'] }}" min="0" max="8" step="0.1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_margin_right')</span>
                        <input type="number" name="margin_right" class="live-mm" data-var="--pad-r" value="{{ $opts['margin_right'] }}" min="0" max="8" step="0.1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_margin_bottom')</span>
                        <input type="number" name="margin_bottom" class="live-mm" data-var="--pad-b" value="{{ $opts['margin_bottom'] }}" min="0" max="8" step="0.1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_margin_left')</span>
                        <input type="number" name="margin_left" class="live-mm" data-var="--pad-l" value="{{ $opts['margin_left'] }}" min="0" max="8" step="0.1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_preset')</span>
                        <select id="opt_preset">
                            <option value="">@lang('lang_v1.label_preset')</option>
                            @foreach($presets as $presetKey => $preset)
                                <option value="{{ $presetKey }}">@lang('lang_v1.label_preset_'.$presetKey)</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @if($printRotate !== '0')
                    <p class="hint" style="color:#fde68a;">This layout is rotated {{ $printRotate }}°. Choose 0° — upright if the shop name should sit at the top. The preview shows the print.</p>
                @endif
            </div>

            <div class="panel fonts">
                <h2>@lang('lang_v1.font_sizes_px') <span class="live-note">(@lang('lang_v1.live_preview'))</span></h2>
                <div class="fields">
                    <label class="field" style="min-width:190px;">
                        <span>@lang('lang_v1.print_business_name')</span>
                        <input type="text" name="label_business_name" id="opt_business_name" value="{{ $business_name }}" maxlength="80" style="max-width:190px;">
                    </label>
                    <label class="field">
                        <span>@lang('business.business_name')</span>
                        <input type="number" name="business_name_size" class="live-font" data-var="--biz-size" value="{{ $opts['business_name_size'] }}" min="6" max="28">
                    </label>
                    <label class="field">
                        <span>@lang('product.product_name')</span>
                        <input type="number" name="name_size" class="live-font" data-var="--name-size" value="{{ $opts['name_size'] }}" min="6" max="32">
                    </label>
                    <label class="field">
                        <span>SKU</span>
                        <input type="number" name="sku_size" class="live-font" data-var="--sku-size" value="{{ $opts['sku_size'] }}" min="5" max="22">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.price')</span>
                        <input type="number" name="price_size" class="live-font" data-var="--price-size" value="{{ $opts['price_size'] }}" min="6" max="36">
                    </label>
                    <label class="field">
                        <span>{{ $costcodeLabel }}</span>
                        <input type="number" name="costcode_size" class="live-font" data-var="--costcode-size" value="{{ $opts['costcode_size'] }}" min="5" max="22">
                    </label>
                    <label class="field">
                        <span>@lang('product.product_name') weight</span>
                        <select name="name_weight" class="live-weight" data-var="--name-weight">
                            @foreach(['400' => 'Regular', '700' => 'Bold', '800' => 'Extra bold', '900' => 'Black'] as $w => $wLabel)
                                <option value="{{ $w }}" @if((string) $opts['name_weight'] === (string) $w) selected @endif>{{ $wLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.price') weight</span>
                        <select name="price_weight" class="live-weight" data-var="--price-weight">
                            @foreach(['400' => 'Regular', '700' => 'Bold', '800' => 'Extra bold', '900' => 'Black'] as $w => $wLabel)
                                <option value="{{ $w }}" @if((string) $opts['price_weight'] === (string) $w) selected @endif>{{ $wLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.price') @lang('lang_v1.type')</span>
                        <select name="price_type">
                            <option value="inclusive" @if($opts['price_type']==='inclusive') selected @endif>{{ __('product.inc_of_tax') }}</option>
                            <option value="exclusive" @if($opts['price_type']==='exclusive') selected @endif>{{ __('product.exc_of_tax') }}</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="panel barcode">
                <h2>Barcode</h2>
                <div class="fields">
                    <label class="field">
                        <span>@lang('lang_v1.barcode_format')</span>
                        <select name="barcode_format" id="opt_barcode_format">
                            <option value="auto" @if(($opts['barcode_format'] ?? 'auto') === 'auto') selected @endif>@lang('lang_v1.barcode_format_auto')</option>
                            @foreach(\App\Utils\BrowserLabelLayout::BARCODE_TYPES as $bcType)
                                <option value="{{ $bcType }}" @if(($opts['barcode_format'] ?? '') === $bcType) selected @endif>{{ $bcType }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_quiet_zone')</span>
                        <input type="number" name="quiet_zone" class="live-mm" data-var="--quiet" value="{{ $opts['quiet_zone'] }}" min="0" max="4" step="0.1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_font')</span>
                        <select name="font_family" id="opt_font">
                            @foreach(\App\Utils\BrowserLabelLayout::FONTS as $fontName)
                                <option value="{{ $fontName }}" @if(($opts['font_family'] ?? 'Arial') === $fontName) selected @endif>{{ $fontName }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.label_align')</span>
                        <select name="align" id="opt_align">
                            <option value="left" @if(($opts['align'] ?? '') === 'left') selected @endif>Left</option>
                            <option value="center" @if(($opts['align'] ?? 'center') === 'center') selected @endif>Center</option>
                            <option value="right" @if(($opts['align'] ?? '') === 'right') selected @endif>Right</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.barcode_position')</span>
                        <select name="barcode_position" id="opt_bc_pos">
                            <option value="top" @if($bcPos==='top') selected @endif>@lang('lang_v1.barcode_pos_top')</option>
                            <option value="middle" @if($bcPos==='middle') selected @endif>@lang('lang_v1.barcode_pos_middle')</option>
                            <option value="bottom" @if($bcPos==='bottom') selected @endif>@lang('lang_v1.barcode_pos_bottom')</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.barcode_width_pct')</span>
                        <input type="number" name="barcode_width" id="opt_bc_w" value="{{ $opts['barcode_width'] }}" min="40" max="100" step="1">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.barcode_height_px')</span>
                        <input type="number" name="barcode_height" id="opt_bc_h" value="{{ $opts['barcode_height'] }}" min="16" max="90">
                    </label>
                    <label class="field">
                        <span>@lang('lang_v1.barcode_bar_thickness')</span>
                        <small>1–5 (@lang('lang_v1.apply_to_rebuild'))</small>
                        <input type="number" name="barcode_bar_width" id="opt_bc_bar" value="{{ $opts['barcode_bar_width'] }}" min="1" max="5" step="1">
                    </label>
                </div>
            </div>

            <div class="panel show">
                <h2>@lang('lang_v1.show_on_label')</h2>
                <div class="checks">
                    <label><input type="hidden" name="show_business_name" value="0"><input type="checkbox" name="show_business_name" value="1" class="live-toggle" data-target=".biz" @if($opts['show_business_name']) checked @endif> @lang('business.business_name')</label>
                    <label><input type="hidden" name="show_name" value="0"><input type="checkbox" name="show_name" value="1" class="live-toggle" data-target=".pname" @if($opts['show_name']) checked @endif> @lang('product.product_name')</label>
                    <label><input type="hidden" name="show_barcode" value="0"><input type="checkbox" name="show_barcode" value="1" class="live-toggle" data-target=".barcode-wrap" @if($opts['show_barcode']) checked @endif> Barcode</label>
                    <label><input type="hidden" name="show_sku" value="0"><input type="checkbox" name="show_sku" value="1" class="live-toggle" data-target=".sku" @if($opts['show_sku']) checked @endif> SKU</label>
                    <label><input type="hidden" name="show_price" value="0"><input type="checkbox" name="show_price" value="1" class="live-toggle" data-target=".price" @if($opts['show_price']) checked @endif> @lang('lang_v1.price')</label>
                    <label><input type="hidden" name="show_costcode" value="0"><input type="checkbox" name="show_costcode" value="1" class="live-toggle" data-target=".cc" @if($opts['show_costcode']) checked @endif> {{ $costcodeLabel }}</label>
                    <label><input type="hidden" name="show_brand" value="0"><input type="checkbox" name="show_brand" value="1" class="live-toggle" data-target=".brand" @if($opts['show_brand']) checked @endif> @lang('product.brand')</label>
                    <label><input type="hidden" name="show_category" value="0"><input type="checkbox" name="show_category" value="1" class="live-toggle" data-target=".category" @if($opts['show_category']) checked @endif> @lang('product.category')</label>
                    <label><input type="hidden" name="show_size" value="0"><input type="checkbox" name="show_size" value="1" class="live-toggle" data-target=".label-size" @if(!empty($opts['show_size'])) checked @endif> @lang('product.size')</label>
                    <label><input type="hidden" name="show_color" value="0"><input type="checkbox" name="show_color" value="1" class="live-toggle" data-target=".label-color" @if(!empty($opts['show_color'])) checked @endif> @lang('product.color')</label>
                </div>
                <p style="margin:10px 0 0;font-size:11px;color:#94a3b8;">@lang('lang_v1.label_footer_fixed_help')</p>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn-apply">@lang('lang_v1.apply_rebuild_barcode')</button>
            <button type="button" class="btn-save" id="btn_save_layout">@lang('lang_v1.save_label_layout')</button>
            <label class="field" style="min-width:160px;">
                <span>@lang('lang_v1.label_profile')</span>
                <select id="opt_profile">
                    <option value="">—</option>
                    @foreach($profiles as $profile)
                        <option value="{{ $profile['id'] }}">{{ $profile['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field" style="min-width:140px;">
                <span>@lang('lang_v1.label_profile_name')</span>
                <input type="text" name="profile_name" id="opt_profile_name" maxlength="40" value="">
            </label>
            <input type="hidden" name="profile_id" id="opt_profile_id" value="">
            <input type="hidden" name="delete_profile_id" id="opt_delete_profile" value="">
            <button type="button" class="btn-save" id="btn_save_profile">@lang('lang_v1.label_save_profile')</button>
            <button type="button" class="btn-close" id="btn_delete_profile">@lang('lang_v1.label_delete_profile')</button>
            <button type="button" class="btn-close" id="btn_restore">@lang('lang_v1.label_restore_defaults')</button>
            <button type="button" class="btn-print" id="btn_print">@lang('messages.print')</button>
            <button type="button" class="btn-close" onclick="window.close()">@lang('messages.close')</button>
            <span class="save-status" id="save_status"></span>
        </div>
    </form>

    <h2 class="batch-title no-print">@lang('lang_v1.label_batch_preview')</h2>
    <div class="sheet">
        @foreach(array_chunk($stickers, max(1, (int) ($pageBox['per_page'] ?? 1))) as $pageStickers)
        <div class="sheet-page">
        @foreach($pageStickers as $sticker)
            @include('labels.partials.browser_sticker')
        @endforeach
        </div>
        @endforeach
    </div>

    <script>
    (function () {
        var STORAGE_KEY = 'qpos_browser_label_layout_v2';
        var root = document.documentElement;
        var form = document.getElementById('browser_label_opts');

        function onFont(el) {
            var v = el.getAttribute('data-var');
            var n = parseInt(el.value, 10);
            if (!v || isNaN(n)) return;
            root.style.setProperty(v, n + 'px');
        }
        document.querySelectorAll('.live-font').forEach(function (el) {
            el.addEventListener('input', function () { onFont(el); });
            onFont(el);
        });
        document.querySelectorAll('.live-weight').forEach(function (el) {
            function onWeight() {
                var v = el.getAttribute('data-var');
                if (v) root.style.setProperty(v, String(parseInt(el.value, 10) || 400));
            }
            el.addEventListener('change', onWeight);
            onWeight();
        });
        document.querySelectorAll('.live-mm').forEach(function (el) {
            function onMm() {
                var v = el.getAttribute('data-var');
                var n = parseFloat(el.value);
                if (!v || isNaN(n)) return;
                root.style.setProperty(v, n + 'mm');
            }
            el.addEventListener('input', onMm);
            onMm();
        });
        var fontEl = document.getElementById('opt_font');
        if (fontEl) fontEl.addEventListener('change', function () {
            document.body.style.fontFamily = fontEl.value + ', Helvetica, sans-serif';
        });
        var alignEl = document.getElementById('opt_align');
        if (alignEl) alignEl.addEventListener('change', function () {
            document.querySelectorAll('.label .head').forEach(function (node) {
                node.style.textAlign = alignEl.value;
            });
        });

        var bcW = document.getElementById('opt_bc_w');
        var bcH = document.getElementById('opt_bc_h');
        var bcPos = document.getElementById('opt_bc_pos');
        var businessName = document.getElementById('opt_business_name');
        if (businessName) businessName.addEventListener('input', function () {
            document.querySelectorAll('.label .biz').forEach(function (node) {
                node.textContent = businessName.value;
            });
        });
        if (bcW) bcW.addEventListener('input', function () {
            root.style.setProperty('--barcode-w', (parseInt(bcW.value, 10) || 94) + '%');
        });
        if (bcH) bcH.addEventListener('input', function () {
            root.style.setProperty('--barcode-h', (parseInt(bcH.value, 10) || 42) + 'px');
        });
        if (bcPos) bcPos.addEventListener('change', function () {
            document.querySelectorAll('.label').forEach(function (lab) {
                lab.classList.remove('bc-top', 'bc-middle', 'bc-bottom');
                lab.classList.add('bc-' + bcPos.value);
            });
        });

        var lw = document.getElementById('opt_label_w');
        var lh = document.getElementById('opt_label_h');
        var rotEl = document.getElementById('opt_print_rotate');

        function numVal(id, fallback) {
            var el = document.getElementById(id);
            var n = el ? parseFloat(el.value) : NaN;
            return isNaN(n) ? fallback : n;
        }

        function applyRotation(rot) {
            if (['0', '90', '180', '270'].indexOf(rot) < 0) rot = '0';
            document.querySelectorAll('.label').forEach(function (lab) {
                lab.classList.remove('rot-0', 'rot-90', 'rot-180', 'rot-270');
                lab.classList.add('rot-' + rot);
            });
            return rot;
        }

        function writePrintCss() {
            var w = parseFloat(lw && lw.value) || {{ (float) $opts['label_w'] }};
            var h = parseFloat(lh && lh.value) || {{ (float) $opts['label_h'] }};
            var rot = rotEl ? String(rotEl.value) : @json($printRotate);
            rot = applyRotation(rot);
            var mediaEl = document.getElementById('opt_media');
            var media = mediaEl ? mediaEl.value : 'roll';
            var cols = Math.max(1, Math.min(6, parseInt(numVal('opt_columns', 1), 10) || 1));
            var rows = Math.max(1, Math.min(20, parseInt(numVal('opt_rows', 1), 10) || 1));
            var gapX = Math.max(0, numVal('opt_gap_x', 0));
            var gapY = Math.max(0, numVal('opt_gap_y', 0));
            var pageW, pageH;
            if (media === 'sheet') {
                pageW = (cols * w) + (Math.max(0, cols - 1) * gapX);
                pageH = (rows * h) + (Math.max(0, rows - 1) * gapY);
            } else {
                cols = 1;
                gapX = 0;
                gapY = 0;
                pageW = w;
                pageH = h;
            }
            root.style.setProperty('--screen-gap-x', (media === 'sheet' ? gapX : 3) + 'mm');
            root.style.setProperty('--screen-gap-y', (media === 'sheet' ? gapY : 3) + 'mm');
            var pageWm = Math.round(pageW * 100) / 100;
            var pageHm = Math.round(pageH * 100) / 100;
            var meta = document.getElementById('live_preview_meta');
            if (meta) {
                meta.textContent = 'Sticker ' + w + ' × ' + h + ' mm. Content ' + rot + '°. One print page ' + pageWm + ' × ' + pageHm + ' mm. 0° puts the shop name at the top.';
            }
            var feedFix = media !== 'sheet' && pageW > pageH + 0.05;
            var note = document.getElementById('print_orient_note');
            if (note) {
                if (feedFix) {
                    note.textContent = 'Print window: Orientation Landscape, margins None, scale 100. The printer turns a wide sticker, so this page is turned ahead of it. One copy is one sticker.';
                } else if (pageH > pageW + 0.05) {
                    note.textContent = 'Print window: Orientation Portrait, paper ' + pageWm + ' × ' + pageHm + ' mm, margins None, scale 100.';
                } else {
                    note.textContent = 'Print window: paper ' + pageWm + ' × ' + pageHm + ' mm, margins None, scale 100.';
                }
            }
            var css = '@page{size:' + pageW + 'mm ' + pageH + 'mm;margin:0;' + (feedFix ? 'page-orientation:rotate-right;' : '') + '}'
                + '@media print{'
                + 'html,body{margin:0 !important;padding:0 !important;background:#fff !important;width:' + pageW + 'mm !important;height:auto !important;}'
                + '.toolbar,.no-print{display:none !important;}'
                + '.sheet{display:block !important;padding:0 !important;margin:0 !important;gap:0 !important;width:' + pageW + 'mm !important;}'
                + '.sheet-page{width:' + pageW + 'mm !important;height:' + pageH + 'mm !important;margin:0 !important;padding:0 !important;display:grid !important;grid-template-columns:repeat(' + cols + ',' + w + 'mm);grid-auto-rows:' + h + 'mm;column-gap:' + gapX + 'mm;row-gap:' + gapY + 'mm;align-content:start;page-break-after:always;break-after:page;overflow:hidden !important;}'
                + '.sheet-page:last-child{page-break-after:auto;break-after:auto;}'
                + '.label{width:' + w + 'mm !important;height:' + h + 'mm !important;max-height:' + h + 'mm !important;margin:0 !important;padding:0 !important;border:0 !important;outline:none !important;box-shadow:none !important;background:#fff !important;overflow:hidden !important;position:relative !important;page-break-after:auto !important;break-after:auto !important;}'
                + '.label-shift{position:relative;width:100%;height:100%;transform:translate(var(--shift-x),var(--shift-y));}'
                + '.label-inner{box-sizing:border-box !important;background:#fff !important;overflow:hidden !important;padding:var(--pad-t) var(--pad-r) var(--pad-b) var(--pad-l) !important;}'
                + '.label .barcode-wrap img{filter:contrast(1.35) brightness(0.9);-webkit-filter:contrast(1.35) brightness(0.9);}'
                + '}';
            var tag = document.getElementById('label-print-css');
            if (tag) tag.textContent = css;
            if (typeof scheduleShots === 'function') scheduleShots();
        }

        function fitPreview() {
            var frame = document.querySelector('.live-preview-frame');
            var zoom = document.querySelector('.live-preview-zoom');
            if (!frame || !zoom) return;
            var w = parseFloat(lw && lw.value) || 38;
            var h = parseFloat(lh && lh.value) || 25;
            var pxW = w * 96 / 25.4;
            var pxH = h * 96 / 25.4;
            var avail = frame.clientWidth;
            if (!avail || avail < 40) {
                avail = 240;
            }
            var scale = avail / pxW;
            if (scale > 3) scale = 3;
            if (scale < 0.4) scale = 0.4;
            zoom.style.transform = 'scale(' + scale + ')';
            frame.style.height = Math.ceil(pxH * scale) + 'px';
        }
        function syncLabelSize() {
            var w = parseFloat(lw && lw.value) || 38;
            var h = parseFloat(lh && lh.value) || 25;
            root.style.setProperty('--label-w', w + 'mm');
            root.style.setProperty('--label-h', h + 'mm');
            writePrintCss();
            fitPreview();
        }
        window.addEventListener('resize', fitPreview);
        if (lw) lw.addEventListener('input', syncLabelSize);
        if (lh) lh.addEventListener('input', syncLabelSize);
        if (rotEl) rotEl.addEventListener('change', writePrintCss);
        ['opt_media', 'opt_columns', 'opt_rows', 'opt_gap_x', 'opt_gap_y'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', writePrintCss);
            if (el) el.addEventListener('input', writePrintCss);
        });
        syncLabelSize();

        var printBtn = document.getElementById('btn_print');
        var shotCache = new WeakMap();
        var shotToken = 0;
        var shotTimer = null;
        var printingShots = false;

        function sheetLabels() {
            return Array.prototype.slice.call(document.querySelectorAll('.sheet .label'));
        }

        function currentRot() {
            var rot = rotEl ? String(rotEl.value) : @json($printRotate);
            return ['0', '90', '180', '270'].indexOf(rot) < 0 ? '0' : rot;
        }

        // 90° and 270° lay the artwork out in a box that is taller than the sticker
        // (the sticker width becomes the layout height) and only then rotate it.
        // Print pagination uses that layout height, so a 38 mm box on a 25 mm
        // sticker continues onto the next label. A bitmap is exactly one sticker.
        function rotationNeedsBitmap(rot) {
            return false;
        }

        function copyLabelVars(node) {
            var cs = getComputedStyle(root);
            ['--label-w','--label-h','--biz-size','--name-size','--sku-size','--price-size','--costcode-size','--barcode-w','--barcode-h','--pad-t','--pad-r','--pad-b','--pad-l','--shift-x','--shift-y','--quiet','--name-weight','--price-weight'].forEach(function (name) {
                var value = cs.getPropertyValue(name);
                if (value) node.style.setProperty(name, value.trim());
            });
        }

        function rasterizeLabel(label) {
            return new Promise(function (resolve) {
                var rect = label.getBoundingClientRect();
                var w = Math.max(1, Math.round(rect.width));
                var h = Math.max(1, Math.round(rect.height));
                var scale = 4;
                var css = '';
                document.querySelectorAll('head style').forEach(function (tag) {
                    if (tag.id === 'label-print-css') return;
                    css += tag.textContent || '';
                });
                css = css.replace(/<\/style/gi, '<\\/style');
                var clone = label.cloneNode(true);
                clone.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
                copyLabelVars(clone);
                clone.style.width = w + 'px';
                clone.style.height = h + 'px';
                clone.style.border = '0';
                clone.style.boxShadow = 'none';
                clone.style.margin = '0';
                var family = getComputedStyle(document.body).fontFamily || 'Arial, sans-serif';
                var xhtml = new XMLSerializer().serializeToString(clone);
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '"><foreignObject width="100%" height="100%"><div xmlns="http://www.w3.org/1999/xhtml" style="width:' + w + 'px;height:' + h + 'px;margin:0;background:#fff;overflow:hidden;font-family:' + family + '"><style><![CDATA[' + css + ']]></style>' + xhtml + '</div></foreignObject></svg>';
                var img = new Image();
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width = w * scale;
                    canvas.height = h * scale;
                    var ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.imageSmoothingEnabled = false;
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    resolve(canvas.toDataURL('image/png'));
                };
                img.onerror = function () { resolve(''); };
                img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
            });
        }

        function refreshShots() {
            var rot = currentRot();
            var token = ++shotToken;
            if (!rotationNeedsBitmap(rot) || printingShots) return Promise.resolve();
            var labels = sheetLabels();
            return Promise.all(labels.map(rasterizeLabel)).then(function (urls) {
                if (token !== shotToken || printingShots) return;
                labels.forEach(function (lab, i) { shotCache.set(lab, urls[i] || ''); });
            });
        }

        function scheduleShots() {
            clearTimeout(shotTimer);
            shotTimer = setTimeout(refreshShots, 200);
        }

        function applyShotsForPrint() {
            if (printingShots) return;
            var rot = currentRot();
            if (!rotationNeedsBitmap(rot)) return;
            var labels = sheetLabels();
            var ready = labels.length > 0 && labels.every(function (lab) { return !!shotCache.get(lab); });
            if (!ready) {
                labels.forEach(function (lab) {
                    var inner = lab.querySelector('.label-inner');
                    if (!inner) return;
                    inner.style.setProperty('width', '100%', 'important');
                    inner.style.setProperty('height', '100%', 'important');
                    inner.style.setProperty('max-height', '100%', 'important');
                    inner.style.setProperty('transform-origin', 'center center', 'important');
                    inner.style.setProperty('transform', rot === '270' ? 'rotate(-90deg)' : 'rotate(90deg)', 'important');
                });
                return;
            }
            printingShots = true;
            labels.forEach(function (lab) {
                lab.setAttribute('data-print-html', lab.innerHTML);
                lab.classList.remove('rot-90', 'rot-180', 'rot-270');
                lab.classList.add('rot-0', 'is-raster');
                lab.innerHTML = '<img class="label-shot" alt="" src="' + shotCache.get(lab) + '" style="width:100%;height:100%;max-height:100%;display:block;object-fit:fill;border:0;">';
            });
        }

        function restoreAfterPrint() {
            sheetLabels().forEach(function (lab) {
                var html = lab.getAttribute('data-print-html');
                if (html != null) {
                    lab.innerHTML = html;
                    lab.removeAttribute('data-print-html');
                    lab.classList.remove('is-raster');
                }
                var inner = lab.querySelector('.label-inner');
                if (inner) {
                    inner.style.width = '';
                    inner.style.height = '';
                    inner.style.maxHeight = '';
                    inner.style.transform = '';
                    inner.style.transformOrigin = '';
                }
            });
            printingShots = false;
            writePrintCss();
        }

        window.addEventListener('beforeprint', applyShotsForPrint);
        window.addEventListener('afterprint', restoreAfterPrint);

        if (printBtn) printBtn.addEventListener('click', function () {
            writePrintCss();
            refreshShots().then(function () {
                applyShotsForPrint();
                window.print();
            });
        });
        scheduleShots();

        var barcodeTimer = null;
        function refreshBarcodeImages() {
            var formatEl = document.getElementById('opt_barcode_format');
            var barEl = document.getElementById('opt_bc_bar');
            var heightEl = document.getElementById('opt_bc_h');
            var seen = {};
            document.querySelectorAll('.label[data-sku]').forEach(function (lab) {
                var sku = lab.getAttribute('data-sku') || '';
                if (!sku || seen[sku]) return;
                seen[sku] = true;
                var body = new URLSearchParams();
                body.append('sku', sku);
                body.append('barcode_format', formatEl ? formatEl.value : 'auto');
                body.append('barcode_bar_width', barEl ? barEl.value : '2');
                body.append('barcode_height', heightEl ? heightEl.value : '42');
                fetch(@json(url('/labels/browser-print/barcode')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: body.toString()
                }).then(function (response) {
                    return response.ok ? response.json() : null;
                }).then(function (result) {
                    if (!result || !result.success || !result.png) return;
                    document.querySelectorAll('.label[data-sku]').forEach(function (el) {
                        if (el.getAttribute('data-sku') !== sku) return;
                        var img = el.querySelector('.js-barcode');
                        if (img) img.src = 'data:image/png;base64,' + result.png;
                    });
                }).catch(function () {});
            });
        }
        function scheduleBarcodeRefresh() {
            clearTimeout(barcodeTimer);
            barcodeTimer = setTimeout(refreshBarcodeImages, 250);
        }
        ['opt_barcode_format', 'opt_bc_bar', 'opt_bc_h'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', scheduleBarcodeRefresh);
            el.addEventListener('input', scheduleBarcodeRefresh);
        });

        var attrCaptions = { size: @json(__('product.size')), color: @json(__('product.color')) };
        function ensureAttrSpan(kind, show) {
            var cls = kind === 'size' ? 'label-size' : 'label-color';
            var attr = kind === 'size' ? 'data-size' : 'data-color';
            document.querySelectorAll('.label').forEach(function (lab) {
                var value = (lab.getAttribute(attr) || '').replace(/^\s+|\s+$/g, '');
                var line = lab.querySelector('.attr-line');
                var node = lab.querySelector('.' + cls);
                if (!value) {
                    if (node) node.style.display = 'none';
                    return;
                }
                var head = lab.querySelector('.head');
                if (!line && head) {
                    line = document.createElement('div');
                    line.className = 'attr-line';
                    head.appendChild(line);
                }
                if (!node && line) {
                    node = document.createElement('span');
                    node.className = cls;
                    node.textContent = attrCaptions[kind] + ': ' + value;
                    if (kind === 'size') line.insertBefore(node, line.firstChild);
                    else line.appendChild(node);
                }
                if (node) node.style.display = show ? '' : 'none';
            });
        }
        function syncAttrLines() {
            document.querySelectorAll('.label .attr-line').forEach(function (line) {
                var any = false;
                line.querySelectorAll('.label-size, .label-color').forEach(function (node) {
                    if (node.style.display !== 'none') any = true;
                });
                line.style.display = any ? '' : 'none';
            });
        }
        document.querySelectorAll('.live-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var sel = cb.getAttribute('data-target');
                if (sel === '.label-size' || sel === '.label-color') {
                    ensureAttrSpan(sel === '.label-size' ? 'size' : 'color', cb.checked);
                    syncAttrLines();
                    return;
                }
                document.querySelectorAll('.label ' + sel).forEach(function (node) {
                    if (sel === '.cc' || sel === '.price') {
                        // keep cell, clear/show text via visibility
                        node.style.visibility = cb.checked ? 'visible' : 'hidden';
                    } else {
                        node.style.display = cb.checked ? '' : 'none';
                    }
                });
                syncAttrLines();
            });
        });
        syncAttrLines();

        function collectLayout() {
            var data = {};
            if (!form) return data;
            var fd = new FormData(form);
            fd.forEach(function (val, key) {
                if (key.indexOf('products[') === 0) return;
                data[key] = val;
            });
            // Force-read barcode fields from DOM (never miss these on Save).
            var forceIds = {
                barcode_width: 'opt_bc_w',
                barcode_height: 'opt_bc_h',
                barcode_bar_width: 'opt_bc_bar',
                barcode_position: 'opt_bc_pos',
                label_w: 'opt_label_w',
                label_h: 'opt_label_h',
                print_rotate: 'opt_print_rotate',
                label_business_name: 'opt_business_name'
            };
            Object.keys(forceIds).forEach(function (key) {
                var el = document.getElementById(forceIds[key]);
                if (el) data[key] = el.value;
            });
            ['show_business_name','show_name','show_barcode','show_sku','show_price','show_costcode','show_brand','show_category','show_size','show_color'].forEach(function (k) {
                if (typeof data[k] === 'undefined') data[k] = '0';
            });
            delete data.profile_name;
            delete data.profile_id;
            delete data.delete_profile_id;
            return data;
        }

        function csrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            return @json(csrf_token());
        }

        function setSaveStatus(message, isError) {
            var el = document.getElementById('save_status');
            if (!el) return;
            el.textContent = message || '';
            el.style.color = isError ? '#fca5a5' : '#86efac';
        }

        function applyLayoutToForm(layout) {
            if (!layout) return;
            var map = {
                barcode_width: 'opt_bc_w',
                barcode_height: 'opt_bc_h',
                barcode_bar_width: 'opt_bc_bar',
                barcode_position: 'opt_bc_pos',
                label_w: 'opt_label_w',
                label_h: 'opt_label_h',
                print_rotate: 'opt_print_rotate',
                label_business_name: 'opt_business_name'
            };
            Object.keys(map).forEach(function (key) {
                if (typeof layout[key] === 'undefined' || layout[key] === null) return;
                var el = document.getElementById(map[key]);
                if (el) el.value = layout[key];
            });
            if (bcW) root.style.setProperty('--barcode-w', (parseInt(layout.barcode_width, 10) || 94) + '%');
            if (bcH) root.style.setProperty('--barcode-h', (parseInt(layout.barcode_height, 10) || 42) + 'px');
            if (bcPos && layout.barcode_position) {
                document.querySelectorAll('.label').forEach(function (lab) {
                    lab.classList.remove('bc-top', 'bc-middle', 'bc-bottom');
                    lab.classList.add('bc-' + layout.barcode_position);
                });
                bcPos.value = layout.barcode_position;
            }
            syncLabelSize();
        }

        var PRESETS = @json($presets);
        var PROFILES = @json($profiles);
        var DEFAULTS = @json(\App\Utils\BrowserLabelLayout::defaults($business_name));

        function setNamed(key, value) {
            if (!form) return;
            var nodes = form.querySelectorAll('[name="' + key + '"]');
            nodes.forEach(function (node) {
                if (node.type === 'checkbox') {
                    var on = value === true || value === 1 || value === '1';
                    node.checked = on;
                    node.dispatchEvent(new Event('change'));
                } else if (node.type === 'hidden') {
                    return;
                } else if (value !== null && typeof value !== 'undefined') {
                    node.value = value;
                    node.dispatchEvent(new Event('input', { bubbles: true }));
                    node.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        var presetEl = document.getElementById('opt_preset');
        if (presetEl) presetEl.addEventListener('change', function () {
            var preset = PRESETS[presetEl.value];
            if (!preset) return;
            Object.keys(preset).forEach(function (key) { setNamed(key, preset[key]); });
            syncLabelSize();
        });

        var profileEl = document.getElementById('opt_profile');
        if (profileEl) profileEl.addEventListener('change', function () {
            var id = profileEl.value;
            var idField = document.getElementById('opt_profile_id');
            if (idField) idField.value = id;
            var found = null;
            PROFILES.forEach(function (profile) {
                if (profile.id === id) found = profile;
            });
            if (!found) return;
            var nameField = document.getElementById('opt_profile_name');
            if (nameField) nameField.value = found.name || '';
            Object.keys(found.layout || {}).forEach(function (key) { setNamed(key, found.layout[key]); });
            syncLabelSize();
        });

        function saveLayout(showStatus, extra) {
            var data = collectLayout();
            if (extra) {
                Object.keys(extra).forEach(function (key) { data[key] = extra[key]; });
            }
            if (showStatus !== false) {
                setSaveStatus(@json(__('lang_v1.saving_label_layout')), false);
            }

            // Prefer form-urlencoded — more reliable than JSON on some cPanel hosts.
            var body = new URLSearchParams();
            Object.keys(data).forEach(function (key) {
                body.append(key, data[key] == null ? '' : String(data[key]));
            });

            return fetch(@json(url('/labels/browser-print/layout')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: body.toString()
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            }).then(function (result) {
                if (!result || !result.success) {
                    throw new Error((result && result.msg) || 'Save failed');
                }
                var layout = result.layout || data;
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(layout));
                } catch (e) {}
                window.qposBrowserLabelLayout = layout;
                try {
                    if (window.opener && !window.opener.closed) {
                        window.opener.qposBrowserLabelLayout = layout;
                    }
                } catch (e) {}
                applyLayoutToForm(layout);
                if (showStatus !== false) {
                    var msg = result.msg || @json(__('lang_v1.label_layout_saved_server'));
                    if (layout.barcode_width || layout.barcode_height) {
                        msg += ' (W ' + (layout.barcode_width || '') + '% / H ' + (layout.barcode_height || '') + 'px)';
                    }
                    setSaveStatus(msg, false);
                }
                return result;
            }).catch(function (error) {
                if (showStatus !== false) {
                    setSaveStatus(@json(__('lang_v1.label_layout_save_failed')) + (error && error.message ? (' — ' + error.message) : ''), true);
                }
                throw error;
            });
        }

        var saveProfileBtn = document.getElementById('btn_save_profile');
        if (saveProfileBtn) saveProfileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var nameField = document.getElementById('opt_profile_name');
            var name = nameField ? nameField.value.trim() : '';
            if (!name) {
                setSaveStatus('Enter a profile name.', true);
                return;
            }
            saveProfileBtn.disabled = true;
            saveLayout(true, {
                profile_name: name,
                profile_id: (document.getElementById('opt_profile') || {}).value || ''
            }).then(function () {
                HTMLFormElement.prototype.submit.call(form);
            }).catch(function () {
                saveProfileBtn.disabled = false;
            });
        });

        var deleteProfileBtn = document.getElementById('btn_delete_profile');
        if (deleteProfileBtn) deleteProfileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var id = (document.getElementById('opt_profile') || {}).value || '';
            if (!id) return;
            deleteProfileBtn.disabled = true;
            saveLayout(true, { delete_profile_id: id }).then(function () {
                HTMLFormElement.prototype.submit.call(form);
            }).catch(function () {
                deleteProfileBtn.disabled = false;
            });
        });

        var restoreBtn = document.getElementById('btn_restore');
        if (restoreBtn) restoreBtn.addEventListener('click', function (e) {
            e.preventDefault();
            Object.keys(DEFAULTS).forEach(function (key) { setNamed(key, DEFAULTS[key]); });
            syncLabelSize();
            restoreBtn.disabled = true;
            saveLayout(true).then(function () {
                HTMLFormElement.prototype.submit.call(form);
            }).catch(function () {
                restoreBtn.disabled = false;
            });
        });

        var saveBtn = document.getElementById('btn_save_layout');
        if (saveBtn) saveBtn.addEventListener('click', function (e) {
            e.preventDefault();
            saveBtn.disabled = true;
            // Save then rebuild so barcode PNG uses the new height/thickness.
            saveLayout(true).then(function () {
                setSaveStatus(@json(__('lang_v1.label_layout_saved_server')) + ' — rebuilding…', false);
                HTMLFormElement.prototype.submit.call(form);
            }).catch(function () {
                saveBtn.disabled = false;
            });
        });

        // Save to the business account before rebuilding the barcode.
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                saveLayout(false).then(function () {
                    HTMLFormElement.prototype.submit.call(form);
                }).catch(function () {
                    // Still rebuild preview locally, but warn that server save failed.
                    setSaveStatus(@json(__('lang_v1.label_layout_save_failed')), true);
                    setTimeout(function () {
                        HTMLFormElement.prototype.submit.call(form);
                    }, 600);
                });
            });
        }
    })();
    </script>
    @if(!empty($opts['auto_print']))
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
    @endif
</body>
</html>
