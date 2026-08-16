<?php
require __DIR__ . '/config.php';
$dataFile = __DIR__ . '/data/links.json';
$data = json_decode(file_get_contents($dataFile), true);
if (!$data) { $data = ['profile'=>['title'=>$APP_NAME,'subtitle'=>$BRAND_TAGLINE,'logo'=>''], 'links'=>[]]; }

function e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function current_public_url($configured){
  if (!empty($configured)) return rtrim($configured, '/');
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
  return $scheme . '://' . $host . $path;
}
$publicUrl = current_public_url($PUBLIC_URL);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=16&data=' . rawurlencode($publicUrl);
$profile = $data['profile'] ?? [];
$title = $profile['title'] ?? $APP_NAME;
$subtitle = $profile['subtitle'] ?? $BRAND_TAGLINE;
$logo = trim($profile['logo'] ?? '');
$initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 2));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($subtitle) ?>">
  <link rel="stylesheet" href="<?= e(xinng_public_base_url()) ?>/assets/style.css">
</head>
<body>
  <main class="page">
    <section class="card">
      <div class="profile">
        <div class="logo">
          <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($title) ?> logo"><?php else: ?><?= e($initials ?: 'QR') ?><?php endif; ?>
        </div>
        <h1><?= e($title) ?></h1>
        <p><?= e($subtitle) ?></p>
      </div>

      <div class="links">
        <?php foreach (($data['links'] ?? []) as $link): ?>
          <?php if (!empty($link['enabled']) && !empty($link['title']) && !empty($link['url'])): ?>
            <a class="link-btn" href="<?= e($link['url']) ?>" target="_blank" rel="noopener">
              <span><?= e($link['title']) ?></span><span class="arrow">→</span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <div class="qr-wrap">
        <img class="qr-img" src="<?= e($qrUrl) ?>" alt="QR code for <?= e($title) ?>">
        <p class="small"><?= e($QR_LABEL) ?></p>
        <p class="small"><a href="<?= e($qrUrl) ?>" download="qr-code.png">Download QR Code</a></p>
      </div>
    </section>
    <div class="footer">Powered by QR Link Manager</div>
  </main>
</body>
</html>
