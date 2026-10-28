@extends('layouts.app')
@section('title', __('lang_v1.items_report'))

@section('content')
{{-- Inline so it wins over head CSS (section css yields too early / twice) --}}
<style>
/* Items Report: keep header text aligned with cell values */
body:not(.lockscreen) #items_report_table.dataTable,
body:not(.lockscreen) #items_report_table {
  border-collapse: collapse !important;
  width: 100% !important;
  table-layout: auto !important;
}
body:not(.lockscreen) #items_report_table > thead > tr > th,
body:not(.lockscreen) #items_report_table > tbody > tr > td,
body:not(.lockscreen) #items_report_table > tfoot > tr > td {
  vertical-align: middle !important;
  box-sizing: border-box !important;
  white-space: nowrap !important;
  font-variant-numeric: tabular-nums;
}
/* Match sort-icon gutter on EVERY cell so borders/content stay in sync */
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th,
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th.sorting,
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th.sorting_asc,
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th.sorting_desc,
body:not(.lockscreen) #items_report_table.dataTable tbody > tr > td,
body:not(.lockscreen) #items_report_table.dataTable tfoot > tr > td {
  padding-top: 8px !important;
  padding-bottom: 8px !important;
  padding-left: 10px !important;
  padding-right: 22px !important;
}
/* Left columns */
body:not(.lockscreen) #items_report_table thead > tr > th.text-left,
body:not(.lockscreen) #items_report_table tbody > tr > td.text-left,
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th.text-left,
body:not(.lockscreen) #items_report_table.dataTable tbody > tr > td.text-left {
  text-align: left !important;
}
/* Money / qty columns — header + values both right */
body:not(.lockscreen) #items_report_table thead > tr > th.text-right,
body:not(.lockscreen) #items_report_table tbody > tr > td.text-right,
body:not(.lockscreen) #items_report_table tfoot > tr > td.text-right,
body:not(.lockscreen) #items_report_table.dataTable thead > tr > th.text-right,
body:not(.lockscreen) #items_report_table.dataTable tbody > tr > td.text-right,
body:not(.lockscreen) #items_report_table.dataTable tfoot > tr > td.text-right {
  text-align: right !important;
  padding-left: 22px !important;
  padding-right: 10px !important;
}
/* Sort icon sits in the gutter, not on top of the label */
body:not(.lockscreen) #items_report_table.dataTable thead th.sorting:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.sorting_asc:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.sorting_desc:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.sorting_asc_disabled:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.sorting_desc_disabled:after {
  bottom: 50% !important;
  transform: translateY(50%);
  right: 6px !important;
  left: auto !important;
  margin: 0 !important;
}
body:not(.lockscreen) #items_report_table.dataTable thead th.text-right.sorting:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.text-right.sorting_asc:after,
body:not(.lockscreen) #items_report_table.dataTable thead th.text-right.sorting_desc:after {
  left: 6px !important;
  right: auto !important;
}
body:not(.lockscreen) #items_report_table .ir-empty {
  color: #737373 !important;
  display: inline-block;
  min-width: 1em;
  text-align: inherit;
}
body:not(.lockscreen) #items_report_table td small {
  display: block;
  margin-top: 2px;
  line-height: 1.2;
  white-space: nowrap;
}
body:not(.lockscreen) #items_report_table th:nth-child(1),
body:not(.lockscreen) #items_report_table td:nth-child(1),
body:not(.lockscreen) #items_report_table th:nth-child(3),
body:not(.lockscreen) #items_report_table td:nth-child(3) {
  white-space: normal !important;
  min-width: 140px;
}
body:not(.lockscreen) .items-report-scroll {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.items_report')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ir_supplier_id', __('purchase.supplier') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('ir_supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ir_purchase_date_filter', __('purchase.purchase_date') . ':') !!}
                    {!! Form::text('ir_purchase_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ir_customer_id', __('contact.customer') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('ir_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ir_sale_date_filter', __('lang_v1.sell_date') . ':') !!}
                    {!! Form::text('ir_sale_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ir_location_id', __('purchase.business_location').':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::select('ir_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            </div>
            @if(Module::has('Manufacturing'))
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
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="items-report-scroll">
                    <table class="table table-bordered table-striped" 
                    id="items_report_table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-left">@lang('sale.product')</th>
                                <th class="text-left">@lang('product.sku')</th>
                                <th class="text-left">@lang('lang_v1.description')</th>
                                <th class="text-left">@lang('purchase.purchase_date')</th>
                                <th class="text-left">@lang('lang_v1.purchase')</th>
                                <th class="text-left">@lang('lang_v1.lot_number')</th>
                                <th class="text-left">@lang('purchase.supplier')</th>
                                <th class="text-right">@lang('lang_v1.purchase_price')</th>
                                <th class="text-left">@lang('lang_v1.sell_date')</th>
                                <th class="text-left">@lang('business.sale')</th>
                                <th class="text-left">@lang('contact.customer')</th>
                                <th class="text-left">@lang('sale.location')</th>
                                <th class="text-right">@lang('lang_v1.sell_quantity')</th>
                                <th class="text-right">@lang('lang_v1.selling_price')</th>
                                <th class="text-right">@lang('sale.subtotal')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total">
                                <td colspan="7" class="text-right"><strong>@lang('sale.total'):</strong></td>
                                <td id="footer_total_pp" class="text-right display_currency" data-currency_symbol="true"></td>
                                <td colspan="4"></td>
                                <td id="footer_total_qty" class="text-right"></td>
                                <td id="footer_total_sp" class="text-right display_currency" data-currency_symbol="true"></td>
                                <td id="footer_total_subtotal" class="text-right display_currency" data-currency_symbol="true"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection
