<?php
/**
 * VsoftPOS Manual Update Script — No Git Required
 * Upload to public/ via cPanel File Manager and visit in browser.
 */

set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);

function ok($msg) { echo '<div style="margin:6px 0;padding:10px 14px;border-radius:10px;background:#ecfdf5;color:#059669;font-size:.9rem;font-weight:500;">✅ ' . $msg . '</div>'; }
function warn($msg) { echo '<div style="margin:6px 0;padding:10px 14px;border-radius:10px;background:#fef2f2;color:#dc2626;font-size:.9rem;font-weight:500;">⚠️ ' . $msg . '</div>'; }
function info($msg) { echo '<div style="margin:6px 0;padding:10px 14px;border-radius:10px;background:#eff6ff;color:#2563eb;font-size:.9rem;font-weight:500;">ℹ️ ' . $msg . '</div>'; }

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manual Update VsoftPOS</title>';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<style>body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;padding:40px 16px;margin:0;}';
echo '.container{max-width:640px;margin:0 auto;background:#fff;border-radius:24px;box-shadow:0 4px 24px rgba(0,0,0,.06);padding:36px 32px;}';
echo 'h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 4px;}p.sub{color:#64748b;font-size:.9rem;margin:0 0 24px;}';
echo 'hr{border:none;border-top:1px solid #e2e8f0;margin:20px 0;}';
echo '.btn{display:inline-block;padding:12px 24px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:600;text-decoration:none;box-shadow:0 4px 16px rgba(37,99,235,.3);margin-top:8px;}';
echo '.footer{margin-top:24px;text-align:center;font-size:.8rem;color:#94a3b8;}';
echo 'code{background:#f1f5f9;padding:2px 6px;border-radius:6px;font-size:.85rem;color:#334155;}</style></head><body>';
echo '<div class="container"><h1>Manual Update VsoftPOS</h1><p class="sub">No Git needed. This script directly fixes files on your server.</p>';

// --- 1. Fix .env ---
try {
    $envFile = $root . '/.env';
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        $changed = false;
        
        // Remove /public from APP_URL
        if (strpos($env, 'APP_URL=https://demo.vsoftpos.lk/public') !== false) {
            $env = str_replace('APP_URL=https://demo.vsoftpos.lk/public', 'APP_URL=https://demo.vsoftpos.lk', $env);
            ok('Fixed APP_URL (removed /public)');
            $changed = true;
        }
        
        // Fix localhost APP_URL
        if (strpos($env, 'APP_URL=https://demo.vsoftpos.lk') === false) {
            $env = preg_replace('/APP_URL=.*/', 'APP_URL=https://demo.vsoftpos.lk', $env);
            ok('Fixed APP_URL to https://demo.vsoftpos.lk');
            $changed = true;
        } else {
            if (!$changed) ok('.env APP_URL already correct');
        }
        
        // Session settings
        $needed = [
            'SESSION_DOMAIN=.vsoftpos.lk',
            'SESSION_SECURE_COOKIE=false',
            'SESSION_SAME_SITE=lax',
        ];
        foreach ($needed as $line) {
            $key = explode('=', $line)[0];
            if (strpos($env, $key . '=') === false) {
                $env .= "\n" . $line;
                ok('Added ' . $key);
                $changed = true;
            } else {
                $env = preg_replace('/' . preg_quote($key, '/') . '=.*/', $line, $env);
            }
        }
        
        file_put_contents($envFile, $env);
    } else {
        warn('.env file not found');
    }
} catch (Exception $e) {
    warn('.env error: ' . $e->getMessage());
}

// --- 2. Fix .htaccess ---
$htaccessContent = '<IfModule mod_rewrite.c>
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

$htaccessFile = $root . '/.htaccess';
try {
    if (file_exists($htaccessFile)) {
        $current = file_get_contents($htaccessFile);
        if (strpos($current, 'RewriteRule ^(.*)$ /public/$1 [L]') === false) {
            file_put_contents($htaccessFile, $htaccessContent);
            ok('.htaccess updated');
        } else {
            ok('.htaccess already correct');
        }
    } else {
        file_put_contents($htaccessFile, $htaccessContent);
        ok('.htaccess created');
    }
} catch (Exception $e) {
    warn('.htaccess error: ' . $e->getMessage());
}

// --- 3. Fix public/.htaccess (add DirectoryIndex if missing) ---
$publicHtaccess = $root . '/public/.htaccess';
try {
    if (file_exists($publicHtaccess)) {
        $current = file_get_contents($publicHtaccess);
        if (strpos($current, 'DirectoryIndex') === false) {
            file_put_contents($publicHtaccess, "DirectoryIndex index.php\n\n" . $current);
            ok('Added DirectoryIndex to public/.htaccess');
        } else {
            ok('public/.htaccess already has DirectoryIndex');
        }
    } else {
        warn('public/.htaccess not found');
    }
} catch (Exception $e) {
    warn('public/.htaccess error: ' . $e->getMessage());
}

echo '<hr>';

// --- 4. Clear Laravel caches ---
try {
    $caches = [
        $root . '/bootstrap/cache/config.php',
        $root . '/bootstrap/cache/routes-v7.php',
        $root . '/bootstrap/cache/packages.php',
        $root . '/bootstrap/cache/services.php',
    ];
    foreach ($caches as $file) {
        if (file_exists($file)) {
            unlink($file);
            ok('Deleted ' . basename($file));
        }
    }
    
    $views = glob($root . '/storage/framework/views/*');
    $c = 0;
    foreach ($views as $f) { if (is_file($f)) { unlink($f); $c++; } }
    if ($c) ok("Cleared $c compiled views");
    
    $cacheData = glob($root . '/storage/framework/cache/data/*');
    $c2 = 0;
    foreach ($cacheData as $f) { if (is_file($f)) { unlink($f); $c2++; } }
    if ($c2) ok("Cleared $c2 cache files");
    
    $sessions = glob($root . '/storage/framework/sessions/*');
    $c3 = 0;
    foreach ($sessions as $f) { if (is_file($f)) { unlink($f); $c3++; } }
    if ($c3) ok("Cleared $c3 old sessions");
} catch (Exception $e) {
    warn('Cache clear issue: ' . $e->getMessage());
}

echo '<hr>';
info('Next steps:');
echo '<ol style="color:#475569;font-size:.9rem;line-height:1.8;margin:10px 0 0 18px;">';
echo '<li><b>Clear browser cache & cookies</b> for demo.vsoftpos.lk<br><code>Ctrl+Shift+Delete</code> → Cached images & files → All time</li>';
echo '<li><b>Test login:</b> Visit <code>https://demo.vsoftpos.lk/login</code></li>';
echo '<li><b>Test logout:</b> Log in → click logout → should redirect to login cleanly</li>';
echo '</ol>';

echo '<div style="text-align:center;margin-top:20px;"><a href="/manual-update.php" class="btn">Run Again</a></div>';
echo '<div class="footer">Delete <code>manual-update.php</code> after use for security.</div>';
echo '</div></body></html>';
