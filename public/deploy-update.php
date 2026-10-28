<?php
/**
 * VsoftPOS Patch Deploy Script
 * Upload to public/ folder and visit in browser.
 * Applies specific edits to existing files.
 */

ini_set('max_execution_time', 300);
$root = dirname(__DIR__);

echo "<pre style='font-family:monospace; font-size:14px;'>";
echo "<h2>VsoftPOS Patch Deploy</h2>\n";

$patches = [];

// PATCH 1: AppServiceProvider.php - wrap System::getProperties in try-catch
$patches[] = [
    'file' => 'app/Providers/AppServiceProvider.php',
    'find' => "                if (isAppInstalled()) {\n                    \$keys = ['additional_js', 'additional_css'];\n                    \$__system_settings = System::getProperties(\$keys, true);",
    'replace' => "                if (isAppInstalled()) {\n                    try {\n                        \$keys = ['additional_js', 'additional_css'];\n                        \$__system_settings = System::getProperties(\$keys, true);",
];

$patches[] = [
    'file' => 'app/Providers/AppServiceProvider.php',
    'find' => "                        \$view->with('__additional_views', \$additional_views);\n                        \$view->with('__additional_html', \$additional_html);\n                        \$view->with('__system_settings', \$__system_settings);\n                    }\n                }\n            }\n        );",
    'replace' => "                        \$view->with('__additional_views', \$additional_views);\n                        \$view->with('__additional_html', \$additional_html);\n                        \$view->with('__system_settings', \$__system_settings);\n                    } catch (\\Exception \$e) {\n                        // Database tables may not exist yet during installation\n                        \$view->with('__additional_views', []);\n                        \$view->with('__additional_html', '');\n                        \$view->with('__system_settings', []);\n                    }\n                    }\n                }\n            }\n        );",
];

// PATCH 2: BusinessController.php - wrap System::getProperties in try-catch
$patches[] = [
    'file' => 'app/Http/Controllers/BusinessController.php',
    'find' => "        \$system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);",
    'replace' => "        try {\n            \$system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);\n        } catch (\\Exception \$e) {\n            \$system_settings = [];\n        }",
];

// PATCH 3: header.blade.php - add Install App menu item
$patches[] = [
    'file' => 'resources/views/layouts/partials/header.blade.php',
    'find' => "                    @if (auth()->user()->hasRole('Admin#' . auth()->user()->business_id))\n                        <li>\n                            <a href=\"#\" id=\"start_tour\" class=\"nv-drop-item\">\n                                <i class=\"fas fa-question-circle nv-drop-icon\"></i> @lang('lang_v1.application_tour')\n                            </a>\n                        </li>\n                    @endif\n                </ul>",
    'replace' => "                    @if (auth()->user()->hasRole('Admin#' . auth()->user()->business_id))\n                        <li>\n                            <a href=\"#\" id=\"start_tour\" class=\"nv-drop-item\">\n                                <i class=\"fas fa-question-circle nv-drop-icon\"></i> @lang('lang_v1.application_tour')\n                            </a>\n                        </li>\n                    @endif\n                    <li id=\"menu-install-app\" style=\"display:none;\">\n                        <a href=\"#\" onclick=\"triggerPWAInstall(); return false;\" class=\"nv-drop-item\">\n                            <i class=\"fas fa-download nv-drop-icon\"></i> @lang('lang_v1.install_app')\n                        </a>\n                    </li>\n                </ul>",
];

