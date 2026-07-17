-- Migration 015: Add is_national and results fields to schedules table
ALTER TABLE `schedules`
ADD COLUMN `is_national` TINYINT(1) DEFAULT 0,
ADD COLUMN `result_url` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `result_button_text` VARCHAR(255) DEFAULT 'View Results';
