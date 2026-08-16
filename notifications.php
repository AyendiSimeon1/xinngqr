<?php
require_once __DIR__ . '/config.php';
session_start();

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

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

switch ($action) {
    case 'mark_read':
        $notificationId = (int)($_POST['id'] ?? 0);
        xinng_mark_notification_read($pdo, $user_id, $notificationId > 0 ? $notificationId : null);
        echo json_encode(['ok' => true]);
        break;

    case 'mark_all_read':
        xinng_mark_notification_read($pdo, $user_id);
        echo json_encode(['ok' => true]);
        break;

    default:
        $notifications = xinng_get_notifications($pdo, $user_id, 20);
        echo json_encode(['ok' => true, 'notifications' => $notifications]);
        break;
}
