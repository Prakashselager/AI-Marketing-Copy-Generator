<?php
require_once 'includes/functions.php';
require_once 'includes/openai.php';
requireLogin();

$user = getCurrentUser();
$platforms = getPlatforms();
$error = '';
$generated_copy = '';
$generation_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    if (!checkCredits($user['user_id'])) {
        $error = 'Insufficient credits. Please purchase more credits.';
    } else {
        $params = [
            'platform' => sanitizeInput($_POST['platform']),
            'product_name' => sanitizeInput($_POST['product_name']),
            'target_audience' => sanitizeInput($_POST['target_audience']),
            'key_benefits' => sanitizeInput($_POST['key_benefits']),
            'tone_style' => sanitizeInput($_POST['tone_style']),
            'template_id' => !empty($_POST['template_id']) ? intval($_POST['template_id']) : null
        ];
        
        $openai = new OpenAIClient();
        $response = $openai->generateMarketingCopy($params);
        $result = $openai->extractGeneratedCopy($response);
        
        if (isset($result['success'])) {
            $generated_copy = $result['copy'];
            $tokens_used = $result['tokens'];
            
            // Save to database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO copy_generations 
                (user_id, template_id, platform, product_name, target_audience, key_benefits, tone_style, generated_copy, tokens_used)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iissssssi",
                $user['user_id'],
                $params['template_id'],
                $params['platform'],
                $params['product_name'],
                $params['target_audience'],
                $params['key_benefits'],
                $params['tone_style'],
                $generated_copy,
                $tokens_used
            );
            $stmt->execute();
            $generation_id = Database::getInstance()->lastInsertId();
            
            // Use one credit
            useCredit($user['user_id']);
            
            // Log analytics
            logAnalytics($user['user_id'], $generation_id, 'generate');
        } else {
            $error = 'Failed to generate copy: ' . $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Copy - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="history.php">History</a></li>
                    <li><span class="credits-badge">Credits: <?php echo $user['credits_remaining']; ?></span></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="generator">
        <div class="container">
            <h1>AI Marketing Copy Generator</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="generator-grid">
                <div class="input-section">
                    <form method="POST" action="" id="generator-form">
                        <div class="form-group">
                            <label for="platform">Platform *</label>
                            <select id="platform" name="platform" required>
                                <option value="">Select Platform</option>
                                <?php foreach ($platforms as $platform): ?>
                                    <option value="<?php echo $platform['platform_name']; ?>">
                                        <?php echo $platform['platform_name']; ?> (Max: <?php echo $platform['max_characters']; ?> chars)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_name">Product/Service Name *</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="target_audience">Target Audience *</label>
                            <textarea id="target_audience" name="target_audience" rows="3" required></textarea>
                            <small>Describe who you want to reach (e.g., "young professionals aged 25-35 interested in fitness")</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="key_benefits">Key Benefits *</label>
                            <textarea id="key_benefits" name="key_benefits" rows="3" required></textarea>
                            <small>List the main benefits, separated by commas</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="tone_style">Tone/Style</label>
                            <select id="tone_style" name="tone_style">
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="enthusiastic">Enthusiastic</option>
                                <option value="humorous">Humorous</option>
                                <option value="luxury">Luxury/High-end</option>
                                <option value="urgent">Urgent/Limited Time</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="generate" class="btn btn-primary btn-large btn-block">
                            Generate Marketing Copy
                        </button>
                    </form>
                </div>
                
                <div class="output-section">
                    <h2>Generated Copy</h2>
                    
                    <?php if ($generated_copy): ?>
                        <div class="copy-result">
                            <div class="copy-content">
                                <?php echo nl2br(htmlspecialchars($generated_copy)); ?>
                            </div>
                            
                            <div class="copy-actions">
                                <button onclick="copyToClipboard()" class="btn btn-secondary">Copy to Clipboard</button>
                                <button onclick="saveCopy(<?php echo $generation_id; ?>)" class="btn btn-primary">Save to Favorites</button>
                                <a href="generator.php" class="btn btn-outline">Generate New</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="placeholder">
                            <p>Your generated copy will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    function copyToClipboard() {
        const copyText = document.querySelector('.copy-content').innerText;
        navigator.clipboard.writeText(copyText).then(() => {
            alert('Copy copied to clipboard!');
        });
    }
    
    function saveCopy(generationId) {
        fetch('api/save_copy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                generation_id: generationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Copy saved to favorites!');
            } else {
                alert('Error saving copy: ' + data.error);
            }
        });
    }
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>