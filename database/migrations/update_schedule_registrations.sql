-- BSFI Event Registration Upgrade Migration Script

-- 1. Extend schedules table
ALTER TABLE `schedules`
ADD COLUMN `registration_mode` ENUM('disabled', 'internal', 'external') DEFAULT 'external',
ADD COLUMN `registration_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `registration_deadline` DATETIME DEFAULT NULL,
ADD COLUMN `max_participants` INT DEFAULT NULL,
ADD COLUMN `allow_waiting_list` TINYINT(1) DEFAULT 0;

-- 2. Create event_form_fields table
CREATE TABLE IF NOT EXISTS `event_form_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `field_label` VARCHAR(255) NOT NULL,
    `field_type` ENUM('text', 'textarea', 'number', 'date', 'dropdown', 'radio', 'checkbox', 'file', 'image') NOT NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `placeholder` VARCHAR(255) DEFAULT NULL,
    `help_text` VARCHAR(255) DEFAULT NULL,
    `field_options` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create event_registrations table
CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_no` VARCHAR(50) NOT NULL UNIQUE,
    `schedule_id` INT NOT NULL,
    `member_type` ENUM('athlete', 'official') NOT NULL,
    `member_id` INT NOT NULL,
    `payment_status` ENUM('free', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `registration_status` ENUM('pending', 'approved', 'rejected', 'waiting_list') NOT NULL DEFAULT 'pending',
    `payment_receipt_path` VARCHAR(255) DEFAULT NULL,
    `transaction_reference` VARCHAR(100) DEFAULT NULL,
    `rejection_remarks` TEXT DEFAULT NULL,
    `snapshot_name` VARCHAR(100) NOT NULL,
    `snapshot_regn_no` VARCHAR(50) NOT NULL,
    `snapshot_email` VARCHAR(100) NOT NULL,
    `snapshot_mobile` VARCHAR(20) NOT NULL,
    `snapshot_state` VARCHAR(100) NOT NULL,
    `snapshot_classification` VARCHAR(50) DEFAULT NULL,
    `snapshot_gender` VARCHAR(20) DEFAULT NULL,
    `snapshot_dob` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_member_event` (`schedule_id`, `member_type`, `member_id`),
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create event_registration_answers table
CREATE TABLE IF NOT EXISTS `event_registration_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `field_id` INT NOT NULL,
    `answer_value` TEXT DEFAULT NULL,
    FOREIGN KEY (`registration_id`) REFERENCES `event_registrations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`field_id`) REFERENCES `event_form_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed default Centralized Federation Bank Details in site_settings
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('payment_bank_name', 'State Bank of India'),
('payment_account_name', 'Boccia Sports Federation of India'),
('payment_account_number', '36123404464'),
('payment_branch', 'Saggu Complex, 100 Feet Road, Near Aakash Institute, Bathinda, Punjab - 151001'),
('payment_ifsc_code', 'SBIN0019158');
