<?php
/**
 * QPOS pre-install — PHP extensions & permissions check (QPOS theme).
 * Runs before Laravel boots (no .env yet).
 */
if (empty($_POST)) {
    $envPath = realpath(__DIR__ . '/../../') . '/.env';
    if (file_exists($envPath)) {
        exit('Installation already done. Delete this check is skipped because .env exists.');
    }
}

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/install/');
if (strpos($actual_link, 'install/index.php') !== false) {
    $install_link = str_replace('install/index.php', 'install-start', $actual_link);
} else {
    $install_link = str_replace('install/', 'install-start', $actual_link);
}
// strip query
$install_link = preg_replace('/\?.*$/', '', $install_link);

$checks = [];
$checks['php'] = (PHP_MAJOR_VERSION > 8 || (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION >= 1));
$checks['php_version'] = PHP_VERSION;
$checks['openssl'] = extension_loaded('openssl');
$checks['pdo'] = extension_loaded('pdo');
$checks['mbstring'] = extension_loaded('mbstring');
$checks['tokenizer'] = extension_loaded('tokenizer');
$checks['xml'] = extension_loaded('xml');
$checks['curl'] = extension_loaded('curl');
$checks['zip'] = extension_loaded('zip');
$checks['gd'] = extension_loaded('gd');
$checks['bcmath'] = extension_loaded('bcmath');
$checks['fileinfo'] = extension_loaded('fileinfo');
$checks['json'] = extension_loaded('json');

$storage_path = realpath(__DIR__ . '/../../storage/') ?: (__DIR__ . '/../../storage');
$log_path = realpath(__DIR__ . '/../../storage/logs/') ?: (__DIR__ . '/../../storage/logs');
$framework = realpath(__DIR__ . '/../../storage/framework/') ?: (__DIR__ . '/../../storage/framework');
$cache_path = realpath(__DIR__ . '/../../bootstrap/cache') ?: (__DIR__ . '/../../bootstrap/cache');
$upload_path = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');

$checks['storage'] = is_writable($storage_path) && is_writable($log_path) && is_writable($framework);
$checks['cache'] = is_writable($cache_path);
$checks['uploads'] = is_dir($upload_path) && is_writable($upload_path);

$all_ok = true;
foreach (['php','openssl','pdo','mbstring','tokenizer','xml','curl','zip','gd','bcmath','fileinfo','json','storage','cache','uploads'] as $k) {
    if (empty($checks[$k])) {
        $all_ok = false;
        break;
    }
}

$logo = '../img/vsoft_logo.png';
if (!is_file(__DIR__ . '/../img/vsoft_logo.png')) {
    $logo = '../img/qpos-icon.png';
}

function qpos_check_row(string $label, bool $ok, string $extra = ''): void
{
    $cls = $ok ? 'pass' : 'fail';
    $icon = $ok ? '✓' : '✗';
    echo '<div class="check-item '.$cls.'"><span>'.htmlspecialchars($label);
    if ($extra !== '') {
        echo ' <small>'.htmlspecialchars($extra).'</small>';
    }
    echo '</span><span class="icon">'.$icon.'</span></div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Check - QPOS</title>
    <style>
        :root { --qpos-green: #00935F; --qpos-green-2: #00b377; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 720px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
        }
        .header {
            background: linear-gradient(135deg, var(--qpos-green) 0%, var(--qpos-green-2) 100%);
            color: #fff;
            text-align: center;
            padding: 32px;
        }
        .header img {
            width: 72px; height: 72px; border-radius: 16px;
            margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.2);
            object-fit: cover; background: #fff;
        }
        .header h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; }
        .header p { margin: 0; opacity: .9; font-size: 14px; }
        .body { padding: 32px; }
        .nav {
            display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px;
        }
        .nav span {
            flex: 1; text-align: center; padding: 12px 8px; font-size: 13px;
            font-weight: 500; color: #9ca3af; margin-bottom: -2px;
            border-bottom: 2px solid transparent;
        }
        .nav span.active { color: var(--qpos-green); border-bottom-color: var(--qpos-green); }
        .section-title {
            font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 12px;
        }
        .check-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 13px;
        }
        .check-item.pass { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .check-item.fail { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .check-item small { opacity: .75; font-weight: 400; }
        .check-item .icon { font-weight: 700; }
        .actions {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e7eb;
        }
        .btn {
            display: inline-block; text-decoration: none;
            background: linear-gradient(135deg, var(--qpos-green) 0%, var(--qpos-green-2) 100%);
            color: #fff; border: none; border-radius: 8px; padding: 12px 28px;
            font-size: 14px; font-weight: 600;
        }
        .btn[disabled], .btn.is-disabled {
            opacity: .5; pointer-events: none; cursor: not-allowed;
        }
        .btn-secondary {
            background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db;
        }
        .banner {
            border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px;
        }
        .banner.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .banner.bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .footer {
            text-align: center; padding: 16px 32px; font-size: 12px; color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
        .hint { font-size: 12px; color: #6b7280; margin-top: 8px; line-height: 1.5; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <img src="<?php echo htmlspecialchars($logo); ?>" alt="QPOS">
        <h1>QPOS Installation</h1>
        <p>Server extensions &amp; permissions</p>
    </div>
    <div class="body">
        <div class="nav">
            <span class="active">Server Check</span>
            <span>Configuration</span>
            <span>Complete</span>
        </div>

        <?php if ($all_ok): ?>
            <div class="banner ok">All checks passed. Continue to database, mail, and business-module setup.</div>
        <?php else: ?>
            <div class="banner bad">Some requirements failed. Fix them on the server, then refresh this page.</div>
        <?php endif; ?>

        <div class="section-title">PHP &amp; extensions</div>
        <?php
        qpos_check_row('PHP >= 8.1', (bool) $checks['php'], 'Current: '.$checks['php_version']);
        qpos_check_row('OpenSSL', (bool) $checks['openssl']);
        qpos_check_row('PDO', (bool) $checks['pdo']);
        qpos_check_row('Mbstring', (bool) $checks['mbstring']);
        qpos_check_row('Tokenizer', (bool) $checks['tokenizer']);
        qpos_check_row('XML', (bool) $checks['xml']);
        qpos_check_row('cURL', (bool) $checks['curl']);
        qpos_check_row('ZIP', (bool) $checks['zip']);
        qpos_check_row('GD', (bool) $checks['gd']);
        qpos_check_row('BCMath', (bool) $checks['bcmath']);
        qpos_check_row('Fileinfo', (bool) $checks['fileinfo']);
        qpos_check_row('JSON', (bool) $checks['json']);
        ?>

        <div class="section-title" style="margin-top:20px;">Directory permissions</div>
        <?php
        qpos_check_row('storage/ writable', (bool) $checks['storage']);
        qpos_check_row('bootstrap/cache writable', (bool) $checks['cache']);
        qpos_check_row('public/uploads writable', (bool) $checks['uploads']);
        ?>

        <p class="hint">
            Next step: create empty MySQL database, then enter DB + mail details and pick modules
            (Nutmeg, Mobile shop, Communication, Restaurant, …).
        </p>

        <div class="actions">
            <a class="btn btn-secondary" href="javascript:location.reload()">Refresh checks</a>
            <?php if ($all_ok): ?>
                <a class="btn" href="<?php echo htmlspecialchars($install_link); ?>">Continue to setup →</a>
            <?php else: ?>
                <a class="btn is-disabled" href="#" disabled>Continue to setup →</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer">QPOS — Smart Point of Sale System</div>
</div>
</body>
</html>
