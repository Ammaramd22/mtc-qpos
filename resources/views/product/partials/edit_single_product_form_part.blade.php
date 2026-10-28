@php
    $price_tax_mode = product_price_tax_mode();
    $default = $price_tax_mode === 'hidden' ? 0 : null;
    $class = $price_tax_mode === 'hidden' ? 'hide' : '';
    $common_settings = session()->get('business.common_settings');
    $wholesale_enabled = \App\Utils\WholesaleUtil::isEnabled();
    $wholesale_price = $wholesale_price ?? null;
@endphp

<div class="col-sm-12"><br>
    <div class="table-responsive">
    <table class="table table-bordered add-product-price-table table-condensed {{ $class }}">
        <tr>
          <th>@lang('product.default_purchase_price')</th>
          <th>@lang('product.profit_percent') @show_tooltip(__('tooltip.profit_percent'))</th>
          <th>@lang('product.default_selling_price')</th>
          @if($wholesale_enabled)
            <th>@lang('lang_v1.wholesale_price')</th>
          @endif
          <th>@lang('lang_v1.product_image')</th>
        </tr>
        @foreach($product_deatails->variations as $variation )
            @php
                $is_image_required = !empty($common_settings['is_product_image_required']) && count($variation->media) == 0;
                if ($wholesale_enabled && $wholesale_price === null) {
                    $wholesale_price = \App\Utils\WholesaleUtil::getWholesalePrice(
                        $variation->id,
                        (int) session('user.business_id')
                    );
                }
            @endphp
            @if($loop->first)
                <tr>
                    <td>
                        <input type="hidden" name="single_variation_id" value="{{$variation->id}}">

                        @if($price_tax_mode === 'full')
                        <div class="col-sm-6">
                          {!! Form::label('single_dpp', trans('product.exc_of_tax') . ':*') !!}
                          {!! Form::text('single_dpp', @num_format($variation->default_purchase_price), ['class' => 'form-control input-sm dpp input_number', 'placeholder' => __('product.exc_of_tax'), 'required']); !!}
                        </div>
                        <div class="col-sm-6">
                          {!! Form::label('single_dpp_inc_tax', trans('product.inc_of_tax') . ':*') !!}
                          {!! Form::text('single_dpp_inc_tax', @num_format($variation->dpp_inc_tax), ['class' => 'form-control input-sm dpp_inc_tax input_number', 'placeholder' => __('product.inc_of_tax'), 'required']); !!}
                        </div>
                        @else
                        <div class="col-sm-12">
                          {!! Form::label('single_dpp', trans('product.default_purchase_price') . ':*') !!}
                          {!! Form::text('single_dpp', @num_format($variation->default_purchase_price), ['class' => 'form-control input-sm dpp input_number', 'placeholder' => __('product.default_purchase_price'), 'required']); !!}
                          {!! Form::hidden('single_dpp_inc_tax', @num_format($variation->dpp_inc_tax), ['class' => 'dpp_inc_tax input_number', 'id' => 'single_dpp_inc_tax']); !!}
                        </div>
                        @endif
                    </td>

                    <td>
                        <br/>
                        {!! Form::text('profit_percent', @num_format($variation->profit_percent), ['class' => 'form-control input-sm input_number', 'id' => 'profit_percent', 'required']); !!}
                    </td>

                    <td>
                        @if($price_tax_mode === 'full')
                        <label><span class="dsp_label"></span></label>
                        {!! Form::text('single_dsp', @num_format($variation->default_sell_price), ['class' => 'form-control input-sm dsp input_number', 'placeholder' => __('product.exc_of_tax'), 'id' => 'single_dsp', 'required']); !!}
                        {!! Form::text('single_dsp_inc_tax', @num_format($variation->sell_price_inc_tax), ['class' => 'form-control input-sm hide input_number', 'placeholder' => __('product.inc_of_tax'), 'id' => 'single_dsp_inc_tax', 'required']); !!}
                        @else
                        <br/>
                        {!! Form::text('single_dsp', @num_format($variation->default_sell_price), ['class' => 'form-control input-sm dsp input_number', 'placeholder' => __('product.default_selling_price'), 'id' => 'single_dsp', 'required']); !!}
                        {!! Form::hidden('single_dsp_inc_tax', @num_format($variation->sell_price_inc_tax), ['class' => 'input_number', 'id' => 'single_dsp_inc_tax']); !!}
                        @endif
                    </td>
                    @if($wholesale_enabled)
                    <td>
                        <br/>
                        {!! Form::text('wholesale_price', !is_null($wholesale_price) ? @num_format($wholesale_price) : null, ['class' => 'form-control input-sm input_number', 'id' => 'wholesale_price', 'placeholder' => __('lang_v1.wholesale_price')]); !!}
                    </td>
                    @endif
                    <td>
                        @php 
                            $action = !empty($action) ? $action : '';
                        @endphp
                        @if($action !== 'duplicate')
                            @foreach($variation->media as $media)
                                <div class="img-thumbnail">
                                    <span class="badge bg-red delete-media" data-href="{{ action([\App\Http\Controllers\ProductController::class, 'deleteMedia'], ['media_id' => $media->id])}}"><i class="fas fa-times"></i></span>
                                    {!! $media->thumbnail() !!}
                                </div>
                            @endforeach
                        @endif
                        <div class="form-group">
                            {!! Form::label('variation_images', __('lang_v1.product_image') . ':') !!}
                            {!! Form::file('variation_images[]', ['class' => 'variation_images', 
                                'accept' => 'image/*', 'multiple', 'required' => $is_image_required]); !!}
                            <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
    </div>
</div>
