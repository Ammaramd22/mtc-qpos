{{-- Vehicle modification workshop services — amount modal (mirrors tyre shop pattern) --}}
<div class="modal fade" id="vspos_mod_svc_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document" style="max-width:380px;">
        <div class="modal-content" style="background:#1a1a1a;color:#eee;border:1px solid #333;border-radius:10px;">
            <div class="modal-header" style="border-bottom:1px solid #333;padding:12px 16px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#aaa;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title" id="vspos_mod_svc_title" style="color:#f5c518;font-weight:700;">Service</h4>
            </div>
            <div class="modal-body" style="padding:16px;">
                <input type="hidden" id="vspos_mod_svc_key" value="">
                <input type="hidden" id="vspos_mod_svc_variation_id" value="">
                <div class="form-group hide" id="vspos_mod_svc_name_wrap">
                    <label for="vspos_mod_svc_name" style="color:#ccc;">@lang('lang_v1.service')</label>
                    <input type="text" id="vspos_mod_svc_name" class="form-control" autocomplete="off"
                        style="background:#111;border-color:#333;color:#fff;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="vspos_mod_svc_amount" style="color:#ccc;">@lang('sale.unit_price')</label>
                    <input type="number" id="vspos_mod_svc_amount" class="form-control input_number" placeholder="0.00" min="0" step="any" autocomplete="off"
                        style="background:#111;border-color:#333;color:#fff;font-size:18px;font-weight:700;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;padding:12px 16px;">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn" id="vspos_mod_svc_add" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                    @lang('lang_v1.add_to_cart')
                </button>
            </div>
        </div>
    </div>
</div>
