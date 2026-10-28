<!DOCTYPE html>
<html lang="en">
<head>
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
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
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
            flex-wrap: wrap;
            gap: 10px;
        }

        /* ===== Sticker ===== */
        .label {
            width: var(--label-w);
            height: var(--label-h);
            background: #fff;
            color: var(--ink);
            border: 1px dashed #64748b;
            padding: 0.9mm 1.3mm 1mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .label .head {
            flex: 0 0 auto;
            text-align: center;
            line-height: 1.05;
        }
        .label .biz {
            font-size: var(--biz-size);
            font-weight: 900;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label .pname {
            font-size: var(--name-size);
            font-weight: 800;
            color: #000;
            max-height: 2.2em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.1;
            margin-top: 0.7mm; /* sit a little below business name */
        }
        .label .body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 0;
            width: 100%;
        }
        /* Barcode position */
        .label.bc-top .body { justify-content: flex-start; }
        .label.bc-middle .body { justify-content: center; }
        .label.bc-bottom .body { justify-content: flex-end; padding-bottom: 0.4mm; }

        .label .barcode-wrap {
            width: var(--barcode-w);
            max-width: 100%;
            line-height: 0;
            flex: 0 0 auto;
        }
        .label .barcode-wrap img {
            width: 100%;
            height: var(--barcode-h);
            object-fit: fill;
            image-rendering: pixelated;
            filter: contrast(1.45) brightness(0.88);
            -webkit-filter: contrast(1.45) brightness(0.88);
        }
        .label .sku {
            font-size: var(--sku-size);
            font-weight: 800;
            color: #000;
            letter-spacing: .03em;
            line-height: 1.05;
            margin-top: 0.2mm;
        }

        /* Footer: Cost Code (left) · Price (right) — no divider line */
        .label .footer {
            flex: 0 0 auto;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.5mm;
            width: 100%;
            margin-top: 0.5mm;
            padding-top: 0;
            border-top: none;
            min-height: calc(var(--price-size) * 1.15px);
        }
        .label .footer .cc {
            flex: 0 1 42%;
            font-size: var(--costcode-size);
            font-weight: 900;
            color: #000;
            line-height: 1.05;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
        }
        .label .footer .price {
            flex: 0 1 55%;
            text-align: right;
            font-size: var(--price-size);
            font-weight: 900;
            color: #000;
            line-height: 1.05;
            white-space: nowrap;
            overflow: visible;
            letter-spacing: -0.01em;
        }

        @media print {
            body { background: #fff; }
            .toolbar, .no-print { display: none !important; }
            .sheet { padding: 0; gap: 0; display: block; }
            .label {
                border: none;
                margin: 0;
                page-break-after: always;
                break-after: page;
            }
            .label:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            .label .barcode-wrap img {
                filter: contrast(1.6) brightness(0.82);
                -webkit-filter: contrast(1.6) brightness(0.82);
            }
            @page {
                size: {{ $opts['label_w'] }}mm {{ $opts['label_h'] }}mm;
                margin: 0;
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
                </div>
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
                </div>
                <p style="margin:10px 0 0;font-size:11px;color:#94a3b8;">@lang('lang_v1.label_footer_fixed_help')</p>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn-apply">@lang('lang_v1.apply_rebuild_barcode')</button>
            <button type="button" class="btn-save" id="btn_save_layout">@lang('lang_v1.save_label_layout')</button>
            <button type="button" class="btn-print" onclick="window.print()">@lang('messages.print')</button>
            <button type="button" class="btn-close" onclick="window.close()">@lang('messages.close')</button>
            <span class="save-status" id="save_status"></span>
        </div>
    </form>

    <div class="sheet">
        @foreach($stickers as $sticker)
            @php
                $sym = trim((string) $currency_symbol);
                if ($sym === '') {
                    $sym = 'Rs';
                }
                // Proper currency display: "Rs 312.50" (space after symbol)
                $amount = number_format(
                    (float) $sticker['price'],
                    session('business.currency_precision', 2),
                    session('currency')['decimal_separator'] ?? '.',
                    session('currency')['thousand_separator'] ?? ','
                );
                $priceText = rtrim($sym) . ' ' . $amount;
                $cc = $sticker['costcode'] ?? '';
            @endphp
            <div class="label bc-{{ $bcPos }}">
                <div class="head">
                    @if($opts['show_business_name'])
                        <div class="biz">{{ $business_name }}</div>
                    @endif
                    @if($opts['show_name'])
                        <div class="pname">{{ $sticker['name'] }}</div>
                    @endif
                </div>

                <div class="body">
                    @if($opts['show_barcode'] && !empty($sticker['barcode_png']))
                        <div class="barcode-wrap">
                            <img src="data:image/png;base64,{{ $sticker['barcode_png'] }}" alt="{{ $sticker['sku'] }}">
                        </div>
                    @endif
                    @if($opts['show_sku'])
                        <div class="sku">{{ $sticker['sku'] }}</div>
                    @endif
                </div>

                <div class="footer">
                    <div class="cc">
                        @if($opts['show_costcode'] && $cc !== ''){{ $cc }}@endif
                    </div>
                    <div class="price">
                        @if($opts['show_price']){{ $priceText }}@endif
                    </div>
                </div>
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
            // Keep footer tall enough for large price
            if (v === '--price-size') {
                document.querySelectorAll('.label .footer').forEach(function (f) {
                    f.style.minHeight = (n * 1.15) + 'px';
                });
            }
        }
        document.querySelectorAll('.live-font').forEach(function (el) {
            el.addEventListener('input', function () { onFont(el); });
            onFont(el);
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
        if (lw) lw.addEventListener('input', function () {
            root.style.setProperty('--label-w', (parseFloat(lw.value) || 38) + 'mm');
        });
        if (lh) lh.addEventListener('input', function () {
            root.style.setProperty('--label-h', (parseFloat(lh.value) || 25) + 'mm');
        });

        document.querySelectorAll('.live-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var sel = cb.getAttribute('data-target');
                document.querySelectorAll('.label ' + sel).forEach(function (node) {
                    if (sel === '.cc' || sel === '.price') {
                        // keep cell, clear/show text via visibility
                        node.style.visibility = cb.checked ? 'visible' : 'hidden';
                    } else {
                        node.style.display = cb.checked ? '' : 'none';
                    }
                });
            });
        });

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
                label_business_name: 'opt_business_name'
            };
            Object.keys(forceIds).forEach(function (key) {
                var el = document.getElementById(forceIds[key]);
                if (el) data[key] = el.value;
            });
            ['show_business_name','show_name','show_barcode','show_sku','show_price','show_costcode'].forEach(function (k) {
                if (typeof data[k] === 'undefined') data[k] = '0';
            });
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
        }

        function saveLayout(showStatus) {
            var data = collectLayout();
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
