<?php
/**
 * Guaranteed Business Settings saver for cPanel (bypasses Laravel form entirely).
 *
 * Upload next to index.php, then open:
 *   https://YOUR-DOMAIN/qpos_settings_save.php?key=qposclear2026
 *
 * DELETE this file after settings work again.
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$key = $_GET['key'] ?? ($_POST['key'] ?? '');
if ($key !== 'qposclear2026') {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Use <code>?key=qposclear2026</code></p>';
    exit;
}

$publicDir = __DIR__;
$base = dirname($publicDir);

function qss_env(string $base): array
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
        if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        $env[trim($k)] = $v;
    }

    return $env;
}

function qss_pdo(array $env): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? ''
    );

    return new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

$env = qss_env($base);
$msg = '';
$err = '';
$row = null;

try {
    if (($env['DB_DATABASE'] ?? '') === '') {
        throw new RuntimeException('.env DB_DATABASE missing');
    }
    $pdo = qss_pdo($env);
    $row = $pdo->query('SELECT * FROM business ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (! $row) {
        throw new RuntimeException('No business row found');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) $row['id'];
        $name = trim((string) ($_POST['name'] ?? $row['name']));
        $date_format = trim((string) ($_POST['date_format'] ?? $row['date_format']));
        $time_format = trim((string) ($_POST['time_format'] ?? $row['time_format']));
        $transaction_edit_days = (int) ($_POST['transaction_edit_days'] ?? $row['transaction_edit_days']);
        $currency_precision = (int) ($_POST['currency_precision'] ?? $row['currency_precision']);
        $quantity_precision = (int) ($_POST['quantity_precision'] ?? $row['quantity_precision']);
        $theme_color = trim((string) ($_POST['theme_color'] ?? ($row['theme_color'] ?? 'primary')));
        $enable_tooltip = ! empty($_POST['enable_tooltip']) ? 1 : 0;

        // Merge common_settings JSON toggles without wiping the rest
        $common = [];
        if (! empty($row['common_settings'])) {
            $decoded = json_decode($row['common_settings'], true);
            $common = is_array($decoded) ? $decoded : [];
        }
        foreach ([
            'enable_bartender_export',
            'enable_weighing_scale_label',
            'enable_weighing_scale_scan',
            'enable_costcode_generation',
            'enable_offline_billing',
            'enable_pwa',
            'enable_tax',
        ] as $flag) {
            if (array_key_exists($flag, $_POST)) {
                $common[$flag] = ! empty($_POST[$flag]) ? 1 : 0;
            }
        }
        $commonJson = json_encode($common, JSON_UNESCAPED_UNICODE);

        $sql = 'UPDATE business SET
            name = ?,
            date_format = ?,
            time_format = ?,
            transaction_edit_days = ?,
            currency_precision = ?,
            quantity_precision = ?,
            theme_color = ?,
            enable_tooltip = ?,
            common_settings = ?,
            updated_at = NOW()
            WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $date_format,
            $time_format,
            $transaction_edit_days,
            $currency_precision,
            $quantity_precision,
            $theme_color,
            $enable_tooltip,
            $commonJson,
            $id,
        ]);

        // Clear Laravel compiled views + session business cache file hint
        foreach ([
            $base.'/storage/framework/views',
            $base.'/storage/framework/cache/data',
        ] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
        }

        @file_put_contents(
            $base.'/storage/logs/settings_update.log',
            '['.date('Y-m-d H:i:s')."] DIRECT_SAVE OK id={$id} name={$name} date_format={$date_format}\n",
            FILE_APPEND
        );

        $row = $pdo->query('SELECT * FROM business WHERE id='.(int) $id)->fetch(PDO::FETCH_ASSOC);
        $msg = 'Saved OK. Business updated in database. Hard-refresh Business Settings (Cmd+Shift+R) — you may need to log out/in so the session reloads.';
    }
} catch (Throwable $e) {
    $err = $e->getMessage();
}

$common = [];
if ($row && ! empty($row['common_settings'])) {
    $decoded = json_decode($row['common_settings'], true);
    $common = is_array($decoded) ? $decoded : [];
}

$dateFormats = [
    'd-m-Y' => 'dd-mm-yyyy',
    'm-d-Y' => 'mm-dd-yyyy',
    'd/m/Y' => 'dd/mm/yyyy',
    'm/d/Y' => 'mm/dd/yyyy',
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QPOS Direct Settings Save</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; max-width: 720px; margin: 24px auto; padding: 0 16px; }
        label { display:block; font-weight:600; margin: 12px 0 4px; }
        input[type=text], input[type=number], select { width:100%; padding:8px; box-sizing:border-box; }
        .row { margin-bottom: 8px; }
        .ok { background:#e8fff0; border:1px solid #0a7; padding:12px; border-radius:8px; }
        .bad { background:#ffecec; border:1px solid #c30; padding:12px; border-radius:8px; }
        button { background:#0a7; color:#fff; border:0; padding:12px 18px; border-radius:8px; font-size:16px; cursor:pointer; margin-top:16px; }
        .hint { color:#666; font-size:13px; }
        code { background:#f4f4f5; padding:2px 6px; border-radius:4px; }
    </style>
</head>
<body>
    <h1>QPOS Direct Settings Save</h1>
    <p class="hint">This writes straight to MySQL (same DB as the POS). Use it while the big Settings page is broken. Delete this file when done.</p>

    <?php if ($msg): ?><div class="ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bad"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <?php if ($row): ?>
    <form method="post">
        <input type="hidden" name="key" value="qposclear2026">

        <label>Business name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars((string) $row['name']); ?>">

        <label>Date format</label>
        <select name="date_format">
            <?php foreach ($dateFormats as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>" <?php echo (($row['date_format'] ?? '') === $val) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Time format</label>
        <select name="time_format">
            <option value="24" <?php echo (($row['time_format'] ?? '') == '24') ? 'selected' : ''; ?>>24 Hour</option>
            <option value="12" <?php echo (($row['time_format'] ?? '') == '12') ? 'selected' : ''; ?>>12 Hour</option>
        </select>

        <label>Transaction edit days</label>
        <input type="number" name="transaction_edit_days" value="<?php echo htmlspecialchars((string) ($row['transaction_edit_days'] ?? 30)); ?>">

        <label>Currency precision</label>
        <input type="number" name="currency_precision" value="<?php echo htmlspecialchars((string) ($row['currency_precision'] ?? 2)); ?>">

        <label>Quantity precision</label>
        <input type="number" name="quantity_precision" value="<?php echo htmlspecialchars((string) ($row['quantity_precision'] ?? 2)); ?>">

        <label>Theme color</label>
        <input type="text" name="theme_color" value="<?php echo htmlspecialchars((string) ($row['theme_color'] ?? 'primary')); ?>">

        <label><input type="checkbox" name="enable_tooltip" value="1" <?php echo ! empty($row['enable_tooltip']) ? 'checked' : ''; ?>> Enable help tooltip</label>

        <h3>Feature toggles</h3>
        <?php
        $flags = [
            'enable_bartender_export' => 'Barcode & Sticker (Bartender)',
            'enable_weighing_scale_label' => 'Weighing Scale Label',
            'enable_weighing_scale_scan' => 'Weighing Scale Barcode Scan',
            'enable_costcode_generation' => 'Cost Code',
            'enable_offline_billing' => 'Offline Billing',
            'enable_pwa' => 'PWA',
            'enable_tax' => 'Tax system',
        ];
        foreach ($flags as $flag => $label):
        ?>
            <label>
                <input type="checkbox" name="<?php echo htmlspecialchars($flag); ?>" value="1" <?php echo ! empty($common[$flag]) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
            </label>
        <?php endforeach; ?>

        <button type="submit">Save to database now</button>
    </form>

    <p class="hint" style="margin-top:24px;">
        APP_ENV=<code><?php echo htmlspecialchars($env['APP_ENV'] ?? ''); ?></code>
        · DB=<code><?php echo htmlspecialchars($env['DB_DATABASE'] ?? ''); ?></code>
        · Business #<?php echo (int) $row['id']; ?>
        · updated_at=<code><?php echo htmlspecialchars((string) ($row['updated_at'] ?? '')); ?></code>
    </p>
    <?php endif; ?>
</body>
</html>
