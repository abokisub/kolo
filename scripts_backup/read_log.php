<?php
$file = 'storage/logs/laravel.log';
$bytes = 5000; // Read last 5KB

if (!file_exists($file)) {
    die("File not found");
}

$fp = fopen($file, 'r');
fseek($fp, -$bytes, SEEK_END);
$content = fread($fp, $bytes);
fclose($fp);

echo $content;
