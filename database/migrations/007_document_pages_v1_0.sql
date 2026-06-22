-- Database migration for BSFI Document Pages Standardization (v1.0)
-- Creates the `document_pages` table and seeds default document-viewing templates.

CREATE TABLE IF NOT EXISTS `document_pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_slug` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `pdf_file` VARCHAR(255) NOT NULL,
    `hero_image` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default document pages
INSERT INTO `document_pages` (`section_slug`, `title`, `subtitle`, `description`, `slug`, `pdf_file`, `sort_order`, `is_published`) VALUES
('about', 'PARALYMPIC COMMITTEE OF INDIA', 'Affiliation', 'Official recognition and affiliation documentation of BSFI with the Paralympic Committee of India (PCI).', 'affiliation-pci', 'uploads/documents/Affiliation_with_PCI.pdf', 10, 1),
('about', 'WORLD BOCCIA', 'Affiliation', 'Official recognition and affiliation documentation of BSFI with World Boccia.', 'affiliation-world-boccia', 'uploads/documents/Affiliation_with_World_Boccia.pdf', 20, 1),
('selection-guidelines', 'SELECTION POLICY', 'Selection Guidelines', 'Official BSFI athlete selection policy, eligibility requirements, evaluation methodology, and competition participation framework.', 'selection-policy', 'uploads/documents/Selection-Policy-___-Boccia-Asian-Para-Games-2026.pdf', 30, 1),
('selection-guidelines', 'BOCCIA ASIAN PARA GAMES 2026', 'Selection Guidelines', 'Official selection criteria, qualification process, athlete requirements and timelines for the Boccia Asian Para Games 2026.', 'apg-2026', 'uploads/documents/Selection-Policy-___-Boccia-Asian-Para-Games-2026.pdf', 40, 1),
('selection-guidelines', 'SELECTION TRIALS APG 2026', 'Selection Guidelines', 'Official trial schedules, athlete eligibility, evaluation standards and guidelines for APG 2026 selection events.', 'apg-trials-2026', 'uploads/documents/Selection-trails-APG-2026-1.pdf', 50, 1),
('news-media', 'BSFI TENDER', 'Tender Notice', 'Official procurement notices, quotations, vendor requirements and federation tender documents.', 'tenders', 'uploads/documents/BSFI-Tender-1-1.pdf', 60, 1)
ON DUPLICATE KEY UPDATE
`title` = VALUES(`title`),
`subtitle` = VALUES(`subtitle`),
`description` = VALUES(`description`),
`pdf_file` = VALUES(`pdf_file`),
`section_slug` = VALUES(`section_slug`);
