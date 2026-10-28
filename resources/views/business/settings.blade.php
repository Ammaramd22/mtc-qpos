@extends('layouts.app')
@section('title', __('business.business_settings'))

@section('content')
@php
    $is_software_owner = $is_software_owner ?? (auth()->check() && method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner());
@endphp

<section class="content-header upos-settings-header">
    <h1>@lang('business.business_settings')</h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> @lang('home.home')</a></li>
        <li class="active">@lang('business.business_settings')</li>
    </ol>
</section>

<section class="content upos-settings-page">

{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']), 'method' => 'post', 'id' => 'bussiness_edit_form', 'files' => true, 'novalidate' => true ]) !!}

    <div class="upos-settings-search">
        @include('layouts.partials.search_settings')
    </div>

    <div class="box box-solid upos-settings-box pos-tab-container">
        <div class="box-header with-border upos-settings-box-head">
            <h3 class="box-title"><i class="fa fa-cogs"></i> @lang('business.business_settings')</h3>
        </div>
        <div class="box-body no-padding upos-settings-body">
            <div class="row" style="margin:0;">
                <div class="col-lg-3 col-md-3 col-sm-4 col-xs-12 pos-tab-menu upos-settings-nav">
                    <div class="list-group upos-settings-nav-list">
                        @if(can_access_settings_option('business'))
                        <a href="#" class="list-group-item active" data-settings-tab="business"><i class="fa fa-briefcase"></i><span>@lang('business.business')</span></a>
                        @endif
                        @if(can_access_settings_option('tax'))
                        <a href="#" class="list-group-item" data-settings-tab="tax"><i class="fa fa-percent"></i><span>@lang('business.tax')</span></a>
                        @endif
                        @if(can_access_settings_option('product'))
                        <a href="#" class="list-group-item" data-settings-tab="product"><i class="fa fa-cubes"></i><span>@lang('business.product')</span></a>
                        @endif
                        @if(can_access_settings_option('contact'))
                        <a href="#" class="list-group-item" data-settings-tab="contact"><i class="fa fa-address-book"></i><span>@lang('contact.contact')</span></a>
                        @endif
                        @if(can_access_settings_option('sale'))
                        <a href="#" class="list-group-item" data-settings-tab="sale"><i class="fa fa-shopping-cart"></i><span>@lang('business.sale')</span></a>
                        @endif
                        @if(can_access_settings_option('pos'))
                        <a href="#" class="list-group-item" data-settings-tab="pos"><i class="fa fa-th-large"></i><span>@lang('sale.pos_sale')</span></a>
                        @endif
                        @if(can_access_settings_option('display_screen'))
                        <a href="#" class="list-group-item" data-settings-tab="display_screen"><i class="fa fa-desktop"></i><span>@lang('lang_v1.display_screen')</span></a>
                        @endif
                        @if(can_access_settings_option('purchases'))
                        <a href="#" class="list-group-item" data-settings-tab="purchases"><i class="fa fa-truck"></i><span>@lang('purchase.purchases')</span></a>
                        @endif
                        @if(can_access_settings_option('payment'))
                        <a href="#" class="list-group-item" data-settings-tab="payment"><i class="fa fa-credit-card"></i><span>@lang('lang_v1.payment')</span></a>
                        @endif
                        @if(can_access_settings_option('dashboard'))
                        <a href="#" class="list-group-item" data-settings-tab="dashboard"><i class="fa fa-dashboard"></i><span>@lang('business.dashboard')</span></a>
                        @endif
                        @if(can_access_settings_option('system'))
                        <a href="#" class="list-group-item" data-settings-tab="system"><i class="fa fa-sliders"></i><span>@lang('business.system')</span></a>
                        @endif
                        @if(can_access_settings_option('prefixes'))
                        <a href="#" class="list-group-item" data-settings-tab="prefixes"><i class="fa fa-hashtag"></i><span>@lang('lang_v1.prefixes')</span></a>
                        @endif
                        @if(can_access_settings_option('email_settings'))
                        <a href="#" class="list-group-item" data-settings-tab="email_settings"><i class="fa fa-envelope"></i><span>@lang('lang_v1.email_settings')</span></a>
                        @endif
                        @if(can_access_settings_option('sms_settings'))
                        <a href="#" class="list-group-item" data-settings-tab="sms_settings"><i class="fa fa-commenting"></i><span>@lang('lang_v1.sms_settings')</span></a>
                        @endif
                        @if(can_access_settings_option('reward_point_settings'))
                        <a href="#" class="list-group-item" data-settings-tab="reward_point_settings"><i class="fa fa-gift"></i><span>@lang('lang_v1.reward_point_settings')</span></a>
                        @endif
                        @if($is_software_owner)
                        <a href="#" class="list-group-item" data-settings-tab="shop_mode" style="border-left: 3px solid #f5c518;"><i class="fa fa-store"></i><span>@lang('lang_v1.shop_mode')</span></a>
                        @endif
                        {{-- Modules for shop users is managed in Soft Owner Access; Soft Owner may still open Modules --}}
                        @if($is_software_owner)
                        <a href="#" class="list-group-item" data-settings-tab="modules"><i class="fa fa-puzzle-piece"></i><span>@lang('lang_v1.modules')</span></a>
                        @endif
                        @if(can_access_settings_option('mobile_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('mobile_shop')))
                        <a href="#" class="list-group-item" data-settings-tab="mobile_shop"><i class="fa fa-mobile-alt"></i><span>@lang('lang_v1.mobile_shop')</span></a>
                        @endif
                        @if(can_access_settings_option('tyre_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('tyre_shop')))
                        <a href="#" class="list-group-item" data-settings-tab="tyre_shop"><i class="fa fa-circle"></i><span>@lang('lang_v1.tyre_shop')</span></a>
                        @endif
                        @if(can_access_settings_option('vehicle_modification') && (!function_exists('shop_mode_allows') || shop_mode_allows('vehicle_modification')))
                        <a href="#" class="list-group-item" data-settings-tab="vehicle_modification"><i class="fa fa-wrench"></i><span>@lang('lang_v1.vehicle_modification')</span></a>
                        @endif
                        @if(can_access_settings_option('vehicle_spare_parts') && (!function_exists('shop_mode_allows') || shop_mode_allows('vehicle_spare_parts')))
                        <a href="#" class="list-group-item" data-settings-tab="vehicle_spare_parts"><i class="fa fa-cogs"></i><span>@lang('lang_v1.vehicle_spare_parts')</span></a>
                        @endif
                        @if(can_access_settings_option('hardware_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('hardware_shop')))
                        <a href="#" class="list-group-item" data-settings-tab="hardware_shop"><i class="fa fa-hammer"></i><span>@lang('lang_v1.hardware_shop')</span></a>
                        @endif
                        @if(can_access_settings_option('reload'))
                        <a href="#" class="list-group-item" data-settings-tab="reload"><i class="fa fa-bolt"></i><span>@lang('lang_v1.reload_module')</span></a>
                        @endif
                        @if(can_access_settings_option('custom_labels'))
                        <a href="#" class="list-group-item" data-settings-tab="custom_labels"><i class="fa fa-tags"></i><span>@lang('lang_v1.custom_labels')</span></a>
                        @endif
                        @if(can_access_settings_option('ebill'))
                        <a href="#" class="list-group-item" data-settings-tab="ebill"><i class="fa fa-paper-plane"></i><span>eBill</span></a>
                        @endif
                        @if(can_access_settings_option('bartender'))
                        <a href="#" class="list-group-item" data-settings-tab="bartender"><i class="fa fa-barcode"></i><span>@lang('lang_v1.bartender')</span></a>
                        @endif
                        @if(can_access_settings_option('weighing_scale_label'))
                        <a href="#" class="list-group-item" data-settings-tab="weighing_scale_label"><i class="fa fa-balance-scale"></i><span>@lang('lang_v1.weighing_scale_label')</span></a>
                        @endif
                        @if(can_access_settings_option('weighing_scale_barcode_scan'))
                        <a href="#" class="list-group-item" data-settings-tab="weighing_scale_barcode_scan"><i class="fa fa-barcode"></i><span>@lang('lang_v1.weighing_scale_barcode_scan')</span></a>
                        @endif
                        @if(can_access_settings_option('costcode'))
                        <a href="#" class="list-group-item" data-settings-tab="costcode"><i class="fa fa-qrcode"></i><span>@lang('lang_v1.costcode')</span></a>
                        @endif
                        @if(can_access_settings_option('offline_billing'))
                        <a href="#" class="list-group-item" data-settings-tab="offline_billing"><i class="fa fa-wifi"></i><span>@lang('lang_v1.offline_billing')</span></a>
                        @endif
                        @if(can_access_settings_option('pwa'))
                        <a href="#" class="list-group-item" data-settings-tab="pwa"><i class="fa fa-mobile"></i><span>@lang('lang_v1.pwa')</span></a>
                        @endif
                        @if($is_software_owner)
                        <a href="#" class="list-group-item" data-settings-tab="software_owner" style="border-left: 3px solid #f5c518;"><i class="fa fa-user-shield"></i><span>@lang('lang_v1.software_owner_access')</span></a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-9 col-md-9 col-sm-8 col-xs-12 pos-tab upos-settings-content">
                    @if(can_access_settings_option('business'))
                        @include('business.partials.settings_business')
                    @endif
                    @if(can_access_settings_option('tax'))
                        @include('business.partials.settings_tax')
                    @endif
                    @if(can_access_settings_option('product'))
                        @include('business.partials.settings_product')
                    @endif
                    @if(can_access_settings_option('contact'))
                        @include('business.partials.settings_contact')
                    @endif
                    @if(can_access_settings_option('sale'))
                        @include('business.partials.settings_sales')
                    @endif
                    @if(can_access_settings_option('pos'))
                        @include('business.partials.settings_pos')
                    @endif
                    @if(can_access_settings_option('display_screen'))
                        @include('business.partials.settings_display_pos')
                    @endif
                    @if(can_access_settings_option('purchases'))
                        @include('business.partials.settings_purchase')
                    @endif
                    @if(can_access_settings_option('payment'))
                        @include('business.partials.settings_payment')
                    @endif
                    @if(can_access_settings_option('dashboard'))
                        @include('business.partials.settings_dashboard')
                    @endif
                    @if(can_access_settings_option('system'))
                        @include('business.partials.settings_system')
                    @endif
                    @if(can_access_settings_option('prefixes'))
                        @include('business.partials.settings_prefixes')
                    @endif
                    @if(can_access_settings_option('email_settings'))
                        @include('business.partials.settings_email')
                    @endif
                    @if(can_access_settings_option('sms_settings'))
                        @include('business.partials.settings_sms')
                    @endif
                    @if(can_access_settings_option('reward_point_settings'))
                        @include('business.partials.settings_reward_point')
                    @endif
                    @if($is_software_owner)
                        <div class="pos-tab-content" data-settings-tab="shop_mode">
                            <div class="alert alert-info">
                                <i class="fa fa-store"></i>
                                <strong>@lang('lang_v1.shop_mode')</strong>
                                <p style="margin:8px 0 0;">@lang('lang_v1.shop_mode_help')</p>
                                <p style="margin:8px 0 0;">
                                    <a href="#shop-mode-box" class="btn btn-sm btn-primary">
                                        <i class="fa fa-sliders"></i> @lang('lang_v1.configure_shop_mode')
                                    </a>
                                </p>
                            </div>
                        </div>
                    @endif
                    @if($is_software_owner)
                        @include('business.partials.settings_modules')
                    @endif
                    @if(can_access_settings_option('mobile_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('mobile_shop')))
                        @include('business.partials.settings_mobile_shop')
                    @endif
                    @if(can_access_settings_option('tyre_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('tyre_shop')))
                        @include('business.partials.settings_tyre_shop')
                    @endif
                    @if(can_access_settings_option('vehicle_modification') && (!function_exists('shop_mode_allows') || shop_mode_allows('vehicle_modification')))
                        @include('business.partials.settings_vehicle_modification')
                    @endif
                    @if(can_access_settings_option('vehicle_spare_parts') && (!function_exists('shop_mode_allows') || shop_mode_allows('vehicle_spare_parts')))
                        @include('business.partials.settings_vehicle_spare_parts')
                    @endif
                    @if(can_access_settings_option('hardware_shop') && (!function_exists('shop_mode_allows') || shop_mode_allows('hardware_shop')))
                        @include('business.partials.settings_hardware_shop')
                    @endif
                    @if(can_access_settings_option('reload'))
                        @include('business.partials.settings_reload')
                    @endif
                    @if(can_access_settings_option('custom_labels'))
                        @include('business.partials.settings_custom_labels')
                    @endif
                    @if(can_access_settings_option('ebill'))
                        @include('business.partials.settings_ebill')
                    @endif
                    @if(can_access_settings_option('bartender'))
                        @include('business.partials.settings_bartender')
                    @endif
                    @if(can_access_settings_option('weighing_scale_label'))
                        @include('business.partials.settings_weighing_scale_label')
                    @endif
                    @if(can_access_settings_option('weighing_scale_barcode_scan'))
                        @include('business.partials.settings_weighing_scale')
                    @endif
                    @if(can_access_settings_option('costcode'))
                        @include('business.partials.settings_costcode')
                    @endif
                    @if(can_access_settings_option('offline_billing'))
                        @include('business.partials.settings_offline')
                    @endif
                    @if(can_access_settings_option('pwa'))
                        @include('business.partials.settings_pwa')
                    @endif
                    @if($is_software_owner)
                        @include('business.partials.settings_software_owner')
                    @endif
                </div>
            </div>
        </div>
        <div class="box-footer text-center upos-settings-footer">
            <button class="btn btn-success btn-lg upos-settings-save" type="button" id="btn_force_save_settings"
                data-label="@lang('business.update_settings')"
                onclick="if(window.__qposForceSaveSettings){window.__qposForceSaveSettings(event);}">
                <i class="fa fa-check"></i> @lang('business.update_settings')
            </button>
        </div>
    </div>

{!! Form::close() !!}

@if($is_software_owner)
{{-- Separate form (not nested) for access toggles --}}
<div class="box box-solid" style="margin-top:15px;" id="software-owner-access-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-user-shield"></i> @lang('lang_v1.software_owner_access')</h3>
    </div>
    <div class="box-body">
        @include('business.partials.settings_software_owner_form')
    </div>
</div>
@endif
</section>
@stop
@section('javascript')
<script type="text/javascript">
    document.getElementById('bussiness_edit_form') &&
        document.getElementById('bussiness_edit_form').setAttribute('data-success-url', @json(url('business/settings')));
</script>
<script src="{{ asset('js/settings-save-v7.js?v=812') }}"></script>
<script type="text/javascript">
    // Guard: page helpers need jQuery; settings saver does not.
    if (typeof window.jQuery === 'undefined') {
        console.error('QPOS: jQuery still missing. Set APP_URL=https://YOUR-DOMAIN (no /public) in .env');
    } else {
        __page_leave_confirmation('#bussiness_edit_form');
        $(document).on('ifToggled', '#use_superadmin_settings', function() {
            if ($('#use_superadmin_settings').is(':checked')) {
                $('#toggle_visibility').addClass('hide');
                $('.test_email_btn').addClass('hide');
            } else {
                $('#toggle_visibility').removeClass('hide');
                $('.test_email_btn').removeClass('hide');
            }
        });

        $(document).ready(function(){

            $(document).on('ifToggled', '#enable_tax_system', function() {
                if ($('#enable_tax_system').is(':checked')) {
                    $('#tax_system_options').removeClass('hide');
                    $('#enable_price_tax_wrapper').removeClass('hide');
                } else {
                    $('#tax_system_options').addClass('hide');
                    $('#enable_price_tax_wrapper').addClass('hide');
                }
            });

            $('#test_email_btn').click( function() {
                var data = {
                    mail_driver: $('#mail_driver').val(),
                    mail_host: $('#mail_host').val(),
                    mail_port: $('#mail_port').val(),
                    mail_username: $('#mail_username').val(),
                    mail_password: $('#mail_password').val(),
                    mail_encryption: $('#mail_encryption').val(),
                    mail_from_address: $('#mail_from_address').val(),
                    mail_from_name: $('#mail_from_name').val(),
                };
                $.ajax({
                    method: 'post',
                    data: data,
                    url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testEmailConfiguration']) }}",
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            swal({ text: result.msg, icon: 'success' });
                        } else {
                            swal({ text: result.msg, icon: 'error' });
                        }
                    },
                });
            });

            function collectSmsSettingsForTest(testNumber) {
                return {
                    sms_service: $('#sms_service').val(),
                    smslenz_user_id: $('#smslenz_user_id').val(),
                    smslenz_api_key: $('#smslenz_api_key').val(),
                    smslenz_sender_id: $('#smslenz_sender_id').val(),
                    notifylk_user_id: $('#notifylk_user_id').val(),
                    notifylk_api_key: $('#notifylk_api_key').val(),
                    notifylk_sender_id: $('#notifylk_sender_id').val(),
                    nexmo_key: $('#nexmo_key').val(),
                    nexmo_secret: $('#nexmo_secret').val(),
                    nexmo_from: $('#nexmo_from').val(),
                    twilio_sid: $('#twilio_sid').val(),
                    twilio_token: $('#twilio_token').val(),
                    twilio_from: $('#twilio_from').val(),
                    url: $('#sms_settings_url').val(),
                    send_to_param_name: $('#send_to_param_name').val(),
                    msg_param_name: $('#msg_param_name').val(),
                    request_method: $('#request_method').val(),
                    param_1: $('#sms_settings_param_key1').val(),
                    param_2: $('#sms_settings_param_key2').val(),
                    param_3: $('#sms_settings_param_key3').val(),
                    param_4: $('#sms_settings_param_key4').val(),
                    param_5: $('#sms_settings_param_key5').val(),
                    param_6: $('#sms_settings_param_key6').val(),
                    param_7: $('#sms_settings_param_key7').val(),
                    param_8: $('#sms_settings_param_key8').val(),
                    param_9: $('#sms_settings_param_key9').val(),
                    param_10: $('#sms_settings_param_key10').val(),
                    param_val_1: $('#sms_settings_param_val1').val(),
                    param_val_2: $('#sms_settings_param_val2').val(),
                    param_val_3: $('#sms_settings_param_val3').val(),
                    param_val_4: $('#sms_settings_param_val4').val(),
                    param_val_5: $('#sms_settings_param_val5').val(),
                    param_val_6: $('#sms_settings_param_val6').val(),
                    param_val_7: $('#sms_settings_param_val7').val(),
                    param_val_8: $('#sms_settings_param_val8').val(),
                    param_val_9: $('#sms_settings_param_val9').val(),
                    param_val_10: $('#sms_settings_param_val10').val(),
                    test_number: testNumber,
                    header_1: $('#sms_settings_header_key1').val(),
                    header_val_1: $('#sms_settings_header_val1').val(),
                    header_2: $('#sms_settings_header_key2').val(),
                    header_val_2: $('#sms_settings_header_val2').val(),
                    header_3: $('#sms_settings_header_key3').val(),
                    header_val_3: $('#sms_settings_header_val3').val(),
                    data_parameter_type: $('#data_parameter_type').val(),
                };
            }

            function runSmsTest(testNumber) {
                if (!testNumber || testNumber.trim() === '') {
                    toastr.error('{{__("lang_v1.test_number_is_required")}}');
                    return;
                }
                $.ajax({
                    method: 'post',
                    data: collectSmsSettingsForTest(testNumber.trim()),
                    url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testSmsConfiguration']) }}",
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true || result.success == 1) {
                            swal({ text: result.msg || 'SMS sent successfully', icon: 'success' });
                        } else {
                            swal({ text: result.msg || 'Failed', icon: 'error' });
                        }
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'SMS test failed';
                        swal({ text: msg, icon: 'error' });
                    }
                });
            }

            $('#test_sms_btn').click(function() {
                runSmsTest($('#test_number').val());
            });

            $('#test_sms_btn_smslenz').click(function() {
                runSmsTest($('#test_number_smslenz').val());
            });

            $('select.custom_labels_products').change(function(){
                value = $(this).val();
                textarea = $(this).parents('div.custom_label_product_div').find('div.custom_label_product_dropdown');
                if(value == 'dropdown'){
                    textarea.removeClass('hide');
                } else{
                    textarea.addClass('hide');
                }
            });

            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: 'textarea#display_screen_heading',
                    height: 250
                });
            }

            if ($.fn.fileinput) {
                $('.carousel_image').fileinput({
                    showUpload: true,
                    showPreview: true,
                    browseLabel: LANG.file_browse_label,
                    removeLabel: LANG.remove,
                });
            }

            if (window.location.hash) {
                var tabId = window.location.hash.substring(1);
                var $tabContent = $('#' + tabId);
                if ($tabContent.length && $tabContent.hasClass('pos-tab-content')) {
                    var tab = $tabContent.data('settings-tab');
                    $('div.pos-tab-menu>div.list-group>a').removeClass('active');
                    if (tab) {
                        $('div.pos-tab-menu>div.list-group>a[data-settings-tab="' + tab + '"]').addClass('active');
                    } else {
                        $('div.pos-tab-menu>div.list-group>a').eq($tabContent.index()).addClass('active');
                    }
                    $('div.pos-tab>div.pos-tab-content').removeClass('active');
                    $tabContent.addClass('active');
                }
            }
        });
    }
</script>
@endsection
