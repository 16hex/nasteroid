<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filePath = $_POST['file_path'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if (!isValidPath($filePath)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } else {
        if (createVersion($filePath, $comment)) {
            $response['success'] = true;
            $response['message'] = 'Yeni sürüm oluşturuldu.';
        } else {
            $response['message'] = 'Sürüm oluşturulurken bir hata oluştu.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 