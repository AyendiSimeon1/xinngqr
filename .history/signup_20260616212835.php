<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = get_db_connection();
        if ($pdo === null) {
            $errors[] = 'Database connection failed.';
        } else {
            // Check existing email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email is already registered.';
            } else {
                $uuid = generate_uuid_v4();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                $ok = $stmt->execute([$uuid, $name, $email, $password_hash]);
                if ($ok) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to create account.';
                }
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
  <title>Sign up — <?= htmlspecialchars($APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/style.css">
  <style> .auth-card{max-width:520px;margin:36px auto;padding:28px;border-radius:20px} .brand{display:flex;align-items:center;gap:12px} .brand .logo{width:64px;height:64px;border-radius:16px} </style>
</head>
<body>
  <div class="page">
    <div class="card auth-card">
      <div class="profile">
        <div class="brand">
          <div class="logo">X</div>
          <div style="text-align:left">
            <h1>Get started</h1>
            <p>Create your account to manage your QR links.</p>
          </div>
        </div>
      </div>

      <?php if ($success): ?>
        <div class="notice">Account created. You can <a href="signin.php">sign in</a> now.</div>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="error"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <label for="name">Full name</label>
          <input id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

          <label for="email">Email</label>
          <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

          <label for="password">Password</label>
          <input id="password" name="password" type="password" required>

          <label for="password_confirm">Confirm password</label>
          <input id="password_confirm" name="password_confirm" type="password" required>

          <div class="actions">
            <button class="btn" type="submit">Create account</button>
            <a class="btn secondary" href="signin.php">Sign in</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($APP_NAME) ?></div>
  </div>
</body>
</html>
