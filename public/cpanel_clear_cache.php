<?php
/**
 * QPOS — clear Laravel caches (NO TERMINAL needed)
 *
 * HOW TO USE:
 * 1. Upload this file to public/
 * 2. Open in browser:
 *    https://qpos.avenque.io/sl/public/cpanel_clear_cache.php?key=YOUR_SECRET
 * 3. Confirm SUCCESS, then DELETE this file.
 */

declare(strict_types=1);

const CLEAR_SECRET = '12345678';

ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';
if (! hash_equals(CLEAR_SECRET, (string) $key)) {
    http_response_code(403);
    echo "FORBIDDEN — wrong key.\n\n";
    echo "Edit public/cpanel_clear_cache.php and set CLEAR_SECRET to match ?key=\n";
    exit;
}

$basePath = dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Clearing caches…\n\n";

$commands = [
    'config:clear',
    'cache:clear',
    'view:clear',
    'route:clear',
];

foreach ($commands as $cmd) {
    try {
        $code = $kernel->call($cmd);
        echo ($code === 0 ? 'OK  ' : "FAIL({$code}) ").$cmd."\n";
        $out = trim((string) $kernel->output());
        if ($out !== '') {
            echo "    ".$out."\n";
        }
    } catch (Throwable $e) {
        echo 'ERR '.$cmd.' — '.$e->getMessage()."\n";
    }
}

// Also remove compiled config if present (File Manager fallback)
$configCache = $basePath.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    @unlink($configCache);
    echo is_file($configCache) ? "WARN could not delete bootstrap/cache/config.php\n"
        : "OK  deleted bootstrap/cache/config.php\n";
} else {
    echo "OK  no bootstrap/cache/config.php\n";
}

echo "\nDONE.\n";
echo "Hard-refresh POS (Ctrl+Shift+R), then DELETE this file.\n";
