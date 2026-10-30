<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
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

function harness_product($name, $sku, $qty, $extra = [])
{
    $product = new stdClass();
    $product->retail_price_baseline = $extra['price'] ?? 15;
    $product->selling_price = $extra['price'] ?? 15;
    $product->group_prices = [];
    $product->id = $extra['id'] ?? 1;
    $product->name = $name;
    $product->tyre_size = null;
    $product->type = 'single';
    $product->enable_stock = 1;
    $product->media = collect();
    $product->product_image = null;
    $product->variation = '';
    $product->sub_sku = $sku;
    $product->unit = 'Pc(s)';
    $product->qty_available = $qty;
    $product->last_purchased_price = null;
    $product->product_size = $extra['size'] ?? '';
    $product->product_color = $extra['color'] ?? '';
    $product->category_name = $extra['category'] ?? '';

    return $product;
}

$rows = [
    harness_product('999 PLAN WHITE VERY LONG PRODUCT NAME THAT MUST NOT OVERFLOW THE ROW', '62920141012010', 5, ['size' => 'Large', 'color' => 'Black', 'category' => 'Cap', 'price' => 19, 'id' => 1]),
    harness_product('999 WHITE WITH LINING', '62920141012109', 9, ['category' => 'Cap', 'price' => 9, 'id' => 2]),
    harness_product('ACQUAD GIO 6ml', '62920141012000', 19, ['size' => '6ml', 'category' => 'PERFUME', 'price' => 19, 'id' => 3]),
    harness_product('AFNAN 9AM dive', '6290171072836', 1, ['color' => 'Blue', 'category' => 'PERFUME', 'price' => 1, 'id' => 4]),
    harness_product('AL FARIS 6ml', '4799975576288', 12, ['category' => 'attar', 'price' => 12, 'id' => 5]),
    harness_product('AL NUAIM NRML', '6292014110194', 0, ['size' => 'Medium', 'color' => 'White', 'category' => 'BANYAN', 'price' => 15, 'id' => 6]),
    harness_product('AL NUAIM NRML', '6292014110125', 29, ['size' => 'Small', 'color' => 'Navy', 'category' => 'BANYAN', 'price' => 29, 'id' => 7]),
    harness_product('AMBER AL ARAB', '6971362640776', 8, ['category' => 'MISWAK', 'price' => 8, 'id' => 8]),
    harness_product('ASAD BOURBON', '6290360234362', 4, ['color' => 'Brown', 'category' => 'PERFUME', 'price' => 24, 'id' => 9]),
    harness_product('ASEEL', '0076', 10, ['category' => 'roomaal', 'price' => 10, 'id' => 10]),
];

