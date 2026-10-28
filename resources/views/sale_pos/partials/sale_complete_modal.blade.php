<div class="vspos-sc-backdrop" id="vspos_sale_complete_backdrop" aria-hidden="true"></div>
<div class="vspos-sc-modal" id="vspos_sale_complete_modal" role="dialog" aria-labelledby="vspos_sc_title" aria-hidden="true">
    <div class="vspos-sc-card">
        <button type="button" class="vspos-sc-close" id="vspos_sc_close" aria-label="Close">
            <i class="fi fi-rr-cross pos-fi-icon"></i>
        </button>

        <div class="vspos-sc-icon-wrap">
            <div class="vspos-sc-icon"><i class="fi fi-rr-check pos-fi-icon"></i></div>
        </div>

        <div class="vspos-sc-title" id="vspos_sc_title">@lang('lang_v1.sc_sale_complete')</div>
        <div class="vspos-sc-customer" id="vspos_sc_customer_name"></div>
        <div class="vspos-sc-wish" id="vspos_sc_wish"></div>

        <div class="vspos-sc-mobile-block">
            <label class="vspos-sc-mobile-label" for="vspos_sc_mobile">
                <i class="fi fi-rr-smartphone pos-fi-icon"></i> @lang('lang_v1.sc_customer_mobile')
            </label>
            <input type="tel" id="vspos_sc_mobile" class="vspos-sc-mobile-input" readonly
                placeholder="@lang('lang_v1.cd_phone_placeholder')" inputmode="numeric">
            <p class="vspos-sc-mobile-hint" id="vspos_sc_mobile_hint">@lang('lang_v1.sc_tap_to_enter_mobile')</p>
        </div>

        <div class="vspos-sc-channels" id="vspos_sc_channels"></div>

        <div class="vspos-sc-actions">
            <button type="button" class="vspos-sc-btn vspos-sc-btn--ghost" id="vspos_sc_btn_close">
                @lang('messages.close')
            </button>
            <button type="button" class="vspos-sc-btn vspos-sc-btn--print" id="vspos_sc_btn_print">
                <i class="fi fi-rr-print pos-fi-icon"></i> @lang('lang_v1.sc_print_receipt')
            </button>
            <button type="button" class="vspos-sc-btn vspos-sc-btn--send" id="vspos_sc_btn_send">
                <i class="fi fi-rr-paper-plane pos-fi-icon"></i> @lang('lang_v1.sc_send_ebill')
            </button>
        </div>

        {{-- Mini numpad for mobile entry --}}
        <div class="vspos-sc-numpad" id="vspos_sc_numpad" aria-hidden="true">
        <div class="vspos-sc-numpad-head">
            <span>@lang('lang_v1.sc_enter_mobile')</span>
            <button type="button" class="vspos-sc-numpad-close" id="vspos_sc_numpad_close"><i class="fi fi-rr-cross pos-fi-icon"></i></button>
        </div>
        <div class="vspos-sc-numpad-display" id="vspos_sc_numpad_display">—</div>
        <div class="vspos-sc-numpad-grid">
            <button type="button" class="sc-nk" data-key="1">1</button>
            <button type="button" class="sc-nk" data-key="2">2</button>
            <button type="button" class="sc-nk" data-key="3">3</button>
            <button type="button" class="sc-nk" data-key="4">4</button>
            <button type="button" class="sc-nk" data-key="5">5</button>
            <button type="button" class="sc-nk" data-key="6">6</button>
            <button type="button" class="sc-nk" data-key="7">7</button>
            <button type="button" class="sc-nk" data-key="8">8</button>
            <button type="button" class="sc-nk" data-key="9">9</button>
            <button type="button" class="sc-nk sc-nk--action" data-key="back"><i class="fi fi-rr-delete pos-fi-icon"></i></button>
            <button type="button" class="sc-nk" data-key="0">0</button>
            <button type="button" class="sc-nk sc-nk--done" data-key="done"><i class="fi fi-rr-check pos-fi-icon"></i></button>
        </div>
        </div>
    </div>
</div>
