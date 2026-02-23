<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit();
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['user_role'];
        
        // Update last login
        $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update->bind_param("i", $user['user_id']);
        $update->execute();
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'credits' => $user['credits_remaining']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'User not found']);
}
?>