<?php
require_once __DIR__.'/config.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $errors[] = 'Both email and password are required.';
    } else {
        $pdo = get_db_connection();
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT id, password_hash, name FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
              session_start();
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['user_name'] = $user['name'];
              header('Location: dashboard.php');
              exit;
            } else {
                $errors[] = 'Invalid credentials.';
            }
        } else {
            $errors[] = 'Unable to connect to the database.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign in — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-head">
        <div class="logo"><img src="assets/logo.svg" alt="logo"></div>
        <h1>Sign in</h1>
        <p class="small">Welcome back — sign in to manage your links.</p>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <form method="post" class="auth-form" novalidate>
        <label>Email</label>
        <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Password</label>
        <div class="pw-wrap">
          <input id="signin-password" name="password" type="password" required>
          <button type="button" class="eye-toggle" data-target="signin-password" aria-label="Toggle password visibility">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>

        <div class="form-foot">
          <label style="font-weight:700"><input type="checkbox" name="remember"> Remember</label>
          <button class="btn brand-gradient-btn" type="submit">Sign in</button>
        </div>
      </form>

      <div style="text-align:center;margin-top:12px" class="small">Don't have an account? <a href="signup.php">Create one</a></div>
    </section>
  </main>

  <script>
  (function(){
    const eye = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOff = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.86 21.86 0 0 1 5.07-6.11"/><path d="M1 1l22 22"/></svg>';
    document.querySelectorAll('.eye-toggle').forEach(btn=>{
      const target = document.getElementById(btn.dataset.target);
      if(!target) return;
      btn.innerHTML = eye;
      btn.addEventListener('click', ()=>{
        if(target.type === 'password'){ target.type = 'text'; btn.innerHTML = eyeOff; } else { target.type = 'password'; btn.innerHTML = eye; }
      });
    });
  })();
  </script>
</body>
</html>
