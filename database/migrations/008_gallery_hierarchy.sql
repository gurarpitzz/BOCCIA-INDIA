-- Database migration for BSFI Gallery Hierarchy Refactoring (v1.4)

-- 1. Rename old gallery_albums table to keep data intact
RENAME TABLE `gallery_albums` TO `gallery_albums_old`;

-- 2. Create gallery_categories table fresh
CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `display_order` INT DEFAULT 0,
    `icon` VARCHAR(100) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Seed gallery_categories with original category data
INSERT INTO `gallery_categories` (`id`, `name`, `slug`, `display_order`, `icon`, `is_active`) VALUES
(1, 'National Championships', 'national-championships', 1, '🏆', 1),
(2, 'International Events', 'international-events', 2, '🌐', 1),
(3, 'Training Camps', 'training-camps', 3, '⛺', 1),
(4, 'Athlete Development', 'athlete-development', 4, '🌱', 1),
(5, 'General', 'general', 5, '🖼️', 1);

-- 4. Create new gallery_albums table
CREATE TABLE IF NOT EXISTS `gallery_albums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `cover_image_id` INT NULL,
    `cover_image_override` VARCHAR(255) NULL,
    `event_date` DATE NULL,
    `event_location` VARCHAR(255) NULL,
    `album_type` ENUM('event', 'training', 'camp', 'ceremony', 'media', 'general') DEFAULT 'event',
    `is_published` TINYINT(1) DEFAULT 1,
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
