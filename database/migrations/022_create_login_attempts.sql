-- 022_create_login_attempts.sql
-- Create table for tracking failed login attempts and enforcing a 24-hour lockout after 5 failed attempts

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identifier_hash` VARCHAR(64) NOT NULL,
    `attempt_type` ENUM('ip', 'username') NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lookup` (`identifier_hash`, `attempt_type`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
