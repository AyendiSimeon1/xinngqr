USE xinng;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS short_link_clicks;
DROP TABLE IF EXISTS short_links;
DROP TABLE IF EXISTS qr_code_scans;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS link_clicks;
DROP TABLE IF EXISTS page_views;
DROP TABLE IF EXISTS page_blocks;
DROP TABLE IF EXISTS page_theme_settings;
DROP TABLE IF EXISTS social_links;
DROP TABLE IF EXISTS links;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS credit_transactions;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS password_reset;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    credit_balance INT NOT NULL DEFAULT 1000,
    credits_purchased_total INT NOT NULL DEFAULT 0,
    credits_used_total INT NOT NULL DEFAULT 0,
    avatar_url TEXT NULL,
    phone VARCHAR(50) NULL,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    status ENUM('active','suspended','deleted') DEFAULT 'active',
    role ENUM('user','admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_status (status),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    page_type VARCHAR(32) NOT NULL DEFAULT 'creator',
    corporate_metadata JSON NULL,
    slug VARCHAR(80) NULL,
    title VARCHAR(150) NOT NULL DEFAULT '',
    bio TEXT NULL,
    description VARCHAR(255) NULL,
    profile_image_url TEXT NULL,
    profile_image_path TEXT NULL,
    cover_image_url TEXT NULL,
    theme VARCHAR(80) DEFAULT 'default',
    layout VARCHAR(80) DEFAULT 'simple',
    font VARCHAR(80) DEFAULT 'system',
    title_color VARCHAR(20) DEFAULT '#26282C',
    description_color VARCHAR(20) DEFAULT '#26282C',
    header_mode VARCHAR(20) DEFAULT 'color',
    header_color VARCHAR(20) DEFAULT '#26282C',
    header_gradient_start VARCHAR(20) DEFAULT '#26282C',
    header_gradient_end VARCHAR(20) DEFAULT '#0A9994',
    header_image_path TEXT NULL,
    header_fit VARCHAR(20) DEFAULT 'cover',
    background_mode VARCHAR(20) DEFAULT 'color',
    background_color VARCHAR(20) DEFAULT '#FFFAF6',
    background_gradient_start VARCHAR(20) DEFAULT '#FFFAF6',
    background_gradient_end VARCHAR(20) DEFAULT '#FFFFFF',
    background_image_path TEXT NULL,
    social_icon_style VARCHAR(20) DEFAULT 'original',
    social_placement VARCHAR(20) DEFAULT 'top',
    block_shape VARCHAR(40) DEFAULT 'rounded',
    block_shadow VARCHAR(40) DEFAULT 'soft',
    block_color VARCHAR(20) DEFAULT '#0A9994',
    block_text_color VARCHAR(20) DEFAULT '#FFFAF6',
    hide_xinng_logo BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'published',
    published_at DATETIME NULL,
    seo_title VARCHAR(180) NULL,
    seo_description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_pages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pages_slug (slug),
    INDEX idx_pages_user_id (user_id),
    INDEX idx_pages_slug (slug),
    INDEX idx_pages_published (is_published),
    INDEX idx_pages_page_type (page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    url TEXT NOT NULL,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    link_type ENUM('url','whatsapp','email','phone','payment','social','file','video','form','booking') DEFAULT 'url',
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    open_in_new_tab BOOLEAN DEFAULT TRUE,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    click_count BIGINT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_links_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_links_page_id (page_id),
    INDEX idx_links_position (position),
    INDEX idx_links_active (is_active),
    INDEX idx_links_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE short_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    destination_url TEXT NOT NULL,
    back_half VARCHAR(64) NOT NULL,
    status ENUM('active','archived','disabled') DEFAULT 'active',
    click_count BIGINT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_short_links_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_short_links_back_half (back_half),
    INDEX idx_short_links_user_id (user_id),
    INDEX idx_short_links_status (status),
    INDEX idx_short_links_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE short_link_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    short_link_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_hash VARCHAR(128) NULL,
    user_agent TEXT NULL,
    referer TEXT NULL,
    country VARCHAR(100) NULL,
    device_type VARCHAR(40) NULL,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_short_link_clicks_short_link FOREIGN KEY (short_link_id) REFERENCES short_links(id) ON DELETE CASCADE,
    INDEX idx_short_link_clicks_short_link_id (short_link_id),
    INDEX idx_short_link_clicks_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE social_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    platform ENUM('instagram','linkedin','x','facebook','youtube','tiktok','snapchat','telegram','github','website') NOT NULL,
    url TEXT NOT NULL,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_social_links_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_social_links_page_id (page_id),
    INDEX idx_social_links_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    page_id BIGINT UNSIGNED NULL,
    short_link_id BIGINT UNSIGNED NULL,
    profile_page_id BIGINT UNSIGNED NULL,
    type ENUM('profile_page','website','page','custom') DEFAULT 'website',
    title VARCHAR(150) NOT NULL DEFAULT 'QR Code',
    name VARCHAR(120) DEFAULT 'QR Code',
    destination_url TEXT NULL,
    back_half VARCHAR(64) NULL,
    qr_image_url TEXT NULL,
    qr_image_path TEXT NULL,
    foreground_color VARCHAR(20) DEFAULT '#000000',
    code_color VARCHAR(20) DEFAULT '#000000',
    background_color VARCHAR(20) DEFAULT '#FFFFFF',
    corner_color VARCHAR(20) NULL,
    pattern_style VARCHAR(80) DEFAULT 'default',
    corner_style VARCHAR(80) DEFAULT 'square',
    frame_style VARCHAR(80) NULL,
    frame_text VARCHAR(120) NULL,
    logo_path TEXT NULL,
    remove_xinng_logo BOOLEAN DEFAULT FALSE,
    scan_count BIGINT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    status ENUM('active','archived','disabled') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_qr_codes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_qr_codes_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
    CONSTRAINT fk_qr_codes_short_link FOREIGN KEY (short_link_id) REFERENCES short_links(id) ON DELETE SET NULL,
    CONSTRAINT fk_qr_codes_profile_page FOREIGN KEY (profile_page_id) REFERENCES pages(id) ON DELETE SET NULL,
    UNIQUE KEY unique_qr_codes_back_half (back_half),
    INDEX idx_qr_codes_user_id (user_id),
    INDEX idx_qr_codes_page_id (page_id),
    INDEX idx_qr_codes_short_link_id (short_link_id),
    INDEX idx_qr_codes_profile_page_id (profile_page_id),
    INDEX idx_qr_codes_status (status),
    INDEX idx_qr_codes_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_code_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qr_code_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_hash VARCHAR(128) NULL,
    user_agent TEXT NULL,
    referer TEXT NULL,
    country VARCHAR(100) NULL,
    device_type VARCHAR(40) NULL,
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qr_code_scans_qr_code FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    INDEX idx_qr_code_scans_qr_code_id (qr_code_id),
    INDEX idx_qr_code_scans_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    referrer TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    device_type ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_page_views_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page_views_page_id (page_id),
    INDEX idx_page_views_viewed_at (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE link_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    referrer TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    device_type ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_link_clicks_link FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
    CONSTRAINT fk_link_clicks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_link_clicks_link_id (link_id),
    INDEX idx_link_clicks_page_id (page_id),
    INDEX idx_link_clicks_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(150) NULL,
    description TEXT NULL,
    destination_url TEXT NULL,
    image_path TEXT NULL,
    metadata JSON NULL,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_page_blocks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_page_blocks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_token_hash (token_hash),
    INDEX idx_password_resets_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE credit_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL,
    amount INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference VARCHAR(255) NULL,
    payment_gateway VARCHAR(80) NULL,
    payment_amount INT NULL,
    payment_currency VARCHAR(10) NULL,
    status ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credit_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_theme_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    theme_id BIGINT UNSIGNED NULL,
    custom_background TEXT NULL,
    custom_primary_color VARCHAR(20) NULL,
    custom_secondary_color VARCHAR(20) NULL,
    custom_accent_color VARCHAR(20) NULL,
    custom_font_family VARCHAR(120) NULL,
    custom_button_style VARCHAR(50) NULL,
    custom_css TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_page_theme_settings_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_page_theme_settings (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    price_monthly DECIMAL(12,2) DEFAULT 0,
    price_yearly DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'NGN',
    max_pages INT DEFAULT 1,
    max_links_per_page INT DEFAULT 10,
    custom_domain_enabled BOOLEAN DEFAULT FALSE,
    analytics_enabled BOOLEAN DEFAULT FALSE,
    remove_branding BOOLEAN DEFAULT FALSE,
    qr_customization_enabled BOOLEAN DEFAULT FALSE,
    team_enabled BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plans_slug (slug),
    INDEX idx_plans_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans (name, slug, price_monthly, price_yearly, currency, max_pages, max_links_per_page, custom_domain_enabled, analytics_enabled, remove_branding, qr_customization_enabled, team_enabled, is_active)
VALUES
('Free','free',0.00,0.00,'NGN',1,5,FALSE,FALSE,FALSE,FALSE,FALSE,TRUE),
('Pro','pro',3000.00,30000.00,'NGN',3,50,FALSE,TRUE,TRUE,TRUE,FALSE,TRUE),
('Business','business',10000.00,100000.00,'NGN',10,200,TRUE,TRUE,TRUE,TRUE,TRUE,TRUE);
