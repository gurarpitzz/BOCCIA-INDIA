-- Delete existing records for registration numbers 0098 and 0099
DELETE FROM athletes WHERE regn_no IN ('0098', '0099');

-- Insert Test Player One
INSERT INTO athletes 
(regn_no, full_name, gender, dob, email, state, representing_for, classification, status, photo_status) 
VALUES 
('0098', 'Test Player One', 'MALE', '1995-05-15', 'gurarpit.sml@gmail.com', 'Punjab', 'Punjab', 'BC1', 'approved', 'verified');

-- Insert Test Player Two
INSERT INTO athletes 
(regn_no, full_name, gender, dob, email, state, representing_for, classification, status, photo_status) 
VALUES 
('0099', 'Test Player Two', 'FEMALE', '1997-08-20', 'mehardeep.sim@gmail.com', 'Punjab', 'Punjab', 'BC2', 'approved', 'verified');
