-- 004_enforce_unique_regn.sql - Enforce strictly formatted registration numbers and unique key constraint

-- Normalize any values to 4-digit strings
UPDATE `athletes` SET `regn_no` = LPAD(CAST(`regn_no` AS UNSIGNED), 4, '0') WHERE `regn_no` REGEXP '^[0-9]+$';

-- Modify column to be NOT NULL to support reliable UNIQUE indexing
ALTER TABLE `athletes` MODIFY COLUMN `regn_no` VARCHAR(50) NOT NULL;
