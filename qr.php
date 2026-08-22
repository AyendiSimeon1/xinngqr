<?php
require_once __DIR__ . '/config.php';
session_start();

$qrData = $_SESSION['pending_qr'] ?? null;
if (!$qrData) {
    header('Location: index.php');
    exit;
}

$destination = $qrData['destination_url'] ?? 'https://example.com';
$type = $qrData['qr_type'] ?? 'Website';
$style = $qrData['style'] ?? 'Standard';
$pngUrl = $qrData['qr_png_url'] ?? '';
$svgUrl = $qrData['qr_svg_url'] ?? '';
$shareUrl = $qrData['share_url'] ?? xinng_public_base_url() . '/qr.php';
$saveLink = (!empty($_SESSION['user_id'])) ? 'save_qr.php' : 'signup.php';
$savedQrId = $qrData['saved_qr_id'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>QR Preview — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="landing-page">
  <div class="landing-bg-glow" aria-hidden="true"></div>

  <main class="landing-shell">
    <section class="landing-hero landing-hero--single">
      <div class="landing-hero__copy">
        <p class="landing-eyebrow">Your QR is ready</p>
        <h1>Preview and share your new QR code.</h1>
        <p class="landing-subtext">This preview is stored in your current session so you can download it right away, then save it permanently after signing up.</p>

        <div class="landing-pill-row">
          <span class="landing-pill">Destination: <?= htmlspecialchars($destination) ?></span>
          <span class="landing-pill">Type: <?= htmlspecialchars($type) ?></span>
          <span class="landing-pill">Style: <?= htmlspecialchars($style) ?></span>
        </div>

        <div class="landing-actions">
          <button class="landing-btn landing-btn--primary" id="download-qr-png" type="button">Download PNG</button>
          <button class="landing-btn landing-btn--secondary" id="download-qr-svg" type="button">Download SVG</button>
        </div>
      </div>

      <aside class="landing-widget">
        <div class="landing-widget__header">
          <div class="landing-widget__icon">QR</div>
          <div>
            <h2>QR preview</h2>
            <p>Share or save this result</p>
          </div>
        </div>
        <div class="landing-widget__divider"></div>
        <div class="landing-qr-preview landing-qr-preview--stacked">
          <img class="landing-qr-image" src="<?= htmlspecialchars($pngUrl) ?>" alt="Generated QR code preview">
        </div>
        <div class="landing-preview">
          <p class="landing-preview__eyebrow">Details</p>
          <p><strong>Destination:</strong> <?= htmlspecialchars($destination) ?></p>
          <p><strong>Type:</strong> <?= htmlspecialchars($type) ?></p>
          <p><strong>Settings:</strong> <?= htmlspecialchars($style) ?></p>
          <p><strong>Share link:</strong> <a href="<?= htmlspecialchars($shareUrl) ?>"><?= htmlspecialchars($shareUrl) ?></a></p>
        </div>
        <div class="landing-actions landing-actions--stacked">
          <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($saveLink) ?>"><?= !empty($savedQrId) ? 'Saved to your account' : 'Save permanently' ?></a>
          <a class="landing-btn landing-btn--primary" href="index.php">Create another</a>
        </div>
      </aside>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>
  <script>
  (function(){
    if (typeof QRCodeStyling === 'undefined') return;
    const data = <?= json_encode($destination) ?>;
    const style = <?= json_encode(strtolower($style)) ?>;
    const styles = {
      bold: { dots: 'rounded', color: '#0A9994', background: '#FFFFFF' },
      minimal: { dots: 'dots', color: '#26282C', background: '#FFFFFF' },
      standard: { dots: 'square', color: '#000000', background: '#FFFFFF' }
    };
    const selected = styles[style] || styles.standard;
    const qrCode = new QRCodeStyling({
      width: 600,
      height: 600,
      type: 'png',
      data,
      qrOptions: { errorCorrectionLevel: 'H' },
      dotsOptions: { type: selected.dots, color: selected.color },
      backgroundOptions: { color: selected.background },
      cornersSquareOptions: { type: selected.dots === 'dots' ? 'dot' : (selected.dots === 'rounded' ? 'extra-rounded' : 'square'), color: selected.color },
      cornersDotOptions: { type: selected.dots === 'dots' ? 'dot' : (selected.dots === 'rounded' ? 'extra-rounded' : 'square'), color: selected.color }
    });
    const download = (extension) => qrCode.download({ name: 'qr-code', extension });
    document.getElementById('download-qr-png')?.addEventListener('click', () => download('png'));
    document.getElementById('download-qr-svg')?.addEventListener('click', () => download('svg'));
  })();
  </script>
</body>
</html>
