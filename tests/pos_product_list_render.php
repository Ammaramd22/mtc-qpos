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

function render_list($products, $term = null, $product_view = 'grid')
{
    return view('sale_pos.partials.product_list', [
        'products' => $products,
        'allowed_group_prices' => [],
        'show_prices' => false,
        'for_pop' => false,
        'active_price_group_id' => null,
        'term' => $term,
        'product_view' => $product_view,
    ])->render();
}

function fake_product($name, $sku, $extra = [])
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
    $product->product_size = $extra['size'] ?? '';
    $product->product_color = $extra['color'] ?? '';
    $product->category_name = $extra['category'] ?? '';

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
check('cards keep the grid text block', substr_count($cards, 'class="text_div') === 2);
check('grid cards do not include list rows', strpos($cards, 'vspos-plist-row') === false);
check('grid cards do not include a list header', strpos($cards, 'vspos-plist-head') === false);

$detailed = render_list(new LengthAwarePaginator(collect([
    fake_product('999 PLAN WHITE VERY LONG PRODUCT NAME THAT MUST ELLIPSIZE', '62920141012010', [
        'size' => 'Large',
        'color' => 'Black',
        'category' => 'Cap',
    ]),
    fake_product('AL FARIS 6ml', '4799975576288'),
]), 2, 50, 1));
check('sized product keeps the grid card', substr_count($detailed, 'class="product_box') === 2);
check('sized product exposes size', strpos($detailed, 'data-size="Large"') !== false);
check('sized product exposes color', strpos($detailed, 'data-color="Black"') !== false);
check('sized product exposes category', strpos($detailed, 'data-category="Cap"') !== false);
check('grid cards do not render size text twice', substr_count($detailed, '>Large<') === 0);
check('detailed cards do not show the empty state', strpos($detailed, 'No Products to display') === false);

$rows = render_list(new LengthAwarePaginator(collect([
    fake_product('999 PLAN WHITE VERY LONG PRODUCT NAME THAT MUST ELLIPSIZE', '62920141012010', [
        'size' => 'Large',
        'color' => 'Black',
        'category' => 'Cap',
    ]),
    fake_product('AL FARIS 6ml', '4799975576288'),
]), 2, 50, 1), null, 'list');
check('list template has one row per product', substr_count($rows, 'class="product_box vspos-plist-row') === 2);
check('list template has one header', substr_count($rows, 'id="vspos_plist_head"') === 1);
check('list template spans the product grid', substr_count($rows, 'grid-column:1 / -1') === 1);
check('list template has a thumbnail per product', substr_count($rows, '<img ') === 2);
check('list template has no grid card image', strpos($rows, 'image-container') === false);
check('list template has no grid text block', strpos($rows, 'text_div') === false);
check('each list row has one name element', substr_count($rows, 'class="vspos-plist-name"') === 2);
check('each list row has one sku column', substr_count($rows, 'class="vspos-plist-sku"') === 2);
check('sized row shows the size in its column', substr_count($rows, '>Large<') === 1);
check('sized row shows the color in its column', substr_count($rows, '>Black<') === 1);
check('header includes size and color', strpos($rows, '>Size<') !== false && strpos($rows, '>Color<') !== false);
check('list rows do not show the empty state', strpos($rows, 'No Products to display') === false);

$listPage2 = render_list(new LengthAwarePaginator(collect(), 12, 50, 2), null, 'list');
check('list page 2 empty is an end marker', strpos($listPage2, 'id="suggestion_end"') !== false);
check('list page 2 does not repeat the header', strpos($listPage2, 'vspos-plist-head') === false);

exit($failures === 0 ? 0 : 1);
