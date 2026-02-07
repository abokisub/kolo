<?php

$dir = 'database/migrations';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Check if it's already an anonymous migration but missing the semicolon
    if (strpos($content, 'return new class extends Migration') !== false) {
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false && ($lastBracePos === strlen($content) - 1 || $content[$lastBracePos + 1] !== ';')) {
            $content = substr_replace($content, '};', $lastBracePos, 1);
            file_put_contents($file, $content);
            echo "Fixed syntax: " . basename($file) . "\n";
        }
        continue;
    }

    // Replace: class Name extends Migration
    // With: return new class extends Migration
    $pattern = '/class\s+\w+\s+extends\s+Migration/';
    $replacement = 'return new class extends Migration';

    $newContent = preg_replace($pattern, $replacement, $content);

    if ($newContent !== $content) {
        // Find the last closing brace and add semicolon
        $lastBracePos = strrpos($newContent, '}');
        if ($lastBracePos !== false) {
            $newContent = substr_replace($newContent, '};', $lastBracePos, 1);
        }
        file_put_contents($file, $newContent);
        echo "Converted: " . basename($file) . "\n";
    }
}
