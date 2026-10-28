@if(in_array('tyre_shop', session('business.enabled_modules', [])) && (!function_exists('shop_mode_allows') || shop_mode_allows('tyre_shop')))
<div class="pos-tab-content" data-settings-tab="tyre_shop">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-circle"></i> @lang('lang_v1.tyre_shop')</h4>
            <p class="help-block">@lang('lang_v1.tyre_shop_settings_help')</p>
            <ul>
                <li>@lang('lang_v1.tyre_shop_settings_item_products')</li>
                <li>@lang('lang_v1.tyre_shop_settings_item_pos')</li>
                <li>@lang('lang_v1.tyre_shop_settings_item_exchange')</li>
            </ul>
            <p class="text-muted">
                @lang('lang_v1.shop_mode_enable_via_owner')
            </p>
        </div>
    </div>
</div>
@endif
