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
$shortLinks = [];
$dbError = false;
$pdo = get_db_connection();
$base = public_base_url();

if ($pdo) {
    xinng_ensure_short_link_tables($pdo);

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

           <button class="primary-btn add-btn" type="button" id="add-short-link"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Add short link</button>

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

          <a class="label-icon" class="icon-btn" href="<?= e($displayUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page"><i class="fa-solid fa-mouse-pointer"></i></a>
          
          <span class="url-text"><?= e(str_replace(['http://', 'https://'], '', $displayUrl)) ?></span>
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
      const editBtn = document.getElementById('edit-slug-btn');
      const slugText = document.getElementById('page-slug-text');
      const slugContainer = document.getElementById('page-slug');
      const urlPill = document.querySelector('.url-pill');
      const urlText = document.querySelector('.url-pill .url-text');
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
              if (urlText) urlText.textContent = urlText.textContent.replace(/\/u\/[^\s]+$/, '/u/'+data.slug);
            } else {
              alert('Error: '+(data.error||'unknown'));
              save.disabled = false; save.textContent = 'Save';
            }
          }).catch(()=>{ alert('Network error'); save.disabled = false; save.textContent = 'Save'; });
        });
      });
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
