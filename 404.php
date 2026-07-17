<?php
// 404.php - Premium Branded 404 Page Not Found
http_response_code(404);

$page_title = "404 - Page Not Found | Boccia India";
$meta_desc = "The requested page was not found on the Boccia Sports Federation of India portal.";

require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5 py-5 text-center" style="min-height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <h1 class="display-1 fw-bold" style="color: #081B4B; font-family: var(--font-heading); font-size: 6rem; line-height: 1;">404</h1>
    <h2 class="fw-bold mt-2" style="color: #FF9933; font-family: var(--font-heading);">Page Not Found</h2>
    <p class="text-muted mt-3" style="max-width: 500px; font-size: 1.1rem;">
        The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
    </p>
    <a href="index.php" style="background: #081B4B; color: #ffffff; padding: 0.75rem 2rem; border-radius: 999px; font-weight: bold; text-decoration: none; display: inline-block; transition: background 0.2s; border: none; margin-top: 1.5rem;" onmouseover="this.style.background='#FF9933'" onmouseout="this.style.background='#081B4B'">Return to Homepage</a>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
