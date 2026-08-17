-- 021_add_nsrs_id.sql
-- Add NSRS ID column to athletes and officials tables

ALTER TABLE `athletes` ADD COLUMN `nsrs_id` VARCHAR(100) NULL DEFAULT NULL AFTER `regn_no`;
ALTER TABLE `officials` ADD COLUMN `nsrs_id` VARCHAR(100) NULL DEFAULT NULL AFTER `official_reg_no`;
