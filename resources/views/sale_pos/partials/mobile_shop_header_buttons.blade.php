@php
    $modules = $enabled_modules ?? session('business.enabled_modules', []);
    if (! is_array($modules)) {
        $modules = [];
    }
    $common = session('business.common_settings', []);
    if (is_string($common)) {
        $common = json_decode($common, true) ?: [];
    }
    if (! is_array($common)) {
        $common = [];
    }
    $reloadSettings = $common['reload'] ?? [];
    $has_mobile_shop = in_array('mobile_shop', $modules, true);
    // Show Reload in POS when the module is enabled OR this business has MyReload turned on.
    $has_reload = in_array('reload', $modules, true)
        || ! empty($reloadSettings['myreload_enabled']);
@endphp
@if(($has_mobile_shop || $has_reload) && empty($is_pop_layout))
    @if($has_reload)
    <button type="button"
        class="pos-header-action vspos-header-reload-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        data-toggle="modal" data-target="#pos_mobile_reload_modal"
        title="@lang('lang_v1.bill_payments')">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5">
            <i class="fa fa-bolt tw-text-[#8B5CF6]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">@lang('lang_v1.mobile_reload')</span>
        </strong>
    </button>
    @endif
    @if($has_mobile_shop)
    <button type="button"
        class="pos-header-action vspos-header-repair-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        data-toggle="modal" data-target="#pos_mobile_repair_payment_modal"
        title="@lang('lang_v1.mobile_repair_pos_select')">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5">
            <i class="fa fa-tools tw-text-[#00935F]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">@lang('lang_v1.mobile_repairs')</span>
        </strong>
    </button>
    @endif
    <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
@endif
