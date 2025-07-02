<?php
$uploadDir = __DIR__ . '/uploads/';
$timeLimit = 60; // 1 minute

if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '*');
    $now = time();

    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $timeLimit) {
            unlink($file);
        }
    }
}
echo 'Cleanup completed.';
?>