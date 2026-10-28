@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.import_products')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>  
        </div>     
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
                {!! Form::open(['url' => action([\App\Http\Controllers\ImportProductsController::class, 'store']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                                {!! Form::file('products_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                              </div>
                        </div>
                        <div class="col-sm-4">
                        <br>
                            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.submit')</button>
                        </div>
                        </div>
                    </div>

                {!! Form::close() !!}
                <br>
                <div class="row">
                    <div class="col-sm-12">
                        <a href="{{ asset('files/import_products_simple_template.csv') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white" download>
                            <i class="fa fa-download"></i> Download template
                        </a>
                        <a href="{{ asset('files/import_products_simple_sample.csv') }}" class="tw-dw-btn tw-dw-btn-info tw-text-white" download style="margin-left:8px;">
                            <i class="fa fa-file-excel-o"></i> Download sample (filled)
                        </a>
                        <a href="{{ asset('files/import_products_csv_template.xls') }}" class="tw-dw-btn tw-dw-btn-ghost tw-text-white" download style="margin-left:8px;" title="Old full template">
                            <i class="fa fa-download"></i> Advanced template
                        </a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
                <div class="alert alert-info" style="margin-bottom:15px;">
                    <strong>Simple format (recommended)</strong><br>
                    Only these columns are required. Unit defaults to <code>Pc(s)</code>, stock is managed, barcode is used as SKU.
                    Leave <strong>Location</strong> blank to use your default store. Leave <strong>Opening Stock</strong> blank if none.
                </div>
                <table class="table table-striped table-bordered">
                    <tr>
                        <th style="width:70px;">@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Barcode <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Product barcode / SKU. Must be unique.</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Item Name <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Product name.</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Supplier <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Supplier name. Created automatically if it does not exist.</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Brand <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Brand name. Created automatically if missing.</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Category <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Category name. Created automatically if missing.</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>Sub Category <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Sub category under the category above.</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>Cost <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Purchase / cost price (numbers only, e.g. <code>3800</code>).</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>MRP <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Selling price / MRP.</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>Opening Stock <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Starting quantity. Leave blank for 0.</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>Location <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Business location name. <strong>Leave blank</strong> to use the default location.</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>Tyre Size <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Tyre size for tyre products (e.g. <code>205/55R16</code>). Leave blank for non-tyre items.</td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>Note <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Extra tyre note (e.g. <code>Jambo</code>, <code>Primacy 4</code>). Used with Tyre Size + Brand to build Item Name when present.</td>
                    </tr>
                </table>
                <p class="text-muted" style="margin-top:10px;">
                    Tip: Fill the sample file, save as CSV / Excel, then upload. When you send a customer sheet, we can map it into this template.
                </p>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection
