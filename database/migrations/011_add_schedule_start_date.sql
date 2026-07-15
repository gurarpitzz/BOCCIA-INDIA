-- migration: add start_date to schedules for automatic chronological sorting
ALTER TABLE `schedules` ADD COLUMN `start_date` DATE DEFAULT NULL;

-- Initialize existing schedules based on date_text
UPDATE `schedules` SET `start_date` = '2026-06-07' WHERE `id` = 1;
UPDATE `schedules` SET `start_date` = '2026-10-01' WHERE `id` = 2;
UPDATE `schedules` SET `start_date` = '2027-01-01' WHERE `id` = 3;
UPDATE `schedules` SET `start_date` = '2026-08-25' WHERE `id` IN (5, 6, 7);
UPDATE `schedules` SET `start_date` = CURRENT_DATE WHERE `start_date` IS NULL;

-- Make it NOT NULL
ALTER TABLE `schedules` MODIFY COLUMN `start_date` DATE NOT NULL;
