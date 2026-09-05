-- 025_add_requested_state_to_profile_update_requests.sql
-- Add requested_state column to profile_update_requests table

ALTER TABLE `profile_update_requests`
    ADD COLUMN IF NOT EXISTS `requested_state` VARCHAR(100) NULL DEFAULT NULL AFTER `requested_pincode`;
