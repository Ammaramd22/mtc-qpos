<?php
/**
 * QPOS — seed 10 dummy products per Shop Mode (NO TERMINAL needed)
 *
 * PREREQUISITE: Run database/cpanel_shop_mode.sql in phpMyAdmin first
 *               (business.shop_mode + industry columns must exist).
 *
 * HOW TO USE:
 * 1. Upload latest code (seeder + this file).
 * 2. Change SEED_SECRET below if you want.
 * 3. Open in browser:
 *    https://YOURDOMAIN.com/cpanel_seed_shop_modes.php?key=YOUR_SECRET
 *    Optional one mode only:
 *    ...?key=YOUR_SECRET&mode=vehicle_modification
 * 4. Wait for SUCCESS, then DELETE this file.
 */

declare(strict_types=1);

// ========== KEY must match the ?key= in the URL ==========
const SEED_SECRET = '12345678';
// Example: https://rts-qpos.avenque.io/cpanel_seed_shop_modes.php?key=12345678
// ========================================================

ini_set('display_errors', '1');
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if (! hash_equals(SEED_SECRET, (string) $key)) {
    http_response_code(403);
    echo "FORBIDDEN — wrong key.\n\n";
    echo "Edit public/cpanel_seed_shop_modes.php and set SEED_SECRET to match ?key=\n";
    exit;
}

$basePath = dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mode = isset($_GET['mode']) ? trim((string) $_GET['mode']) : null;
if ($mode === '') {
    $mode = null;
}

$allowed = [
    'normal_retail',
    'tyre_shop',
    'mobile_shop',
    'vehicle_modification',
    'vehicle_spare_parts',
    'hardware_shop',
];

if ($mode !== null && ! in_array($mode, $allowed, true)) {
    http_response_code(400);
    echo "Invalid mode: {$mode}\n";
    echo 'Allowed: '.implode(', ', $allowed)."\n";
    exit;
}

try {
    if (! Illuminate\Support\Facades\Schema::hasColumn('business', 'shop_mode')) {
        http_response_code(500);
        echo "MISSING COLUMN: business.shop_mode\n\n";
        echo "1) Open phpMyAdmin → SQL\n";
        echo "2) Run database/cpanel_shop_mode.sql\n";
        echo "3) Reload this page\n";
        exit;
    }

    echo $mode
        ? "Seeding shop mode: {$mode}…\n\n"
        : "Seeding ALL shop modes (10 products each)…\n\n";

    $args = [];
    if ($mode) {
        $args['mode'] = $mode;
    }

    $exitCode = Illuminate\Support\Facades\Artisan::call('pos:seed-shop-modes', $args);
    echo Illuminate\Support\Facades\Artisan::output();
    echo "\nExit code: {$exitCode}\n";

    if ($exitCode === 0) {
        echo "\nSUCCESS — dummy data ready.\n\n";
        echo "Logins (password: 123456):\n";
        echo "  demoretail     → Demo Normal Retail\n";
        echo "  demotyre       → Demo Tyre Shop\n";
        echo "  demomobile     → Demo Mobile Shop\n";
        echo "  demomod        → Demo Vehicle Modification\n";
        echo "  demospares     → Demo Vehicle Spare Parts\n";
        echo "  demohardware   → Demo Hardware Shop\n\n";
        echo "IMPORTANT: Delete this file now:\n";
        echo "  public/cpanel_seed_shop_modes.php\n";
    } else {
        echo "\nFAILED — check output above / storage/logs/laravel.log\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
