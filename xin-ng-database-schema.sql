-- xin.ng Database Schema
-- QR-powered Linktree clone
-- Compatible with MySQL 8+ / MariaDB 10.4+
-- Run this in phpMyAdmin, Adminer, MySQL CLI, or your hosting database panel.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS xin_ng
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE xin_ng;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS media_files;
DROP TABLE IF EXISTS team_members;
DROP TABLE IF EXISTS custom_domains;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS qr_scans;
DROP TABLE IF EXISTS qr_code_scans;
DROP TABLE IF EXISTS short_link_clicks;
DROP TABLE IF EXISTS short_links;
DROP TABLE IF EXISTS link_clicks;
DROP TABLE IF EXISTS page_views;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS page_theme_settings;
DROP TABLE IF EXISTS themes;
DROP TABLE IF EXISTS social_links;
DROP TABLE IF EXISTS links;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,

    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    avatar_url TEXT NULL,
    phone VARCHAR(50) NULL,

    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,

    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    role ENUM('user', 'admin') DEFAULT 'user',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    INDEX idx_users_email (email),
    INDEX idx_users_status (status),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 2. PAGES
-- =========================================================

CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    slug VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    bio TEXT NULL,

    profile_image_url TEXT NULL,
    cover_image_url TEXT NULL,

    is_published BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,

    page_type ENUM('personal', 'business', 'creator', 'event') DEFAULT 'personal',

    seo_title VARCHAR(180) NULL,
    seo_description TEXT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pages_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_pages_user_id (user_id),
    INDEX idx_pages_slug (slug),
    INDEX idx_pages_published (is_published),
    INDEX idx_pages_type (page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3. LINKS
-- =========================================================

CREATE TABLE links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(150) NOT NULL,
    url TEXT NOT NULL,

    description TEXT NULL,
    icon VARCHAR(100) NULL,

    link_type ENUM(
        'url',
        'whatsapp',
        'email',
        'phone',
        'payment',
        'social',
        'file',
        'video',
        'form',
        'booking'
    ) DEFAULT 'url',

    position INT DEFAULT 0,

    is_active BOOLEAN DEFAULT TRUE,
    open_in_new_tab BOOLEAN DEFAULT TRUE,

    start_at DATETIME NULL,
    end_at DATETIME NULL,

    click_count BIGINT UNSIGNED DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_links_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_links_page_id (page_id),
    INDEX idx_links_position (position),
    INDEX idx_links_active (is_active),
    INDEX idx_links_type (link_type),
    INDEX idx_links_start_at (start_at),
    INDEX idx_links_end_at (end_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. SHORT LINKS
-- =========================================================

CREATE TABLE short_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(150) NOT NULL,
    destination_url TEXT NOT NULL,
    back_half VARCHAR(64) NOT NULL,

    status ENUM('active', 'archived', 'disabled') DEFAULT 'active',
    click_count BIGINT UNSIGNED DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_short_links_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

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

    CONSTRAINT fk_short_link_clicks_short_link
        FOREIGN KEY (short_link_id) REFERENCES short_links(id)
        ON DELETE CASCADE,

    INDEX idx_short_link_clicks_short_link_id (short_link_id),
    INDEX idx_short_link_clicks_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. SOCIAL LINKS
-- =========================================================

CREATE TABLE social_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,

    platform ENUM(
        'instagram',
        'linkedin',
        'x',
        'facebook',
        'youtube',
        'tiktok',
        'snapchat',
        'telegram',
        'github',
        'website'
    ) NOT NULL,

    url TEXT NOT NULL,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_social_links_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_social_links_page_id (page_id),
    INDEX idx_social_links_platform (platform),
    INDEX idx_social_links_active (is_active),
    INDEX idx_social_links_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. THEMES
-- =========================================================

CREATE TABLE themes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,

    background_type ENUM('solid', 'gradient', 'image', 'video') DEFAULT 'solid',
    background_value TEXT NULL,

    primary_color VARCHAR(20) DEFAULT '#111111',
    secondary_color VARCHAR(20) DEFAULT '#ffffff',
    accent_color VARCHAR(20) DEFAULT '#2563eb',

    font_family VARCHAR(120) DEFAULT 'Inter',

    button_style ENUM('rounded', 'pill', 'square', 'glass', 'outline') DEFAULT 'rounded',
    button_background VARCHAR(20) DEFAULT '#111111',
    button_text_color VARCHAR(20) DEFAULT '#ffffff',

    is_premium BOOLEAN DEFAULT FALSE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_themes_slug (slug),
    INDEX idx_themes_premium (is_premium)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. PAGE THEME SETTINGS
-- =========================================================

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

    CONSTRAINT fk_page_theme_settings_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_page_theme_settings_theme
        FOREIGN KEY (theme_id) REFERENCES themes(id)
        ON DELETE SET NULL,

    UNIQUE KEY unique_page_theme_settings (page_id),
    INDEX idx_page_theme_settings_theme_id (theme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 7. QR CODES
-- =========================================================

CREATE TABLE qr_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    page_id BIGINT UNSIGNED NULL,
    short_link_id BIGINT UNSIGNED NULL,
    profile_page_id BIGINT UNSIGNED NULL,

    type ENUM('profile_page', 'website', 'page', 'custom') DEFAULT 'website',
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
    logo_url TEXT NULL,
    logo_path TEXT NULL,
    remove_xinng_logo BOOLEAN DEFAULT FALSE,

    scan_count BIGINT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'archived', 'disabled') DEFAULT 'active',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    CONSTRAINT fk_qr_codes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_qr_codes_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_qr_codes_short_link
        FOREIGN KEY (short_link_id) REFERENCES short_links(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_qr_codes_profile_page
        FOREIGN KEY (profile_page_id) REFERENCES pages(id)
        ON DELETE SET NULL,

    UNIQUE KEY unique_qr_codes_back_half (back_half),
    INDEX idx_qr_codes_user_id (user_id),
    INDEX idx_qr_codes_page_id (page_id),
    INDEX idx_qr_codes_short_link_id (short_link_id),
    INDEX idx_qr_codes_profile_page_id (profile_page_id),
    INDEX idx_qr_codes_active (is_active),
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

    CONSTRAINT fk_qr_code_scans_qr_code
        FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id)
        ON DELETE CASCADE,

    INDEX idx_qr_code_scans_qr_code_id (qr_code_id),
    INDEX idx_qr_code_scans_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 8. PAGE VIEWS
-- =========================================================

CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,

    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,

    referrer TEXT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,

    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
    browser VARCHAR(100) NULL,
    os VARCHAR(100) NULL,

    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_page_views_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_page_views_page_id (page_id),
    INDEX idx_page_views_visitor_id (visitor_id),
    INDEX idx_page_views_device_type (device_type),
    INDEX idx_page_views_viewed_at (viewed_at),
    INDEX idx_page_views_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 9. LINK CLICKS
-- =========================================================

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

    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',

    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_link_clicks_link
        FOREIGN KEY (link_id) REFERENCES links(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_link_clicks_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_link_clicks_link_id (link_id),
    INDEX idx_link_clicks_page_id (page_id),
    INDEX idx_link_clicks_visitor_id (visitor_id),
    INDEX idx_link_clicks_device_type (device_type),
    INDEX idx_link_clicks_clicked_at (clicked_at),
    INDEX idx_link_clicks_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 10. QR SCANS
-- =========================================================

CREATE TABLE qr_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qr_code_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,

    visitor_id VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,

    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,

    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_qr_scans_qr_code
        FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_qr_scans_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_qr_scans_qr_code_id (qr_code_id),
    INDEX idx_qr_scans_page_id (page_id),
    INDEX idx_qr_scans_visitor_id (visitor_id),
    INDEX idx_qr_scans_scanned_at (scanned_at),
    INDEX idx_qr_scans_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 11. PLANS
-- =========================================================

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

-- =========================================================
-- 12. SUBSCRIPTIONS
-- =========================================================

CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,

    status ENUM('active', 'trialing', 'past_due', 'cancelled', 'expired') DEFAULT 'active',

    payment_provider ENUM('paystack', 'flutterwave', 'stripe', 'manual') DEFAULT 'paystack',
    provider_customer_id VARCHAR(190) NULL,
    provider_subscription_id VARCHAR(190) NULL,

    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    renews_at DATETIME NULL,
    cancelled_at DATETIME NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_subscriptions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_subscriptions_plan
        FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON DELETE RESTRICT,

    INDEX idx_subscriptions_user_id (user_id),
    INDEX idx_subscriptions_plan_id (plan_id),
    INDEX idx_subscriptions_status (status),
    INDEX idx_subscriptions_provider_subscription_id (provider_subscription_id),
    INDEX idx_subscriptions_renews_at (renews_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 13. PAYMENTS
-- =========================================================

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NULL,

    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'NGN',

    payment_provider ENUM('paystack', 'flutterwave', 'stripe', 'manual') DEFAULT 'paystack',
    provider_reference VARCHAR(190) UNIQUE,

    status ENUM('pending', 'successful', 'failed', 'refunded') DEFAULT 'pending',

    paid_at DATETIME NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_payments_subscription
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
        ON DELETE SET NULL,

    INDEX idx_payments_user_id (user_id),
    INDEX idx_payments_subscription_id (subscription_id),
    INDEX idx_payments_status (status),
    INDEX idx_payments_provider_reference (provider_reference),
    INDEX idx_payments_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 14. CUSTOM DOMAINS
-- =========================================================

CREATE TABLE custom_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,

    domain VARCHAR(190) NOT NULL UNIQUE,

    verification_token VARCHAR(190) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,

    ssl_status ENUM('pending', 'active', 'failed') DEFAULT 'pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_custom_domains_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE,

    INDEX idx_custom_domains_page_id (page_id),
    INDEX idx_custom_domains_domain (domain),
    INDEX idx_custom_domains_verified (is_verified),
    INDEX idx_custom_domains_ssl_status (ssl_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 15. TEAM MEMBERS
-- =========================================================

CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    owner_user_id BIGINT UNSIGNED NOT NULL,
    member_user_id BIGINT UNSIGNED NOT NULL,

    role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_team_members_owner
        FOREIGN KEY (owner_user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_team_members_member
        FOREIGN KEY (member_user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_team_member (owner_user_id, member_user_id),
    INDEX idx_team_members_owner_user_id (owner_user_id),
    INDEX idx_team_members_member_user_id (member_user_id),
    INDEX idx_team_members_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 16. MEDIA FILES
-- =========================================================

CREATE TABLE media_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_url TEXT NOT NULL,

    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED DEFAULT 0,

    media_type ENUM('image', 'video', 'document', 'audio', 'other') DEFAULT 'other',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_media_files_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_media_files_user_id (user_id),
    INDEX idx_media_files_media_type (media_type),
    INDEX idx_media_files_mime_type (mime_type),
    INDEX idx_media_files_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- STARTER DATA
-- =========================================================

INSERT INTO plans (
    name,
    slug,
    price_monthly,
    price_yearly,
    currency,
    max_pages,
    max_links_per_page,
    custom_domain_enabled,
    analytics_enabled,
    remove_branding,
    qr_customization_enabled,
    team_enabled,
    is_active
) VALUES
(
    'Free',
    'free',
    0.00,
    0.00,
    'NGN',
    1,
    5,
    FALSE,
    FALSE,
    FALSE,
    FALSE,
    FALSE,
    TRUE
),
(
    'Pro',
    'pro',
    3000.00,
    30000.00,
    'NGN',
    3,
    50,
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE
),
(
    'Business',
    'business',
    10000.00,
    100000.00,
    'NGN',
    10,
    200,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE
);

INSERT INTO themes (
    name,
    slug,
    background_type,
    background_value,
    primary_color,
    secondary_color,
    accent_color,
    font_family,
    button_style,
    button_background,
    button_text_color,
    is_premium
) VALUES
(
    'Clean Light',
    'clean-light',
    'solid',
    '#ffffff',
    '#111111',
    '#ffffff',
    '#2563eb',
    'Inter',
    'rounded',
    '#111111',
    '#ffffff',
    FALSE
),
(
    'Dark Business',
    'dark-business',
    'solid',
    '#0f172a',
    '#ffffff',
    '#0f172a',
    '#38bdf8',
    'Inter',
    'rounded',
    '#1e293b',
    '#ffffff',
    FALSE
),
(
    'Premium Glass',
    'premium-glass',
    'gradient',
    'linear-gradient(135deg, #020617, #1e293b)',
    '#ffffff',
    '#020617',
    '#22c55e',
    'Inter',
    'glass',
    'rgba(255,255,255,0.12)',
    '#ffffff',
    TRUE
);

-- =========================================================
-- USEFUL VIEWS
-- =========================================================

CREATE OR REPLACE VIEW page_analytics_summary AS
SELECT
    p.id AS page_id,
    p.slug,
    p.title,
    p.user_id,
    COUNT(DISTINCT pv.id) AS total_page_views,
    COUNT(DISTINCT qs.id) AS total_qr_scans,
    COUNT(DISTINCT lc.id) AS total_link_clicks
FROM pages p
LEFT JOIN page_views pv ON pv.page_id = p.id
LEFT JOIN qr_scans qs ON qs.page_id = p.id
LEFT JOIN link_clicks lc ON lc.page_id = p.id
GROUP BY p.id, p.slug, p.title, p.user_id;

CREATE OR REPLACE VIEW link_analytics_summary AS
SELECT
    l.id AS link_id,
    l.page_id,
    l.title,
    l.url,
    l.link_type,
    l.position,
    l.is_active,
    COUNT(lc.id) AS total_clicks
FROM links l
LEFT JOIN link_clicks lc ON lc.link_id = l.id
GROUP BY l.id, l.page_id, l.title, l.url, l.link_type, l.position, l.is_active;

-- =========================================================
-- DONE
-- =========================================================

-- Public page example:
-- https://xin.ng/u/micony
--
-- QR tracking route example:
-- https://xin.ng/qr/micony
--
-- Link click tracking route example:
-- https://xin.ng/l/123
