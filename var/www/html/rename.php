<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_path = $_POST['old_path'] ?? '';
    $new_name = $_POST['new_name'] ?? '';
    
    if (!isValidPath($old_path)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } elseif (!preg_match('/^[a-zA-Z0-9-_.]+$/', $new_name)) {
        $response['message'] = 'Geçersiz dosya/klasör adı.';
    } else {
        if (renameItem($old_path, $new_name)) {
            $response['success'] = true;
            $response['message'] = 'Yeniden adlandırma başarılı.';
        } else {
            $response['message'] = 'Yeniden adlandırma başarısız. Bu isimde bir dosya/klasör zaten var olabilir.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 