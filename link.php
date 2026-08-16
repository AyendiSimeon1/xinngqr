<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	http_response_code(404);
	echo 'Link not found';
	exit;
}

$pdo = get_db_connection();
if (!$pdo) {
	http_response_code(500);
	echo 'Database connection unavailable';
	exit;
}

$stmt = $pdo->prepare('SELECT pb.destination_url, pb.page_id, p.is_published FROM page_blocks pb JOIN pages p ON p.id = pb.page_id WHERE pb.id = ? AND pb.deleted_at IS NULL LIMIT 1');
$stmt->execute([$id]);
$block = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$block || empty($block['destination_url']) || empty($block['is_published'])) {
	http_response_code(404);
	echo 'Link not found';
	exit;
}

$destination = trim((string)$block['destination_url']);
if ($destination === '') {
	http_response_code(404);
	echo 'Link not found';
	exit;
}

try {
	xinng_record_link_click($pdo, (int)$block['page_id'], $id, null);
} catch (Throwable $e) {
	error_log($e->getMessage());
}

header('Location: ' . $destination, true, 302);
exit;
