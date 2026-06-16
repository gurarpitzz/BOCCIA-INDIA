<?php
// save-news.php - Admin / Editor news management writer

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Access restricted.']);
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$featured = isset($_POST['featured']) ? (int)$_POST['featured'] : 0;
$pinned = isset($_POST['pinned']) ? (int)$_POST['pinned'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
$image = isset($_POST['image']) ? trim($_POST['image']) : '';

if (empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Required fields (Title, Content) are missing.']);
    exit();
}

try {
    $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE news SET title=?, excerpt=?, content=?, featured=?, pinned=?, status=?, image=?, published_at=COALESCE(?, published_at) WHERE id=?");
        $stmt->execute([$title, $excerpt, $content, $featured, $pinned, $status, $image, $published_at, $id]);
        logAction($pdo, "Updated News", "news", $id, "Title: $title");
        echo json_encode(['success' => 'News updated successfully.']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO news (title, excerpt, content, featured, pinned, status, image, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $excerpt, $content, $featured, $pinned, $status, $image, $published_at]);
        $newId = $pdo->lastInsertId();
        logAction($pdo, "Created News", "news", $newId, "Title: $title");
        echo json_encode(['success' => 'News created successfully.', 'id' => $newId]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
