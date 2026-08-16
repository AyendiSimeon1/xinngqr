<?php
require_once __DIR__.'/config.php';
session_start();
$errors = [];
$sent = false;
$display_link = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Provide a valid email address.';
        } else {
            $pdo = get_db_connection();
            if ($pdo) {
                try {
                    xinng_ensure_password_reset_table($pdo);
                    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    // Always show success to avoid account enumeration
                    $sent = true;
                    if ($user) {
                        $token = bin2hex(random_bytes(32));
                        $token_hash = hash('sha256', $token);
                        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                        $ins = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())');
                        $ins->execute([(int)$user['id'], $token_hash, $expires]);
                        $resetUrl = xinng_public_base_url() . '/reset_password.php?token=' . urlencode($token);
                        $mailOk = xinng_send_password_reset_email((int)$user['id'], $email, $resetUrl);
                        if (!$mailOk) {
                          $display_link = $resetUrl;
                        }
                    }
                } catch (Throwable $e) {
                    error_log($e->getMessage());
                    $errors[] = 'Server error. Try again later.';
                }
            } else {
                $errors[] = 'Unable to connect to the database.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset password — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-head">
        <div class="logo"><img src="assets/logo.svg" alt="logo"></div>
        <h1>Reset your password</h1>
        <p class="small">Enter the email for your account and we'll send a reset link.</p>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <?php if ($sent): ?>
        <div class="notice">If an account exists for that email, a reset link has been sent.</div>
        <?php if ($display_link): ?>
          <div class="small" style="margin-top:8px">Development reset link: <a href="<?= htmlspecialchars($display_link) ?>">Open reset link</a></div>
        <?php endif; ?>
      <?php else: ?>
        <form method="post" class="auth-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <label>Email</label>
          <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

          <div class="form-foot">
            <button class="btn brand-gradient-btn" type="submit">Send reset link</button>
          </div>
        </form>
      <?php endif; ?>

      <div style="text-align:center;margin-top:12px" class="small">Remembered? <a href="signin.php">Sign in</a></div>
    </section>
  </main>
</body>
</html>
