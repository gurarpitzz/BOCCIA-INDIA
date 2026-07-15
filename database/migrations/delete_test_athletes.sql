-- Deletes all test athlete records created with a registration number greater than 99.
DELETE FROM athletes WHERE CAST(regn_no AS UNSIGNED) > 99;
