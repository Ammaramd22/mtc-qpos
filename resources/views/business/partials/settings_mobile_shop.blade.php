@if(in_array('mobile_shop', session('business.enabled_modules', [])) && (!function_exists('shop_mode_allows') || shop_mode_allows('mobile_shop')))
<div class="pos-tab-content" data-settings-tab="mobile_shop">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-mobile-alt"></i> @lang('lang_v1.mobile_shop')</h4>
            <p class="help-block">@lang('lang_v1.mobile_shop_settings_help')</p>
            <div class="alert alert-info">
                <i class="fa fa-bolt"></i>
                @lang('lang_v1.mobile_shop_reload_moved_help')
                <strong>@lang('lang_v1.reload_module')</strong>
                (@lang('business.settings') → @lang('lang_v1.reload_module')).
            </div>
        </div>
    </div>
</div>
@endif
