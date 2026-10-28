@if(in_array('vehicle_spare_parts', session('business.enabled_modules', [])) && function_exists('shop_mode_allows') && shop_mode_allows('vehicle_spare_parts'))
<div class="pos-tab-content" data-settings-tab="vehicle_spare_parts">
    <div class="row">
        <div class="col-sm-12">
            <h4><i class="fa fa-cogs"></i> @lang('lang_v1.vehicle_spare_parts')</h4>
            <p class="help-block">@lang('lang_v1.vehicle_spare_parts_settings_help')</p>
            <p class="text-muted">@lang('lang_v1.shop_mode_enable_via_owner')</p>
        </div>
    </div>
</div>
@endif
