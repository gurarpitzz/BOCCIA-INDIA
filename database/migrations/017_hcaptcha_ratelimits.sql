-- 017_hcaptcha_ratelimits.sql - Schema adjustments for OTP hardening

-- 1. Create otp_rate_limits table
CREATE TABLE IF NOT EXISTS `otp_rate_limits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identifier_hash` VARCHAR(64) NOT NULL,
    `identifier_type` ENUM('ip', 'email') NOT NULL,
    `window_type` ENUM('15min', '24hr') NOT NULL,
    `request_count` INT DEFAULT 1,
    `window_started_at` DATETIME NOT NULL,
    `last_request_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rate_limit` (`identifier_hash`, `identifier_type`, `window_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Drop existing email_otps table if needed or rename / recreate it to support status and hashing
DROP TABLE IF EXISTS `email_otps`;

CREATE TABLE `email_otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email_hash` VARCHAR(64) NOT NULL,
    `otp_hash` VARCHAR(64) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `status` ENUM('pending', 'sent', 'used', 'invalidated', 'failed') NOT NULL DEFAULT 'pending',
    `expires_at` DATETIME NOT NULL,
    `attempt_count` INT DEFAULT 0,
    `used_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email_action_status` (`email_hash`, `action`, `status`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
