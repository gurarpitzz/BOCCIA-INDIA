-- Clean up test athlete records 100 and 101, and reset the registration sequence.
DELETE FROM athlete_status_history WHERE athlete_id IN (SELECT id FROM athletes WHERE CAST(regn_no AS UNSIGNED) > 99);
DELETE FROM athletes WHERE CAST(regn_no AS UNSIGNED) > 99;
UPDATE registration_sequences SET athlete_last_no = 99 WHERE id = 1;
