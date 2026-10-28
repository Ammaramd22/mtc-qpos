<?php
/**
 * QPOS one-shot repair for cPanel (no SSH / File Manager delete needed).
 *
 * Upload to: public_html/public/qpos_repair.php  (or wherever index.php lives)
 * Open:     https://YOUR-DOMAIN/qpos_repair.php?key=qposclear2026
 *
 * What it does:
 *  1) Clears compiled Blade views + Laravel caches (fixes "old settings page")
 *  2) Shows DB connection + current business name
 *  3) Optional test save: ?key=...&set_name=TestShop
 *  4) Optional self-delete: ?key=...&delete=1
 *
 * DELETE this file after use.
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$key = $_GET['key'] ?? '';
if ($key !== 'qposclear2026') {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Use <code>?key=qposclear2026</code></p>';
    exit;
}

$publicDir = __DIR__;
$base = dirname($publicDir); // project root

function qpos_wipe_dir(string $dir, int &$deleted, int &$failed, array &$log): void
{
    if (! is_dir($dir)) {
        $log[] = "[skip] not found: {$dir}";
        return;
    }
    $log[] = "[dir] {$dir}";
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            $ok = $file->isDir() ? @rmdir($path) : @unlink($path);
            if ($ok) {
                $deleted++;
            } else {
                $failed++;
                $log[] = "  FAILED: {$path}";
            }
        }
    } catch (Throwable $e) {
        $failed++;
        $log[] = '  ERROR: '.$e->getMessage();
    }
}

function qpos_load_env(string $base): array
{
    $env = [];
    $path = $base.'/.env';
    if (! is_file($path)) {
        return $env;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if ((strlen($v) >= 2) && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        $env[trim($k)] = $v;
    }

    return $env;
}

$deleted = 0;
$failed = 0;
$log = [];
$log[] = 'QPOS repair starting…';
$log[] = "Root: {$base}";
$log[] = "Public: {$publicDir}";

$targets = [
    $base.'/storage/framework/views',
    $base.'/storage/framework/cache/data',
    $base.'/storage/framework/cache',
    $base.'/bootstrap/cache',
];

foreach ($targets as $dir) {
    qpos_wipe_dir($dir, $deleted, $failed, $log);
}

foreach (['config.php', 'routes-v7.php', 'routes.php', 'services.php', 'packages.php'] as $cacheFile) {
    $p = $base.'/bootstrap/cache/'.$cacheFile;
    if (is_file($p)) {
        if (@unlink($p)) {
            $deleted++;
            $log[] = "deleted bootstrap cache: {$cacheFile}";
        } else {
            $failed++;
            $log[] = "FAILED bootstrap cache: {$cacheFile}";
        }
    }
}

// Touch settings blade so any remaining opcode caches see a new mtime when possible
$settingsBlade = $base.'/resources/views/business/settings.blade.php';
if (is_file($settingsBlade)) {
    @touch($settingsBlade);
    $log[] = 'touched settings.blade.php';
}

$log[] = 'PHP max_input_vars='.(string) ini_get('max_input_vars');
$log[] = 'PHP post_max_size='.(string) ini_get('post_max_size');

$settingsLogPath = $base.'/storage/logs/settings_update.log';
$settingsLogTail = '';
if (is_file($settingsLogPath)) {
    $raw = @file_get_contents($settingsLogPath);
    if ($raw !== false && $raw !== '') {
        $lines = preg_split("/\r\n|\n|\r/", trim($raw));
        $settingsLogTail = implode("\n", array_slice($lines, -15));
    }
} else {
    $settingsLogTail = '(no settings_update.log yet — Update Settings has not hit the controller)';
}

$env = qpos_load_env($base);

// Optional: rewrite APP_URL to https://current-host (no /public)
if (! empty($_GET['fix_https']) || ! empty($_GET['fix_app_url'])) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $envPath = $base.'/.env';
    if ($host && is_file($envPath) && is_writable($envPath)) {
        $raw = file_get_contents($envPath);
        // Never put /public in APP_URL — assets become /public/js/... and 404 when
        // the site is already served from public/ (or rewritten to it).
        $newUrl = 'https://'.$host;
        $newUrl = rtrim($newUrl, '/');
        if (preg_match('/^APP_URL=.*/m', $raw)) {
            $raw2 = preg_replace('/^APP_URL=.*/m', 'APP_URL="'.$newUrl.'"', $raw, 1);
        } else {
            $raw2 = rtrim($raw)."\nAPP_URL=\"{$newUrl}\"\n";
        }
        if ($raw2 !== $raw && @file_put_contents($envPath, $raw2) !== false) {
            $log[] = "Updated APP_URL to {$newUrl}";
            foreach (['config.php', 'services.php', 'packages.php'] as $cacheFile) {
                $p = $base.'/bootstrap/cache/'.$cacheFile;
                if (is_file($p)) {
                    @unlink($p);
                    $log[] = "deleted bootstrap/cache/{$cacheFile}";
                }
            }
            $env = qpos_load_env($base);
        } else {
            $log[] = 'APP_URL update failed (unchanged or not writable)';
        }
    } else {
        $log[] = 'Cannot fix APP_URL (.env missing/unwritable or no host)';
    }
}

