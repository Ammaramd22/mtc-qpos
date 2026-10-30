@forelse ($products as $product)
    @php
        $row_index = $loop->index + $index;
    @endphp
    <tr class="labels-product-row">
        <td>
            <span class="labels-product-name">{{ $product->product_name }}</span>
            @if($product->variation_name != 'DUMMY')
                <span class="labels-product-variation">{{ $product->variation_name }}</span>
            @endif
            <input type="hidden" name="products[{{ $row_index }}][product_id]" value="{{ $product->product_id }}">
            <input type="hidden" name="products[{{ $row_index }}][variation_id]" value="{{ $product->variation_id }}">
        </td>
        <td>
            <input type="number" class="form-control input-sm text-center" min="1"
                name="products[{{ $row_index }}][quantity]"
                value="{{ isset($product->quantity) ? max(1, (int) ceil((float) $product->quantity)) : 1 }}">
        </td>
        @if(request()->session()->get('business.enable_lot_number') == 1)
            <td>
                <input type="text" class="form-control input-sm"
                    name="products[{{ $row_index }}][lot_number]"
                    value="{{ isset($product->lot_number) ? $product->lot_number : '' }}"
                    placeholder="@lang('lang_v1.lot_number')">
            </td>
        @endif
        @if(request()->session()->get('business.enable_product_expiry') == 1)
            <td>
                <input type="text" class="form-control input-sm label-date-picker"
                    name="products[{{ $row_index }}][exp_date]"
                    value="{{ isset($product->exp_date) ? @format_date($product->exp_date) : '' }}"
                    placeholder="@lang('product.exp_date')">
            </td>
        @endif
        <td class="text-center">
            <button type="button" class="labels-remove-row" title="@lang('messages.delete')">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
@empty
@endforelse
