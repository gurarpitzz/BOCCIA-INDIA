-- 002_membership_refactor.sql - Schema changes for Athlete & Official separation and duplicate management

-- 1. Alter existing athletes table
ALTER TABLE `athletes` 
    ADD COLUMN `digilocker_imported` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `aadhaar` VARCHAR(50) NULL DEFAULT NULL,
    ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;

-- 2. Create athlete_applications table
CREATE TABLE IF NOT EXISTS `athlete_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `dob` DATE NOT NULL,
    `father_name` VARCHAR(100) NULL DEFAULT NULL,
    `mother_name` VARCHAR(100) NULL DEFAULT NULL,
    `age_category` VARCHAR(50) NULL DEFAULT NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NULL DEFAULT NULL,
    `impairment_type` VARCHAR(255) NULL DEFAULT NULL,
    `classification` VARCHAR(20) NOT NULL,
    `wheelchair_status` VARCHAR(50) NULL DEFAULT NULL,
    `aadhaar` VARCHAR(50) NULL DEFAULT NULL,
    `phone` VARCHAR(20) NULL DEFAULT NULL,
    `email` VARCHAR(100) NULL DEFAULT NULL,
    `address` TEXT NULL DEFAULT NULL,
    `pincode` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tshirt` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tracksuit` VARCHAR(20) NULL DEFAULT NULL,
    `kit_shoe` VARCHAR(20) NULL DEFAULT NULL,
    `photo_path` VARCHAR(255) NULL DEFAULT NULL,
    `receipt_path` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'correction_requested') NOT NULL DEFAULT 'pending',
    `existing_athlete_id` INT NULL DEFAULT NULL,
    `possible_duplicate` TINYINT(1) NOT NULL DEFAULT 0,
    `duplicate_score` INT NOT NULL DEFAULT 0,
    `review_notes` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`existing_athlete_id`) REFERENCES `athletes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create officials table
CREATE TABLE IF NOT EXISTS `officials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `official_reg_no` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `designation` VARCHAR(100) NULL DEFAULT NULL,
    `gender` VARCHAR(20) NOT NULL DEFAULT 'Male',
    `dob` DATE NULL DEFAULT NULL,
    `father_name` VARCHAR(100) NULL DEFAULT NULL,
    `state` VARCHAR(100) NOT NULL,
    `aadhaar` VARCHAR(50) NULL DEFAULT NULL,
    `phone` VARCHAR(20) NULL DEFAULT NULL,
    `email` VARCHAR(100) NULL DEFAULT NULL,
    `address` TEXT NULL DEFAULT NULL,
    `pincode` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tshirt` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tracksuit` VARCHAR(20) NULL DEFAULT NULL,
    `kit_shoe` VARCHAR(20) NULL DEFAULT NULL,
    `photo_path` VARCHAR(255) NULL DEFAULT NULL,
    `receipt_path` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'archived', 'suspended') NOT NULL DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create official_applications table
CREATE TABLE IF NOT EXISTS `official_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `dob` DATE NOT NULL,
    `father_name` VARCHAR(100) NULL DEFAULT NULL,
    `state` VARCHAR(100) NOT NULL,
    `aadhaar` VARCHAR(50) NULL DEFAULT NULL,
    `phone` VARCHAR(20) NULL DEFAULT NULL,
    `email` VARCHAR(100) NULL DEFAULT NULL,
    `address` TEXT NULL DEFAULT NULL,
    `pincode` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tshirt` VARCHAR(20) NULL DEFAULT NULL,
    `kit_tracksuit` VARCHAR(20) NULL DEFAULT NULL,
    `kit_shoe` VARCHAR(20) NULL DEFAULT NULL,
    `photo_path` VARCHAR(255) NULL DEFAULT NULL,
    `receipt_path` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'correction_requested') NOT NULL DEFAULT 'pending',
    `existing_official_id` INT NULL DEFAULT NULL,
    `possible_duplicate` TINYINT(1) NOT NULL DEFAULT 0,
    `duplicate_score` INT NOT NULL DEFAULT 0,
    `review_notes` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`existing_official_id`) REFERENCES `officials`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create athlete_history table
CREATE TABLE IF NOT EXISTS `athlete_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `event_name` VARCHAR(255) NOT NULL,
    `event_year` INT NOT NULL,
    `classification` VARCHAR(50) NULL DEFAULT NULL,
    `event_level` VARCHAR(50) NULL DEFAULT NULL,
    `state_represented` VARCHAR(100) NULL DEFAULT NULL,
    `rank` VARCHAR(50) NULL DEFAULT NULL,
    `medal` VARCHAR(50) NULL DEFAULT NULL,
    `remarks` TEXT NULL DEFAULT NULL,
    FOREIGN KEY (`athlete_id`) REFERENCES `athletes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create athlete_registry_import table
CREATE TABLE IF NOT EXISTS `athlete_registry_import` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `source_file` VARCHAR(255) NOT NULL,
    `import_batch` VARCHAR(50) NOT NULL,
    `original_csv_row` JSON NOT NULL,
    `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`athlete_id`) REFERENCES `athletes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create athlete_status_history table
CREATE TABLE IF NOT EXISTS `athlete_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `old_status` VARCHAR(50) NULL DEFAULT NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` INT NOT NULL,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `remarks` TEXT NULL DEFAULT NULL,
    FOREIGN KEY (`athlete_id`) REFERENCES `athletes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create registration_sequences table
CREATE TABLE IF NOT EXISTS `registration_sequences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_last_no` INT NOT NULL DEFAULT 100,
    `official_last_no` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Seed sequences table with initial values
INSERT INTO `registration_sequences` (`athlete_last_no`, `official_last_no`) VALUES (100, 0);
