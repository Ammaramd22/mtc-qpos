<?php
/**
 * QPOS — diagnose jQuery / vendor.js (NO TERMINAL)
 * Open: https://qpos.avenque.io/sl/public/cpanel_jquery_diagnose.php?key=12345678
 * DELETE after use.
 */
declare(strict_types=1);

const DIAG_SECRET = '12345678';

header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';
if (! hash_equals(DIAG_SECRET, (string) $key)) {
    http_response_code(403);
    echo "FORBIDDEN — use ?key=12345678\n";
    exit;
}

$base = dirname(__DIR__);
$public = __DIR__;

echo "QPOS jQuery diagnose\n";
echo "====================\n\n";

$files = [
    'public/js/jquery-3.7.1.min.js' => $public.'/js/jquery-3.7.1.min.js',
    'public/js/vendor.js' => $public.'/js/vendor.js',
    'resources/views/layouts/app.blade.php' => $base.'/resources/views/layouts/app.blade.php',
    'resources/views/layouts/partials/javascripts.blade.php' => $base.'/resources/views/layouts/partials/javascripts.blade.php',
];

foreach ($files as $label => $path) {
    if (! is_file($path)) {
        echo "MISSING  {$label}\n";
        continue;
    }
    echo 'OK       '.$label.' ('.filesize($path)." bytes)\n";
}

echo "\n--- blade contains jquery script? ---\n";
foreach (['app.blade.php' => $base.'/resources/views/layouts/app.blade.php',
          'javascripts.blade.php' => $base.'/resources/views/layouts/partials/javascripts.blade.php'] as $n => $p) {
    if (! is_file($p)) {
        continue;
    }
    $c = file_get_contents($p);
    $has = str_contains($c, 'jquery-3.7.1.min.js') ? 'YES' : 'NO';
    echo "{$n}: {$has}\n";
}

echo "\n--- compiled views with jquery? ---\n";
$viewDir = $base.'/storage/framework/views';
$count = 0;
$withJq = 0;
if (is_dir($viewDir)) {
    foreach (glob($viewDir.'/*.php') ?: [] as $vf) {
        $count++;
        $txt = @file_get_contents($vf);
        if ($txt !== false && str_contains($txt, 'jquery-3.7.1.min.js')) {
            $withJq++;
        }
    }
}
echo "compiled view files: {$count}\n";
echo "mentioning jquery-3.7.1: {$withJq}\n";

echo "\n--- asset_version ---\n";
$const = $base.'/config/constants.php';
if (is_file($const) && preg_match("/asset_version'\s*=>\s*(\d+)/", file_get_contents($const), $m)) {
    echo 'config/constants.php asset_version='.$m[1]."\n";
}

echo "\nNext: run cpanel_clear_cache.php?key=12345678 then hard-refresh POS.\n";
echo "Delete this diagnose file after.\n";
