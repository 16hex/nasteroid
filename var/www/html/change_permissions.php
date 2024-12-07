<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = $_POST['path'] ?? '';
    $permissions = $_POST['permissions'] ?? '';
    
    if (!isValidPath($path)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } elseif (!preg_match('/^[0-7]{3}$/', $permissions)) {
        $response['message'] = 'Geçersiz izin değeri.';
    } else {
        if (changePermissions($path, $permissions)) {
            $response['success'] = true;
            $response['message'] = 'İzinler başarıyla değiştirildi.';
        } else {
            $response['message'] = 'İzinler değiştirilirken bir hata oluştu.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 