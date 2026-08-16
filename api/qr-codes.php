<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode(['ok' => false, 'error' => 'auth']);
	exit;
}

$user_id = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
if (!$pdo) {
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'db']);
	exit;
}
xinng_ensure_short_link_tables($pdo);
xinng_ensure_qr_code_tables($pdo);
xinng_ensure_credit_tables($pdo);

function qr_payload(): array {
	$json = json_decode(file_get_contents('php://input'), true);
	return is_array($json) ? $json : $_POST;
}

function qr_row(array $row): array {
	$id = (int)$row['id'];
	$codeColor = $row['code_color'] ?: ($row['foreground_color'] ?? '#000000');
	$bgColor = $row['background_color'] ?: '#FFFFFF';
	return [
		'id' => $id,
		'type' => $row['type'] ?: 'website',
		'title' => $row['title'] ?: ($row['name'] ?? 'QR Code'),
		'destination_url' => $row['destination_url'],
		'back_half' => $row['back_half'],
		'full_short_url' => !empty($row['back_half']) ? xinng_short_url($row['back_half']) : null,
		'qr_image_url' => xinng_qr_image_url_for_row($row),
		'scan_url' => xinng_qr_scan_url($id),
		'scan_count' => (int)($row['scan_count'] ?? 0),
		'created_at' => $row['created_at'] ?? null,
		'status' => $row['status'] ?? 'active',
		'is_profile_qr' => ($row['type'] ?? '') === 'profile_page',
		'design' => [
			'code_color' => $codeColor,
			'background_color' => $bgColor,
			'corner_color' => $row['corner_color'] ?? null,
			'pattern_style' => $row['pattern_style'] ?: 'default',
			'corner_style' => $row['corner_style'] ?: 'square',
			'frame_style' => $row['frame_style'] ?? null,
			'frame_text' => $row['frame_text'] ?? null,
			'logo_path' => $row['logo_path'] ?? null,
			'remove_xinng_logo' => !empty($row['remove_xinng_logo']),
		],
	];
}

function current_qr(PDO $pdo, int $id, int $user_id): ?array {
	$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([$id, $user_id]);
	return $stmt->fetch() ?: null;
}

function ensure_profile_qr(PDO $pdo, int $user_id): ?array {
	$stmt = $pdo->prepare('SELECT id, slug, title FROM pages WHERE user_id = ? ORDER BY id ASC LIMIT 1');
	$stmt->execute([$user_id]);
	$page = $stmt->fetch();
	if (!$page) return null;

	$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE user_id = ? AND type = "profile_page" AND profile_page_id = ? AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([$user_id, (int)$page['id']]);
	$row = $stmt->fetch();
	$destination = xinng_short_url($page['slug']);
	if ($row) {
		$stmt = $pdo->prepare('UPDATE qr_codes SET title = ?, destination_url = ?, page_id = ?, updated_at = NOW() WHERE id = ?');
		$stmt->execute([$page['title'] . ' page QR', $destination, (int)$page['id'], (int)$row['id']]);
		return current_qr($pdo, (int)$row['id'], $user_id);
	}

	$stmt = $pdo->prepare('INSERT INTO qr_codes (user_id, page_id, profile_page_id, type, title, name, destination_url, status, code_color, background_color, pattern_style, corner_style, created_at, updated_at) VALUES (?, ?, ?, "profile_page", ?, ?, ?, "active", "#000000", "#FFFFFF", "default", "square", NOW(), NOW())');
	$title = $page['title'] . ' page QR';
	$stmt->execute([$user_id, (int)$page['id'], (int)$page['id'], $title, $title, $destination]);
	$id = (int)$pdo->lastInsertId();
	$stmt = $pdo->prepare('UPDATE qr_codes SET qr_image_url = ? WHERE id = ?');
	$stmt->execute([xinng_qr_image_url($id, '#000000', '#FFFFFF', $destination), $id]);
	return current_qr($pdo, $id, $user_id);
}

