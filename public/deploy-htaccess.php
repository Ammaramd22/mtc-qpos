<?php
/**
 * Deploy root .htaccess for demo.vsoftpos.lk
 * Upload to public/ and visit in browser.
 */

$htaccess = base64_decode('PElmTW9kdWxlIG1vZF9yZXdyaXRlLmM+CiAgICBSZXdyaXRlRW5naW5lIE9uCiAgICBSZXdyaXRlQmFzZSAvCgogICAgIyBTdG9wIGlmIGFscmVhZHkgcmV3cml0dGVuIHRvIHB1YmxpYyAocHJldmVudHMgaW5maW5pdGUgbG9vcCkKICAgIFJld3JpdGVDb25kICV7RU5WOlJFRElSRUNUX1NUQVRVU30gXiQKCiAgICAjIERvIG5vdCByZXdyaXRlIGlmIGFscmVhZHkgcG9pbnRpbmcgdG8gcHVibGljLwogICAgUmV3cml0ZUNvbmQgJXtSRVFVRVNUX1VSSX0gIV4vcHVibGljLwoKICAgICMgUmV3cml0ZSBldmVyeXRoaW5nIHRvIHB1YmxpYy8gKGludGVybmFsIHJld3JpdGUsIHByZXNlcnZlcyBQT1NUL0NTUkYpCiAgICBSZXdyaXRlUnVsZSBeKC4qKSQgL3B1YmxpYy8kMSBbTF0KPC9JZk1vZHVsZT4KCiMgUHJldmVudCBkaXJlY3RvcnkgbGlzdGluZwpPcHRpb25zIC1JbmRleGVzCgojIFByb3RlY3Qgc2Vuc2l0aXZlIGZpbGVzCjxGaWxlc01hdGNoICJeXC4iPgogICAgT3JkZXIgYWxsb3csZGVueQogICAgRGVueSBmcm9tIGFsbAo8L0ZpbGVzTWF0Y2g+Cg==');
$path = dirname(__DIR__) . '/.htaccess';

file_put_contents($path, $htaccess);
echo "<p>✅ Root .htaccess deployed</p>";
echo "<pre>" . htmlspecialchars($htaccess) . "</pre>";
echo "<p><b>Test:</b> Visit <a href='https://demo.vsoftpos.lk/'>https://demo.vsoftpos.lk/</a></p>";
echo "<p><b>Delete this file after use.</b></p>";
