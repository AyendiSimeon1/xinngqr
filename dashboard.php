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
    return xinng_public_base_url();
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
$shortLinks = [];
$dbError = false;
$pdo = get_db_connection();
$base = public_base_url();
$creditBalance = 0;
$notifications = [];
$unreadNotificationCount = 0;

if ($pdo) {
    xinng_ensure_short_link_tables($pdo);
    xinng_ensure_credit_tables($pdo);
    xinng_ensure_communication_tables($pdo);

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

    $stmt = $pdo->prepare('SELECT id, title, destination_url, back_half, status, click_count, created_at, updated_at FROM short_links WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC, id ASC');
    $stmt->execute([$user_id]);
    $shortLinks = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT credit_balance FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$user_id]);
    $creditBalance = (int) $stmt->fetchColumn();

    $notifications = xinng_get_notifications($pdo, $user_id, 10);
    $unreadNotificationCount = xinng_unread_notification_count($pdo, $user_id);
} else {
    $dbError = true;
}

$displayTitle = $activePage['title'] ?? $user_name;
$displayBio = $activePage['bio'] ?? 'Create a smart QR-powered link page you can update anytime.';
$displaySlug = $activePage['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '', $user_name ?: 'user'));
$displayUrl = $activePage ? $base . '/u/' . $activePage['slug'] : $base . '/u/your-slug';
$mainPageUrl = $activePage ? xinng_short_url($activePage['slug']) : xinng_short_url('your-slug');
$qrUrl = $activePage ? $base . '/qr/' . $activePage['slug'] : $base . '/qr/your-slug';
$links = $activePage['links'] ?? [];
$totalViews = $activePage['page_views'] ?? 0;
$totalQr = $activePage['qr_scans'] ?? 0;
$totalShortClicks = array_sum(array_map(static fn($link) => (int)($link['click_count'] ?? 0), $shortLinks));
$totalClicks = ($activePage['link_clicks'] ?? 0) + $totalShortClicks;
$completion = $activePage ? min(100, 45 + (count($shortLinks) * 10) + (!empty($activePage['bio']) ? 10 : 0) + (!empty($activePage['qr']) ? 15 : 0)) : 30;
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
        <div class="account-actions" style="margin-top:8px">
          <a class="ghost-btn" href="logout.php" aria-label="Log out">Log out</a>
        </div>
      </div>

      <nav>
        <div class="nav-section">
          <div class="nav-heading"><span>Workspace</span><span><i class="fa-solid fa-chevron-up"></i></span></div>
          <a class="nav-item active" href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-link"></i></span>URL Links</a>
          <a class="nav-item" href="pages.php"><span class="nav-icon"><i class="fa-regular fa-file-lines"></i></span>Pages</a>
          <a class="nav-item" href="qr_codes.php"><span class="nav-icon"><i class="fa-solid fa-qrcode"></i></span>QR Codes</a>
          <a class="nav-item" href="credits.php"><span class="nav-icon"><i class="fa-solid fa-coins"></i></span>Credits</a>
          <a class="nav-item" href="insights.php"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>Insights</a>
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
          <div class="notification-wrapper">
            <button class="icon-btn notification-toggle" type="button" aria-label="Notifications">
              <i class="fa-solid fa-bell"></i>
              <?php if ($unreadNotificationCount > 0): ?>
                <span class="notification-badge"><?= (int)$unreadNotificationCount ?></span>
              <?php endif; ?>
            </button>
            <div class="notification-panel" id="notification-panel" hidden>
              <div class="notification-header">
                <strong>Notifications</strong>
                <button type="button" class="notification-mark-all" id="mark-all-read">Mark all read</button>
              </div>
              <?php if (empty($notifications)): ?>
                <div class="notification-empty">No new notifications.</div>
              <?php else: ?>
                <ul class="notification-list">
                  <?php foreach ($notifications as $notification): ?>
                    <li class="notification-item <?= !empty($notification['is_read']) ? 'read' : 'unread' ?>" data-id="<?= (int)$notification['id'] ?>">
                      <div class="notification-meta"><?= htmlspecialchars($notification['type']) ?></div>
                      <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                      <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                      <?php if (!empty($notification['action_url'])): ?>
                        <a href="<?= htmlspecialchars($notification['action_url']) ?>">Open</a>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
          <a class="icon-btn" href="#" aria-label="Settings"><i class="fa-solid fa-gear"></i></a>
          <a class="icon-btn" href="?action=logout" title="Logout" aria-label="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
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

          <div class="credit-summary" aria-label="Credit balance">
            <div class="credit-card">
              <div class="credit-card-top">
                <span class="credit-label">Credit balance</span>
                <span class="credit-status">Available credits</span>
              </div>
              <strong><?= number_format($creditBalance) ?></strong>
              <p>Save credits for short links, pages, QR codes, and campaign actions.</p>
              <a class="primary-btn" href="credits.php">Buy more credits</a>
            </div>
          </div>

          <div class="model-note" id="model-note">
            <button class="model-note-close" aria-label="Dismiss notice">×</button>
            <strong>Links are short-link assets.</strong> Pages are page builders, and QR codes are standalone trackable assets. Add existing links to this page, or create a new short link from here.
          </div>

          <button class="primary-btn add-btn" type="button" id="add-short-link"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Add short link</button>

          <section class="main-page-card" id="main-page-card" data-page-id="<?= e($activePage['id'] ?? '') ?>" data-base-url="<?= e(xinng_public_base_url()) ?>" data-public-prefix="<?= e(xinng_public_base_url() . '/') ?>">
            <div class="main-page-copy">
              <span class="eyebrow">Main Page URL</span>
              <h2><?= e($displayTitle) ?></h2>
              <p><?= e($displayBio) ?></p>
              <div class="main-page-url-row">
                <span class="main-page-domain"><?= e(str_replace(['http://', 'https://'], '', xinng_public_base_url())) ?>/</span>
                <strong id="main-page-slug-text"><?= e($activePage['slug'] ?? 'your-slug') ?></strong>
                <form id="main-page-slug-form" class="main-page-slug-form" hidden>
                  <span><?= e(str_replace(['http://', 'https://'], '', xinng_public_base_url())) ?>/</span>
                  <input id="main-page-slug-input" name="slug" type="text" value="<?= e($activePage['slug'] ?? '') ?>" autocomplete="off">
                  <button class="small-btn primary" type="submit">Save</button>
                  <button class="small-btn" type="button" id="cancel-main-page-slug">Cancel</button>
                </form>
              </div>
              <div class="main-page-status" id="main-page-status" role="status"></div>
            </div>
            <div class="main-page-actions">
              <button class="ghost-btn" id="edit-main-page-slug" type="button"><span class="label-icon"><i class="fa-solid fa-pen"></i></span>Edit URL</button>
              <?php if ($activePage): ?><a class="ghost-btn" href="page_builder.php?id=<?= e($activePage['id']) ?>"><span class="label-icon"><i class="fa-regular fa-file-lines"></i></span>Edit page</a><?php endif; ?>
              <button class="icon-btn copy-main-page-url" type="button" data-copy="<?= e($mainPageUrl) ?>" title="Copy URL" aria-label="Copy URL"><i class="fa-solid fa-copy"></i></button>
              <a class="icon-btn" id="main-page-open-link" href="<?= e($mainPageUrl) ?>" target="_blank" rel="noopener" title="Open page" aria-label="Open page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
          </section>

         

          <div class="secondary-row">
            <a class="ghost-btn" href="#"><span class="label-icon"><i class="fa-solid fa-layer-group"></i></span>Add collection</a>
            <a class="archive-link" href="#"><span class="label-icon"><i class="fa-solid fa-box-archive"></i></span>View archive <span aria-hidden="true">></span></a>
          </div>

          <div id="short-link-list">
          <?php if (empty($shortLinks)): ?>
            <div class="empty-state" id="empty-short-links">
              <strong>No short links yet.</strong><br>
              Create a memorable short link for a campaign or destination, then add it to this page when needed.
            </div>
          <?php else: ?>
            <?php foreach ($shortLinks as $index => $link): ?>
              <?php
                $title = $link['title'] ?: $link['back_half'];
                $shortUrl = xinng_short_url($link['back_half']);
              ?>
              <article class="link-card short-link-card" data-id="<?= e($link['id']) ?>" data-title="<?= e($title) ?>" data-back-half="<?= e($link['back_half']) ?>" data-destination-url="<?= e($link['destination_url']) ?>">
                <div class="drag" aria-hidden="true">::</div>
                <div>
                  <div class="link-title">
                    <?= e($title) ?>
                    <button class="tiny-edit edit-short-link" type="button" aria-label="Edit <?= e($title) ?>">edit</button>
                  </div>
                  <div class="link-url">
                    <span class="link-slug-text"><?= e($shortUrl) ?></span>
                    <span class="link-url-actions">
                      <button class="tiny-edit copy-short-link" type="button" data-copy="<?= e($shortUrl) ?>" aria-label="Copy short URL">copy</button>
                      <span class="slug-share-icon" title="Copy short URL"><i class="fa-solid fa-copy"></i></span>
                    </span>
                  </div>
                  <div class="destination-url">
                    <span>Destination</span>
                    <strong><?= e($link['destination_url']) ?></strong>
                  </div>
                  <div class="tool-row">
                    <span class="active-tool" title="Short link"><i class="fa-solid fa-link"></i></span>
                    <span title="Active"><?= e($link['status']) ?></span>
                    <span title="Analytics"><i class="fa-solid fa-chart-simple"></i></span>
                    <span class="click-count"><i class="fa-solid fa-arrow-pointer"></i><?= number_format((int) ($link['click_count'] ?? 0)) ?> clicks</span>
                  </div>
                </div>
                <div class="link-actions">
                  <span class="toggle<?= ($link['status'] ?? '') === 'active' ? ' on' : '' ?>" aria-label="<?= ($link['status'] ?? '') === 'active' ? 'Active' : 'Inactive' ?>"></span>
                  <button class="action-icon archive-short-link" type="button" title="Archive"><i class="fa-regular fa-trash-can"></i></button>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <aside class="preview" aria-label="Public page preview">
      <header class="preview-header">
        <div class="url-pill">

          <button class="pill-share" aria-label="Share page" title="Share page"><i class="fa-solid fa-share-nodes"></i></button>

          <a class="label-icon" id="preview-open-link" href="<?= e($mainPageUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page"><i class="fa-solid fa-mouse-pointer"></i></a>
          
          <span class="url-text" id="preview-url-text"><?= e(str_replace(['http://', 'https://'], '', $mainPageUrl)) ?></span>
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
      const card = document.getElementById('main-page-card');
      if (!card) return;
      const pageId = card.dataset.pageId;
      const publicPrefix = card.dataset.publicPrefix || '';
      const editBtn = document.getElementById('edit-main-page-slug');
      const form = document.getElementById('main-page-slug-form');
      const input = document.getElementById('main-page-slug-input');
      const cancel = document.getElementById('cancel-main-page-slug');
      const slugText = document.getElementById('main-page-slug-text');
      const status = document.getElementById('main-page-status');
      const copyBtn = document.querySelector('.copy-main-page-url');
      const mainOpen = document.getElementById('main-page-open-link');
      const previewOpen = document.getElementById('preview-open-link');
      const previewText = document.getElementById('preview-url-text');

      function normalize(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');
      }
      function publicUrl(slug) {
        return publicPrefix + slug;
      }
      function displayUrl(slug) {
        return publicUrl(slug).replace(/^https?:\/\//, '');
      }
      function setStatus(message, kind) {
        status.textContent = message || '';
        status.dataset.kind = kind || '';
      }
      function setEditing(isEditing) {
        form.hidden = !isEditing;
        slugText.hidden = isEditing;
        editBtn.hidden = isEditing;
        if (isEditing) {
          input.value = slugText.textContent.trim();
          input.focus();
          input.select();
          setStatus('', '');
        }
      }
      function syncUrl(slug) {
        const url = publicUrl(slug);
        slugText.textContent = slug;
        input.value = slug;
        copyBtn.dataset.copy = url;
        mainOpen.href = url;
        previewOpen.href = url;
        previewText.textContent = displayUrl(slug);
      }

      editBtn?.addEventListener('click', () => setEditing(true));
      cancel?.addEventListener('click', () => setEditing(false));
      input?.addEventListener('input', () => {
        input.value = normalize(input.value);
        setStatus(input.value.length < 3 ? 'Use at least 3 characters.' : '', input.value.length < 3 ? 'error' : '');
      });
      form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const nextSlug = normalize(input.value);
        if (!pageId) return setStatus('Create a page before editing the URL.', 'error');
        if (nextSlug.length < 3) return setStatus('Use at least 3 characters.', 'error');
        const saveBtn = form.querySelector('button[type="submit"]');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        setStatus('', '');
        const body = new URLSearchParams({ page_id: pageId, slug: nextSlug, csrf_token: csrf });
        try {
          const response = await fetch('update_slug.php', { method: 'POST', body });
          const data = await response.json();
          if (!data.ok) {
            setStatus(data.error === 'taken' ? 'That URL is already taken.' : 'Could not save this URL.', 'error');
            return;
          }
          syncUrl(data.slug);
          setEditing(false);
          setStatus('URL updated.', 'success');
        } catch (error) {
          setStatus('Network error. Try again.', 'error');
        } finally {
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save';
        }
      });
      copyBtn?.addEventListener('click', async () => {
        const value = copyBtn.dataset.copy || '';
        try {
          await navigator.clipboard.writeText(value);
          setStatus('URL copied.', 'success');
        } catch (error) {
          alert(value);
        }
      });
    })();
    (function(){
      const toggle = document.querySelector('.notification-toggle');
      const panel = document.getElementById('notification-panel');
      const markAllRead = document.getElementById('mark-all-read');
      if (toggle && panel) {
        toggle.addEventListener('click', () => {
          panel.hidden = !panel.hidden;
        });
      }
      markAllRead?.addEventListener('click', async () => {
        const response = await fetch('notifications.php?action=mark_all_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'csrf_token=<?= e(csrf_token()) ?>'
        });
        if (response.ok) {
          document.querySelectorAll('.notification-item.unread').forEach((item) => item.classList.remove('unread'));
          const badge = document.querySelector('.notification-badge');
          badge?.remove();
          panel.hidden = false;
        }
      });
      document.querySelectorAll('.notification-item').forEach((item) => {
        item.addEventListener('click', async () => {
          const id = item.dataset.id;
          if (!id) return;
          await fetch('notifications.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id) + '&csrf_token=<?= e(csrf_token()) ?>'
          });
          item.classList.remove('unread');
          item.classList.add('read');
        });
      });
    })();
    (function(){
      const note = document.getElementById('model-note');
      const close = note?.querySelector('.model-note-close');
      if (!note || !close) return;
      close.addEventListener('click', ()=>{ note.style.display = 'none'; });
    })();
    (function(){
      const csrf = '<?= e(csrf_token()) ?>';
      const list = document.getElementById('short-link-list');
      const addBtn = document.getElementById('add-short-link');
      if (!list || !addBtn) return;

      function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function(char) {
          return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]);
        });
      }

      function formCard(data = {}) {
        const isEdit = Boolean(data.id);
        const article = document.createElement('article');
        article.className = 'link-card short-link-card editing';
        if (isEdit) article.dataset.id = data.id;
        article.innerHTML = `
          <form class="short-link-form">
            <div class="form-grid">
              <div class="field">
                <label>Title</label>
                <input name="title" type="text" value="${escapeHtml(data.title || '')}" placeholder="Sheltercon campaign">
              </div>
              <div class="field">
                <label>Back-half</label>
                <input name="back_half" type="text" value="${escapeHtml(data.back_half || '')}" placeholder="sheltercon" required>
              </div>
            </div>
            <div class="field">
              <label>Destination URL</label>
              <input name="destination_url" type="text" value="${escapeHtml(data.destination_url || '')}" placeholder="link.com, www.link.com, https://link.com, or http://link.com" required>
            </div>
            <div class="form-actions">
              <div class="form-error" role="alert"></div>
              <div class="form-actions-main">
                <button class="small-btn cancel-short-link" type="button">Cancel</button>
                <button class="small-btn primary save-short-link" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save</button>
              </div>
            </div>
          </form>
        `;
        return article;
      }

      function showError(form, message) {
        const error = form.querySelector('.form-error');
        if (error) error.textContent = message || '';
      }

      async function requestShortLink(payload, method) {
        const body = new URLSearchParams(payload);
        body.append('csrf_token', csrf);
        if (method && method !== 'POST') body.append('_method', method);
        const response = await fetch('api/short-links.php', { method: 'POST', body });
        const data = await response.json().catch(() => ({ ok: false, error: 'Invalid server response.' }));
        return { status: response.status, data };
      }

      addBtn.addEventListener('click', () => {
        const existingDraft = list.querySelector('.short-link-card.editing:not([data-id])');
        if (existingDraft) {
          existingDraft.scrollIntoView({ behavior: 'smooth', block: 'center' });
          existingDraft.querySelector('input')?.focus();
          return;
        }
        document.getElementById('empty-short-links')?.remove();
        const card = formCard();
        list.appendChild(card);
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.querySelector('input')?.focus();
      });

      list.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('.edit-short-link');
        if (editBtn) {
          const card = editBtn.closest('.short-link-card');
          if (!card) return;
          card.replaceWith(formCard({
            id: card.dataset.id,
            title: card.dataset.title,
            back_half: card.dataset.backHalf,
            destination_url: card.dataset.destinationUrl
          }));
          return;
        }

        const cancelBtn = event.target.closest('.cancel-short-link');
        if (cancelBtn) {
          const card = cancelBtn.closest('.short-link-card');
          if (!card) return;
          if (card.dataset.id) {
            location.reload();
          } else {
            card.remove();
            if (!list.querySelector('.short-link-card')) {
              list.innerHTML = '<div class="empty-state" id="empty-short-links"><strong>No short links yet.</strong><br>Create a memorable short link for a campaign or destination, then add it to this page when needed.</div>';
            }
          }
          return;
        }

        const copyBtn = event.target.closest('.copy-short-link, .slug-share-icon');
        if (copyBtn) {
          const copyValue = copyBtn.dataset.copy || copyBtn.closest('.link-url')?.querySelector('.link-slug-text')?.textContent?.trim();
          if (!copyValue) return;
          try {
            await navigator.clipboard.writeText(copyValue);
            copyBtn.closest('.link-url')?.classList.add('copied');
            setTimeout(() => copyBtn.closest('.link-url')?.classList.remove('copied'), 900);
          } catch (e) {
            alert(copyValue);
          }
          return;
        }

        const archiveBtn = event.target.closest('.archive-short-link');
        if (archiveBtn) {
          const card = archiveBtn.closest('.short-link-card');
          if (!card?.dataset.id) return;
          if (!confirm('Archive this short link? Analytics will be preserved.')) return;
          archiveBtn.disabled = true;
          const result = await requestShortLink({ id: card.dataset.id }, 'DELETE');
          if (result.data.ok) {
            card.remove();
            if (!list.querySelector('.short-link-card')) {
              list.innerHTML = '<div class="empty-state" id="empty-short-links"><strong>No short links yet.</strong><br>Create a memorable short link for a campaign or destination, then add it to this page when needed.</div>';
            }
          } else {
            alert(result.data.error || 'Unable to archive short link.');
            archiveBtn.disabled = false;
          }
        }
      });

      list.addEventListener('submit', async (event) => {
        const form = event.target.closest('.short-link-form');
        if (!form) return;
        event.preventDefault();
        showError(form, '');
        const card = form.closest('.short-link-card');
        const save = form.querySelector('.save-short-link');
        const payload = {
          title: form.elements.title.value.trim(),
          back_half: form.elements.back_half.value.trim(),
          destination_url: form.elements.destination_url.value.trim()
        };
        if (card?.dataset.id) payload.id = card.dataset.id;
        save.disabled = true;
        const result = await requestShortLink(payload, card?.dataset.id ? 'PATCH' : 'POST');
        if (result.status === 409 && result.data.requires_confirmation) {
          save.disabled = false;
          if (confirm(result.data.message || 'Changing the destination URL will create a new short link so analytics stay accurate. Continue?')) {
            payload.confirm_create_new = '1';
            const confirmed = await requestShortLink(payload, 'PATCH');
            if (confirmed.data.ok) location.reload();
            else showError(form, confirmed.data.error || 'Unable to save short link.');
          }
          return;
        }
        if (result.data.ok) {
          location.reload();
        } else {
          showError(form, result.data.error || 'Unable to save short link.');
          save.disabled = false;
        }
      });
    })();
  </script>
</body>
</html>
