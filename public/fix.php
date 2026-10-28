<?php
/**
 * Fix session and cache for demo.vsoftpos.lk
 * Upload to public/ and visit in browser.
 */

$root = dirname(__DIR__);
$envFile = $root . '/.env';

echo "<h2>Fixing VsoftPOS...</h2>";

// 1. Fix .env
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    $env = preg_replace('/^APP_URL=.*$/m', 'APP_URL=https://demo.vsoftpos.lk', $env);
    
    $settings = [
        'SESSION_DOMAIN=.vsoftpos.lk',
        'SESSION_SECURE_COOKIE=false',
        'SESSION_SAME_SITE=lax',
    ];
    foreach ($settings as $line) {
        $key = explode('=', $line)[0];
        if (strpos($env, $key . '=') === false) {
            $env .= "\n" . $line;
        } else {
            $env = preg_replace('/' . $key . '=.*/', $line, $env);
        }
    }
    
    file_put_contents($envFile, $env);
    echo "<p>✅ .env fixed</p>";
} else {
    echo "<p>❌ .env not found</p>";
}

// 2. Fix root .htaccess
$htaccessFile = $root . '/.htaccess';
$correctHtaccess = '<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Do not rewrite if already pointing to public/
    RewriteCond %{REQUEST_URI} !^/public/

    # Rewrite everything to public/ (internal rewrite, preserves POST/CSRF)
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
';

if (file_exists($htaccessFile)) {
    $current = file_get_contents($htaccessFile);
    if (strpos($current, 'RewriteRule ^(.*)$ /public/$1 [L]') === false) {
        file_put_contents($htaccessFile, $correctHtaccess);
        echo "<p>✅ .htaccess updated</p>";
    } else {
        echo "<p>✅ .htaccess already correct</p>";
    }
} else {
    file_put_contents($htaccessFile, $correctHtaccess);
    echo "<p>✅ .htaccess created</p>";
}

// 3. Clear caches manually
$caches = [
    $root . '/bootstrap/cache/config.php',
    $root . '/bootstrap/cache/routes-v7.php',
    $root . '/bootstrap/cache/packages.php',
    $root . '/bootstrap/cache/services.php',
];
foreach ($caches as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "<p>✅ Deleted " . basename($file) . "</p>";
    }
}

// Clear view cache
$views = glob($root . '/storage/framework/views/*');
$count = 0;
foreach ($views as $f) { if (is_file($f)) { unlink($f); $count++; } }
if ($count) echo "<p>✅ Cleared $count view files</p>";

// Clear app cache
$cacheFiles = glob($root . '/storage/framework/cache/data/*');
$count2 = 0;
foreach ($cacheFiles as $f) { if (is_file($f)) { unlink($f); $count2++; } }
if ($count2) echo "<p>✅ Cleared $count2 cache files</p>";

// Clear sessions
$sessionFiles = glob($root . '/storage/framework/sessions/*');
$count3 = 0;
foreach ($sessionFiles as $f) { if (is_file($f)) { unlink($f); $count3++; } }
if ($count3) echo "<p>✅ Cleared $count3 session files</p>";

echo "<hr><h3>NEXT:</h3>";
echo "<p>1. Clear browser cookies for demo.vsoftpos.lk</p>";
echo "<p>2. Open login in Incognito/Private window</p>";
echo "<p>3. <b>DELETE this fix.php file after!</b></p>";