$viewData = [
    'products' => new LengthAwarePaginator(collect($rows), count($rows), 50, 1),
    'allowed_group_prices' => [],
    'show_prices' => true,
    'for_pop' => false,
    'active_price_group_id' => null,
    'term' => null,
];
$gridHtml = view('sale_pos.partials.product_list', $viewData + ['product_view' => 'grid'])->render();
$listHtml = view('sale_pos.partials.product_list', $viewData + ['product_view' => 'list'])->render();
$gridJson = json_encode($gridHtml, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$listJson = json_encode($listHtml, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

$page = <<<'HTML'
<!DOCTYPE html>
<html data-vspos-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS product list view harness</title>
<link rel="stylesheet" href="../../public/css/pos-touch.css">
<link rel="stylesheet" href="../../public/css/pos-mobile-retail.css">
<style>
  html, body { margin: 0; background: #0a0a0a; }
  .vspos-touch-shell { height: 100vh; max-height: 100vh; }
  #harness_cart { color: #f5f5f5; padding: 12px; font: 13px/1.4 sans-serif; }
  #harness_cart strong { color: #f5c518; }
</style>
</head>
<body class="vspos-touch-ui lockscreen">
<div class="vspos-touch-shell" id="vspos_touch_shell">
  <div class="vspos-touch-layout">
    <aside class="vspos-touch-cats" id="vspos_touch_cats">
      <div class="vspos-touch-cats-head"><span>Category</span></div>
      <div class="vspos-touch-cats-scroll">
        <button type="button" class="vspos-touch-cat is-active" data-value="all">All Categories</button>
        <button type="button" class="vspos-touch-cat" data-value="4">Cap</button>
        <button type="button" class="vspos-touch-cat" data-value="8">PERFUME</button>
      </div>
    </aside>
    <section class="vspos-touch-products">
      <div class="vspos-touch-products-toolbar">
        <div class="vspos-touch-search">
          <input type="text" id="search_product" class="form-control" placeholder="Enter Product name / SKU / Scan bar" value="">
        </div>
        <div class="vspos-pos-view-switch" role="group" aria-label="Product view">
          <button type="button" class="vspos-pos-view-btn is-active" data-pos-view="grid" aria-pressed="true">Grid</button>
          <button type="button" class="vspos-pos-view-btn" data-pos-view="list" aria-pressed="false">List</button>
        </div>
        <div class="vspos-touch-products-title" id="vspos_touch_cat_title">All Categories</div>
      </div>
      <div id="product_list_body" class="vspos-touch-product-grid">
        <div id="product_list_items" class="vspos-touch-product-items"></div>
      </div>
    </section>
    <aside class="vspos-touch-cart">
      <div id="harness_cart">
        <div>Cart lines: <strong id="cart_count">0</strong></div>
        <div id="cart_log"></div>
        <div id="metrics"></div>
      </div>
    </aside>
  </div>
</div>
<script>
(function () {
  var templates = { grid: GRID_JSON, list: LIST_JSON };
  var count = 0;
  var ids = [];
  function paintButtons() {
    var list = document.documentElement.classList.contains('vspos-product-view-list');
    document.querySelectorAll('.vspos-pos-view-btn').forEach(function (btn) {
      var on = btn.getAttribute('data-pos-view') === (list ? 'list' : 'grid');
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }
  function show(mode) {
    var list = mode === 'list';
    document.documentElement.classList.toggle('vspos-product-view-list', list);
    document.getElementById('product_list_items').innerHTML = templates[list ? 'list' : 'grid'];
    paintButtons();
    measure();
  }
  document.querySelectorAll('.vspos-pos-view-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      show(btn.getAttribute('data-pos-view'));
    });
  });
  show('grid');
  document.getElementById('vspos_touch_shell').addEventListener('click', function (e) {
    var box = e.target.closest && e.target.closest('div.product_box');
    if (!box || !document.getElementById('vspos_touch_shell').contains(box)) return;
    count += 1;
    ids.push(String(box.getAttribute('data-variation_id')));
    document.getElementById('cart_count').textContent = String(count);
    document.getElementById('cart_log').textContent = ids.join(', ');
    box.classList.add('is-touch-added');
    setTimeout(function () { box.classList.remove('is-touch-added'); }, 220);
  });
  document.getElementById('vspos_touch_cats').addEventListener('click', function (e) {
    var btn = e.target.closest('.vspos-touch-cat');
    if (!btn) return;
    document.querySelectorAll('.vspos-touch-cat').forEach(function (el) { el.classList.remove('is-active'); });
    btn.classList.add('is-active');
    var value = btn.getAttribute('data-value');
    document.documentElement.classList.toggle('vspos-plist-filtered', value !== 'all');
    document.getElementById('vspos_touch_cat_title').textContent = btn.textContent;
  });
  function measure() {
    var body = document.getElementById('product_list_body');
    var box = document.querySelector('.product_box');
    var img = box ? box.querySelector('.image-container') : null;
    var name = document.querySelector('.vspos-plist-name');
    var overflow = document.documentElement.scrollWidth > document.documentElement.clientWidth + 1;
    var rowOverflow = box ? box.scrollWidth > box.clientWidth + 1 : false;
    document.getElementById('metrics').textContent = JSON.stringify({
      view: document.documentElement.classList.contains('vspos-product-view-list') ? 'list' : 'grid',
      pageOverflow: overflow,
      rowOverflow: rowOverflow,
      bodyClient: body ? body.clientWidth : 0,
      boxDisplay: box ? getComputedStyle(box).display : '',
      imgH: img ? getComputedStyle(img).height : '',
      imgW: img ? getComputedStyle(img).width : '',
      nameOverflow: name ? name.scrollWidth > name.clientWidth + 1 : false,
      visibleBoxes: document.querySelectorAll('.product_box').length,
      cart: count
    });
  }
  window.harnessMeasure = measure;
  measure();
})();
</script>
</body>
</html>
HTML;

$page = str_replace(['GRID_JSON', 'LIST_JSON'], [$gridJson, $listJson], $page);
file_put_contents(__DIR__ . '/pos-product-list-view-harness.html', $page);
echo "wrote harness\n";
