<div class="pos-tab-content" data-settings-tab="pos">
    <h4>@lang('business.add_keyboard_shortcuts'):</h4>
    <p class="help-block">Prefer <b>F1–F12</b> for POS (fast on barcode scanners / keyboards). Example: <b>f3</b>, <b>f8</b>, <b>f10</b>. You can also use combos like <b>shift+d</b>.</p>
    <p class="help-block">
        <b>Suggested F-keys:</b>
        F1 Cash · F2 Qty · F3 Pay · F4 Search · F5 Card · F6 Credit · F8 Products · F9 Supplier · F10 Loyalty · F11 Bank · F12 Split
    </p>
    <p class="help-block">
        <b>@lang('lang_v1.available_key_names_are'):</b>
        <br> f1–f12, shift, ctrl, alt, backspace, tab, enter, esc, space, …
    </p>
    <div class="row">
        <div class="col-sm-6">
            <table class="table table-striped">
                <tr>
                    <th>@lang('business.operations')</th>
                    <th>@lang('business.keyboard_shortcut')</th>
                </tr>
                <tr>
                    <td>{!! __('sale.express_finalize') !!}:</td>
                    <td>
                        {!! Form::text('shortcuts[pos][express_checkout]', 
                        !empty($shortcuts["pos"]["express_checkout"]) ? $shortcuts["pos"]["express_checkout"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('sale.finalize'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][pay_n_ckeckout]', !empty($shortcuts["pos"]["pay_n_ckeckout"]) ? $shortcuts["pos"]["pay_n_ckeckout"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('sale.draft'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][draft]', !empty($shortcuts["pos"]["draft"]) ? $shortcuts["pos"]["draft"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('messages.cancel'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][cancel]', !empty($shortcuts["pos"]["cancel"]) ? $shortcuts["pos"]["cancel"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.recent_product_quantity'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][recent_product_quantity]', !empty($shortcuts["pos"]["recent_product_quantity"]) ? $shortcuts["pos"]["recent_product_quantity"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.weighing_scale'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][weighing_scale]', !empty($shortcuts["pos"]["weighing_scale"]) ? $shortcuts["pos"]["weighing_scale"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-sm-6">
            <table class="table table-striped">
                <tr>
                    <th>@lang('business.operations')</th>
                    <th>@lang('business.keyboard_shortcut')</th>
                </tr>
                <tr>
                    <td>@lang('sale.edit_discount'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][edit_discount]', !empty($shortcuts["pos"]["edit_discount"]) ? $shortcuts["pos"]["edit_discount"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('sale.edit_order_tax'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][edit_order_tax]', !empty($shortcuts["pos"]["edit_order_tax"]) ? $shortcuts["pos"]["edit_order_tax"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('sale.add_payment_row'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][add_payment_row]', !empty($shortcuts["pos"]["add_payment_row"]) ? $shortcuts["pos"]["add_payment_row"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('sale.finalize_payment'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][finalize_payment]', !empty($shortcuts["pos"]["finalize_payment"]) ? $shortcuts["pos"]["finalize_payment"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.add_new_product'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][add_new_product]', !empty($shortcuts["pos"]["add_new_product"]) ? $shortcuts["pos"]["add_new_product"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.bulk_product_search'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][bulk_product_search]', !empty($shortcuts["pos"]["bulk_product_search"]) ? $shortcuts["pos"]["bulk_product_search"] : 'f8', ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.pay_supplier'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][pay_supplier]', !empty($shortcuts["pos"]["pay_supplier"]) ? $shortcuts["pos"]["pay_supplier"] : 'f9', ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.loyalty_customer_shortcut'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][loyalty_customer]', !empty($shortcuts["pos"]["loyalty_customer"]) ? $shortcuts["pos"]["loyalty_customer"] : 'f10', ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.express_checkout_card'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][express_checkout_card]', !empty($shortcuts["pos"]["express_checkout_card"]) ? $shortcuts["pos"]["express_checkout_card"] : 'f5', ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.credit_sale'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][credit_sale]', !empty($shortcuts["pos"]["credit_sale"]) ? $shortcuts["pos"]["credit_sale"] : 'f6', ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.suspend'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][suspend]', !empty($shortcuts["pos"]["suspend"]) ? $shortcuts["pos"]["suspend"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
                <tr>
                    <td>@lang('lang_v1.quotation'):</td>
                    <td>
                        {!! Form::text('shortcuts[pos][quotation]', !empty($shortcuts["pos"]["quotation"]) ? $shortcuts["pos"]["quotation"] : null, ['class' => 'form-control']); !!}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <h4>@lang('lang_v1.pos_settings'):</h4>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('pos_settings_pos_ui', __('lang_v1.pos_ui') . ':') !!}
                {!! Form::select('pos_settings[pos_ui]', [
                    'classic' => __('lang_v1.pos_ui_classic'),
                    'touch' => __('lang_v1.pos_ui_touch'),
                ], $pos_settings['pos_ui'] ?? 'classic', [
                    'class' => 'form-control select2',
                    'id' => 'pos_settings_pos_ui',
                    'style' => 'width:100%;',
                ]); !!}
                <p class="help-block" style="margin-top:6px;">
                    @lang('lang_v1.pos_ui_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[enable_pop]', 1,
                        !empty($pos_settings['enable_pop']),
                    [ 'class' => 'input-icheck', 'id' => 'pos_settings_enable_pop']); !!} {{ __( 'lang_v1.enable_point_of_purchase' ) }}
                  </label>
                </div>
                <p class="help-block" style="margin-top:0;">
                    @lang('lang_v1.enable_point_of_purchase_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('pos_settings_pop_ui', __('lang_v1.pop_ui') . ':') !!}
                {!! Form::select('pos_settings[pop_ui]', [
                    'classic' => __('lang_v1.pos_ui_classic'),
                    'touch' => __('lang_v1.pos_ui_touch'),
                ], $pos_settings['pop_ui'] ?? 'touch', [
                    'class' => 'form-control select2',
                    'id' => 'pos_settings_pop_ui',
                    'style' => 'width:100%;',
                ]); !!}
                <p class="help-block" style="margin-top:6px;">
                    @lang('lang_v1.pop_ui_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('pos_settings_purchase_ui', __('lang_v1.purchase_ui') . ':') !!}
                {!! Form::select('pos_settings[purchase_ui]', [
                    'classic' => __('lang_v1.purchase_ui_classic'),
                    'pop' => __('lang_v1.purchase_ui_pop'),
                ], $pos_settings['purchase_ui'] ?? 'classic', [
                    'class' => 'form-control select2',
                    'id' => 'pos_settings_purchase_ui',
                    'style' => 'width:100%;',
                ]); !!}
                <p class="help-block" style="margin-top:6px;">
                    @lang('lang_v1.purchase_ui_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[pop_contact_both]', 1,
                        !isset($pos_settings['pop_contact_both']) || !empty($pos_settings['pop_contact_both']),
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.pop_contact_both' ) }}
                  </label>
                </div>
                <p class="help-block" style="margin-top:0;">
                    @lang('lang_v1.pop_contact_both_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[pop_link_cash_register]', 1,
                        !isset($pos_settings['pop_link_cash_register']) || !empty($pos_settings['pop_link_cash_register']),
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.pop_link_cash_register' ) }}
                  </label>
                </div>
                <p class="help-block" style="margin-top:0;">
                    @lang('lang_v1.pop_link_cash_register_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[touch_ask_qty]', 1,
                        !empty($pos_settings['touch_ask_qty']),
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.touch_ask_qty' ) }}
                  </label>
                </div>
                <p class="help-block" style="margin-top:0;">
                    @lang('lang_v1.touch_ask_qty_help')
                </p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_pay_checkout]', 1,  
                        $pos_settings['disable_pay_checkout'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_pay_checkout' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_draft]', 1,  
                        $pos_settings['disable_draft'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_draft' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_quotation]', 1,
                        !empty($pos_settings['disable_quotation']) ? $pos_settings['disable_quotation'] : null,
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_quotation' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_express_checkout]', 1,  
                        $pos_settings['disable_express_checkout'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_express_checkout' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[hide_product_suggestion]', 1,  $pos_settings['hide_product_suggestion'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.hide_product_suggestion' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[hide_recent_trans]', 1,  $pos_settings['hide_recent_trans'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.hide_recent_trans' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_discount]', 1,  $pos_settings['disable_discount'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_discount' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_order_tax]', 1,  $pos_settings['disable_order_tax'] , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_order_tax' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[is_pos_subtotal_editable]', 1,  
                    empty($pos_settings['is_pos_subtotal_editable']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.subtotal_editable' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.subtotal_editable_help_text'))
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_suspend]', 1,  
                    empty($pos_settings['disable_suspend']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_suspend_sale' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-6">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[enable_transaction_date]', 1,  
                    empty($pos_settings['enable_transaction_date']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_pos_transaction_date' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[inline_service_staff]', 1,  
                    !empty($pos_settings['inline_service_staff']) ? true : false , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_service_staff_in_product_line' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.inline_service_staff_tooltip'))
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[is_service_staff_required]', 1,  
                    empty($pos_settings['is_service_staff_required']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.is_service_staff_required' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[disable_credit_sale_button]', 1,  
                    empty($pos_settings['disable_credit_sale_button']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.disable_credit_sale_button' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.show_credit_sale_btn_help'))
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[enable_weighing_scale]', 1,  
                    empty($pos_settings['enable_weighing_scale']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_weighing_scale' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[show_invoice_scheme]', 1,  
                       empty($pos_settings['show_invoice_scheme']) ? 0 : 1 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.show_invoice_scheme' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[show_invoice_layout]', 1,  
                        !empty($pos_settings['show_invoice_layout']) , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.show_invoice_layout' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[print_on_suspend]', 1,  
                        !empty($pos_settings['print_on_suspend']) , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.print_on_suspend' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[show_pricing_on_product_sugesstion]', 1,  
                        !empty($pos_settings['show_pricing_on_product_sugesstion']) , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.show_pricing_on_product_sugesstion' ) }}
                  </label>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[enable_serial_per_qty]', 1,
                        !empty($pos_settings['enable_serial_per_qty']),
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_serial_per_qty' ) }}
                  </label>
                  <p class="help-block">{{ __( 'lang_v1.enable_serial_per_qty_help' ) }}</p>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[enable_handheld_58mm]', 1,
                        !empty($pos_settings['enable_handheld_58mm']),
                    [ 'class' => 'input-icheck', 'id' => 'enable_handheld_58mm']); !!} {{ __( 'lang_v1.enable_handheld_58mm' ) }}
                  </label>
                  <p class="help-block">{{ __( 'lang_v1.enable_handheld_58mm_help' ) }}</p>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[mute_sound]', 1,
                        !empty($pos_settings['mute_sound']),
                    [ 'class' => 'input-icheck', 'id' => 'pos_settings_mute_sound']); !!} {{ __( 'lang_v1.mute_sound' ) }}
                  </label>
                  <p class="help-block">{{ __( 'lang_v1.mute_sound_help' ) }}</p>
                </div>
            </div>
        </div>
    </div>    
    <hr>
    {{-- Removed settings_weighing_scale include to avoid duplicate fields --}}
</div>