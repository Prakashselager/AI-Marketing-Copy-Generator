<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ai_marketing_copy');

// OpenAI API Configuration
define('OPENAI_API_KEY', 'your actual gemeni API key');
define('OPENAI_MODEL', 'gpt-3.5-turbo');

// Site configuration
define('SITE_NAME', 'AI Marketing Copy Generator');
define('SITE_URL', 'http://localhost/ai-marketing-copy-generator');
define('ADMIN_EMAIL', 'admin@example.com');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');
?>