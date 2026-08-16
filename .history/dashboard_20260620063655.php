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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
  <div class="upgrade-bar">
    <span class="spark"><i class="fa-solid fa-bolt"></i></span>
    <span>Elevate your design with better themes and styles.</span>
    <a class="upgrade-pill" href="#" aria-label="Upgrade account">Lightning Upgrade</a>
  </div>

  <div class="dashboard">
    <aside class="sidebar" aria-label="Dashboard navigation">
      <div class="brand-slot" aria-label="xin.ng brand">
        <img src="assets/logo.svg" alt="xin.ng logo">
      </div>

      <div class="account">
        <div class="account-main">
          <span class="avatar"><?= e(initials($user_name)) ?></span>
          <span><?= e($displaySlug ?: $user_name) ?></span>
          <span aria-hidden="true">v</span>
        </div>
        <a class="icon-btn" href="?action=logout" title="Logout" aria-label="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
      </div>

      <nav>
        <div class="nav-section">
          <div class="nav-heading"><span>Workspace</span><span><i class="fa-solid fa-chevron-up"></i></span></div>
          <a class="nav-item active" href="#"><span class="nav-icon"><i class="fa-solid fa-link"></i></span>URL Links</a>
          <a class="nav-item" href="#"><span class="nav-icon"><i class="fa-regular fa-file-lines"></i></span>Pages</a>
          <a class="nav-item" href="#"><span class="nav-icon"><i class="fa-solid fa-qrcode"></i></span>QR Codes</a>
          <a class="nav-item" href="#"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>Insights</a>
        </div>

      </nav>

      <div class="setup-card">
        <div class="progress-ring" style="--completion: <?= (int) $completion ?>%;"><?= (int) $completion ?>%</div>
        <strong>Your setup checklist</strong>
        <p><?= $activePage ? '4 of 6 complete' : '1 of 6 complete' ?></p>
        <a class="primary-btn" href="signup.php"><span class="label-icon"><i class="fa-solid fa-check"></i></span>Finish setup</a>
      </div>
    </aside>

    <main class="main">
      <header class="main-header">
        <div class="header-title">
          <h1>Short links</h1>
          <span>Create memorable URLs, then place them on pages or QR campaigns.</span>
        </div>
        <div class="header-actions">
          <!-- <a class="ghost-btn" href="#"><span class="label-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>Enhance</a> -->
          <a class="icon-btn" href="#" aria-label="Settings"><i class="fa-solid fa-gear"></i></a>
        </div>
      </header>

      <div class="content">
        <div class="editor-column">
          <?php if ($dbError): ?>
            <div class="notice">Database connection is not available. The dashboard design is loaded, but live page data cannot be shown.</div>
          <?php endif; ?>

          <div class="stats-row" aria-label="Workspace analytics summary">
            <div class="stat-card"><strong><?= number_format((int) $totalViews) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-regular fa-eye"></i></span>Page views</span></div>
            <div class="stat-card"><strong><?= number_format((int) $totalQr) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-qrcode"></i></span>QR scans</span></div>
            <div class="stat-card"><strong><?= number_format((int) $totalClicks) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-arrow-pointer"></i></span>Link clicks</span></div>
          </div>

          <div class="model-note">
            <strong>Links are short-link assets.</strong> Pages are page builders, and QR codes are standalone trackable assets. Add existing links to this page, or create a new short link from here.
          </div>

          <div class="profile-strip">
            <div class="profile-copy">
              <strong><?= e($displayTitle) ?></strong>
              <span><?= e($displayBio) ?></span>
              <div style="margin-top:8px;display:flex;align-items:center;gap:8px">
                <div id="page-slug" data-page-id="<?= e($activePage['id'] ?? '') ?>">Slug: <strong id="page-slug-text"><?= e($activePage['slug'] ?? '') ?></strong></div>
                <button id="edit-slug-btn" class="ghost-btn" style="min-width:90px;">Edit slug</button>
              </div>
              <div class="social-row" aria-label="Social links">
                <span class="social-dot"><i class="fa-brands fa-instagram"></i></span>
                <span class="social-dot"><i class="fa-brands fa-whatsapp"></i></span>
                <span class="social-dot"><i class="fa-brands fa-x-twitter"></i></span>
                <span class="social-dot"><i class="fa-regular fa-envelope"></i></span>
              </div>
            </div>
          </div>

          <a class="primary-btn add-btn" href="#"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Add short link</a>

          <div class="secondary-row">
            <a class="ghost-btn" href="#"><span class="label-icon"><i class="fa-solid fa-layer-group"></i></span>Add collection</a>
            <a class="archive-link" href="#"><span class="label-icon"><i class="fa-solid fa-box-archive"></i></span>View archive <span aria-hidden="true">></span></a>
          </div>

          <?php if (empty($links)): ?>
            <div class="empty-state">
              <strong>No short links yet.</strong><br>
              Create a memorable short link for a campaign or destination, then add it to this page when needed.
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
                    <span class="link-slug-text"><?= e($link['url']) ?></span>
                    <span class="link-url-actions">
                      <a class="tiny-edit" href="#" aria-label="Edit URL">edit</a>
                      <span class="slug-share-icon" title="Share"><i class="fa-solid fa-share-nodes"></i></span>
                    </span>
                  </div>
                  <div class="tool-row">
                    <span class="active-tool" title="<?= e($icon) ?>"><i class="fa-solid fa-link"></i></span>
                    <span title="Image"><i class="fa-regular fa-image"></i></span>
                    <span title="Pin"><i class="fa-solid fa-thumbtack"></i></span>
                    <span title="Animation"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <span title="Schedule"><i class="fa-regular fa-calendar"></i></span>
                    <span title="Lock"><i class="fa-solid fa-lock"></i></span>
                    <span title="Chart"><i class="fa-solid fa-chart-simple"></i></span>
                    <span class="click-count"><i class="fa-solid fa-arrow-pointer"></i><?= number_format((int) ($link['click_count'] ?? 0)) ?> clicks</span>
                  </div>
                </div>
                <div class="link-actions">
                  <span class="toggle<?= !empty($link['is_active']) ? ' on' : '' ?>" aria-label="<?= !empty($link['is_active']) ? 'Active' : 'Inactive' ?>"></span>
                  <span class="action-icon" title="Delete"><i class="fa-regular fa-trash-can"></i></span>
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
        <div class="url-pill">

          <button class="pill-share" aria-label="Share page" title="Share page"><i class="fa-solid fa-share-nodes"></i></button>

          <span class="label-icon"><a class="icon-btn" href="<?= e($displayUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page">^</a></span>
          
          <span class="url-text"><?= e(str_replace(['http://', 'https://'], '', $displayUrl)) ?></span>
        </div>
        <!-- <div class="header-actions">
         
          <a class="icon-btn" href="#" aria-label="Preview settings">=</a>
        </div> -->
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
                <strong>Add short link</strong>
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

            <div class="phone-brand">xin.ng page builder preview</div>
          </div>
        </div>
      </div>
    </aside>
  </div>
  <script>
    (function(){
      const csrf = '<?= e(csrf_token()) ?>';
      const editBtn = document.getElementById('edit-slug-btn');
      const slugText = document.getElementById('page-slug-text');
      const slugContainer = document.getElementById('page-slug');
      const urlPill = document.querySelector('.url-pill');
      if (!editBtn || !slugText || !slugContainer) return;
      editBtn.addEventListener('click', ()=>{
        const pageId = slugContainer.dataset.pageId;
        const current = slugText.textContent.trim();
        const input = document.createElement('input');
        input.type = 'text'; input.value = current; input.style.padding = '6px 10px'; input.style.borderRadius = '8px';
        const save = document.createElement('button'); save.textContent = 'Save'; save.className = 'ghost-btn'; save.style.minWidth='80px';
        const cancel = document.createElement('button'); cancel.textContent = 'Cancel'; cancel.className = 'ghost-btn'; cancel.style.minWidth='80px';
        slugContainer.innerHTML = 'Slug: '; slugContainer.appendChild(input); slugContainer.appendChild(save); slugContainer.appendChild(cancel);
        input.focus();
        cancel.addEventListener('click', ()=>{ slugContainer.innerHTML = 'Slug: <strong id="page-slug-text">'+current+'</strong>'; });
        save.addEventListener('click', ()=>{
          const newSlug = input.value.trim();
          if (!newSlug) return alert('Enter a slug');
          const form = new URLSearchParams(); form.append('page_id', pageId); form.append('slug', newSlug); form.append('csrf_token', csrf);
          save.disabled = true; save.textContent = 'Saving...';
          fetch('update_slug.php', { method: 'POST', body: form }).then(r=>r.json()).then(data=>{
            if (data.ok) {
              slugContainer.innerHTML = 'Slug: <strong id="page-slug-text">'+data.slug+'</strong>';
              if (urlPill) urlPill.textContent = urlPill.textContent.replace(/\/u\/[^\s]+$/, '/u/'+data.slug);
            } else {
              alert('Error: '+(data.error||'unknown'));
              save.disabled = false; save.textContent = 'Save';
            }
          }).catch(()=>{ alert('Network error'); save.disabled = false; save.textContent = 'Save'; });
        });
      });
    })();
  </script>
</body>
</html>

