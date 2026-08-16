<?php
require_once __DIR__ . '/config.php';
session_start();

function xinng_temp_qr_image_url(string $data, string $format = 'png'): string {
    $params = [
        'size' => '600x600',
        'margin' => 18,
        'format' => $format,
        'data' => $data,
    ];
    return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query($params);
}

$destination = trim((string)($_POST['destination'] ?? ''));
if ($destination === '') {
    $destination = 'https://example.com';
}

$validated = xinng_validate_destination_url($destination);
if (!$validated['ok']) {
    $_SESSION['qr_flash'] = $validated['error'];
    header('Location: index.php');
    exit;
}

$type = trim((string)($_POST['qr_type'] ?? 'Website'));
$style = trim((string)($_POST['style'] ?? 'Standard'));

$_SESSION['pending_qr'] = [
    'destination_url' => $validated['url'],
    'qr_type' => $type !== '' ? $type : 'Website',
    'style' => $style !== '' ? $style : 'Standard',
    'created_at' => date('c'),
    'qr_png_url' => xinng_temp_qr_image_url($validated['url'], 'png'),
    'qr_svg_url' => xinng_temp_qr_image_url($validated['url'], 'svg'),
    'share_url' => xinng_public_base_url() . '/qr.php',
];

if (!empty($_SESSION['user_id'])) {
    $pdo = get_db_connection();
    if ($pdo) {
        $savedId = xinng_persist_pending_qr($pdo, $_SESSION['pending_qr'], (int)$_SESSION['user_id']);
        if ($savedId > 0) {
            $_SESSION['pending_qr']['saved_qr_id'] = $savedId;
        }
    }
}

header('Location: qr.php');
exit;
