<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false, 'error' => 'auth']); exit; }
$user_id = (int)$_SESSION['user_id'];
$page_id = (int)($_POST['page_id'] ?? 0);
$new_slug_raw = trim($_POST['slug'] ?? '');
$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) { echo json_encode(['ok' => false, 'error' => 'csrf']); exit; }
if ($page_id <= 0 || $new_slug_raw === '') { echo json_encode(['ok' => false, 'error' => 'invalid']); exit; }

$new_slug = slugify($new_slug_raw);
if ($new_slug === null) { echo json_encode(['ok' => false, 'error' => 'invalid_slug']); exit; }

$pdo = get_db_connection();
if (!$pdo) { echo json_encode(['ok' => false, 'error' => 'db']); exit; }

// verify ownership
$stmt = $pdo->prepare('SELECT user_id FROM pages WHERE id = ? LIMIT 1');
$stmt->execute([$page_id]);
$owner = $stmt->fetchColumn();
if (!$owner || (int)$owner !== $user_id) { echo json_encode(['ok' => false, 'error' => 'forbidden']); exit; }

// check unique excluding current page
$stmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?');
$stmt->execute([$new_slug, $page_id]);
if ((int)$stmt->fetchColumn() > 0) { echo json_encode(['ok' => false, 'error' => 'taken']); exit; }

// update
$stmt = $pdo->prepare('UPDATE pages SET slug = ?, updated_at = NOW() WHERE id = ?');
try {
    $stmt->execute([$new_slug, $page_id]);
    echo json_encode(['ok' => true, 'slug' => $new_slug]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'db']);
}
