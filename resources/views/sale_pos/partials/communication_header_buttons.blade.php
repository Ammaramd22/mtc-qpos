{{-- POS header: Photocopy / Printout (classic POS only — touch UI uses category-rail buttons) --}}
@php
    $__comm_modules = $enabled_modules ?? session('business.enabled_modules', []);
    if (! is_array($__comm_modules)) {
        $__comm_modules = [];
    }
    $__pos_ui_now = $pos_ui ?? ($pos_settings['pos_ui'] ?? 'classic');
    $has_communication = in_array('communication', $__comm_modules, true)
        && (! function_exists('feature_enabled') || feature_enabled('communication'));
    $show_header_comm = $has_communication && empty($is_pop_layout) && $__pos_ui_now !== 'touch';
@endphp
@if($show_header_comm)
    <button type="button"
        class="pos-header-action vspos-header-comm-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        id="vspos_pos_photocopy_btn"
        title="@lang('lang_v1.photocopy')"
        onclick="if(typeof window.vsposOpenCommunication==='function'){window.vsposOpenCommunication('photocopy');}return false;">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5">
            <i class="fa fa-copy tw-text-[#0f766e]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">@lang('lang_v1.photocopy')</span>
        </strong>
    </button>
    <button type="button"
        class="pos-header-action vspos-header-comm-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        id="vspos_pos_printout_btn"
        title="@lang('lang_v1.printout')"
        onclick="if(typeof window.vsposOpenCommunication==='function'){window.vsposOpenCommunication('printout');}return false;">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5">
            <i class="fa fa-print tw-text-[#0369a1]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">@lang('lang_v1.printout')</span>
        </strong>
    </button>
    <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
@endif
