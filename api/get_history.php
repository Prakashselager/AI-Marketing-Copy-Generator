<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = getCurrentUserId();
$history = getUserHistory($user_id);

echo json_encode([
    'success' => true,
    'history' => $history
]);
?>