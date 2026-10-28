{{-- Low stock alert dropdown in POS header --}}
<div class="vspos-stock-alert" id="vspos_stock_alert">
    <button type="button"
        class="pos-header-action vspos-stock-alert-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        id="vspos_stock_alert_btn"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="vspos_stock_alert_panel"
        title="{{ __('home.product_stock_alert') }}">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5 tw-relative">
            <i class="fas fa-exclamation-triangle tw-text-[#F59E0B]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">Stock</span>
            <span class="vspos-stock-alert-badge" id="vspos_stock_alert_badge" hidden>0</span>
        </strong>
    </button>
    <div class="vspos-stock-alert-panel" id="vspos_stock_alert_panel" hidden role="menu" aria-label="{{ __('home.product_stock_alert') }}">
        <div class="vspos-stock-alert-head">
            <span class="vspos-stock-alert-title">{{ __('home.product_stock_alert') }}</span>
            <button type="button" class="vspos-stock-alert-refresh" id="vspos_stock_alert_refresh" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="vspos-stock-alert-body" id="vspos_stock_alert_body">
            <div class="vspos-stock-alert-empty">Loading…</div>
        </div>
    </div>
</div>
<span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
