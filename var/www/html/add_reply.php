<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentId = $_POST['parent_id'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if (empty($parentId) || empty($comment)) {
        $response['message'] = 'Geçersiz parametreler.';
    } else {
        if (addCommentReply($parentId, $comment)) {
            $response['success'] = true;
            $response['message'] = 'Yanıt eklendi.';
        } else {
            $response['message'] = 'Yanıt eklenirken bir hata oluştu.';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response); 