{{-- Loyalty customer finder — keyboard-first POS popup --}}
@php
    $cs = session('business.common_settings') ?? [];
    if (is_string($cs)) {
        $cs = json_decode($cs, true) ?? [];
    }
    $ask_mobile = !empty($cs['loyalty_ask_mobile_on_pos']);
    $shortcuts = session('business.keyboard_shortcuts');
    if (is_string($shortcuts)) {
        $shortcuts = json_decode($shortcuts, true) ?? [];
    }
    $loyalty_key = strtoupper($shortcuts['pos']['loyalty_customer'] ?? 'f10');
@endphp
@if(session('business.enable_rp') == 1 && $ask_mobile)
<input type="hidden" id="loyalty_ask_mobile_on_pos" value="1">
@endif
@if(session('business.enable_rp') == 1)
<input type="hidden" id="loyalty_customer_shortcut" value="{{ strtolower($shortcuts['pos']['loyalty_customer'] ?? 'f10') }}">
@endif

<div class="modal fade vspos-pos-modal" id="loyaltyCustomerModal" tabindex="-1" role="dialog" aria-labelledby="loyaltyCustomerModalLabel" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog vspos-loyalty-dialog" role="document">
        <div class="modal-content vspos-pos-modal-content vspos-loyalty-shell">
            <div class="modal-header vspos-pos-modal-header vspos-loyalty-head">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="loyaltyCustomerModalLabel">
                    <i class="fa fa-gift"></i>
                    {{ session('business.rp_name') ?: __('lang_v1.loyalty') }}
                    <span class="vspos-loyalty-head-sub">@lang('lang_v1.loyalty_find_customer')</span>
                </h4>
                <div class="vspos-loyalty-keys-hint">
                    <span><kbd>Enter</kbd> Search / Use</span>
                    <span><kbd>Esc</kbd> Skip</span>
                    <span><kbd>{{ $loyalty_key }}</kbd> Open</span>
                </div>
            </div>
            <div class="modal-body vspos-pos-modal-body vspos-loyalty-body">
                <div id="loyalty_lookup_step">
                    <label class="vspos-loyalty-label" for="loyalty_mobile_input">@lang('contact.mobile')</label>
                    <div class="vspos-loyalty-input-row">
                        <span class="vspos-loyalty-input-ico"><i class="fa fa-phone"></i></span>
                        <input type="text" id="loyalty_mobile_input" class="form-control vspos-loyalty-input mousetrap"
                            placeholder="@lang('lang_v1.enter_mobile_number')"
                            autocomplete="off" inputmode="tel" autofocus>
                    </div>
                    <p class="vspos-loyalty-help">@lang('lang_v1.loyalty_lookup_help')</p>
                    <div id="loyalty_lookup_result" class="hide"></div>
                </div>

                <div id="loyalty_create_step" class="hide">
                    <p class="vspos-loyalty-notfound">@lang('lang_v1.loyalty_customer_not_found')</p>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="loyalty_new_first_name">@lang('business.first_name') *</label>
                                <input type="text" id="loyalty_new_first_name" class="form-control mousetrap" required>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="loyalty_new_last_name">@lang('business.last_name')</label>
                                <input type="text" id="loyalty_new_last_name" class="form-control mousetrap">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="loyalty_new_mobile">@lang('contact.mobile') *</label>
                                <input type="text" id="loyalty_new_mobile" class="form-control mousetrap" required>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="loyalty_new_email">@lang('business.email')</label>
                                <input type="email" id="loyalty_new_email" class="form-control mousetrap">
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="loyalty_new_address">@lang('business.address')</label>
                                <input type="text" id="loyalty_new_address" class="form-control mousetrap">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer vspos-pos-modal-footer vspos-loyalty-foot">
                <button type="button" class="btn vspos-loyalty-btn vspos-loyalty-btn--ghost" data-dismiss="modal" id="loyalty_skip_btn">
                    @lang('lang_v1.loyalty_skip_walkin') <span class="keycap">Esc</span>
                </button>
                <button type="button" class="btn vspos-loyalty-btn vspos-loyalty-btn--primary hide" id="loyalty_create_btn">
                    <i class="fa fa-user-plus"></i> @lang('lang_v1.loyalty_add_customer') <span class="keycap">Enter</span>
                </button>
                <button type="button" class="btn vspos-loyalty-btn vspos-loyalty-btn--primary" id="loyalty_search_btn">
                    <i class="fa fa-search"></i> @lang('lang_v1.search') <span class="keycap">Enter</span>
                </button>
                <button type="button" class="btn vspos-loyalty-btn vspos-loyalty-btn--ok hide" id="loyalty_select_btn">
                    <i class="fa fa-check"></i> @lang('lang_v1.loyalty_use_customer') <span class="keycap">Enter</span>
                </button>
            </div>
        </div>
    </div>
