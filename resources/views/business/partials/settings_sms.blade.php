@php
    $sms_service = isset($sms_settings['sms_service']) ? $sms_settings['sms_service'] : 'notifylk';
    // Map legacy nexmo/twilio to "other" for the simplified 3-option UI
    if (in_array($sms_service, ['nexmo', 'twilio'], true)) {
        $sms_service = 'other';
    }
@endphp
<div class="pos-tab-content" data-settings-tab="sms_settings">
    <div class="well well-sm" style="margin-bottom:16px;">
        <p style="margin:0 0 4px;font-weight:700;"><i class="fa fa-commenting"></i> SMS Provider</p>
        <p class="help-block" style="margin:0;">Choose one gateway. eBill SMS and other notifications use this provider.</p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('sms_service', __('lang_v1.sms_service') . ':') !!}
                {!! Form::select('sms_settings[sms_service]', [
                    'notifylk' => 'notify.lk',
                    'smslenz' => 'SMSlenz',
                    'other' => 'Any UK / custom SMS provider',
                ], $sms_service, ['class' => 'form-control', 'id' => 'sms_service']); !!}
            </div>
        </div>
    </div>

    {{-- notify.lk --}}
    <div class="row sms_service_settings @if($sms_service != 'notifylk') hide @endif" data-service="notifylk">
        <div class="col-xs-12" style="margin-bottom:10px;">
            <div class="alert alert-info" style="margin-bottom:0;">
                Configure credentials from your <strong>notify.lk</strong> account.
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('notifylk_user_id', 'User ID:') !!}
                {!! Form::text('sms_settings[notifylk_user_id]', $sms_settings['notifylk_user_id'] ?? null, ['class' => 'form-control', 'placeholder' => 'User ID', 'id' => 'notifylk_user_id']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('notifylk_api_key', 'API Key:') !!}
                {!! Form::text('sms_settings[notifylk_api_key]', $sms_settings['notifylk_api_key'] ?? null, ['class' => 'form-control', 'placeholder' => 'API Key', 'id' => 'notifylk_api_key']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('notifylk_sender_id', 'Sender ID:') !!}
                {!! Form::text('sms_settings[notifylk_sender_id]', $sms_settings['notifylk_sender_id'] ?? 'NotifyDEMO', ['class' => 'form-control', 'placeholder' => 'NotifyDEMO', 'id' => 'notifylk_sender_id']); !!}
            </div>
        </div>
        <div class="col-sm-8">
            <div class="form-group">
                <div class="input-group">
                    {!! Form::text('test_number_notifylk', null, ['class' => 'form-control', 'placeholder' => 'Test number e.g. 077xxxxxxx', 'id' => 'test_number_notifylk']); !!}
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-success" id="test_sms_btn_notifylk">@lang('lang_v1.test_sms_configuration')</button>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- SMSlenz --}}
    <div class="row sms_service_settings @if($sms_service != 'smslenz') hide @endif" data-service="smslenz">
        <div class="col-xs-12" style="margin-bottom:10px;">
            <div class="alert alert-info" style="margin-bottom:0;">
                Get User ID and API Key from
                <a href="https://smslenz.lk/account/api-key" target="_blank" rel="noopener">smslenz.lk → API Settings</a>.
                Test sender: <code>SMSlenzDEMO</code> (case sensitive).
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('smslenz_user_id', 'User ID:') !!}
                {!! Form::text('sms_settings[smslenz_user_id]', $sms_settings['smslenz_user_id'] ?? null, ['class' => 'form-control', 'placeholder' => 'User ID', 'id' => 'smslenz_user_id']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('smslenz_api_key', 'API Key:') !!}
                {!! Form::text('sms_settings[smslenz_api_key]', $sms_settings['smslenz_api_key'] ?? null, ['class' => 'form-control', 'placeholder' => 'API Key', 'id' => 'smslenz_api_key']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('smslenz_sender_id', 'Sender ID:') !!}
                {!! Form::text('sms_settings[smslenz_sender_id]', $sms_settings['smslenz_sender_id'] ?? 'SMSlenzDEMO', ['class' => 'form-control', 'placeholder' => 'SMSlenzDEMO', 'id' => 'smslenz_sender_id']); !!}
            </div>
        </div>
        <div class="col-sm-8">
            <div class="form-group">
                <div class="input-group">
                    {!! Form::text('test_number_smslenz', null, ['class' => 'form-control', 'placeholder' => 'Test number e.g. 077xxxxxxx', 'id' => 'test_number_smslenz']); !!}
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-success" id="test_sms_btn_smslenz">@lang('lang_v1.test_sms_configuration')</button>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Any UK / custom SMS provider (HTTP API) --}}
    <div class="row sms_service_settings @if($sms_service != 'other') hide @endif" data-service="other">
        <div class="col-xs-12" style="margin-bottom:10px;">
            <div class="alert alert-info" style="margin-bottom:0;">
                Use this for <strong>any UK or custom SMS gateway</strong> that accepts HTTP GET/POST (Textlocal, MessageBird, ClickSend, Twilio URL, etc.).
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
            	{!! Form::label('sms_settings_url', 'API URL:') !!}
            	{!! Form::text('sms_settings[url]', $sms_settings['url'] ?? null, ['class' => 'form-control','placeholder' => 'https://api.provider.com/send', 'id' => 'sms_settings_url']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('send_to_param_name', __('lang_v1.send_to_param_name') . ':') !!}
                <div class="input-group">
                    {!! Form::text('sms_settings[send_to_param_name]', !empty($sms_settings['send_to_param_name']) ? $sms_settings['send_to_param_name'] : null, ['class' => 'form-control', 'id' => 'send_to_param_name']); !!}
                    <div class="input-group-addon">
                        <select class="" name="sms_settings[send_to_param_type]">
                            <option value="string" @if (empty($sms_settings['send_to_param_type']) || $sms_settings['send_to_param_type'] == 'string') selected @endif>{{ __('lang_v1.string_default') }}</option>
                            <option value="array" @if (!empty($sms_settings['send_to_param_type']) && $sms_settings['send_to_param_type'] == 'array') selected @endif>{{ __('lang_v1.array') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('msg_param_name', __('lang_v1.msg_param_name') . ':') !!}
                {!! Form::text('sms_settings[msg_param_name]', $sms_settings['msg_param_name'] ?? null, ['class' => 'form-control','placeholder' => __('lang_v1.msg_param_name'), 'id' => 'msg_param_name']); !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('request_method', __('lang_v1.request_method') . ':') !!}
                {!! Form::select('sms_settings[request_method]', ['get' => 'GET', 'post' => 'POST'], $sms_settings['request_method'] ?? 'post', ['class' => 'form-control', 'id' => 'request_method']); !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! Form::label('data_parameter_type', __('lang_v1.data_parameter_type') . ':') !!}
                {!! Form::select('sms_settings[data_parameter_type]', ['form-data' => 'Form Data', 'json' => 'JSON'], !empty($sms_settings['data_parameter_type']) ? $sms_settings['data_parameter_type'] : 'form-data', ['class' => 'form-control', 'id' => 'data_parameter_type']); !!}
            </div>
        </div>
        <div class="clearfix"></div>
        <hr>
        <div class="col-xs-12"><p class="help-block"><strong>Optional headers</strong></p></div>
        @for ($h = 1; $h <= 3; $h++)
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sms_settings_header_key'.$h, __('lang_v1.sms_settings_header_key', ['number' => $h]) . ':') !!}
                {!! Form::text('sms_settings[header_'.$h.']', $sms_settings['header_'.$h] ?? null, ['class' => 'form-control', 'id' => 'sms_settings_header_key'.$h]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sms_settings_header_val'.$h, __('lang_v1.sms_settings_header_val', ['number' => $h]) . ':') !!}
                {!! Form::text('sms_settings[header_val_'.$h.']', $sms_settings['header_val_'.$h] ?? null, ['class' => 'form-control', 'id' => 'sms_settings_header_val'.$h]); !!}
            </div>
        </div>
        <div class="clearfix"></div>
        @endfor
        <hr>
        <div class="col-xs-12"><p class="help-block"><strong>Extra parameters</strong> (API key, username, etc.)</p></div>
        @for ($i = 1; $i <= 6; $i++)
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sms_settings_param_key'.$i, __('lang_v1.sms_settings_param_key', ['number' => $i]) . ':') !!}
                {!! Form::text('sms_settings[param_'.$i.']', $sms_settings['param_'.$i] ?? null, ['class' => 'form-control', 'id' => 'sms_settings_param_key'.$i]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sms_settings_param_val'.$i, __('lang_v1.sms_settings_param_val', ['number' => $i]) . ':') !!}
                {!! Form::text('sms_settings[param_val_'.$i.']', $sms_settings['param_val_'.$i] ?? null, ['class' => 'form-control', 'id' => 'sms_settings_param_val'.$i]); !!}
            </div>
        </div>
        <div class="clearfix"></div>
        @endfor
        <hr>
        <div class="col-md-8">
            <div class="form-group">
                <div class="input-group">
                    {!! Form::text('test_number', null, ['class' => 'form-control','placeholder' => __('lang_v1.test_number'), 'id' => 'test_number']); !!}
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-success" id="test_sms_btn">@lang('lang_v1.test_sms_configuration')</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $(document).on('change', '#sms_service', function () {
        var service = $(this).val();
        $('.sms_service_settings').addClass('hide');
        $('.sms_service_settings[data-service="' + service + '"]').removeClass('hide');
    });
});
</script>
