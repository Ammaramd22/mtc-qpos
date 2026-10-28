<?php
/**
 * QPOS PATCH v4 — fixes bartender crash after Update Settings.
 *
 * This script:
 *  1) Repairs DB common_settings.bartender_fields (must be an array)
 *  2) OVERWRITES settings_bartender.blade.php on the server (safe in_array)
 *  3) Patches BusinessController so save cannot flatten bartender_fields again
 *  4) Clears compiled Blade views
 *
 * Upload ONLY this file into public/ (same folder as index.php), then open:
 *   https://proway-qpos.avenque.io/public/qpos_patch_v4.php?key=qposclear2026
 *   (or without /public/ if that is how your site works)
 *
 * DELETE this file after it says DONE.
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_GET['key'] ?? '') !== 'qposclear2026') {
    http_response_code(403);
    echo 'Forbidden. Use ?key=qposclear2026';
    exit;
}

$log = [];
$publicDir = __DIR__;
$base = dirname($publicDir);
if (! is_file($base.'/.env') && is_file(dirname($base).'/.env')) {
    $base = dirname($base);
}
$log[] = 'Base: '.$base;
$log[] = 'Public: '.$publicDir;

function load_env($base)
{
    $env = [];
    $path = $base.'/.env';
    if (! is_file($path)) {
        throw new RuntimeException('.env not found at '.$path);
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }

    return $env;
}

$defaultBartender = [
    'product_name', 'sku', 'barcode', 'barcode_value', 'price',
    'category', 'brand', 'unit', 'weight', 'description', 'quantity',
];

try {
    $env = load_env($base);
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'] ?? '127.0.0.1',
            $env['DB_PORT'] ?? '3306',
            $env['DB_DATABASE'] ?? ''
        ),
        $env['DB_USERNAME'] ?? '',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $rows = $pdo->query('SELECT id, name, common_settings FROM business')->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare('UPDATE business SET common_settings = ?, updated_at = NOW() WHERE id = ?');
    foreach ($rows as $row) {
        $cs = json_decode($row['common_settings'] ?: '{}', true);
        if (! is_array($cs)) {
            $cs = [];
        }
        // Force bartender_fields to a clean string list
        $bf = $cs['bartender_fields'] ?? null;
        if (! is_array($bf) || $bf === []) {
            $cs['bartender_fields'] = $defaultBartender;
        } else {
            $cs['bartender_fields'] = array_values(array_filter($bf, function ($x) {
                return is_string($x) || is_numeric($x);
            }));
            if ($cs['bartender_fields'] === []) {
                $cs['bartender_fields'] = $defaultBartender;
            }
        }
        if (! is_array($cs['bartender_custom_fields'] ?? null)) {
            $cs['bartender_custom_fields'] = [];
        }
        // Flatten only known scalar keys that may be arrays
        foreach ($cs as $k => $v) {
            if (! is_array($v)) {
                continue;
            }
            if (in_array($k, ['ebill_settings', 'mobile_shop', 'bartender_fields', 'bartender_custom_fields'], true)) {
                continue;
            }
            if (substr((string) $k, -7) === '_fields') {
                continue;
            }
            if ($v !== [] && array_keys($v) !== range(0, count($v) - 1)) {
                continue;
            }
            $flat = null;
            array_walk_recursive($v, function ($item) use (&$flat) {
                if (! is_array($item)) {
                    $flat = $item;
                }
            });
            $cs[$k] = $flat;
        }
        $upd->execute([json_encode($cs, JSON_UNESCAPED_UNICODE), (int) $row['id']]);
        $log[] = 'DB business #'.$row['id'].' bartender_fields='.count($cs['bartender_fields']);
    }

    // --- Overwrite blade with crash-proof version ---
    $bladePath = $base.'/resources/views/business/partials/settings_bartender.blade.php';
    if (! is_file($bladePath)) {
        throw new RuntimeException('Blade not found: '.$bladePath);
    }
    $blade = file_get_contents($bladePath);
    $backup = $bladePath.'.bak-'.date('YmdHis');
    @file_put_contents($backup, $blade);
    $log[] = 'Blade backup: '.$backup;

    // Replace ANY selectedFields / in_array pattern with safe code
    $safePhp = <<<'BLADE'
                    @php
                        $defaultFields = [
                            'product_name' => __('product.product_name'),
                            'sku' => __('product.sku'),
                            'barcode' => __('product.barcode_type'),
                            'barcode_value' => __('lang_v1.barcode_value'),
                            'price' => __('product.default_selling_price'),
                            'category' => __('product.category'),
                            'brand' => __('product.brand'),
                            'unit' => __('product.unit'),
                            'weight' => __('lang_v1.weight'),
                            'description' => __('lang_v1.description'),
                            'quantity' => __('lang_v1.label_quantity'),
                        ];
                        $selectedFields = $common_settings['bartender_fields'] ?? array_keys($defaultFields);
                        if (!is_array($selectedFields)) { $selectedFields = array_keys($defaultFields); }
                        $selectedFields = array_map('strval', array_values($selectedFields));
                    @endphp
                    @foreach($defaultFields as $key => $label)
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="common_settings[bartender_fields][]" value="{{ $key }}"
                                        @if(in_array((string)$key, $selectedFields, true)) checked @endif>
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
BLADE;

    // Try to replace the fields_to_export checkbox block
    $pattern = '/@php\s*\$defaultFields\s*=\s*\[[\s\S]*?@endforeach/m';
    if (! preg_match($pattern, $blade)) {
        $pattern = '/@php\s*\$default_fields\s*=\s*\[[\s\S]*?@endforeach/m';
    }
    if (preg_match($pattern, $blade)) {
        $blade2 = preg_replace($pattern, trim($safePhp), $blade, 1);
        if ($blade2 && $blade2 !== $blade) {
            file_put_contents($bladePath, $blade2);
            $log[] = 'Blade PATCHED via regex (safe in_array)';
        } else {
            // Fallback: force-safe the in_array line only
            $blade2 = preg_replace(
                '/@if\s*\(\s*in_array\s*\(\s*\$key\s*,\s*\$selectedFields\s*\)\s*\)/',
                '@if(is_array($selectedFields) && in_array($key, $selectedFields, true))',
                $blade
            );
            $blade2 = preg_replace(
                '/\$selectedFields\s*=\s*!empty\(\$common_settings\[[\'"]bartender_fields[\'"]\]\)\s*\?\s*\$common_settings\[[\'"]bartender_fields[\'"]\]\s*:\s*\[\];?/',
                '$selectedFields = $common_settings[\'bartender_fields\'] ?? []; if (!is_array($selectedFields)) { $selectedFields = []; }',
                $blade2
            );
            file_put_contents($bladePath, $blade2);
            $log[] = 'Blade PATCHED via in_array fallback';
        }
    } else {
        // Absolute fallback: prepend guard at top of file
        if (strpos($blade, 'QPOS_BARTENDER_SAFE_GUARD') === false) {
            $guard = "{{-- QPOS_BARTENDER_SAFE_GUARD --}}\n@php\n"
                ."if (isset(\$common_settings['bartender_fields']) && !is_array(\$common_settings['bartender_fields'])) {\n"
                ."    \$common_settings['bartender_fields'] = ['product_name','sku','barcode','barcode_value','price','category','brand','unit','weight','description','quantity'];\n"
                ."}\n@endphp\n";
            file_put_contents($bladePath, $guard.$blade);
            $log[] = 'Blade PATCHED via top guard';
        } else {
            $log[] = 'Blade already had guard';
        }
    }

    // Verify blade no longer has unsafe pattern
    $check = file_get_contents($bladePath);
    if (preg_match('/in_array\s*\(\s*\$key\s*,\s*\$selectedFields\s*\)/', $check)
        && strpos($check, 'is_array($selectedFields)') === false
        && strpos($check, 'is_array($selectedFields)') === false
        && strpos($check, 'in_array((string)$key') === false
        && strpos($check, 'in_array((string) $key') === false) {
        $log[] = 'WARNING: blade may still be unsafe — upload QPOS-FIX-EVERYTHING.zip views manually';
    } else {
        $log[] = 'Blade looks SAFE';
    }

    // --- Patch BusinessController save flatten ---
    $ctrl = $base.'/app/Http/Controllers/BusinessController.php';
    if (is_file($ctrl) && is_writable($ctrl)) {
        $c = file_get_contents($ctrl);
        @file_put_contents($ctrl.'.bak-'.date('YmdHis'), $c);
        // Fix the bad foreach that flattens bartender_fields
        $bad = "if (is_array(\$cv) && ! in_array(\$ck, ['ebill_settings', 'mobile_shop'], true))";
        $good = "if (is_array(\$cv) && ! in_array(\$ck, ['ebill_settings', 'mobile_shop', 'bartender_fields', 'bartender_custom_fields'], true) && substr((string)\$ck, -7) !== '_fields')";
        if (strpos($c, $bad) !== false) {
            $c = str_replace($bad, $good, $c);
            // Do both occurrences (req_common + existing)
            file_put_contents($ctrl, $c);
            $log[] = 'BusinessController PATCHED (keep bartender_fields on save)';
        } elseif (strpos($c, "'bartender_fields', 'bartender_custom_fields'") !== false
            || strpos($c, 'bartender_fields') !== false && strpos($c, 'flattenScalarSettingsArray') !== false) {
            $log[] = 'BusinessController already has bartender_fields protection';
        } else {
            $log[] = 'WARNING: could not auto-patch BusinessController — upload zip app/Http/Controllers/BusinessController.php';
        }

        // Inject hard guarantee before common_settings assignment to business_details if possible
        $marker = "\$business_details['common_settings'] = \$common_settings;";
        $inject = "if (!isset(\$common_settings['bartender_fields']) || !is_array(\$common_settings['bartender_fields'])) {\n"
            ."                \$common_settings['bartender_fields'] = ['product_name','sku','barcode','barcode_value','price','category','brand','unit','weight','description','quantity'];\n"
            ."            }\n"
            ."            \$business_details['common_settings'] = \$common_settings;";
        $c = file_get_contents($ctrl);
        if (strpos($c, "QPOS_BARTENDER_SAVE_GUARD") === false && strpos($c, $marker) !== false) {
            $c = str_replace(
                $marker,
                "// QPOS_BARTENDER_SAVE_GUARD\n            ".$inject,
                $c,
                $count
            );
            if ($count > 0) {
                file_put_contents($ctrl, $c);
                $log[] = 'BusinessController SAVE GUARD injected';
            }
        }
    } else {
        $log[] = 'BusinessController not writable or missing';
    }

    // Clear views
    $views = $base.'/storage/framework/views';
    $n = 0;
    if (is_dir($views)) {
        foreach (glob($views.'/*') as $f) {
            if (is_file($f) && basename($f) !== '.gitignore') {
                @unlink($f);
                $n++;
            }
        }
    }
    $log[] = "Cleared {$n} compiled views";
} catch (Throwable $e) {
    $log[] = 'ERROR: '.$e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>QPOS Patch v4</title>
<style>
body{font-family:system-ui;max-width:800px;margin:24px auto;padding:0 16px}
.ok{color:#0a7;font-weight:700}.bad{color:#c30}
pre{background:#f4f4f5;padding:12px;overflow:auto}
a.btn{display:inline-block;background:#0a7;color:#fff;padding:12px 16px;border-radius:8px;text-decoration:none;margin-top:12px}
</style>
</head>
<body>
<h1>QPOS Patch v4</h1>
<p class="ok">Run complete. Read the log:</p>
<pre><?php echo htmlspecialchars(implode("\n", $log)); ?></pre>
<p><a class="btn" href="/business/settings">Open Business Settings</a>
<a class="btn" href="/public/business/settings" style="background:#2563eb">Open /public/business/settings</a></p>
<p>Hard refresh (Ctrl+Shift+R), then Update Settings again. It must not show the red error.</p>
<p><strong>Delete this file:</strong> qpos_patch_v4.php</p>
</body>
</html>
