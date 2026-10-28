<!--payment related settings -->
<div class="pos-tab-content" data-settings-tab="payment">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('cash_denominations', __('lang_v1.cash_denominations') . ':') !!}
                 {!! Form::text('pos_settings[cash_denominations]', isset($pos_settings['cash_denominations']) ? $pos_settings['cash_denominations'] : null, ['class' => 'form-control', 'id' => 'cash_denominations']); !!}
                 <p class="help-block">{{__('lang_v1.cash_denominations_help')}}</p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('enable_cash_denomination_on', __('lang_v1.enable_cash_denomination_on') . ':') !!}
                {!! Form::select('pos_settings[enable_cash_denomination_on]', ['pos_screen' => __('lang_v1.pos_screen'), 'all_screens' => __('lang_v1.all_screen')], isset($pos_settings['enable_cash_denomination_on']) ? $pos_settings['enable_cash_denomination_on'] : 'pos_screen', ['class' => 'form-control', 'style' => 'width: 100%;' ]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('enable_cash_denomination_for_payment_methods', __('lang_v1.enable_cash_denomination_for_payment_methods') . ':') !!}
                {!! Form::select('pos_settings[enable_cash_denomination_for_payment_methods][]', $payment_types, isset($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'multiple' ]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[cash_denomination_strict_check]', 1,
                        !empty($pos_settings['cash_denomination_strict_check']) ,
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.strict_check' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.strict_check_help'))
                </div>
            </div>
        </div>
    </div>

    <hr style="border-color: #e2e8f0;">
    <div class="row">
        <div class="col-md-12">
            <h4 style="font-weight:700;color:#0f172a;margin:0 0 16px;">🏦 Cheque Bank Settings</h4>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <div class="checkbox" style="margin-top: 8px;">
                    <label style="font-weight: 600; color: #334155;">
                        {!! Form::checkbox('pos_settings[use_sri_lankan_banks]', 1,
                            !empty($pos_settings['use_sri_lankan_banks']) && $pos_settings['use_sri_lankan_banks'] == 1,
                            ['class' => 'input-icheck']); !!}
                        Use Sri Lankan Banks List
                    </label>
                </div>
                <p class="help-block" style="margin-left: 20px; margin-top: 4px;">
                    If enabled, cheque forms will show a dropdown with all licensed Sri Lankan banks.<br>
                    If disabled, you can freely type any bank name.
                </p>
            </div>
        </div>
    </div>
</div>