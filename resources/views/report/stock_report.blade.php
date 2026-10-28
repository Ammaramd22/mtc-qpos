@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report.stock_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'stock_report_filter_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('category_id', __('category.category') . ':') !!}
                        {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'sub_category_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand', $brands, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit',__('product.unit') . ':') !!}
                        {!! Form::select('unit', $units, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                @if($show_manufacturing_data)
                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <div class="checkbox">
                                <label>
                                  {!! Form::checkbox('only_mfg', 1, false, 
                                  [ 'class' => 'input-icheck', 'id' => 'only_mfg_products']); !!} {{ __('manufacturing::lang.only_mfg_products') }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    @can('view_product_stock_value')
    @php
        $stock_cost_label = (session('business.accounting_method') === 'avco')
            ? __('report.closing_stock').' ('.__('lang_v1.by_average_cost').')'
            : __('report.closing_stock').' ('.__('lang_v1.by_purchase_price').')';
    @endphp
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
            <div class="row vspos-stock-value-cards" style="margin:0;">
                <div class="col-sm-6 col-md-3" style="margin-bottom:12px;">
                    <div class="vspos-svc">
                        <div class="vspos-svc-label">{{ $stock_cost_label }}</div>
                        <div class="vspos-svc-value" id="closing_stock_by_pp">—</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom:12px;">
                    <div class="vspos-svc">
                        <div class="vspos-svc-label">@lang('report.closing_stock') (@lang('lang_v1.by_sale_price'))</div>
                        <div class="vspos-svc-value" id="closing_stock_by_sp">—</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom:12px;">
                    <div class="vspos-svc">
                        <div class="vspos-svc-label">@lang('lang_v1.potential_profit')</div>
                        <div class="vspos-svc-value" id="potential_profit">—</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom:12px;">
                    <div class="vspos-svc">
                        <div class="vspos-svc-label">@lang('lang_v1.profit_margin') %</div>
                        <div class="vspos-svc-value" id="profit_margin">—</div>
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    <style>
        .vspos-svc {
            background: #111;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            padding: 14px 16px;
            min-height: 84px;
        }
        .vspos-svc-label {
            font-size: 12px;
            color: #a3a3a3;
            margin-bottom: 8px;
            line-height: 1.35;
            min-height: 32px;
        }
        .vspos-svc-value {
            font-size: 22px;
            font-weight: 700;
            color: #f5f5f5;
            letter-spacing: 0.2px;
            word-break: break-word;
        }
    </style>
    @endcan
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid'])
                @include('report.partials.stock_report_table')
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection