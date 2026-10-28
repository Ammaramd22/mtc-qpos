@php
    $tyre_size_val = old('tyre_size');
    if ($tyre_size_val === null && isset($product)) {
        $tyre_size_val = $product->tyre_size;
    }
    if ($tyre_size_val === null && isset($duplicate_product)) {
        $tyre_size_val = $duplicate_product->tyre_size;
    }
@endphp
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('tyre_size', __('product.tyre_size') . ':') !!}
        {!! Form::text('tyre_size', $tyre_size_val, [
            'class' => 'form-control',
            'placeholder' => __('product.tyre_size_placeholder'),
            'maxlength' => 50,
        ]); !!}
    </div>
</div>
