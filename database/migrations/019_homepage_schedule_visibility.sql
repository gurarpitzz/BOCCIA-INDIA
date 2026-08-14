-- Database migration for BSFI dynamic schedules homepage visibility control (v1.0)
-- Adds `show_on_homepage` column to the `schedules` table.

ALTER TABLE `schedules`
ADD COLUMN `show_on_homepage` TINYINT(1) NOT NULL DEFAULT 0;
