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

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

function e($value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function initials(string $name): string {
	$clean = preg_replace('/[^A-Za-z0-9 ]/', '', $name);
	$parts = array_values(array_filter(explode(' ', trim($clean))));
	if (count($parts) >= 2) return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
	return strtoupper(substr($clean ?: 'X', 0, 2));
}

function qr_card_image(array $qr): string {
	return xinng_qr_image_url_for_row($qr);
}

$pdo = get_db_connection();
$dbError = false;
$activePage = null;
$qrCodes = [];
$totalViews = 0;
$totalQr = 0;
$totalClicks = 0;

if ($pdo) {
	xinng_ensure_short_link_tables($pdo);
	xinng_ensure_qr_code_tables($pdo);

	$stmt = $pdo->prepare('SELECT id, slug, title, bio FROM pages WHERE user_id = ? ORDER BY id ASC LIMIT 1');
	$stmt->execute([$user_id]);
	$activePage = $stmt->fetch() ?: null;

	if ($activePage) {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM page_views WHERE page_id = ?');
		$stmt->execute([(int)$activePage['id']]);
		$totalViews = (int)$stmt->fetchColumn();

		$stmt = $pdo->prepare('SELECT COUNT(*) FROM link_clicks WHERE page_id = ?');
		$stmt->execute([(int)$activePage['id']]);
		$totalClicks = (int)$stmt->fetchColumn();

		$destination = xinng_short_url($activePage['slug']);
		$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE user_id = ? AND type = "profile_page" AND profile_page_id = ? AND deleted_at IS NULL LIMIT 1');
		$stmt->execute([$user_id, (int)$activePage['id']]);
		$profileQr = $stmt->fetch();
		if (!$profileQr) {
			$title = $activePage['title'] . ' page QR';
			$stmt = $pdo->prepare('INSERT INTO qr_codes (user_id, page_id, profile_page_id, type, title, name, destination_url, status, code_color, background_color, pattern_style, corner_style, created_at, updated_at) VALUES (?, ?, ?, "profile_page", ?, ?, ?, "active", "#000000", "#FFFFFF", "default", "square", NOW(), NOW())');
			$stmt->execute([$user_id, (int)$activePage['id'], (int)$activePage['id'], $title, $title, $destination]);
			$id = (int)$pdo->lastInsertId();
			$stmt = $pdo->prepare('UPDATE qr_codes SET qr_image_url = ? WHERE id = ?');
			$stmt->execute([xinng_qr_image_url($id, '#000000', '#FFFFFF', $destination), $id]);
		} else {
			$stmt = $pdo->prepare('UPDATE qr_codes SET title = ?, destination_url = ?, page_id = ?, updated_at = NOW() WHERE id = ?');
			$stmt->execute([$activePage['title'] . ' page QR', $destination, (int)$activePage['id'], (int)$profileQr['id']]);
		}
	}

	$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE user_id = ? AND deleted_at IS NULL ORDER BY type = "profile_page" DESC, created_at ASC, id ASC');
	$stmt->execute([$user_id]);
	$qrCodes = $stmt->fetchAll();
	$totalQr = array_sum(array_map(static fn($qr) => (int)($qr['scan_count'] ?? 0), $qrCodes));
} else {
	$dbError = true;
}

$displayTitle = $activePage['title'] ?? $user_name;
$displaySlug = $activePage['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '', $user_name ?: 'user'));
$displayUrl = $activePage ? xinng_short_url($activePage['slug']) : xinng_short_url('your-slug');
$completion = $activePage ? min(100, 45 + (count($qrCodes) * 10) + (!empty($activePage['bio']) ? 10 : 0)) : 30;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR Codes - <?= e($APP_NAME ?? 'xin.ng') ?></title>
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
      <div class="brand-slot" aria-label="xin.ng brand"><img src="assets/logo.svg" alt="xin.ng logo"></div>
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
          <a class="nav-item" href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-link"></i></span>URL Links</a>
          <a class="nav-item" href="pages.php"><span class="nav-icon"><i class="fa-regular fa-file-lines"></i></span>Pages</a>
          <a class="nav-item active" href="qr_codes.php"><span class="nav-icon"><i class="fa-solid fa-qrcode"></i></span>QR Codes</a>
          <a class="nav-item" href="#"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>Insights</a>
        </div>
      </nav>
      <div class="setup-card">
        <div class="progress-ring" style="--completion: <?= (int)$completion ?>%;"><?= (int)$completion ?>%</div>
        <strong>Your setup checklist</strong>
        <p><?= $activePage ? '4 of 6 complete' : '1 of 6 complete' ?></p>
        <a class="primary-btn" href="signup.php"><span class="label-icon"><i class="fa-solid fa-check"></i></span>Finish setup</a>
      </div>
    </aside>

    <main class="main">
      <header class="main-header">
        <div class="header-title">
          <h1>QR Codes</h1>
          <span>Create trackable QR assets for your page, links, and campaigns.</span>
        </div>
        <div class="header-actions">
          <a class="icon-btn" href="#" aria-label="Settings"><i class="fa-solid fa-gear"></i></a>
        </div>
      </header>

      <div class="content">
        <div class="editor-column">
          <?php if ($dbError): ?><div class="notice">Database connection is not available.</div><?php endif; ?>
          <div class="stats-row" aria-label="Workspace analytics summary">
            <div class="stat-card"><strong><?= number_format($totalViews) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-regular fa-eye"></i></span>Page views</span></div>
            <div class="stat-card"><strong><?= number_format($totalQr) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-qrcode"></i></span>QR scans</span></div>
            <div class="stat-card"><strong><?= number_format($totalClicks) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-arrow-pointer"></i></span>Link clicks</span></div>
          </div>

          <button class="primary-btn add-btn centered-action" type="button" id="create-qr-code"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Create QR Code</button>

          <div id="qr-code-list">
            <?php foreach ($qrCodes as $qr): ?>
              <?php
                $isProfile = ($qr['type'] ?? '') === 'profile_page';
                $title = $qr['title'] ?: ($qr['name'] ?? 'QR Code');
                $destination = $qr['destination_url'] ?: $displayUrl;
                $qrImage = qr_card_image($qr);
              ?>
              <article class="link-card qr-card" data-id="<?= e($qr['id']) ?>" data-title="<?= e($title) ?>" data-destination-url="<?= e($destination) ?>" data-back-half="<?= e($qr['back_half'] ?? '') ?>">
                <div class="qr-thumb"><img src="<?= e($qrImage) ?>" alt="<?= e($title) ?> QR code"></div>
                <div>
                  <div class="link-title">
                    <?= e($title) ?>
                    <span class="qr-badge"><?= $isProfile ? 'Profile page' : e($qr['type'] ?: 'Website') ?></span>
                  </div>
                  <div class="destination-url">
                    <span>Destination</span>
                    <strong><?= e($destination) ?></strong>
                  </div>
                  <?php if (!empty($qr['back_half'])): ?>
                    <div class="link-url">
                      <span class="link-slug-text"><?= e(xinng_short_url($qr['back_half'])) ?></span>
                      <span class="link-url-actions"><span class="slug-share-icon" title="Copy"><i class="fa-solid fa-copy"></i></span></span>
                    </div>
                  <?php endif; ?>
                  <div class="tool-row">
                    <span class="active-tool" title="QR"><i class="fa-solid fa-qrcode"></i></span>
                    <span class="click-count"><i class="fa-solid fa-camera"></i><?= number_format((int)($qr['scan_count'] ?? 0)) ?> scans</span>
                    <span title="Created"><i class="fa-regular fa-calendar"></i></span>
                    <span><?= e(date('M j, Y', strtotime($qr['created_at'] ?? 'now'))) ?></span>
                  </div>
                </div>
                <div class="link-actions">
                  <a class="action-icon" href="edit_qr_code.php?id=<?= e($qr['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <?php /* TODO: Replace this legacy static download with styled qr-code-styling export from the QR editor. */ ?>
                  <a class="action-icon" href="<?= e($qrImage) ?>" target="_blank" rel="noopener" title="Open QR code"><i class="fa-solid fa-download"></i></a>
                  <span class="action-icon" title="Analytics"><i class="fa-solid fa-chart-simple"></i></span>
                  <?php if (!$isProfile): ?><button class="action-icon archive-qr-code" type="button" title="Archive"><i class="fa-solid fa-ellipsis"></i></button><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>

    <aside class="preview" aria-label="Public page preview">
      <header class="preview-header">
        <div class="url-pill">
          <button class="pill-share" aria-label="Share page" title="Share page"><i class="fa-solid fa-share-nodes"></i></button>
          <a class="label-icon" href="<?= e($displayUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page"><i class="fa-solid fa-mouse-pointer"></i></a>
          <span class="url-text"><?= e(str_replace(['http://', 'https://'], '', $displayUrl)) ?></span>
        </div>
      </header>
      <div class="preview-body">
        <div class="phone">
          <div class="phone-screen">
            <div class="phone-avatar"><?= e(initials($displayTitle)) ?></div>
            <h2><?= e($displayTitle) ?></h2>
            <p><?= e($activePage['bio'] ?? 'Create a smart QR-powered link page you can update anytime.') ?></p>
            <div class="phone-socials"><span>IG</span><span>WA</span><span>X</span></div>
            <a class="phone-link hero" href="#"><span class="hero-icon">+</span><strong>Create QR Code</strong></a>
            <div class="phone-brand">xin.ng QR preview</div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <script>
  (function(){
    const csrf = '<?= e(csrf_token()) ?>';
    const button = document.getElementById('create-qr-code');
    const list = document.getElementById('qr-code-list');
    if (!button || !list) return;

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]);
      });
    }

    function formCard() {
      const card = document.createElement('article');
      card.className = 'link-card qr-card editing';
      card.innerHTML = `
        <form class="short-link-form qr-form">
          <div class="form-grid">
            <div class="field"><label>Title</label><input name="title" type="text" placeholder="Build with Sheltercon" required></div>
            <div class="field"><label>Back-half</label><input name="back_half" type="text" placeholder="sheltercon"></div>
          </div>
          <div class="field"><label>Destination URL</label><input name="destination_url" type="text" placeholder="link.com, www.link.com, https://link.com, or http://link.com" required></div>
          <div class="form-grid">
            <div class="field"><label>Code color</label><input name="code_color" type="text" value="#000000"></div>
            <div class="field"><label>Background color</label><input name="background_color" type="text" value="#FFFFFF"></div>
          </div>
          <input type="hidden" name="type" value="website">
          <div class="form-actions">
            <div class="form-error" role="alert"></div>
            <div class="form-actions-main">
              <button class="small-btn cancel-qr-form" type="button">Cancel</button>
              <button class="small-btn primary save-qr-code" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save</button>
            </div>
          </div>
        </form>`;
      return card;
    }

    button.addEventListener('click', () => {
      if (list.querySelector('.qr-card.editing')) return;
      const card = formCard();
      list.appendChild(card);
      card.scrollIntoView({ behavior: 'smooth', block: 'center' });
      card.querySelector('input')?.focus();
    });

    list.addEventListener('click', async (event) => {
      const cancel = event.target.closest('.cancel-qr-form');
      if (cancel) cancel.closest('.qr-card')?.remove();
      const archive = event.target.closest('.archive-qr-code');
      if (archive) {
        const card = archive.closest('.qr-card');
        if (!card?.dataset.id || !confirm('Archive this QR code? Scan analytics will be preserved.')) return;
        const body = new URLSearchParams({ _method: 'DELETE', id: card.dataset.id, csrf_token: csrf });
        const response = await fetch('api/qr-codes.php', { method: 'POST', body });
        const data = await response.json();
        if (data.ok) card.remove();
        else alert(data.error || 'Unable to archive QR code.');
      }
    });

    list.addEventListener('submit', async (event) => {
      const form = event.target.closest('.qr-form');
      if (!form) return;
      event.preventDefault();
      const error = form.querySelector('.form-error');
      const save = form.querySelector('.save-qr-code');
      error.textContent = '';
      save.disabled = true;
      const body = new URLSearchParams(new FormData(form));
      body.append('csrf_token', csrf);
      const response = await fetch('api/qr-codes.php', { method: 'POST', body });
      const data = await response.json().catch(() => ({ ok: false, error: 'Invalid server response.' }));
      if (data.ok) location.reload();
      else { error.textContent = data.error || 'Unable to save QR code.'; save.disabled = false; }
    });
  })();
  </script>
</body>
</html>
