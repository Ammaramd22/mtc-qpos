<div class="pos-tab-content" data-settings-tab="modules">
	<div class="row">
	@php
		$industry_module_keys = class_exists(\App\Utils\ShopModeUtil::class)
			? \App\Utils\ShopModeUtil::industryModuleKeys()
			: ['tyre_shop', 'mobile_shop'];
		$biz_modules = is_array($business->enabled_modules ?? null) ? $business->enabled_modules : ($enabled_modules ?? []);
	@endphp
	@if(!empty($modules))
		<h4>@lang('lang_v1.enable_disable_modules')</h4>
		<p class="help-block">@lang('lang_v1.shop_mode_modules_hint')</p>
		@foreach($industry_module_keys as $ind_key)
			@if(in_array($ind_key, $biz_modules, true))
				{!! Form::hidden('enabled_modules[]', $ind_key) !!}
			@endif
		@endforeach
		@foreach($modules as $k => $v)
			@if(in_array($k, $industry_module_keys, true))
				@continue
			@endif
            <div class="col-sm-4">
                <div class="form-group">
                    <div class="checkbox">
                    <br>
                      <label>
                        {!! Form::checkbox('enabled_modules[]', $k,  in_array($k, $biz_modules) , 
                        ['class' => 'input-icheck']); !!} {{$v['name']}}
                      </label>
                      @if(!empty($v['tooltip'])) @show_tooltip($v['tooltip']) @endif
                    </div>
                </div>
            </div>
        @endforeach
	@endif
	</div>
</div>