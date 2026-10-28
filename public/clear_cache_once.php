<?php
/**
 * One-time cache clearer for cPanel (no SSH).
 *
 * Upload to: public_html/public/clear_cache_once.php
 * Open:     https://YOUR-DOMAIN.com/clear_cache_once.php?key=qposclear2026
 * Then DELETE this file from the server.
 */

$key = $_GET['key'] ?? '';
if ($key !== 'qposclear2026') {
    http_response_code(403);
    echo 'Forbidden. Use ?key=qposclear2026';
    exit;
}

$base = dirname(__DIR__); // project root (parent of /public)
$targets = [
    $base.'/storage/framework/views',
    $base.'/storage/framework/cache/data',
    $base.'/storage/framework/cache',
    $base.'/bootstrap/cache',
];

header('Content-Type: text/plain; charset=utf-8');
echo "QPOS cache clear\n";
echo "Root: {$base}\n\n";

$deleted = 0;
$failed = 0;

foreach ($targets as $dir) {
    if (! is_dir($dir)) {
        echo "[skip] not found: {$dir}\n";
        continue;
    }
    echo "[dir] {$dir}\n";
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $path = $file->getPathname();
        // Keep folder itself; remove files and nested dirs inside
        if ($path === $dir) {
            continue;
        }
        // Do not delete .gitignore placeholders
        if (substr($file->getFilename(), 0, 1) === '.' && $file->getFilename() === '.gitignore') {
            continue;
        }
        $ok = $file->isDir() ? @rmdir($path) : @unlink($path);
        if ($ok) {
            $deleted++;
            echo "  deleted: {$path}\n";
        } else {
            $failed++;
            echo "  FAILED: {$path}\n";
        }
    }
}

// Remove config/route/services cache files if present
foreach (['config.php', 'routes-v7.php', 'routes.php', 'services.php', 'packages.php'] as $cacheFile) {
    $p = $base.'/bootstrap/cache/'.$cacheFile;
    if (is_file($p)) {
        if (@unlink($p)) {
            $deleted++;
            echo "deleted bootstrap cache: {$cacheFile}\n";
        } else {
            $failed++;
            echo "FAILED bootstrap cache: {$cacheFile}\n";
        }
    }
}

echo "\nDone. Deleted={$deleted} Failed={$failed}\n";
echo "Now hard-refresh the site (Cmd+Shift+R).\n";
echo "IMPORTANT: delete this file (public/clear_cache_once.php) after use.\n";
