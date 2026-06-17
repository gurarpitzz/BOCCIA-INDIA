-- 001_cms_extensions.sql
-- Add status columns and soft deletes to existing tables where applicable, and create the new CMS-related tables.

-- 1. Alter Existing Tables to support status and soft deletes
ALTER TABLE `news` 
    ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;

ALTER TABLE `gallery_images` 
    ADD COLUMN `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published' AFTER `active`,
    ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;

-- 2. Media Assets Table (Central Registry)
CREATE TABLE IF NOT EXISTS `media_assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `filesize` INT NOT NULL,
    `thumbnail` VARCHAR(500) NULL DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB;

-- 3. Dynamic Site Pages Table
CREATE TABLE IF NOT EXISTS `site_pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `og_image` VARCHAR(500) NULL,
    `canonical_url` VARCHAR(255) NULL,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    UNIQUE KEY `sec_slug_unique` (`section`, `slug`)
) ENGINE=InnoDB;

-- 4. Page Versions (For rollback)
CREATE TABLE IF NOT EXISTS `page_versions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT NOT NULL,
    `version` INT NOT NULL,
    `content_snapshot` LONGTEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`page_id`) REFERENCES `site_pages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Dynamic Navigation Items Table
CREATE TABLE IF NOT EXISTS `navigation_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NULL DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NULL DEFAULT NULL,
    `section` VARCHAR(50) NULL DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `icon` VARCHAR(50) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `navigation_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Events Extensions
ALTER TABLE `events`
    ADD COLUMN `banner_image` VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;

-- 7. Event Documents Table
CREATE TABLE IF NOT EXISTS `event_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `media_asset_id` INT NOT NULL,
    `doc_type` ENUM('circular', 'schedule', 'results', 'other') NOT NULL DEFAULT 'other',
    `title` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Event Gallery Table
CREATE TABLE IF NOT EXISTS `event_gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `media_asset_id` INT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Search Index Table (with FULLTEXT searching)
CREATE TABLE IF NOT EXISTS `search_index` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NULL,
    `type` VARCHAR(50) NOT NULL, -- 'page', 'document', 'news', 'event', etc.
    `url` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY `search_idx` (`title`, `content`)
) ENGINE=MyISAM; -- MyISAM or InnoDB (MySQL 5.6+ supports FULLTEXT in InnoDB, but MyISAM is safe and robust on all setups)

-- 10. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
