-- 019_add_documents_to_profile_update_requests.sql
-- Add passport_file and medical_certificate requested columns to profile_update_requests table

ALTER TABLE `profile_update_requests`
    ADD COLUMN IF NOT EXISTS `requested_passport_file` VARCHAR(255) NULL DEFAULT NULL AFTER `requested_photo_path`,
    ADD COLUMN IF NOT EXISTS `requested_medical_certificate` VARCHAR(255) NULL DEFAULT NULL AFTER `requested_passport_file`;
