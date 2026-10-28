@extends('layouts.app')
@section('title', __('lang_v1.tyre_shop'))

@section('content')
<section class="content-header">
    <h1><i class="fa fa-circle"></i> @lang('lang_v1.tyre_shop')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}" class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.products')</span>
                    <span class="info-box-number">@lang('product.tyre_size')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}" class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-th-large"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.pos_sale')</span>
                    <span class="info-box-number">@lang('lang_v1.tyre_quick_services')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-chart-bar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('report.stock_report')</span>
                    <span class="info-box-number">@lang('product.tyre_size')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) }}#tyre_shop" class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-cog"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('business.settings')</span>
                    <span class="info-box-number">@lang('lang_v1.tyre_shop')</span>
                </div>
            </a>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <p class="help-block" style="margin:0;">@lang('lang_v1.tyre_shop_dashboard_help')</p>
        </div>
    </div>
</section>
@endsection
