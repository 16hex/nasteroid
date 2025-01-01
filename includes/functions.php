<?php
require_once 'db.php';

// İşletim sistemi kontrolü
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$base_path = $is_windows ? str_replace('\\', '/', __DIR__ . '/..') : '/var/www';
$storage_path = $base_path . '/storage';

// Debug için yol bilgisini yazdır
error_log("Storage path: " . $storage_path);

// Storage klasörünü oluştur
if (!is_dir($storage_path)) {
    mkdir($storage_path, 0777, true);
    error_log("Created storage directory: " . $storage_path);
}

function scanDirectory($dir) {
    global $storage_path;
    $files = [];
    $full_path = $storage_path;
    
    if (!empty($dir) && $dir !== '/') {
        $full_path .= '/' . trim($dir, '/');
    }
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $full_path . '/' . $item;
            $relative_path = trim($dir, '/') . '/' . $item;
            
            $files[] = [
                'name' => $item,
                'path' => $relative_path,
                'type' => is_dir($item_path) ? 'dir' : 'file',
                'size' => is_file($item_path) ? filesize($item_path) : 0,
                'modified' => filemtime($item_path)
            ];
        }
    }
    
    return $files;
}

// ... existing code ... 