@if(in_array('hardware_shop', session('business.enabled_modules', [])) && function_exists('shop_mode_allows') && shop_mode_allows('hardware_shop'))
<div class="pos-tab-content" data-settings-tab="hardware_shop">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-hammer"></i> @lang('lang_v1.hardware_shop')</h4>
            <p class="help-block">@lang('lang_v1.hardware_shop_settings_help')</p>
            <p class="text-muted">@lang('lang_v1.shop_mode_enable_via_owner')</p>
        </div>
    </div>
</div>
@endif
