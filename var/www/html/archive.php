<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_path = $_POST['source_path'] ?? '';
    $operation = $_POST['operation'] ?? '';
    
    if (!isValidPath($source_path)) {
        $response['message'] = 'Geçersiz dosya yolu.';
    } else {
        if ($operation === 'compress') {
            $archive_name = basename($source_path) . '.zip';
            if (createArchive($source_path, $archive_name)) {
                $response['success'] = true;
                $response['message'] = 'Arşiv oluşturuldu.';
            } else {
                $response['message'] = 'Arşiv oluşturulurken bir hata oluştu.';
            }
        } elseif ($operation === 'extract') {
            $dest_path = dirname($source_path) . '/' . pathinfo($source_path, PATHINFO_FILENAME);
            if (extractArchive($source_path, $dest_path)) {
                $response['success'] = true;
                $response['message'] = 'Arşiv çıkartıldı.';
            } else {
                $response['message'] = 'Arşiv çıkartılırken bir hata oluştu.';
            }
        } else {
            $response['message'] = 'Geçersiz işlem.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 