<?php
/**
 * One-shot: add products.supplier_id if missing.
 * Upload to public/ and open: /qpos_add_product_supplier.php?key=qposclear2026
 * Delete after use.
 */
$key = $_GET['key'] ?? '';
if ($key !== 'qposclear2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

$added = false;
try {
    if (! Schema::hasColumn('products', 'supplier_id')) {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('supplier_id')->nullable()->index();
        });
        $added = true;
    }
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    echo '<h2>OK</h2>';
    echo $added
        ? '<p>Added products.supplier_id column.</p>'
        : '<p>products.supplier_id already exists.</p>';
    echo '<p>Delete this file from public/ now.</p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Error</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
}
