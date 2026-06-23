-- 009_registry_hardening.sql - BSFI Registry Hardening
-- 1. Reset sequences so next athlete registration starts at 0100
UPDATE `registration_sequences` SET `athlete_last_no` = 99 WHERE `id` = 1;

-- 2. Extend athletes table with missing demographic and profile columns directly
ALTER TABLE `athletes` 
    ADD COLUMN IF NOT EXISTS `father_name` VARCHAR(100) NULL DEFAULT NULL AFTER `dob`,
    ADD COLUMN IF NOT EXISTS `mother_name` VARCHAR(100) NULL DEFAULT NULL AFTER `father_name`,
    ADD COLUMN IF NOT EXISTS `age_category` VARCHAR(50) NULL DEFAULT NULL AFTER `mother_name`,
    ADD COLUMN IF NOT EXISTS `impairment_type` VARCHAR(255) NULL DEFAULT NULL AFTER `classification`,
    ADD COLUMN IF NOT EXISTS `address` TEXT NULL DEFAULT NULL AFTER `representing_for`,
    ADD COLUMN IF NOT EXISTS `pincode` VARCHAR(20) NULL DEFAULT NULL AFTER `address`,
    ADD COLUMN IF NOT EXISTS `kit_tshirt` VARCHAR(20) NULL DEFAULT NULL AFTER `wheelchair_status`,
    ADD COLUMN IF NOT EXISTS `kit_tracksuit` VARCHAR(20) NULL DEFAULT NULL AFTER `kit_tshirt`,
    ADD COLUMN IF NOT EXISTS `kit_shoe` VARCHAR(20) NULL DEFAULT NULL AFTER `kit_tracksuit`,
    ADD COLUMN IF NOT EXISTS `passport_file` VARCHAR(255) NULL DEFAULT NULL AFTER `receipt_path`,
    ADD COLUMN IF NOT EXISTS `is_legacy_registry` TINYINT(1) NOT NULL DEFAULT 0;

-- 3. Extend officials table with is_legacy_registry
ALTER TABLE `officials`
    ADD COLUMN IF NOT EXISTS `is_legacy_registry` TINYINT(1) NOT NULL DEFAULT 0;

-- 4. Mark legacy athletes (0001 -> 0099) as legacy registry protected
UPDATE `athletes` 
SET `is_legacy_registry` = 1 
WHERE CAST(`regn_no` AS UNSIGNED) BETWEEN 1 AND 99 AND `regn_no` REGEXP '^[0-9]+$';
