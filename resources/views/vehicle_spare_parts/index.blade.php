@extends('layouts.app')
@section('title', __('lang_v1.vehicle_spare_parts'))

@section('content')
<section class="content-header">
    <h1><i class="fa fa-cogs"></i> @lang('lang_v1.vehicle_spare_parts')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}" class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.products')</span>
                    <span class="info-box-number">@lang('product.part_number')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}" class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-th-large"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.pos_sale')</span>
                    <span class="info-box-number">@lang('lang_v1.search_by_part_oem')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}" class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-plus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('product.add_new_product')</span>
                    <span class="info-box-number">@lang('lang_v1.spare_part')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-chart-bar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('report.stock_report')</span>
                    <span class="info-box-number">@lang('lang_v1.vehicle_spare_parts')</span>
                </div>
            </a>
        </div>
    </div>
    <div class="box box-solid">
        <div class="box-body">
            <p class="help-block" style="margin:0;">@lang('lang_v1.vehicle_spare_parts_dashboard_help')</p>
        </div>
    </div>
</section>
@endsection
