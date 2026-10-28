<?php
$j = json_decode(file_get_contents($argv[1]), true);
file_put_contents($argv[2], base64_decode($j['data']));
echo strlen(base64_decode($j['data']));
