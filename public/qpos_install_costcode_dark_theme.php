<?php
/**
 * QPOS cost-code dark-theme installer.
 * Installs one Blade view only. It makes no database changes and does not
 * regenerate cost codes.
 */

@set_time_limit(120);
header('Content-Type: text/html; charset=UTF-8');

$requiredKey = 'qposclear2026';
$providedKey = isset($_GET['key']) ? (string) $_GET['key'] : '';
$keyMatches = function_exists('hash_equals')
    ? hash_equals($requiredKey, $providedKey)
    : ($requiredKey === $providedKey);

function qposHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function qposRender($ok, $title, array $messages)
{
    http_response_code($ok ? 200 : 500);
    $color = $ok ? '#166534' : '#991b1b';
    $background = $ok ? '#f0fdf4' : '#fef2f2';
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . qposHtml($title) . '</title><style>body{font:16px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f8fafc;color:#111827;margin:0;padding:32px}.card{max-width:850px;margin:auto;background:#fff;border:1px solid #d1d5db;border-radius:12px;padding:28px;box-shadow:0 8px 24px rgba(15,23,42,.08)}h1{color:' . $color . ';margin-top:0}.status{background:' . $background . ';border-left:5px solid ' . $color . ';padding:14px 18px;margin:18px 0}li{margin:7px 0}.warning{margin-top:24px;padding:16px;border:3px solid #b91c1c;background:#fff1f2;color:#7f1d1d;font-weight:800;font-size:18px}code{overflow-wrap:anywhere}</style></head><body><main class="card">';
    echo '<h1>' . qposHtml($title) . '</h1><div class="status"><strong>' . ($ok ? 'SUCCESS' : 'ERROR') . '</strong></div><ul>';
    foreach ($messages as $message) {
        echo '<li>' . qposHtml($message) . '</li>';
    }
    echo '</ul><div class="warning">SECURITY WARNING: DELETE qpos_install_costcode_dark_theme.php FROM THE SERVER NOW.</div>';
    echo '</main></body></html>';
    exit;
}

if (!$keyMatches) {
    http_response_code(403);
    qposRender(false, 'Authorization failed', array('Missing or invalid installer key.'));
}

