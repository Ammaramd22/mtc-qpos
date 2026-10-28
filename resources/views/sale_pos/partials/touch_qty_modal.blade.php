{{-- Touch POS/POP: quantity + rate keypad before add-to-cart --}}
@php
    $is_pop = !empty($is_pop) || request()->is('purchase-pos*');
    $rate_label = $is_pop ? __('product.default_purchase_price') : 'MRP';
@endphp
<div class="modal fade vspos-pos-modal {{ !empty($is_pop) ? 'vspos-touch-qty--purchase' : '' }}" id="vspos_touch_qty_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_touch_qty_title">
    <div class="modal-dialog vspos-touch-qty-dialog" role="document">
        <div class="modal-content vspos-touch-qty-content">
            <div class="modal-header vspos-touch-qty-header">
                <img id="vspos_touch_qty_image" class="vspos-touch-qty-photo" alt="" hidden>
                <div class="vspos-touch-qty-header-text">
                    <span class="vspos-touch-qty-mode-badge" id="vspos_touch_qty_mode_badge" @if(empty($is_pop)) style="display:none" @endif>Purchase</span>
                    <h4 class="modal-title" id="vspos_touch_qty_title">
                        @if(!empty($is_pop))
                            Qty &amp; Cost Price
                        @else
                            Enter Quantity &amp; MRP
                        @endif
                    </h4>
                    <p class="vspos-touch-qty-product" id="vspos_touch_qty_product">—</p>
                    <p class="vspos-touch-qty-meta">
                        <span id="vspos_touch_qty_sku"></span>
                        <span id="vspos_touch_qty_price"></span>
                    </p>
                    <p class="vspos-touch-qty-last-buy" id="vspos_touch_qty_last_buy" style="display:none;"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body vspos-touch-qty-body">
                <div class="vspos-touch-qty-tabs" role="tablist">
                    <button type="button" class="vspos-touch-qty-tab is-active" data-field="qty" id="vspos_touch_tab_qty">
                        Qty
                    </button>
                    <button type="button" class="vspos-touch-qty-tab" data-field="rate" id="vspos_touch_tab_rate">
                        <span id="vspos_touch_rate_tab_label">{{ $rate_label }}</span>
                    </button>
                </div>

                <div class="vspos-touch-qty-fields">
                    <div class="vspos-touch-qty-display-wrap is-active" data-field="qty" id="vspos_touch_qty_wrap">
                        <span class="vspos-touch-field-label">Qty</span>
                        <input type="text"
                            id="vspos_touch_qty_input"
                            class="vspos-touch-qty-display"
                            inputmode="decimal"
                            autocomplete="off"
                            spellcheck="false"
                            value="1"
                            placeholder="1">
                        <span class="vspos-touch-qty-unit" id="vspos_touch_qty_unit"></span>
                    </div>
                    <div class="vspos-touch-qty-display-wrap" data-field="rate" id="vspos_touch_rate_wrap">
                        <span class="vspos-touch-field-label" id="vspos_touch_rate_field_label">{{ $rate_label }}</span>
                        <input type="text"
                            id="vspos_touch_rate_input"
                            class="vspos-touch-qty-display vspos-touch-rate-display"
                            inputmode="decimal"
                            autocomplete="off"
                            spellcheck="false"
                            value=""
                            placeholder="0">
                    </div>
                </div>

                <div class="vspos-touch-qty-keypad" id="vspos_touch_qty_keypad" aria-label="Quantity and rate keypad">
                    <div class="vspos-touch-keypad-grid">
                        @foreach (['1','2','3','4','5','6','7','8','9','.','0','⌫'] as $key)
                            <button type="button"
                                class="vspos-touch-key {{ $key === '⌫' ? 'vspos-touch-key--back' : ($key === '.' ? 'vspos-touch-key--dot' : '') }}"
                                data-key="{{ $key === '⌫' ? 'back' : $key }}">
                                {{ $key }}
                            </button>
                        @endforeach
                    </div>
                    <div class="vspos-touch-keypad-row">
                        <button type="button" class="vspos-touch-key vspos-touch-key--clear" data-key="clear">C</button>
                        <button type="button" class="vspos-touch-key vspos-touch-key--exact" data-key="one" id="vspos_touch_key_one">Qty 1</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer vspos-touch-qty-footer">
                <button type="button" class="btn vspos-touch-qty-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn vspos-touch-qty-enter" id="vspos_touch_qty_enter">
                    Enter · Add
                </button>
            </div>
        </div>
    </div>
</div>
