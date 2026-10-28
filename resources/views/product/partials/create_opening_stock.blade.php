@can('product.opening_stock')
<div class="row" id="quick_product_opening_stock_div" style="@if(empty($duplicate_product) || (!empty($duplicate_product) && $duplicate_product->enable_stock == 1)) @else display:none; @endif">
    <div class="col-sm-12">
        <h4 style="margin-top:0;">@lang('product.opening_stock_on_create')</h4>
        <p class="help-block" style="margin-top:-6px;"><i>@lang('product.opening_stock_on_create_help')</i></p>
    </div>
    <div class="col-sm-12">
        <div class="table-responsive">
            <table class="table table-condensed table-th-green" id="quick_product_opening_stock_table">
                <thead>
                    <tr>
                        <th>@lang('sale.location')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('product.default_purchase_price')</th>
                        @if(!empty($enable_expiry))
                            <th>@lang('lang_v1.expiry_date')</th>
                        @endif
                        @if(!empty($enable_lot))
                            <th>@lang('lang_v1.lot_number')</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $key => $value)
                        <tr>
                            <td>{{ $value }}</td>
                            <td>
                                {!! Form::text('opening_stock[' . $key . '][quantity]', null, [
                                    'class' => 'form-control input-sm input_number purchase_quantity',
                                    'placeholder' => '0',
                                ]) !!}
                            </td>
                            <td>
                                {!! Form::text('opening_stock[' . $key . '][purchase_price]', null, [
                                    'class' => 'form-control input-sm input_number unit_price opening_stock_cost',
                                    'placeholder' => __('product.default_purchase_price'),
                                ]) !!}
                            </td>
                            @if(!empty($enable_expiry))
                                <td>
                                    {!! Form::text('opening_stock[' . $key . '][exp_date]', null, [
                                        'class' => 'form-control input-sm os_exp_date',
                                        'readonly',
                                    ]) !!}
                                </td>
                            @endif
                            @if(!empty($enable_lot))
                                <td>
                                    {!! Form::text('opening_stock[' . $key . '][lot_number]', null, [
                                        'class' => 'form-control input-sm',
                                    ]) !!}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endcan
