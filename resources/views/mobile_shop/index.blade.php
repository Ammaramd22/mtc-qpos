@extends('layouts.app')
@section('title', __('lang_v1.mobile_shop'))

@section('content')
<section class="content-header">
    <h1><i class="fa fa-mobile-alt"></i> @lang('lang_v1.mobile_shop')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'imeiIndex']) }}" class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-barcode"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.mobile_imei_inventory')</span>
                    <span class="info-box-number">@lang('messages.view')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'warrantyLookup']) }}" class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-shield-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.mobile_warranty_lookup')</span>
                    <span class="info-box-number">@lang('lang_v1.search')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\MobileShopController::class, 'repairIndex']) }}" class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-tools"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.mobile_repairs')</span>
                    <span class="info-box-number">@lang('messages.view')</span>
                </div>
            </a>
        </div>
        @if(in_array('reload', session('business.enabled_modules', [])))
        <div class="col-md-3 col-sm-6">
            <a href="{{ url('/reload') }}" class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-bolt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.reload_module')</span>
                    <span class="info-box-number">MyReload</span>
                </div>
            </a>
        </div>
        @endif
    </div>
</section>
@endsection