function save_qr_data(PDO $pdo, int $user_id, array $payload, ?array $existing = null): array {
	$title = trim((string)($payload['title'] ?? ($existing['title'] ?? '')));
	if ($title === '') return ['ok' => false, 'error' => 'Title is required.'];
	$type = in_array(($payload['type'] ?? 'website'), ['website','page','custom'], true) ? $payload['type'] : 'website';
	$destination = xinng_validate_destination_url((string)($payload['destination_url'] ?? ($existing['destination_url'] ?? '')));
	if (!$destination['ok']) return $destination;
	$qrId = $existing ? (int)$existing['id'] : null;
	$currentShortLinkId = !empty($existing['short_link_id']) ? (int)$existing['short_link_id'] : null;
	$backHalf = xinng_validate_qr_back_half($pdo, $payload['back_half'] ?? '', $qrId, $currentShortLinkId);
	if (!$backHalf['ok']) return $backHalf;

	$shortLinkId = !empty($backHalf['back_half']) ? ($existing['short_link_id'] ?? null) : null;
	if (!empty($backHalf['back_half'])) {
		if ($shortLinkId) {
			$old = null;
			$stmt = $pdo->prepare('SELECT * FROM short_links WHERE id = ? AND user_id = ? LIMIT 1');
			$stmt->execute([(int)$shortLinkId, $user_id]);
			$old = $stmt->fetch();
			if ($old && rtrim($old['destination_url'], '/') === rtrim($destination['url'], '/')) {
				$stmt = $pdo->prepare('UPDATE short_links SET title = ?, back_half = ?, updated_at = NOW() WHERE id = ?');
				$stmt->execute([$title, $backHalf['back_half'], (int)$shortLinkId]);
			} else {
				if ($old && $old['back_half'] === $backHalf['back_half']) {
					return ['ok' => false, 'error' => 'Changing the destination creates a new short link. Choose a new back-half.'];
				}
				$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
				$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
				$shortLinkId = (int)$pdo->lastInsertId();
			}
		} else {
			$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
			$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
			$shortLinkId = (int)$pdo->lastInsertId();
		}
	}

	$codeColor = xinng_validate_hex_color($payload['code_color'] ?? ($existing['code_color'] ?? '#000000'), '#000000');
	$bgColor = xinng_validate_hex_color($payload['background_color'] ?? ($existing['background_color'] ?? '#FFFFFF'), '#FFFFFF');
	$cornerColor = trim((string)($payload['corner_color'] ?? ($existing['corner_color'] ?? '')));
	$cornerColor = $cornerColor !== '' ? xinng_validate_hex_color($cornerColor, '#000000') : null;
	$pattern = trim((string)($payload['pattern_style'] ?? ($existing['pattern_style'] ?? 'default'))) ?: 'default';
	$corner = trim((string)($payload['corner_style'] ?? ($existing['corner_style'] ?? 'square'))) ?: 'square';
	$frame = trim((string)($payload['frame_style'] ?? ($existing['frame_style'] ?? '')));
	$frameText = trim((string)($payload['frame_text'] ?? ($existing['frame_text'] ?? '')));
	$logoPath = trim((string)($payload['logo_path'] ?? ($existing['logo_path'] ?? '')));
	$removeLogo = !empty($payload['remove_xinng_logo']) ? 1 : 0;

	if ($existing) {
		if (($existing['type'] ?? '') === 'profile_page') return ['ok' => false, 'error' => 'Profile QR cannot be edited from here.'];
		if (xinng_ensure_credit_balance($pdo, $user_id) < 1) {
			return ['ok' => false, 'error' => 'insufficient_credits'];
		}
		$stmt = $pdo->prepare('UPDATE qr_codes SET short_link_id = ?, type = ?, title = ?, name = ?, destination_url = ?, back_half = ?, code_color = ?, background_color = ?, corner_color = ?, pattern_style = ?, corner_style = ?, frame_style = ?, frame_text = ?, logo_path = ?, remove_xinng_logo = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
		$stmt->execute([$shortLinkId, $type, $title, $title, $destination['url'], $backHalf['back_half'], $codeColor, $bgColor, $cornerColor, $pattern, $corner, $frame ?: null, $frameText ?: null, $logoPath ?: null, $removeLogo, (int)$existing['id'], $user_id]);
		$id = (int)$existing['id'];
	} else {
		if (xinng_ensure_credit_balance($pdo, $user_id) < 1) {
			return ['ok' => false, 'error' => 'insufficient_credits'];
		}
		$stmt = $pdo->prepare('INSERT INTO qr_codes (user_id, short_link_id, type, title, name, destination_url, back_half, status, code_color, background_color, corner_color, pattern_style, corner_style, frame_style, frame_text, logo_path, remove_xinng_logo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, "active", ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
		$stmt->execute([$user_id, $shortLinkId, $type, $title, $title, $destination['url'], $backHalf['back_half'], $codeColor, $bgColor, $cornerColor, $pattern, $corner, $frame ?: null, $frameText ?: null, $logoPath ?: null, $removeLogo]);
		$id = (int)$pdo->lastInsertId();
	}

	xinng_charge_credits($pdo, $user_id, 1, 'Create QR code', 'qr:' . $id);
	$stmt = $pdo->prepare('UPDATE qr_codes SET qr_image_url = ? WHERE id = ?');
	$stmt->execute([xinng_qr_image_url($id, $codeColor, $bgColor, $destination['url']), $id]);
	return ['ok' => true, 'qr_code' => qr_row(current_qr($pdo, $id, $user_id))];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = qr_payload();
if ($method === 'POST' && !empty($payload['_method'])) $method = strtoupper((string)$payload['_method']);

try {
	if ($method === 'GET') {
		$id = (int)($_GET['id'] ?? 0);
		if ($id > 0) {
			$row = current_qr($pdo, $id, $user_id);
			if (!$row) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'not_found']); exit; }
			echo json_encode(['ok' => true, 'qr_code' => qr_row($row)]);
			exit;
		}
		$profile = ensure_profile_qr($pdo, $user_id);
		$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE user_id = ? AND type != "profile_page" AND deleted_at IS NULL ORDER BY created_at ASC, id ASC');
		$stmt->execute([$user_id]);
		$rows = [];
		if ($profile) $rows[] = qr_row($profile);
		foreach ($stmt->fetchAll() as $row) $rows[] = qr_row($row);
		echo json_encode(['ok' => true, 'qr_codes' => $rows]);
		exit;
	}

	if (!verify_csrf_token($payload['csrf_token'] ?? null)) {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'csrf']);
		exit;
	}

	if ($method === 'POST') {
		$result = save_qr_data($pdo, $user_id, $payload);
		if (!$result['ok']) http_response_code(422);
		echo json_encode($result);
		exit;
	}

	if ($method === 'PATCH') {
		$id = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
		$row = $id > 0 ? current_qr($pdo, $id, $user_id) : null;
		if (!$row) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'not_found']); exit; }
		$result = save_qr_data($pdo, $user_id, $payload, $row);
		if (!$result['ok']) http_response_code(422);
		echo json_encode($result);
		exit;
	}

	if ($method === 'DELETE') {
		$id = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
		$row = $id > 0 ? current_qr($pdo, $id, $user_id) : null;
		if (!$row) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'not_found']); exit; }
		if (($row['type'] ?? '') === 'profile_page') { http_response_code(422); echo json_encode(['ok' => false, 'error' => 'Profile QR cannot be archived.']); exit; }
		$stmt = $pdo->prepare('UPDATE qr_codes SET status = "archived", deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND user_id = ?');
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
