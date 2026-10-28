@php
    $show_vehicle_history = function_exists('vehicle_modification_enabled')
        && vehicle_modification_enabled()
        && auth()->check()
        && (
            (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())
            || auth()->user()->can('vehicle_modification.view_history')
        );
@endphp
@if($show_vehicle_history && empty($is_pop_layout))
    <button type="button"
        class="pos-header-action vspos-header-vehicle-history-btn tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-10 tw-w-auto tw-h-10 tw-text-gray-600 active:tw-scale-95 tw-transition-transform pull-right"
        data-toggle="modal" data-target="#pos_vehicle_history_modal"
        title="@lang('lang_v1.vehicle_history')">
        <strong class="!tw-m-3 tw-inline-flex tw-items-center tw-gap-1.5">
            <i class="fa fa-car tw-text-[#F59E0B]"></i>
            <span class="tw-hidden lg:tw-inline tw-text-xs tw-font-bold">@lang('lang_v1.vehicle_history')</span>
        </strong>
    </button>
    <span class="pos-nav-divider tw-inline-block tw-w-px tw-h-[18px] tw-bg-[#e2e8f0] tw-flex-shrink-0 tw-self-center tw-rounded-[1px] tw-mx-[3px]"></span>
@endif
