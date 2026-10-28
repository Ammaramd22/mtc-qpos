<div class="pos-tab-content" data-settings-tab="system">
     <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('theme_color', __('lang_v1.theme_color')); !!}
                {!! Form::select('theme_color', $theme_colors,   $business->theme_color, 
                    ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $page_entries = [25 => 25, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, -1 => __('lang_v1.all')];
                @endphp
                {!! Form::label('default_datatable_page_entries', __('lang_v1.default_datatable_page_entries')); !!}
                {!! Form::select('common_settings[default_datatable_page_entries]', $page_entries, !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25 , 
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'default_datatable_page_entries']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_tooltip', 1, $business->enable_tooltip , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'business.show_help_text' ) }}
                  </label>
                </div>
            </div>
        </div>
        @php $is_admin_or_super = auth()->user()->can('superadmin') || auth()->user()->hasRole('Admin#'.session('user.business_id')); @endphp
        @if($is_admin_or_super)
        <div class="col-sm-12"><hr style="border-color:#2a2a2a;margin:18px 0;"></div>
        <div class="col-sm-12 vspos-income-masking-block" style="margin-bottom:12px;">
            <h4 class="vspos-income-masking-title" style="font-weight:800;color:#f5c518;margin:0 0 6px;font-size:1rem;">
                Income Masking / IC Mode
            </h4>
            <p class="help-block vspos-income-masking-help" style="color:#a3a3a3;margin:0 0 14px;">
                When enabled, sales / purchase / profit figures shown to non-admin users are reduced to the display percentage. Use IC Mode PIN to temporarily show real values.
            </p>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label style="color:#f5f5f5;font-weight:600;">
                    {!! Form::checkbox('common_settings[enable_commission_masking]', 1, !empty($common_settings['enable_commission_masking']), ['class' => 'input-icheck', 'id' => 'enable_commission_masking']); !!}
                    Enable Income Masking
                  </label>
                </div>
                <p class="help-block" style="color:#737373;">Uncheck this and save to turn masking off completely.</p>
            </div>
        </div>
        <div class="col-sm-4" id="commission_percentage_div" @if(empty($common_settings['enable_commission_masking'])) style="display:none;" @endif>
            <div class="form-group">
                {!! Form::label('common_settings[commission_percentage]', 'Display Percentage (%)', ['style' => 'color:#e5e5e5']) !!}
                {!! Form::number('common_settings[commission_percentage]', !empty($common_settings['commission_percentage']) ? $common_settings['commission_percentage'] : 100, ['class' => 'form-control', 'min' => 1, 'max' => 100, 'step' => '0.01', 'placeholder' => 'e.g. 20']) !!}
                <p class="help-block" style="color:#737373;">Non-admin users will see (real value × this%) in dashboard &amp; reports.</p>
            </div>
        </div>
        <div class="col-sm-4" id="ic_mode_pin_div" @if(empty($common_settings['enable_commission_masking'])) style="display:none;" @endif>
            <div class="form-group">
                {!! Form::label('common_settings[ic_mode_pin]', 'IC Mode PIN', ['style' => 'color:#e5e5e5']) !!}
                {!! Form::text('common_settings[ic_mode_pin]', !empty($common_settings['ic_mode_pin']) ? $common_settings['ic_mode_pin'] : '', ['class' => 'form-control', 'placeholder' => 'Secret PIN', 'autocomplete' => 'off']) !!}
                <p class="help-block" style="color:#737373;">Enter this PIN (shortcut key) to temporarily view real values.</p>
            </div>
        </div>
        <div class="col-sm-4" id="ic_mode_shortcut_div" @if(empty($common_settings['enable_commission_masking'])) style="display:none;" @endif>
            <div class="form-group">
                {!! Form::label('common_settings[ic_mode_shortcut_key]', 'IC Mode Shortcut Key (Default)', ['style' => 'color:#e5e5e5']) !!}
                {!! Form::text('common_settings[ic_mode_shortcut_key]', !empty($common_settings['ic_mode_shortcut_key']) ? $common_settings['ic_mode_shortcut_key'] : 'F7', ['class' => 'form-control', 'id' => 'ic_mode_shortcut_key_input', 'placeholder' => 'e.g. F7, F8, F9', 'readonly' => 'readonly']) !!}
                <p class="help-block" style="color:#737373;">Click the field, then press a key to set. Each user can override this in their profile.</p>
            </div>
        </div>
        <div class="col-sm-4" id="ic_mode_timeout_div" @if(empty($common_settings['enable_commission_masking'])) style="display:none;" @endif>
            <div class="form-group">
                {!! Form::label('common_settings[ic_mode_timeout_minutes]', 'Auto-Mask Timeout (Minutes)', ['style' => 'color:#e5e5e5']) !!}
                {!! Form::number('common_settings[ic_mode_timeout_minutes]', !empty($common_settings['ic_mode_timeout_minutes']) ? $common_settings['ic_mode_timeout_minutes'] : 5, ['class' => 'form-control', 'min' => 1, 'max' => 60, 'placeholder' => 'e.g. 5']) !!}
                <p class="help-block" style="color:#737373;">Real values will auto-hide after this many minutes. Min 1, Max 60.</p>
            </div>
        </div>
        @endif

        @php
            $can_clear_cache = auth()->user()->can('business_settings.access')
                || auth()->user()->can('superadmin')
                || auth()->user()->hasRole('Admin#'.session('user.business_id'));
        @endphp
        @if($can_clear_cache)
        <div class="col-sm-12"><hr style="border-color:#2a2a2a;margin:18px 0;"></div>
        <div class="col-sm-12" style="margin-bottom:8px;">
            <h4 style="font-weight:800;color:#38bdf8;margin:0 0 6px;font-size:1rem;">
                <i class="fa fa-refresh"></i> @lang('lang_v1.clear_cache')
            </h4>
            <p class="help-block" style="color:#a3a3a3;margin:0 0 12px;">
                @lang('lang_v1.clear_cache_help')
            </p>
            <button type="button" class="btn btn-warning" id="btn_clear_app_cache">
                <i class="fa fa-trash"></i> @lang('lang_v1.clear_cache_now')
            </button>
            <span id="clear_cache_status" style="margin-left:10px;color:#86efac;"></span>
        </div>
        @endif
    </div>
</div>
@push('javascript')
<script>
    $(document).on('ifToggled', '#enable_commission_masking', function() {
        if ($(this).is(':checked')) {
            $('#commission_percentage_div').show();
            $('#ic_mode_pin_div').show();
            $('#ic_mode_shortcut_div').show();
            $('#ic_mode_timeout_div').show();
        } else {
            $('#commission_percentage_div').hide();
            $('#ic_mode_pin_div').hide();
            $('#ic_mode_shortcut_div').hide();
            $('#ic_mode_timeout_div').hide();
        }
    });

    $('#ic_mode_shortcut_key_input').on('keydown', function(e) {
        e.preventDefault();
        $(this).val(e.key);
    }).on('focus', function() {
        $(this).val('').attr('placeholder', 'Press any key...');
    }).on('blur', function() {
        if (!$(this).val()) $(this).val('F7');
        $(this).attr('placeholder', 'e.g. F7, F8, F9');
    });

    $(document).on('click', '#btn_clear_app_cache', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#clear_cache_status');
        if (!window.confirm(@json(__('lang_v1.clear_cache_confirm')))) {
            return;
        }
        $btn.prop('disabled', true);
        $status.text(@json(__('lang_v1.clearing_cache')));
        $.ajax({
            method: 'POST',
            url: "{{ url('/business/clear-cache') }}",
            dataType: 'json',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                if (res && res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.msg || 'Cache cleared');
                    }
                    $status.text(res.msg || 'Done');
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((res && res.msg) || 'Failed');
                    }
                    $status.text((res && res.msg) || 'Failed');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed to clear cache';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
                $status.text(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
@endpush
