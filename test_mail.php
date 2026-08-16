<?php
require_once __DIR__ . '/config.php';

$to = null;
// CLI usage: php test_mail.php you@example.com
if (PHP_SAPI === 'cli') {
    $to = $argv[1] ?? null;
} else {
    $to = $_GET['to'] ?? null;
}

if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $usage = "Usage (CLI): php test_mail.php you@example.com\n" .
             "Usage (web): /test_mail.php?to=you@example.com\n";
    echo $usage;
    exit(1);
}

$subject = ($APP_NAME ?? 'Application') . ' — test email';
$body = "This is a test email from " . ($APP_NAME ?? 'the app') . " sent at " . date('c') . "\n\n";
$body .= "If you received this, the mailer configuration is working.";

$ok = false;
try {
    $ok = send_mail($to, $subject, $body, false);
} catch (Throwable $e) {
    error_log('test_mail exception: ' . $e->getMessage());
}

if ($ok) {
    echo "OK: sent to $to\n";
    // When run in browser, give a clickable confirmation.
    if (PHP_SAPI !== 'cli') {
        echo "<p>OK: sent to " . htmlspecialchars($to) . "</p>";
    }
    exit(0);
} else {
    echo "ERROR: failed to send to $to\n";
    if (PHP_SAPI !== 'cli') {
        echo "<p>ERROR: failed to send to " . htmlspecialchars($to) . "</p>";
    }
    exit(2);
}
