<?php
require_once __DIR__ . '/config.php';
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: signin.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function public_base_url(): string {
    global $PUBLIC_URL;
    if (!empty($PUBLIC_URL)) {
        return (stripos($PUBLIC_URL, 'http') === 0) ? rtrim($PUBLIC_URL, '/') : 'http://' . rtrim($PUBLIC_URL, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function initials(string $name): string {
    $clean = preg_replace('/[^A-Za-z0-9 ]/', '', $name);
    $parts = array_values(array_filter(explode(' ', trim($clean))));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }

    return strtoupper(substr($clean ?: 'X', 0, 2));
}

function link_icon(string $type, string $title): string {
    $label = strtolower($type ?: $title);
    if (str_contains($label, 'whatsapp')) return 'WA';
    if (str_contains($label, 'instagram')) return 'IG';
    if ($label === 'x' || str_contains($label, 'twitter')) return 'X';
    if (str_contains($label, 'email')) return '@';
    if (str_contains($label, 'phone')) return 'TEL';
    if (str_contains($label, 'payment')) return 'PAY';
    return 'LINK';
}

$pages = [];
$activePage = null;
$dbError = false;
$pdo = get_db_connection();
$base = public_base_url();

if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, slug, title, bio, profile_image_url, is_published FROM pages WHERE user_id = ? ORDER BY id ASC');
    $stmt->execute([$user_id]);
    $pages = $stmt->fetchAll();

    foreach ($pages as &$page) {
        $pid = (int) $page['id'];

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM page_views WHERE page_id = ?');
        $stmt->execute([$pid]);
        $page['page_views'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_scans WHERE page_id = ?');
        $stmt->execute([$pid]);
        $page['qr_scans'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM link_clicks WHERE page_id = ?');
        $stmt->execute([$pid]);
        $page['link_clicks'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT id, title, url, description, link_type, position, is_active, click_count FROM links WHERE page_id = ? ORDER BY position ASC, id ASC LIMIT 50');
        $stmt->execute([$pid]);
        $page['links'] = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT id, qr_image_url, destination_url, scan_count FROM qr_codes WHERE page_id = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$pid]);
        $page['qr'] = $stmt->fetch() ?: null;
    }
    unset($page);

    $activePage = $pages[0] ?? null;
} else {
    $dbError = true;
}

$displayTitle = $activePage['title'] ?? $user_name;
$displayBio = $activePage['bio'] ?? 'Create a smart QR-powered link page you can update anytime.';
$displaySlug = $activePage['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '', $user_name ?: 'user'));
$displayUrl = $activePage ? $base . '/u/' . $activePage['slug'] : $base . '/u/your-slug';
$qrUrl = $activePage ? $base . '/qr/' . $activePage['slug'] : $base . '/qr/your-slug';
$links = $activePage['links'] ?? [];
$totalViews = $activePage['page_views'] ?? 0;
$totalQr = $activePage['qr_scans'] ?? 0;
$totalClicks = $activePage['link_clicks'] ?? 0;
$completion = $activePage ? min(100, 45 + (count($links) * 10) + (!empty($activePage['bio']) ? 10 : 0) + (!empty($activePage['qr']) ? 15 : 0)) : 30;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Links - <?= e($APP_NAME ?? 'xin.ng') ?></title>
  <style>
    :root {
      --bg: #f7f7f5;
      --panel: #ffffff;
      --sidebar: #f0f0ee;
      --ink: #1d1d1b;
      --muted: #6f726b;
      --line: #e5e4df;
      --soft: #f5f4f0;
      --purple: #8f22ff;
      --purple-dark: #7618e8;
      --green: #24d464;
      --blue: #2f73ff;
      --phone: #111111;
      --shadow: 0 16px 36px rgba(29, 29, 27, .08);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      overflow: hidden;
      background: var(--bg);
      color: var(--ink);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    a { color: inherit; }

    .upgrade-bar {
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      background: #141414;
      color: #f7f7f5;
      font-size: 14px;
      font-weight: 800;
    }

    .upgrade-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border: 1px solid var(--green);
      border-radius: 999px;
      padding: 5px 13px;
      color: var(--green);
      text-decoration: none;
    }

    .spark { color: var(--green); font-size: 24px; line-height: 1; }

    .dashboard {
      height: calc(100vh - 52px);
      display: grid;
      grid-template-columns: 240px minmax(560px, 1fr) 468px;
      background: var(--panel);
    }

    .sidebar {
      display: flex;
      flex-direction: column;
      min-height: 0;
      padding: 18px 12px 16px;
      background: var(--sidebar);
      border-right: 1px solid var(--line);
    }

    .account {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 26px;
    }

    .account-main {
      display: flex;
      align-items: center;
      gap: 9px;
      min-width: 0;
      font-weight: 800;
      color: #5c5e59;
      font-size: 14px;
    }

    .avatar {
      width: 24px;
      height: 24px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      border-radius: 999px;
      color: #111;
      font-size: 10px;
      font-weight: 900;
      background: linear-gradient(135deg, #ffb000, #ff7a00 45%, var(--purple));
    }

    .icon-btn {
      width: 40px;
      height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: var(--panel);
      color: var(--ink);
      text-decoration: none;
      font-size: 16px;
      cursor: pointer;
    }

    .sidebar .icon-btn {
      width: 30px;
      height: 30px;
      border: 0;
      background: transparent;
      color: #2c2c2b;
    }

    .nav-section { margin-bottom: 18px; }
    .nav-heading {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 8px 8px;
      color: #666864;
      font-size: 15px;
      font-weight: 900;
    }

    .nav-small {
      margin: 20px 8px 8px;
      color: #8b8d87;
      font-size: 12px;
      font-weight: 700;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      min-height: 29px;
      padding: 5px 9px;
      border-radius: 8px;
      color: #5a5d57;
      text-decoration: none;
      font-size: 14px;
      font-weight: 800;
    }

    .nav-item.active {
      background: #e4e3df;
      color: var(--purple);
    }

    .nav-icon {
      width: 18px;
      text-align: center;
      color: #6e716b;
      font-size: 13px;
    }

    .new-badge {
      margin-left: auto;
      padding: 1px 6px;
      border: 1px solid var(--purple);
      border-radius: 999px;
      color: var(--purple);
      font-size: 10px;
      font-weight: 900;
    }

    .setup-card {
      margin-top: auto;
      padding: 16px;
      border-radius: 22px;
      background: var(--panel);
    }

    .progress-ring {
      width: 55px;
      height: 55px;
      display: grid;
      place-items: center;
      margin-bottom: 9px;
      border-radius: 50%;
      background:
        radial-gradient(circle at center, #fff 52%, transparent 54%),
        conic-gradient(var(--purple) <?= (int) $completion ?>%, #dcd8f8 0);
      color: var(--purple);
      font-size: 13px;
      font-weight: 900;
    }

    .setup-card strong { display: block; font-size: 14px; }
    .setup-card p { margin: 8px 0 16px; color: #333; font-size: 14px; }

    .primary-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      min-height: 48px;
      border: 0;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--purple), var(--purple-dark));
      color: #fff;
      text-decoration: none;
      font-size: 16px;
      font-weight: 900;
      cursor: pointer;
    }

    .main {
      min-width: 0;
      min-height: 0;
      display: flex;
      flex-direction: column;
      background: #fbfbfa;
      border-right: 1px solid var(--line);
    }

    .main-header,
    .preview-header {
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 18px;
      border-bottom: 1px solid var(--line);
      background: rgba(255,255,255,.82);
    }

    .main-header h1 {
      margin: 0;
      font-size: 28px;
      line-height: 1;
      letter-spacing: -.02em;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 9px;
    }

    .ghost-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 40px;
      padding: 0 14px;
      border: 1px solid #d7d6d0;
      border-radius: 999px;
      background: #fff;
      color: var(--ink);
      text-decoration: none;
      font-size: 14px;
      font-weight: 800;
    }

    .content {
      min-height: 0;
      overflow-y: auto;
      padding: 24px 32px 64px;
    }

    .content::-webkit-scrollbar,
    .phone-screen::-webkit-scrollbar { width: 12px; }
    .content::-webkit-scrollbar-track,
    .phone-screen::-webkit-scrollbar-track { background: transparent; }
    .content::-webkit-scrollbar-thumb,
    .phone-screen::-webkit-scrollbar-thumb { background: #8f8f8d; border-radius: 999px; }

    .editor-column {
      width: min(642px, 100%);
      margin: 0 auto;
    }

    .profile-strip {
      display: flex;
      gap: 13px;
      align-items: center;
      margin-bottom: 18px;
    }

    .profile-copy strong {
      display: block;
      font-size: 16px;
      margin-bottom: 2px;
    }

    .profile-copy span {
      display: block;
      color: #676a63;
      font-size: 14px;
      line-height: 1.35;
    }

    .social-row {
      display: flex;
      gap: 8px;
      margin-top: 9px;
    }

    .social-dot {
      width: 18px;
      height: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #9d9f99;
      border-radius: 5px;
      color: #111;
      font-size: 10px;
      font-weight: 900;
    }

    .add-btn {
      min-height: 48px;
      margin: 12px 0 24px;
      font-size: 16px;
    }

    .secondary-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
    }

    .archive-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #111;
      text-decoration: none;
      font-size: 14px;
      font-weight: 700;
    }

    .link-card {
      position: relative;
      display: grid;
      grid-template-columns: 28px 1fr auto;
      gap: 12px;
      min-height: 140px;
      margin-bottom: 24px;
      padding: 28px 24px 20px 10px;
      border: 1px solid var(--line);
      border-radius: 30px;
      background: #fff;
      box-shadow: var(--shadow);
    }

    .link-card.selected {
      border-color: var(--blue);
      box-shadow: 0 14px 32px rgba(47, 115, 255, .12);
    }

    .drag {
      align-self: center;
      color: #7d7f79;
      font-size: 18px;
      text-align: center;
    }

    .link-title {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
      font-weight: 900;
      line-height: 1.1;
    }

    .link-url {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #2f312e;
      font-size: 15px;
      word-break: break-word;
    }

    .tiny-edit {
      color: #111;
      font-size: 13px;
      font-weight: 900;
      text-decoration: none;
    }

    .tool-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 19px;
      margin-top: 18px;
      color: #7d8279;
      font-size: 14px;
      font-weight: 800;
    }

    .tool-row .active-tool { color: var(--purple); }

    .link-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      justify-content: space-between;
      color: #6f746b;
      font-size: 15px;
    }

    .toggle {
      width: 34px;
      height: 20px;
      position: relative;
      border-radius: 999px;
      background: #c9cbc5;
    }

    .toggle::after {
      content: "";
      position: absolute;
      top: 3px;
      left: 3px;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 1px 2px rgba(0,0,0,.2);
    }

    .toggle.on { background: #087a32; }
    .toggle.on::after { left: 17px; }

    .suggestion {
      grid-column: 1 / -1;
      margin: 0 -24px -20px -10px;
      padding: 12px 18px;
      border-top: 1px solid var(--blue);
      border-radius: 0 0 29px 29px;
      background: #edf6ff;
      color: #111;
      font-size: 15px;
    }

    .empty-state {
      padding: 28px;
      border: 1px dashed #c9c8c2;
      border-radius: 24px;
      background: #fff;
      color: var(--muted);
      text-align: center;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 22px;
    }

    .stat-card {
      padding: 14px;
      border: 1px solid var(--line);
      border-radius: 18px;
      background: #fff;
    }

    .stat-card strong {
      display: block;
      font-size: 20px;
    }

    .stat-card span {
      color: var(--muted);
      font-size: 12px;
      font-weight: 800;
    }

    .preview {
      min-width: 0;
      min-height: 0;
      display: flex;
      flex-direction: column;
      background: #fff;
    }

    .url-pill {
      flex: 1;
      max-width: 355px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 16px;
      border: 1px solid #d7d6d0;
      border-radius: 999px;
      color: #222;
      font-size: 14px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .preview-body {
      min-height: 0;
      display: grid;
      place-items: center;
      padding: 26px;
      overflow: auto;
    }

    .phone {
      width: 308px;
      height: 694px;
      padding: 10px;
      border-radius: 30px;
      background: #000;
      box-shadow: 0 22px 46px rgba(0,0,0,.16);
    }

    .phone-screen {
      position: relative;
      width: 100%;
      height: 100%;
      overflow-y: auto;
      border-radius: 22px;
      background: var(--phone);
      color: #fff;
      padding: 84px 12px 20px;
      text-align: center;
    }

    .phone-top {
      position: absolute;
      top: 10px;
      left: 10px;
      right: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      pointer-events: none;
    }

    .phone-fab {
      width: 32px;
      height: 32px;
      display: grid;
      place-items: center;
      border-radius: 10px;
      background: rgba(255,255,255,.34);
      color: #000;
      font-size: 14px;
      font-weight: 900;
    }

    .phone-avatar {
      width: 68px;
      height: 68px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffb000, #ff7a00 45%, var(--purple));
      color: #101010;
      font-size: 21px;
      font-weight: 1000;
    }

    .phone h2 {
      margin: 12px 0 5px;
      color: #fff;
      font-size: 20px;
      line-height: 1;
      letter-spacing: -.02em;
    }

    .phone p {
      width: 220px;
      margin: 0 auto;
      color: #fff;
      font-size: 12px;
      line-height: 1.45;
      font-weight: 800;
    }

    .phone-socials {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin: 16px 0 32px;
    }

    .phone-socials span {
      width: 22px;
      height: 22px;
      display: grid;
      place-items: center;
      border: 2px solid #fff;
      border-radius: 6px;
      font-size: 10px;
      font-weight: 900;
    }

    .phone-link {
      min-height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      margin-bottom: 10px;
      padding: 13px 36px;
      border-radius: 11px;
      background: #1d1d1d;
      color: #fff;
      text-decoration: none;
      font-size: 11px;
      font-weight: 900;
    }

    .phone-link.hero {
      min-height: 150px;
      flex-direction: column;
      gap: 12px;
      background: #fff7ef;
      color: var(--green);
      font-size: 34px;
    }

    .phone-link.hero .hero-icon {
      width: 62px;
      height: 62px;
      display: grid;
      place-items: center;
      border: 5px solid var(--green);
      border-radius: 50%;
      font-size: 28px;
    }

    .phone-link .more {
      position: absolute;
      right: 12px;
      color: #898989;
      font-size: 18px;
    }

    .phone-brand {
      margin-top: 12px;
      color: #b6b6b6;
      font-size: 11px;
      font-weight: 800;
    }

    .notice {
      margin-bottom: 18px;
      padding: 13px 16px;
      border: 1px solid #f5d08a;
      border-radius: 14px;
      background: #fff8e8;
      color: #7a4b00;
      font-size: 14px;
      font-weight: 800;
    }

    @media (max-width: 1180px) {
      body { overflow: auto; }
      .dashboard {
        height: auto;
        min-height: calc(100vh - 52px);
        grid-template-columns: 220px 1fr;
      }
      .preview { display: none; }
      .main { border-right: 0; }
    }

    @media (max-width: 760px) {
      .upgrade-bar { height: auto; min-height: 52px; padding: 10px 12px; text-align: center; }
      .dashboard { display: block; }
      .sidebar { display: none; }
      .main-header { position: sticky; top: 0; z-index: 4; }
      .content { padding: 20px 14px 44px; }
      .main-header h1 { font-size: 24px; }
      .stats-row { grid-template-columns: 1fr; }
      .link-card { grid-template-columns: 1fr; padding: 22px; border-radius: 22px; }
      .drag { display: none; }
      .link-actions { flex-direction: row; align-items: center; justify-content: flex-start; gap: 18px; }
      .suggestion { margin: 0 -22px -22px; border-radius: 0 0 22px 22px; }
    }
  </style>
</head>
<body>
  <div class="upgrade-bar">
    <span class="spark">*</span>
    <span>Elevate your design with better themes and styles.</span>
    <a class="upgrade-pill" href="#" aria-label="Upgrade account">Lightning Upgrade</a>
  </div>

  <div class="dashboard">
    <aside class="sidebar" aria-label="Dashboard navigation">
      <div class="account">
        <div class="account-main">
          <span class="avatar"><?= e(initials($user_name)) ?></span>
          <span><?= e($displaySlug ?: $user_name) ?></span>
          <span aria-hidden="true">v</span>
        </div>
        <a class="icon-btn" href="?action=logout" title="Logout" aria-label="Logout">Q</a>
      </div>

      <nav>
        <div class="nav-section">
          <div class="nav-heading"><span>My Linktree</span><span>^</span></div>
          <a class="nav-item active" href="#"><span class="nav-icon">#</span>Links</a>
          <a class="nav-item" href="#"><span class="nav-icon">$</span>Shop</a>
          <a class="nav-item" href="#"><span class="nav-icon">%</span>Design</a>
        </div>

        <div class="nav-section">
          <a class="nav-item" href="#"><span class="nav-icon">[]</span>Earn</a>
          <a class="nav-item" href="#"><span class="nav-icon">()</span>Audience</a>
          <a class="nav-item" href="#"><span class="nav-icon">||</span>Insights</a>
        </div>

        <div class="nav-small">Tools</div>
        <a class="nav-item" href="#"><span class="nav-icon">ID</span>Business cards <span class="new-badge">NEW</span></a>
        <a class="nav-item" href="#"><span class="nav-icon">Cal</span>Social planner</a>
        <a class="nav-item" href="#"><span class="nav-icon">Msg</span>Instagram auto-reply</a>
        <a class="nav-item" href="#"><span class="nav-icon">URL</span>Link shortener</a>
        <a class="nav-item" href="#"><span class="nav-icon">AI</span>Post ideas</a>
      </nav>

      <div class="setup-card">
        <div class="progress-ring"><?= (int) $completion ?>%</div>
        <strong>Your setup checklist</strong>
        <p><?= $activePage ? '4 of 6 complete' : '1 of 6 complete' ?></p>
        <a class="primary-btn" href="signup.php">Finish setup</a>
      </div>
    </aside>

    <main class="main">
      <header class="main-header">
        <h1>Links</h1>
        <div class="header-actions">
          <a class="ghost-btn" href="#">Enhance</a>
          <a class="icon-btn" href="#" aria-label="Settings">o</a>
        </div>
      </header>

      <div class="content">
        <div class="editor-column">
          <?php if ($dbError): ?>
            <div class="notice">Database connection is not available. The dashboard design is loaded, but live page data cannot be shown.</div>
          <?php endif; ?>

          <div class="profile-strip">
            <div class="profile-copy">
              <strong><?= e($displayTitle) ?></strong>
              <span><?= e($displayBio) ?></span>
              <div style="margin-top:8px;display:flex;align-items:center;gap:8px">
                <div id="page-slug" data-page-id="<?= e($activePage['id'] ?? '') ?>">Slug: <strong id="page-slug-text"><?= e($activePage['slug'] ?? '') ?></strong></div>
                <button id="edit-slug-btn" class="ghost-btn" style="min-width:90px;">Edit slug</button>
              </div>
              <div class="social-row" aria-label="Social links">
                <span class="social-dot">IG</span>
                <span class="social-dot">WA</span>
                <span class="social-dot">X</span>
                <span class="social-dot">E</span>
              </div>
            </div>
          </div>

          <a class="primary-btn add-btn" href="#">+ Add</a>

          <div class="secondary-row">
            <a class="ghost-btn" href="#">Add collection</a>
            <a class="archive-link" href="#">View archive <span aria-hidden="true">></span></a>
          </div>

          <div class="stats-row" aria-label="Page analytics summary">
            <div class="stat-card"><strong><?= number_format((int) $totalViews) ?></strong><span>Page views</span></div>
            <div class="stat-card"><strong><?= number_format((int) $totalQr) ?></strong><span>QR scans</span></div>
            <div class="stat-card"><strong><?= number_format((int) $totalClicks) ?></strong><span>Link clicks</span></div>
          </div>

          <?php if (empty($links)): ?>
            <div class="empty-state">
              <strong>No links yet.</strong><br>
              Add your first WhatsApp, Instagram, payment, booking, or website link to start building your public page.
            </div>
          <?php else: ?>
            <?php foreach ($links as $index => $link): ?>
              <?php
                $title = $link['title'] ?: 'Untitled link';
                $type = $link['link_type'] ?? 'url';
                $icon = link_icon($type, $title);
                $isSelected = $index === 1;
              ?>
              <article class="link-card<?= $isSelected ? ' selected' : '' ?>">
                <div class="drag" aria-hidden="true">::</div>
                <div>
                  <div class="link-title">
                    <?= e($title) ?>
                    <a class="tiny-edit" href="#" aria-label="Edit <?= e($title) ?>">edit</a>
                  </div>
                  <div class="link-url">
                    <?= e($link['url']) ?>
                    <a class="tiny-edit" href="#" aria-label="Edit URL">edit</a>
                  </div>
                  <div class="tool-row">
                    <span class="active-tool"><?= e($icon) ?></span>
                    <span>Img</span>
                    <span>Star</span>
                    <span>Anim</span>
                    <span>Sch</span>
                    <span>Lock</span>
                    <span>Chart</span>
                    <span><?= number_format((int) ($link['click_count'] ?? 0)) ?> clicks</span>
                  </div>
                </div>
                <div class="link-actions">
                  <span title="Share">share</span>
                  <span class="toggle<?= !empty($link['is_active']) ? ' on' : '' ?>" aria-label="<?= !empty($link['is_active']) ? 'Active' : 'Inactive' ?>"></span>
                  <span title="Delete">delete</span>
                </div>
                <?php if ($isSelected): ?>
                  <div class="suggestion">Looking for a more visual display? <a href="#">Connect your Instagram</a></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <aside class="preview" aria-label="Public page preview">
      <header class="preview-header">
        <div class="url-pill"><?= e(str_replace(['http://', 'https://'], '', $displayUrl)) ?></div>
        <div class="header-actions">
          <a class="icon-btn" href="<?= e($displayUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page">^</a>
          <a class="icon-btn" href="#" aria-label="Preview settings">=</a>
        </div>
      </header>

      <div class="preview-body">
        <div class="phone">
          <div class="phone-screen">
            <div class="phone-top">
              <span class="phone-fab">*</span>
              <span class="phone-fab">^</span>
            </div>

            <div class="phone-avatar"><?= e(initials($displayTitle)) ?></div>
            <h2><?= e($displayTitle) ?></h2>
            <p><?= e($displayBio) ?></p>
            <div class="phone-socials">
              <span>IG</span>
              <span>WA</span>
              <span>X</span>
            </div>

            <?php if (empty($links)): ?>
              <a class="phone-link hero" href="#">
                <span class="hero-icon">+</span>
                <strong>Add Link</strong>
              </a>
            <?php else: ?>
              <?php foreach ($links as $index => $link): ?>
                <?php if (empty($link['is_active'])) continue; ?>
                <?php if ($index === 0): ?>
                  <a class="phone-link hero" href="#">
                    <span class="hero-icon"><?= e(substr(link_icon($link['link_type'] ?? '', $link['title'] ?? ''), 0, 2)) ?></span>
                    <strong><?= e($link['title']) ?></strong>
                    <span class="more">:</span>
                  </a>
                <?php else: ?>
                  <a class="phone-link" href="#">
                    <?= e($link['title']) ?>
                    <span class="more">:</span>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>

            <div class="phone-brand">xin.ng landing page</div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</body>
</html>
