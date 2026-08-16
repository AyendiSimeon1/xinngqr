<?php
// Minimal security: Only allow from localhost or via a secret token
$allowedIPs = ['127.0.0.1', 'localhost'];
$secret = 'your-secret-key-change-this'; // Change this to something random

$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$token = $_GET['token'] ?? '';

$isLocalhost = in_array($clientIP, $allowedIPs);
$isTokenValid = !empty($token) && hash_equals($secret, $token);

if (!$isLocalhost && !$isTokenValid) {
    http_response_code(403);
    die('Access Denied');
}

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');
echo "Testing weekly analytics job...\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

$sent = xinng_send_due_weekly_analytics_reports();
echo "✓ Weekly analytics emails sent: {$sent}\n";
echo "✓ Job completed successfully.\n";
