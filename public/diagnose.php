<?php
/**
 * VsoftPOS Diagnostic Script
 * Upload to public/ and visit to check server issues.
 */

$root = dirname(__DIR__);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Diagnostics</title>';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<style>body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;padding:40px 16px;margin:0;}';
echo '.container{max-width:700px;margin:0 auto;background:#fff;border-radius:24px;box-shadow:0 4px 24px rgba(0,0,0,.06);padding:36px 32px;}';
echo 'h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 4px;}p.sub{color:#64748b;font-size:.9rem;margin:0 0 24px;}';
echo 'hr{border:none;border-top:1px solid #e2e8f0;margin:20px 0;}';
echo '.ok{color:#059669;background:#ecfdf5;padding:10px 14px;border-radius:10px;margin:6px 0;font-size:.9rem;font-weight:500;}';
echo '.warn{color:#dc2626;background:#fef2f2;padding:10px 14px;border-radius:10px;margin:6px 0;font-size:.9rem;font-weight:500;}';
echo '.info{color:#2563eb;background:#eff6ff;padding:10px 14px;border-radius:10px;margin:6px 0;font-size:.9rem;font-weight:500;}';
echo 'code{background:#f1f5f9;padding:2px 6px;border-radius:6px;font-size:.85rem;color:#334155;}';
echo 'table{width:100%;border-collapse:collapse;font-size:.9rem;color:#475569;}';
echo 'td{padding:10px 0;border-bottom:1px solid #f1f5f9;}';
echo 'td:first-child{font-weight:600;color:#334155;width:40%;}';
echo '.footer{margin-top:24px;text-align:center;font-size:.8rem;color:#94a3b8;}</style></head><body>';
echo '<div class="container"><h1>VsoftPOS Diagnostics</h1><p class="sub">Checking server configuration and common issues.</p>';

function ok($m) { echo '<div class="ok">✅ ' . $m . '</div>'; }
function warn($m) { echo '<div class="warn">⚠️ ' . $m . '</div>'; }
function info($m) { echo '<div class="info">ℹ️ ' . $m . '</div>'; }

// 1. PHP Version
info('PHP Version: ' . PHP_VERSION);

// 2. Check .env
$envFile = $root . '/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    preg_match('/APP_URL=(.*)/', $env, $m);
    $appUrl = isset($m[1]) ? trim($m[1]) : 'NOT SET';
    if (strpos($appUrl, '/public') !== false) {
        warn('APP_URL has /public: ' . $appUrl);
    } else {
        ok('APP_URL: ' . $appUrl);
    }
    
    preg_match('/APP_ENV=(.*)/', $env, $m);
    info('APP_ENV: ' . (isset($m[1]) ? trim($m[1]) : 'NOT SET'));
    
    preg_match('/SESSION_DOMAIN=(.*)/', $env, $m);
    info('SESSION_DOMAIN: ' . (isset($m[1]) ? trim($m[1]) : 'NOT SET'));
    
    preg_match('/SESSION_SECURE_COOKIE=(.*)/', $env, $m);
    info('SESSION_SECURE_COOKIE: ' . (isset($m[1]) ? trim($m[1]) : 'NOT SET'));
} else {
    warn('.env file not found!');
}

echo '<hr>';

// 3. Check directory structure
info('Checking directory structure...');
$dirs = [
    'Root' => $root,
    'public/' => $root . '/public',
    'storage/' => $root . '/storage',
    'storage/framework/' => $root . '/storage/framework',
    'storage/framework/sessions' => $root . '/storage/framework/sessions',
    'storage/framework/views' => $root . '/storage/framework/views',
    'storage/framework/cache' => $root . '/storage/framework/cache',
    'bootstrap/cache' => $root . '/bootstrap/cache',
];

echo '<table>';
foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $writable = is_writable($path);
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $status = $writable ? '✅ Writable' : '❌ NOT Writable';
        $color = $writable ? '#059669' : '#dc2626';
        echo '<tr><td>' . $name . '</td><td>' . $perms . ' — <span style="color:' . $color . '">' . $status . '</span></td></tr>';
    } else {
        echo '<tr><td>' . $name . '</td><td style="color:#dc2626;">❌ Directory missing</td></tr>';
    }
}
echo '</table>';

echo '<hr>';

// 4. Test session write
info('Testing session write...');
$sessionDir = $root . '/storage/framework/sessions';
$testFile = $sessionDir . '/test_' . time() . '.txt';
if (is_writable($sessionDir)) {
    if (file_put_contents($testFile, 'test')) {
        if (file_get_contents($testFile) === 'test') {
            ok('Session directory is writable and readable');
        } else {
            warn('Can write but cannot read session files');
        }
        unlink($testFile);
    } else {
        warn('Cannot write to session directory');
    }
} else {
    warn('Session directory is NOT writable — this causes 419 errors!');
}

// 5. Check current user
$serverUser = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : (getenv('USER') ?: 'unknown');
info('Server user: ' . $serverUser);
$serverGroup = function_exists('posix_getgrgid') ? posix_getgrgid(posix_getegid())['name'] : 'unknown';
info('Server group: ' . $serverGroup);

// 6. Check .htaccess
$htaccess = $root . '/.htaccess';
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    if (strpos($content, 'RewriteRule ^(.*)$ /public/$1') !== false) {
        ok('.htaccess has internal rewrite to public/');
    } else {
        warn('.htaccess missing internal rewrite rule');
    }
    if (strpos($content, 'THE_REQUEST') !== false) {
        warn('.htaccess has broken THE_REQUEST redirect rule — remove it!');
    }
} else {
    warn('.htaccess not found in root');
}

echo '<hr>';
info('If session directory is NOT writable, run this in cPanel SSH or terminal:');
echo '<pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;font-size:.85rem;overflow-x:auto;">chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R youruser:youruser storage/ bootstrap/cache/</pre>';

echo '<div class="footer">Delete diagnose.php after use for security.</div>';
echo '</div></body></html>';
