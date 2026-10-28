@php
    $labelLayout = !empty($common_settings['browser_label_layout']) && is_array($common_settings['browser_label_layout'])
        ? $common_settings['browser_label_layout']
        : [];
    $labelDefaults = [
        'label_business_name' => session('business.name', $business->name ?? ''),
        'label_w' => '38',
        'label_h' => '25',
        'business_name_size' => '8',
        'name_size' => '9',
        'sku_size' => '8',
        'price_size' => '12',
        'costcode_size' => '8',
        'barcode_width' => '94',
        'barcode_height' => '42',
        'barcode_bar_width' => '4',
        'barcode_position' => 'bottom',
        'price_type' => 'inclusive',
        'show_business_name' => '1',
        'show_name' => '1',
        'show_sku' => '1',
        'show_barcode' => '1',
        'show_price' => '1',
        'show_costcode' => '1',
    ];
    $labelLayout = array_merge($labelDefaults, $labelLayout);
@endphp

<div class="pos-tab-content" id="settings-bartender" data-settings-tab="bartender">
    {{-- BarTender is intentionally disabled; Browser Print is the supported label workflow. --}}
    <input type="hidden" name="common_settings[enable_bartender_export]" value="0">

    <div class="browser-label-settings">
        <div class="browser-label-header">
            <div>
                <h4><i class="fa fa-barcode"></i> @lang('lang_v1.browser_label_print_settings')</h4>
                <p>@lang('lang_v1.browser_label_print_settings_help')</p>
            </div>
            <span class="browser-label-badge">@lang('lang_v1.browser_print_only')</span>
        </div>

        <div class="browser-label-section">
            <h5>@lang('lang_v1.general_layout')</h5>
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.print_business_name')</label>
                        <input type="text" class="form-control" maxlength="80"
                            name="common_settings[browser_label_layout][label_business_name]"
                            value="{{ $labelLayout['label_business_name'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.label_width_mm')</label>
                        <input type="number" class="form-control" min="20" max="100" step="0.5"
                            name="common_settings[browser_label_layout][label_w]"
                            value="{{ $labelLayout['label_w'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.label_height_mm')</label>
                        <input type="number" class="form-control" min="15" max="80" step="0.5"
                            name="common_settings[browser_label_layout][label_h]"
                            value="{{ $labelLayout['label_h'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('business.business_name') @lang('lang_v1.font_size_px')</label>
                        <input type="number" class="form-control" min="6" max="28"
                            name="common_settings[browser_label_layout][business_name_size]"
                            value="{{ $labelLayout['business_name_size'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('product.product_name') @lang('lang_v1.font_size_px')</label>
                        <input type="number" class="form-control" min="6" max="32"
                            name="common_settings[browser_label_layout][name_size]"
                            value="{{ $labelLayout['name_size'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.price') @lang('lang_v1.font_size_px')</label>
                        <input type="number" class="form-control" min="6" max="36"
                            name="common_settings[browser_label_layout][price_size]"
                            value="{{ $labelLayout['price_size'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>SKU @lang('lang_v1.font_size_px')</label>
                        <input type="number" class="form-control" min="5" max="22"
                            name="common_settings[browser_label_layout][sku_size]"
                            value="{{ $labelLayout['sku_size'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.costcode') @lang('lang_v1.font_size_px')</label>
                        <input type="number" class="form-control" min="5" max="22"
                            name="common_settings[browser_label_layout][costcode_size]"
                            value="{{ $labelLayout['costcode_size'] }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.price') @lang('lang_v1.type')</label>
                        <select class="form-control" name="common_settings[browser_label_layout][price_type]">
                            <option value="inclusive" @if($labelLayout['price_type'] === 'inclusive') selected @endif>{{ __('product.inc_of_tax') }}</option>
                            <option value="exclusive" @if($labelLayout['price_type'] === 'exclusive') selected @endif>{{ __('product.exc_of_tax') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="browser-label-section">
            <h5>@lang('lang_v1.barcode_image_settings')</h5>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.barcode_width_pct')</label>
                        <input type="number" class="form-control" min="40" max="100"
                            name="common_settings[browser_label_layout][barcode_width]"
                            value="{{ $labelLayout['barcode_width'] }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.barcode_height_px')</label>
                        <input type="number" class="form-control" min="16" max="90"
                            name="common_settings[browser_label_layout][barcode_height]"
                            value="{{ $labelLayout['barcode_height'] }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.barcode_bar_thickness')</label>
                        <input type="number" class="form-control" min="1" max="5"
                            name="common_settings[browser_label_layout][barcode_bar_width]"
                            value="{{ $labelLayout['barcode_bar_width'] }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>@lang('lang_v1.barcode_position')</label>
                        <select class="form-control" name="common_settings[browser_label_layout][barcode_position]">
                            <option value="top" @if($labelLayout['barcode_position'] === 'top') selected @endif>@lang('lang_v1.barcode_pos_top')</option>
                            <option value="middle" @if($labelLayout['barcode_position'] === 'middle') selected @endif>@lang('lang_v1.barcode_pos_middle')</option>
                            <option value="bottom" @if($labelLayout['barcode_position'] === 'bottom') selected @endif>@lang('lang_v1.barcode_pos_bottom')</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="browser-label-section">
            <h5>@lang('lang_v1.display_elements')</h5>
            <p class="browser-label-section-help">@lang('lang_v1.display_elements_help')</p>
            <div class="browser-label-switch-grid">
                @php
                    $displayOptions = [
                        'show_business_name' => __('business.business_name'),
                        'show_name' => __('product.product_name'),
                        'show_barcode' => 'Barcode',
                        'show_sku' => 'SKU / '.__('lang_v1.barcode_text'),
                        'show_price' => __('lang_v1.price'),
                        'show_costcode' => __('lang_v1.costcode'),
                    ];
                @endphp
                @foreach($displayOptions as $option => $label)
                    <div class="browser-label-switch-item">
                        <span>{{ $label }}</span>
                        <label class="browser-label-switch">
                            <input type="hidden" name="common_settings[browser_label_layout][{{ $option }}]" value="0">
                            <input type="checkbox" name="common_settings[browser_label_layout][{{ $option }}]" value="1"
                                @if(!in_array((string) $labelLayout[$option], ['0', 'false', 'off', ''], true)) checked @endif>
                            <span class="browser-label-slider"></span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="browser-label-note">
            <i class="fa fa-info-circle"></i>
            @lang('lang_v1.browser_label_settings_save_note')
        </div>
    </div>
</div>

<style>
.browser-label-settings { color:#111827; }
.browser-label-header {
    display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
    padding:18px 20px; margin-bottom:18px; border:1px solid #dbeafe;
    border-radius:12px; background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 100%);
}
.browser-label-header h4 { margin:0 0 5px; font-size:19px; font-weight:800; color:#0f172a; }
.browser-label-header h4 i { color:#2563eb; margin-right:7px; }
.browser-label-header p { margin:0; color:#475569; font-size:13px; }
.browser-label-badge {
    flex:0 0 auto; padding:6px 10px; border-radius:999px; background:#dbeafe;
    color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em;
}
.browser-label-section { padding:2px 0 16px; margin-bottom:16px; border-bottom:1px solid #e5e7eb; }
.browser-label-section h5 {
    margin:0 0 14px; padding-bottom:8px; border-bottom:1px solid #e5e7eb;
    color:#0f172a; font-size:14px; font-weight:800;
}
.browser-label-section .form-group label { color:#334155; font-size:12px; font-weight:700; }
.browser-label-section .form-control {
    height:40px; border:1px solid #cbd5e1; border-radius:7px; color:#0f172a;
    background:#fff; font-weight:600; box-shadow:none;
}
.browser-label-section .form-control:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.browser-label-section-help { margin:-8px 0 14px; color:#64748b; font-size:12px; }
.browser-label-switch-grid { display:grid; grid-template-columns:repeat(3,minmax(150px,1fr)); gap:12px 18px; }
.browser-label-switch-item {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    min-height:48px; padding:10px 12px; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc;
    color:#1e293b; font-size:13px; font-weight:700;
}
.browser-label-switch { position:relative; display:inline-block; flex:0 0 auto; width:48px; height:26px; margin:0; }
.browser-label-switch input { opacity:0; width:0; height:0; }
.browser-label-slider {
    position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:26px; transition:.2s;
}
.browser-label-slider:before {
    position:absolute; content:""; width:20px; height:20px; left:3px; bottom:3px;
    background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(15,23,42,.3); transition:.2s;
}
.browser-label-switch input:checked + .browser-label-slider { background:#0ea5e9; }
.browser-label-switch input:checked + .browser-label-slider:before { transform:translateX(22px); }
.browser-label-switch input:focus + .browser-label-slider { box-shadow:0 0 0 3px rgba(14,165,233,.2); }
.browser-label-note {
    padding:12px 14px; border-radius:8px; background:#f0fdf4; border:1px solid #bbf7d0;
    color:#166534; font-size:12px; font-weight:600;
}
.browser-label-note i { margin-right:5px; }
@media (max-width:767px) {
    .browser-label-header { flex-direction:column; }
    .browser-label-switch-grid { grid-template-columns:1fr; }
}
</style>
