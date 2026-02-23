<?php
require_once 'includes/functions.php';
requireLogin();

$user = getCurrentUser();
$history = getUserHistory($user['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="generator.php">Generator</a></li>
                    <li><span class="credits-badge">Credits: <?php echo $user['credits_remaining']; ?></span></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="history-page">
        <div class="container">
            <h1>Your Generation History</h1>
            
            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <p>No generations yet. <a href="generator.php">Create your first marketing copy!</a></p>
                </div>
            <?php else: ?>
                <div class="history-filters">
                    <input type="text" id="search-history" placeholder="Search history..." class="form-control">
                    <select id="filter-platform" class="form-control">
                        <option value="">All Platforms</option>
                        <?php 
                        $platforms = array_unique(array_column($history, 'platform'));
                        foreach ($platforms as $platform): 
                        ?>
                            <option value="<?php echo $platform; ?>"><?php echo $platform; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="history-list">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item" data-platform="<?php echo $item['platform']; ?>">
                            <div class="history-header">
                                <span class="platform-badge"><?php echo $item['platform']; ?></span>
                                <span class="history-date"><?php echo formatDate($item['created_at']); ?></span>
                            </div>
                            
                            <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            
                            <div class="history-details">
                                <p><strong>Target Audience:</strong> <?php echo htmlspecialchars($item['target_audience']); ?></p>
                                <p><strong>Key Benefits:</strong> <?php echo htmlspecialchars($item['key_benefits']); ?></p>
                                <p><strong>Tone:</strong> <?php echo $item['tone_style'] ?? 'Not specified'; ?></p>
                            </div>
                            
                            <div class="history-preview">
                                <?php echo nl2br(htmlspecialchars(substr($item['generated_copy'], 0, 200))); ?>...
                            </div>
                            
                            <div class="history-actions">
                                <a href="view_copy.php?id=<?php echo $item['generation_id']; ?>" class="btn btn-small">View Full</a>
                                <button onclick="copyText(<?php echo $item['generation_id']; ?>)" class="btn btn-small btn-secondary">Copy</button>
                                <?php if (!$item['is_saved']): ?>
                                    <button onclick="saveCopy(<?php echo $item['generation_id']; ?>)" class="btn btn-small btn-primary">Save</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    // Search and filter functionality
    document.getElementById('search-history')?.addEventListener('keyup', filterHistory);
    document.getElementById('filter-platform')?.addEventListener('change', filterHistory);
    
    function filterHistory() {
        const searchTerm = document.getElementById('search-history').value.toLowerCase();
        const platform = document.getElementById('filter-platform').value;
        const items = document.querySelectorAll('.history-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matchesSearch = text.includes(searchTerm);
            const matchesPlatform = !platform || item.dataset.platform === platform;
            
            if (matchesSearch && matchesPlatform) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function copyText(id) {
        fetch(`api/get_copy.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    navigator.clipboard.writeText(data.copy).then(() => {
                        alert('Copy copied to clipboard!');
                    });
                }
            });
    }
    
    function saveCopy(id) {
        fetch('api/save_copy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                generation_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Copy saved to favorites!');
                location.reload();
            } else {
                alert('Error saving copy: ' + data.error);
            }
        });
    }
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>