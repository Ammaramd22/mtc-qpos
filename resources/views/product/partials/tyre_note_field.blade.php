@php
    $tyre_note_val = old('tyre_note');
    if ($tyre_note_val === null && isset($product)) {
        $tyre_note_val = $product->tyre_note;
    }
    if ($tyre_note_val === null && isset($duplicate_product)) {
        $tyre_note_val = $duplicate_product->tyre_note;
    }
@endphp
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('tyre_note', __('product.tyre_note') . ':') !!}
        {!! Form::text('tyre_note', $tyre_note_val, [
            'class' => 'form-control',
            'placeholder' => __('product.tyre_note_placeholder'),
            'maxlength' => 100,
        ]); !!}
    </div>
</div>
