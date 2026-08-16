<?php
require_once __DIR__ . '/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
	header('Location: signin.php');
	exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = get_db_connection();
if (!$pdo || $id <= 0) {
	http_response_code(404);
	echo 'QR code not found';
	exit;
}

xinng_ensure_short_link_tables($pdo);
xinng_ensure_qr_code_tables($pdo);

function e($value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function load_qr(PDO $pdo, int $id, int $user_id): ?array {
	$stmt = $pdo->prepare('SELECT q.*, p.slug AS profile_slug FROM qr_codes q LEFT JOIN pages p ON p.id = q.profile_page_id WHERE q.id = ? AND q.user_id = ? AND q.deleted_at IS NULL LIMIT 1');
	$stmt->execute([$id, $user_id]);
	return $stmt->fetch() ?: null;
}

$qr = load_qr($pdo, $id, $user_id);
if (!$qr) {
	http_response_code(404);
	echo 'QR code not found';
	exit;
}

$errors = [];
$saved = false;
$isProfile = ($qr['type'] ?? '') === 'profile_page';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Security token expired. Refresh and try again.';
	} else {
		$title = trim($_POST['title'] ?? '');
		if ($title === '') $errors[] = 'Title is required.';
		$type = $isProfile ? 'profile_page' : (in_array($_POST['type'] ?? 'website', ['website','page','custom'], true) ? $_POST['type'] : 'website');
		$destination = $isProfile ? ['ok' => true, 'url' => $qr['destination_url']] : xinng_validate_destination_url($_POST['destination_url'] ?? '');
		if (!$destination['ok']) $errors[] = $destination['error'];
		$backHalf = $isProfile ? ['ok' => true, 'back_half' => null] : xinng_validate_qr_back_half($pdo, $_POST['back_half'] ?? '', $id, !empty($qr['short_link_id']) ? (int)$qr['short_link_id'] : null);
		if (!$backHalf['ok']) $errors[] = $backHalf['error'];

		$logoPath = $qr['logo_path'] ?? null;
		if (!empty($_FILES['logo_file']['name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
			$mime = mime_content_type($_FILES['logo_file']['tmp_name']);
			if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'], true)) {
				$errors[] = 'Logo must be PNG, JPG, WEBP, or SVG.';
			} else {
				$dir = __DIR__ . '/uploads/qr-logos';
				if (!is_dir($dir)) mkdir($dir, 0775, true);
				$ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION)) ?: 'png';
				$name = 'qr-' . $id . '-' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
				if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dir . '/' . $name)) {
					$logoPath = 'uploads/qr-logos/' . $name;
				} else {
					$errors[] = 'Unable to save logo upload.';
				}
			}
		}

		if (!$errors) {
			$shortLinkId = !empty($backHalf['back_half']) ? ($qr['short_link_id'] ?? null) : null;
			if (!$isProfile && !empty($backHalf['back_half'])) {
				if ($shortLinkId) {
					$stmt = $pdo->prepare('SELECT destination_url FROM short_links WHERE id = ? AND user_id = ? LIMIT 1');
					$stmt->execute([(int)$shortLinkId, $user_id]);
					$oldDestination = $stmt->fetchColumn();
					if ($oldDestination && rtrim($oldDestination, '/') === rtrim($destination['url'], '/')) {
						$stmt = $pdo->prepare('UPDATE short_links SET title = ?, back_half = ?, updated_at = NOW() WHERE id = ?');
						$stmt->execute([$title, $backHalf['back_half'], (int)$shortLinkId]);
					} else {
						$stmt = $pdo->prepare('SELECT back_half FROM short_links WHERE id = ? AND user_id = ? LIMIT 1');
						$stmt->execute([(int)$shortLinkId, $user_id]);
						$oldBackHalf = $stmt->fetchColumn();
						if ($oldBackHalf === $backHalf['back_half']) {
							$errors[] = 'Changing the destination creates a new short link. Choose a new back-half.';
						} else {
							$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
							$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
							$shortLinkId = (int)$pdo->lastInsertId();
						}
					}
				} else {
					$stmt = $pdo->prepare('INSERT INTO short_links (user_id, title, destination_url, back_half, status, created_at, updated_at) VALUES (?, ?, ?, ?, "active", NOW(), NOW())');
					$stmt->execute([$user_id, $title, $destination['url'], $backHalf['back_half']]);
					$shortLinkId = (int)$pdo->lastInsertId();
				}
			}

			if (!$errors) {
				$codeColor = xinng_validate_hex_color($_POST['code_color'] ?? '#000000', '#000000');
				$bgColor = xinng_validate_hex_color($_POST['background_color'] ?? '#FFFFFF', '#FFFFFF');
				$cornerColor = trim($_POST['corner_color'] ?? '') !== '' ? xinng_validate_hex_color($_POST['corner_color'], '#000000') : null;
				$removeLogo = !empty($_POST['remove_xinng_logo']) ? 1 : 0;
				$stmt = $pdo->prepare('UPDATE qr_codes SET short_link_id = ?, type = ?, title = ?, name = ?, destination_url = ?, back_half = ?, code_color = ?, background_color = ?, corner_color = ?, pattern_style = ?, corner_style = ?, frame_style = ?, frame_text = ?, logo_path = ?, remove_xinng_logo = ?, qr_image_url = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
				$stmt->execute([
					$shortLinkId,
					$type,
					$title,
					$title,
					$destination['url'],
					$backHalf['back_half'],
					$codeColor,
					$bgColor,
					$cornerColor,
					trim($_POST['pattern_style'] ?? 'default') ?: 'default',
					trim($_POST['corner_style'] ?? 'square') ?: 'square',
					trim($_POST['frame_style'] ?? '') ?: null,
					trim($_POST['frame_text'] ?? '') ?: null,
					$logoPath,
					$removeLogo,
					xinng_qr_image_url($id, $codeColor, $bgColor, $destination['url']),
					$id,
					$user_id
				]);
				$saved = true;
				$qr = load_qr($pdo, $id, $user_id);
			}
		}
	}
}

