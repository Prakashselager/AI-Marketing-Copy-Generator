<?php
require_once 'database.php';

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Check if user has enough credits
function checkCredits($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT credits_remaining FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    return $user['credits_remaining'] > 0;
}

// Use one credit
function useCredit($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET credits_remaining = credits_remaining - 1 WHERE user_id = ? AND credits_remaining > 0");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// Add credits
function addCredits($user_id, $amount) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET credits_remaining = credits_remaining + ? WHERE user_id = ?");
    $stmt->bind_param("ii", $amount, $user_id);
    return $stmt->execute();
}

// Get user's copy generation history
function getUserHistory($user_id, $limit = 50) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT c.*, t.template_name, p.platform_name 
        FROM copy_generations c
        LEFT JOIN templates t ON c.template_id = t.template_id
        LEFT JOIN platforms p ON c.platform = p.platform_name
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    return $history;
}

// Log analytics
function logAnalytics($user_id, $generation_id, $action_type) {
    $db = Database::getInstance()->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO analytics (user_id, generation_id, action_type, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $user_id, $generation_id, $action_type, $ip, $user_agent);
    return $stmt->execute();
}

// Get platform list
function getPlatforms() {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT * FROM platforms WHERE is_active = 1");
    
    $platforms = [];
    while ($row = $result->fetch_assoc()) {
        $platforms[] = $row;
    }
    
    return $platforms;
}

// Get templates by platform
function getTemplatesByPlatform($platform) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM templates WHERE platform = ? AND is_active = 1");
    $stmt->bind_param("s", $platform);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    return $templates;
}

// Validate and sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format date
function formatDate($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}
?>