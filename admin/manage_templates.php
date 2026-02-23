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

// Handle template actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_template'])) {
        $name = sanitizeInput($_POST['template_name']);
        $description = sanitizeInput($_POST['template_description']);
        $platform = sanitizeInput($_POST['platform']);
        $structure = sanitizeInput($_POST['template_structure']);
        $limit = intval($_POST['character_limit']);
        
        // Check if template name already exists
        $check = $db->prepare("SELECT template_id FROM templates WHERE template_name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Template name already exists!";
        } else {
            $stmt = $db->prepare("INSERT INTO templates (template_name, template_description, platform, template_structure, character_limit, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssii", $name, $description, $platform, $structure, $limit, $user['user_id']);
            
            if ($stmt->execute()) {
                $message = "Template added successfully!";
            } else {
                $error = "Error adding template: " . $db->error;
            }
        }
    }
    
    if (isset($_POST['delete_template'])) {
        $id = intval($_POST['template_id']);
        
        // Check if template is being used
        $check = $db->prepare("SELECT COUNT(*) as count FROM generated_content WHERE template_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result();
        $usage = $result->fetch_assoc();
        
        if ($usage['count'] > 0) {
            $error = "Cannot delete template that has been used in generated content!";
        } else {
            $stmt = $db->prepare("DELETE FROM templates WHERE template_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Template deleted successfully!";
            } else {
                $error = "Error deleting template: " . $db->error;
            }
        }
    }
    
    if (isset($_POST['update_template'])) {
        $id = intval($_POST['template_id']);
        $name = sanitizeInput($_POST['template_name']);
        $description = sanitizeInput($_POST['template_description']);
        $platform = sanitizeInput($_POST['platform']);
        $structure = sanitizeInput($_POST['template_structure']);
        $limit = intval($_POST['character_limit']);
        
        $stmt = $db->prepare("UPDATE templates SET template_name = ?, template_description = ?, platform = ?, template_structure = ?, character_limit = ? WHERE template_id = ?");
        $stmt->bind_param("ssssii", $name, $description, $platform, $structure, $limit, $id);
        
        if ($stmt->execute()) {
            $message = "Template updated successfully!";
        } else {
            $error = "Error updating template: " . $db->error;
        }
    }
}

