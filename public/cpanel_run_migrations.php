<?php
/**
 * QPOS — one-time cPanel migration runner (NO TERMINAL needed)
 *
 * HOW TO USE:
 * 1. Change MIGRATE_SECRET below to a random password.
 * 2. Upload this file (already in the ZIP under public/).
 * 3. Open in browser:
 *    https://YOURDOMAIN.com/cpanel_run_migrations.php?key=YOUR_LONG_SECRET
 * 4. Wait for "SUCCESS".
 * 5. DELETE this file from the server immediately.
 *
 * This runs: php artisan migrate --force
 * That applies pending migrations only. It does not drop the database.
 * It is still a schema change. Back up the database in cPanel before opening this URL.
 * Do not use this file for a brand-new install. The /install wizard is a different path
 * and it runs migrate:fresh, which deletes every table.
 */

declare(strict_types=1);

// Change this to a private random string of at least 16 characters before use.
const MIGRATE_SECRET = 'CHANGE_ME_BEFORE_USE';

ini_set('display_errors', '0');
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');
header('Content-Type: text/plain; charset=utf-8');

$weakSecrets = ['', 'CHANGE_ME_BEFORE_USE', '12345678', 'password', 'secret', 'qpos'];
if (in_array(MIGRATE_SECRET, $weakSecrets, true) || strlen(MIGRATE_SECRET) < 16) {
    http_response_code(403);
    echo "LOCKED — this script will not run until MIGRATE_SECRET is changed.\n\n";
    echo "Edit public/cpanel_run_migrations.php in cPanel File Manager.\n";
    echo "Set MIGRATE_SECRET to a private random string of at least 16 characters.\n";
    echo "Open the file again with ?key= set to that same string.\n";
    echo "Back up the database first. Delete this file after SUCCESS.\n";
    exit;
}

$key = $_GET['key'] ?? '';
if (! hash_equals(MIGRATE_SECRET, (string) $key)) {
    http_response_code(403);
    echo "FORBIDDEN — wrong key.\n";
    exit;
}

$basePath = dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Running pending migrations...\n\n";
    $exitCode = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo Illuminate\Support\Facades\Artisan::output();
    echo "\nExit code: {$exitCode}\n";

    if ($exitCode === 0) {
        echo "\nSUCCESS — migrations applied.\n";
        echo "IMPORTANT: Delete this file now:\n";
        echo "  public/cpanel_run_migrations.php\n";
    } else {
        echo "\nFAILED — check the output above / storage/logs/laravel.log\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: ".$e->getMessage()."\n";
    echo "See storage/logs/laravel.log on the server. Do not paste that log if it contains passwords.\n";
}
