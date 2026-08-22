<?php
require_once __DIR__.'/config.php';

// Test database connection
echo "<h2>Database Connection Test</h2>";
$pdo = get_db_connection();
if ($pdo) {
    echo "✓ Database connected successfully<br>";
} else {
    echo "✗ Database connection failed<br>";
    exit;
}

// Test table creation
echo "<h2>Testing Table Creation</h2>";
try {
    xinng_ensure_credit_tables($pdo);
    echo "✓ Credit tables created successfully<br>";
} catch (Exception $e) {
    echo "✗ Credit tables error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

try {
    xinng_ensure_short_link_tables($pdo);
    echo "✓ Short link tables created successfully<br>";
} catch (Exception $e) {
    echo "✗ Short link tables error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

try {
    xinng_ensure_page_builder_tables($pdo);
    echo "✓ Page builder tables created successfully<br>";
} catch (Exception $e) {
    echo "✗ Page builder tables error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test UUID function
echo "<h2>Testing UUID() Function</h2>";
try {
    $stmt = $pdo->query("SELECT UUID() as test_uuid");
    $result = $stmt->fetch();
    if ($result) {
        echo "✓ UUID() works: " . htmlspecialchars($result['test_uuid']) . "<br>";
    }
} catch (Exception $e) {
    echo "✗ UUID() error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>This might be the problem!</strong> Try fixing signup.php to not use UUID().<br>";
}

// Test a sample insert
echo "<h2>Testing Sample Insert</h2>";
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, credit_balance, credits_purchased_total, credits_used_total, created_at, updated_at) 
                            VALUES (UUID(), ?, ?, ?, 1000, 0, 0, NOW(), NOW())");
    $stmt->execute(['Test User', 'test@example.com', password_hash('test', PASSWORD_DEFAULT)]);
    $pdo->rollBack();
    echo "✓ Sample insert works (rolled back)<br>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "✗ Insert error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<h2>Logs</h2>";
$logFile = __DIR__ . '/logs/app.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -20);
    echo "<pre>" . htmlspecialchars(implode('', $lines)) . "</pre>";
} else {
    echo "No log file found at " . htmlspecialchars($logFile);
}
?>
