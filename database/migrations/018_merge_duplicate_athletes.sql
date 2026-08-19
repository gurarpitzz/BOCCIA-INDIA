-- 018_merge_duplicate_athletes.sql
-- Merge parent details from duplicate un-padded regn_no rows into legacy padded rows (0001 to 0099)
-- and delete un-padded duplicate athlete records (1 to 99)

UPDATE athletes target
JOIN athletes source 
    ON CAST(target.regn_no AS UNSIGNED) = CAST(source.regn_no AS UNSIGNED)
   AND target.regn_no REGEXP '^[0-9]{4}$'
   AND source.regn_no NOT REGEXP '^[0-9]{4}$'
SET 
    target.father_name = COALESCE(target.father_name, source.father_name),
    target.mother_name = COALESCE(target.mother_name, source.mother_name);

-- Delete history attached to duplicate un-padded rows
DELETE FROM athlete_history WHERE athlete_id IN (
    SELECT id FROM athletes WHERE regn_no NOT REGEXP '^[0-9]{4}$' AND CAST(regn_no AS UNSIGNED) BETWEEN 1 AND 99
);

-- Delete duplicate un-padded athlete rows (1 to 99)
DELETE FROM athletes WHERE regn_no NOT REGEXP '^[0-9]{4}$' AND CAST(regn_no AS UNSIGNED) BETWEEN 1 AND 99;
