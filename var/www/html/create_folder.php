<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder_name = $_POST['folder_name'] ?? '';
    $current_dir = $_POST['current_dir'] ?? '';
    
    if (!preg_match('/^[a-zA-Z0-9-_]+$/', $folder_name)) {
        $response['message'] = 'Geçersiz klasör adı.';
    } elseif (!isValidPath($current_dir)) {
        $response['message'] = 'Geçersiz dizin yolu.';
    } else {
        $new_folder_path = '/storage/' . trim($current_dir, '/') . '/' . $folder_name;
        
        if (file_exists($new_folder_path)) {
            $response['message'] = 'Bu isimde bir klasör zaten var.';
        } else {
            if (mkdir($new_folder_path, 0755, true)) {
                $response['success'] = true;
                $response['message'] = 'Klasör başarıyla oluşturuldu.';
            } else {
                $response['message'] = 'Klasör oluşturulurken bir hata oluştu.';
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 