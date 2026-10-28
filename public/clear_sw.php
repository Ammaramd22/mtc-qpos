<?php
/**
 * Unregister old service workers + clear caches (fixes stale Invoice Settings page).
 * Open once: /clear_sw.php?key=qposclear2026  then delete this file.
 */
header('Content-Type: text/html; charset=utf-8');
if (($_GET['key'] ?? '') !== 'qposclear2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Clear Service Worker</title></head>
<body>
<h1>Clearing service worker…</h1>
<script>
(async function () {
  try {
    if ('serviceWorker' in navigator) {
      var regs = await navigator.serviceWorker.getRegistrations();
      for (var i = 0; i < regs.length; i++) {
        await regs[i].unregister();
      }
    }
    if (window.caches) {
      var keys = await caches.keys();
      for (var j = 0; j < keys.length; j++) {
        await caches.delete(keys[j]);
      }
    }
    document.body.innerHTML = '<h1 style="color:green">Done</h1><p>Service workers and caches cleared. <a href="/invoice-layouts">Open Invoice Settings</a></p><p>Delete public/clear_sw.php now.</p>';
  } catch (e) {
    document.body.innerHTML = '<h1 style="color:red">Error</h1><pre>' + e + '</pre>';
  }
})();
</script>
</body></html>
