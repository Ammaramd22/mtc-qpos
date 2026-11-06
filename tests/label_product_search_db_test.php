<?php

/**
 * Read-only Print Labels search check against the configured database.
 * Run: php tests/label_product_search_db_test.php
 * Prints counts and names only. Does not update data.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\LabelsController;
use Illuminate\Support\Facades\DB;

$failed = 0;

function expect($label, $condition, $detail = '')
{
    global $failed;
    if ($condition) {
        echo "OK {$label}".($detail !== '' ? " — {$detail}" : '')."\n";

        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL {$label}".($detail !== '' ? " — {$detail}" : '')."\n");
}

try {
    DB::connection()->getPdo();
} catch (Throwable $e) {
    fwrite(STDERR, "SKIP database: ".$e->getMessage()."\n");
    exit(0);
}

$businessId = (int) DB::table('business')->orderBy('id')->value('id');
expect('business exists', $businessId > 0, 'id '.$businessId);
if ($businessId < 1) {
    exit(1);
}

$controller = app(LabelsController::class);
$method = new ReflectionMethod($controller, 'labelProductSuggestions');
$method->setAccessible(true);

$search = function (string $term) use ($method, $controller, $businessId) {
    return $method->invoke($controller, $businessId, $term);
};

$total = (int) DB::table('products')
    ->where('business_id', $businessId)
    ->where('type', '!=', 'modifier')
    ->count();
$withVariation = (int) DB::table('products as p')
    ->where('p.business_id', $businessId)
    ->where('p.type', '!=', 'modifier')
    ->whereExists(function ($q) {
        $q->select(DB::raw(1))
            ->from('variations as v')
            ->whereColumn('v.product_id', 'p.id')
            ->whereNull('v.deleted_at');
    })
    ->count();
$inactive = (int) DB::table('products')
    ->where('business_id', $businessId)
    ->where('type', '!=', 'modifier')
    ->where('is_inactive', 1)
    ->count();
echo "Catalog products={$total} with_variation={$withVariation} inactive={$inactive}\n";

$list = DB::table('products as p')
    ->join('variations as v', 'v.product_id', '=', 'p.id')
    ->whereNull('v.deleted_at')
    ->where('p.business_id', $businessId)
    ->where('p.type', '!=', 'modifier')
    ->groupBy('p.id', 'p.name', 'p.sku', 'p.is_inactive')
    ->orderBy('p.id')
    ->get(['p.id', 'p.name', 'p.sku', 'p.is_inactive']);

expect('list has products', $list->count() > 0, 'rows '.$list->count());

$pastFirstPage = $list->slice(25, 5);
$checked = 0;
foreach ($pastFirstPage as $product) {
    $name = trim((string) $product->name);
    $token = '';
    foreach (preg_split('/\s+/u', $name) ?: [] as $part) {
        if (mb_strlen($part) >= 4 && ! preg_match('/^\d+$/u', $part)) {
            $token = $part;
            break;
        }
    }
    if ($token === '') {
        continue;
    }
    $found = $search($token);
    $ids = array_column($found['results'], 'product_id');
    $hit = in_array((int) $product->id, $ids, true) || $found['truncated'];
    expect('product past page 1 is searchable', $hit, $token.' #'.$product->id.($found['truncated'] ? ' (narrow)' : ''));
    $checked++;
    if ($checked >= 3) {
        break;
    }
}
expect('checked products beyond the first page', $checked > 0, 'checked '.$checked);

$sample = $list->first();
if ($sample) {
    $full = trim((string) $sample->name);
    $words = preg_split('/\s+/u', $full) ?: [];
    $partial = mb_substr($words[0] ?? $full, 0, 12);
    if (mb_strlen($partial) >= 2) {
        $byPartial = $search($partial);
        $ids = array_column($byPartial['results'], 'product_id');
        expect('partial name', in_array((int) $sample->id, $ids, true) || $byPartial['truncated'], $partial);
    }
    if (mb_strlen($full) >= 2) {
        $byFull = $search(mb_substr($full, 0, 80));
        $ids = array_column($byFull['results'], 'product_id');
        expect('full name', in_array((int) $sample->id, $ids, true) || $byFull['truncated'], mb_substr($full, 0, 40));
    }
    $sku = trim((string) $sample->sku);
    if (mb_strlen($sku) >= 2) {
        $bySku = $search($sku);
        $ids = array_column($bySku['results'], 'product_id');
        expect('sku', in_array((int) $sample->id, $ids, true), $sku);
    }
}

$barcode = DB::table('variations as v')
    ->join('products as p', 'p.id', '=', 'v.product_id')
    ->where('p.business_id', $businessId)
    ->where('p.type', '!=', 'modifier')
    ->whereNull('v.deleted_at')
    ->whereNotNull('v.sub_sku')
    ->where('v.sub_sku', '!=', '')
    ->orderBy('v.id')
    ->first(['p.id as product_id', 'v.sub_sku']);
if ($barcode && mb_strlen(trim((string) $barcode->sub_sku)) >= 2) {
    $byBarcode = $search(trim((string) $barcode->sub_sku));
    $ids = array_column($byBarcode['results'], 'product_id');
    expect('barcode', in_array((int) $barcode->product_id, $ids, true), trim((string) $barcode->sub_sku));
}

if ($inactive > 0) {
    $inactiveRow = DB::table('products')
        ->where('business_id', $businessId)
        ->where('type', '!=', 'modifier')
        ->where('is_inactive', 1)
        ->orderBy('id')
        ->first(['id', 'name', 'sku']);
    $needle = trim((string) ($inactiveRow->sku ?: $inactiveRow->name));
    if (mb_strlen($needle) >= 2) {
        $found = $search(mb_substr($needle, 0, 40));
        $ids = array_column($found['results'], 'product_id');
        expect('inactive product', in_array((int) $inactiveRow->id, $ids, true) || $found['truncated'], '#'.$inactiveRow->id);
    }
}

$zeroStock = DB::table('products as p')
    ->join('variations as v', 'v.product_id', '=', 'p.id')
    ->leftJoin('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
    ->where('p.business_id', $businessId)
    ->where('p.type', '!=', 'modifier')
    ->whereNull('v.deleted_at')
    ->groupBy('p.id', 'p.name', 'p.sku')
    ->havingRaw('COALESCE(SUM(vld.qty_available), 0) <= 0')
    ->orderBy('p.id')
    ->first(['p.id', 'p.name', 'p.sku']);
if ($zeroStock) {
    $needle = trim((string) ($zeroStock->sku ?: $zeroStock->name));
    if (mb_strlen($needle) >= 2) {
        $found = $search(mb_substr($needle, 0, 40));
        $ids = array_column($found['results'], 'product_id');
        expect('zero stock product', in_array((int) $zeroStock->id, $ids, true) || $found['truncated'], '#'.$zeroStock->id);
    }
}

$withImage = DB::table('products')
    ->where('business_id', $businessId)
    ->where('type', '!=', 'modifier')
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->orderBy('id')
    ->first(['id', 'sku', 'name']);
$withoutImage = DB::table('products')
    ->where('business_id', $businessId)
    ->where('type', '!=', 'modifier')
    ->where(function ($q) {
        $q->whereNull('image')->orWhere('image', '');
    })
    ->orderBy('id')
    ->first(['id', 'sku', 'name']);
foreach (['with image' => $withImage, 'without image' => $withoutImage] as $label => $row) {
    if (! $row) {
        echo "SKIP {$label}\n";
        continue;
    }
    $needle = trim((string) ($row->sku ?: $row->name));
    if (mb_strlen($needle) < 2) {
        continue;
    }
    $found = $search(mb_substr($needle, 0, 40));
    $ids = array_column($found['results'], 'product_id');
    expect($label, in_array((int) $row->id, $ids, true) || $found['truncated'], '#'.$row->id);
}

$sizeRows = [];
try {
    $sizeRows = DB::table('product_size')
        ->join('sizes', 'sizes.id', '=', 'product_size.size_id')
        ->join('products', 'products.id', '=', 'product_size.product_id')
        ->where('sizes.business_id', $businessId)
        ->where('products.business_id', $businessId)
        ->where('products.type', '!=', 'modifier')
        ->orderBy('products.id')
        ->limit(30)
        ->get(['products.id as product_id', 'products.name', 'sizes.name as size']);
} catch (Throwable $e) {
    echo "SKIP size pivot: ".$e->getMessage()."\n";
}

$seenSizes = [];
foreach ($sizeRows as $row) {
    $size = trim((string) $row->size);
    if ($size === '' || isset($seenSizes[$size])) {
        continue;
    }
    $seenSizes[$size] = true;
    $found = $search($size);
    $ids = array_column($found['results'], 'product_id');
    expect('size '.$size, in_array((int) $row->product_id, $ids, true) || $found['truncated'], 'product '.$row->product_id);
    if (count($seenSizes) >= 3) {
        break;
    }
}

if ($seenSizes === []) {
    $textSize = DB::table('products')
        ->where('business_id', $businessId)
        ->where('type', '!=', 'modifier')
        ->whereNotNull('size')
        ->where('size', '!=', '')
        ->orderBy('id')
        ->first(['id', 'size', 'name']);
    if ($textSize) {
        $parts = preg_split('/\s*,\s*/u', (string) $textSize->size) ?: [];
        $size = trim((string) ($parts[0] ?? ''));
        if (mb_strlen($size) >= 2) {
            $found = $search($size);
            $ids = array_column($found['results'], 'product_id');
            expect('text size '.$size, in_array((int) $textSize->id, $ids, true) || $found['truncated'], 'product '.$textSize->id);
        }
    } else {
        echo "SKIP size — no assigned sizes\n";
    }
}

