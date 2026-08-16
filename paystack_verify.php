<?php
require_once __DIR__ . '/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$reference = trim((string)($_GET['reference'] ?? ''));
$packageId = trim((string)($_GET['package'] ?? ''));
$package = xinng_credit_package($packageId);

if ($reference === '' || !$package) {
    header('Location: credits.php?error=invalid_package');
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    header('Location: credits.php?error=server');
    exit;
}

xinng_ensure_credit_tables($pdo);
$stmt = $pdo->prepare('SELECT * FROM credit_transactions WHERE user_id = ? AND reference = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$user_id, $reference]);
$txn = $stmt->fetch();
if (!$txn) {
    header('Location: credits.php?error=notfound');
    exit;
}

if (empty(PAYSTACK_SECRET_KEY)) {
    header('Location: credits.php?error=missing_secret');
    exit;
}

$verification = null;
$ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
    'Content-Type: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    header('Location: credits.php?error=verification_failed');
    exit;
}

$verification = json_decode($response, true);
if (empty($verification['status']) || !$verification['status'] || empty($verification['data']) || ($verification['data']['status'] ?? '') !== 'success') {
    $stmt = $pdo->prepare('UPDATE credit_transactions SET status = ? WHERE id = ?');
    $stmt->execute(['failed', (int)$txn['id']]);
    header('Location: credits.php?error=payment_failed');
    exit;
}

$amountPaid = (int)($verification['data']['amount'] ?? 0);
$expectedAmount = $package['price'] * 100;
if ($amountPaid !== $expectedAmount) {
    header('Location: credits.php?error=verification_failed');
    exit;
}

if ($txn['status'] === 'completed') {
    header('Location: credits.php?success=1');
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT credit_balance, credits_purchased_total FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        $pdo->rollBack();
        header('Location: credits.php?error=server');
        exit;
    }

    $newBalance = (int)$user['credit_balance'] + (int)$package['credits'];
    $newPurchased = (int)$user['credits_purchased_total'] + (int)$package['credits'];
    $stmt = $pdo->prepare('UPDATE users SET credit_balance = ?, credits_purchased_total = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$newBalance, $newPurchased, $user_id]);

    $stmt = $pdo->prepare('UPDATE credit_transactions SET status = ?, payment_amount = ?, payment_currency = ?, payment_gateway = ?, reason = ? WHERE id = ?');
    $stmt->execute(['completed', $amountPaid / 100, 'NGN', 'paystack', 'Credit purchase', (int)$txn['id']]);

    $pdo->commit();
    header('Location: credits.php?success=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: credits.php?error=server');
    exit;
}