// Get all templates with platform info
$templates = $db->query("SELECT t.*, p.platform_name FROM templates t JOIN platforms p ON t.platform = p.platform_name ORDER BY t.created_at DESC");
$platforms = $db->query("SELECT * FROM platforms ORDER BY platform_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Templates - Admin Panel</title>
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

        /* Perfect Sidebar Styling - Matches Manage Users */
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

        /* Add Template Form */
        .add-template-form {
            background: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .add-template-form h3 {
            font-size: 1.3rem;
            color: #1e293b;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-template-form h3 i {
            color: #4f46e5;
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

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .variable-hint {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #475569;
            border-left: 4px solid #4f46e5;
        }

        .variable-hint i {
            color: #4f46e5;
            margin-right: 8px;
        }

        .variable-tag {
            background: #4f46e5;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin: 0 4px 4px 0;
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

        .btn-edit {
            background: #f59e0b;
        }

        .btn-edit:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-danger {
            background: #ef4444;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Template Grid */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .template-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #4f46e5;
        }

        .template-platform {
            background: #4f46e5;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .template-card h3 {
            font-size: 1.2rem;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .template-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .template-structure {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 13px;
            color: #334155;
            border: 1px solid #e2e8f0;
            white-space: pre-wrap;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .template-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .template-meta i {
            color: #4f46e5;
            margin-right: 5px;
        }

        .template-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
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

        /* Edit Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #1e293b;
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
            
            .template-grid {
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
                    <a href="manage_templates.php" class="active">
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
                <i class="fas fa-file-alt"></i>
                Manage Templates
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
            
            <!-- Add Template Form -->
            <div class="add-template-form">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Add New Template
                </h3>
                
                <div class="variable-hint">
                    <i class="fas fa-info-circle"></i>
                    Available variables: 
                    <span class="variable-tag">{product_name}</span>
                    <span class="variable-tag">{target_audience}</span>
                    <span class="variable-tag">{key_benefits}</span>
                    <span class="variable-tag">{tone}</span>
                    <span class="variable-tag">{cta}</span>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Template Name</label>
                        <input type="text" name="template_name" placeholder="e.g., Facebook Ad Copy" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="template_description" placeholder="Brief description of what this template does..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" required>
                            <option value="">Select Platform</option>
                            <?php 
                            if ($platforms && $platforms->num_rows > 0) {
                                while($p = $platforms->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $p['platform_name']; ?>"><?php echo $p['platform_name']; ?></option>
                            <?php 
                                endwhile;
                            } else {
                                // Default platforms if none in database
                            ?>
                                <option value="Facebook">Facebook</option>
                                <option value="Instagram">Instagram</option>
                                <option value="Twitter">Twitter</option>
                                <option value="LinkedIn">LinkedIn</option>
                                <option value="Google Ads">Google Ads</option>
                                <option value="Email">Email</option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Template Structure</label>
                        <textarea name="template_structure" rows="5" placeholder="Example: Introducing {product_name} - the perfect solution for {target_audience}! {key_benefits} ..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Character Limit</label>
                        <input type="number" name="character_limit" value="1000" min="50" max="5000" required>
                    </div>
                    
                    <button type="submit" name="add_template" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Template
                    </button>
                </form>
            </div>
            
            <!-- Templates Grid -->
            <div class="template-grid">
                <?php 
                if ($templates && $templates->num_rows > 0) {
                    while($t = $templates->fetch_assoc()): 
                ?>
                <div class="template-card">
                    <span class="template-platform">
                        <i class="fas fa-<?php 
                            echo strtolower($t['platform_name']) == 'facebook' ? 'facebook' : 
                                (strtolower($t['platform_name']) == 'instagram' ? 'instagram' : 
                                (strtolower($t['platform_name']) == 'twitter' ? 'twitter' : 
                                (strtolower($t['platform_name']) == 'linkedin' ? 'linkedin' : 
                                (strtolower($t['platform_name']) == 'google ads' ? 'google' : 'envelope')))); 
                        ?>"></i>
                        <?php echo htmlspecialchars($t['platform_name']); ?>
                    </span>
                    
                    <h3><?php echo htmlspecialchars($t['template_name']); ?></h3>
                    
                    <p class="template-description"><?php echo htmlspecialchars($t['template_description']); ?></p>
                    
                    <div class="template-structure">
                        <?php echo htmlspecialchars($t['template_structure']); ?>
                    </div>
                    
                    <div class="template-meta">
                        <span>
                            <i class="fas fa-text-height"></i>
                            Max: <?php echo $t['character_limit']; ?> chars
                        </span>
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="template-actions">
                        <button onclick="editTemplate(<?php echo $t['template_id']; ?>)" class="btn-small btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this template?');">
                            <input type="hidden" name="template_id" value="<?php echo $t['template_id']; ?>">
                            <button type="submit" name="delete_template" class="btn-small btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php 
                    endwhile;
                } else { 
                ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: white; border-radius: 16px; color: #64748b;">
                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <h3>No Templates Found</h3>
                    <p>Get started by adding your first template above.</p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal (can be implemented later) -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Template</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="template_id" id="edit_id">
                <div class="form-group">
                    <label>Template Name</label>
                    <input type="text" name="template_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="template_description" id="edit_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Platform</label>
                    <select name="platform" id="edit_platform" required>
                        <?php 
                        if ($platforms) {
                            mysqli_data_seek($platforms, 0);
                            while($p = $platforms->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $p['platform_name']; ?>"><?php echo $p['platform_name']; ?></option>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Template Structure</label>
                    <textarea name="template_structure" id="edit_structure" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label>Character Limit</label>
                    <input type="number" name="character_limit" id="edit_limit" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn" style="background: #e2e8f0;">Cancel</button>
                    <button type="submit" name="update_template" class="btn btn-primary">Update Template</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editTemplate(id) {
            // You would typically fetch template data via AJAX
            // For now, just show a message
            alert('Edit functionality - Template ID: ' + id);
            // Implement AJAX call to get template data and populate modal
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>