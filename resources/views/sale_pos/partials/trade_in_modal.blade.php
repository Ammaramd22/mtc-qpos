{{-- POS Exchange / Trade-In modal --}}
<div class="modal fade" id="vspos_tradein_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_tradein_title">
    <div class="modal-dialog" role="document" style="max-width:560px;">
        <div class="modal-content" style="background:#1a1a1a;color:#eee;border:1px solid #333;border-radius:10px;">
            <div class="modal-header" style="border-bottom:1px solid #333;padding:12px 16px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#aaa;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title" id="vspos_tradein_title" style="color:#f5c518;font-weight:700;">
                    <i class="fas fa-exchange-alt"></i> Exchange / Trade-In
                </h4>
            </div>
            <div class="modal-body" style="padding:16px;">
                <input type="hidden" id="vspos_tradein_row" value="">
                <p class="text-muted" style="color:#999;margin-top:0;font-size:12px;">
                    New item: <strong id="vspos_tradein_new_name" style="color:#f5c518;"></strong>
                </p>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label style="color:#ccc;">Old Item Name</label>
                            <input type="text" id="vspos_ti_item_name" class="form-control" placeholder="e.g. CEAT 215/55R17 Used"
                                style="background:#111;border-color:#333;color:#fff;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Tyre Size</label>
                            <input type="text" id="vspos_ti_tyre_size" class="form-control" placeholder="215/55R17"
                                style="background:#111;border-color:#333;color:#fff;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Brand</label>
                            <select id="vspos_ti_brand_id" class="form-control select2" style="width:100%;background:#111;border-color:#333;color:#fff;">
                                <option value="">— Select / type below —</option>
                            </select>
                            <input type="text" id="vspos_ti_brand_name" class="form-control" placeholder="Or type brand name"
                                style="background:#111;border-color:#333;color:#fff;margin-top:6px;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Category</label>
                            <select id="vspos_ti_category_id" class="form-control" style="background:#111;border-color:#333;color:#fff;"></select>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Subcategory</label>
                            <select id="vspos_ti_sub_category_id" class="form-control" style="background:#111;border-color:#333;color:#fff;">
                                <option value="">— None —</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Condition</label>
                            <select id="vspos_ti_condition" class="form-control" style="background:#111;border-color:#333;color:#fff;"></select>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Approx. usage %</label>
                            <input type="number" id="vspos_ti_usage" class="form-control input_number" min="0" max="100" step="1" placeholder="60"
                                style="background:#111;border-color:#333;color:#fff;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="color:#ccc;">Barcode / SKU <small>(optional)</small></label>
                            <input type="text" id="vspos_ti_barcode" class="form-control" style="background:#111;border-color:#333;color:#fff;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label style="color:#ccc;">Qty</label>
                            <input type="number" id="vspos_ti_qty" class="form-control input_number" min="0.01" step="any" value="1"
                                style="background:#111;border-color:#333;color:#fff;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label style="color:#ccc;">Exchange Value <span style="color:#f5c518;">*</span></label>
                            <input type="number" id="vspos_ti_value" class="form-control input_number" min="0" step="any" placeholder="0.00"
                                style="background:#111;border-color:#333;color:#fff;font-weight:700;font-size:16px;" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="color:#ccc;">Notes / Reason</label>
                            <textarea id="vspos_ti_notes" class="form-control" rows="2" placeholder="Customer trade-in / warranty exchange"
                                style="background:#111;border-color:#333;color:#fff;"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;padding:12px 16px;">
                <button type="button" class="btn btn-default" id="vspos_ti_clear" style="background:#2a2a2a;border-color:#444;color:#f87171;float:left;">
                    Remove Exchange
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal" style="background:#2a2a2a;border-color:#444;color:#ddd;">Cancel</button>
                <button type="button" class="btn" id="vspos_ti_add" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                    Add Exchange
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.vspos-tradein-badge {
    display: block;
    margin-top: 6px;
    padding: 6px 8px;
    border-radius: 6px;
    background: rgba(245, 197, 24, 0.12);
    border: 1px solid rgba(245, 197, 24, 0.35);
    color: #f5c518;
    font-size: 11px;
    line-height: 1.35;
}
.vspos-tradein-badge .vspos-ti-val {
    color: #f87171;
    font-weight: 700;
}
.vspos-line-exchange {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 4px;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid rgba(245, 197, 24, 0.45);
    background: rgba(245, 197, 24, 0.12);
    color: #f5c518;
    cursor: pointer;
    padding: 0;
}
.vspos-line-exchange:hover {
    background: rgba(245, 197, 24, 0.28);
    color: #fff;
}
.vspos-card--tradein {
    border-color: rgba(245, 197, 24, 0.4) !important;
}
.vspos-card--tradein .vspos-card-value {
    color: #f87171 !important;
}
#vspos_tradein_refund_banner {
    margin: 6px 0 0;
    padding: 8px 10px;
    border-radius: 6px;
    background: rgba(248, 113, 113, 0.12);
    border: 1px solid rgba(248, 113, 113, 0.4);
    color: #fca5a5;
    font-size: 12px;
    display: none;
}
</style>
