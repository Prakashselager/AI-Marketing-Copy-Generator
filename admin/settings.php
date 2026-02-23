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

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // General Settings
    if (isset($_POST['update_general'])) {
        $site_name = sanitizeInput($_POST['site_name']);
        $site_url = sanitizeInput($_POST['site_url']);
        $admin_email = sanitizeInput($_POST['admin_email']);
        $timezone = sanitizeInput($_POST['timezone']);
        
        // Update settings in database (you'll need a settings table)
        $updates = [
            'site_name' => $site_name,
            'site_url' => $site_url,
            'admin_email' => $admin_email,
            'timezone' => $timezone
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $message = "General settings updated successfully!";
    }
    
    // API Settings
    if (isset($_POST['update_api'])) {
        $openai_key = sanitizeInput($_POST['openai_key']);
        $openai_model = sanitizeInput($_POST['openai_model']);
        $max_tokens = intval($_POST['max_tokens']);
        $temperature = floatval($_POST['temperature']);
        
        $updates = [
            'openai_key' => $openai_key,
            'openai_model' => $openai_model,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, (string)$value, (string)$value);
            $stmt->execute();
        }
        
        $message = "API settings updated successfully!";
    }
    
    // Credit Settings
    if (isset($_POST['update_credits'])) {
        $default_credits = intval($_POST['default_credits']);
        $credits_per_generation = intval($_POST['credits_per_generation']);
        $enable_purchases = isset($_POST['enable_purchases']) ? 1 : 0;
        $credit_pack_1 = intval($_POST['credit_pack_1']);
        $credit_pack_2 = intval($_POST['credit_pack_2']);
        $credit_pack_3 = intval($_POST['credit_pack_3']);
        
        $updates = [
            'default_credits' => $default_credits,
            'credits_per_generation' => $credits_per_generation,
            'enable_purchases' => $enable_purchases,
            'credit_pack_1' => $credit_pack_1,
            'credit_pack_2' => $credit_pack_2,
            'credit_pack_3' => $credit_pack_3
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, (string)$value, (string)$value);
            $stmt->execute();
        }
        
        $message = "Credit settings updated successfully!";
    }
    
    // Email Settings
    if (isset($_POST['update_email'])) {
        $smtp_host = sanitizeInput($_POST['smtp_host']);
        $smtp_port = intval($_POST['smtp_port']);
        $smtp_username = sanitizeInput($_POST['smtp_username']);
        $smtp_password = sanitizeInput($_POST['smtp_password']);
        $smtp_encryption = sanitizeInput($_POST['smtp_encryption']);
        $from_email = sanitizeInput($_POST['from_email']);
        $from_name = sanitizeInput($_POST['from_name']);
        
        $updates = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_encryption' => $smtp_encryption,
            'from_email' => $from_email,
            'from_name' => $from_name
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, (string)$value, (string)$value);
            $stmt->execute();
        }
        
        $message = "Email settings updated successfully!";
    }
    
    // Security Settings
    if (isset($_POST['update_security'])) {
        $session_timeout = intval($_POST['session_timeout']);
        $max_login_attempts = intval($_POST['max_login_attempts']);
        $lockout_time = intval($_POST['lockout_time']);
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        $password_expiry = intval($_POST['password_expiry']);
        
        $updates = [
            'session_timeout' => $session_timeout,
            'max_login_attempts' => $max_login_attempts,
            'lockout_time' => $lockout_time,
            'two_factor_auth' => $two_factor_auth,
            'password_expiry' => $password_expiry
        ];
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, (string)$value, (string)$value);
            $stmt->execute();
        }
        
        $message = "Security settings updated successfully!";
    }
}

// Get current settings
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Load current settings
$site_name = getSetting($db, 'site_name', 'AI Marketing Copy Generator');
$site_url = getSetting($db, 'site_url', 'https://example.com');
$admin_email = getSetting($db, 'admin_email', 'admin@example.com');
$timezone = getSetting($db, 'timezone', 'UTC');

$openai_key = getSetting($db, 'openai_key', '');
$openai_model = getSetting($db, 'openai_model', 'gpt-4');
$max_tokens = getSetting($db, 'max_tokens', '2000');
$temperature = getSetting($db, 'temperature', '0.7');

$default_credits = getSetting($db, 'default_credits', '10');
$credits_per_generation = getSetting($db, 'credits_per_generation', '1');
$enable_purchases = getSetting($db, 'enable_purchases', '1');
$credit_pack_1 = getSetting($db, 'credit_pack_1', '100');
$credit_pack_2 = getSetting($db, 'credit_pack_2', '500');
$credit_pack_3 = getSetting($db, 'credit_pack_3', '1000');

$smtp_host = getSetting($db, 'smtp_host', 'smtp.gmail.com');
$smtp_port = getSetting($db, 'smtp_port', '587');
$smtp_username = getSetting($db, 'smtp_username', '');
$smtp_password = getSetting($db, 'smtp_password', '');
$smtp_encryption = getSetting($db, 'smtp_encryption', 'tls');
$from_email = getSetting($db, 'from_email', 'noreply@example.com');
$from_name = getSetting($db, 'from_name', 'AI Marketing');

