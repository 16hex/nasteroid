<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$query = $_GET['q'] ?? '';
$tags = [];

if (!empty($query)) {
    $tags = searchTags($query);
}

header('Content-Type: application/json');
echo json_encode($tags); 