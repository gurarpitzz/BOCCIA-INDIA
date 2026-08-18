-- 022_unique_nsrs_id.sql
-- Step 1: Convert empty/blank strings and duplicate test entries to NULL so MySQL UNIQUE index permits them
UPDATE `athletes` SET `nsrs_id` = NULL WHERE `nsrs_id` = '' OR TRIM(`nsrs_id`) = '';
UPDATE `officials` SET `nsrs_id` = NULL WHERE `nsrs_id` = '' OR TRIM(`nsrs_id`) = '';

-- Step 2: Enforce UNIQUE key constraints on nsrs_id column for athletes and officials tables
ALTER TABLE `athletes` ADD UNIQUE KEY `uq_athletes_nsrs_id` (`nsrs_id`);
ALTER TABLE `officials` ADD UNIQUE KEY `uq_officials_nsrs_id` (`nsrs_id`);
