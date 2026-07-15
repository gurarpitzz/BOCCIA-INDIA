-- ==================================================================================
-- WARNING: This migration is intended for local development and testing environments only.
-- It shall never be executed against staging or production databases.
-- Its sole purpose is to restore developer test data back to a production-like state.
-- ==================================================================================

-- Revert the emails of legacy players 0098 and 0099 back to NULL
UPDATE athletes SET email = NULL WHERE regn_no IN ('0098', '0099');
