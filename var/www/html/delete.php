<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = $_POST['path'] ?? '';
    
    if (!isValidPath($path)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } else {
        $full_path = '/storage/' . trim($path, '/');
        
        if (is_dir($full_path)) {
            if (deleteDirectory($full_path)) {
                $response['success'] = true;
                $response['message'] = 'Klasör başarıyla silindi.';
            } else {
                $response['message'] = 'Klasör silinirken bir hata oluştu.';
            }
        } elseif (is_file($full_path)) {
            if (unlink($full_path)) {
                $response['success'] = true;
                $response['message'] = 'Dosya başarıyla silindi.';
            } else {
                $response['message'] = 'Dosya silinirken bir hata oluştu.';
            }
        } else {
            $response['message'] = 'Dosya veya klasör bulunamadı.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 