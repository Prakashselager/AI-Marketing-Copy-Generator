<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Create Perfect Social Media Ads</title>
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
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php" class="btn btn-primary">Dashboard</a></li>
                        <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline">Login</a></li>
                        <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Generate Perfect Marketing Copy with AI</h1>
                <p>Create engaging social media ads in seconds. Save time and boost your conversions with AI-powered copywriting.</p>
                <div class="hero-buttons">
                    <a href="generator.php" class="btn btn-primary btn-large">Start Generating</a>
                    <a href="#how-it-works" class="btn btn-outline btn-large">See How It Works</a>
                </div>
            </div>
        </div>
    </header>

    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Powerful Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>AI-Powered</h3>
                    <p>Advanced AI generates high-converting copy tailored to your brand</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Multi-Platform</h3>
                    <p>Optimized for Facebook, Instagram, Twitter, LinkedIn, and TikTok</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>Custom Templates</h3>
                    <p>Choose from various templates or create your own</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics</h3>
                    <p>Track performance and optimize your copy strategy</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Enter Your Details</h3>
                    <p>Tell us about your product, target audience, and key benefits</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Choose Platform & Tone</h3>
                    <p>Select the social media platform and desired tone of voice</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Generate Copy</h3>
                    <p>Our AI creates multiple variations of engaging copy for you</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Save & Export</h3>
                    <p>Save your favorites, edit, or export directly to your social platforms</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>