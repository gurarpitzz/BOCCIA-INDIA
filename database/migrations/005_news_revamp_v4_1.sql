-- Database migration for BSFI News System Revamp (v4.1)

-- 1. Create News Categories Table
CREATE TABLE IF NOT EXISTS `news_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. Seed News Categories
INSERT IGNORE INTO `news_categories` (`name`) VALUES
('National Championship'),
('International Competition'),
('Selection Trials'),
('Training Camp'),
('Federation Notice'),
('Government Circular'),
('Tender'),
('Paralympic Updates');

-- 3. Alter News Table to support the revamp
ALTER TABLE `news`
    ADD COLUMN IF NOT EXISTS `category_id` INT NULL,
    ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `is_pinned` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `thumbnail_image` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `facebook_url` VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS `instagram_url` VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS `twitter_url` VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS `linkedin_url` VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS `youtube_url` VARCHAR(500) NULL,
    ADD CONSTRAINT `fk_news_category` FOREIGN KEY (`category_id`) REFERENCES `news_categories`(`id`) ON DELETE SET NULL;

-- 4. Adjust the existing status enum if necessary (we can leave it as draft/published/scheduled/archived from schema.sql)

-- 5. Add indexes for performance optimization
ALTER TABLE `news` ADD INDEX IF NOT EXISTS `idx_news_title` (`title`);
ALTER TABLE `news` ADD INDEX IF NOT EXISTS `idx_news_slug` (`slug`);
ALTER TABLE `news` ADD INDEX IF NOT EXISTS `idx_news_status` (`status`);
ALTER TABLE `news` ADD INDEX IF NOT EXISTS `idx_news_published` (`published_at`);
ALTER TABLE `news` ADD INDEX IF NOT EXISTS `idx_news_deleted` (`deleted_at`);

-- 6. Add caption column to news_images table if it does not exist
ALTER TABLE `news_images`
    ADD COLUMN IF NOT EXISTS `caption` VARCHAR(255) NULL;
