<?php
// includes/document_renderer.php - Universal Document & Asset Renderer

class DocumentRenderer {
    public static function render($filePath, $mimeType = null) {
        if (empty($filePath)) {
            return "<p class='text-muted'>No file specified.</p>";
        }

        $fullPath = __DIR__ . '/../' . $filePath;
        if (!file_exists($fullPath)) {
            // Check fallback for subfolders or paths starting with /
            $fullPath = __DIR__ . '/..' . $filePath;
            if (!file_exists($fullPath)) {
                return "<div class='alert alert-warning'>Document file not found: " . htmlspecialchars(basename($filePath)) . "</div>";
            }
        }

        if (empty($mimeType)) {
            $mimeType = mime_content_type($fullPath);
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $downloadUrl = htmlspecialchars($filePath);
        $filename = htmlspecialchars(basename($filePath));

        $html = "<div class='document-viewer-container mb-4 p-3 border rounded bg-white shadow-sm'>";
        $html .= "<div class='d-flex justify-content-between align-items-center mb-3 border-bottom pb-2'>";
        $html .= "<h5 class='text-primary m-0'><i class='bi bi-file-earmark'></i> $filename</h5>";
        $html .= "<a href='$downloadUrl' download class='btn btn-sm btn-outline-primary'><svg viewBox='0 0 24 24' width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' style='vertical-align:-3px; margin-right:3px;'><path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'></path><polyline points='7 10 12 15 17 10'></polyline><line x1='12' y1='15' x2='12' y2='3'></line></svg> Download</a>";
        $html .= "</div>";

        if ($ext === 'pdf' || $mimeType === 'application/pdf') {
            // Inline PDF Embed
            $html .= "<div class='ratio ratio-16x9' style='height: 600px;'>";
            $html .= "<object data='$downloadUrl' type='application/pdf' width='100%' height='100%'>";
            $html .= "<p>Your browser does not support inline PDFs. <a href='$downloadUrl'>Download PDF</a> instead.</p>";
            $html .= "</object>";
            $html .= "</div>";
        } elseif ($ext === 'csv' || $mimeType === 'text/csv' || $mimeType === 'text/plain' && $ext === 'csv') {
            // Render CSV as interactive HTML table
            $html .= self::renderCsvToTable($fullPath);
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) || strpos($mimeType, 'image/') !== false) {
            // Lazy load Image
            $html .= "<div class='text-center'>";
            $html .= "<img src='$downloadUrl' class='img-fluid rounded shadow-sm' style='max-height: 500px;' alt='$filename' loading='lazy'>";
            $html .= "</div>";
        } elseif (in_array($ext, ['mp4', 'webm', 'ogg']) || strpos($mimeType, 'video/') !== false) {
            // Video Player
            $html .= "<div class='ratio ratio-16x9'>";
            $html .= "<video controls class='w-100 rounded' style='max-height: 500px;'>";
            $html .= "<source src='$downloadUrl' type='$mimeType'>";
            $html .= "Your browser does not support the video tag.";
            $html .= "</video>";
            $html .= "</div>";
        } else {
            // Fallback for docx / binary / other files
            $html .= "<div class='p-4 text-center bg-light rounded'>";
            $html .= "<p class='mb-3 text-muted'>Preview is not available for this file type ($ext).</p>";
            $html .= "<a href='$downloadUrl' class='btn btn-primary' download>Download and Open File</a>";
            $html .= "</div>";
        }

        $html .= "</div>";
        return $html;
    }

    private static function renderCsvToTable($fullPath) {
        if (($handle = fopen($fullPath, "r")) === FALSE) {
            return "<p class='text-danger'>Unable to open CSV data.</p>";
        }

        $html = "<div class='table-responsive' style='max-height: 500px; overflow-y: auto;'>";
        $html .= "<table class='table table-striped table-hover table-bordered mb-0' style='font-size: 0.9rem;'>";
        
        $isHeader = true;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $html .= "<tr>";
            foreach ($data as $cell) {
                $cleanCell = htmlspecialchars(trim($cell), ENT_QUOTES, 'UTF-8');
                if ($isHeader) {
                    $html .= "<th class='bg-primary text-white' style='position: sticky; top: 0;'>" . $cleanCell . "</th>";
                } else {
                    $html .= "<td>" . $cleanCell . "</td>";
                }
            }
            $html .= "</tr>";
            $isHeader = false;
        }
        fclose($handle);

        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }
}
