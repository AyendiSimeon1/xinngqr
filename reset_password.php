<?php
require_once __DIR__.'/config.php';
session_start();
$errors = [];
$success = false;
$show_form = false;
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if ($token === '') {
    $errors[] = 'Invalid or missing token.';
} else {
    $pdo = get_db_connection();
    if (!$pdo) {
        $errors[] = 'Unable to connect to the database.';
    } else {
        try {
            xinng_ensure_password_reset_table($pdo);
            $token_hash = hash('sha256', $token);
            $stmt = $pdo->prepare('SELECT pr.id AS pr_id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ? LIMIT 1');
            $stmt->execute([$token_hash]);
            $row = $stmt->fetch();
            if (!$row) {
                $errors[] = 'Invalid or expired token.';
            } else {
                $expires = strtotime($row['expires_at']);
                if ($expires < time()) {
                    $errors[] = 'This reset link has expired.';
                } else {
                    $show_form = true;
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                            $errors[] = 'Invalid request.';
                        } else {
                            $password = $_POST['password'] ?? '';
                            $password2 = $_POST['password2'] ?? '';
                            if ($password === '' || $password2 === '') {
                                $errors[] = 'Both password fields are required.';
                            } elseif ($password !== $password2) {
                                $errors[] = 'Passwords do not match.';
                            } elseif (strlen($password) < 6) {
                                $errors[] = 'Password must be at least 6 characters.';
                            } else {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                $pdo->beginTransaction();
                                $upd = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
                                $upd->execute([$hash, (int)$row['user_id']]);
                                $del = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                                $del->execute([(int)$row['user_id']]);
                                $pdo->commit();
                                $success = true;
                                $show_form = false;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Server error. Try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set new password — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-head">
        <div class="logo"><img src="assets/logo.svg" alt="logo"></div>
        <h1>Set a new password</h1>
        <p class="small">Choose a secure password for your account.</p>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <?php if ($success): ?>
        <div class="notice">Your password has been updated. You can now <a href="signin.php">sign in</a>.</div>
      <?php elseif ($show_form): ?>
        <form method="post" class="auth-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <label>New password</label>
          <div class="pw-wrap"><input id="new-password" name="password" type="password" required></div>

          <label>Confirm password</label>
          <div class="pw-wrap"><input id="new-password2" name="password2" type="password" required></div>

          <div class="form-foot">
            <button class="btn brand-gradient-btn" type="submit">Set password</button>
          </div>
        </form>
      <?php endif; ?>

      <div style="text-align:center;margin-top:12px" class="small">Remembered? <a href="signin.php">Sign in</a></div>
    </section>
  </main>

  <script>
  (function(){
    const eye = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOff = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.86 21.86 0 0 1 5.07-6.11"/><path d="M1 1l22 22"/></svg>';
    document.querySelectorAll('.pw-wrap').forEach(w=>{
      const input = w.querySelector('input');
      if(!input) return;
      const btn = document.createElement('button');
      btn.type = 'button'; btn.className = 'eye-toggle'; btn.setAttribute('aria-label','Toggle password visibility'); btn.innerHTML = eye;
      btn.addEventListener('click', ()=>{ if(input.type === 'password'){ input.type = 'text'; btn.innerHTML = eyeOff; } else { input.type = 'password'; btn.innerHTML = eye; } });
      w.appendChild(btn);
    });
  })();
  </script>
</body>
</html>