$dbInfo = [
    'host' => $env['DB_HOST'] ?? '127.0.0.1',
    'port' => $env['DB_PORT'] ?? '3306',
    'database' => $env['DB_DATABASE'] ?? '',
    'username' => $env['DB_USERNAME'] ?? '',
    'password' => $env['DB_PASSWORD'] ?? '',
];

$bizRows = [];
$dbError = null;
$saveResult = null;

try {
    if ($dbInfo['database'] === '') {
        throw new RuntimeException('.env DB_DATABASE missing');
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbInfo['host'],
        $dbInfo['port'],
        $dbInfo['database']
    );
    $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    if (isset($_GET['set_name']) && $_GET['set_name'] !== '') {
        $newName = substr(trim((string) $_GET['set_name']), 0, 190);
        $id = (int) $pdo->query('SELECT id FROM business ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($id < 1) {
            throw new RuntimeException('No business row found');
        }
        $stmt = $pdo->prepare('UPDATE business SET name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newName, $id]);
        $saveResult = "Updated business #{$id} name to: {$newName}";
        $log[] = $saveResult;
    }

    $bizRows = $pdo->query('SELECT id, name, currency_id, updated_at FROM business ORDER BY id ASC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $dbError = $e->getMessage();
    $log[] = 'DB ERROR: '.$dbError;
}

$filesCheck = [
    'BusinessController' => $base.'/app/Http/Controllers/BusinessController.php',
    'settings.blade.php' => $base.'/resources/views/business/settings.blade.php',
    'app.js' => $publicDir.'/js/app.js',
    'common.js' => $publicDir.'/js/common.js',
    'constants.php' => $base.'/config/constants.php',
];

$self = basename(__FILE__);
$qs = 'key=qposclear2026';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QPOS Repair</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; max-width: 900px; margin: 24px auto; padding: 0 16px; }
        .ok { color: #0a7; } .bad { color: #c30; }
        code, pre { background: #f4f4f5; padding: 2px 6px; border-radius: 4px; }
        pre { padding: 12px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin: 12px 0; }
        td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin: 16px 0; }
        a.btn { display: inline-block; background: #0a7; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; margin: 4px 4px 4px 0; }
        a.btn.warn { background: #c60; }
        a.btn.danger { background: #c30; }
    </style>
</head>
<body>
    <h1>QPOS Repair</h1>
    <p>Cache clear: <strong class="<?php echo $failed ? 'bad' : 'ok'; ?>">deleted=<?php echo (int) $deleted; ?> failed=<?php echo (int) $failed; ?></strong></p>

    <div class="box">
        <h2>0) APP_URL / HTTPS (critical)</h2>
        <?php
            $appUrl = trim($env['APP_URL'] ?? '', " \t\"'");
            $appUrlIsHttp = stripos($appUrl, 'http://') === 0;
            $appUrlHasPublic = (bool) preg_match('#/public/?$#i', $appUrl);
            $appUrlBad = $appUrlIsHttp || $appUrlHasPublic || $appUrl === '';
        ?>
        <p>APP_URL in .env: <code><?php echo htmlspecialchars($appUrl); ?></code>
            <?php if ($appUrlBad): ?>
                <strong class="bad"> ← BROKEN</strong>
            <?php else: ?>
                <strong class="ok">OK</strong>
            <?php endif; ?>
        </p>
        <?php if ($appUrlHasPublic): ?>
            <p class="bad"><strong>Remove <code>/public</code> from APP_URL.</strong>
            It makes JS load from <code>/public/js/vendor.js</code> which 404s → <code>$ is not defined</code>
            and form posts go to the wrong URL → <code>Failed to fetch</code>.</p>
        <?php endif; ?>
        <?php if ($appUrlIsHttp): ?>
            <p class="bad">APP_URL is http while the site is https — browser blocks scripts.</p>
        <?php endif; ?>
        <p>Correct value: <code>https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'your-domain.com'); ?></code>
            (no <code>/public</code>)</p>
        <p><em>Missing bootstrap/cache/config.php is fine — config is not cached.</em></p>
        <?php if ($appUrlBad): ?>
            <p><a class="btn" href="?<?php echo $qs; ?>&amp;fix_app_url=1">Fix APP_URL now</a></p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>1) Files on server</h2>
        <table>
            <tr><th>File</th><th>Present</th><th>Modified</th></tr>
            <?php foreach ($filesCheck as $label => $path): ?>
                <tr>
                    <td><?php echo htmlspecialchars($label); ?></td>
                    <td class="<?php echo is_file($path) ? 'ok' : 'bad'; ?>"><?php echo is_file($path) ? 'YES' : 'MISSING'; ?></td>
                    <td><?php echo is_file($path) ? date('Y-m-d H:i:s', filemtime($path)) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
            $appJs = $publicDir.'/js/app.js';
            $hasForce = is_file($appJs) && strpos((string) @file_get_contents($appJs), 'btn_force_save_settings') !== false;
            $blade = @file_get_contents($settingsBlade) ?: '';
            $hasBtn = strpos($blade, 'btn_force_save_settings') !== false;
        ?>
        <p>Force-save button in blade: <strong class="<?php echo $hasBtn ? 'ok' : 'bad'; ?>"><?php echo $hasBtn ? 'YES (new fix uploaded)' : 'NO — upload the zip files'; ?></strong></p>
        <p>Force-save handler in app.js: <strong class="<?php echo $hasForce ? 'ok' : 'bad'; ?>"><?php echo $hasForce ? 'YES' : 'NO — upload public/js/app.js'; ?></strong></p>
    </div>

    <div class="box">
        <h2>2) Database</h2>
        <?php if ($dbError): ?>
            <p class="bad">Could not connect: <?php echo htmlspecialchars($dbError); ?></p>
        <?php else: ?>
            <p class="ok">Connected to <code><?php echo htmlspecialchars($dbInfo['database']); ?></code></p>
            <?php if ($saveResult): ?>
                <p class="ok"><strong><?php echo htmlspecialchars($saveResult); ?></strong></p>
                <p>If this worked, DB writes are fine — Settings page/JS was the problem.</p>
            <?php endif; ?>
            <table>
                <tr><th>ID</th><th>Name</th><th>Currency</th><th>Updated</th></tr>
                <?php foreach ($bizRows as $row): ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) $row['name']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['currency_id']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['updated_at'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p>
                <a class="btn" href="?<?php echo $qs; ?>&amp;set_name=QPOS-Repair-OK">Test DB save (rename first business)</a>
            </p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>2b) PHP limits + settings log</h2>
        <?php $miv = (int) ini_get('max_input_vars'); ?>
        <p>max_input_vars: <strong class="<?php echo $miv >= 3000 ? 'ok' : 'bad'; ?>"><?php echo $miv; ?></strong>
            <?php if ($miv < 3000): ?>
                — too low (default 1000). Upload <code>public/.user.ini</code> then wait a few minutes / restart PHP.
            <?php endif; ?>
        </p>
        <p>post_max_size: <code><?php echo htmlspecialchars((string) ini_get('post_max_size')); ?></code></p>
        <p><strong>settings_update.log</strong> (last lines):</p>
        <pre><?php echo htmlspecialchars($settingsLogTail !== '' ? $settingsLogTail : '(empty)'); ?></pre>
    </div>

    <div class="box">
        <h2>3) Next steps</h2>
        <ol>
            <li><strong>Use direct save now:</strong> <a href="qpos_settings_save.php?key=qposclear2026">qpos_settings_save.php</a> (bypasses the broken Settings form).</li>
            <li>Hard refresh Business Settings — button must say <strong>Update Settings ★v5</strong>.</li>
            <li>Then delete these repair files.</li>
        </ol>
        <p>
            <a class="btn" href="qpos_settings_save.php?key=qposclear2026">Open direct settings save</a>
            <a class="btn warn" href="?<?php echo $qs; ?>">Run cache clear again</a>
            <a class="btn danger" href="?<?php echo $qs; ?>&amp;delete=1" onclick="return confirm('Delete qpos_repair.php now?');">Delete this repair file</a>
        </p>
    </div>

    <div class="box">
        <h2>Log</h2>
        <pre><?php echo htmlspecialchars(implode("\n", $log)); ?></pre>
    </div>
</body>
</html>
<?php

if (! empty($_GET['delete'])) {
    @unlink(__FILE__);
}
