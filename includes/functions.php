<?php
function listFiles($directory) {
    $files = [];
    $dir = scandir($_SERVER['DOCUMENT_ROOT'] . $directory);
    
    foreach ($dir as $file) {
        if ($file != '.' && $file != '..') {
            $path = $_SERVER['DOCUMENT_ROOT'] . $directory . '/' . $file;
            $files[] = [
                'name' => $file,
                'size' => formatFileSize(filesize($path)),
                'type' => filetype($path),
                'modified' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }
    
    return $files;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
} 