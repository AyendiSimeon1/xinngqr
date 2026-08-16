<?php
require_once __DIR__.'/config.php';
session_start();
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
  $slug_raw = trim($_POST['slug'] ?? '');
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
        // create user and initial page inside a transaction
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, created_at, updated_at) VALUES (UUID(), ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $email, $hash]);
        $newId = $pdo->lastInsertId();

        // build slug: prefer provided, fallback to name
        $make_slug = function($s){
          $s = mb_strtolower($s, 'UTF-8');
          $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
          $s = trim($s, '-');
          if ($s === '') {
            return 'u'.bin2hex(random_bytes(3));
          }
          return $s;
        };
        $base_slug = $slug_raw !== '' ? $make_slug($slug_raw) : $make_slug($name);

        // ensure unique slug
        $slug = $base_slug;
        $i = 0;
        $check = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
        while (true) {
          $check->execute([$slug]);
          $cnt = (int)$check->fetchColumn();
          if ($cnt === 0) break;
          $i++;
          $slug = $base_slug . '-' . $i;
        }

        // create initial page
        $title = $name ?: $slug;
        $stmt = $pdo->prepare('INSERT INTO pages (user_id, slug, title, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$newId, $slug, $title]);

        $pdo->commit();
        // auto-login and redirect to dashboard
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_name'] = $name;
        header('Location: dashboard.php');
        exit;
            } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
          $pdo->rollBack();
        }
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
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label>Name</label>
        <input name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label>Email</label>
        <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Page slug</label>
        <div style="display:flex;align-items:center;gap:8px">
          <input id="slug-input" name="slug" type="text" placeholder="your-name or username" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
          <span id="slug-status" class="slug-status"></span>
        </div>

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
  // slug availability check
  (function(){
    const input = document.getElementById('slug-input');
    const status = document.getElementById('slug-status');
    const submit = document.querySelector('button[type="submit"]');
    let last = '';
    let timer = null;
    function normalize(s){
      return s.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }
    function setStatus(cls, txt){ status.className = 'slug-status '+cls; status.textContent = txt; }
    function checkSlugNow(value){
      const slug = normalize(value);
      if (!slug) { setStatus('unavailable','invalid'); submit.disabled = true; return; }
      if (slug === last) return;
      last = slug;
      setStatus('checking','checking...');
      const form = new URLSearchParams();
      form.append('slug', slug);
      form.append('csrf_token', document.querySelector('input[name="csrf_token"]').value || '');
      fetch('check_slug_post.php', { method: 'POST', body: form }).then(r=>r.json()).then(data=>{
        if (!data.ok) { setStatus('unavailable',data.error || 'error'); submit.disabled = true; return; }
        if (data.available) { setStatus('available',data.slug+' available'); submit.disabled = false; }
        else { setStatus('unavailable',data.slug+' taken'); submit.disabled = true; }
      }).catch(()=>{ setStatus('unavailable','error'); submit.disabled = true; });
    }
    input.addEventListener('input', ()=>{
      submit.disabled = true;
      setStatus('checking','checking...');
      clearTimeout(timer);
      timer = setTimeout(()=>checkSlugNow(input.value), 350);
    });
    // initial check if prefilled
    if (input.value.trim() !== '') { checkSlugNow(input.value); }
  })();
  </script>
</body>
</html>