$payloadBase64 = 'PGRpdiBjbGFzcz0icG9zLXRhYi1jb250ZW50IiBpZD0ic2V0dGluZ3MtY29zdGNvZGUiIGRhdGEtc2V0dGluZ3MtdGFiPSJjb3N0Y29kZSI+CiAgICA8aDQgY2xhc3M9InR3LXRleHQtbGcgdHctZm9udC1zZW1pYm9sZCB0dy10ZXh0LWJsYWNrIj5AbGFuZygnbGFuZ192MS5jb3N0Y29kZV9zZXR0aW5ncycpPC9oND4KICAgIDxwIGNsYXNzPSJ0dy10ZXh0LXNtIHR3LXRleHQtZ3JheS01MDAgdHctbWItNCI+QGxhbmcoJ2xhbmdfdjEuY29zdGNvZGVfc2V0dGluZ3NfaGVscCcpPC9wPgoKICAgIHt7LS0gRW5hYmxlIC8gRGlzYWJsZSBUb2dnbGUgLS19fQogICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICA8ZGl2IGNsYXNzPSJjb2wtbWQtMTIiPgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9InR3LWZsZXggdHctaXRlbXMtY2VudGVyIHR3LWdhcC00IHR3LXAtNCB0dy1yb3VuZGVkLWxnIHR3LWJvcmRlciB0dy1ib3JkZXItZ3JheS0yMDAgdHctYmctZ3JheS01MCI+CiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0idHctZmxleC0xIj4KICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0idHctZm9udC1zZW1pYm9sZCB0dy10ZXh0LWdyYXktOTAwIj5AbGFuZygnbGFuZ192MS5lbmFibGVfY29zdGNvZGVfZ2VuZXJhdGlvbicpPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9InR3LXRleHQtc20gdHctdGV4dC1ncmF5LTUwMCI+QGxhbmcoJ2xhbmdfdjEuZW5hYmxlX2Nvc3Rjb2RlX2dlbmVyYXRpb25faGVscCcpPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0idHctZmxleCB0dy1pdGVtcy1jZW50ZXIgdHctZ2FwLTIiPgogICAgICAgICAgICAgICAgICAgICAgICA8bGFiZWwgY2xhc3M9ImJhcnRlbmRlci1zd2l0Y2giIGFyaWEtbGFiZWw9IkBsYW5nKCdsYW5nX3YxLmVuYWJsZV9jb3N0Y29kZV9nZW5lcmF0aW9uJykiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY29tbW9uX3NldHRpbmdzW2VuYWJsZV9jb3N0Y29kZV9nZW5lcmF0aW9uXSIgdmFsdWU9IjAiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImNoZWNrYm94IiBuYW1lPSJjb21tb25fc2V0dGluZ3NbZW5hYmxlX2Nvc3Rjb2RlX2dlbmVyYXRpb25dIiB2YWx1ZT0iMSIgaWQ9InRvZ2dsZV9jb3N0Y29kZSIKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBAaWYoIWVtcHR5KCRjb21tb25fc2V0dGluZ3NbJ2VuYWJsZV9jb3N0Y29kZV9nZW5lcmF0aW9uJ10pKSBjaGVja2VkIEBlbmRpZj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJiYXJ0ZW5kZXItc2xpZGVyIj48L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzPSJ0dy10ZXh0LXNtIHR3LWZvbnQtbWVkaXVtIHR3LXRleHQtZ3JheS05MDAiIGlkPSJjb3N0Y29kZV90b2dnbGVfbGFiZWwiIHN0eWxlPSJtaW4td2lkdGg6NjBweDtkaXNwbGF5OmlubGluZS1ibG9jazsiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAge3sgIWVtcHR5KCRjb21tb25fc2V0dGluZ3NbJ2VuYWJsZV9jb3N0Y29kZV9nZW5lcmF0aW9uJ10pID8gX18oJ2xhbmdfdjEuZW5hYmxlZCcpIDogX18oJ2xhbmdfdjEuZGlzYWJsZWQnKSB9fQogICAgICAgICAgICAgICAgICAgICAgICA8L3NwYW4+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICA8L2Rpdj4KCiAgICB7ey0tIERpZ2l0IHRvIExldHRlciBNYXBwaW5nIC0tfX0KICAgIDxkaXYgaWQ9ImNvc3Rjb2RlX3NldHRpbmdzX2NvbnRhaW5lciIgc3R5bGU9Int7ICFlbXB0eSgkY29tbW9uX3NldHRpbmdzWydlbmFibGVfY29zdGNvZGVfZ2VuZXJhdGlvbiddKSA/ICcnIDogJ29wYWNpdHk6MC41OyBwb2ludGVyLWV2ZW50czpub25lOyBmaWx0ZXI6Z3JheXNjYWxlKDgwJSk7JyB9fSI+CiAgICAgICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICAgICAgPGRpdiBjbGFzcz0iY29sLW1kLTEyIj4KICAgICAgICAgICAgICAgIDxoNSBjbGFzcz0idHctZm9udC1zZW1pYm9sZCB0dy1tdC00IHR3LW1iLTIiPgogICAgICAgICAgICAgICAgICAgIEBsYW5nKCdsYW5nX3YxLmRpZ2l0X2xldHRlcl9tYXBwaW5nJykKICAgICAgICAgICAgICAgICAgICA8c3BhbiBzdHlsZT0iZGlzcGxheTppbmxpbmUtYmxvY2s7bWFyZ2luLWxlZnQ6NnB4O3BhZGRpbmc6MnB4IDdweDtib3JkZXItcmFkaXVzOjk5OXB4O2JhY2tncm91bmQ6IzE2NjUzNDtjb2xvcjojZGNmY2U3O2ZvbnQtc2l6ZToxMHB4O2ZvbnQtd2VpZ2h0OjgwMDt2ZXJ0aWNhbC1hbGlnbjptaWRkbGU7Ij5WNjwvc3Bhbj4KICAgICAgICAgICAgICAgIDwvaDU+CiAgICAgICAgICAgICAgICA8cCBjbGFzcz0idHctdGV4dC1zbSB0dy10ZXh0LWdyYXktNTAwIHR3LW1iLTMiPkBsYW5nKCdsYW5nX3YxLmRpZ2l0X2xldHRlcl9tYXBwaW5nX2hlbHAnKTwvcD4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICAgICAgQHBocAogICAgICAgICAgICAgICAgJHN0b3JlZE1hcHBpbmcgPSAhZW1wdHkoJGNvbW1vbl9zZXR0aW5nc1snY29zdGNvZGVfbWFwcGluZyddKSA/ICRjb21tb25fc2V0dGluZ3NbJ2Nvc3Rjb2RlX21hcHBpbmcnXSA6IFtdOwogICAgICAgICAgICAgICAgaWYgKCEgaXNfYXJyYXkoJHN0b3JlZE1hcHBpbmcpKSB7CiAgICAgICAgICAgICAgICAgICAgJHN0b3JlZE1hcHBpbmcgPSBbXTsKICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgICAgICRzdG9yZWRLZXl3b3JkID0gc3RydG91cHBlcihwcmVnX3JlcGxhY2UoJy9bXkEtWl0vaScsICcnLCAoc3RyaW5nKSAoJGNvbW1vbl9zZXR0aW5nc1snY29zdGNvZGVfa2V5d29yZCddID8/ICcnKSkpOwogICAgICAgICAgICAgICAgaWYgKHN0cmxlbigkc3RvcmVkS2V5d29yZCkgIT09IDEwIHx8IGNvdW50KGFycmF5X3VuaXF1ZShzdHJfc3BsaXQoJHN0b3JlZEtleXdvcmQpKSkgIT09IDEwKSB7CiAgICAgICAgICAgICAgICAgICAgJG9yZGVyZWRNYXBwaW5nID0gJHN0b3JlZE1hcHBpbmc7CiAgICAgICAgICAgICAgICAgICAga3NvcnQoJG9yZGVyZWRNYXBwaW5nKTsKICAgICAgICAgICAgICAgICAgICAkbWFwcGluZ1dvcmQgPSBzdHJ0b3VwcGVyKGltcGxvZGUoJycsIGFycmF5X3ZhbHVlcygkb3JkZXJlZE1hcHBpbmcpKSk7CiAgICAgICAgICAgICAgICAgICAgJHN0b3JlZEtleXdvcmQgPSBzdHJsZW4oJG1hcHBpbmdXb3JkKSA9PT0gMTAgJiYgY291bnQoYXJyYXlfdW5pcXVlKHN0cl9zcGxpdCgkbWFwcGluZ1dvcmQpKSkgPT09IDEwCiAgICAgICAgICAgICAgICAgICAgICAgID8gJG1hcHBpbmdXb3JkCiAgICAgICAgICAgICAgICAgICAgICAgIDogJ1BST0ZJVEFCTEUnOwogICAgICAgICAgICAgICAgfQogICAgICAgICAgICAgICAgJGRlZmF1bHRMZXR0ZXJzID0gc3RyX3NwbGl0KCRzdG9yZWRLZXl3b3JkKTsKICAgICAgICAgICAgICAgICRzdG9yZWRSZXBlYXRLZXl3b3JkID0gc3RydG91cHBlcihwcmVnX3JlcGxhY2UoJy9bXkEtWl0vaScsICcnLCAoc3RyaW5nKSAoJGNvbW1vbl9zZXR0aW5nc1snY29zdGNvZGVfcmVwZWF0X2tleXdvcmQnXSA/PyAnJykpKTsKICAgICAgICAgICAgICAgICRzdG9yZWRSZXBlYXRMZXR0ZXJzID0gc3RyX3NwbGl0KCRzdG9yZWRSZXBlYXRLZXl3b3JkKTsKICAgICAgICAgICAgICAgIGlmIChzdHJsZW4oJHN0b3JlZFJlcGVhdEtleXdvcmQpICE9PSAxMAogICAgICAgICAgICAgICAgICAgIHx8IGNvdW50KGFycmF5X3VuaXF1ZSgkc3RvcmVkUmVwZWF0TGV0dGVycykpICE9PSAxMAogICAgICAgICAgICAgICAgICAgIHx8IGNvdW50KGFycmF5X2ludGVyc2VjdCgkZGVmYXVsdExldHRlcnMsICRzdG9yZWRSZXBlYXRMZXR0ZXJzKSkgIT09IDApIHsKICAgICAgICAgICAgICAgICAgICAkdW51c2VkTGV0dGVycyA9IGFycmF5X3ZhbHVlcyhhcnJheV9kaWZmKHJhbmdlKCdBJywgJ1onKSwgJGRlZmF1bHRMZXR0ZXJzKSk7CiAgICAgICAgICAgICAgICAgICAgJHN0b3JlZFJlcGVhdEtleXdvcmQgPSBpbXBsb2RlKCcnLCBhcnJheV9zbGljZSgkdW51c2VkTGV0dGVycywgMCwgMTApKTsKICAgICAgICAgICAgICAgICAgICAkc3RvcmVkUmVwZWF0TGV0dGVycyA9IHN0cl9zcGxpdCgkc3RvcmVkUmVwZWF0S2V5d29yZCk7CiAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgIEBlbmRwaHAKICAgICAgICAgICAgPGRpdiBjbGFzcz0iY29sLW1kLTciPgogICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iZm9ybS1ncm91cCI+CiAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iY29zdGNvZGVfa2V5d29yZCI+QGxhbmcoJ2xhbmdfdjEuY29zdGNvZGVfc2VjcmV0X3dvcmQnKTwvbGFiZWw+CiAgICAgICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9InRleHQiCiAgICAgICAgICAgICAgICAgICAgICAgIGlkPSJjb3N0Y29kZV9rZXl3b3JkIgogICAgICAgICAgICAgICAgICAgICAgICBuYW1lPSJjb21tb25fc2V0dGluZ3NbY29zdGNvZGVfa2V5d29yZF0iCiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzPSJmb3JtLWNvbnRyb2wiCiAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlPSJ7eyAkc3RvcmVkS2V5d29yZCB9fSIKICAgICAgICAgICAgICAgICAgICAgICAgbWF4bGVuZ3RoPSIxMCIKICAgICAgICAgICAgICAgICAgICAgICAgYXV0b2NvbXBsZXRlPSJvZmYiCiAgICAgICAgICAgICAgICAgICAgICAgIHNwZWxsY2hlY2s9ImZhbHNlIgogICAgICAgICAgICAgICAgICAgICAgICBzdHlsZT0idGV4dC10cmFuc2Zvcm06dXBwZXJjYXNlO2ZvbnQtc2l6ZToxOHB4O2ZvbnQtd2VpZ2h0OjgwMDtsZXR0ZXItc3BhY2luZzouMThlbTsiPgogICAgICAgICAgICAgICAgICAgIDxzbWFsbCBjbGFzcz0iaGVscC1ibG9jayI+QGxhbmcoJ2xhbmdfdjEuY29zdGNvZGVfc2VjcmV0X3dvcmRfaGVscCcpPC9zbWFsbD4KICAgICAgICAgICAgICAgICAgICA8ZGl2IGlkPSJjb3N0Y29kZV9rZXl3b3JkX3N0YXR1cyIgY2xhc3M9InR3LXRleHQtc20gdHctZm9udC1zZW1pYm9sZCI+PC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CiAgICAgICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICAgICAgQGZvcigkZGlnaXQgPSAwOyAkZGlnaXQgPD0gOTsgJGRpZ2l0KyspCiAgICAgICAgICAgICAgICBAcGhwCiAgICAgICAgICAgICAgICAgICAgJGxldHRlciA9ICFlbXB0eSgkc3RvcmVkTWFwcGluZ1skZGlnaXRdKSA/ICRzdG9yZWRNYXBwaW5nWyRkaWdpdF0gOiAoIWVtcHR5KCRzdG9yZWRNYXBwaW5nWyhzdHJpbmcpJGRpZ2l0XSkgPyAkc3RvcmVkTWFwcGluZ1soc3RyaW5nKSRkaWdpdF0gOiAkZGVmYXVsdExldHRlcnNbJGRpZ2l0XSk7CiAgICAgICAgICAgICAgICAgICAgaWYgKGlzX2FycmF5KCRsZXR0ZXIpKSB7CiAgICAgICAgICAgICAgICAgICAgICAgICRsZXR0ZXIgPSByZXNldCgkbGV0dGVyKTsKICAgICAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgICAgICBAZW5kcGhwCiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJjb2wtbWQtMiBjb2wtc20tMyBjb2wteHMtNCI+CiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzcz0iZm9ybS1ncm91cCI+CiAgICAgICAgICAgICAgICAgICAgICAgIHshISBGb3JtOjpsYWJlbCgiY29tbW9uX3NldHRpbmdzW2Nvc3Rjb2RlX21hcHBpbmddW3skZGlnaXR9XSIsICRkaWdpdCAuICcgPScsIFsnY2xhc3MnID0+ICd0dy10ZXh0LXNtJ10pICEhfQogICAgICAgICAgICAgICAgICAgICAgICB7ISEgRm9ybTo6dGV4dCgiY29tbW9uX3NldHRpbmdzW2Nvc3Rjb2RlX21hcHBpbmddW3skZGlnaXR9XSIsICRsZXR0ZXIsIFsnY2xhc3MnID0+ICdmb3JtLWNvbnRyb2wgY29zdGNvZGUtbWFwLWlucHV0JywgJ2RhdGEtZGlnaXQnID0+ICRkaWdpdCwgJ21heGxlbmd0aCcgPT4gMSwgJ3JlYWRvbmx5JyA9PiB0cnVlLCAnc3R5bGUnID0+ICd0ZXh0LXRyYW5zZm9ybTp1cHBlcmNhc2U7IHRleHQtYWxpZ246Y2VudGVyOyBmb250LXdlaWdodDo4MDA7J10pOyAhIX0KICAgICAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICBAZW5kZm9yCiAgICAgICAgPC9kaXY+CgogICAgICAgIDxkaXYgY2xhc3M9ImNvc3Rjb2RlLXJlcGVhdC1wYW5lbCB0dy1wLTQgdHctcm91bmRlZC1sZyB0dy1ib3JkZXIiIHN0eWxlPSJtYXJnaW46MTBweCAwIDE4cHg7Ij4KICAgICAgICAgICAgPGg1IGNsYXNzPSJ0dy1mb250LXNlbWlib2xkIHR3LW0tMCB0dy1tYi0yIj5AbGFuZygnbGFuZ192MS5jb3N0Y29kZV9yZXBlYXRfbWFwcGluZycpPC9oNT4KICAgICAgICAgICAgPHAgY2xhc3M9ImNvc3Rjb2RlLXJlcGVhdC1kZXNjcmlwdGlvbiB0dy10ZXh0LXNtIHR3LW1iLTMiPkBsYW5nKCdsYW5nX3YxLmNvc3Rjb2RlX3JlcGVhdF9tYXBwaW5nX2hlbHAnKTwvcD4KICAgICAgICAgICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImNvbC1tZC03Ij4KICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJmb3JtLWdyb3VwIj4KICAgICAgICAgICAgICAgICAgICAgICAgPGxhYmVsIGZvcj0iY29zdGNvZGVfcmVwZWF0X2tleXdvcmQiPkBsYW5nKCdsYW5nX3YxLmNvc3Rjb2RlX3JlcGVhdF93b3JkJyk8L2xhYmVsPgogICAgICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0idGV4dCIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlkPSJjb3N0Y29kZV9yZXBlYXRfa2V5d29yZCIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5hbWU9ImNvbW1vbl9zZXR0aW5nc1tjb3N0Y29kZV9yZXBlYXRfa2V5d29yZF0iCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzcz0iZm9ybS1jb250cm9sIgogICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWU9Int7ICRzdG9yZWRSZXBlYXRLZXl3b3JkIH19IgogICAgICAgICAgICAgICAgICAgICAgICAgICAgbWF4bGVuZ3RoPSIxMCIKICAgICAgICAgICAgICAgICAgICAgICAgICAgIGF1dG9jb21wbGV0ZT0ib2ZmIgogICAgICAgICAgICAgICAgICAgICAgICAgICAgc3BlbGxjaGVjaz0iZmFsc2UiCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzdHlsZT0idGV4dC10cmFuc2Zvcm06dXBwZXJjYXNlO2ZvbnQtc2l6ZToxOHB4O2ZvbnQtd2VpZ2h0OjgwMDtsZXR0ZXItc3BhY2luZzouMThlbTsiPgogICAgICAgICAgICAgICAgICAgICAgICA8c21hbGwgY2xhc3M9ImhlbHAtYmxvY2siPkBsYW5nKCdsYW5nX3YxLmNvc3Rjb2RlX3JlcGVhdF93b3JkX2hlbHAnKTwvc21hbGw+CiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgaWQ9ImNvc3Rjb2RlX3JlcGVhdF9rZXl3b3JkX3N0YXR1cyIgY2xhc3M9InR3LXRleHQtc20gdHctZm9udC1zZW1pYm9sZCI+PC9kaXY+CiAgICAgICAgICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgICAgIDxkaXYgY2xhc3M9InJvdyI+CiAgICAgICAgICAgICAgICBAZm9yKCRkaWdpdCA9IDA7ICRkaWdpdCA8PSA5OyAkZGlnaXQrKykKICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzPSJjb2wtbWQtMiBjb2wtc20tMyBjb2wteHMtNCI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImZvcm0tZ3JvdXAiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgeyEhIEZvcm06OmxhYmVsKCJjb21tb25fc2V0dGluZ3NbY29zdGNvZGVfcmVwZWF0X21hcHBpbmddW3skZGlnaXR9XSIsICRkaWdpdCAuICcgcmVwZWF0ID0nLCBbJ2NsYXNzJyA9PiAndHctdGV4dC1zbSddKSAhIX0KICAgICAgICAgICAgICAgICAgICAgICAgICAgIHshISBGb3JtOjp0ZXh0KCJjb21tb25fc2V0dGluZ3NbY29zdGNvZGVfcmVwZWF0X21hcHBpbmddW3skZGlnaXR9XSIsICRzdG9yZWRSZXBlYXRMZXR0ZXJzWyRkaWdpdF0sIFsnY2xhc3MnID0+ICdmb3JtLWNvbnRyb2wgY29zdGNvZGUtcmVwZWF0LW1hcC1pbnB1dCcsICdkYXRhLWRpZ2l0JyA9PiAkZGlnaXQsICdtYXhsZW5ndGgnID0+IDEsICdyZWFkb25seScgPT4gdHJ1ZSwgJ3N0eWxlJyA9PiAndGV4dC10cmFuc2Zvcm06dXBwZXJjYXNlOyB0ZXh0LWFsaWduOmNlbnRlcjsgZm9udC13ZWlnaHQ6ODAwOyddKTsgISF9CiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgICAgIDwvZGl2PgogICAgICAgICAgICAgICAgQGVuZGZvcgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICA8L2Rpdj4KCiAgICAgICAgPGRpdiBjbGFzcz0icm93Ij4KICAgICAgICAgICAgPGRpdiBjbGFzcz0iY29sLW1kLTEyIj4KICAgICAgICAgICAgICAgIDxkaXYgY2xhc3M9ImNvc3Rjb2RlLWV4YW1wbGUtcGFuZWwgdHctcC0zIHR3LXJvdW5kZWQtbGcgdHctYm9yZGVyIHR3LXRleHQtc20iPgogICAgICAgICAgICAgICAgICAgIDxiPkBsYW5nKCdsYW5nX3YxLmNvc3Rjb2RlX2V4YW1wbGUnKTo8L2I+PGJyPgogICAgICAgICAgICAgICAgICAgIDxzdHJvbmcgaWQ9ImNvc3Rjb2RlX2xpdmVfZXhhbXBsZSI+PC9zdHJvbmc+PGJyPgogICAgICAgICAgICAgICAgICAgIEBsYW5nKCdsYW5nX3YxLmNvc3Rjb2RlX2V4YW1wbGVfdGV4dCcpCiAgICAgICAgICAgICAgICA8L2Rpdj4KICAgICAgICAgICAgPC9kaXY+CiAgICAgICAgPC9kaXY+CgogICAgICAgIDxkaXYgY2xhc3M9InJvdyIgc3R5bGU9Im1hcmdpbi10b3A6MTZweDsiPgogICAgICAgICAgICA8ZGl2IGNsYXNzPSJjb2wtbWQtMTIiPgogICAgICAgICAgICAgICAgPGg1IGNsYXNzPSJ0dy1mb250LXNlbWlib2xkIHR3LW1iLTIiPkBsYW5nKCdsYW5nX3YxLnJlZ2VuZXJhdGVfY29zdGNvZGVzJyk8L2g1PgogICAgICAgICAgICAgICAgPHAgY2xhc3M9InR3LXRleHQtc20gdHctdGV4dC1ncmF5LTUwMCB0dy1tYi0zIj5AbGFuZygnbGFuZ192MS5yZWdlbmVyYXRlX2Nvc3Rjb2Rlc19oZWxwJyk8L3A+CiAgICAgICAgICAgICAgICA8YnV0dG9uIHR5cGU9ImJ1dHRvbiIgY2xhc3M9ImJ0biBidG4tcHJpbWFyeSIgaWQ9ImJ0bl9yZWdlbmVyYXRlX2Nvc3Rjb2RlcyI+CiAgICAgICAgICAgICAgICAgICAgPGkgY2xhc3M9ImZhIGZhLW1hZ2ljIj48L2k+IEBsYW5nKCdsYW5nX3YxLnJlZ2VuZXJhdGVfY29zdGNvZGVzX25vdycpCiAgICAgICAgICAgICAgICA8L2J1dHRvbj4KICAgICAgICAgICAgICAgIDxzcGFuIGlkPSJyZWdlbmVyYXRlX2Nvc3Rjb2Rlc19zdGF0dXMiIHN0eWxlPSJtYXJnaW4tbGVmdDoxMHB4OyI+PC9zcGFuPgogICAgICAgICAgICA8L2Rpdj4KICAgICAgICA8L2Rpdj4KICAgIDwvZGl2Pgo8L2Rpdj4KCjxzdHlsZT4KICAgICNzZXR0aW5ncy1jb3N0Y29kZSAuY29zdGNvZGUtcmVwZWF0LXBhbmVsIHsKICAgICAgICBiYWNrZ3JvdW5kOiAjMTExODI3ICFpbXBvcnRhbnQ7CiAgICAgICAgYm9yZGVyOiAxcHggc29saWQgIzM3NDE1MSAhaW1wb3J0YW50OwogICAgICAgIGNvbG9yOiAjZjhmYWZjICFpbXBvcnRhbnQ7CiAgICAgICAgYm94LXNoYWRvdzogMCAxMHB4IDI0cHggcmdiYSgwLCAwLCAwLCAuMjIpOwogICAgfQogICAgI3NldHRpbmdzLWNvc3Rjb2RlIC5jb3N0Y29kZS1yZXBlYXQtcGFuZWwgaDUsCiAgICAjc2V0dGluZ3MtY29zdGNvZGUgLmNvc3Rjb2RlLXJlcGVhdC1wYW5lbCBsYWJlbCB7CiAgICAgICAgY29sb3I6ICNmOGZhZmMgIWltcG9ydGFudDsKICAgIH0KICAgICNzZXR0aW5ncy1jb3N0Y29kZSAuY29zdGNvZGUtcmVwZWF0LXBhbmVsIC5jb3N0Y29kZS1yZXBlYXQtZGVzY3JpcHRpb24sCiAgICAjc2V0dGluZ3MtY29zdGNvZGUgLmNvc3Rjb2RlLXJlcGVhdC1wYW5lbCAuaGVscC1ibG9jayB7CiAgICAgICAgY29sb3I6ICNjYmQ1ZTEgIWltcG9ydGFudDsKICAgIH0KICAgICNzZXR0aW5ncy1jb3N0Y29kZSAuY29zdGNvZGUtcmVwZWF0LXBhbmVsIC5mb3JtLWNvbnRyb2wgewogICAgICAgIGJhY2tncm91bmQ6ICMwODBkMTYgIWltcG9ydGFudDsKICAgICAgICBib3JkZXI6IDFweCBzb2xpZCAjNGI1NTYzICFpbXBvcnRhbnQ7CiAgICAgICAgY29sb3I6ICNmZmZmZmYgIWltcG9ydGFudDsKICAgICAgICBib3gtc2hhZG93OiBub25lICFpbXBvcnRhbnQ7CiAgICB9CiAgICAjc2V0dGluZ3MtY29zdGNvZGUgLmNvc3Rjb2RlLXJlcGVhdC1wYW5lbCAuZm9ybS1jb250cm9sOmZvY3VzIHsKICAgICAgICBib3JkZXItY29sb3I6ICM2MGE1ZmEgIWltcG9ydGFudDsKICAgICAgICBib3gtc2hhZG93OiAwIDAgMCAzcHggcmdiYSg5NiwgMTY1LCAyNTAsIC4yKSAhaW1wb3J0YW50OwogICAgfQogICAgI3NldHRpbmdzLWNvc3Rjb2RlIC5jb3N0Y29kZS1yZXBlYXQtcGFuZWwgLmNvc3Rjb2RlLXJlcGVhdC1tYXAtaW5wdXRbcmVhZG9ubHldIHsKICAgICAgICBjb2xvcjogI2ZmZmZmZiAhaW1wb3J0YW50OwogICAgICAgIG9wYWNpdHk6IDE7CiAgICB9CiAgICAjc2V0dGluZ3MtY29zdGNvZGUgLmNvc3Rjb2RlLWV4YW1wbGUtcGFuZWwgewogICAgICAgIGJhY2tncm91bmQ6ICMwYzFiMzEgIWltcG9ydGFudDsKICAgICAgICBib3JkZXI6IDFweCBzb2xpZCAjMjY0YTc1ICFpbXBvcnRhbnQ7CiAgICAgICAgY29sb3I6ICNkYmVhZmUgIWltcG9ydGFudDsKICAgIH0KICAgICNzZXR0aW5ncy1jb3N0Y29kZSAuY29zdGNvZGUtZXhhbXBsZS1wYW5lbCBzdHJvbmcsCiAgICAjc2V0dGluZ3MtY29zdGNvZGUgLmNvc3Rjb2RlLWV4YW1wbGUtcGFuZWwgYiB7CiAgICAgICAgY29sb3I6ICNmZmZmZmYgIWltcG9ydGFudDsKICAgIH0KPC9zdHlsZT4KCjxzY3JpcHQ+CiAgICAkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHsKICAgICAgICBmdW5jdGlvbiB1cGRhdGVDb3N0Y29kZVRvZ2dsZSgpIHsKICAgICAgICAgICAgdmFyIGlzQ2hlY2tlZCA9ICQoJyN0b2dnbGVfY29zdGNvZGUnKS5pcygnOmNoZWNrZWQnKTsKICAgICAgICAgICAgdmFyICRjb250YWluZXIgPSAkKCcjY29zdGNvZGVfc2V0dGluZ3NfY29udGFpbmVyJyk7CiAgICAgICAgICAgIHZhciAkbGFiZWwgPSAkKCcjY29zdGNvZGVfdG9nZ2xlX2xhYmVsJyk7CiAgICAgICAgICAgIGlmIChpc0NoZWNrZWQpIHsKICAgICAgICAgICAgICAgICRjb250YWluZXIuY3NzKHsKICAgICAgICAgICAgICAgICAgICAnb3BhY2l0eSc6ICcxJywKICAgICAgICAgICAgICAgICAgICAncG9pbnRlci1ldmVudHMnOiAnYXV0bycsCiAgICAgICAgICAgICAgICAgICAgJ2ZpbHRlcic6ICdub25lJwogICAgICAgICAgICAgICAgfSk7CiAgICAgICAgICAgICAgICAkbGFiZWwudGV4dChAanNvbihfXygnbGFuZ192MS5lbmFibGVkJykpKTsKICAgICAgICAgICAgfSBlbHNlIHsKICAgICAgICAgICAgICAgICRjb250YWluZXIuY3NzKHsKICAgICAgICAgICAgICAgICAgICAnb3BhY2l0eSc6ICcwLjUnLAogICAgICAgICAgICAgICAgICAgICdwb2ludGVyLWV2ZW50cyc6ICdub25lJywKICAgICAgICAgICAgICAgICAgICAnZmlsdGVyJzogJ2dyYXlzY2FsZSg4MCUpJwogICAgICAgICAgICAgICAgfSk7CiAgICAgICAgICAgICAgICAkbGFiZWwudGV4dChAanNvbihfXygnbGFuZ192MS5kaXNhYmxlZCcpKSk7CiAgICAgICAgICAgIH0KICAgICAgICB9CiAgICAgICAgJCgnI3RvZ2dsZV9jb3N0Y29kZScpLm9uKCdjaGFuZ2UnLCB1cGRhdGVDb3N0Y29kZVRvZ2dsZSk7CiAgICAgICAgdXBkYXRlQ29zdGNvZGVUb2dnbGUoKTsKCiAgICAgICAgZnVuY3Rpb24gdXBkYXRlQ29zdGNvZGVFeGFtcGxlKCkgewogICAgICAgICAgICB2YXIgbWFwcGluZyA9IFtdOwogICAgICAgICAgICB2YXIgcmVwZWF0TWFwcGluZyA9IFtdOwogICAgICAgICAgICAkKCcuY29zdGNvZGUtbWFwLWlucHV0JykuZWFjaChmdW5jdGlvbiAoKSB7CiAgICAgICAgICAgICAgICB2YXIgZGlnaXQgPSBwYXJzZUludCgkKHRoaXMpLmRhdGEoJ2RpZ2l0JyksIDEwKTsKICAgICAgICAgICAgICAgIHZhciB2YWx1ZSA9IFN0cmluZygkKHRoaXMpLnZhbCgpIHx8ICcnKS50cmltKCkudG9VcHBlckNhc2UoKTsKICAgICAgICAgICAgICAgIG1hcHBpbmdbZGlnaXRdID0gL15bQS1aXSQvLnRlc3QodmFsdWUpID8gdmFsdWUgOiBkaWdpdDsKICAgICAgICAgICAgfSk7CiAgICAgICAgICAgICQoJy5jb3N0Y29kZS1yZXBlYXQtbWFwLWlucHV0JykuZWFjaChmdW5jdGlvbiAoKSB7CiAgICAgICAgICAgICAgICB2YXIgZGlnaXQgPSBwYXJzZUludCgkKHRoaXMpLmRhdGEoJ2RpZ2l0JyksIDEwKTsKICAgICAgICAgICAgICAgIHZhciB2YWx1ZSA9IFN0cmluZygkKHRoaXMpLnZhbCgpIHx8ICcnKS50cmltKCkudG9VcHBlckNhc2UoKTsKICAgICAgICAgICAgICAgIHJlcGVhdE1hcHBpbmdbZGlnaXRdID0gL15bQS1aXSQvLnRlc3QodmFsdWUpID8gdmFsdWUgOiBkaWdpdDsKICAgICAgICAgICAgfSk7CiAgICAgICAgICAgIHZhciBvY2N1cnJlbmNlcyA9IHt9OwogICAgICAgICAgICB2YXIgY29kZSA9ICcnOwogICAgICAgICAgICAnMTAwMCcuc3BsaXQoJycpLmZvckVhY2goZnVuY3Rpb24gKGRpZ2l0KSB7CiAgICAgICAgICAgICAgICB2YXIgb2NjdXJyZW5jZSA9IG9jY3VycmVuY2VzW2RpZ2l0XSB8fCAwOwogICAgICAgICAgICAgICAgb2NjdXJyZW5jZXNbZGlnaXRdID0gb2NjdXJyZW5jZSArIDE7CiAgICAgICAgICAgICAgICBpZiAob2NjdXJyZW5jZSA9PT0gMCkgewogICAgICAgICAgICAgICAgICAgIGNvZGUgKz0gbWFwcGluZ1twYXJzZUludChkaWdpdCwgMTApXSB8fCBkaWdpdDsKICAgICAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICAgICAgdmFyIHJlcGVhdERpZ2l0ID0gKHBhcnNlSW50KGRpZ2l0LCAxMCkgKyAoKG9jY3VycmVuY2UgLSAxKSAqIDMpKSAlIDEwOwogICAgICAgICAgICAgICAgICAgIGNvZGUgKz0gcmVwZWF0TWFwcGluZ1tyZXBlYXREaWdpdF0gfHwgcmVwZWF0RGlnaXQ7CiAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgIH0pOwogICAgICAgICAgICAkKCcjY29zdGNvZGVfbGl2ZV9leGFtcGxlJykudGV4dCgnMTAwMCDihpIgJyArIGNvZGUpOwogICAgICAgIH0KCiAgICAgICAgZnVuY3Rpb24gYXBwbHlDb3N0Y29kZUtleXdvcmQoKSB7CiAgICAgICAgICAgIHZhciAkaW5wdXQgPSAkKCcjY29zdGNvZGVfa2V5d29yZCcpOwogICAgICAgICAgICB2YXIga2V5d29yZCA9IFN0cmluZygkaW5wdXQudmFsKCkgfHwgJycpLnRvVXBwZXJDYXNlKCkucmVwbGFjZSgvW15BLVpdL2csICcnKS5zbGljZSgwLCAxMCk7CiAgICAgICAgICAgICRpbnB1dC52YWwoa2V5d29yZCk7CiAgICAgICAgICAgIHZhciB1bmlxdWVDb3VudCA9IChuZXcgU2V0KGtleXdvcmQuc3BsaXQoJycpKSkuc2l6ZTsKICAgICAgICAgICAgdmFyIHZhbGlkID0ga2V5d29yZC5sZW5ndGggPT09IDEwICYmIHVuaXF1ZUNvdW50ID09PSAxMDsKICAgICAgICAgICAgdmFyICRzdGF0dXMgPSAkKCcjY29zdGNvZGVfa2V5d29yZF9zdGF0dXMnKTsKCiAgICAgICAgICAgIGlmICh2YWxpZCkgewogICAgICAgICAgICAgICAgJCgnLmNvc3Rjb2RlLW1hcC1pbnB1dCcpLmVhY2goZnVuY3Rpb24gKCkgewogICAgICAgICAgICAgICAgICAgICQodGhpcykudmFsKGtleXdvcmQuY2hhckF0KHBhcnNlSW50KCQodGhpcykuZGF0YSgnZGlnaXQnKSwgMTApKSk7CiAgICAgICAgICAgICAgICB9KTsKICAgICAgICAgICAgICAgICRzdGF0dXMuY3NzKCdjb2xvcicsICcjMTZhMzRhJykudGV4dChAanNvbihfXygnbGFuZ192MS5jb3N0Y29kZV9zZWNyZXRfd29yZF92YWxpZCcpKSk7CiAgICAgICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICAgICAkc3RhdHVzLmNzcygnY29sb3InLCAnI2RjMjYyNicpLnRleHQoCiAgICAgICAgICAgICAgICAgICAga2V5d29yZC5sZW5ndGggPCAxMAogICAgICAgICAgICAgICAgICAgICAgICA/IEBqc29uKF9fKCdsYW5nX3YxLmNvc3Rjb2RlX3NlY3JldF93b3JkX2xlbmd0aF9lcnJvcicpKQogICAgICAgICAgICAgICAgICAgICAgICA6IEBqc29uKF9fKCdsYW5nX3YxLmNvc3Rjb2RlX3NlY3JldF93b3JkX3VuaXF1ZV9lcnJvcicpKQogICAgICAgICAgICAgICAgKTsKICAgICAgICAgICAgfQogICAgICAgICAgICBhcHBseUNvc3Rjb2RlUmVwZWF0S2V5d29yZCgpOwogICAgICAgICAgICB1cGRhdGVDb3N0Y29kZUV4YW1wbGUoKTsKICAgICAgICB9CgogICAgICAgIGZ1bmN0aW9uIGFwcGx5Q29zdGNvZGVSZXBlYXRLZXl3b3JkKCkgewogICAgICAgICAgICB2YXIgbWFpbktleXdvcmQgPSBTdHJpbmcoJCgnI2Nvc3Rjb2RlX2tleXdvcmQnKS52YWwoKSB8fCAnJykudG9VcHBlckNhc2UoKTsKICAgICAgICAgICAgdmFyICRpbnB1dCA9ICQoJyNjb3N0Y29kZV9yZXBlYXRfa2V5d29yZCcpOwogICAgICAgICAgICB2YXIga2V5d29yZCA9IFN0cmluZygkaW5wdXQudmFsKCkgfHwgJycpLnRvVXBwZXJDYXNlKCkucmVwbGFjZSgvW15BLVpdL2csICcnKS5zbGljZSgwLCAxMCk7CiAgICAgICAgICAgICRpbnB1dC52YWwoa2V5d29yZCk7CiAgICAgICAgICAgIHZhciBsZXR0ZXJzID0ga2V5d29yZC5zcGxpdCgnJyk7CiAgICAgICAgICAgIHZhciB2YWxpZExlbmd0aCA9IGtleXdvcmQubGVuZ3RoID09PSAxMDsKICAgICAgICAgICAgdmFyIHVuaXF1ZSA9IChuZXcgU2V0KGxldHRlcnMpKS5zaXplID09PSAxMDsKICAgICAgICAgICAgdmFyIG5vT3ZlcmxhcCA9IGxldHRlcnMuZXZlcnkoZnVuY3Rpb24gKGxldHRlcikgewogICAgICAgICAgICAgICAgcmV0dXJuIG1haW5LZXl3b3JkLmluZGV4T2YobGV0dGVyKSA9PT0gLTE7CiAgICAgICAgICAgIH0pOwogICAgICAgICAgICB2YXIgdmFsaWQgPSB2YWxpZExlbmd0aCAmJiB1bmlxdWUgJiYgbm9PdmVybGFwOwogICAgICAgICAgICB2YXIgJHN0YXR1cyA9ICQoJyNjb3N0Y29kZV9yZXBlYXRfa2V5d29yZF9zdGF0dXMnKTsKCiAgICAgICAgICAgIGlmICh2YWxpZCkgewogICAgICAgICAgICAgICAgJCgnLmNvc3Rjb2RlLXJlcGVhdC1tYXAtaW5wdXQnKS5lYWNoKGZ1bmN0aW9uICgpIHsKICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLnZhbChrZXl3b3JkLmNoYXJBdChwYXJzZUludCgkKHRoaXMpLmRhdGEoJ2RpZ2l0JyksIDEwKSkpOwogICAgICAgICAgICAgICAgfSk7CiAgICAgICAgICAgICAgICAkc3RhdHVzLmNzcygnY29sb3InLCAnIzE2YTM0YScpLnRleHQoQGpzb24oX18oJ2xhbmdfdjEuY29zdGNvZGVfcmVwZWF0X3dvcmRfdmFsaWQnKSkpOwogICAgICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAgICAgdmFyIG1lc3NhZ2UgPSAhdmFsaWRMZW5ndGgKICAgICAgICAgICAgICAgICAgICA/IEBqc29uKF9fKCdsYW5nX3YxLmNvc3Rjb2RlX3NlY3JldF93b3JkX2xlbmd0aF9lcnJvcicpKQogICAgICAgICAgICAgICAgICAgIDogKCF1bmlxdWUKICAgICAgICAgICAgICAgICAgICAgICAgPyBAanNvbihfXygnbGFuZ192MS5jb3N0Y29kZV9zZWNyZXRfd29yZF91bmlxdWVfZXJyb3InKSkKICAgICAgICAgICAgICAgICAgICAgICAgOiBAanNvbihfXygnbGFuZ192MS5jb3N0Y29kZV9yZXBlYXRfd29yZF9vdmVybGFwX2Vycm9yJykpKTsKICAgICAgICAgICAgICAgICRzdGF0dXMuY3NzKCdjb2xvcicsICcjZGMyNjI2JykudGV4dChtZXNzYWdlKTsKICAgICAgICAgICAgfQogICAgICAgICAgICB1cGRhdGVDb3N0Y29kZUV4YW1wbGUoKTsKICAgICAgICB9CiAgICAgICAgJChkb2N1bWVudCkub24oJ2lucHV0IGNoYW5nZScsICcjY29zdGNvZGVfa2V5d29yZCcsIGFwcGx5Q29zdGNvZGVLZXl3b3JkKTsKICAgICAgICAkKGRvY3VtZW50KS5vbignaW5wdXQgY2hhbmdlJywgJyNjb3N0Y29kZV9yZXBlYXRfa2V5d29yZCcsIGFwcGx5Q29zdGNvZGVSZXBlYXRLZXl3b3JkKTsKICAgICAgICBhcHBseUNvc3Rjb2RlS2V5d29yZCgpOwoKICAgICAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCAnI2J0bl9yZWdlbmVyYXRlX2Nvc3Rjb2RlcycsIGZ1bmN0aW9uIChlKSB7CiAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTsKICAgICAgICAgICAgaWYgKCF3aW5kb3cuY29uZmlybShAanNvbihfXygnbGFuZ192MS5yZWdlbmVyYXRlX2Nvc3Rjb2Rlc19jb25maXJtJykpKSkgewogICAgICAgICAgICAgICAgcmV0dXJuOwogICAgICAgICAgICB9CiAgICAgICAgICAgIHZhciAkYnRuID0gJCh0aGlzKTsKICAgICAgICAgICAgdmFyICRzdGF0dXMgPSAkKCcjcmVnZW5lcmF0ZV9jb3N0Y29kZXNfc3RhdHVzJyk7CiAgICAgICAgICAgIHZhciBjdXJyZW50TWFwcGluZyA9IHt9OwogICAgICAgICAgICAkKCdbbmFtZV49ImNvbW1vbl9zZXR0aW5nc1tjb3N0Y29kZV9tYXBwaW5nXSJdJykuZWFjaChmdW5jdGlvbiAoKSB7CiAgICAgICAgICAgICAgICB2YXIgbWF0Y2ggPSB0aGlzLm5hbWUubWF0Y2goL1xbKFxkKVxdJC8pOwogICAgICAgICAgICAgICAgaWYgKG1hdGNoKSB7CiAgICAgICAgICAgICAgICAgICAgY3VycmVudE1hcHBpbmdbbWF0Y2hbMV1dID0gJCh0aGlzKS52YWwoKTsKICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgfSk7CiAgICAgICAgICAgICRidG4ucHJvcCgnZGlzYWJsZWQnLCB0cnVlKTsKICAgICAgICAgICAgJHN0YXR1cy5jc3MoJ2NvbG9yJywgJyM5NGEzYjgnKS50ZXh0KEBqc29uKF9fKCdsYW5nX3YxLnBsZWFzZV93YWl0JykpKTsKICAgICAgICAgICAgJC5hamF4KHsKICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLAogICAgICAgICAgICAgICAgdXJsOiAie3sgdXJsKCcvcHJvZHVjdHMvcmVnZW5lcmF0ZS1jb3N0LWNvZGVzJykgfX0iLAogICAgICAgICAgICAgICAgZGF0YVR5cGU6ICdqc29uJywKICAgICAgICAgICAgICAgIGRhdGE6IHsKICAgICAgICAgICAgICAgICAgICBfdG9rZW46ICQoJ21ldGFbbmFtZT0iY3NyZi10b2tlbiJdJykuYXR0cignY29udGVudCcpLAogICAgICAgICAgICAgICAgICAgIGVuYWJsZV9jb3N0Y29kZV9nZW5lcmF0aW9uOiAkKCcjdG9nZ2xlX2Nvc3Rjb2RlJykuaXMoJzpjaGVja2VkJykgPyAxIDogMCwKICAgICAgICAgICAgICAgICAgICBjb3N0Y29kZV9rZXl3b3JkOiAkKCcjY29zdGNvZGVfa2V5d29yZCcpLnZhbCgpLAogICAgICAgICAgICAgICAgICAgIGNvc3Rjb2RlX21hcHBpbmc6IGN1cnJlbnRNYXBwaW5nLAogICAgICAgICAgICAgICAgICAgIGNvc3Rjb2RlX3JlcGVhdF9rZXl3b3JkOiAkKCcjY29zdGNvZGVfcmVwZWF0X2tleXdvcmQnKS52YWwoKQogICAgICAgICAgICAgICAgfSwKICAgICAgICAgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uIChyZXMpIHsKICAgICAgICAgICAgICAgICAgICBpZiAocmVzICYmIHJlcy5zdWNjZXNzKSB7CiAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgdG9hc3RyICE9PSAndW5kZWZpbmVkJykgdG9hc3RyLnN1Y2Nlc3MocmVzLm1zZyk7CiAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0dXMuY3NzKCdjb2xvcicsICcjMTZhMzRhJykudGV4dChyZXMubXNnIHx8ICdEb25lJyk7CiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHsKICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiB0b2FzdHIgIT09ICd1bmRlZmluZWQnKSB0b2FzdHIuZXJyb3IoKHJlcyAmJiByZXMubXNnKSB8fCAnRmFpbGVkJyk7CiAgICAgICAgICAgICAgICAgICAgICAgICRzdGF0dXMuY3NzKCdjb2xvcicsICcjZGMyNjI2JykudGV4dCgocmVzICYmIHJlcy5tc2cpIHx8ICdGYWlsZWQnKTsKICAgICAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgICAgICB9LAogICAgICAgICAgICAgICAgZXJyb3I6IGZ1bmN0aW9uICh4aHIpIHsKICAgICAgICAgICAgICAgICAgICB2YXIgbXNnID0gKHhoci5yZXNwb25zZUpTT04gJiYgeGhyLnJlc3BvbnNlSlNPTi5tc2cpID8geGhyLnJlc3BvbnNlSlNPTi5tc2cgOiAnRmFpbGVkJzsKICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIHRvYXN0ciAhPT0gJ3VuZGVmaW5lZCcpIHRvYXN0ci5lcnJvcihtc2cpOwogICAgICAgICAgICAgICAgICAgICRzdGF0dXMuY3NzKCdjb2xvcicsICcjZGMyNjI2JykudGV4dChtc2cpOwogICAgICAgICAgICAgICAgfSwKICAgICAgICAgICAgICAgIGNvbXBsZXRlOiBmdW5jdGlvbiAoKSB7CiAgICAgICAgICAgICAgICAgICAgJGJ0bi5wcm9wKCdkaXNhYmxlZCcsIGZhbHNlKTsKICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgfSk7CiAgICAgICAgfSk7CiAgICB9KTsKPC9zY3JpcHQ+Cg==';
$payloadSha1 = 'dc52074d93c49911632b40f9cf863d83b9dab96d';
$payload = base64_decode($payloadBase64, true);

