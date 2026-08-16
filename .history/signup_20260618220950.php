<?php
require_once __DIR__.'/config.php';
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Provide a valid email address.';
    } else {
        $pdo = get_db_connection();
        if ($pdo) {
            // Minimal user creation for demo only
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, created_at, updated_at) VALUES (UUID(), ?, ?, ?, NOW(), NOW())');
                $stmt->execute([$name, $email, $hash]);
                $success = true;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $errors[] = 'An account with that email already exists.';
                } else {
                    $errors[] = 'Database error. Check logs.';
                    error_log($e->getMessage());
                }
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
  <title>Sign up — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-head">
        <div class="logo"><img src="assets/logo.svg" alt="logo"></div>
        <h1>Create your account</h1>
        <p class="small">Quickly create an account to manage your links.</p>
      </div>

      <?php if ($success): ?>
        <div class="notice">Account created. You can now <a href="signin.php">sign in</a>.</div>
      <?php endif; ?>

      <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <form method="post" class="auth-form" novalidate>
        <label>Name</label>
        <input name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label>Email</label>
        <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Password</label>
        <div class="pw-wrap">
          <input id="signup-password" name="password" type="password" required>
          <button type="button" class="eye-toggle" data-target="signup-password" aria-label="Toggle password visibility">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>

        <div class="form-foot">
          <small class="small">By creating an account you agree to our terms.</small>
          <button class="btn brand-gradient-btn" type="submit">Create account</button>
        </div>
      </form>

      <div style="text-align:center;margin-top:12px" class="small">Already have an account? <a href="signin.php">Sign in</a></div>
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
