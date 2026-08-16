<?php
require_once __DIR__ . '/config.php';

$back_half = xinng_normalize_back_half($_GET['back_half'] ?? '');
if ($back_half === null) {
	http_response_code(404);
	echo 'Not found';
	exit;
}

$pdo = get_db_connection();
if (!$pdo) {
	http_response_code(500);
	echo 'Database connection unavailable';
	exit;
}

xinng_ensure_short_link_tables($pdo);

$stmt = $pdo->prepare('SELECT id, user_id, destination_url FROM short_links WHERE back_half = ? AND status = "active" AND deleted_at IS NULL LIMIT 1');
$stmt->execute([$back_half]);
$shortLink = $stmt->fetch();

if ($shortLink) {
	try {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$ipHash = $ip !== '' ? hash('sha256', $ip) : null;
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
		$referer = $_SERVER['HTTP_REFERER'] ?? null;

		$stmt = $pdo->prepare('INSERT INTO short_link_clicks (short_link_id, user_id, ip_hash, user_agent, referer, clicked_at) VALUES (?, ?, ?, ?, ?, NOW())');
		$stmt->execute([(int)$shortLink['id'], (int)$shortLink['user_id'], $ipHash, $userAgent, $referer]);

		$stmt = $pdo->prepare('UPDATE short_links SET click_count = click_count + 1, updated_at = NOW() WHERE id = ?');
		$stmt->execute([(int)$shortLink['id']]);
	} catch (PDOException $e) {
		error_log($e->getMessage());
	}

	header('Location: ' . $shortLink['destination_url'], true, 302);
	exit;
}

$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$back_half]);
$page = $stmt->fetch();
if ($page) {
	$_GET['slug'] = $back_half;
	$blocks = [];
	$stmtBlocks = $pdo->prepare('SELECT id, title, description, type, destination_url FROM page_blocks WHERE page_id = ? AND deleted_at IS NULL ORDER BY position ASC, id ASC');
	$stmtBlocks->execute([(int)$page['id']]);
	$blocks = $stmtBlocks->fetchAll(PDO::FETCH_ASSOC) ?: [];
	require __DIR__ . '/public_page.php';
	exit;
}

http_response_code(404);
echo 'Not found';
