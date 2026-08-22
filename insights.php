<?php
require_once __DIR__ . '/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
	header('Location: signin.php');
	exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function insights_initials(string $name): string { return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'X', 0, 2)); }

$pdo = get_db_connection();
$dbError = false;
$displaySlug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $user_name ?: 'user'));
$totals = ['views' => 0, 'clicks' => 0, 'scans' => 0];
$trend = [];
$topLinks = [];
$topQrs = [];
$recent = [];

for ($i = 6; $i >= 0; $i--) {
	$day = date('Y-m-d', strtotime("-{$i} days"));
	$trend[$day] = ['views' => 0, 'clicks' => 0, 'scans' => 0];
}

if ($pdo) {
	xinng_ensure_short_link_tables($pdo);
	xinng_ensure_qr_code_tables($pdo);
	xinng_ensure_page_builder_tables($pdo);

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND p.deleted_at IS NULL');
	$stmt->execute([$user_id]);
	$totals['views'] = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM link_clicks lc JOIN pages p ON p.id = lc.page_id WHERE p.user_id = ? AND p.deleted_at IS NULL');
	$stmt->execute([$user_id]);
	$totals['clicks'] = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_code_scans qs JOIN qr_codes q ON q.id = qs.qr_code_id WHERE q.user_id = ?');
	$stmt->execute([$user_id]);
	$totals['scans'] = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT DATE(pv.viewed_at) AS day, COUNT(*) AS total FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND p.deleted_at IS NULL AND pv.viewed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(pv.viewed_at)');
	$stmt->execute([$user_id]);
	foreach ($stmt->fetchAll() as $row) if (isset($trend[$row['day']])) $trend[$row['day']]['views'] = (int)$row['total'];

	$stmt = $pdo->prepare('SELECT DATE(lc.clicked_at) AS day, COUNT(*) AS total FROM link_clicks lc JOIN pages p ON p.id = lc.page_id WHERE p.user_id = ? AND p.deleted_at IS NULL AND lc.clicked_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(lc.clicked_at)');
	$stmt->execute([$user_id]);
	foreach ($stmt->fetchAll() as $row) if (isset($trend[$row['day']])) $trend[$row['day']]['clicks'] = (int)$row['total'];

  $stmt = $pdo->prepare('SELECT DATE(qs.scanned_at) AS day, COUNT(*) AS total FROM qr_code_scans qs JOIN qr_codes q ON q.id = qs.qr_code_id WHERE q.user_id = ? AND qs.scanned_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(qs.scanned_at)');
	$stmt->execute([$user_id]);
	foreach ($stmt->fetchAll() as $row) if (isset($trend[$row['day']])) $trend[$row['day']]['scans'] = (int)$row['total'];

	$stmt = $pdo->prepare('SELECT title, back_half, destination_url, click_count FROM short_links WHERE user_id = ? AND deleted_at IS NULL ORDER BY click_count DESC, updated_at DESC LIMIT 5');
	$stmt->execute([$user_id]);
	$topLinks = $stmt->fetchAll();

	$stmt = $pdo->prepare('SELECT title, type, scan_count FROM qr_codes WHERE user_id = ? AND deleted_at IS NULL ORDER BY scan_count DESC, updated_at DESC LIMIT 5');
	$stmt->execute([$user_id]);
	$topQrs = $stmt->fetchAll();

  $stmt = $pdo->prepare('SELECT "Page view" AS event_name, pv.viewed_at AS event_time, p.title AS item_title FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND p.deleted_at IS NULL UNION ALL SELECT "QR scan", qs.scanned_at, q.title FROM qr_code_scans qs JOIN qr_codes q ON q.id = qs.qr_code_id WHERE q.user_id = ? UNION ALL SELECT "Link click", lc.clicked_at, COALESCE(pb.title, "Page link") FROM link_clicks lc JOIN pages p ON p.id = lc.page_id LEFT JOIN page_blocks pb ON pb.id = lc.block_id WHERE p.user_id = ? AND p.deleted_at IS NULL ORDER BY event_time DESC LIMIT 8');
	$stmt->execute([$user_id, $user_id, $user_id]);
	$recent = $stmt->fetchAll();
} else {
	$dbError = true;
}

