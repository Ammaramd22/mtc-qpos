@php
    $custom_labels = session('business.custom_labels');
    if (is_string($custom_labels)) {
        $custom_labels = json_decode($custom_labels, true);
    }
    if (! is_array($custom_labels)) {
        $custom_labels = [];
    }
    $costcode_label = ! empty($custom_labels['product']['custom_field_1'])
        ? $custom_labels['product']['custom_field_1']
        : __('lang_v1.costcode');
    $supplier_code_label = ! empty($custom_labels['product']['custom_field_2'])
        ? $custom_labels['product']['custom_field_2']
        : __('lang_v1.supplier_code');

    $product_ref = $product ?? ($duplicate_product ?? null);
    $selected_supplier = old('supplier_id', $product_ref->supplier_id ?? null);
    $cf1_val = old('product_custom_field1', $product_ref->product_custom_field1 ?? null);
    $cf2_val = old('product_custom_field2', $product_ref->product_custom_field2 ?? null);
@endphp
{{-- Always show Supplier / Cost Code / Supplier Code (not only when custom labels are set) --}}
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('supplier_id', __('lang_v1.supplier') . ':') !!}
        {!! Form::select(
            'supplier_id',
            $suppliers ?? [],
            $selected_supplier,
            ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'supplier_id']
        ) !!}
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('product_custom_field2', $supplier_code_label . ':') !!}
        {!! Form::text('product_custom_field2', $cf2_val, [
            'class' => 'form-control',
            'placeholder' => $supplier_code_label,
            'id' => 'product_custom_field2',
        ]) !!}
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('product_custom_field1', $costcode_label . ':') !!}
        {!! Form::text('product_custom_field1', $cf1_val, [
            'class' => 'form-control',
            'placeholder' => $costcode_label,
            'id' => 'product_custom_field1',
        ]) !!}
        <p class="help-block" style="margin-bottom:0;font-size:11px;">@lang('lang_v1.costcode_field_help')</p>
    </div>
</div>
<div class="clearfix"></div>