$session_timeout = getSetting($db, 'session_timeout', '3600');
$max_login_attempts = getSetting($db, 'max_login_attempts', '5');
$lockout_time = getSetting($db, 'lockout_time', '900');
$two_factor_auth = getSetting($db, 'two_factor_auth', '0');
$password_expiry = getSetting($db, 'password_expiry', '90');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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

        /* Sidebar Styling */
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

        /* Settings Sections */
        .settings-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .settings-section h2 {
            font-size: 1.3rem;
            color: #1e293b;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .settings-section h2 i {
            color: #4f46e5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
        }

        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group label {
            margin-bottom: 0;
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

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
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

        .info-box {
            background: #eff6ff;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1e40af;
        }

        .info-box i {
            margin-right: 8px;
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
            
            .form-grid {
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
                    <a href="dashboard.php">
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
                    <a href="settings.php" class="active">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h1>
                <i class="fas fa-cog"></i>
                Settings
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

            <!-- General Settings -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-globe"></i>
                    General Settings
                </h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="url" name="site_url" value="<?php echo htmlspecialchars($site_url); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admin Email</label>
                            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Timezone</label>
                            <select name="timezone">
                                <option value="UTC" <?php echo $timezone == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo $timezone == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                <option value="America/Chicago" <?php echo $timezone == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                <option value="America/Denver" <?php echo $timezone == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                <option value="America/Los_Angeles" <?php echo $timezone == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                <option value="Europe/London" <?php echo $timezone == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                <option value="Europe/Paris" <?php echo $timezone == 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                <option value="Asia/Tokyo" <?php echo $timezone == 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                <option value="Asia/Singapore" <?php echo $timezone == 'Asia/Singapore' ? 'selected' : ''; ?>>Singapore</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_general" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </form>
            </div>

            <!-- API Settings -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-cloud"></i>
                    API Settings
                </h2>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    Enter your OpenAI API key to enable AI copy generation. Get your key from 
                    <a href="https://platform.openai.com/api-keys" target="_blank" style="color: #4f46e5;">OpenAI Dashboard</a>
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>OpenAI API Key</label>
                            <input type="password" name="openai_key" value="<?php echo htmlspecialchars($openai_key); ?>" placeholder="sk-...">
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <select name="openai_model">
                                <option value="gpt-4" <?php echo $openai_model == 'gpt-4' ? 'selected' : ''; ?>>GPT-4 (Best Quality)</option>
                                <option value="gpt-4-turbo" <?php echo $openai_model == 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo (Fast)</option>
                                <option value="gpt-3.5-turbo" <?php echo $openai_model == 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo (Economical)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Max Tokens</label>
                            <input type="number" name="max_tokens" value="<?php echo $max_tokens; ?>" min="100" max="4000">
                        </div>
                        <div class="form-group">
                            <label>Temperature (0-1)</label>
                            <input type="number" name="temperature" value="<?php echo $temperature; ?>" min="0" max="1" step="0.1">
                        </div>
                    </div>
                    <button type="submit" name="update_api" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save API Settings
                    </button>
                </form>
            </div>

            <!-- Credit Settings -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-coins"></i>
                    Credit Settings
                </h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Default Credits for New Users</label>
                            <input type="number" name="default_credits" value="<?php echo $default_credits; ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Credits per Generation</label>
                            <input type="number" name="credits_per_generation" value="<?php echo $credits_per_generation; ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="enable_purchases" id="enable_purchases" <?php echo $enable_purchases == '1' ? 'checked' : ''; ?>>
                                <label for="enable_purchases">Enable Credit Purchases</label>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="margin: 20px 0 15px; font-size: 1.1rem; color: #1e293b;">Credit Packs</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Small Pack (Credits)</label>
                            <input type="number" name="credit_pack_1" value="<?php echo $credit_pack_1; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Medium Pack (Credits)</label>
                            <input type="number" name="credit_pack_2" value="<?php echo $credit_pack_2; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Large Pack (Credits)</label>
                            <input type="number" name="credit_pack_3" value="<?php echo $credit_pack_3; ?>" min="0">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_credits" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Credit Settings
                    </button>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-envelope"></i>
                    Email Settings
                </h2>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    Configure SMTP settings for sending verification emails and notifications.
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo $smtp_port; ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>">
                        </div>
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?php echo $smtp_encryption == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $smtp_encryption == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $smtp_encryption == 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="from_email" value="<?php echo htmlspecialchars($from_email); ?>">
                        </div>
                        <div class="form-group">
                            <label>From Name</label>
                            <input type="text" name="from_name" value="<?php echo htmlspecialchars($from_name); ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_email" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Email Settings
                    </button>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Session Timeout (seconds)</label>
                            <input type="number" name="session_timeout" value="<?php echo $session_timeout; ?>" min="60">
                        </div>
                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" value="<?php echo $max_login_attempts; ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Lockout Time (seconds)</label>
                            <input type="number" name="lockout_time" value="<?php echo $lockout_time; ?>" min="60">
                        </div>
                        <div class="form-group">
                            <label>Password Expiry (days)</label>
                            <input type="number" name="password_expiry" value="<?php echo $password_expiry; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="two_factor_auth" id="two_factor_auth" <?php echo $two_factor_auth == '1' ? 'checked' : ''; ?>>
                                <label for="two_factor_auth">Enable Two-Factor Authentication</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_security" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Security Settings
                    </button>
                </form>
            </div>

            <!-- System Info -->
            <div class="settings-section">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    System Information
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>PHP Version</label>
                        <input type="text" value="<?php echo phpversion(); ?>" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Database</label>
                        <input type="text" value="MySQL" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Server Software</label>
                        <input type="text" value="<?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label>Upload Max Size</label>
                        <input type="text" value="<?php echo ini_get('upload_max_filesize'); ?>" readonly disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confirm before leaving with unsaved changes
        let formChanged = false;
        
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                formChanged = false;
            });
        });
    </script>
</body>
</html>