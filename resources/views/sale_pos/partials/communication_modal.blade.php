{{-- Communication service modal — same shell as touch qty popup --}}
<div class="modal fade vspos-pos-modal" id="vspos_communication_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_communication_title">
    <div class="modal-dialog vspos-touch-qty-dialog vspos-comm-dialog" role="document">
        <div class="modal-content vspos-touch-qty-content vspos-comm-content">
            <div class="modal-header vspos-touch-qty-header vspos-comm-header">
                <div class="vspos-touch-qty-header-text">
                    <h4 class="modal-title" id="vspos_communication_title">
                        <span id="vspos_comm_title_text">@lang('lang_v1.photocopy')</span>
                    </h4>
                    <p class="vspos-touch-qty-product" id="vspos_comm_product_label">—</p>
                    <p class="vspos-touch-qty-meta">
                        <span id="vspos_comm_stock_hint">Stock: —</span>
                    </p>
                    <p class="vspos-touch-qty-meta" style="margin-top:2px;">
                        <span>@lang('lang_v1.avg_cost'):</span>
                        <span id="vspos_comm_avg_cost" style="color:#f5c518;font-weight:700;">—</span>
                    </p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body vspos-touch-qty-body vspos-comm-body">
                <input type="hidden" id="vspos_comm_service" value="photocopy">

                <div class="vspos-comm-field">
                    <span class="vspos-touch-field-label">@lang('lang_v1.paper')</span>
                    <select id="vspos_comm_paper" class="vspos-comm-select"></select>
                </div>

                <div class="vspos-comm-field" id="vspos_comm_color_wrap" style="display:none;">
                    <span class="vspos-touch-field-label">@lang('lang_v1.print_color_mode')</span>
                    <div class="vspos-comm-seg" role="group">
                        <button type="button" class="vspos-comm-seg-btn vspos-comm-color-btn is-active" data-color="bw">@lang('lang_v1.black_and_white')</button>
                        <button type="button" class="vspos-comm-seg-btn vspos-comm-color-btn" data-color="color">@lang('lang_v1.colour')</button>
                    </div>
                </div>

                <div class="vspos-comm-field" id="vspos_comm_bw_label_wrap">
                    <span class="vspos-touch-field-label">@lang('lang_v1.print_color_mode')</span>
                    <p class="vspos-comm-static">@lang('lang_v1.black_and_white')</p>
                </div>

                <div class="vspos-comm-field">
                    <span class="vspos-touch-field-label">@lang('lang_v1.sides')</span>
                    <div class="vspos-comm-seg" role="group">
                        <button type="button" class="vspos-comm-seg-btn vspos-comm-side-btn is-active" data-side="single">@lang('lang_v1.single_side')</button>
                        <button type="button" class="vspos-comm-seg-btn vspos-comm-side-btn" data-side="double">@lang('lang_v1.double_side')</button>
                    </div>
                </div>

                <div class="vspos-touch-qty-fields vspos-comm-qty-fields">
                    <div class="vspos-touch-qty-display-wrap is-active" data-field="qty" id="vspos_comm_qty_wrap">
                        <span class="vspos-touch-field-label">@lang('lang_v1.quantity')</span>
                        <input type="text"
                            id="vspos_comm_qty"
                            class="vspos-touch-qty-display"
                            inputmode="decimal"
                            autocomplete="off"
                            spellcheck="false"
                            value="1"
                            placeholder="1">
                    </div>
                    <div class="vspos-touch-qty-display-wrap" data-field="rate" id="vspos_comm_rate_wrap">
                        <span class="vspos-touch-field-label">@lang('sale.unit_price')</span>
                        <input type="text"
                            id="vspos_comm_price"
                            class="vspos-touch-qty-display vspos-touch-rate-display"
                            inputmode="decimal"
                            autocomplete="off"
                            spellcheck="false"
                            value=""
                            placeholder="0">
                    </div>
                </div>

                <div class="vspos-touch-qty-keypad" id="vspos_comm_keypad" aria-label="Quantity and price keypad">
                    <div class="vspos-touch-keypad-grid">
                        @foreach (['1','2','3','4','5','6','7','8','9','.','0','⌫'] as $key)
                            <button type="button"
                                class="vspos-touch-key {{ $key === '⌫' ? 'vspos-touch-key--back' : ($key === '.' ? 'vspos-touch-key--dot' : '') }}"
                                data-comm-key="{{ $key === '⌫' ? 'back' : $key }}">
                                {{ $key }}
                            </button>
                        @endforeach
                    </div>
                    <div class="vspos-touch-keypad-row">
                        <button type="button" class="vspos-touch-key vspos-touch-key--clear" data-comm-key="clear">C</button>
                        <button type="button" class="vspos-touch-key vspos-touch-key--exact" data-comm-key="one">Qty 1</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer vspos-touch-qty-footer vspos-comm-footer">
                <button type="button" class="btn vspos-touch-qty-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn vspos-touch-qty-enter" id="vspos_comm_add_btn">
                    Enter · Add
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Communication modal — works even when pos-touch.css is not loaded (classic POS) */
#vspos_communication_modal .vspos-comm-dialog { width: min(400px, 94vw) !important; max-width: calc(100vw - 24px) !important; margin: 12px auto !important; }
#vspos_communication_modal {
    z-index: 30000 !important;
}
body.modal-open #vspos_communication_modal {
    z-index: 30000 !important;
}
#vspos_communication_modal .vspos-comm-content { background: #111; border: 1px solid #2a2a2a; border-radius: 18px; overflow: hidden; }
#vspos_communication_modal .vspos-comm-header,
#vspos_communication_modal .vspos-comm-body,
#vspos_communication_modal .vspos-comm-footer { padding-left: 16px; padding-right: 16px; box-sizing: border-box; }
#vspos_communication_modal .vspos-comm-header { display: flex; align-items: flex-start; gap: 12px; border-bottom: 1px solid #222; padding-top: 14px; padding-bottom: 12px; }
#vspos_communication_modal .vspos-comm-header .modal-title { margin: 0; color: #a3a3a3; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
#vspos_communication_modal .vspos-touch-qty-product { margin: 4px 0 0; color: #fafafa; font-size: 1.05rem; font-weight: 800; }
#vspos_communication_modal .vspos-touch-qty-meta { margin: 4px 0 0; color: #737373; font-size: 12px; }
#vspos_communication_modal .vspos-comm-header .close { color: #a3a3a3; opacity: 1; text-shadow: none; font-size: 28px; font-weight: 300; }
#vspos_communication_modal .vspos-comm-body { padding-top: 12px; padding-bottom: 8px; }
#vspos_communication_modal .vspos-comm-field { margin: 0 0 12px; }
#vspos_communication_modal .vspos-touch-field-label { display: block; color: #737373; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
#vspos_communication_modal .vspos-comm-select { width: 100%; appearance: none; background: #0a0a0a; border: 1px solid #333; border-radius: 12px; color: #fafafa; font-size: 14px; font-weight: 700; min-height: 44px; padding: 8px 12px; }
#vspos_communication_modal .vspos-comm-static { margin: 0; color: #fafafa; font-size: 15px; font-weight: 800; }
#vspos_communication_modal .vspos-comm-seg { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
#vspos_communication_modal .vspos-comm-seg-btn { appearance: none; background: #0a0a0a; border: 1px solid #333; border-radius: 12px; color: #e5e5e5; font-size: 13px; font-weight: 700; min-height: 44px; padding: 8px 10px; cursor: pointer; }
#vspos_communication_modal .vspos-comm-seg-btn.is-active { border-color: #f5c518; box-shadow: inset 0 0 0 1px #f5c518; background: #141008; color: #fafafa; }
#vspos_communication_modal .vspos-comm-qty-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 0 0 12px; }
#vspos_communication_modal .vspos-touch-qty-display-wrap { display: flex; flex-direction: column; gap: 2px; padding: 8px 10px; min-height: 64px; background: #0a0a0a; border: 1px solid #333; border-radius: 12px; cursor: pointer; }
#vspos_communication_modal .vspos-touch-qty-display-wrap.is-active { border-color: #f5c518; box-shadow: inset 0 0 0 1px #f5c518; background: #141008; }
#vspos_communication_modal .vspos-touch-qty-display-wrap.is-active .vspos-touch-field-label { color: #f5c518; }
#vspos_communication_modal .vspos-touch-qty-display-wrap > input.vspos-touch-qty-display { width: 100% !important; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; color: #fafafa !important; font-size: 1.6rem; font-weight: 800; padding: 0 !important; margin: 0 !important; min-height: 32px; }
#vspos_communication_modal .vspos-touch-keypad-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
#vspos_communication_modal .vspos-touch-keypad-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 8px; }
#vspos_communication_modal .vspos-touch-key { appearance: none; background: #1a1a1a; border: 1px solid #333; border-radius: 12px; color: #fafafa; font-size: 1.2rem; font-weight: 700; min-height: 48px; width: 100%; cursor: pointer; }
#vspos_communication_modal .vspos-touch-key--clear { color: #f87171; }
#vspos_communication_modal .vspos-touch-key--exact { color: #f5c518; font-size: 14px; }
#vspos_communication_modal .vspos-comm-footer { display: flex; align-items: stretch; gap: 8px; border-top: 1px solid #222; padding-top: 12px; padding-bottom: 14px; margin: 0; }
#vspos_communication_modal .vspos-touch-qty-cancel { flex: 0 0 96px; background: #1a1a1a !important; border: 1px solid #333 !important; color: #e5e5e5 !important; border-radius: 12px !important; font-weight: 700 !important; height: 48px !important; margin: 0 !important; }
#vspos_communication_modal .vspos-touch-qty-enter { flex: 1 1 auto; background: #f5c518 !important; border: none !important; color: #111 !important; border-radius: 12px !important; font-weight: 800 !important; font-size: 15px !important; height: 48px !important; margin: 0 !important; }
</style>
