<?php
require_once 'includes/functions.php';
session_start();

// Kimlik doğrulama kontrolü
checkAuth();

if (isset($_GET['file'])) {
    $file_path = '/storage/' . trim($_GET['file'], '/');
    
    // Güvenlik kontrolleri
    if (!isValidPath($_GET['file'])) {
        die('Geçersiz dosya yolu.');
    }
    
    downloadFile($file_path);
} elseif (isset($_GET['code'])) {
    $code = $_GET['code'];
    $share = getShareInfo($code);
    
    if (!$share) {
        die('Geçersiz veya süresi dolmuş paylaşım bağlantısı.');
    }
    
    $file_path = '/storage/' . trim($share['file_path'], '/');
    downloadFile($file_path);
}

// Dosya bulunamadı
header('Location: index.php');
exit;

function downloadFile($file_path) {
    if (file_exists($file_path) && is_file($file_path)) {
        $file_name = basename($file_path);
        
        // Dosya tipini kontrol et
        if (!isAllowedFileType($file_name)) {
            die('Bu dosya türüne izin verilmiyor.');
        }
        
        // Dosya indirme başlıkları
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        
        // Dosyayı oku ve gönder
        readfile($file_path);
        exit;
    }
}