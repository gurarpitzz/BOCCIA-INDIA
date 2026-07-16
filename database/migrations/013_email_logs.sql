-- Migration: 013_email_logs.sql
-- Creates the email_logs table used by includes/mailer.php for
-- structured logging and duplicate suppression.

CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `recipient`     VARCHAR(255) NOT NULL,
    `subject`       VARCHAR(500) NOT NULL,
    `status`        ENUM('sent', 'failed') NOT NULL DEFAULT 'failed',
    `response_code` SMALLINT UNSIGNED DEFAULT NULL,
    `response_body` VARCHAR(500) DEFAULT NULL,
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `sent_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email_logs_dedupe` (`recipient`(100), `subject`(200), `status`, `sent_at`),
    INDEX `idx_email_logs_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
