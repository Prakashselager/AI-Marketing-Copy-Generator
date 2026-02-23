-- Create database
CREATE DATABASE IF NOT EXISTS ai_marketing_copy;
USE ai_marketing_copy;

-- Table 1: Users
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    user_role ENUM('admin', 'user') DEFAULT 'user',
    credits_remaining INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Table 2: Copy Generations
CREATE TABLE copy_generations (
    generation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    template_id INT,
    platform VARCHAR(50),
    product_name VARCHAR(255),
    target_audience TEXT,
    key_benefits TEXT,
    tone_style VARCHAR(50),
    generated_copy TEXT,
    character_count INT,
    ai_model VARCHAR(50),
    tokens_used INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_saved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table 3: Templates
CREATE TABLE templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100),
    template_description TEXT,
    platform VARCHAR(50),
    template_structure TEXT,
    character_limit INT,
    is_premium BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Table 4: Platforms
CREATE TABLE platforms (
    platform_id INT PRIMARY KEY AUTO_INCREMENT,
    platform_name VARCHAR(50) UNIQUE,
    platform_icon VARCHAR(100),
    max_characters INT,
    recommended_image_size VARCHAR(50),
    hashtag_limit INT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Table 5: SavedCopies (Favorites)
CREATE TABLE saved_copies (
    saved_id INT PRIMARY KEY AUTO_INCREMENT,
    generation_id INT UNIQUE,
    user_id INT,
    notes TEXT,
    folder_name VARCHAR(100) DEFAULT 'General',
    is_public BOOLEAN DEFAULT FALSE,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generation_id) REFERENCES copy_generations(generation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table 6: Analytics
CREATE TABLE analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    generation_id INT,
    action_type ENUM('view', 'edit', 'copy', 'download', 'share') NOT NULL,
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (generation_id) REFERENCES copy_generations(generation_id) ON DELETE CASCADE
);

-- Insert default data
INSERT INTO platforms (platform_name, max_characters, recommended_image_size, hashtag_limit) VALUES
('Facebook', 63206, '1200x630', 0),
('Instagram', 2200, '1080x1080', 30),
('Twitter', 280, '1600x900', 0),
('LinkedIn', 3000, '1200x627', 0),
('TikTok', 150, '1080x1920', 5);

INSERT INTO templates (template_name, template_description, platform, template_structure, character_limit, created_by) VALUES
('Product Launch', 'Generate excitement for new product', 'Instagram', '🚀 Exciting news! {product_name} is here!\n\n✨ {key_benefits}\n\n🎯 Perfect for {target_audience}\n\n👇 Link in bio to shop now!', 2200, 1),
('Special Offer', 'Create urgency for limited-time offers', 'Facebook', '🔥 SPECIAL OFFER ALERT!\n\nGet {product_name} at an amazing price!\n\n✓ {key_benefits}\n\n⏰ Limited time only!\n\nShop now: {link}', 63206, 1),
('Professional Update', 'Share professional achievements', 'LinkedIn', "I'm excited to share that {product_name} is transforming how {target_audience} work!\n\nKey benefits:\n✓ {key_benefits}\n\nLearn more about how we're innovating in this space.\n\n#Innovation #Technology", 3000, 1);

-- Create admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, user_role, credits_remaining) VALUES
('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin', 999999);