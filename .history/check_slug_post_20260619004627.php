<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$slug_raw = trim($_POST['slug'] ?? '');
$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) {
    echo json_encode(['ok' => false, 'error' => 'csrf']); exit;
}
// simple rate limit: max 10 checks per 60s per session
$_SESSION['slug_checks'] = array_filter($_SESSION['slug_checks'] ?? [], function($t){ return $t > time() - 60; });
if (count($_SESSION['slug_checks']) >= 10) {
    echo json_encode(['ok' => false, 'error' => 'rate_limited']); exit;
}
$_SESSION['slug_checks'][] = time();

$slug = slugify($slug_raw);
if ($slug === null) { echo json_encode(['ok' => false, 'error' => 'invalid']); exit; }

$pdo = get_db_connection();
if (!$pdo) { echo json_encode(['ok' => false, 'error' => 'db']); exit; }

$stmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
$stmt->execute([$slug]);
$cnt = (int)$stmt->fetchColumn();
echo json_encode(['ok' => true, 'slug' => $slug, 'available' => $cnt === 0]);
