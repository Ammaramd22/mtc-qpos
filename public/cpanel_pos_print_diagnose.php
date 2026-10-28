<?php
/**
 * QPOS — POS Pay schema diagnose
 * URL: /cpanel_pos_print_diagnose.php?key=12345678
 * DELETE after use.
 */
declare(strict_types=1);

const DIAG_SECRET = '12345678';
header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';
if (! hash_equals(DIAG_SECRET, (string) $key)) {
    http_response_code(403);
    echo "FORBIDDEN\n";
    exit;
}

$basePath = dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "QPOS POS Pay diagnose\n=====================\n\n";

$cols = [
    'transactions' => ['offline_uuid', 'credit_note_status', 'invoice_token', 'is_kitchen_order'],
    'transaction_payments' => ['cheque_number', 'cheque_name', 'cheque_bank_name', 'cheque_date', 'cheque_status', 'cheque_type', 'credit_note_id', 'account_id'],
    'products' => ['tyre_size'],
];

foreach ($cols as $table => $names) {
    echo "[table] {$table}\n";
    if (! Schema::hasTable($table)) {
        echo "  MISSING TABLE\n\n";
        continue;
    }
    foreach ($names as $col) {
        echo '  '.(Schema::hasColumn($table, $col) ? 'OK   ' : 'MISS ').$col."\n";
    }
    echo "\n";
}

try {
    $loc = DB::table('business_locations')->where('is_active', 1)->orderBy('id')->first();
    if ($loc) {
        echo "[location] #{$loc->id} {$loc->name}\n";
        $defaults = $loc->default_payment_accounts ?? '';
        $decoded = is_string($defaults) ? (json_decode($defaults, true) ?: []) : (array) $defaults;
        $cashAcc = $decoded['cash']['account'] ?? null;
        echo '  default cash account: '.($cashAcc ?: '(NOT SET — link in Business Locations)')."\n";
    }
} catch (Throwable $e) {
    echo 'Location error: '.$e->getMessage()."\n";
}

echo "\nIf any line says MISS, run database/cpanel_pos_print_fix.sql in phpMyAdmin.\n";
echo "DELETE this file when finished.\n";
