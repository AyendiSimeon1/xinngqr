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

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pages_initials(string $name): string { return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'X', 0, 2)); }
function pages_status_label(array $page): string { return (($page['status'] ?? '') === 'published' || !empty($page['is_published'])) ? 'Published' : 'Draft'; }
function pages_type_label(array $page): string { return ($page['page_type'] ?? 'creator') === 'corporate' ? 'Company Page' : 'Personal Page'; }

$pdo = get_db_connection();
$pages = [];
$dbError = false;
if ($pdo) {
	xinng_ensure_page_builder_tables($pdo);
	$stmt = $pdo->prepare('
		SELECT p.*,
			(SELECT COUNT(*) FROM page_views pv WHERE pv.page_id = p.id) AS views,
			(SELECT COUNT(*) FROM page_blocks pb WHERE pb.page_id = p.id) AS engagements
		FROM pages p
		WHERE p.user_id = ? AND p.deleted_at IS NULL
		ORDER BY p.updated_at DESC, p.id DESC
	');
	$stmt->execute([$user_id]);
	$pages = $stmt->fetchAll();
} else {
	$dbError = true;
}

$activePage = $pages[0] ?? null;
$displayPageType = ($activePage['page_type'] ?? 'creator') === 'corporate' ? 'corporate' : 'creator';
$displayTitle = $activePage['title'] ?? 'Your Xinng page';
$displayDescription = $activePage['description'] ?? ($activePage['bio'] ?? 'Create a mobile-first landing page for your links, QR codes, and campaigns.');
$displayUrl = $activePage ? xinng_short_url($activePage['slug']) : xinng_short_url('your-page');
$totalViews = array_sum(array_map(static fn($page) => (int)($page['views'] ?? 0), $pages));
$totalEngagements = array_sum(array_map(static fn($page) => (int)($page['engagements'] ?? 0), $pages));
$completion = min(100, 35 + (count($pages) * 12));
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pages - <?= e($APP_NAME ?? 'xin.ng') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
  <div class="upgrade-bar">
    <span class="spark"><i class="fa-solid fa-bolt"></i></span>
    <span>Elevate your pages with better themes and styles.</span>
    <a class="upgrade-pill" href="#">Lightning Upgrade</a>
  </div>

  <div class="dashboard">
    <aside class="sidebar" aria-label="Dashboard navigation">
      <div class="brand-slot"><img src="assets/logo.svg" alt="xin.ng logo"></div>

      <div class="account">
        <div class="account-main">
          <span class="avatar"><?= e(pages_initials($user_name)) ?></span>
          <span><?= e($activePage['slug'] ?? $user_name) ?></span>
          <span aria-hidden="true">v</span>
        </div>
      </div>

      <nav>
        <div class="nav-section">
          <div class="nav-heading"><span>Workspace</span><span><i class="fa-solid fa-chevron-up"></i></span></div>
          <a class="nav-item" href="dashboard.php"><span class="nav-icon"><i class="fa-solid fa-link"></i></span>URL Links</a>
          <a class="nav-item active" href="pages.php"><span class="nav-icon"><i class="fa-regular fa-file-lines"></i></span>Pages</a>
          <a class="nav-item" href="qr_codes.php"><span class="nav-icon"><i class="fa-solid fa-qrcode"></i></span>QR Codes</a>
          <a class="nav-item" href="#"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>Insights</a>
        </div>
      </nav>

      <div class="setup-card">
        <div class="progress-ring" style="--completion: <?= (int)$completion ?>%;"><?= (int)$completion ?>%</div>
        <strong>Your setup checklist</strong>
        <p><?= count($pages) ? '4 of 6 complete' : '1 of 6 complete' ?></p>
        <button class="primary-btn" id="sidebar-create-page" type="button"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Finish setup</button>
      </div>
    </aside>

    <main class="main">
      <header class="main-header">
        <div class="header-title">
          <h1>Pages</h1>
          <span>Create mobile landing pages at <?= e(str_replace(['http://','https://'], '', xinng_short_url('your-page'))) ?></span>
        </div>
        <div class="header-actions">
          <a class="icon-btn" href="#" aria-label="Settings"><i class="fa-solid fa-gear"></i></a>
          <a class="icon-btn" href="?action=logout" title="Logout" aria-label="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        </div>
      </header>

      <div class="content">
        <div class="editor-column">
          <?php if ($dbError): ?><div class="notice">Database connection is not available.</div><?php endif; ?>

          <div class="stats-row" aria-label="Pages analytics summary">
            <div class="stat-card"><strong><?= number_format(count($pages)) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-regular fa-file-lines"></i></span>Total pages</span></div>
            <div class="stat-card"><strong><?= number_format($totalViews) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-regular fa-eye"></i></span>Page views</span></div>
            <div class="stat-card"><strong><?= number_format($totalEngagements) ?></strong><span class="stat-label"><span class="label-icon"><i class="fa-solid fa-bullseye"></i></span>Engagements</span></div>
          </div>

          <div class="model-note" id="pages-model-note">
            <button class="model-note-close" aria-label="Dismiss notice">x</button>
            <strong>Xinng Pages includes Personal Page and Company Page.</strong> Both use the same builder, blocks, QR tracking, short links, and public renderer.
          </div>

          <button class="primary-btn add-btn" id="create-page" type="button"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Create Page</button>

          <div class="secondary-row">
            <a class="ghost-btn" href="#"><span class="label-icon"><i class="fa-solid fa-layer-group"></i></span>Collections</a>
            <a class="archive-link" href="#"><span class="label-icon"><i class="fa-solid fa-box-archive"></i></span>View archive <span aria-hidden="true">></span></a>
          </div>

          <div id="page-list">
            <?php if (empty($pages)): ?>
              <div class="empty-state">
                <strong>No pages yet.</strong><br>
                Create your first Xinng page to start building.
              </div>
            <?php endif; ?>

            <?php foreach ($pages as $index => $page): ?>
              <?php
                $url = xinng_short_url($page['slug']);
                $title = $page['title'] ?: $page['slug'];
                $description = $page['description'] ?: ($page['bio'] ?? '');
              ?>
              <article class="link-card page-link-card<?= $index === 0 ? ' selected' : '' ?>" data-title="<?= e($title) ?>" data-description="<?= e($description) ?>" data-url="<?= e($url) ?>" data-page-type="<?= e(($page['page_type'] ?? 'creator') === 'corporate' ? 'corporate' : 'creator') ?>" data-header="<?= e($page['header_color'] ?: '#26282C') ?>" data-block="<?= e($page['block_color'] ?: '#0A9994') ?>">
                <div class="drag" aria-hidden="true">::</div>
                <div class="page-card-preview" style="--thumb-bg: <?= e($page['header_color'] ?: '#26282C') ?>; --thumb-block: <?= e($page['block_color'] ?: '#0A9994') ?>;">
                  <div class="page-card-preview-head"></div>
                  <div class="page-card-preview-avatar"><?= e(pages_initials($title)) ?></div>
                  <div class="page-card-preview-line"></div>
                  <div class="page-card-preview-line short"></div>
                </div>
                <div>
                  <div class="link-title">
                    <?= e(str_replace(['http://','https://'], '', $url)) ?>
                    <a class="tiny-edit" href="page_builder.php?id=<?= e($page['id']) ?>">edit</a>
                  </div>
                  <div class="destination-url">
                    <span><?= e(pages_type_label($page)) ?> · <?= e(pages_status_label($page)) ?></span>
                    <strong><?= e($title) ?></strong>
                  </div>
                  <div class="tool-row">
                    <span class="active-tool" title="Page"><i class="fa-regular fa-file-lines"></i></span>
                    <span title="Created"><i class="fa-regular fa-calendar"></i></span>
                    <span><?= e(date('M j, Y', strtotime($page['created_at'] ?? 'now'))) ?></span>
                    <span title="Views"><i class="fa-regular fa-eye"></i></span>
                    <span><?= number_format((int)($page['views'] ?? 0)) ?> views</span>
                    <span title="Engagements"><i class="fa-solid fa-bullseye"></i></span>
                    <span><?= number_format((int)($page['engagements'] ?? 0)) ?></span>
                  </div>
                </div>
                <div class="link-actions">
                  <a class="action-icon" href="page_builder.php?id=<?= e($page['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <button class="action-icon copy-page" type="button" data-copy="<?= e($url) ?>" title="Copy page URL"><i class="fa-solid fa-share-nodes"></i></button>
                  <a class="action-icon" href="<?= e($url) ?>" target="_blank" rel="noopener" title="Open public page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                  <button class="action-icon" type="button" title="More"><i class="fa-solid fa-ellipsis"></i></button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>

    <aside class="preview" aria-label="Page preview">
      <header class="preview-header">
        <div class="url-pill">
          <button class="pill-share" aria-label="Share page" title="Share page"><i class="fa-solid fa-share-nodes"></i></button>
          <a class="label-icon" href="<?= e($displayUrl) ?>" target="_blank" rel="noopener" aria-label="Open public page"><i class="fa-solid fa-mouse-pointer"></i></a>
          <span class="url-text" id="preview-page-url"><?= e(str_replace(['http://','https://'], '', $displayUrl)) ?></span>
        </div>
      </header>

      <div class="preview-body">
        <div class="phone">
          <div class="phone-screen pages-phone-screen" id="pages-phone-screen" style="--preview-header: <?= e($activePage['header_color'] ?? '#26282C') ?>; --preview-block: <?= e($activePage['block_color'] ?? '#0A9994') ?>;">
            <div class="pages-preview-header">
              <div class="phone-avatar"><?= e(pages_initials($displayTitle)) ?></div>
            </div>
            <h2 id="preview-page-title"><?= e($displayTitle) ?></h2>
            <p id="preview-page-description"><?= e($displayDescription) ?></p>
            <div class="phone-socials"><span>IG</span><span>WA</span><span>X</span></div>
            <a class="phone-link hero pages-preview-block" id="preview-primary-action" href="#"><strong><?= $displayPageType === 'corporate' ? 'Request Quote' : 'Follow me' ?></strong></a>
            <a class="phone-link pages-preview-block" id="preview-secondary-action" href="#"><?= $displayPageType === 'corporate' ? 'Download Company Profile' : 'Book me' ?></a>
            <a class="phone-link pages-preview-block" id="preview-tertiary-action" href="#"><?= $displayPageType === 'corporate' ? 'Book Technical Meeting' : 'Subscribe' ?></a>
            <div class="phone-brand">xin.ng page preview</div>
          </div>
        </div>
      </div>
    </aside>
  </div>

  <div class="page-type-modal" id="page-type-modal" hidden>
    <div class="page-type-dialog" role="dialog" aria-modal="true" aria-labelledbya="page-type-title">
      <button class="model-notse-close page-type-close" type="button" aria-label="Close">x</button>
      <h2 id="page-type-title">What are you building?</h2>
      <div class="page-type-options">
        <button class="page-type-option" type="button" data-page-type="creator">
          <strong>Creator Page</strong>
          <span>For personal brands, creators, freelancers, consultants, and individuals.</span>
          <small>Grow audience · Share links · Sell products · Collect bookings to change · Share content</small>
        </button>
        <button class="page-type-option" type="button" data-page-type="corporate">
          <strong>Company Page</strong>
          <span>For companies, events, documents, meetings, products, teams, and business inquiries.</span>
          <small>Get inquiries · Book meetings · Share documents · Promote event · Request quote · Route contacts</small>
        </button>
      </div>
      <div class="page-title-form" id="page-title-form">
        <input type="hidden" id="page-type-hidden" name="page_type" value="creator">
        <label class="page-title-label" for="page-title-input">Page title</label>
        <div class="page-title-actions">
          <input class="page-title-input" id="page-title-input" name="title" type="text" placeholder="Enter a page title" autocomplete="off">
          <button class="primary-btn page-title-submit" id="page-title-submit" type="button">Create page</button>
        </div>
      </div>
      <div class="page-create-status" id="page-create-status" role="alert" aria-live="polite" hidden>
        <div class="page-create-status__icon"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div>
          <strong id="page-create-status-title">Unable to create page</strong>
          <p id="page-create-status-message">Please try again in a moment.</p>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function(){
    const csrf = <?= json_encode($csrf) ?>;
    const createButtons = [document.getElementById('create-page'), document.getElementById('sidebar-create-page')].filter(Boolean);
    const modal = document.getElementById('page-type-modal');
    const statusBox = document.getElementById('page-create-status');
    const statusTitle = document.getElementById('page-create-status-title');
    const statusMessage = document.getElementById('page-create-status-message');
    const titleInput = document.getElementById('page-title-input');
    const titleSubmit = document.getElementById('page-title-submit');
    const pageTypeHidden = document.getElementById('page-type-hidden');
    const typeOptions = Array.from(document.querySelectorAll('.page-type-option'));
    let selectedPageType = null;
    function clearStatus() {
      if (!statusBox) return;
      statusBox.hidden = true;
      statusBox.classList.remove('is-success');
    }
    function showStatus(type, title, message) {
      if (!statusBox || !statusTitle || !statusMessage) return;
      statusBox.hidden = false;
      statusBox.classList.toggle('is-success', type === 'success');
      statusTitle.textContent = title;
      statusMessage.textContent = message;
    }
    function openTypeModal() {
      clearStatus();
      if (modal) modal.hidden = false;
      if (titleInput) {
        titleInput.value = '';
        titleInput.placeholder = 'Enter a page title';
        titleInput.focus();
      }
      selectedPageType = null;
      typeOptions.forEach(option => option.classList.remove('is-active'));
    }
    function closeTypeModal() {
      clearStatus();
      if (modal) modal.hidden = true;
    }
    function setSelectedPageType(pageType) {
      selectedPageType = pageType;
      if (pageTypeHidden) pageTypeHidden.value = pageType;
      typeOptions.forEach(option => option.classList.toggle('is-active', option.dataset.pageType === pageType));
      if (titleInput) {
        const defaultTitle = pageType === 'corporate' ? 'Company Page' : 'Creator Page';
        if (!titleInput.value.trim()) {
          titleInput.placeholder = defaultTitle;
        }
        titleInput.focus();
      }
      clearStatus();
    }
    async function createPage(pageType, titleOverride) {
      const defaultTitle = pageType === 'corporate' ? 'Company Page' : 'Creator Page';
      const trimmedTitle = (titleOverride ?? titleInput?.value ?? '').trim() || defaultTitle;
      if (!trimmedTitle || trimmedTitle === defaultTitle && !titleOverride) {
        showStatus('error', 'Title required', 'Please enter a page title before creating your page.');
        return;
      }
      if (!pageType && pageTypeHidden) {
        pageType = pageTypeHidden.value || 'creator';
      }
      const body = new URLSearchParams({ csrf_token: csrf, page_type: pageType || 'creator', title: trimmedTitle, slug: trimmedTitle });
      const response = await fetch('api/pages.php', { method: 'POST', body });
      const data = await response.json().catch(() => ({ ok:false, error:'Invalid response' }));
      if (data.ok) {
        showStatus('success', 'Page created', 'Opening the editor now…');
        location.href = data.edit_url;
      } else {
        showStatus('error', 'Unable to create page', data.error || 'Please try again in a moment.');
      }
    }
    createButtons.forEach(btn => btn.addEventListener('click', openTypeModal));
    modal?.querySelector('.page-type-close')?.addEventListener('click', closeTypeModal);
    typeOptions.forEach(option => {
      option.addEventListener('click', () => {
        setSelectedPageType(option.dataset.pageType === 'corporate' ? 'corporate' : 'creator');
      });
    });
    titleSubmit?.addEventListener('click', () => {
      if (!selectedPageType && pageTypeHidden) {
        selectedPageType = pageTypeHidden.value || null;
      }
      if (!selectedPageType) {
        showStatus('error', 'Select a page type', 'Choose whether this is a creator page or company page first.');
        return;
      }
      createPage(selectedPageType);
    });
    titleInput?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        titleSubmit?.click();
      }
    });

    document.querySelectorAll('.copy-page').forEach(btn => btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        btn.classList.add('copied');
        setTimeout(() => btn.classList.remove('copied'), 800);
      } catch(e) {
        alert(btn.dataset.copy);
      }
    }));

    const note = document.getElementById('pages-model-note');
    note?.querySelector('.model-note-close')?.addEventListener('click', () => note.style.display = 'none');

    const screen = document.getElementById('pages-phone-screen');
    const title = document.getElementById('preview-page-title');
    const description = document.getElementById('preview-page-description');
    const url = document.getElementById('preview-page-url');
    const primaryAction = document.getElementById('preview-primary-action');
    const secondaryAction = document.getElementById('preview-secondary-action');
    const tertiaryAction = document.getElementById('preview-tertiary-action');
    document.querySelectorAll('.page-link-card').forEach(card => {
      card.addEventListener('mouseenter', () => {
        document.querySelectorAll('.page-link-card').forEach(item => item.classList.remove('selected'));
        card.classList.add('selected');
        title.textContent = card.dataset.title || 'Untitled page';
        description.textContent = card.dataset.description || 'Create a mobile-first landing page for your links.';
        url.textContent = (card.dataset.url || '').replace(/^https?:\/\//, '');
        const corporate = card.dataset.pageType === 'corporate';
        primaryAction.innerHTML = '<strong>' + (corporate ? 'Request Quote' : 'Follow me') + '</strong>';
        secondaryAction.textContent = corporate ? 'Download Company Profile' : 'Book me';
        tertiaryAction.textContent = corporate ? 'Book Technical Meeting' : 'Subscribe';
        screen.style.setProperty('--preview-header', card.dataset.header || '#26282C');
        screen.style.setProperty('--preview-block', card.dataset.block || '#0A9994');
      });
    });
  })();
  </script>
</body>
</html>
