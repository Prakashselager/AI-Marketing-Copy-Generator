<?php
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = Database::getInstance()->getConnection();

// Get user stats
$stats = [];
$queries = [
    'total_generations' => "SELECT COUNT(*) as count FROM copy_generations WHERE user_id = ?",
    'saved_copies' => "SELECT COUNT(*) as count FROM saved_copies WHERE user_id = ?",
    'platforms_used' => "SELECT COUNT(DISTINCT platform) as count FROM copy_generations WHERE user_id = ?",
    'recent_activity' => "SELECT c.*, p.platform_name FROM copy_generations c LEFT JOIN platforms p ON c.platform = p.platform_name WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 5"
];

foreach ($queries as $key => $sql) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats[$key] = $result->fetch_assoc()['count'] ?? 0;
}

// Get recent activity
$stmt = $db->prepare($queries['recent_activity']);
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$recent = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php"><?php echo SITE_NAME; ?></a>
            </div>
            <div class="nav-menu">
                <ul>
                    <li><a href="generator.php">Generator</a></li>
                    <li><a href="history.php">History</a></li>
                    <li><span class="credits-badge">Credits: <?php echo $user['credits_remaining']; ?></span></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="dashboard">
        <div class="container">
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h1>
                <p>Ready to create some amazing marketing copy?</p>
                <a href="generator.php" class="btn btn-primary btn-large">Generate New Copy</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_generations']; ?></div>
                    <div class="stat-label">Total Generations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['saved_copies']; ?></div>
                    <div class="stat-label">Saved Copies</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['platforms_used']; ?></div>
                    <div class="stat-label">Platforms Used</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $user['credits_remaining']; ?></div>
                    <div class="stat-label">Credits Left</div>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <?php if ($recent->num_rows > 0): ?>
                    <div class="activity-list">
                        <?php while ($item = $recent->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon">📝</div>
                                <div class="activity-details">
                                    <div class="activity-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="activity-meta">
                                        <span>Platform: <?php echo $item['platform']; ?></span>
                                        <span>Date: <?php echo formatDate($item['created_at']); ?></span>
                                    </div>
                                </div>
                                <a href="view_copy.php?id=<?php echo $item['generation_id']; ?>" class="btn btn-small">View</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="no-activity">No activity yet. <a href="generator.php">Generate your first copy!</a></p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>