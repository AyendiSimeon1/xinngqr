<?php
require 'config.php';
$pdo = get_db_connection();
if (!$pdo) {
    echo "NO_DB\n";
    exit;
}
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS c FROM pages');
    $row = $stmt->fetch();
    echo 'pages_count=' . ($row['c'] ?? 0) . "\n";
} catch (Throwable $e) {
    echo 'pages_error=' . $e->getMessage() . "\n";
}
