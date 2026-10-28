@php
    $shop_mode_defs = $shop_mode_defs ?? \App\Utils\ShopModeUtil::definitions();
    $current_shop_mode = $current_shop_mode ?? \App\Utils\ShopModeUtil::current();
@endphp

<div id="shop-mode-box">
<h3 style="margin-top:0;"><i class="fa fa-store"></i> @lang('lang_v1.shop_mode')</h3>
<p class="text-muted" style="margin-top:0;">@lang('lang_v1.shop_mode_help')</p>

<div class="alert alert-info">
    <strong>@lang('lang_v1.current_shop_mode'):</strong>
    {{ $shop_mode_defs[$current_shop_mode]['label'] ?? $current_shop_mode }}
</div>

{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postShopMode']), 'method' => 'post', 'style' => 'margin-bottom: 24px;']) !!}
<div class="row">
    @foreach($shop_mode_defs as $key => $def)
        <div class="col-md-6" style="margin-bottom: 10px;">
            <label style="display:block;font-weight:normal;border:1px solid #2a2a2a;border-radius:8px;padding:12px;cursor:pointer;@if($current_shop_mode === $key) background:#1f1a08;border-color:#f5c518; @endif">
                <input type="radio" name="shop_mode" value="{{ $key }}"
                    @if($current_shop_mode === $key) checked @endif
                    style="margin-right:8px;">
                <strong>{{ $def['label'] }}</strong>
                <p class="help-block" style="margin:6px 0 0 22px;">{{ $def['help'] }}</p>
            </label>
        </div>
    @endforeach
</div>
<p class="help-block">@lang('lang_v1.shop_mode_switch_warning')</p>
<button type="submit" class="btn btn-primary"
    onclick="return confirm(@json(__('lang_v1.shop_mode_switch_confirm')));">
    <i class="fa fa-save"></i> @lang('lang_v1.save_shop_mode')
</button>
{!! Form::close() !!}
</div>
<hr>