$colorRows = [];
try {
    $colorRows = DB::table('product_color')
        ->join('colors', 'colors.id', '=', 'product_color.color_id')
        ->join('products', 'products.id', '=', 'product_color.product_id')
        ->where('colors.business_id', $businessId)
        ->where('products.type', '!=', 'modifier')
        ->orderBy('products.id')
        ->limit(20)
        ->get(['products.id as product_id', 'colors.name as color']);
} catch (Throwable $e) {
    echo "SKIP color pivot: ".$e->getMessage()."\n";
}
$seenColors = [];
foreach ($colorRows as $row) {
    $color = trim((string) $row->color);
    if (mb_strlen($color) < 2 || isset($seenColors[mb_strtolower($color)])) {
        continue;
    }
    $seenColors[mb_strtolower($color)] = true;
    $found = $search($color);
    $ids = array_column($found['results'], 'product_id');
    expect('color '.$color, in_array((int) $row->product_id, $ids, true) || $found['truncated'], 'product '.$row->product_id);
    if (count($seenColors) >= 2) {
        break;
    }
}
if ($seenColors === []) {
    echo "SKIP color — no catalog colors linked\n";
}

$short = $search('a');
expect('one character returns nothing', $short['results'] === [] && $short['truncated'] === false);

$none = $search('zzznomatch999');
expect('unknown term is empty', $none['results'] === [] && $none['truncated'] === false);

if ($failed > 0) {
    fwrite(STDERR, "{$failed} database checks failed\n");
    exit(1);
}

echo "Database search checks passed\n";
