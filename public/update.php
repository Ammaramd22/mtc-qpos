<?php
/**
 * VsoftPOS Auto Update Script for cPanel
 * Upload to public/ and visit in browser.
 */

set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
$results = [];

function result($icon, $message, $status = 'info') {
    $color = $status === 'success' ? '#059669' : ($status === 'error' ? '#dc2626' : '#2563eb');
    return '<div style="margin: 6px 0; padding: 10px 14px; border-radius: 10px; background: ' . ($status==='success'?'#ecfdf5':($status==='error'?'#fef2f2':'#eff6ff')) . '; color: ' . $color . '; font-size: 0.9rem; font-weight: 500;">' . $icon . ' ' . $message . '</div>';
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Update VsoftPOS</title>';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<style>body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;padding:40px 16px;margin:0;}';
echo '.container{max-width:640px;margin:0 auto;background:#fff;border-radius:24px;box-shadow:0 4px 24px rgba(0,0,0,.06);padding:36px 32px;}';
echo 'h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 4px;}p.sub{color:#64748b;font-size:0.9rem;margin:0 0 24px;}';
echo 'hr{border:none;border-top:1px solid #e2e8f0;margin:20px 0;}';
echo '.btn{display:inline-block;padding:12px 24px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:600;text-decoration:none;box-shadow:0 4px 16px rgba(37,99,235,.3);}';
echo '.btn:hover{box-shadow:0 6px 24px rgba(37,99,235,.5);}';
echo '.footer{margin-top:24px;text-align:center;font-size:0.8rem;color:#94a3b8;}</style></head><body>';
echo '<div class="container"><h1>VsoftPOS Auto Update</h1><p class="sub">This script will update your files and fix common issues.</p>';

// 1. Git Pull
if (function_exists('exec')) {
    $gitAvailable = shell_exec('which git');
    if (!empty($gitAvailable)) {
        chdir($root);
        $output = [];
        $return = 0;
        exec('git pull 2>&1', $output, $return);
        $gitOutput = implode("<br>", array_map('htmlspecialchars', $output));
        if ($return === 0) {
            echo result('✅', 'Git pull completed successfully', 'success');
            echo '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:0.8rem;color:#475569;margin-top:8px;">' . $gitOutput . '</div>';
        } else {
            echo result('⚠️', 'Git pull had issues — please update manually via cPanel File Manager or Git', 'error');
            echo '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px;font-size:0.8rem;color:#991b1b;margin-top:8px;">' . $gitOutput . '</div>';
        }
    } else {
        echo result('⚠️', 'Git not available — update files manually via cPanel File Manager', 'error');
    }
} else {
    echo result('⚠️', 'exec() disabled — update files manually via cPanel File Manager', 'error');
}

echo '<hr>';

// 2. Fix .env
try {
    $envFile = $root . '/.env';
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        $changes = false;
        
        // Fix APP_URL (remove /public)
        if (preg_match('/APP_URL=https:\/\/demo\.vsoftpos\.lk\/public/', $env)) {
            $env = preg_replace('/APP_URL=https:\/\/demo\.vsoftpos\.lk\/public/', 'APP_URL=https://demo.vsoftpos.lk', $env);
            $changes = true;
            echo result('✅', 'Fixed APP_URL (removed /public)', 'success');
        }
        
        // Ensure APP_URL is correct
        if (strpos($env, 'APP_URL=https://demo.vsoftpos.lk') === false && strpos($env, 'APP_URL=http://localhost') !== false) {
            $env = preg_replace('/APP_URL=http:\/\/localhost(:\d+)?/', 'APP_URL=https://demo.vsoftpos.lk', $env);
            $changes = true;
            echo result('✅', 'Fixed APP_URL to https://demo.vsoftpos.lk', 'success');
        }
        
        // Ensure session settings exist
        $settings = [
            'SESSION_DOMAIN=.vsoftpos.lk',
            'SESSION_SECURE_COOKIE=false',
            'SESSION_SAME_SITE=lax',
        ];
        foreach ($settings as $line) {
            $key = explode('=', $line)[0];
            if (strpos($env, $key . '=') === false) {
                $env .= "\n" . $line;
                $changes = true;
                echo result('✅', 'Added ' . $key, 'success');
            } else {
                $env = preg_replace('/' . $key . '=.*/', $line, $env);
            }
        }
        
        file_put_contents($envFile, $env);
        if (!$changes) {
            echo result('✅', '.env already correct', 'success');
        }
    } else {
        echo result('❌', '.env file not found', 'error');
    }
} catch (Exception $e) {
    echo result('❌', '.env error: ' . $e->getMessage(), 'error');
}

// 3. Fix .htaccess
$htaccessFile = $root . '/.htaccess';
$correctHtaccess = '<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect /public/ URLs to clean URLs
    RewriteCond %{THE_REQUEST} \s/public/ [NC]
    RewriteRule ^public/(.*)$ /$1 [R=301,L]

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
    if (strpos($current, 'THE_REQUEST') === false) {
        file_put_contents($htaccessFile, $correctHtaccess);
        echo result('✅', '.htaccess updated with redirect fix', 'success');
    } else {
        echo result('✅', '.htaccess already correct', 'success');
    }
} else {
    file_put_contents($htaccessFile, $correctHtaccess);
    echo result('✅', '.htaccess created', 'success');
}

// 4. Clear caches
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
            echo result('✅', 'Deleted cache: ' . basename($file), 'success');
        }
    }
    
    // Clear view cache
    $views = glob($root . '/storage/framework/views/*');
    $count = 0;
    foreach ($views as $f) { if (is_file($f)) { unlink($f); $count++; } }
    if ($count) {
        echo result('✅', "Cleared $count compiled view files", 'success');
    }
    
    // Clear app cache
    $cacheFiles = glob($root . '/storage/framework/cache/data/*');
    $count2 = 0;
    foreach ($cacheFiles as $f) { if (is_file($f)) { unlink($f); $count2++; } }
    if ($count2) {
        echo result('✅', "Cleared $count2 cache files", 'success');
    }
    
    // Clear sessions
    $sessionFiles = glob($root . '/storage/framework/sessions/*');
    $count3 = 0;
    foreach ($sessionFiles as $f) { if (is_file($f)) { unlink($f); $count3++; } }
    if ($count3) {
        echo result('✅', "Cleared $count3 old sessions", 'success');
    }
} catch (Exception $e) {
    echo result('⚠️', 'Cache clear issue: ' . $e->getMessage(), 'error');
}

echo '<hr>';
echo '<div style="text-align:center;"><a href="/' . basename(__FILE__) . '" class="btn">Run Update Again</a></div>';
echo '<div class="footer">After updating, clear your browser cookies & cache for demo.vsoftpos.lk<br>Delete this update.php file when done for security.</div>';
echo '</div></body></html>';
