<?php
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'bad_request']);
    exit;
}

$packageId = trim((string) ($_POST['package'] ?? ''));
$package = xinng_credit_package($packageId);
if (!$package) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_package']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server']);
    exit;
}

xinng_ensure_credit_tables($pdo);
$stmt = $pdo->prepare('SELECT email FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || empty($user['email'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

$amount = (int)$package['price'];
$reference = 'xinng-' . $user_id . '-' . time() . '-' . bin2hex(random_bytes(4));

$stmt = $pdo->prepare('INSERT INTO credit_transactions (user_id, type, amount, reason, reference, payment_gateway, payment_amount, payment_currency, status, created_at) VALUES (?, "purchase", ?, "Pending Paystack purchase", ?, "paystack", ?, "NGN", "pending", NOW())');
$stmt->execute([$user_id, $amount, $reference, $amount]);

// Return payment details for Paystack inline checkout.
echo json_encode([
    'ok' => true,
    'reference' => $reference,
    'amount' => $amount * 100,
]);
exit;
