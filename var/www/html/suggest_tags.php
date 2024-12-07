<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$filePath = $_GET['file'] ?? '';
$suggestions = [];

if (!empty($filePath) && isValidPath($filePath)) {
    $suggestions = suggestTags($filePath);
}

header('Content-Type: application/json');
echo json_encode($suggestions); 