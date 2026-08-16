<?php
require 'config.php';
$pdo = get_db_connection();
if (!$pdo) {
    echo "no-pdo\n";
    exit(1);
}
$stmt = $pdo->query('SELECT slug, is_published FROM pages ORDER BY id DESC LIMIT 10');
foreach ($stmt as $row) {
    echo $row['slug'] . '|' . $row['is_published'] . PHP_EOL;
}
