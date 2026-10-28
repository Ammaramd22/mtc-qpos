{{-- Vehicle Modification POS — job history lookup (registration / phone) --}}
@php
    $show_vehicle_history = function_exists('vehicle_modification_enabled')
        && vehicle_modification_enabled()
        && auth()->check()
        && (
            (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())
            || auth()->user()->can('vehicle_modification.view_history')
        );
@endphp
@if($show_vehicle_history)
<div class="modal fade vspos-pos-modal" id="pos_vehicle_history_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="max-width:820px;">
        <div class="modal-content" style="background:#161616;color:#eee;border:1px solid #333;border-radius:12px;">
            <div class="modal-header" style="border-bottom:1px solid #333;padding:12px 16px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#aaa;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title" style="color:#f5c518;font-weight:700;">
                    <i class="fa fa-car"></i> @lang('lang_v1.vehicle_history')
                </h4>
            </div>
            <div class="modal-body" style="padding:14px 16px;max-height:70vh;overflow:auto;">
                <p class="help-block" style="margin-top:0;color:#aaa;">@lang('lang_v1.vehicle_history_help')</p>

                <div class="btn-group" style="margin-bottom:10px;">
                    <button type="button" class="btn btn-sm vspos-vh-type active" data-type="registration" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                        @lang('lang_v1.registration_number')
                    </button>
                    <button type="button" class="btn btn-sm vspos-vh-type" data-type="phone" style="background:#2a2a2a;border-color:#444;color:#ddd;">
                        @lang('contact.mobile')
                    </button>
                </div>

                <div class="input-group" style="margin-bottom:12px;">
                    <input type="text" id="vspos_vh_q" class="form-control" autocomplete="off"
                        placeholder="@lang('lang_v1.vehicle_history_search_placeholder')"
                        style="background:#111;border-color:#333;color:#fff;height:42px;font-size:16px;">
                    <span class="input-group-btn">
                        <button type="button" class="btn" id="vspos_vh_search_btn"
                            style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;height:42px;">
                            <i class="fa fa-search"></i> @lang('lang_v1.search')
                        </button>
                    </span>
                </div>

                <div id="vspos_vh_loading" class="text-center hide" style="padding:18px 0;"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                <div id="vspos_vh_empty" class="alert alert-info hide" style="margin:0 0 10px;"></div>

                <div id="vspos_vh_results" class="hide">
                    <h5 style="color:#f5c518;margin-top:0;">@lang('lang_v1.customer_vehicles')</h5>
                    <div id="vspos_vh_vehicle_list" class="list-group" style="margin-bottom:12px;"></div>
                </div>

                <div id="vspos_vh_jobs_wrap" class="hide">
                    <div class="clearfix" style="margin-bottom:8px;">
                        <strong id="vspos_vh_selected_vehicle" style="color:#fff;"></strong>
                        <button type="button" class="btn btn-xs btn-success pull-right" id="vspos_vh_use_vehicle">
                            <i class="fa fa-check"></i> @lang('lang_v1.use_vehicle_in_pos')
                        </button>
                    </div>
                    <div id="vspos_vh_job_list" class="list-group" style="margin-bottom:0;"></div>
                </div>

                <div id="vspos_vh_job_detail" class="hide" style="margin-top:12px;border-top:1px solid #333;padding-top:12px;">
                    <button type="button" class="btn btn-xs btn-default" id="vspos_vh_back_jobs" style="margin-bottom:8px;">
                        <i class="fa fa-arrow-left"></i> @lang('lang_v1.back')
                    </button>
                    <div id="vspos_vh_job_detail_body"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;padding:10px 16px;">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
<style>
#pos_vehicle_history_modal .list-group-item {
    background:#1f1f1f;border-color:#333;color:#eee;cursor:pointer;
}
#pos_vehicle_history_modal .list-group-item:hover,
#pos_vehicle_history_modal .list-group-item:focus {
    background:#2a2410;border-color:#f5c518;color:#fff;
}
#pos_vehicle_history_modal .vspos-vh-meta { color:#aaa;font-size:12px; }
#pos_vehicle_history_modal table.vspos-vh-items td,
#pos_vehicle_history_modal table.vspos-vh-items th {
    border-color:#333 !important;color:#ddd;
}
</style>
@endif
