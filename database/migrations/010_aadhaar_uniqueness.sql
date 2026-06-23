-- 010_aadhaar_uniqueness.sql - Apply database level Aadhaar uniqueness constraint
ALTER TABLE `athletes` ADD UNIQUE KEY `uq_athletes_aadhaar` (`aadhaar`);
ALTER TABLE `officials` ADD UNIQUE KEY `uq_officials_aadhaar` (`aadhaar`);
