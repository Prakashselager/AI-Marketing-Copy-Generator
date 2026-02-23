<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../includes/openai.php';

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

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

$user = getCurrentUser();

if (!checkCredits($user['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Insufficient credits']);
    exit();
}

$openai = new OpenAIClient();
$response = $openai->generateMarketingCopy($data);
$result = $openai->extractGeneratedCopy($response);

if (isset($result['success'])) {
    // Save to database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO copy_generations 
        (user_id, platform, product_name, target_audience, key_benefits, tone_style, generated_copy, tokens_used)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "issssssi",
        $user['user_id'],
        $data['platform'],
        $data['product_name'],
        $data['target_audience'],
        $data['key_benefits'],
        $data['tone_style'],
        $result['copy'],
        $result['tokens']
    );
    
    if ($stmt->execute()) {
        $generation_id = Database::getInstance()->lastInsertId();
        useCredit($user['user_id']);
        logAnalytics($user['user_id'], $generation_id, 'generate');
        
        echo json_encode([
            'success' => true,
            'copy' => $result['copy'],
            'generation_id' => $generation_id,
            'credits_remaining' => $user['credits_remaining'] - 1
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save to database']);
    }
} else {
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
?>