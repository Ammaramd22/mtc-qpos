{{-- Nutmeg bag packing popup (POS sell) --}}
<div class="modal fade" id="nutmeg_bag_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="background:#111;color:#eee;border:1px solid #333;">
            <div class="modal-header" style="border-bottom:1px solid #333;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;"><span>&times;</span></button>
                <h4 class="modal-title" style="color:#f5c518;"><i class="fa fa-shopping-bag"></i> @lang('lang_v1.pack_in_bag')</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="nbag_product_name" style="margin-top:0;"></p>
                <div class="form-group">
                    <label>@lang('lang_v1.bag_size')</label>
                    <select id="nbag_bag_id" class="form-control" style="background:#1a1a1a;border-color:#333;color:#eee;"></select>
                </div>
                <div class="form-group">
                    <label>@lang('lang_v1.number_of_bags')</label>
                    <input type="text" id="nbag_qty" class="form-control input_number" value="1" style="background:#1a1a1a;border-color:#333;color:#eee;">
                </div>
                <div class="well well-sm" style="background:#1a1a1a;border-color:#333;color:#ccc;">
                    <div>@lang('lang_v1.product_weight'): <strong id="nbag_weight" style="color:#f5c518;">0</strong></div>
                    <div>@lang('lang_v1.bags_to_deduct'): <strong id="nbag_bags_lbl">0</strong></div>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="nbag_skip"> @lang('lang_v1.sell_without_bag')
                    </label>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-warning" id="nbag_confirm" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                    @lang('messages.add')
                </button>
            </div>
        </div>
    </div>
</div>
