<?php
/**
 * VsoftPOS Deploy Script
 * Upload this to your cPanel public/ folder and visit it in browser.
 * It downloads the latest changed files from GitHub.
 */

ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

$baseUrl = 'https://raw.githubusercontent.com/Aadhi06/VsoftPOS/main/';

$files = [
    'app/Providers/AppServiceProvider.php',
    'app/Http/Controllers/BusinessController.php',
    'resources/views/layouts/partials/header.blade.php',
    'resources/views/layouts/app.blade.php',
    'lang/en/lang_v1.php',
];

echo "<pre style='font-family:monospace; font-size:14px;'>";
echo "<h2>VsoftPOS Deploy Update</h2>";
echo "Downloading files from GitHub...\n\n";

$success = 0;
$failed = 0;

foreach ($files as $file) {
    $url = $baseUrl . $file;
    $localPath = __DIR__ . '/../' . $file;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $content && strpos($content, '<!DOCTYPE') === false) {
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (file_put_contents($localPath, $content)) {
            echo "[OK] $file\n";
            $success++;
        } else {
            echo "[FAIL] $file - cannot write\n";
            $failed++;
        }
    } else {
        echo "[FAIL] $file - HTTP $httpCode\n";
        $failed++;
    }
}

echo "\n";
echo "==========================\n";
echo "Success: $success | Failed: $failed\n";
echo "==========================\n\n";

if ($failed === 0) {
    echo "<span style='color:green'><b>All files updated successfully!</b></span>\n";
    echo "\nNext steps:\n";
    echo "1. Delete this deploy.php file now (security).\n";
    echo "2. Clear browser cache (Ctrl+Shift+R).\n";
    echo "3. Refresh your dashboard.\n";
} else {
    echo "<span style='color:red'><b>Some files failed. Check permissions.</b></span>\n";
}

echo "</pre>";
