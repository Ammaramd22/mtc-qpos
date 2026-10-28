@php
    $shop_mode_fields = function_exists('shop_mode_list_columns') && class_exists(\App\Utils\ShopModeUtil::class)
        ? \App\Utils\ShopModeUtil::extraProductFields()
        : [];
    $src = $product ?? ($duplicate_product ?? null);
@endphp
@foreach($shop_mode_fields as $column => $meta)
    @php
        $val = old($column);
        if ($val === null && $src) {
            $val = $src->{$column} ?? null;
        }
        $type = $meta['type'] ?? 'text';
    @endphp
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label($column, __($meta['label']) . ':') !!}
            @if($type === 'number')
                {!! Form::number($column, $val, [
                    'class' => 'form-control',
                    'placeholder' => __($meta['placeholder'] ?? $meta['label']),
                    'maxlength' => $meta['maxlength'] ?? 80,
                ]) !!}
            @else
                {!! Form::text($column, $val, [
                    'class' => 'form-control',
                    'placeholder' => __($meta['placeholder'] ?? $meta['label']),
                    'maxlength' => $meta['maxlength'] ?? 80,
                ]) !!}
            @endif
        </div>
    </div>
@endforeach
