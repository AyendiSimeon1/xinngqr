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
                header('Location: index.php');
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
        <input name="password" type="password" required>

        <div class="form-foot">
          <label style="font-weight:700"><input type="checkbox" name="remember"> Remember</label>
          <button class="btn brand-gradient-btn" type="submit">Sign in</button>
        </div>
      </form>

      <div style="text-align:center;margin-top:12px" class="small">Don't have an account? <a href="signup.php">Create one</a></div>
    </section>
  </main>
</body>
</html>
