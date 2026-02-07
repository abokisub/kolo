<?php

$dir = __DIR__ . '/database/migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, '20') !== 0)
        continue; // All migrations

    $path = $dir . '/' . $file;
    $content = file_get_contents($path);
    $original = $content;

    $content = str_replace("->default('false')", "->default(false)", $content);
    $content = str_replace("->default('true')", "->default(true)", $content);

    if ($content !== $original) {
        echo "Fixed boolean defaults in $file\n";
        file_put_contents($path, $content);
    }
}
echo "Done.\n";
