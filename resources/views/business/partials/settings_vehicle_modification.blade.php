@if(in_array('vehicle_modification', session('business.enabled_modules', [])) && function_exists('shop_mode_allows') && shop_mode_allows('vehicle_modification'))
<div class="pos-tab-content" data-settings-tab="vehicle_modification">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-car"></i> @lang('lang_v1.vehicle_modification')</h4>
            <p class="help-block">@lang('lang_v1.vehicle_modification_settings_help')</p>
            <ul>
                <li>@lang('lang_v1.vehicle_modification_settings_item_jobs')</li>
                <li>@lang('lang_v1.vehicle_modification_settings_item_vehicles')</li>
                <li>@lang('lang_v1.vehicle_modification_settings_item_pos')</li>
            </ul>
            <p class="text-muted">@lang('lang_v1.shop_mode_enable_via_owner')</p>
        </div>
    </div>
</div>
@endif
