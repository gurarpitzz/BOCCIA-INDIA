-- Migration: 014_add_recognition_certificates.sql
-- Seeds the Recognition Certificates document page into the database.

INSERT INTO `document_pages` (`section_slug`, `title`, `subtitle`, `description`, `slug`, `pdf_file`, `sort_order`, `is_published`) VALUES
('about', 'RECOGNITION CERTIFICATES', 'Affiliation', 'Official government and federation recognition certificates issued for the Boccia Sports Federation of India (BSFI).', 'recognition-certificates', 'uploads/documents/Certificate___List_of_governing_body.pdf', 25, 1)
ON DUPLICATE KEY UPDATE
`title` = VALUES(`title`),
`subtitle` = VALUES(`subtitle`),
`description` = VALUES(`description`),
`pdf_file` = VALUES(`pdf_file`),
`section_slug` = VALUES(`section_slug`);
