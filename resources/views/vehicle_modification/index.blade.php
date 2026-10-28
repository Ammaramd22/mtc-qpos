@extends('layouts.app')
@section('title', __('lang_v1.vehicle_modification'))

@section('content')
<section class="content-header">
    <h1><i class="fa fa-car"></i> @lang('lang_v1.vehicle_modification')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'jobIndex']) }}" class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-wrench"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.workshop_jobs')</span>
                    <span class="info-box-number">{{ $open_jobs }} @lang('lang_v1.open')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'vehicleIndex']) }}" class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-car"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('lang_v1.customer_vehicles')</span>
                    <span class="info-box-number">{{ $vehicles }}</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}" class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.products')</span>
                    <span class="info-box-number">@lang('lang_v1.parts_and_accessories')</span>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}" class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-th-large"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sale.pos_sale')</span>
                    <span class="info-box-number">@lang('lang_v1.workshop_services')</span>
                </div>
            </a>
        </div>
    </div>
    <div class="box box-solid">
        <div class="box-body">
            <p class="help-block" style="margin:0;">@lang('lang_v1.vehicle_modification_dashboard_help')</p>
        </div>
    </div>
</section>
@endsection
