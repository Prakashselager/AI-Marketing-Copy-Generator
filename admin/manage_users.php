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
$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $credits = intval($_POST['credits']);
        
        // Check if email already exists
        $check = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_role, credits_remaining, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $username, $email, $password, $role, $credits);
            
            if ($stmt->execute()) {
                $message = "User added successfully!";
            } else {
                $error = "Error adding user: " . $db->error;
            }
        }
    }
    
    if (isset($_POST['update_credits'])) {
        $user_id = intval($_POST['user_id']);
        $credits = intval($_POST['credits']);
        
        $stmt = $db->prepare("UPDATE users SET credits_remaining = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $credits, $user_id);
        
        if ($stmt->execute()) {
            $message = "Credits updated successfully!";
        } else {
            $error = "Error updating credits: " . $db->error;
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Don't allow deleting own account
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ? AND user_role != 'admin'");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
            } else {
                $error = "Error deleting user: " . $db->error;
            }
        }
    }
}

// Get all users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        }

        .admin-container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }

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

        .admin-content h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-content h1 i {
            color: #4f46e5;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .add-user-form {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .add-user-form h3 {
            font-size: 1.3rem;
            color: #1e293b;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-user-form h3 i {
            color: #4f46e5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-grid input,
        .form-grid select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-grid input:focus,
        .form-grid select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-small {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: #4f46e5;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-danger {
            background: #ef4444;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .user-table {
            width: 100%;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .user-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            padding: 18px 15px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }

        .user-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 14px;
        }

        .user-table tbody tr:hover {
            background: #f8fafc;
        }

        .user-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 6px 12px;
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

        .actions-form {
            display: inline-block;
            margin-right: 5px;
        }

        .credits-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credits-form input {
            width: 90px;
            padding: 8px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
        }

        .credits-form input:focus {
            outline: none;
            border-color: #4f46e5;
        }

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

        .current-user-badge {
            color: #64748b;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .current-user-badge i {
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
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="../index.php">
                    <i class="fas fa-robot" style="margin-right: 8px;"></i>
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
                    <a href="dashboard.php">
                        <i class="fas fa-chart-pie"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" class="active">
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
            </ul>
        </div>
        
        <div class="admin-content">
            <h1>
                <i class="fas fa-users"></i>
                Manage Users
            </h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="add-user-form">
                <h3>
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h3>
                <form method="POST" class="form-grid">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                    <input type="number" name="credits" placeholder="Credits" value="10" required>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add User
                    </button>
                </form>
            </div>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Credits</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['user_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['user_role']; ?>">
                                    <?php echo ucfirst($row['user_role']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="credits-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    <input type="number" name="credits" value="<?php echo $row['credits_remaining']; ?>" min="0">
                                    <button type="submit" name="update_credits" class="btn-small">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php if ($row['last_login']): ?>
                                    <?php echo date('M d, Y', strtotime($row['last_login'])); ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['user_role'] !== 'admin' && $row['user_id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="actions-form" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-small btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php elseif ($row['user_id'] == $_SESSION['user_id']): ?>
                                    <span class="current-user-badge">
                                        <i class="fas fa-user-circle"></i>
                                        Current User
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <br>No users found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>