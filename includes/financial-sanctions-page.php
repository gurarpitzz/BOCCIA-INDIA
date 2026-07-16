<?php
// includes/financial-sanctions-page.php - Custom Template for MYAS Financial Sanctions
$page_title = "Financial Sanctions | Boccia India";
$meta_desc = "Official financial sanction details issued by the Ministry of Youth Affairs and Sports (MYAS) for Para Boccia events.";
$canonical_url = "page.php?section=myas&slug=financial-sanctions";

include __DIR__ . '/header.php';

// Prepare variables for document-view-page template
$doc_title = "FINANCIAL SANCTIONS";
$doc_subtitle = "MYAS Disclosures";
$doc_desc = "Official financial sanction logs and compliance records approved by the Ministry of Youth Affairs and Sports.";
$pdf_file = "uploads/documents/FINANCIAL_SANCTION_FCC-67_OF_PARA_BOCCIA.pdf";
$doc_date = "2022-2023";
$doc_dept = "Ministry of Youth Affairs & Sports (MYAS)";
$doc_type = "Financial Sanction Record (FCC-67)";

include __DIR__ . '/document-view-page.php';
include __DIR__ . '/footer.php';
?>
