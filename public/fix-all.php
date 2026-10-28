<?php
/**
 * ONE-CLICK FIX for demo.vsoftpos.lk
 * Upload this file to public/ on cPanel and visit in browser.
 */

$root = dirname(__DIR__);
$envFile = $root . '/.env';

echo "<h2>VsoftPOS Server Fix</h2>";

// 1. Fix .env
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);

    // Ensure APP_URL is correct
    $env = preg_replace('/^APP_URL=.*$/m', 'APP_URL=https://demo.vsoftpos.lk', $env);

    // Add or fix session settings
    $settings = [
        'SESSION_DOMAIN' => '.vsoftpos.lk',
        'SESSION_SECURE_COOKIE' => 'false',
        'SESSION_SAME_SITE' => 'lax',
    ];

    foreach ($settings as $key => $value) {
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line = $key . '=' . $value;
        if (preg_match($pattern, $env)) {
            $env = preg_replace($pattern, $line, $env);
        } else {
            $env .= "\n" . $line;
        }
    }

    file_put_contents($envFile, $env);
    echo "<p>✅ .env updated</p>";
} else {
    echo "<p>❌ .env NOT FOUND at $envFile</p>";
}

// 2. Clear Laravel caches
$artisan = $root . '/artisan';
if (file_exists($artisan)) {
    passthru('cd ' . escapeshellarg($root) . ' && php artisan config:clear 2>&1');
    passthru('cd ' . escapeshellarg($root) . ' && php artisan cache:clear 2>&1');
    passthru('cd ' . escapeshellarg($root) . ' && php artisan view:clear 2>&1');
    echo "<p>✅ Laravel caches cleared</p>";
} else {
    echo "<p>⚠️ artisan not found at $artisan</p>";
}

echo "<hr><h3>NEXT STEPS (VERY IMPORTANT):</h3>";
echo "<ol>";
echo "<li><b>Clear browser cookies</b> for demo.vsoftpos.lk (Ctrl+Shift+Delete → Cookies)</li>";
echo "<li>OR open a <b>Private/Incognito</b> window</li>";
echo "<li>Visit: <a href='https://demo.vsoftpos.lk/public/login'>https://demo.vsoftpos.lk/public/login</a></li>";
echo "</ol>";
echo "<p><b>After testing, DELETE this fix-all.php file from public/ for security.</b></p>";
