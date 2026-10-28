@php
    $ebill = ! empty($common_settings['ebill_settings']) ? $common_settings['ebill_settings'] : [];
    $enable_sms_ebill       = ! empty($ebill['enable_sms_ebill']) ? 1 : 0;
    $enable_whatsapp_ebill  = ! empty($ebill['enable_whatsapp_ebill']) ? 1 : 0;
    $prompt_before_send     = ! empty($ebill['prompt_before_send']) ? 1 : 0;
    $wasender_api_token     = $ebill['wasender_api_token'] ?? '';
    $custom_invoice_domain  = $ebill['custom_invoice_domain'] ?? '';
    $sms_template           = $ebill['sms_template'] ?? "Dear {contact_name},\nThank you for shopping at {business_name}.\nView your bill here: {invoice_url}";
    $whatsapp_template      = $ebill['whatsapp_template'] ?? "Dear {contact_name},\nThank you for shopping at {business_name}.\nView your bill here: {invoice_url}";
    $social_facebook        = $ebill['social_facebook'] ?? '';
    $social_instagram       = $ebill['social_instagram'] ?? '';
    $social_website         = $ebill['social_website'] ?? '';
@endphp

<div class="pos-tab-content" data-settings-tab="ebill">

    <div class="well well-sm" style="margin-bottom:16px;">
        <p style="margin:0 0 4px;font-weight:700;"><i class="fa fa-paper-plane"></i> eBill — Digital Invoice Delivery</p>
        <p class="help-block" style="margin:0;">Send a digital bill link after sale via <strong>SMS</strong> (uses your SMS Settings provider) and/or <strong>WhatsApp</strong>.</p>
        <p class="help-block" style="margin:6px 0 0;">Template tags: <code>{contact_name}</code> · <code>{business_name}</code> · <code>{invoice_no}</code> · <code>{invoice_url}</code></p>
    </div>

    {{-- Ask before send --}}
    <div class="well" style="margin-bottom:16px;">
        <div class="checkbox" style="margin:0;">
            <label>
                {!! Form::checkbox('common_settings[ebill_settings][prompt_before_send]', 1, $prompt_before_send, ['class' => 'input-icheck']); !!}
                <strong>Ask before sending eBill</strong>
            </label>
        </div>
        <p class="help-block" style="margin:6px 0 0;">If enabled, a prompt appears after each sale to confirm sending the digital bill.</p>
    </div>

    {{-- SMS eBill (uses SMS Settings) --}}
    <div class="box box-solid" style="margin-bottom:16px;border:1px solid #2a2a2a;">
        <div class="box-header with-border" style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h4 class="box-title" style="margin:0;font-size:14px;">Send eBill via SMS</h4>
                <p class="help-block" style="margin:4px 0 0;">Uses the SMS provider configured under <strong>SMS Settings</strong>.</p>
            </div>
            <label class="ebill-switch" style="margin:0;">
                <input type="checkbox" name="common_settings[ebill_settings][enable_sms_ebill]" value="1"
                    class="ebill-toggle" data-target="#sms_ebill_settings"
                    @if($enable_sms_ebill) checked @endif>
                <span class="ebill-slider"></span>
            </label>
        </div>
        <div id="sms_ebill_settings" class="box-body @if(!$enable_sms_ebill) hide @endif">
            <div class="form-group">
                {!! Form::label('ebill_custom_invoice_domain', 'Custom Invoice Domain (URL Mask):') !!}
                {!! Form::text('common_settings[ebill_settings][custom_invoice_domain]', $custom_invoice_domain, ['class' => 'form-control', 'placeholder' => 'e.g. https://ebill.mybusiness.com']) !!}
                <p class="help-block">Optional. Invoice link will use this domain instead of the POS URL.</p>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                {!! Form::label('ebill_sms_template', 'SMS Message Template:') !!}
                {!! Form::textarea('common_settings[ebill_settings][sms_template]', $sms_template, ['class' => 'form-control', 'rows' => 4]) !!}
            </div>
        </div>
    </div>

    {{-- WhatsApp --}}
    <div class="box box-solid" style="margin-bottom:16px;border:1px solid #2a2a2a;">
        <div class="box-header with-border" style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h4 class="box-title" style="margin:0;font-size:14px;">Send eBill via WhatsApp</h4>
                <p class="help-block" style="margin:4px 0 0;">WaSender API (wasender.app)</p>
            </div>
            <label class="ebill-switch" style="margin:0;">
                <input type="checkbox" name="common_settings[ebill_settings][enable_whatsapp_ebill]" value="1"
                    class="ebill-toggle" data-target="#whatsapp_ebill_settings"
                    @if($enable_whatsapp_ebill) checked @endif>
                <span class="ebill-slider ebill-green"></span>
            </label>
        </div>
        <div id="whatsapp_ebill_settings" class="box-body @if(!$enable_whatsapp_ebill) hide @endif">
            <div class="form-group">
                {!! Form::label('ebill_wasender_api_token', 'WaSender API Token:') !!}
                {!! Form::text('common_settings[ebill_settings][wasender_api_token]', $wasender_api_token, ['class' => 'form-control', 'placeholder' => 'Bearer token from wasender.app']) !!}
            </div>
            <div class="form-group" style="margin-bottom:0;">
                {!! Form::label('ebill_whatsapp_template', 'WhatsApp Message Template:') !!}
                {!! Form::textarea('common_settings[ebill_settings][whatsapp_template]', $whatsapp_template, ['class' => 'form-control', 'rows' => 4]) !!}
            </div>
        </div>
    </div>

    {{-- Social --}}
    <div class="box box-solid" style="margin-bottom:0;border:1px solid #2a2a2a;">
        <div class="box-header with-border">
            <h4 class="box-title" style="margin:0;font-size:14px;">Social Media Links</h4>
            <p class="help-block" style="margin:4px 0 0;">Shown on the customer eBill page footer</p>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('ebill_social_facebook', 'Facebook:') !!}
                        {!! Form::text('common_settings[ebill_settings][social_facebook]', $social_facebook, ['class' => 'form-control', 'placeholder' => 'https://facebook.com/yourpage']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('ebill_social_instagram', 'Instagram:') !!}
                        {!! Form::text('common_settings[ebill_settings][social_instagram]', $social_instagram, ['class' => 'form-control', 'placeholder' => 'https://instagram.com/yourhandle']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" style="margin-bottom:0;">
                        {!! Form::label('ebill_social_website', 'Website:') !!}
                        {!! Form::text('common_settings[ebill_settings][social_website]', $social_website, ['class' => 'form-control', 'placeholder' => 'https://yourwebsite.com']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.ebill-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.ebill-switch input { opacity: 0; width: 0; height: 0; }
.ebill-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #525252; border-radius: 24px; transition: .25s; }
.ebill-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .25s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.ebill-switch input:checked + .ebill-slider { background: #2563eb; }
.ebill-switch input:checked + .ebill-slider.ebill-green { background: #16a34a; }
.ebill-switch input:checked + .ebill-slider:before { transform: translateX(20px); }
</style>

<script>
$(document).ready(function() {
    $(document).on('change', '.ebill-toggle', function() {
        var target = $(this).data('target');
        if ($(this).is(':checked')) {
            $(target).removeClass('hide tw-hidden');
        } else {
            $(target).addClass('hide');
        }
    });
});
</script>
