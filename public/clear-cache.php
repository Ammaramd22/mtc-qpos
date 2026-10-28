<?php
/**
 * Clear Laravel caches
 * Upload to public/ and visit in browser.
 */

$root = dirname(__DIR__);

echo "<h2>Clearing Caches...</h2>";

// 1. Config cache
$configCache = $root . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    unlink($configCache);
    echo "<p>✅ Config cache cleared</p>";
}

// 2. Route cache
$routeCache = $root . '/bootstrap/cache/routes-v7.php';
if (file_exists($routeCache)) {
    unlink($routeCache);
    echo "<p>✅ Route cache cleared</p>";
}

// 3. View cache
$viewPath = $root . '/storage/framework/views';
if (is_dir($viewPath)) {
    $files = glob($viewPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
    echo "<p>✅ View cache cleared</p>";
}

// 4. Application cache
$cachePath = $root . '/storage/framework/cache/data';
if (is_dir($cachePath)) {
    $files = glob($cachePath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
    echo "<p>✅ Application cache cleared</p>";
}

// 5. Session files
$sessionPath = $root . '/storage/framework/sessions';
if (is_dir($sessionPath)) {
    $files = glob($sessionPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    echo "<p>✅ $count session files cleared</p>";
}

echo "<hr><h3>Done!</h3>";
echo "<p>Now <b>clear browser cookies</b> and visit login again.</p>";
echo "<p><b>Delete this file after use.</b></p>";
