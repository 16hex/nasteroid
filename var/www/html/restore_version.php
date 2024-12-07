<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filePath = $_POST['file_path'] ?? '';
    $versionNumber = $_POST['version_number'] ?? '';
    
    if (!isValidPath($filePath)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } else {
        if (restoreVersion($filePath, $versionNumber)) {
            $response['success'] = true;
            $response['message'] = 'Sürüm başarıyla geri yüklendi.';
        } else {
            $response['message'] = 'Sürüm geri yüklenirken bir hata oluştu.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 