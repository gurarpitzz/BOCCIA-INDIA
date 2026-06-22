-- 003_profile_governance.sql - Add photo status and profile update requests table

ALTER TABLE `athletes`
    ADD COLUMN `photo_status` ENUM('missing', 'verified') NOT NULL DEFAULT 'missing';

ALTER TABLE `officials`
    ADD COLUMN `photo_status` ENUM('missing', 'verified') NOT NULL DEFAULT 'missing';

-- Set initial values
UPDATE `athletes` SET `photo_status` = IF(photo_path IS NOT NULL AND photo_path != '', 'verified', 'missing');
UPDATE `officials` SET `photo_status` = IF(photo_path IS NOT NULL AND photo_path != '', 'verified', 'missing');

CREATE TABLE IF NOT EXISTS `profile_update_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_type` ENUM('athlete', 'official') NOT NULL,
    `member_id` INT NOT NULL,
    `requested_email` VARCHAR(100) NULL,
    `requested_phone` VARCHAR(20) NULL,
    `requested_address` TEXT NULL,
    `requested_pincode` VARCHAR(20) NULL,
    `requested_photo_path` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `reviewed_by` INT NULL,
    `review_notes` TEXT NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
