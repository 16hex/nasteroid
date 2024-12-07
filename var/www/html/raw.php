<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$file = $_GET['file'] ?? '';

if (!empty($file) && isValidPath($file)) {
    $file_path = '/storage/' . trim($file, '/');
    if (file_exists($file_path) && is_file($file_path)) {
        $mime_type = mime_content_type($file_path);
        header('Content-Type: ' . $mime_type);
        readfile($file_path);
        exit;
    }
}

header('HTTP/1.1 404 Not Found');
echo 'Dosya bulunamadı.'; 