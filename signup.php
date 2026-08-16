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
        if (!$pdo) {
            $errors[] = 'Unable to connect to the database.';
        } else {
            $slug_error = null;
            if ($slug_raw !== '') {
                $slug_check = xinng_validate_page_slug($pdo, $slug_raw);
                if (!$slug_check['ok']) {
                    $slug_error = $slug_check['error'];
                }
            }

            if ($slug_error) {
                $errors[] = $slug_error;
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    xinng_ensure_short_link_tables($pdo);
                    xinng_ensure_page_builder_tables($pdo);
                    xinng_ensure_credit_tables($pdo);
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, credit_balance, credits_purchased_total, credits_used_total, created_at, updated_at) VALUES (UUID(), ?, ?, ?, 1000, 0, 0, NOW(), NOW())');
                    $stmt->execute([$name, $email, $hash]);
                    $newId = $pdo->lastInsertId();

                    $stmt = $pdo->prepare('INSERT INTO credit_transactions (user_id, type, amount, reason, reference, created_at) VALUES (?, "signup_bonus", 1000, "Signup bonus", "signup", NOW())');
                    $stmt->execute([$newId]);

                    $make_slug = function ($s) {
                        $s = mb_strtolower($s, 'UTF-8');
                        $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
                        $s = trim($s, '-');
                        if ($s === '') {
                            return 'u' . bin2hex(random_bytes(3));
                        }
                        return $s;
                    };

                    $base_slug = $slug_raw !== '' ? (xinng_normalize_back_half($slug_raw) ?? $make_slug($slug_raw)) : $make_slug($name);

                    $slug = $base_slug;
                    $i = 0;
                    $check = $pdo->prepare('
                        SELECT
                            (SELECT COUNT(*) FROM pages WHERE slug = ? AND deleted_at IS NULL) +
                            (SELECT COUNT(*) FROM short_links WHERE back_half = ? AND deleted_at IS NULL) +
                            (SELECT COUNT(*) FROM qr_codes WHERE back_half = ? AND deleted_at IS NULL)
                    ');

                    while (true) {
                        $check->execute([$slug, $slug, $slug]);
                        $cnt = (int)$check->fetchColumn();
                        if ($cnt === 0) {
                            break;
                        }
                        $i++;
                        $slug = $base_slug . '-' . $i;
                    }

                    $title = $name ?: $slug;
                    $stmt = $pdo->prepare('INSERT INTO pages (user_id, page_type, slug, title, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                    $stmt->execute([$newId, 'creator', $slug, $title]);

                    $pdo->commit();

                    xinng_send_welcome_email($newId, $email, $name);
                    xinng_create_notification($pdo, $newId, 'account', 'Welcome to Xinng', 'Your account has been created and is ready to use.', 'dashboard.php');

                    $_SESSION['user_id'] = $newId;
                    $_SESSION['user_name'] = $name;

                    if (!empty($_SESSION['pending_qr'])) {
                        $savedId = xinng_persist_pending_qr($pdo, $_SESSION['pending_qr'], $newId);
                        if ($savedId > 0) {
                            $_SESSION['pending_qr']['saved_qr_id'] = $savedId;
                        }
                        header('Location: qr_codes.php');
                        exit;
                    }

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
    const reserved = ['login','signin','sign-in','register','signup','sign-up','dashboard','admin','api','settings','links','qr','qrcodes','insights','account','billing','support','help','logout','assets','data','uploads','includes','actions','controllers','page','pages','profile','profiles','u','l'];
    function normalize(s){
      const cleaned = (s || '').trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]+/g, '').replace(/^-+|-+$/g, '');
      return cleaned;
    }
    function setStatus(cls, txt){ status.className = 'slug-status '+cls; status.textContent = txt; }
    function checkSlugNow(value){
      const slug = normalize(value);
      if (!slug) {
        setStatus('available', '');
        submit.disabled = false;
        return;
      }
      if (slug.length < 3) {
        setStatus('unavailable', 'at least 3 chars');
        submit.disabled = true;
        return;
      }
      if (slug.length > 64) {
        setStatus('unavailable', 'too long');
        submit.disabled = true;
        return;
      }
      if (reserved.includes(slug)) {
        setStatus('unavailable', 'reserved');
        submit.disabled = true;
        return;
      }
      if (slug === last) return;
      last = slug;
      setStatus('checking','checking...');
      const form = new URLSearchParams();
      form.append('slug', slug);
      form.append('csrf_token', document.querySelector('input[name="csrf_token"]').value || '');
      fetch('check_slug_post.php', { method: 'POST', body: form }).then(r=>r.json()).then(data=>{
        if (!data.ok) {
          const err = data.error || 'error';
          if (err === 'csrf') {
            setStatus('unavailable', 'session expired');
          } else {
            setStatus('unavailable', err);
          }
          submit.disabled = true;
          return;
        }
        if (data.available) { setStatus('available', data.slug + ' available'); submit.disabled = false; }
        else { setStatus('unavailable', data.slug + ' taken'); submit.disabled = true; }
      }).catch(()=>{ setStatus('unavailable','error'); submit.disabled = true; });
    }
    input.addEventListener('input', ()=>{
      submit.disabled = false;
      clearTimeout(timer);
      timer = setTimeout(()=>checkSlugNow(input.value), 350);
    });
    // initial check if prefilled
    if (input.value.trim() !== '') { checkSlugNow(input.value); }
    else { setStatus('available', ''); }
  })();
  </script>
</body>
</html>
