<?php
header('Location: credits.php');
exit;

$user_id = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$creditBalance = 0;
$userEmail = '';
$transactions = [];
$packages = xinng_credit_packages();
$notice = null;
$error = null;

if (isset($_GET['success'])) {
    $notice = 'Payment completed successfully. Your credit balance has been updated.';
}
if (isset($_GET['error'])) {
    $messages = [
        'invalid_package' => 'The selected package is invalid.',
        'notfound' => 'Payment record not found.',
        'payment_failed' => 'Payment was not successful.',
        'verification_failed' => 'Unable to verify the payment with Paystack.',
        'auth' => 'Please sign in to complete payment.',
        'bad_request' => 'Failed to start the payment request.',
        'server' => 'A server error occurred while processing payment.',
    ];
    $error = $messages[$_GET['error']] ?? 'Could not complete the payment.';
}

if ($pdo) {
    xinng_ensure_credit_tables($pdo);
    $stmt = $pdo->prepare('SELECT credit_balance, email FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $creditBalance = (int)$row['credit_balance'];
        $userEmail = (string)$row['email'];
    }

    $stmt = $pdo->prepare('SELECT id, type, amount, reason, reference, payment_gateway, payment_amount, payment_currency, status, created_at FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credits — <?= e($APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(xinng_public_base_url()) ?>/assets/style.css">
</head>
<body class="pricing-page">
  <div class="pricing-shell">
    <header class="pricing-topbar">
      <div class="pricing-brand"><?= e($APP_NAME) ?></div>
      <nav class="pricing-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="pages.php">Pages</a>
        <a href="qr_codes.php">QR Codes</a>
        <a class="active" href="pricing.php">Credits</a>
        <a href="signin.php?action=logout">Logout</a>
      </nav>
    </header>

    <main class="pricing-main">
      <section class="pricing-hero">
        <div class="pricing-hero-copy">
          <p class="eyebrow">Credit Wallet</p>
          <h1>Buy credits for pages, QR codes, and campaigns.</h1>
          <p class="subtext">Every premium action consumes credits. Purchase securely with Paystack and manage your balance here.</p>
        </div>
        <div class="wallet-card">
          <div class="wallet-card-title">Current balance</div>
          <div class="wallet-card-value"><?= number_format($creditBalance) ?></div>
          <p class="wallet-card-note">Credits are used for short links, page creation, QR codes, and campaign actions.</p>
        </div>
      </section>

      <?php if ($notice): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

      <section class="pricing-grid">
        <?php foreach ($packages as $package): ?>
          <article class="package-card">
            <div class="package-card-header">
              <div>
                <span class="package-name"><?= e($package['name']) ?></span>
                <p class="package-description"><?= e($package['description']) ?></p>
              </div>
              <?php if ($package['id'] === 'growth'): ?>
                <span class="package-badge">Most popular</span>
              <?php endif; ?>
            </div>
            <div class="package-price">
              <strong><?= number_format($package['credits']) ?> credits</strong>
              <span>₦<?= number_format($package['price']) ?></span>
            </div>
            <button class="btn package-cta" type="button" data-package="<?= e($package['id']) ?>">Buy <?= number_format($package['credits']) ?></button>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="transactions-panel">
        <div class="transactions-header">
          <div>
            <h2>Recent credit activity</h2>
            <p>Track purchases, pending payments, and credit adjustments in one place.</p>
          </div>
          <a class="btn secondary" href="dashboard.php">Back to dashboard</a>
        </div>

        <?php if (empty($transactions)): ?>
          <div class="empty-state">No credit activity yet. Make a purchase to see your history.</div>
        <?php else: ?>
          <div class="transaction-table-wrap">
            <table class="transaction-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Credits</th>
                  <th>Paid</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($transactions as $txn): ?>
                  <tr>
                    <td><?= e(date('M j, Y H:i', strtotime($txn['created_at'] ?? 'now'))) ?></td>
                    <td><?= e(ucfirst(str_replace('_', ' ', $txn['type']))) ?></td>
                    <td><?= $txn['type'] === 'purchase' ? number_format((int) ($txn['amount'] ?? 0)) : '-' ?></td>
                    <td><?= $txn['payment_gateway'] ? e(strtoupper($txn['payment_gateway'])) . ' ' . e($txn['payment_currency'] ?? 'NGN') . ' ' . number_format((int)($txn['payment_amount'] ?? $txn['amount'] ?? 0)) : '-' ?></td>
                    <td><span class="status-pill <?= e($txn['status'] ?? 'completed') ?>"><?= e(ucfirst($txn['status'] ?? 'completed')) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script src="https://js.paystack.co/v1/inline.js"></script>
  <script>
    const paystackKey = '<?= e(PAYSTACK_PUBLIC_KEY) ?>';
    const userEmail = '<?= e($userEmail) ?>';

    function showError(message) {
      const existing = document.querySelector('.error');
      if (existing) {
        existing.textContent = message;
        return;
      }
      const container = document.createElement('div');
      container.className = 'error';
      container.textContent = message;
      document.querySelector('.pricing-main').prepend(container);
    }

    async function startPaystackPayment(packageId) {
      if (!paystackKey) {
        showError('Paystack public key is not configured. Set PAYSTACK_PUBLIC_KEY in config.php.');
        return;
      }
      if (!userEmail) {
        showError('Your account email is required for payment.');
        return;
      }
      try {
        const response = await fetch('paystack_init.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ package: packageId }),
        });
        const data = await response.json();
        if (!data.ok) {
          showError(data.error || 'Unable to start payment.');
          return;
        }
        if (typeof PaystackPop === 'undefined') {
          showError('Paystack checkout failed to load.');
          return;
        }
        const handler = PaystackPop.setup({
          key: paystackKey,
          email: userEmail,
          amount: data.amount,
          ref: data.reference,
          currency: 'NGN',
          callback: function(response) {
            window.location.href = 'paystack_verify.php?reference=' + encodeURIComponent(response.reference) + '&package=' + encodeURIComponent(packageId);
          },
          onClose: function() {
            showError('Payment was cancelled.');
          }
        });
        handler.openIframe();
      } catch (err) {
        showError('Payment request failed.');
      }
    }

    document.querySelectorAll('[data-package]').forEach((button) => {
      button.addEventListener('click', function() {
        startPaystackPayment(this.dataset.package);
      });
    });
  </script>
</body>
</html>
