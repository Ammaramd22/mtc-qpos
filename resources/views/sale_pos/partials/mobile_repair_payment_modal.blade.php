<div class="modal fade vspos-pos-modal" id="pos_mobile_repair_payment_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content vspos-pos-modal-content">
            <div class="modal-header vspos-pos-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-tools"></i> @lang('lang_v1.mobile_repair_pos_select')</h4>
            </div>
            <div class="modal-body vspos-pos-modal-body">
                <p class="text-muted vspos-repair-pay-help">@lang('lang_v1.mobile_repair_pos_select_help')</p>
                <div id="pos_mobile_repair_pay_loading" class="text-center" style="padding:24px 0;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                </div>
                <div id="pos_mobile_repair_pay_empty" class="alert alert-info hide" style="margin:0;">
                    @lang('lang_v1.mobile_repair_pos_no_pending')
                </div>
                <div id="pos_mobile_repair_pay_list" class="vspos-repair-pay-list hide"></div>
            </div>
            <div class="modal-footer vspos-pos-modal-footer">
                <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairIndex']) }}" class="btn btn-default pull-left" target="_blank">
                    <i class="fa fa-external-link-alt"></i> @lang('lang_v1.mobile_repairs')
                </a>
                <button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
