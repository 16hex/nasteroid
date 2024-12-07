<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_path = $_POST['source_path'] ?? '';
    $dest_path = $_POST['dest_path'] ?? '';
    $operation = $_POST['operation'] ?? '';
    
    if (!isValidPath($source_path) || !isValidPath($dest_path)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } else {
        if ($operation === 'copy') {
            if (copyItem($source_path, $dest_path)) {
                $response['success'] = true;
                $response['message'] = 'Kopyalama işlemi başarılı.';
            } else {
                $response['message'] = 'Kopyalama işlemi başarısız.';
            }
        } elseif ($operation === 'move') {
            if (moveItem($source_path, $dest_path)) {
                $response['success'] = true;
                $response['message'] = 'Taşıma işlemi başarılı.';
            } else {
                $response['message'] = 'Taşıma işlemi başarısız.';
            }
        } else {
            $response['message'] = 'Geçersiz işlem.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 