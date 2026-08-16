<?php
require_once __DIR__ . '/config.php';
session_start();
if (empty($_SESSION['user_id'])) { header('Location: signin.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$id = (int)($_GET['id'] ?? 0);
$pdo = get_db_connection();
if (!$pdo || $id <= 0) { http_response_code(404); echo 'Page not found'; exit; }
xinng_ensure_page_builder_tables($pdo);
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function initials3(string $name): string { return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'X', 0, 2)); }
$stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
$stmt->execute([$id, $user_id]);
$page = $stmt->fetch();
if (!$page) { http_response_code(404); echo 'Page not found'; exit; }
$stmt = $pdo->prepare('SELECT * FROM page_blocks WHERE page_id = ? AND deleted_at IS NULL ORDER BY position ASC, id ASC');
$stmt->execute([$id]);
$blocks = array_map(static function($b) { $b['metadata'] = !empty($b['metadata']) ? json_decode($b['metadata'], true) : []; $b['is_active'] = (bool)$b['is_active']; return $b; }, $stmt->fetchAll());
$stmt = $pdo->prepare('SELECT * FROM page_socials WHERE page_id = ? AND deleted_at IS NULL ORDER BY position ASC, id ASC');
$stmt->execute([$id]);
$socials = array_map(static function($s) { $s['is_active'] = (bool)$s['is_active']; return $s; }, $stmt->fetchAll());
$corporateMetadata = xinng_load_corporate_page_data($pdo, (int)$page['id'], $page);
$state = [
	'id' => (int)$page['id'],
	'page_type' => in_array($page['page_type'] ?? 'creator', ['creator', 'corporate'], true) ? $page['page_type'] : 'creator',
	'corporate' => $corporateMetadata,
	'slug' => $page['slug'],
	'title' => $page['title'] ?? '',
	'description' => $page['description'] ?? ($page['bio'] ?? ''),
	'profile_image' => $page['profile_image_path'] ?? ($page['profile_image_url'] ?? ''),
	'header' => ['mode' => $page['header_mode'] ?? 'color', 'color' => $page['header_color'] ?? '#26282C', 'gradient_start' => $page['header_gradient_start'] ?? '#26282C', 'gradient_end' => $page['header_gradient_end'] ?? '#0A9994', 'image' => $page['header_image_path'] ?? '', 'fit' => $page['header_fit'] ?? 'cover'],
	'background' => ['mode' => $page['background_mode'] ?? 'color', 'color' => $page['background_color'] ?? '#FFFAF6', 'gradient_start' => $page['background_gradient_start'] ?? '#FFFAF6', 'gradient_end' => $page['background_gradient_end'] ?? '#FFFFFF', 'image' => $page['background_image_path'] ?? ''],
	'theme' => $page['theme'] ?? 'default',
	'layout' => $page['layout'] ?? 'simple',
	'font' => $page['font'] ?? 'system',
	'text_color' => $page['title_color'] ?? '#26282C',
	'description_color' => $page['description_color'] ?? '#26282C',
	'socials' => $socials,
	'social_style' => $page['social_icon_style'] ?? 'original',
	'social_placement' => $page['social_placement'] ?? 'top',
	'block_style' => ['shape' => $page['block_shape'] ?? 'rounded', 'shadow' => $page['block_shadow'] ?? 'soft', 'block_color' => $page['block_color'] ?? '#0A9994', 'block_text_color' => $page['block_text_color'] ?? '#FFFAF6'],
	'blocks' => $blocks,
	'branding' => ['hide_xinng_logo' => !empty($page['hide_xinng_logo'])],
];
$publicUrl = xinng_short_url($page['slug']);
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page Builder - <?= e($page['title'] ?: $page['slug']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body class="builder-page">
  <header class="builder-topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="ghost-btn" id="back-to-pages" type="button"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back to pages</button>
      <div class="builder-url"><i class="fa-solid fa-link"></i><strong id="builder-url-text"><?= e(str_replace(['http://','https://'], '', $publicUrl)) ?></strong><button id="copy-page-url" type="button"><i class="fa-regular fa-copy"></i></button></div>
    </div>
    <div class="builder-actions">
      <button class="small-btn" type="button"><i class="fa-solid fa-ellipsis"></i></button>
      <a class="small-btn" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
      <button class="small-btn" type="button"><i class="fa-solid fa-share-nodes"></i>Share</button>
      <button class="small-btn primary" id="publish-page" type="button" disabled>Publish changes</button>
    </div>
  </header>
  <main class="builder-shell">
    <section class="builder-workspace">
      <nav class="builder-tabs">
        <button class="active" data-tab="build" type="button">Build</button>
        <button data-tab="design" type="button">Design</button>
        <button data-tab="track" type="button">Track</button>
        <span class="feedback-link"><i class="fa-regular fa-message"></i> Leave Feedback</span>
      </nav>
      <div class="builder-panel">
        <section class="builder-tab active" id="tab-build">
          <div class="builder-mode-note" id="builder-mode-note"></div>
          <button class="primary-btn builder-add-btn" id="add-block" type="button"><span class="label-icon"><i class="fa-solid fa-plus"></i></span>Add</button>
          <div class="block-type-menu" id="block-type-menu" hidden>
            <div class="block-type-group" id="suggested-block-types"></div>
            <button class="block-more-toggle" id="show-more-blocks" type="button">More blocks</button>
            <div class="block-type-group more" id="more-block-types" hidden></div>
          </div>
          <div id="block-list"></div>
        </section>
        <section class="builder-tab" id="tab-design">
          <div class="design-card">
            <h2>Page Settings</h2>
            <label>Page Type
              <select id="page-type-select">
                <option value="creator">Creator Page</option>
                <option value="corporate">Company Page</option>
              </select>
            </label>
            <p class="muted-row" id="page-type-help"></p>
          </div>
          <div class="design-card corporate-fields" id="corporate-fields" hidden>
            <h2>Company Page Content</h2>
            <p class="muted-row">These fields drive the public corporate page and the live preview.</p>
            <div id="corporate-editor"></div>
          </div>
          <div class="design-card">
            <h2 id="profile-section-title">Profile</h2>
            <label>Image</label>
            <div class="image-picker"><div class="image-placeholder" id="profile-image-preview"><i class="fa-regular fa-image"></i></div><label class="small-btn">Add image<input id="profile-image-input" type="file" accept="image/*" hidden></label><button class="small-btn" id="remove-profile-image" type="button">Remove</button></div>
            <label>Title <span id="title-count">0/32</span></label><input id="page-title" maxlength="32">
            <label>Description <span id="desc-count">0/80</span></label><input id="page-description" maxlength="80">
          </div>
          <div class="design-card">
            <h2>Page</h2><label>Slug</label><input id="page-slug" maxlength="64">
            <h3>Themes</h3><div class="theme-row" id="theme-row"></div>
            <h3>Layout</h3><div class="layout-row" id="layout-row"></div>
          </div>
          <div class="design-card">
            <h2>Header</h2>
            <div class="segmented" data-bind="header.mode"><button data-value="color">Color</button><button data-value="gradient">Gradient</button><button data-value="image">Image</button></div>
            <div class="form-grid"><label>Color<input id="header-color" type="text"></label><label>Gradient start<input id="header-gradient-start" type="text"></label><label>Gradient end<input id="header-gradient-end" type="text"></label><label>Fit<select id="header-fit"><option value="cover">Stretch</option><option value="contain">Fit</option><option value="repeat">Repeat</option></select></label></div>
            <label class="small-btn">Header image<input id="header-image-input" type="file" accept="image/*" hidden></label>
          </div>
          <div class="design-card">
            <h2>Background</h2>
            <div class="segmented" data-bind="background.mode"><button data-value="color">Color</button><button data-value="gradient">Gradient</button><button data-value="image">Image</button></div>
            <div class="form-grid"><label>Color<input id="background-color" type="text"></label><label>Gradient start<input id="background-gradient-start" type="text"></label><label>Gradient end<input id="background-gradient-end" type="text"></label><label class="small-btn">Background image<input id="background-image-input" type="file" accept="image/*" hidden></label></div>
          </div>
          <div class="design-card">
            <h2>Text color</h2>
            <div class="form-grid"><label>Title<input id="title-color" type="text"></label><label>Description<input id="description-color" type="text"></label><label>Font<select id="font-select"><option value="system">Default system</option><option value="Montserrat">Montserrat</option><option value="Inter">Inter</option><option value="Arial">Arial</option><option value="Poppins">Poppins</option></select></label></div>
          </div>
          <div class="design-card">
            <h2>Socials</h2><div class="social-picker" id="social-picker"></div>
            <h3>Style</h3><div class="radio-row" id="social-style-row"></div>
            <h3>Placement</h3><div class="radio-row" id="social-placement-row"></div>
          </div>
          <div class="design-card">
            <h2>Blocks</h2><h3>Shape</h3><div class="block-style-grid" id="block-shape-row"></div><h3>Shadow</h3><div class="block-style-grid" id="block-shadow-row"></div>
            <div class="form-grid"><label>Block color<input id="block-color" type="text"></label><label>Block text<input id="block-text-color" type="text"></label></div>
          </div>
          <div class="design-card">
            <h2>Branding</h2><label class="check-row"><input id="hide-branding" type="checkbox"> Hide the Xinng logo</label>
          </div>
        </section>
        <section class="builder-tab" id="tab-track">
          <div class="design-card">
            <h2>Page analytics</h2>
            <p class="muted-row">Monitor the page’s reach, engagement, and actions from the same builder view.</p>
            <div id="analytics-panel" class="analytics-panel"></div>
          </div>
        </section>
      </div>
    </section>
    <aside class="builder-preview">
      <span class="live-badge">Live preview</span>
      <div class="phone bitly-phone"><div class="phone-screen" id="page-preview"></div></div>
      <p class="preview-note">Scheduled and Disabled links are not shown</p>
    </aside>
  </main>
  <script>
  window.initialPageState = <?= json_encode($state, JSON_UNESCAPED_SLASHES) ?>;
  window.pageBuilderConfig = { csrf: <?= json_encode($csrf) ?>, publicBase: <?= json_encode(rtrim(xinng_public_base_url(), '/')) ?> };
    (function(){
      // Track simple dirty state and prompt before navigating away
      window.pageBuilderDirty = false;
      const shell = document.querySelector('.builder-shell');
      const setDirty = ()=> { window.pageBuilderDirty = true; };
      if (shell) {
        shell.addEventListener('input', setDirty, true);
        shell.addEventListener('change', setDirty, true);
        shell.addEventListener('click', (e)=>{
          if (e.target.closest('.builder-add-btn') || e.target.closest('.builder-block-actions') || e.target.closest('.small-btn') || e.target.closest('.segmented') || e.target.closest('.theme-dot') || e.target.closest('.layout-card')) setDirty();
        }, true);
      }

      const backBtn = document.getElementById('back-to-pages');
      backBtn?.addEventListener('click', (ev)=>{
        ev.preventDefault();
        const dest = 'pages.php';
        if (!window.pageBuilderDirty) return window.location.href = dest;
        const shouldSave = confirm('You have unsaved changes. Press OK to save changes before leaving, or Cancel to leave without saving.');
        if (shouldSave) {
          const publish = document.getElementById('publish-page');
          if (publish) {
            publish.click();
            setTimeout(()=> { window.location.href = dest; }, 800);
          } else {
            window.location.href = dest;
          }
        } else {
          window.location.href = dest;
        }
      });

      window.addEventListener('beforeunload', function(e){ if (window.pageBuilderDirty) { e.preventDefault(); e.returnValue = ''; } });
    })();
  </script>
  <script src="assets/page-builder.js"></script>
</body>
</html>