$shortLinkDomain = str_replace(['http://', 'https://'], '', xinng_public_base_url()) . '/';
$previewTitle = $qr['title'] ?: ($qr['name'] ?: 'QR Code');
$previewDestination = $qr['destination_url'] ?: xinng_qr_scan_url($id);
$previewQrDataUrl = xinng_qr_data_url_for_row($qr);
$codeColorValue = xinng_validate_hex_color($qr['code_color'] ?: '#000000', '#000000');
$backgroundColorValue = xinng_validate_hex_color($qr['background_color'] ?: '#FFFFFF', '#FFFFFF');
$cornerColorTextValue = trim((string)($qr['corner_color'] ?? ''));
$cornerColorPickerValue = $cornerColorTextValue !== '' ? xinng_validate_hex_color($cornerColorTextValue, '#000000') : '#000000';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit QR Code - <?= e($APP_NAME ?? 'xin.ng') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body class="builder-page">
  <header class="builder-topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <a class="ghost-btn" href="qr_codes.php"><i class="fa-solid fa-arrow-left"></i>&nbsp;Back to QR Codes</a>
      <div class="builder-url"><i class="fa-solid fa-qrcode"></i><strong id="builder-url-text"><?= e($previewTitle) ?></strong></div>
    </div>
    <div class="builder-actions">
      <a class="small-btn" href="qr_codes.php">Cancel</a>
      <button class="small-btn primary" form="qr-edit-form" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save changes</button>
    </div>
  </header>

  <main class="builder-shell">
    <section class="builder-workspace">
      <div class="builder-panel">
        <h1>Edit QR Code</h1>
        <?php if ($saved): ?><div class="notice success">Saved changes.</div><?php endif; ?>
        <?php foreach ($errors as $error): ?><div class="notice"><?= e($error) ?></div><?php endforeach; ?>

        <nav class="builder-tabs qr-builder-tabs" aria-label="QR editor sections">
          <button class="active" data-tab="details" type="button">Details</button>
          <button data-tab="design" type="button">Design</button>
          <button data-tab="track" type="button">Track</button>
        </nav>

        <form method="post" enctype="multipart/form-data" class="qr-edit-form" id="qr-edit-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= e($id) ?>">

          <section class="builder-tab qr-tab-panel active" id="tab-details">
            <section class="detail-section">
              <h2>Details</h2>
              <div class="field">
                <label>Short link</label>
                <?php if ($isProfile): ?>
                  <div class="link-url">
                    <span class="link-slug-text"><?= e(!empty($qr['profile_slug']) ? xinng_short_url($qr['profile_slug']) : xinng_qr_scan_url($id)) ?></span>
                    <span class="slug-share-icon"><i class="fa-solid fa-copy"></i></span>
                  </div>
                  <p class="muted-row">Profile QR links use the page slug.</p>
                <?php else: ?>
                  <div class="short-link-edit-row">
                    <span class="short-link-domain"><?= e($shortLinkDomain) ?></span>
                    <input name="back_half" type="text" value="<?= e($qr['back_half'] ?? '') ?>" placeholder="sheltercon" maxlength="64">
                  </div>
                  <p class="muted-row">Leave blank to encode the destination URL directly.</p>
                <?php endif; ?>
              </div>
              <div class="field"><label>Title</label><input name="title" type="text" required value="<?= e($qr['title'] ?: $qr['name']) ?>"></div>
            </section>

            <section class="detail-section">
              <h2>Content</h2>
              <div class="field">
                <label>Scan destination</label>
                <select name="type" <?= $isProfile ? 'disabled' : '' ?>>
                  <option value="website" <?= ($qr['type'] ?? '') === 'website' ? 'selected' : '' ?>>Website</option>
                  <option value="page" <?= ($qr['type'] ?? '') === 'page' ? 'selected' : '' ?>>QR Code / Page</option>
                  <option value="custom" <?= ($qr['type'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
              </div>
              <div class="field"><label>Destination URL</label><input name="destination_url" type="text" value="<?= e($qr['destination_url']) ?>" <?= $isProfile ? 'readonly' : '' ?>></div>
              <p class="muted-row"><i class="fa-solid fa-turn-down"></i> QR opens <span id="live-destination-inline"><?= e($previewDestination) ?></span></p>
            </section>
          </section>

          <section class="builder-tab qr-tab-panel" id="tab-design">
            <section class="detail-section">
              <h2>Design</h2>
              <div class="form-grid">
                <div class="field"><label>QR style / pattern</label><select name="pattern_style"><option value="default">Default</option><option value="dots" <?= ($qr['pattern_style'] ?? '') === 'dots' ? 'selected' : '' ?>>Dots</option><option value="rounded" <?= ($qr['pattern_style'] ?? '') === 'rounded' ? 'selected' : '' ?>>Rounded</option></select></div>
                <div class="field"><label>Corners</label><select name="corner_style"><option value="square">Square</option><option value="rounded" <?= ($qr['corner_style'] ?? '') === 'rounded' ? 'selected' : '' ?>>Rounded</option><option value="circle" <?= ($qr['corner_style'] ?? '') === 'circle' ? 'selected' : '' ?>>Circle</option></select></div>
              </div>
              <div class="form-grid">
                <div class="field">
                  <label>Code color</label>
                  <div class="color-input-row">
                    <input name="code_color" type="text" value="<?= e($codeColorValue) ?>">
                    <input class="color-picker" type="color" value="<?= e($codeColorValue) ?>" data-color-input="code_color" aria-label="Pick code color">
                  </div>
                </div>
                <div class="field">
                  <label>Background color</label>
                  <div class="color-input-row">
                    <input name="background_color" type="text" value="<?= e($backgroundColorValue) ?>">
                    <input class="color-picker" type="color" value="<?= e($backgroundColorValue) ?>" data-color-input="background_color" aria-label="Pick background color">
                  </div>
                </div>
              </div>
              <div class="form-grid">
                <div class="field">
                  <label>Corner color</label>
                  <div class="color-input-row">
                    <input name="corner_color" type="text" value="<?= e($cornerColorTextValue) ?>" placeholder="#000000">
                    <input class="color-picker" type="color" value="<?= e($cornerColorPickerValue) ?>" data-color-input="corner_color" aria-label="Pick corner color">
                  </div>
                </div>
                <div class="field"><label>Frame</label><select name="frame_style"><option value="">No frame</option><option value="simple" <?= ($qr['frame_style'] ?? '') === 'simple' ? 'selected' : '' ?>>Simple</option></select></div>
              </div>
              <div class="field"><label>Frame text</label><input name="frame_text" type="text" value="<?= e($qr['frame_text'] ?? '') ?>" placeholder="Scan me"></div>
            </section>

            <section class="detail-section">
              <h2>Branding</h2>
              <div class="field logo-upload-field">
                <label for="logo-file">Add logo to center</label>
                <div class="file-picker">
                  <input id="logo-file" name="logo_file" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                  <label class="file-picker-btn" for="logo-file"><i class="fa-regular fa-image"></i>Choose logo</label>
                  <span class="file-picker-name" id="logo-file-name">No logo chosen</span>
                </div>
              </div>
              <?php if (!empty($qr['logo_path'])): ?><p class="muted-row">Current logo: <?= e($qr['logo_path']) ?></p><?php endif; ?>
              <label class="check-row"><input name="remove_xinng_logo" type="checkbox" value="1" <?= !empty($qr['remove_xinng_logo']) ? 'checked' : '' ?>> Remove Xinng logo <span class="muted-inline">Plan enforcement TODO</span></label>
            </section>
          </section>

          <section class="builder-tab qr-tab-panel" id="tab-track">
            <div class="design-card">
              <h2>Analytics coming soon.</h2>
              <p class="muted-row">Scans, devices, locations, and campaign reports will appear here later.</p>
            </div>
          </section>

        </form>
      </div>
    </section>

    <aside class="builder-preview">
      <div class="builder-panel" style="width:320px;">
        <strong>Live Preview</strong>
        <div class="qr-preview-card" id="qr-preview-card">
          <div class="qr-preview-frame" id="qr-preview-frame">
            <div id="qr-preview-canvas" aria-label="<?= e($previewTitle) ?> QR preview"></div>
          </div>
          <div class="qr-preview-meta">
            <span id="live-frame-text"><?= e($qr['frame_text'] ?? '') ?></span>
            <h2 id="live-title"><?= e($previewTitle) ?></h2>
            <p id="live-destination"><?= e($previewDestination) ?></p>
          </div>
          <div class="qr-download-actions">
            <button class="small-btn" id="download-qr-png" type="button"><i class="fa-solid fa-download"></i>PNG</button>
            <button class="small-btn" id="download-qr-svg" type="button"><i class="fa-regular fa-file-code"></i>SVG</button>
          </div>
        </div>
      </div>
    </aside>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>
  <script>
  (function(){
    const form = document.getElementById('qr-edit-form');
    const canvas = document.getElementById('qr-preview-canvas');
    const previewFrame = document.getElementById('qr-preview-frame');
    const liveTitle = document.getElementById('live-title');
    const liveFrame = document.getElementById('live-frame-text');
    const liveDestination = document.getElementById('live-destination');
    const liveDestinationInline = document.getElementById('live-destination-inline');
    const pngButton = document.getElementById('download-qr-png');
    const svgButton = document.getElementById('download-qr-svg');
    const logoFileInput = form?.elements.logo_file || null;
    const logoFileName = document.getElementById('logo-file-name');

    document.querySelectorAll('.qr-builder-tabs button').forEach((button) => {
      button.addEventListener('click', () => {
        document.querySelectorAll('.qr-builder-tabs button').forEach((item) => item.classList.remove('active'));
        document.querySelectorAll('.qr-tab-panel').forEach((panel) => panel.classList.remove('active'));
        button.classList.add('active');
        document.getElementById('tab-' + button.dataset.tab)?.classList.add('active');
      });
    });

    if (!form || !canvas || typeof QRCodeStyling === 'undefined') return;

    const isProfile = <?= $isProfile ? 'true' : 'false' ?>;
    const savedQrDataUrl = <?= json_encode($previewQrDataUrl) ?>;
    const fallbackDestination = <?= json_encode($previewDestination) ?>;
    const savedLogoPath = <?= json_encode($qr['logo_path'] ?? '') ?>;
    const defaultXinngLogo = 'assets/logo-icon.svg';
    const qrCanvasSize = 260;
    const qrLogoSize = 65;
    let previewTimer = null;
    let selectedLogoObjectUrl = null;
    let selectedLogoFile = null;

    const qrCode = new QRCodeStyling({
      width: qrCanvasSize,
      height: qrCanvasSize,
      type: 'svg',
      data: savedQrDataUrl,
      qrOptions: {
        errorCorrectionLevel: 'H'
      },
      dotsOptions: {
        type: 'square',
        color: '#000000'
      },
      backgroundOptions: {
        color: '#FFFFFF'
      },
      cornersSquareOptions: {
        type: 'square',
        color: '#000000'
      },
      cornersDotOptions: {
        type: 'square',
        color: '#000000'
      },
      imageOptions: {
        crossOrigin: 'anonymous',
        margin: 8,
        imageSize: qrLogoSize / qrCanvasSize
      }
    });

    qrCode.append(canvas);

    function normalizeUrl(value) {
      value = String(value || '').trim();
      if (value === '') return fallbackDestination;
      if (!/^https?:\/\//i.test(value)) value = 'https://' + value;
      return value;
    }

    function qrDataUrl(destination) {
      if (isProfile) return savedQrDataUrl || fallbackDestination;
      return destination || savedQrDataUrl || fallbackDestination;
    }

    function normalizeHex(value, fallback) {
      value = String(value || '').trim();
      if (/^#[0-9a-fA-F]{6}$/.test(value)) return value.toUpperCase();
      if (/^[0-9a-fA-F]{6}$/.test(value)) return '#' + value.toUpperCase();
      return fallback.toUpperCase();
    }

    function syncColorPicker(picker) {
      const input = form.elements[picker.dataset.colorInput];
      if (!input) return;
      picker.addEventListener('input', () => {
        input.value = picker.value.toUpperCase();
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      input.addEventListener('input', () => {
        const normalized = normalizeHex(input.value, '');
        if (/^#[0-9A-F]{6}$/.test(normalized)) picker.value = normalized;
      });
    }

    function mapDotsType(value) {
      if (value === 'dots') return 'dots';
      if (value === 'rounded') return 'rounded';
      return 'square';
    }

    function mapCornerType(value) {
      if (value === 'rounded') return 'extra-rounded';
      if (value === 'circle') return 'dot';
      return 'square';
    }

    function getLogo() {
      const file = form.elements.logo_file?.files?.[0] || null;
      if (file) {
        if (file !== selectedLogoFile) {
          if (selectedLogoObjectUrl) URL.revokeObjectURL(selectedLogoObjectUrl);
          selectedLogoFile = file;
          selectedLogoObjectUrl = URL.createObjectURL(file);
        }
        return selectedLogoObjectUrl;
      }
      selectedLogoFile = null;
      if (savedLogoPath) return savedLogoPath;
      if (form.elements.remove_xinng_logo?.checked) return undefined;
      return defaultXinngLogo;
    }

    function safeFileName(value) {
      return String(value || 'xinng-qr-code')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'xinng-qr-code';
    }

    function updatePreview() {
      const title = form.elements.title?.value?.trim() || 'QR Code';
      const frameText = form.elements.frame_text?.value?.trim() || '';
      const rawDestination = isProfile ? fallbackDestination : form.elements.destination_url?.value;
      const destination = normalizeUrl(rawDestination);
      const dataUrl = qrDataUrl(destination);
      const codeColor = normalizeHex(form.elements.code_color?.value, '#000000');
      const bgColor = normalizeHex(form.elements.background_color?.value, '#FFFFFF');
      const cornerColor = normalizeHex(form.elements.corner_color?.value, codeColor);
      const frameStyle = form.elements.frame_style?.value || '';

      liveTitle.textContent = title;
      liveFrame.textContent = frameText;
      liveFrame.hidden = frameText === '';
      liveDestination.textContent = destination;
      liveDestinationInline.textContent = destination;
      canvas.setAttribute('aria-label', title + ' QR preview');
      previewFrame.classList.toggle('has-simple-frame', frameStyle === 'simple');

      qrCode.update({
        data: dataUrl,
        dotsOptions: {
          type: mapDotsType(form.elements.pattern_style?.value || 'default'),
          color: codeColor
        },
        backgroundOptions: {
          color: bgColor
        },
        cornersSquareOptions: {
          type: mapCornerType(form.elements.corner_style?.value || 'square'),
          color: cornerColor
        },
        cornersDotOptions: {
          type: mapCornerType(form.elements.corner_style?.value || 'square'),
          color: cornerColor
        },
        image: getLogo()
      });
    }

    form.addEventListener('input', () => {
      window.clearTimeout(previewTimer);
      previewTimer = window.setTimeout(updatePreview, 180);
    });
    form.addEventListener('change', updatePreview);
    document.querySelectorAll('.color-picker[data-color-input]').forEach(syncColorPicker);
    logoFileInput?.addEventListener('change', () => {
      if (logoFileName) {
        logoFileName.textContent = logoFileInput.files?.[0]?.name || 'No logo chosen';
      }
      updatePreview();
    });

    pngButton?.addEventListener('click', () => {
      qrCode.download({ name: safeFileName(form.elements.title?.value || 'xinng-qr-code'), extension: 'png' });
    });
    svgButton?.addEventListener('click', () => {
      qrCode.download({ name: safeFileName(form.elements.title?.value || 'xinng-qr-code'), extension: 'svg' });
    });

    updatePreview();
  })();
  </script>
</body>
</html>
