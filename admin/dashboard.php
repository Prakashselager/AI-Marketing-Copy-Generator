<?php
require_once '../includes/functions.php';
requireLogin();

// Check if user is admin
$user = getCurrentUser();
if ($user['user_role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [];

// Total users
$result = $db->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total generations
$result = $db->query("SELECT COUNT(*) as count FROM copy_generations");
$stats['total_generations'] = $result->fetch_assoc()['count'];

// Total saved copies
$result = $db->query("SELECT COUNT(*) as count FROM saved_copies");
$stats['total_saved'] = $result->fetch_assoc()['count'];

// Total credits used
$result = $db->query("SELECT SUM(tokens_used) as total FROM copy_generations");
$stats['total_tokens'] = $result->fetch_assoc()['total'] ?? 0;

// Get counts by platform
$platform_stats = $db->query("
    SELECT platform, COUNT(*) as count 
    FROM copy_generations 
    GROUP BY platform 
    ORDER BY count DESC 
    LIMIT 5
");

// Get today's activity
$today = date('Y-m-d');
$result = $db->query("SELECT COUNT(*) as count FROM copy_generations WHERE DATE(created_at) = '$today'");
$stats['today_generations'] = $result->fetch_assoc()['count'];

// Recent users
$recent_users = $db->query("SELECT user_id, username, email, created_at, credits_remaining, user_role FROM users ORDER BY created_at DESC LIMIT 5");

// Recent generations
$recent_generations = $db->query("
    SELECT c.*, u.username 
    FROM copy_generations c 
    JOIN users u ON c.user_id = u.user_id 
    ORDER BY c.created_at DESC 
    LIMIT 5
");

// Get template usage stats
$template_stats = $db->query("
    SELECT t.template_name, COUNT(c.generation_id) as usage_count 
    FROM templates t 
    LEFT JOIN copy_generations c ON t.template_id = c.template_id 
    GROUP BY t.template_id 
    ORDER BY usage_count DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo defined('SITE_NAME') ? SITE_NAME : 'AI Marketing'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .nav-brand a {
            color: #4f46e5;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }

        /* Sidebar Styling - Matching other pages */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e2937 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: calc(100vh - 70px);
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .admin-sidebar h3 {
            padding: 25px 20px;
            font-size: 1.3rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-sidebar h3 i {
            color: #4f46e5;
        }

        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }

        .admin-sidebar ul li {
            margin: 5px 0;
        }

        .admin-sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 15px;
        }

        .admin-sidebar ul li a:hover,
        .admin-sidebar ul li a.active {
            background: rgba(79, 70, 229, 0.2);
            color: white;
            border-left-color: #4f46e5;
        }

        .admin-sidebar ul li a i {
            margin-right: 15px;
            width: 20px;
            font-size: 18px;
        }

        .admin-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: #f8fafc;
            min-height: calc(100vh - 70px);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            font-size: 2rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-header h1 i {
            color: #4f46e5;
        }

        .welcome-badge {
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            color: #475569;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .welcome-badge i {
            color: #4f46e5;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #4f46e5;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.users {
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }

        .stat-icon.generations {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-icon.saved {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-icon.tokens {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-icon.today {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            margin-top: 5px;
        }

        /* Recent Sections */
        .recent-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header h2 {
            font-size: 1.3rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: #4f46e5;
        }

        .view-all {
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            color: #4338ca;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 15px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-admin {
            background: #4f46e5;
            color: white;
        }

        .badge-user {
            background: #e2e8f0;
            color: #475569;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .saved-indicator {
            color: #10b981;
            font-size: 18px;
        }

        .unsaved-indicator {
            color: #94a3b8;
            font-size: 18px;
        }

        /* Platform Stats */
        .platform-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .platform-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .platform-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .platform-count {
            color: #4f46e5;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Logout Button */
        .logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #4f46e5;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .admin-content {
                margin-left: 0;
            }
            
            .admin-sidebar.active {
                width: 280px;
                transform: translateX(0);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="../index.php">
                    <i class="fas fa-robot"></i>
                    <?php echo defined('SITE_NAME') ? SITE_NAME : 'AI Marketing'; ?>
                </a>
            </div>
            <div class="nav-menu">
                <button onclick="location.href='../logout.php'" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>
                <i class="fas fa-cog"></i>
                Admin Menu
            </h3>
            <ul>
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-chart-pie"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="manage_templates.php">
                        <i class="fas fa-file-alt"></i>
                        Manage Templates
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li>
                    <a href="../generator.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Generator
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>
                    <i class="fas fa-chart-pie"></i>
                    Dashboard
                </h1>
                <div class="welcome-badge">
                    <i class="fas fa-user-circle"></i>
                    Welcome, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon generations">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_generations']; ?></div>
                        <div class="stat-label">Total Generations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon saved">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_saved']; ?></div>
                        <div class="stat-label">Saved Copies</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon tokens">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_tokens']); ?></div>
                        <div class="stat-label">Total Tokens Used</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['today_generations']; ?></div>
                        <div class="stat-label">Today's Generations</div>
                    </div>
                </div>
            </div>

            <!-- Platform Statistics -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-chart-bar"></i>
                        Platform Usage
                    </h2>
                </div>
                <div class="platform-stats">
                    <?php 
                    if ($platform_stats && $platform_stats->num_rows > 0) {
                        while ($platform = $platform_stats->fetch_assoc()): 
                    ?>
                        <div class="platform-item">
                            <div class="platform-name">
                                <i class="fab fa-<?php echo strtolower($platform['platform']); ?>"></i>
                                <?php echo htmlspecialchars($platform['platform']); ?>
                            </div>
                            <div class="platform-count"><?php echo $platform['count']; ?></div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<p style="grid-column: 1/-1; text-align: center; color: #64748b;">No platform data available</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-user-plus"></i>
                        Recent Users
                    </h2>
                    <a href="manage_users.php" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Credits</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                <?php while ($recent_user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $recent_user['user_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($recent_user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($recent_user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $recent_user['user_role']; ?>">
                                            <?php echo ucfirst($recent_user['user_role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $recent_user['credits_remaining']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($recent_user['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">No recent users</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Generations -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-history"></i>
                        Recent Generations
                    </h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Product</th>
                                <th>Platform</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_generations && $recent_generations->num_rows > 0): ?>
                                <?php while ($gen = $recent_generations->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $gen['generation_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($gen['username']); ?></td>
                                    <td><?php echo htmlspecialchars($gen['product_name']); ?></td>
                                    <td>
                                        <i class="fab fa-<?php echo strtolower($gen['platform']); ?>"></i>
                                        <?php echo $gen['platform']; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($gen['created_at'])); ?></td>
                                    <td>
                                        <?php if ($gen['is_saved']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Saved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">No recent generations</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Template Usage Stats -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-chart-simple"></i>
                        Most Used Templates
                    </h2>
                    <a href="manage_templates.php" class="view-all">
                        Manage Templates <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Usage Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($template_stats && $template_stats->num_rows > 0): ?>
                                <?php while ($tmpl = $template_stats->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tmpl['template_name']); ?></td>
                                    <td>
                                        <strong><?php echo $tmpl['usage_count']; ?></strong> times
                                    </td>
                                    <td>
                                        <a href="manage_templates.php" class="btn-small" style="text-decoration: none;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #64748b;">No template data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>