@if($prices->isEmpty())
    <p class="text-muted text-center">No prices added yet.</p>
@else
    <table class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th>Variation</th>
                <th>Label</th>
                <th>Selling Price (inc. tax)</th>
                <th>Added</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($prices as $price)
            <tr id="stock_price_row_{{ $price->id }}">
                <td>
                    @if($price->variation)
                        {{ $price->variation->product->type == 'variable'
                            ? optional($price->variation->product_variation)->name . ' - ' . $price->variation->name
                            : 'Default' }}
                        <small class="text-muted">({{ $price->variation->sub_sku }})</small>
                    @endif
                </td>
                <td>{{ $price->label ?: '—' }}</td>
                <td><strong>@format_currency($price->sell_price_inc_tax)</strong></td>
                <td>{{ $price->created_at->format('d M Y') }}</td>
                <td>
                    <button class="btn btn-xs btn-danger delete_stock_price" data-id="{{ $price->id }}">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
