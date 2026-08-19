-- 020_expand_profile_update_requests.sql
-- Add Kit details, Aadhaar, Impairment Type, and Wheelchair status to profile_update_requests table

ALTER TABLE `profile_update_requests`
    ADD COLUMN IF NOT EXISTS `requested_kit_tshirt` VARCHAR(20) NULL DEFAULT NULL AFTER `requested_pincode`,
    ADD COLUMN IF NOT EXISTS `requested_kit_tracksuit` VARCHAR(20) NULL DEFAULT NULL AFTER `requested_kit_tshirt`,
    ADD COLUMN IF NOT EXISTS `requested_kit_shoe` VARCHAR(20) NULL DEFAULT NULL AFTER `requested_kit_tracksuit`,
    ADD COLUMN IF NOT EXISTS `requested_aadhaar` VARCHAR(20) NULL DEFAULT NULL AFTER `requested_kit_shoe`,
    ADD COLUMN IF NOT EXISTS `requested_impairment_type` VARCHAR(255) NULL DEFAULT NULL AFTER `requested_aadhaar`,
    ADD COLUMN IF NOT EXISTS `requested_wheelchair_status` VARCHAR(50) NULL DEFAULT NULL AFTER `requested_impairment_type`;
