<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['generation_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing generation ID']);
    exit();
}

$user_id = getCurrentUserId();
$generation_id = intval($data['generation_id']);

$db = Database::getInstance()->getConnection();

// Check if generation belongs to user
$check = $db->prepare("SELECT generation_id FROM copy_generations WHERE generation_id = ? AND user_id = ?");
$check->bind_param("ii", $generation_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Generation not found']);
    exit();
}

// Check if already saved
$check_saved = $db->prepare("SELECT saved_id FROM saved_copies WHERE generation_id = ?");
$check_saved->bind_param("i", $generation_id);
$check_saved->execute();
$saved_result = $check_saved->get_result();

if ($saved_result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Already saved']);
    exit();
}

// Save to favorites
$stmt = $db->prepare("INSERT INTO saved_copies (generation_id, user_id) VALUES (?, ?)");
$stmt->bind_param("ii", $generation_id, $user_id);

if ($stmt->execute()) {
    // Update is_saved flag in copy_generations
    $update = $db->prepare("UPDATE copy_generations SET is_saved = TRUE WHERE generation_id = ?");
    $update->bind_param("i", $generation_id);
    $update->execute();
    
    logAnalytics($user_id, $generation_id, 'save');
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save']);
}
?>