{{-- Tyre shop quick service amount modal --}}
<div class="modal fade" id="vspos_tyre_svc_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_tyre_svc_title">
    <div class="modal-dialog modal-sm" role="document" style="max-width:380px;">
        <div class="modal-content" style="background:#1a1a1a;color:#eee;border:1px solid #333;border-radius:10px;">
            <div class="modal-header" style="border-bottom:1px solid #333;padding:12px 16px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#aaa;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title" id="vspos_tyre_svc_title" style="color:#f5c518;font-weight:700;">Service</h4>
            </div>
            <div class="modal-body" style="padding:16px;">
                <input type="hidden" id="vspos_tyre_svc_key" value="">
                <input type="hidden" id="vspos_tyre_svc_variation_id" value="">
                <div class="form-group hide" id="vspos_tyre_svc_name_wrap">
                    <label for="vspos_tyre_svc_name" style="color:#ccc;">Name</label>
                    <input type="text" id="vspos_tyre_svc_name" class="form-control" placeholder="e.g. Wheel Polish" autocomplete="off"
                        style="background:#111;border-color:#333;color:#fff;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="vspos_tyre_svc_amount" style="color:#ccc;">Amount</label>
                    <input type="number" id="vspos_tyre_svc_amount" class="form-control input_number" placeholder="0.00" min="0" step="any" autocomplete="off"
                        style="background:#111;border-color:#333;color:#fff;font-size:18px;font-weight:700;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;padding:12px 16px;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="background:#2a2a2a;border-color:#444;color:#ddd;">Cancel</button>
                <button type="button" class="btn" id="vspos_tyre_svc_add" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                    Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Service bar button styles live in public/css/pos-touch.css
     (vehicle modification uses the same classes without this modal). --}}
