<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['ok' => false, 'error' => 'auth']);
	exit;
}

$user_id = (int) $_SESSION['user_id'];
$pdo = get_db_connection();
if (!$pdo) {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'db']);
	exit;
}

xinng_ensure_short_link_tables($pdo);
xinng_ensure_credit_tables($pdo);

function short_link_payload(): array {
	$raw = file_get_contents('php://input');
	$json = json_decode($raw, true);
	if (is_array($json)) return $json;
	return $_POST;
}

function short_link_row(array $row): array {
	return [
		'id' => (int) $row['id'],
		'title' => $row['title'],
		'destination_url' => $row['destination_url'],
		'back_half' => $row['back_half'],
		'full_short_url' => xinng_short_url($row['back_half']),
		'status' => $row['status'],
		'click_count' => (int) ($row['click_count'] ?? 0),
		'created_at' => $row['created_at'] ?? null,
		'updated_at' => $row['updated_at'] ?? null,
	];
}

function current_user_short_link(PDO $pdo, int $id, int $user_id): ?array {
	$stmt = $pdo->prepare('SELECT * FROM short_links WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([$id, $user_id]);
	$row = $stmt->fetch();
	return $row ?: null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = short_link_payload();
if ($method === 'POST' && !empty($payload['_method'])) {
	$method = strtoupper((string) $payload['_method']);
}

try {
	if ($method === 'GET') {
		$stmt = $pdo->prepare('SELECT id, title, destination_url, back_half, status, click_count, created_at, updated_at FROM short_links WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC, id ASC');
		$stmt->execute([$user_id]);
		echo json_encode(['ok' => true, 'short_links' => array_map('short_link_row', $stmt->fetchAll())]);
		exit;
	}

	if (!verify_csrf_token($payload['csrf_token'] ?? null)) {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'csrf']);
		exit;
	}

	if ($method === 'POST') {
		$title = trim((string)($payload['title'] ?? ''));
		$destination = xinng_validate_destination_url((string)($payload['destination_url'] ?? ''));
		$backHalf = xinng_validate_back_half($pdo, (string)($payload['back_half'] ?? ''));
		if (!$destination['ok']) {
			http_response_code(422);
			echo json_encode(['ok' => false, 'error' => $destination['error']]);
			exit;
		}
		if (!$backHalf['ok']) {
			http_response_code(422);
			echo json_encode(['ok' => false, 'error' => $backHalf['error']]);
			exit;
		}
		if ($title === '') $title = $backHalf['back_half'];
		if (xinng_ensure_credit_balance($pdo, $user_id) < 1) {
			http_response_code(402);
			echo json_encode(['ok' => false, 'error' => 'insufficient_credits']);
			exit;
		}

		$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
		$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
		xinng_charge_credits($pdo, $user_id, 1, 'Create short link', 'short-link:' . $pdo->lastInsertId());
		$row = current_user_short_link($pdo, (int)$pdo->lastInsertId(), $user_id);
		echo json_encode(['ok' => true, 'short_link' => short_link_row($row)]);
		exit;
	}

	if ($method === 'PATCH') {
		$id = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
		$row = $id > 0 ? current_user_short_link($pdo, $id, $user_id) : null;
		if (!$row) {
			http_response_code(404);
			echo json_encode(['ok' => false, 'error' => 'not_found']);
			exit;
		}

		$title = trim((string)($payload['title'] ?? $row['title']));
		$requestedDestination = (string)($payload['destination_url'] ?? $row['destination_url']);
		$destination = xinng_validate_destination_url($requestedDestination);
		if (!$destination['ok']) {
			http_response_code(422);
			echo json_encode(['ok' => false, 'error' => $destination['error']]);
			exit;
		}

		$destinationChanged = rtrim($destination['url'], '/') !== rtrim((string)$row['destination_url'], '/');
		if ($destinationChanged && empty($payload['confirm_create_new'])) {
			http_response_code(409);
			echo json_encode([
				'ok' => false,
				'requires_confirmation' => true,
				'message' => 'Changing the destination URL will create a new short link so analytics stay accurate. Continue?'
			]);
			exit;
		}

		if ($destinationChanged) {
			$backHalf = xinng_validate_back_half($pdo, (string)($payload['back_half'] ?? ''));
			if (!$backHalf['ok']) {
				http_response_code(422);
				echo json_encode(['ok' => false, 'error' => $backHalf['error']]);
				exit;
			}
			if ($title === '') $title = $backHalf['back_half'];
			if (xinng_ensure_credit_balance($pdo, $user_id) < 1) {
				http_response_code(402);
				echo json_encode(['ok' => false, 'error' => 'insufficient_credits']);
				exit;
			}
			$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
			$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
			xinng_charge_credits($pdo, $user_id, 1, 'Create short link', 'short-link:' . $pdo->lastInsertId());
			$newRow = current_user_short_link($pdo, (int)$pdo->lastInsertId(), $user_id);
			echo json_encode(['ok' => true, 'created_new' => true, 'short_link' => short_link_row($newRow)]);
			exit;
		}

		$backHalf = xinng_validate_back_half($pdo, (string)($payload['back_half'] ?? $row['back_half']), $id);
		if (!$backHalf['ok']) {
			http_response_code(422);
			echo json_encode(['ok' => false, 'error' => $backHalf['error']]);
			exit;
		}
		if ($title === '') $title = $backHalf['back_half'];
		$stmt = $pdo->prepare('UPDATE short_links SET title = ?, back_half = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
		$stmt->execute([$title, $backHalf['back_half'], $id, $user_id]);
		echo json_encode(['ok' => true, 'short_link' => short_link_row(current_user_short_link($pdo, $id, $user_id))]);
		exit;
	}

	if ($method === 'DELETE') {
		$id = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
		$row = $id > 0 ? current_user_short_link($pdo, $id, $user_id) : null;
		if (!$row) {
			http_response_code(404);
			echo json_encode(['ok' => false, 'error' => 'not_found']);
			exit;
		}
		$stmt = $pdo->prepare('UPDATE short_links SET status = "archived", deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND user_id = ?');
		$stmt->execute([$id, $user_id]);
		echo json_encode(['ok' => true]);
		exit;
	}

	http_response_code(405);
	echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
} catch (PDOException $e) {
	error_log($e->getMessage());
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'db']);
}
