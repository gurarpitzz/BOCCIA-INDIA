-- 024_fix_regn_no_100_sequence.sql
-- Fix registration number gap after 0099 by re-assigning athlete 0101 (Gurarpit Singh) to 0100 
-- and setting the sequence counter to 100.

START TRANSACTION;

-- Reassign athlete 0101 (Gurarpit Singh) to 0100
UPDATE athletes 
SET regn_no = '0100' 
WHERE regn_no = '0101';

-- Update sequence counter
UPDATE registration_sequences 
SET athlete_last_no = 100 
WHERE id = 1;

COMMIT;
