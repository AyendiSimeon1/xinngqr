<?php
require_once __DIR__.'/config.php';
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php'); exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

$pdo = get_db_connection();
// Fetch user pages
$pages = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, slug, title FROM pages WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $pages = $stmt->fetchAll();

    // Precompute analytics per page
    foreach ($pages as &$p) {
        $pid = $p['id'];
        // page views
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM page_views WHERE page_id = ?');
        $stmt->execute([$pid]); $p['page_views'] = (int)$stmt->fetchColumn();
        // qr scans
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM qr_scans WHERE page_id = ?');
        $stmt->execute([$pid]); $p['qr_scans'] = (int)$stmt->fetchColumn();
        // link clicks (via link_clicks)
        $stmt = $pdo->prepare('SELECT COUNT(lc.id) FROM link_clicks lc JOIN links l ON lc.link_id = l.id WHERE l.page_id = ?');
        $stmt->execute([$pid]); $p['link_clicks'] = (int)$stmt->fetchColumn();
        // links
        $stmt = $pdo->prepare('SELECT id, title, url, click_count, is_active FROM links WHERE page_id = ? ORDER BY position ASC LIMIT 200');
        $stmt->execute([$pid]); $p['links'] = $stmt->fetchAll();
        // qr code preview (first)
        $stmt = $pdo->prepare('SELECT id, qr_image_url, destination_url FROM qr_codes WHERE page_id = ? LIMIT 1');
        $stmt->execute([$pid]); $p['qr'] = $stmt->fetch() ?: null;
    }
    unset($p);
}

function public_base_url() {
    global $PUBLIC_URL;
    if (!empty($PUBLIC_URL)) {
        return (stripos($PUBLIC_URL, 'http') === 0) ? rtrim($PUBLIC_URL, '/') : 'http://'.rtrim($PUBLIC_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme.'://'.$_SERVER['HTTP_HOST'];
}

$base = public_base_url();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="admin-shell">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px">
        <div class="logo"><img src="assets/logo.svg" alt="logo"></div>
        <div>
          <div style="font-weight:800">Hello, <?= htmlspecialchars($user_name ?: 'User') ?></div>
          <div class="small">Manage your pages and analytics</div>
        </div>
      </div>
      <div class="actions">
        <a class="btn secondary" href="signup.php">Create account</a>
        <a class="btn" href="?action=logout">Logout</a>
      </div>
    </div>

    <div class="admin-grid">
      <?php if (empty($pages)): ?>
        <div class="card">
          <h1>Welcome</h1>
          <p class="small">You don't have a public page yet. Create a page to get started.</p>
          <div class="actions"><a class="btn" href="signup.php">Create page</a></div>
        </div>
      <?php else: ?>
        <?php foreach ($pages as $page): ?>
          <section class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
              <div>
                <h1 style="margin:0"><?= htmlspecialchars($page['title'] ?: $page['slug']) ?></h1>
                <div class="small">Public URL: <a href="<?= htmlspecialchars($base.'/u/'.$page['slug']) ?>" target="_blank"><?= htmlspecialchars($base.'/u/'.$page['slug']) ?></a></div>
              </div>
              <div style="text-align:right">
                <?php if (!empty($page['qr']) && !empty($page['qr']['qr_image_url'])): ?>
                  <img class="qr-img" src="<?= htmlspecialchars($page['qr']['qr_image_url']) ?>" alt="qr">
                <?php else: ?>
                  <div class="qr-img" style="display:flex;align-items:center;justify-content:center;color:var(--muted)">No QR</div>
                <?php endif; ?>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:18px">
              <div class="form-card"><div style="font-weight:800">Page views</div><div class="small"><?= number_format($page['page_views']) ?></div></div>
              <div class="form-card"><div style="font-weight:800">QR scans</div><div class="small"><?= number_format($page['qr_scans']) ?></div></div>
              <div class="form-card"><div style="font-weight:800">Link clicks</div><div class="small"><?= number_format($page['link_clicks']) ?></div></div>
            </div>

            <div style="margin-top:18px">
              <div style="display:flex;align-items:center;justify-content:space-between"><strong>Links</strong><a class="btn secondary" href="#">Manage links</a></div>
              <div class="links" style="margin-top:12px">
                <?php foreach ($page['links'] as $link): ?>
                  <div class="link-btn">
                    <div style="display:flex;gap:12px;align-items:center">
                      <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(90deg,var(--brand),var(--brand-2));color:white;display:flex;align-items:center;justify-content:center;font-weight:800">→</div>
                      <div style="min-width:0">
                        <div style="font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($link['title']) ?></div>
                        <div class="small" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($link['url']) ?></div>
                      </div>
                    </div>
                    <div style="text-align:right">
                      <div class="small">Clicks: <?= number_format($link['click_count'] ?? 0) ?></div>
                      <div class="small" style="color:<?= $link['is_active'] ? '#059669' : '#94a3b8' ?>"><?= $link['is_active'] ? 'Active' : 'Inactive' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

<?php
// simple logout handler
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset(); session_destroy(); header('Location: signin.php'); exit;
}
?>
</body>
</html>