</div>
<style>
.vspos-loyalty-dialog { width: min(440px, 94vw); margin: 8vh auto; }
.vspos-loyalty-shell {
    border-radius: 16px !important;
    overflow: hidden;
    border: 1px solid #2a2a2a;
    box-shadow: 0 24px 64px rgba(0,0,0,.55);
}
.vspos-loyalty-head {
    display: block !important;
    padding: 14px 16px 10px !important;
    border-bottom: 1px solid #2a2a2a !important;
}
.vspos-loyalty-head .modal-title {
    font-size: 16px;
    font-weight: 800;
    margin: 0 28px 8px 0;
}
.vspos-loyalty-head-sub {
    display: block;
    font-size: 12px;
    font-weight: 500;
    opacity: .7;
    margin-top: 2px;
}
.vspos-loyalty-keys-hint {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    font-size: 11px;
    opacity: .85;
}
.vspos-loyalty-keys-hint kbd,
.vspos-loyalty-foot .keycap {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid rgba(245,197,24,.45);
    background: rgba(245,197,24,.12);
    color: #f5c518;
    font-size: 10px;
    font-weight: 700;
    font-family: inherit;
    margin-left: 4px;
}
.vspos-loyalty-body { padding: 16px !important; }
.vspos-loyalty-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
    opacity: .75;
}
.vspos-loyalty-input-row {
    display: flex;
    align-items: stretch;
    border: 1px solid #333;
    border-radius: 10px;
    overflow: hidden;
    background: #0a0a0a;
}
.vspos-loyalty-input-ico {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    color: #f5c518;
    background: #141414;
    border-right: 1px solid #333;
}
.vspos-loyalty-input {
    border: 0 !important;
    box-shadow: none !important;
    height: 48px !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    letter-spacing: .04em;
    background: transparent !important;
    color: inherit !important;
}
.vspos-loyalty-help {
    margin: 8px 0 0;
    font-size: 12px;
    opacity: .65;
}
.vspos-loyalty-notfound {
    margin: 0 0 12px;
    font-size: 13px;
    color: #fbbf24;
}
.vspos-loyalty-foot {
    display: flex !important;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    padding: 12px 16px !important;
    border-top: 1px solid #2a2a2a !important;
}
.vspos-loyalty-btn {
    border-radius: 10px !important;
    font-weight: 700 !important;
    padding: 9px 14px !important;
    border: 1px solid transparent !important;
}
.vspos-loyalty-btn--ghost {
    background: #1a1a1a !important;
    border-color: #333 !important;
    color: #e5e5e5 !important;
}
.vspos-loyalty-btn--primary {
    background: #2563eb !important;
    color: #fff !important;
}
.vspos-loyalty-btn--ok {
    background: #16a34a !important;
    color: #fff !important;
}
#loyaltyCustomerModal .loyalty-found-card {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(22, 163, 74, 0.18);
    border: 1px solid rgba(74, 222, 128, 0.35);
    color: #bbf7d0;
}
#loyaltyCustomerModal .loyalty-found-name {
    font-size: 17px;
    font-weight: 800;
    color: #ecfdf5;
    margin-bottom: 4px;
}
#loyaltyCustomerModal .loyalty-found-meta,
#loyaltyCustomerModal .loyalty-found-points {
    color: #86efac;
    font-size: 13px;
    line-height: 1.45;
}
#loyaltyCustomerModal .loyalty-found-points .fa { margin-right: 4px; }

html[data-vspos-theme="light"] .vspos-loyalty-shell {
    border-color: #e5e7eb;
    background: #fff;
}
html[data-vspos-theme="light"] .vspos-loyalty-input-row {
    background: #fff;
    border-color: #d1d5db;
}
html[data-vspos-theme="light"] .vspos-loyalty-input-ico {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #b45309;
}
html[data-vspos-theme="light"] #loyaltyCustomerModal .loyalty-found-card {
    background: #e8f5e9;
    border-color: #a5d6a7;
    color: #1b5e20;
}
html[data-vspos-theme="light"] #loyaltyCustomerModal .loyalty-found-name { color: #0d3d12; }
html[data-vspos-theme="light"] #loyaltyCustomerModal .loyalty-found-meta,
html[data-vspos-theme="light"] #loyaltyCustomerModal .loyalty-found-points { color: #2e7d32; }

.vspos-loyalty-open-btn {
    display: inline-flex !important;
    align-items: center;
    gap: 4px;
    font-weight: 700;
}
.vspos-loyalty-open-btn .keycap {
    margin-left: 2px;
    padding: 0 5px;
    border-radius: 3px;
    background: rgba(245,197,24,.2);
    border: 1px solid rgba(245,197,24,.4);
    color: #f5c518;
    font-size: 10px;
    font-weight: 800;
}
</style>
