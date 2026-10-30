@php
    $size_color_source = null;
    if (isset($product)) {
        $size_color_source = $product;
    } elseif (isset($duplicate_product)) {
        $size_color_source = $duplicate_product;
    }
    $size_val = old('size', $size_color_source->size ?? null);
    $color_val = old('color', $size_color_source->color ?? null);
@endphp
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('size', __('product.size') . ':') !!}
        {!! Form::text('size', $size_val, [
            'class' => 'form-control',
            'placeholder' => __('product.size_placeholder'),
            'maxlength' => 191,
        ]); !!}
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('color', __('product.color') . ':') !!}
        {!! Form::text('color', $color_val, [
            'class' => 'form-control',
            'placeholder' => __('product.color_placeholder'),
            'maxlength' => 191,
        ]); !!}
    </div>
</div>
