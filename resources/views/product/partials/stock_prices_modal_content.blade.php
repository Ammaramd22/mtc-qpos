<div class="row">
    <div class="col-sm-12">
        <h4 class="tw-font-semibold tw-mb-2">{{ $product->name }} &mdash; Manage Prices</h4>
    </div>
</div>

{{-- Add new price form --}}
<form id="add_stock_price_form">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <label>Variation</label>
                <select name="variation_id" class="form-control" required>
                    @foreach($product->variations as $v)
                        <option value="{{ $v->id }}">
                            {{ $product->type == 'variable' ? $v->product_variation->name . ' - ' . $v->name : 'Default' }}
                            ({{ $v->sub_sku }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label>Label <small class="text-muted">(optional)</small></label>
                <input type="text" name="label" class="form-control" placeholder="e.g. Old Price, New Price">
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label>Selling Price (inc. tax)</label>
                <input type="number" name="sell_price_inc_tax" class="form-control" step="0.01" min="0" required placeholder="0.00">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label>&nbsp;</label><br>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-plus"></i> Add
                </button>
            </div>
        </div>
    </div>
</form>

<hr>

{{-- Existing prices table --}}
<div id="stock_prices_list">
    @include('product.partials.stock_prices_list', ['prices' => $prices])
</div>

<script>
$(function() {
    $('#add_stock_price_form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: '/products/stock-prices',
            method: 'POST',
            data: form.serialize(),
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    // Reload the list
                    var product_id = form.find('[name=product_id]').val();
                    $.get('/products/stock-prices?product_id=' + product_id, function(html) {
                        $('#stock_prices_list').html($(html).find('#stock_prices_list').html());
                    });
                    form.find('[name=label]').val('');
                    form.find('[name=sell_price_inc_tax]').val('');
                } else {
                    toastr.error('Error saving price.');
                }
            },
            error: function() { toastr.error('Something went wrong.'); }
        });
    });

    $(document).on('click', '.delete_stock_price', function() {
        var id = $(this).data('id');
        if (!confirm('Delete this price?')) return;
        $.ajax({
            url: '/products/stock-prices/' + id,
            method: 'DELETE',
            data: { _token: $('meta[name=csrf-token]').attr('content') },
            success: function(res) {
                if (res.success) {
                    $('#stock_price_row_' + id).remove();
                    toastr.success('Price deleted.');
                }
            }
        });
    });
});
</script>