if ($payload === false || sha1($payload) !== $payloadSha1) {
    qposRender(false, 'Embedded payload verification failed', array('The installer payload is corrupt. Upload a fresh installer.'));
}

function qposDetectRoot($installerDirectory)
{
    $candidates = array($installerDirectory, dirname($installerDirectory));
    foreach ($candidates as $candidate) {
        $candidate = realpath($candidate);
        if ($candidate && is_file($candidate . '/artisan') && is_dir($candidate . '/resources') && is_dir($candidate . '/bootstrap')) {
            return $candidate;
        }
    }
    return false;
}

function qposClearViewCache($directory, array &$messages)
{
    if (!is_dir($directory)) {
        $messages[] = 'Compiled-view directory was absent; nothing to remove.';
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    $removed = 0;
    foreach ($iterator as $item) {
        if ($item->isLink()) {
            throw new RuntimeException('Refusing to follow symbolic link in compiled-view directory: ' . $item->getPathname());
        }
        if ($item->isDir()) {
            if (!@rmdir($item->getPathname()) && is_dir($item->getPathname())) {
                throw new RuntimeException('Could not remove compiled-view subdirectory: ' . $item->getPathname());
            }
        } elseif ($item->getFilename() !== '.gitignore') {
            if (!@unlink($item->getPathname()) && file_exists($item->getPathname())) {
                throw new RuntimeException('Could not remove compiled view: ' . $item->getPathname());
            }
            $removed++;
        }
    }
    $messages[] = 'Cleared ' . $removed . ' compiled view file(s); preserved .gitignore.';
}

function qposAtomicWrite($target, $contents, $expectedSha1)
{
    $directory = dirname($target);
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Target directory is not writable: ' . $directory);
    }

    $temporary = tempnam($directory, '.qpos-costcode-');
    if ($temporary === false) {
        throw new RuntimeException('Could not create a temporary file in target directory.');
    }

    try {
        $handle = @fopen($temporary, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Could not open temporary file for writing.');
        }
        $length = strlen($contents);
        $written = 0;
        while ($written < $length) {
            $count = fwrite($handle, substr($contents, $written));
            if ($count === false || $count === 0) {
                fclose($handle);
                throw new RuntimeException('Could not completely write temporary file.');
            }
            $written += $count;
        }
        if (!fflush($handle)) {
            fclose($handle);
            throw new RuntimeException('Could not flush temporary file.');
        }
        fclose($handle);
        @chmod($temporary, 0644);

        if (sha1_file($temporary) !== $expectedSha1) {
            throw new RuntimeException('Temporary file SHA1 verification failed.');
        }
        if (!@rename($temporary, $target)) {
            throw new RuntimeException('Atomic rename into place failed.');
        }
        if (!is_file($target) || sha1_file($target) !== $expectedSha1) {
            throw new RuntimeException('Installed file SHA1 verification failed.');
        }
    } finally {
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }
}

