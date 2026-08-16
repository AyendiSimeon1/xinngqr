<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	http_response_code(404);
	echo 'QR code not found';
	exit;
}

$pdo = get_db_connection();
if (!$pdo) {
	http_response_code(500);
	echo 'Database connection unavailable';
	exit;
}
xinng_ensure_short_link_tables($pdo);
xinng_ensure_qr_code_tables($pdo);
xinng_ensure_tracking_tables($pdo);

$stmt = $pdo->prepare('SELECT q.*, p.slug AS profile_slug FROM qr_codes q LEFT JOIN pages p ON p.id = q.profile_page_id WHERE q.id = ? AND q.status = "active" AND q.deleted_at IS NULL LIMIT 1');
$stmt->execute([$id]);
$qr = $stmt->fetch();
if (!$qr) {
	http_response_code(404);
	echo 'QR code not found';
	exit;
}

try {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$ipHash = $ip !== '' ? hash('sha256', $ip) : null;
	$stmt = $pdo->prepare('INSERT INTO qr_code_scans (qr_code_id, user_id, ip_hash, user_agent, referer, scanned_at) VALUES (?, ?, ?, ?, ?, NOW())');
	$stmt->execute([$id, $qr['user_id'] ? (int)$qr['user_id'] : null, $ipHash, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null]);
	$stmt = $pdo->prepare('UPDATE qr_codes SET scan_count = scan_count + 1, updated_at = NOW() WHERE id = ?');
	$stmt->execute([$id]);
	if (!empty($qr['page_id']) || !empty($qr['profile_page_id'])) {
		$trackedPageId = !empty($qr['page_id']) ? (int)$qr['page_id'] : (int)$qr['profile_page_id'];
		xinng_record_qr_scan($pdo, $id, $trackedPageId);
	}
} catch (PDOException $e) {
	error_log($e->getMessage());
}

if (($qr['type'] ?? '') === 'profile_page' && !empty($qr['profile_slug'])) {
	header('Location: ' . xinng_short_url($qr['profile_slug']), true, 302);
	exit;
}

if (!empty($qr['destination_url'])) {
	header('Location: ' . $qr['destination_url'], true, 302);
	exit;
}

if (!empty($qr['short_link_id'])) {
	$stmt = $pdo->prepare('SELECT destination_url FROM short_links WHERE id = ? AND status = "active" AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([(int)$qr['short_link_id']]);
	$destination = $stmt->fetchColumn();
	if ($destination) {
		header('Location: ' . $destination, true, 302);
		exit;
	}
}

http_response_code(404);
echo 'QR destination unavailable';
