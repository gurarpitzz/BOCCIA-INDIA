-- Migration 015: Add is_national and results fields to schedules table
ALTER TABLE `schedules`
ADD COLUMN `competition_scope` ENUM('International', 'National', 'State') DEFAULT 'National',
ADD COLUMN `result_url` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `result_button_text` VARCHAR(255) DEFAULT 'View Results';
