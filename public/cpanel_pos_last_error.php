<?php
/**
 * QPOS — show the REAL Pay/POS exception (temporary)
 * URL: /cpanel_pos_last_error.php?key=12345678
 * DELETE after use.
 */
declare(strict_types=1);

const ERR_SECRET = '12345678';
header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';
if (!hash_equals(ERR_SECRET, (string)$key)) {
    http_response_code(403);
    echo "FORBIDDEN\n";
    exit;
}

$log = dirname(__DIR__).'/storage/logs/laravel.log';
if (!is_file($log)) {
    echo "No laravel.log at {$log}\n";
    exit;
}

$lines = @file($log, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    echo "Cannot read log\n";
    exit;
}

$chunk = array_slice($lines, -400);
$text = implode("\n", $chunk);

// Prefer last emergency / ERROR near POS
preg_match_all('/\[(.*?)\]\s+\w+\.(ERROR|EMERGENCY|CRITICAL):\s*(.*?)(?=\n\[|\z)/s', $text, $m, PREG_SET_ORDER);
if (!$m) {
    echo "No ERROR/EMERGENCY in last 400 lines.\n\n--- tail ---\n";
    echo implode("\n", array_slice($chunk, -80));
    exit;
}

$last = end($m);
echo "LAST {$last[2]} @ {$last[1]}\n";
echo str_repeat('=', 60)."\n";
$msg = $last[3];
// Truncate huge stack traces but keep message + first frames
$parts = preg_split('/\n(?=#\d+|\{"exception)/', $msg, 2);
echo trim($parts[0])."\n\n";
if (isset($parts[1])) {
    $stack = substr($parts[1], 0, 2500);
    echo $stack.(strlen($parts[1]) > 2500 ? "\n...[truncated]\n" : "\n");
}
echo "\nDELETE this file after copying the output.\n";
