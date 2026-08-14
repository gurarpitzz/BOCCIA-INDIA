-- Database migration to add category and professional details to officials/applications
-- Adds category, education_qualification, para_sports_experience, classifier_type, classifier_type_other, and passport_file columns.

ALTER TABLE `official_applications`
    ADD COLUMN `category` VARCHAR(100) NULL DEFAULT NULL AFTER `role`,
    ADD COLUMN `education_qualification` VARCHAR(255) NULL DEFAULT NULL AFTER `kit_shoe`,
    ADD COLUMN `para_sports_experience` TEXT NULL DEFAULT NULL AFTER `education_qualification`,
    ADD COLUMN `classifier_type` VARCHAR(100) NULL DEFAULT NULL AFTER `para_sports_experience`,
    ADD COLUMN `classifier_type_other` VARCHAR(255) NULL DEFAULT NULL AFTER `classifier_type`,
    ADD COLUMN `passport_file` VARCHAR(255) NULL DEFAULT NULL AFTER `receipt_path`;

ALTER TABLE `officials`
    ADD COLUMN `category` VARCHAR(100) NULL DEFAULT NULL AFTER `role`,
    ADD COLUMN `education_qualification` VARCHAR(255) NULL DEFAULT NULL AFTER `kit_shoe`,
    ADD COLUMN `para_sports_experience` TEXT NULL DEFAULT NULL AFTER `education_qualification`,
    ADD COLUMN `classifier_type` VARCHAR(100) NULL DEFAULT NULL AFTER `para_sports_experience`,
    ADD COLUMN `classifier_type_other` VARCHAR(255) NULL DEFAULT NULL AFTER `classifier_type`,
    ADD COLUMN `passport_file` VARCHAR(255) NULL DEFAULT NULL AFTER `receipt_path`;
