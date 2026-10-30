<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

session([
    'business.currency_precision' => 2,
    'business.currency_symbol_placement' => 'before',
    'currency' => [
        'symbol' => 'OMR',
        'decimal_separator' => '.',
        'thousand_separator' => ',',
    ],
]);

use Illuminate\Pagination\LengthAwarePaginator;

function render_list($products, $term = null)
{
    return view('sale_pos.partials.product_list', [
        'products' => $products,
        'allowed_group_prices' => [],
        'show_prices' => false,
        'for_pop' => false,
        'active_price_group_id' => null,
        'term' => $term,
    ])->render();
}

function fake_product($name, $sku)
{
    $product = new stdClass();
    $product->retail_price_baseline = 30;
    $product->selling_price = 30;
    $product->group_prices = [];
    $product->id = 11;
    $product->name = $name;
    $product->tyre_size = null;
    $product->type = 'single';
    $product->enable_stock = 1;
    $product->media = collect();
    $product->product_image = null;
    $product->variation = '';
    $product->sub_sku = $sku;
    $product->unit = 'Pc(s)';
    $product->qty_available = 30;
    $product->last_purchased_price = null;

    return $product;
}

$failures = 0;
function check($label, $cond)
{
    global $failures;
    if ($cond) {
        echo "PASS {$label}\n";
    } else {
        $failures++;
        echo "FAIL {$label}\n";
    }
}

$page2 = render_list(new LengthAwarePaginator(collect(), 12, 50, 2));
check('page 2 empty is an end marker', strpos($page2, 'id="suggestion_end"') !== false);
check('page 2 empty hides No Products', strpos($page2, 'No Products to display') === false);
check('page 2 empty has no product card', strpos($page2, 'product_box') === false);

$page1empty = render_list(new LengthAwarePaginator(collect(), 0, 50, 1));
check('page 1 empty shows the empty state', strpos($page1empty, 'No Products to display') !== false);
check('page 1 empty has retry', strpos($page1empty, 'id="vspos_retry_products"') !== false);
check('page 1 empty is marked no_products_found', strpos($page1empty, 'id="no_products_found"') !== false);
check('page 1 empty is not an end marker', strpos($page1empty, 'id="suggestion_end"') === false);

$searchEmpty = render_list(new LengthAwarePaginator(collect(), 0, 50, 1), 'attar-missing');
check('search miss does not say No Products to display', strpos($searchEmpty, 'No Products to display') === false);
check('search miss says no matching products', strpos($searchEmpty, 'No matching products found') !== false);
check('search miss has no retry button', strpos($searchEmpty, 'id="vspos_retry_products"') === false);

$cards = render_list(new LengthAwarePaginator(collect([
    fake_product('ROSE BERRY 6ml', '62292014102165'),
    fake_product('SABAYA', '62292014102151'),
]), 2, 50, 1));
check('cards include product_box', substr_count($cards, 'class="product_box') === 2);
check('cards include sku', strpos($cards, '62292014102165') !== false);
check('cards include stock', strpos($cards, '30.00') !== false || strpos($cards, '30') !== false);
check('cards do not include the empty state', strpos($cards, 'No Products to display') === false);
check('cards do not include the end marker', strpos($cards, 'suggestion_end') === false);

exit($failures === 0 ? 0 : 1);
