<?php
session_start();
require __DIR__ . '/config.php';
$dataFile = __DIR__ . '/data/links.json';

function e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect_admin(){ header('Location: admin.php'); exit; }

if (isset($_GET['make_hash'])) {
  header('Content-Type: text/plain');
  echo password_hash($_GET['make_hash'], PASSWORD_DEFAULT);
  exit;
}

if (!file_exists($dataFile)) {
  file_put_contents($dataFile, json_encode(['profile'=>['title'=>$APP_NAME,'subtitle'=>$BRAND_TAGLINE,'logo'=>''], 'links'=>[]], JSON_PRETTY_PRINT));
}
$data = json_decode(file_get_contents($dataFile), true) ?: ['profile'=>[], 'links'=>[]];

$error = '';
$notice = '';

if (isset($_POST['login'])) {
  $password = $_POST['password'] ?? '';
  if (password_verify($password, $ADMIN_PASSWORD_HASH)) {
    $_SESSION['qr_admin'] = true;
    redirect_admin();
  } else {
    $error = 'Wrong password.';
  }
}

if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: admin.php'); exit;
}

$loggedIn = !empty($_SESSION['qr_admin']);

if ($loggedIn && isset($_POST['save'])) {
  $profile = [
    'title' => trim($_POST['profile_title'] ?? ''),
    'subtitle' => trim($_POST['profile_subtitle'] ?? ''),
    'logo' => trim($_POST['profile_logo'] ?? '')
  ];

  $links = [];
  $titles = $_POST['link_title'] ?? [];
  $urls = $_POST['link_url'] ?? [];
  $enabled = $_POST['link_enabled'] ?? [];

  for ($i = 0; $i < count($titles); $i++) {
    $title = trim($titles[$i] ?? '');
    $url = trim($urls[$i] ?? '');
    if ($title === '' && $url === '') continue;
    $links[] = [
      'title' => $title,
      'url' => $url,
      'enabled' => isset($enabled[$i])
    ];
  }

  $newData = ['profile' => $profile, 'links' => $links];
  file_put_contents($dataFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  $data = $newData;
  $notice = 'Saved successfully.';
}

if ($loggedIn && isset($_POST['add_sample'])) {
  $data['links'][] = ['title'=>'New Link', 'url'=>'https://example.com', 'enabled'=>true];
  file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  redirect_admin();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | <?= e($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="admin-shell">
    <div class="topbar">
      <div>
        <h1>QR Link Manager</h1>
        <p>Update your live QR destination links without changing the QR code.</p>
      </div>
      <?php if ($loggedIn): ?>
      <div class="actions">
        <a class="btn secondary" href="index.php" target="_blank">View Page</a>
        <a class="btn danger" href="admin.php?logout=1">Logout</a>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>

    <?php if (!$loggedIn): ?>
      <form class="form-card" method="post">
        <label>Admin Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <div class="actions"><button class="btn" type="submit" name="login">Login</button></div>
        <p class="small">Default password: changeMe123! Change it in config.php before going live.</p>
      </form>
    <?php else: ?>
      <form method="post">
        <section class="form-card">
          <h2>Profile</h2>
          <div class="row">
            <div>
              <label>Page Title</label>
              <input name="profile_title" value="<?= e($data['profile']['title'] ?? '') ?>" required>
            </div>
            <div>
              <label>Logo URL / Path</label>
              <input name="profile_logo" value="<?= e($data['profile']['logo'] ?? '') ?>" placeholder="assets/logo.png or https://...">
            </div>
          </div>
          <div style="margin-top:12px">
            <label>Subtitle</label>
            <textarea name="profile_subtitle" rows="3"><?= e($data['profile']['subtitle'] ?? '') ?></textarea>
          </div>
        </section>

        <section class="form-card" style="margin-top:16px">
          <h2>Links</h2>
          <?php
            $links = $data['links'] ?? [];
            for ($i=0; $i < max(count($links), 1); $i++):
              $link = $links[$i] ?? ['title'=>'','url'=>'','enabled'=>true];
          ?>
            <div class="link-edit">
              <div class="row">
                <div>
                  <label>Button Title</label>
                  <input name="link_title[]" value="<?= e($link['title'] ?? '') ?>" placeholder="Book a Technical Session">
                </div>
                <div>
                  <label>URL</label>
                  <input name="link_url[]" value="<?= e($link['url'] ?? '') ?>" placeholder="https://... / mailto:... / tel:...">
                </div>
              </div>
              <label class="check"><input type="checkbox" name="link_enabled[<?= $i ?>]" <?= !empty($link['enabled']) ? 'checked' : '' ?>> Enabled</label>
            </div>
          <?php endfor; ?>
          <div class="actions">
            <button class="btn" name="save" type="submit">Save Changes</button>
            <button class="btn secondary" name="add_sample" type="submit">Add Link Slot</button>
          </div>
        </section>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
