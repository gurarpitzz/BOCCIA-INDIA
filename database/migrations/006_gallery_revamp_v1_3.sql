-- Database migration for BSFI Photo Gallery Revamp (v1.3)

-- 1. Create Gallery Albums Table
CREATE TABLE IF NOT EXISTS `gallery_albums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `cover_image` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Seed Default Albums
INSERT IGNORE INTO `gallery_albums` (`title`, `slug`, `description`) VALUES
('National Championships', 'national-championships', 'Photos from BSFI National Championship matches and awards.'),
('International Events', 'international-events', 'Indian Boccia athletes competing globally.'),
('Training Camps', 'training-camps', 'Snapshots from national training and selection camps.'),
('Athlete Development', 'athlete-development', 'Grassroots development, coaching workshops, and trials.'),
('General', 'general', 'General promotional pictures and official events.');

-- 3. Create Gallery Images Table
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `album_id` INT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `thumbnail_path` VARCHAR(255) NULL,
    `medium_path` VARCHAR(255) NULL,
    `full_path` VARCHAR(255) NULL,
    `file_hash` CHAR(64) UNIQUE NOT NULL,
    `caption` VARCHAR(255) NULL,
    `credit` VARCHAR(255) NULL,
    `alt_text` VARCHAR(255) NULL,
    `uploaded_by` INT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `show_in_hero` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `views` INT DEFAULT 0,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'published',
    `is_deleted` TINYINT(1) DEFAULT 0,
    `deleted_by` INT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`album_id`) REFERENCES `gallery_albums`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Create Indexes
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_status` (`status`);
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_deleted` (`is_deleted`);
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_featured` (`is_featured`);
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_hero` (`show_in_hero`);
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_sort` (`sort_order`);
ALTER TABLE `gallery_images` ADD INDEX IF NOT EXISTS `idx_gallery_hash` (`file_hash`);
