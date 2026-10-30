<?php
$root = dirname(__DIR__, 2);
$outDir = __DIR__;
$zipPath = $outDir . DIRECTORY_SEPARATOR . 'QPOS-POS-List-View-Final-Fix-cPanel.zip';
$files = [
    'app/Http/Controllers/SellPosController.php',
    'resources/views/sale_pos/partials/product_list.blade.php',
    'resources/views/sale_pos/partials/product_list_rows.blade.php',
    'resources/views/sale_pos/partials/touch_main.blade.php',
    'public/js/pos-touch.js',
    'public/js/pos.js',
    'public/css/pos-touch.css',
];
if (is_file($zipPath)) {
    unlink($zipPath);
}
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "cannot create zip\n");
    exit(1);
}
foreach ($files as $rel) {
    $abs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($abs)) {
        fwrite(STDERR, "missing {$rel}\n");
        exit(1);
    }
    $zip->addFile($abs, $rel);
}
$zip->close();
echo "wrote {$zipPath} " . filesize($zipPath) . "\n";
$check = new ZipArchive();
$check->open($zipPath);
for ($i = 0; $i < $check->numFiles; $i++) {
    echo $check->getNameIndex($i) . "\n";
}
$check->close();
