<?php
require __DIR__ . '/config.php';
$pdo = get_db_connection();
if (!$pdo) { echo "NO_DB\n"; exit; }
$slug = 'e2e-corp-' . time();
$stmt = $pdo->query('SELECT user_id FROM pages LIMIT 1');
$u = $stmt ? $stmt->fetchColumn() : false;
$user = $u ? (int)$u : 1;
$defaults = ['theme'=>'navy','layout'=>'compact','header_color'=>'#102A43','background_color'=>'#F6F8FB','block_color'=>'#102A943','block_text_color'=>'#FFFFFF'];
$corp_defaults = [
    'cards_title' => 'E2E Capabilities',
    'cards_lede' => 'Custom lede for testing.',
    'actions_title' => 'E2E Actions',
    'actions_lede' => 'Actions lede for testing.',
    'event_register_title' => 'Register Now'
];
try {
    $stmt = $pdo->prepare("INSERT INTO pages (user_id, page_type, corporate_metadata, slug, title, description, bio, is_published, status, theme, layout, font, title_color, description_color, header_mode, header_color, background_mode, background_color, block_shape, block_shadow, block_color, block_text_color, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'published', ?, ?, 'system', '#26282C', '#26282C', 'color', ?, 'color', ?, 'rounded', 'soft', ?, ?, NOW(), NOW(), NOW())");
    $stmt->execute([$user, 'corporate', json_encode($corp_defaults, JSON_UNESCAPED_SLASHES), $slug, 'E2E Corp Test', 'E2E description', 'E2E description', $defaults['theme'], $defaults['layout'], $defaults['header_color'], $defaults['background_color'], $defaults['block_color'], $defaults['block_text_color']]);
    $id = (int)$pdo->lastInsertId();

    $rowStmt = $pdo->prepare('SELECT id, slug, page_type, corporate_metadata, is_published FROM pages WHERE id = ? LIMIT 1');
    $rowStmt->execute([$id]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
    echo "DB ROW:\n"; var_dump($row);

    // Save corporate detailed data
    if (function_exists('xinng_save_corporate_page_data')) {
        xinng_save_corporate_page_data($pdo, $id, $user, $corp_defaults);
    }
    // Ensure page_type is explicitly set to corporate (some direct inserts may leave it blank)
    $pdo->prepare('UPDATE pages SET page_type = ? WHERE id = ?')->execute(['corporate', $id]);
    $rowStmt->execute([$id]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
    echo "DB ROW AFTER UPDATE:\n"; var_dump($row);

    echo "\nRendering public_page.php...\n";
    $_GET['slug'] = $slug;
    ob_start();
    require __DIR__ . '/public_page.php';
    $html = ob_get_clean();
    echo substr($html, 0, 800);

    // Cleanup
    $pdo->prepare('DELETE FROM page_corporate_profiles WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_facts WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_socials WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_events WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_cards WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_buttons WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_team_members WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM page_corporate_quote_requests WHERE page_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
    echo "\nCleaned up inserted records\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
