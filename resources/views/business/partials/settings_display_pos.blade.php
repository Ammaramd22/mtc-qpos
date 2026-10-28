<div class="pos-tab-content" data-settings-tab="display_screen">
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[customer_display_screen]', 1,  
                        !empty($pos_settings['customer_display_screen']) , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_customer_display_screen' ) }}
                  </label>
                  <p class="help-block"><i> @lang('lang_v1.customer_display_instraction')</i></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('customer_display_mode', __('lang_v1.customer_display_mode') . ':') !!}
                {!! Form::select('pos_settings[customer_display_mode]', [
                    'screen' => __('lang_v1.customer_display_mode_screen'),
                    'analog' => __('lang_v1.customer_display_mode_analog'),
                ], $pos_settings['customer_display_mode'] ?? 'screen', [
                    'class' => 'form-control',
                    'id' => 'customer_display_mode',
                ]); !!}
                <p class="help-block"><i>@lang('lang_v1.customer_display_mode_help')</i></p>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    <input type="hidden" name="pos_settings[enable_cd_enter_number]" value="0">
                    {!! Form::checkbox('pos_settings[enable_cd_enter_number]', 1,
                        !isset($pos_settings['enable_cd_enter_number']) || !empty($pos_settings['enable_cd_enter_number']),
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_cd_enter_number' ) }}
                  </label>
                  <p class="help-block"><i>@lang('lang_v1.enable_cd_enter_number_help')</i></p>
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <hr>
            <h4 class="tw-font-semibold tw-text-base tw-mb-2">@lang('lang_v1.hardware_customer_display')</h4>
            <p class="help-block tw-mt-0"><i>@lang('lang_v1.hardware_customer_display_help')</i></p>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    <input type="hidden" name="pos_settings[enable_vfd_customer_display]" value="0">
                    {!! Form::checkbox('pos_settings[enable_vfd_customer_display]', 1,
                        !empty($pos_settings['enable_vfd_customer_display']),
                    [ 'class' => 'input-icheck', 'id' => 'enable_vfd_customer_display']); !!} {{ __( 'lang_v1.enable_vfd_customer_display' ) }}
                  </label>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('vfd_display_protocol', __('lang_v1.vfd_display_protocol') . ':') !!}
                {!! Form::select('pos_settings[vfd_display_protocol]', [
                    'numeric' => __('lang_v1.vfd_protocol_numeric'),
                    'generic' => __('lang_v1.vfd_protocol_generic'),
                    'epson' => __('lang_v1.vfd_protocol_epson'),
                    'cd7220' => __('lang_v1.vfd_protocol_cd7220'),
                ], $pos_settings['vfd_display_protocol'] ?? 'numeric', ['class' => 'form-control', 'id' => 'vfd_display_protocol']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('vfd_display_baud', __('lang_v1.vfd_display_baud') . ':') !!}
                {!! Form::select('pos_settings[vfd_display_baud]', [
                    '2400' => '2400',
                    '4800' => '4800',
                    '9600' => '9600',
                    '19200' => '19200',
                    '38400' => '38400',
                ], $pos_settings['vfd_display_baud'] ?? '9600', ['class' => 'form-control', 'id' => 'vfd_display_baud']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('vfd_display_chars', __('lang_v1.vfd_display_chars') . ':') !!}
                {!! Form::number('pos_settings[vfd_display_chars]', $pos_settings['vfd_display_chars'] ?? 8, [
                    'class' => 'form-control',
                    'id' => 'vfd_display_chars',
                    'min' => 4,
                    'max' => 40,
                ]); !!}
                <p class="help-block"><i>@lang('lang_v1.vfd_display_chars_help')</i></p>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('display_screen_heading', __('lang_v1.display_screen_heading') . ':') !!}
                 {!! Form::textarea('pos_settings[display_screen_heading]', isset($pos_settings['display_screen_heading']) ? $pos_settings['display_screen_heading'] : null, ['class' => 'form-control', 'id' => 'display_screen_heading']); !!}
            </div>
        </div>
        @for ($i = 1; $i <= 10; $i++)
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label("carousel_image_$i", __('lang_v1.carousel_image', ['number' => $i]) . ':') !!}
                    {!! Form::file("carousel_image_$i", ['accept' => 'image/*', 'class' => 'carousel_image']) !!}
                    <p class="help-block"><i> @lang('lang_v1.image_help')</i></p>
                </div>
            </div>
        @endfor
    </div>
</div>
