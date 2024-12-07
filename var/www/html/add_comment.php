<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filePath = $_POST['file_path'] ?? '';
    $versionNumber = $_POST['version_number'] ?? '';
    $lineNumber = $_POST['line_number'] ?? null;
    $comment = $_POST['comment'] ?? '';
    
    if (!isValidPath($filePath) || empty($comment)) {
        $response['message'] = 'Geçersiz parametreler.';
    } else {
        if (addVersionComment($filePath, $versionNumber, $lineNumber, $comment)) {
            $response['success'] = true;
            $response['message'] = 'Yorum eklendi.';
        } else {
            $response['message'] = 'Yorum eklenirken bir hata oluştu.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 