$trendTotals = array_values(array_map(static fn($day) => $day['views'] + $day['clicks'] + $day['scans'], $trend));
$maxTrend = max(1, ...$trendTotals);
$totalActions = $totals['clicks'] + $totals['scans'];
$rate = $totals['views'] > 0 ? round(($totalActions / $totals['views']) * 100, 1) : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Insights - <?= e($APP_NAME ?? 'xin.ng') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
  <style>
    .insights-grid { display:grid; grid-template-columns:minmax(0, 1.35fr) minmax(280px, .65fr); gap:18px; }
    .insights-panel { background:#fff; border:1px solid #e7dfd6; border-radius:16px; padding:22px; }
    .insights-panel h2 { margin:0 0 6px; font-size:18px; }
    .insights-panel > p { margin:0 0 18px; color:#777; font-size:13px; }
    .trend { display:grid; grid-template-columns:repeat(7, 1fr); align-items:end; gap:10px; min-height:210px; padding-top:12px; }
    .trend-day { display:grid; gap:8px; justify-items:center; height:190px; grid-template-rows:1fr auto; }
    .trend-bars { width:100%; height:100%; display:flex; align-items:end; justify-content:center; gap:3px; }
    .trend-bar { width:28%; min-height:3px; border-radius:4px 4px 0 0; }
    .trend-bar.views { background:#b75356; } .trend-bar.clicks { background:#1979bf; } .trend-bar.scans { background:#0a9994; }
    .trend-day small { color:#777; font-size:11px; }
    .legend { display:flex; gap:16px; margin-top:12px; color:#666; font-size:12px; } .legend span:before { content:''; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px; } .legend .views:before { background:#b75356; } .legend .clicks:before { background:#1979bf; } .legend .scans:before { background:#0a9994; }
    .insights-list { display:grid; gap:12px; } .insight-row { display:flex; justify-content:space-between; gap:14px; align-items:center; padding-bottom:12px; border-bottom:1px solid #eee8e1; } .insight-row:last-child { border-bottom:0; padding-bottom:0; } .insight-row strong { display:block; font-size:14px; } .insight-row small { color:#888; display:block; margin-top:3px; overflow-wrap:anywhere; } .insight-value { font-weight:900; color:#001a38; white-space:nowrap; } .empty-insights { color:#888; font-size:13px; padding:10px 0; }
    .activity-list { display:grid; gap:0; } .activity-item { display:flex; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid #eee8e1; } .activity-item:last-child { border-bottom:0; } .activity-icon { width:32px; height:32px; display:grid; place-items:center; border-radius:9px; background:#f8e9e7; color:#b75356; flex:none; } .activity-item strong { font-size:13px; } .activity-item small { color:#888; display:block; margin-top:3px; }
    @media (max-width:900px) { .insights-grid { grid-template-columns:1fr; } }
    @media (max-width:560px) { .trend { gap:4px; } .trend-bar { width:30%; } .insights-panel { padding:17px; } }
  </style>
</head>
<body>
  <div class="upgrade-bar"><span class="spark"><i class="fa-solid fa-bolt"></i></span><span>Turn attention into measurable action.</span><a class="upgrade-pill" href="pricing.php">Explore plans</a></div>
  <div class="dashboard">
    <aside class="sidebar" aria-label="Dashboard navigation">
      <div class="brand-slot"><img src="assets/logo.svg" alt="xin.ng logo"></div>
      <div class="account"><div class="account-main"><span class="avatar"><?= e(insights_initials($user_name)) ?></span><span><?= e($displaySlug ?: $user_name) ?></span><span aria-hidden="true">v</span></div><a class="icon-btn" href="logout.php" title="Logout" aria-label="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></div>
      <nav><div class="nav-section"><div class="nav-heading"><span>Workspace</span><span><i class="fa-solid fa-chevron-up"></i></span></div>
        <a class="nav-item" href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-link"></i></span>URL Links</a>
        <a class="nav-item" href="pages.php"><span class="nav-icon"><i class="fa-regular fa-file-lines"></i></span>Pages</a>
        <a class="nav-item" href="qr_codes.php"><span class="nav-icon"><i class="fa-solid fa-qrcode"></i></span>QR Codes</a>
        <a class="nav-item active" href="insights.php"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>Insights</a>
      </div></nav>
    </aside>
    <main class="main">
      <header class="main-header"><div class="header-title"><h1>Insights</h1><span>See what your links, pages, and QR campaigns are doing.</span></div><div class="header-actions"><a class="icon-btn" href="notifications.php" title="Notifications" aria-label="Notifications"><i class="fa-solid fa-bell"></i></a></div></header>
      <div class="content">
        <?php if ($dbError): ?><div class="notice">Database connection is not available.</div><?php endif; ?>
        <div class="stats-row" aria-label="Insights summary"><div class="stat-card"><strong><?= number_format($totals['views']) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-regular fa-eye"></i></span>Total views</span></div><div class="stat-card"><strong><?= number_format($totals['clicks']) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-arrow-pointer"></i></span>Link clicks</span></div><div class="stat-card"><strong><?= number_format($totals['scans']) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-qrcode"></i></span>QR scans</span></div><div class="stat-card"><strong><?= e($rate) ?>%</strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-bullseye"></i></span>Action rate</span></div></div>
        <div class="insights-grid">
          <section class="insights-panel"><h2>Activity over the last 7 days</h2><p>Compare attention with the actions it creates.</p><div class="trend"><?php foreach ($trend as $day => $values): ?><div class="trend-day"><div class="trend-bars"><span class="trend-bar views" style="height:<?= max(3, round(($values['views'] / $maxTrend) * 100)) ?>%" title="<?= e($values['views']) ?> views"></span><span class="trend-bar clicks" style="height:<?= max(3, round(($values['clicks'] / $maxTrend) * 100)) ?>%" title="<?= e($values['clicks']) ?> clicks"></span><span class="trend-bar scans" style="height:<?= max(3, round(($values['scans'] / $maxTrend) * 100)) ?>%" title="<?= e($values['scans']) ?> scans"></span></div><small><?= e(date('D', strtotime($day))) ?></small></div><?php endforeach; ?></div><div class="legend"><span class="views">Views</span><span class="clicks">Clicks</span><span class="scans">Scans</span></div></section>
          <section class="insights-panel"><h2>Top short links</h2><p>Links receiving the most clicks.</p><div class="insights-list"><?php if (!$topLinks): ?><div class="empty-insights">Your short-link performance will appear here.</div><?php else: foreach ($topLinks as $link): ?><div class="insight-row"><div><strong><?= e($link['title'] ?: $link['back_half']) ?></strong><small><?= e($link['back_half'] ? xinng_short_url($link['back_half']) : $link['destination_url']) ?></small></div><span class="insight-value"><?= number_format((int)$link['click_count']) ?></span></div><?php endforeach; endif; ?></div></section>
          <section class="insights-panel"><h2>Top QR codes</h2><p>QR codes ranked by scans.</p><div class="insights-list"><?php if (!$topQrs): ?><div class="empty-insights">Your QR scan performance will appear here.</div><?php else: foreach ($topQrs as $qr): ?><div class="insight-row"><div><strong><?= e($qr['title'] ?: 'QR Code') ?></strong><small><?= e(ucwords(str_replace('_', ' ', $qr['type'] ?: 'website'))) ?></small></div><span class="insight-value"><?= number_format((int)$qr['scan_count']) ?></span></div><?php endforeach; endif; ?></div></section>
          <section class="insights-panel"><h2>Recent activity</h2><p>The latest events across your workspace.</p><div class="activity-list"><?php if (!$recent): ?><div class="empty-insights">Activity will appear here as people visit, click, and scan.</div><?php else: foreach ($recent as $event): ?><div class="activity-item"><span class="activity-icon"><i class="fa-solid <?= $event['event_name'] === 'QR scan' ? 'fa-qrcode' : ($event['event_name'] === 'Link click' ? 'fa-arrow-pointer' : 'fa-eye') ?>"></i></span><div><strong><?= e($event['event_name']) ?></strong><small><?= e($event['item_title'] ?: 'Untitled') ?> · <?= e(date('M j, g:i a', strtotime($event['event_time']))) ?></small></div></div><?php endforeach; endif; ?></div></section>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