// PATCH 4: app.blade.php - update PWA script for menu item
$patches[] = [
    'file' => 'resources/views/layouts/app.blade.php',
    'find' => "        window.addEventListener('beforeinstallprompt', function(e) {\n            e.preventDefault();\n            deferredPrompt = e;\n            document.getElementById('pwa-install-btn').style.display = 'block';\n        });\n        function triggerPWAInstall() {\n            if (!deferredPrompt) return;\n            deferredPrompt.prompt();\n            deferredPrompt.userChoice.then(function(choiceResult) {\n                if (choiceResult.outcome === 'accepted') {\n                    document.getElementById('pwa-install-btn').style.display = 'none';\n                }\n                deferredPrompt = null;\n            });\n        }\n        window.addEventListener('appinstalled', function() {\n            document.getElementById('pwa-install-btn').style.display = 'none';\n            deferredPrompt = null;\n        });",
    'replace' => "        window.addEventListener('beforeinstallprompt', function(e) {\n            e.preventDefault();\n            deferredPrompt = e;\n            var btn = document.getElementById('pwa-install-btn');\n            if (btn) btn.style.display = 'block';\n            var menuItem = document.getElementById('menu-install-app');\n            if (menuItem) menuItem.style.display = 'block';\n        });\n        function triggerPWAInstall() {\n            if (!deferredPrompt) {\n                alert('App install is not available in this browser. Please use Chrome/Edge on Android or Safari on iOS.');\n                return;\n            }\n            deferredPrompt.prompt();\n            deferredPrompt.userChoice.then(function(choiceResult) {\n                if (choiceResult.outcome === 'accepted') {\n                    var btn = document.getElementById('pwa-install-btn');\n                    if (btn) btn.style.display = 'none';\n                    var menuItem = document.getElementById('menu-install-app');\n                    if (menuItem) menuItem.style.display = 'none';\n                }\n                deferredPrompt = null;\n            });\n        }\n        window.addEventListener('appinstalled', function() {\n            var btn = document.getElementById('pwa-install-btn');\n            if (btn) btn.style.display = 'none';\n            var menuItem = document.getElementById('menu-install-app');\n            if (menuItem) menuItem.style.display = 'none';\n            deferredPrompt = null;\n        });",
];

// PATCH 5: lang_v1.php - add install_app key
$patches[] = [
    'file' => 'lang/en/lang_v1.php',
    'find' => "    'pwa_how_it_works' => 'When enabled, users on mobile will see an install prompt (or can manually add to home screen). The app opens without browser address bar and works like a native app. Start URL lets you choose whether POS, Dashboard, or Products opens first. Display mode controls whether browser chrome is hidden.',\n\n    'update_product_price' => 'Update Price',",
    'replace' => "    'pwa_how_it_works' => 'When enabled, users on mobile will see an install prompt (or can manually add to home screen). The app opens without browser address bar and works like a native app. Start URL lets you choose whether POS, Dashboard, or Products opens first. Display mode controls whether browser chrome is hidden.',\n    'install_app' => 'Install App',\n\n    'update_product_price' => 'Update Price',",
];

$success = 0;
$failed = 0;

foreach ($patches as $i => $patch) {
    $path = $root . '/' . $patch['file'];
    echo "Patch " . ($i + 1) . ": " . $patch['file'] . "\n";
    
    if (!file_exists($path)) {
        echo "  [FAIL] File not found: $path\n";
        $failed++;
        continue;
    }
    
    $content = file_get_contents($path);
    if (strpos($content, $patch['find']) === false) {
        // Maybe already patched?
        if (strpos($content, trim($patch['replace'])) !== false || strpos($content, 'install_app') !== false) {
            echo "  [SKIP] Already patched\n";
            $success++;
        } else {
            echo "  [FAIL] Could not find target text (file may differ from expected)\n";
            $failed++;
        }
        continue;
    }
    
    $newContent = str_replace($patch['find'], $patch['replace'], $content);
    if (file_put_contents($path, $newContent)) {
        echo "  [OK] Patched\n";
        $success++;
    } else {
        echo "  [FAIL] Cannot write file\n";
        $failed++;
    }
}

echo "\n==========================\n";
echo "Success: $success | Failed: $failed\n";
echo "==========================\n\n";

if ($failed === 0) {
    echo "<span style='color:green'><b>All patches applied!</b></span>\n";
    echo "1. Delete this deploy-update.php file now.\n";
    echo "2. Clear browser cache (Ctrl+Shift+R).\n";
} else {
    echo "<span style='color:red'><b>Some patches failed.</b></span>\n";
}
echo "</pre>";
