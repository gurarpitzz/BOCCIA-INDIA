-- Database initialization for Boccia Sports Federation of India (BSFI)

-- CREATE DATABASE IF NOT EXISTS `boccia_india` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `boccia_india`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- States Table
CREATE TABLE IF NOT EXISTS `states` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `type` ENUM('state', 'union_territory') NOT NULL DEFAULT 'state',
    `active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- State Associations Table
CREATE TABLE IF NOT EXISTS `state_associations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `state_id` INT NOT NULL,
    `association_name` VARCHAR(150) NOT NULL UNIQUE,
    `contact_email` VARCHAR(100) NULL,
    `contact_phone` VARCHAR(20) NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (`state_id`) REFERENCES `states`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Athletes Table
CREATE TABLE IF NOT EXISTS `athletes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `regn_no` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL,
    `dob` DATE NOT NULL,
    `mobile` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NULL,
    `classification` VARCHAR(20) NOT NULL,
    `representing_for` VARCHAR(100) NOT NULL,
    `state_association_id` INT NULL,
    `wheelchair_status` VARCHAR(50) NULL,
    `photo_path` VARCHAR(255) NULL,
    `receipt_path` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'archived') NOT NULL DEFAULT 'pending',
    `created_by` INT NULL,
    `updated_by` INT NULL,
    `reviewed_by` INT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`state_association_id`) REFERENCES `state_associations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- News Table
CREATE TABLE IF NOT EXISTS `news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(255) UNIQUE,
    `excerpt` TEXT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `featured` TINYINT(1) DEFAULT 0,
    `pinned` TINYINT(1) DEFAULT 0,
    `status` ENUM('draft', 'published', 'scheduled', 'archived') NOT NULL DEFAULT 'draft',
    `views` INT DEFAULT 0,
    `author_name` VARCHAR(100) DEFAULT 'BSFI Official',
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `published_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- News Images Table
CREATE TABLE IF NOT EXISTS `news_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `news_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Schedules Table
CREATE TABLE IF NOT EXISTS `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `discipline` VARCHAR(150) NOT NULL,
    `event_type` VARCHAR(100) DEFAULT NULL,
    `date_text` VARCHAR(100) NOT NULL,
    `venue` VARCHAR(200) NOT NULL,
    `registration_link` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Gallery Table
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_name` VARCHAR(255) NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `description` TEXT NULL,
    `featured` TINYINT(1) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Site Settings Table
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL
) ENGINE=InnoDB;

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(255) NOT NULL,
    `user_id` INT NULL,
    `target_type` VARCHAR(50) NULL,
    `target_id` INT NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert initial users (Hashed passwords for demo: 'adminpassword', 'editorpassword', 'viewerpassword')
-- Admin: admin / adminpassword
-- Editor: editor / editorpassword
-- Viewer: viewer / viewerpassword
INSERT IGNORE INTO `users` (`username`, `password_hash`, `role`) VALUES
('admin', '$2y$10$t2u7jJ7870tH7hUuV0D7EOMK.F.xN9uVp1U901m1b3R9d3s5v3W.m', 'admin'),
('editor', '$2y$10$t2u7jJ7870tH7hUuV0D7EOMK.F.xN9uVp1U901m1b3R9d3s5v3W.m', 'editor'),
('viewer', '$2y$10$t2u7jJ7870tH7hUuV0D7EOMK.F.xN9uVp1U901m1b3R9d3s5v3W.m', 'viewer');

-- Seed States/UTs (36 standard regions)
INSERT IGNORE INTO `states` (`name`, `type`) VALUES
('Andhra Pradesh', 'state'),
('Arunachal Pradesh', 'state'),
('Assam', 'state'),
('Bihar', 'state'),
('Chhattisgarh', 'state'),
('Goa', 'state'),
('Gujarat', 'state'),
('Haryana', 'state'),
('Himachal Pradesh', 'state'),
('Jharkhand', 'state'),
('Karnataka', 'state'),
('Kerala', 'state'),
('Madhya Pradesh', 'state'),
('Maharashtra', 'state'),
('Manipur', 'state'),
('Meghalaya', 'state'),
('Mizoram', 'state'),
('Nagaland', 'state'),
('Odisha', 'state'),
('Punjab', 'state'),
('Rajasthan', 'state'),
('Sikkim', 'state'),
('Tamil Nadu', 'state'),
('Telangana', 'state'),
('Tripura', 'state'),
('Uttar Pradesh', 'state'),
('Uttarakhand', 'state'),
('West Bengal', 'state'),
('Andaman and Nicobar Islands', 'union_territory'),
('Chandigarh', 'union_territory'),
('Dadra and Nagar Haveli and Daman and Diu', 'union_territory'),
('Delhi', 'union_territory'),
('Jammu and Kashmir', 'union_territory'),
('Ladakh', 'union_territory'),
('Lakshadweep', 'union_territory'),
('Puducherry', 'union_territory');

-- Seed State Associations (Initial seeds mapping to active states)
INSERT IGNORE INTO `state_associations` (`state_id`, `association_name`, `contact_email`, `contact_phone`) VALUES
((SELECT `id` FROM `states` WHERE `name`='Delhi'), 'Delhi Boccia Association', 'delhiboccia@gmail.com', '9812345678'),
((SELECT `id` FROM `states` WHERE `name`='Maharashtra'), 'Maharashtra Paralympic Boccia Association', 'mahboccia@gmail.com', '9823456781'),
((SELECT `id` FROM `states` WHERE `name`='Tamil Nadu'), 'Tamil Nadu Boccia Association', 'tnboccia@gmail.com', '9834567812'),
((SELECT `id` FROM `states` WHERE `name`='Gujarat'), 'Gujarat Paralympic Boccia Committee', 'gujboccia@gmail.com', '9845678123'),
((SELECT `id` FROM `states` WHERE `name`='Assam'), 'Assam Boccia Association', 'assamboccia@gmail.com', '9856781234'),
((SELECT `id` FROM `states` WHERE `name`='Karnataka'), 'Karnataka Paralympic Boccia Association', 'karboccia@gmail.com', '9867812345'),
((SELECT `id` FROM `states` WHERE `name`='Punjab'), 'Punjab State Boccia Association', 'punjboccia@gmail.com', '9464500042'),
((SELECT `id` FROM `states` WHERE `name`='Haryana'), 'Haryana Boccia Association', 'haryanaboccia@gmail.com', '9876543210');

