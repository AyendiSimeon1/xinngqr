<?php
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
$slug_raw = trim($_GET['slug'] ?? '');
if ($slug_raw === '') {
    echo json_encode(['ok' => false, 'error' => 'empty']);
    exit;
}

$make_slug = function($s){
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim($s, '-');
    if ($s === '') return null;
    return $s;
};

$slug = $make_slug($slug_raw);
if ($slug === null) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['ok' => false, 'error' => 'db']);
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
$stmt->execute([$slug]);
$cnt = (int)$stmt->fetchColumn();

echo json_encode(['ok' => true, 'slug' => $slug, 'available' => $cnt === 0]);
