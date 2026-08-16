<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$slug_raw = trim($_POST['slug'] ?? '');
// The slug availability check is a lightweight lookup and should not block valid inputs
// with a stale or missing CSRF token from a browser AJAX request.
// simple rate limit: max 10 checks per 60s per session
$_SESSION['slug_checks'] = array_filter($_SESSION['slug_checks'] ?? [], function($t){ return $t > time() - 60; });
if (count($_SESSION['slug_checks']) >= 10) {
    echo json_encode(['ok' => false, 'error' => 'rate_limited']); exit;
}
$_SESSION['slug_checks'][] = time();

if ($slug_raw === '') {
    echo json_encode(['ok' => true, 'slug' => '', 'available' => true]);
    exit;
}

$pdo = get_db_connection();
if (!$pdo) { echo json_encode(['ok' => false, 'error' => 'db']); exit; }

xinng_ensure_page_builder_tables($pdo);
xinng_ensure_short_link_tables($pdo);

$slugCheck = xinng_validate_page_slug($pdo, $slug_raw);
if (!$slugCheck['ok']) {
    echo json_encode(['ok' => false, 'error' => $slugCheck['error']]);
    exit;
}

echo json_encode(['ok' => true, 'slug' => $slugCheck['slug'], 'available' => true]);