$messages = array();
try {
    $root = qposDetectRoot(__DIR__);
    if ($root === false) {
        throw new RuntimeException('Laravel root was not found in the installer directory or its parent. Place this file beside public/index.php.');
    }
    $messages[] = 'Laravel root detected: ' . $root;

    $target = $root . '/resources/views/business/partials/settings_costcode.blade.php';
    $backupDirectory = $root . '/storage/app/qpos-installer-backups';
    if (!is_dir($backupDirectory) && !@mkdir($backupDirectory, 0755, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException('Could not create backup directory: ' . $backupDirectory);
    }
    if (!is_writable($backupDirectory)) {
        throw new RuntimeException('Backup directory is not writable: ' . $backupDirectory);
    }

    if (is_file($target)) {
        $backup = $backupDirectory . '/settings_costcode.blade.php.' . gmdate('Ymd_His') . '.' . substr(sha1(uniqid('', true)), 0, 8) . '.bak';
        if (!@copy($target, $backup) || !is_file($backup) || sha1_file($backup) !== sha1_file($target)) {
            throw new RuntimeException('Existing Blade backup failed verification.');
        }
        $messages[] = 'Existing Blade backed up to: ' . $backup;
    } else {
        $messages[] = 'No existing Blade file was present; no backup was needed.';
    }

    qposAtomicWrite($target, $payload, $payloadSha1);
    $messages[] = 'Blade installed atomically and SHA1 verified: ' . $payloadSha1;

    qposClearViewCache($root . '/storage/framework/views', $messages);

    $autoload = $root . '/vendor/autoload.php';
    $bootstrap = $root . '/bootstrap/app.php';
    if (is_file($autoload) && is_file($bootstrap)) {
        try {
            require_once $autoload;
            $app = require $bootstrap;
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $viewResult = $kernel->call('view:clear');
            $cacheResult = $kernel->call('cache:clear');
            if ($viewResult !== 0 || $cacheResult !== 0) {
                throw new RuntimeException('Laravel cache commands returned a non-zero status.');
            }
            $messages[] = 'Laravel view:clear and cache:clear completed.';
        } catch (Throwable $bootstrapError) {
            $messages[] = 'Laravel bootstrap/cache command warning: ' . $bootstrapError->getMessage();
        }
    } else {
        $messages[] = 'Laravel bootstrap skipped because vendor/autoload.php or bootstrap/app.php was unavailable.';
    }

    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($target, true);
        @opcache_invalidate(__FILE__, true);
        $messages[] = 'OPcache entries invalidated.';
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $messages[] = 'OPcache reset requested.';
    }

    $installed = @file_get_contents($target);
    if ($installed === false || sha1($installed) !== $payloadSha1) {
        throw new RuntimeException('Final installed Blade SHA1 verification failed.');
    }
    foreach (array('costcode-repeat-panel', 'costcode-example-panel', '#111827') as $marker) {
        if (strpos($installed, $marker) === false) {
            throw new RuntimeException('Required marker missing after installation: ' . $marker);
        }
    }
    $messages[] = 'Marker verification passed: costcode-repeat-panel, costcode-example-panel, and #111827.';
    $messages[] = 'No database changes were made and no cost codes were regenerated.';

    qposRender(true, 'Cost-code dark theme installed', $messages);
} catch (Throwable $error) {
    $messages[] = $error->getMessage();
    $messages[] = 'Installation did not complete. Review permissions and use the verified backup if restoration is needed.';
    qposRender(false, 'Cost-code dark theme installation failed', $messages);
}
