@extends('layouts.app')
@section('title', __('report.expense_report'))

@section('css')
<style>
    /* Expense report chart — match dark admin theme (Highcharts defaults to white) */
    .expense-report-chart .highcharts-background { fill: transparent !important; }
    .expense-report-chart .highcharts-container { background: transparent !important; }
    .expense-report-chart text,
    .expense-report-chart .highcharts-axis-labels text,
    .expense-report-chart .highcharts-title,
    .expense-report-chart .highcharts-legend-item text {
        fill: #a3a3a3 !important;
    }
    .expense-report-chart .highcharts-title { fill: #f5f5f5 !important; }
    .expense-report-chart .highcharts-axis-line,
    .expense-report-chart .highcharts-grid-line,
    .expense-report-chart .highcharts-tick { stroke: rgba(42, 42, 42, 0.9) !important; }
    .expense-report-empty {
        text-align: center;
        padding: 28px 16px 8px;
        color: #a3a3a3;
        font-size: 14px;
    }
    .expense-report-empty strong {
        color: #f5f5f5;
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
    }
</style>
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold">{{ __('report.expense_report')}}</h1>
    @if(!empty($date_range_display))
        <p class="tw-text-sm tw-text-gray-400 tw-mt-1 tw-mb-0">{{ $date_range_display }}</p>
    @endif
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters'), 'open' => true])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getExpenseReport']), 'method' => 'get' ]) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, $filters['location_id'] ?? null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('category_id', __('category.category').':') !!}
                        {!! Form::select('category', $categories, $filters['category'] ?? null, ['placeholder' =>
                        __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('trending_product_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', $date_range_display ?? null , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'trending_product_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-sm-12">
                  <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white pull-right">@lang('report.apply_filters')</button>
                </div> 
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="expense-report-chart">
                    {!! $chart->container() !!}
                </div>
                @if($expenses->isEmpty())
                    <div class="expense-report-empty">
                        <strong>@lang('lang_v1.no_data')</strong>
                        @lang('report.expense_report') — {{ $date_range_display ?? '' }}
                    </div>
                @endif
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary'])
            <table class="table" id="expense_report_table">
                <thead>
                    <tr>
                        <th>@lang( 'expense.expense_categories' )</th>
                        <th>@lang( 'report.total_expense' )</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total_expense = 0;
                    @endphp
                    @foreach($expenses as $expense)
                        <tr>
                            <td>{{$expense->category ?? __('report.others')}}</td>
                            <td><span class="display_currency" data-currency_symbol="true">{{$expense->total_expense}}</span></td>
                        </tr>
                        @php
                            $total_expense += (float) $expense->total_expense;
                        @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>@lang('sale.total')</td>
                        <td><span class="display_currency" data-currency_symbol="true">{{$total_expense}}</span></td>
                    </tr>
                </tfoot>
            </table>
        @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    {!! $chart->script() !!}
@endsection
