-- 022_unique_nsrs_id.sql
-- Enforce UNIQUE key constraints on nsrs_id column for athletes and officials tables

ALTER TABLE `athletes` ADD UNIQUE KEY `uq_athletes_nsrs_id` (`nsrs_id`);
ALTER TABLE `officials` ADD UNIQUE KEY `uq_officials_nsrs_id` (`nsrs_id`);
