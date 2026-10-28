<?php
/**
 * Fix session/cookie config for demo.vsoftpos.lk
 * Upload to public/ and visit in browser.
 */

die('<!-- REMOVE THIS LINE to run the fix -->');

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die(".env not found at: $envPath");
}

$env = file_get_contents($envPath);

// Fix APP_URL
$env = preg_replace('/APP_URL=.*/', 'APP_URL=https://demo.vsoftpos.lk', $env);

// Fix SESSION settings
$lines = [
    'SESSION_DOMAIN=.vsoftpos.lk',
    'SESSION_SECURE_COOKIE=false',
    'SESSION_SAME_SITE=lax',
];
foreach ($lines as $line) {
    $key = explode('=', $line)[0];
    if (strpos($env, $key . '=') === false) {
        $env .= "\n" . $line;
    } else {
        $env = preg_replace('/' . $key . '=.*/', $line, $env);
    }
}

file_put_contents($envPath, $env);
echo "<p>.env updated successfully.</p>";

// Clear caches
$artisan = __DIR__ . '/../artisan';
if (file_exists($artisan)) {
    passthru('cd ' . escapeshellarg(dirname($artisan)) . ' && php artisan config:clear 2>&1');
    passthru('cd ' . escapeshellarg(dirname($artisan)) . ' && php artisan cache:clear 2>&1');
    passthru('cd ' . escapeshellarg(dirname($artisan)) . ' && php artisan view:clear 2>&1');
    echo "<p>Laravel caches cleared.</p>";
}

echo "<h2>Done!</h2>";
echo "<p><b>IMPORTANT:</b> Now clear browser cookies for demo.vsoftpos.lk and reload.</p>";
echo "<p>Ctrl+Shift+Delete → Cookies → demo.vsoftpos.lk → Clear</p>";
