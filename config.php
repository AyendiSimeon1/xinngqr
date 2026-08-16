<?php
// Load simple .env file if present (KEY=VALUE lines). This is a tiny loader
// to avoid requiring external libraries like vlucas/phpdotenv for local setups.
function xinng_load_dotenv(string $path): void {
	if (!file_exists($path) || !is_readable($path)) return;
	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#')) continue;
		if (!str_contains($line, '=')) continue;
		[$name, $value] = array_map('trim', explode('=', $line, 2));
		// Remove surrounding quotes if present
		if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
			$value = substr($value, 1, -1);
		}
		// Unescape common sequences
		$value = str_replace(['\\n','\\r','\\t'], ["\n","\r","\t"], $value);
		putenv("{$name}={$value}");
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}
}

// Auto-load .env in project root
xinng_load_dotenv(__DIR__ . '/.env');
// QR Link Manager Configuration
// Change these before uploading publicly.

$APP_NAME = "xin_ng";
$BRAND_TAGLINE = "Show your self!";

// Environment
$APP_ENV = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

// Production-safe error and logging configuration
if ($APP_ENV === 'production') {
	ini_set('display_errors', '0');
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
	ini_set('log_errors', '1');
	if (!is_dir(__DIR__ . '/logs')) {
		@mkdir(__DIR__ . '/logs', 0755, true);
	}
	ini_set('error_log', __DIR__ . '/logs/app.log');
} else {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

// Default admin password: changeMe123!
// Generate a new hash at: /admin.php?make_hash=YOUR_NEW_PASSWORD
$ADMIN_PASSWORD_HASH = '$2y$10$7uL.r0Qr52/V9OaMT03yXuVtCr4f1sW53orhjxiiHQxZw.7UqWpf.';

// Optional: set your final public URL here after upload, e.g. https://xin.ng
// Leave blank to auto-detect current URL.
// In production (including cPanel), prefer setting APP_URL or PUBLIC_URL in .env
// so links are generated with your live domain instead of localhost.
$PUBLIC_URL = getenv('APP_URL') ?: getenv('PUBLIC_URL') ?: getenv('SITE_URL') ?: '';

// -------------------------
// SMTP / Mailer configuration (optional)
// By default prefer Gmail SMTP for local/dev testing; credentials are read from
// environment variables to avoid committing secrets. You can override by
// defining these constants before including `config.php`.
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('GMAIL_USER') ?: '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('GMAIL_PASS') ?: '');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // 'tls', 'ssl', or ''
if (!defined('SMTP_FROM')) define('SMTP_FROM', getenv('GMAIL_FROM') ?: '');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', $APP_NAME);

// Try to load Composer autoload if available (for PHPMailer)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Secure session cookie defaults (applies before session_start calls elsewhere)
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
// Set cookie_samesite if supported
if (PHP_VERSION_ID >= 70300) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'domain' => '',
		'secure' => $secureFlag,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
} else {
	session_set_cookie_params(0, '/; samesite=Lax', '', $secureFlag, true);
}

// Mailgun configuration (optional)
if (!defined('MAILGUN_DOMAIN')) define('MAILGUN_DOMAIN', 'sandbox86f58c84abe349d2a0d0bd4a4c6c533a.mailgun.org');
if (!defined('MAILGUN_API_KEY')) define('MAILGUN_API_KEY', '49e1a098fbd2daf96e301032d0f69ef2-11c539c0-d62eb2e4');

function send_mail_smtp(string $to, string $subject, string $body, bool $isHtml = false): bool {
	// Use PHPMailer if available
	if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
		try {
			$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
			$mail->isSMTP();
			if (SMTP_HOST !== '') $mail->Host = SMTP_HOST;
			$mail->SMTPAuth = (SMTP_USER !== '');
			if (SMTP_USER !== '') $mail->Username = SMTP_USER;
			if (SMTP_PASS !== '') $mail->Password = SMTP_PASS;
			if (!empty(SMTP_SECURE)) $mail->SMTPSecure = SMTP_SECURE;
			$mail->Port = SMTP_PORT;
			$mail->setFrom(SMTP_FROM, SMTP_FROM_NAME ?: $GLOBALS['APP_NAME']);
			$mail->addAddress($to);
			$mail->isHTML($isHtml);
			$mail->Subject = $subject;
			$mail->Body = $body;
			if (!$isHtml) $mail->AltBody = $body;
			$mail->send();
			return true;
		} catch (Throwable $e) {
			error_log('PHPMailer error: ' . $e->getMessage());
			return false;
		}
	}

	// Fallback to simple mail()
	$fromName = SMTP_FROM_NAME ?: $GLOBALS['APP_NAME'];
	$headers = 'From: ' . $fromName . ' <' . SMTP_FROM . '>' . "\r\n";
	if ($isHtml) {
		$headers .= 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=UTF-8' . "\r\n";
	}
	$ok = @mail($to, $subject, $body, $headers);
	if (!$ok) error_log('mail() failed sending to ' . $to);
	return (bool)$ok;
}

function send_mail_mailgun_api(string $to, string $subject, string $body, bool $isHtml = false): bool {
	if (empty(MAILGUN_DOMAIN) || empty(MAILGUN_API_KEY)) return false;
	$url = 'https://api.mailgun.net/v3/' . MAILGUN_DOMAIN . '/messages';
	$from = SMTP_FROM ?: ('no-reply@' . MAILGUN_DOMAIN);
	$fields = [
		'from' => $from,
		'to' => $to,
		'subject' => $subject,
	];
	if ($isHtml) {
		$fields['html'] = $body;
		$fields['text'] = strip_tags($body);
	} else {
		$fields['text'] = $body;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_USERPWD, 'api:' . MAILGUN_API_KEY);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$resp = curl_exec($ch);
	$err = curl_error($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($resp === false || $code >= 400) {
		error_log('Mailgun send failed: ' . ($err ?: $resp));
		return false;
	}
	return true;
}

// Unified send_mail helper: prefer Mailgun API, then PHPMailer SMTP, then mail()
function send_mail(string $to, string $subject, string $body, bool $isHtml = false): bool {
	// Prefer SMTP (e.g. Gmail) when SMTP credentials are provided; otherwise
	// fall back to Mailgun API, then local mail(). This ensures explicit
	// SMTP configuration (GMAIL_USER/GMAIL_PASS or SMTP_USER/SMTP_PASS)
	// is honoured even if MAILGUN_* variables exist.
	$smtpUser = getenv('SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : '');
	$smtpPass = getenv('SMTP_PASS') ?: (defined('SMTP_PASS') ? SMTP_PASS : '');
	if (!empty($smtpUser) || !empty($smtpPass)) {
		$ok = send_mail_smtp($to, $subject, $body, $isHtml);
		if ($ok) return true;
	}

	if (!empty(MAILGUN_DOMAIN) && !empty(MAILGUN_API_KEY)) {
		$ok = send_mail_mailgun_api($to, $subject, $body, $isHtml);
		if ($ok) return true;
	}

	// final fallback to PHP mail()
	return send_mail_smtp($to, $subject, $body, $isHtml);
}

// QR code brand label shown under QR image.
$QR_LABEL = "xinng";

// -------------------------
// Database configuration
// Update `DB_USER` / `DB_PASS` for your environment (XAMPP default: root / empty)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'xinng');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

if (!defined('PAYSTACK_PUBLIC_KEY')) define('PAYSTACK_PUBLIC_KEY', 'pk_test_2e418f1bd0fab9b2c034f08729bc45d5490dffff');
if (!defined('PAYSTACK_SECRET_KEY')) define('PAYSTACK_SECRET_KEY', 'sk_test_d302885900cb8234664c32e14531f5e0a11c363e');

if (!function_exists('e')) {
	function e($value): string {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

function get_db_connection(): ?PDO {
	static $pdo = null;
	if ($pdo instanceof PDO) return $pdo;
	$dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
	$opts = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_PERSISTENT => false,
	];
	try {
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
		return $pdo;
	} catch (PDOException $e) {
		error_log('DB connection failed: '.$e->getMessage());
		return null;
	}
}
// -------------------------
// Slug helper
function slugify(string $s): ?string {
	$s = mb_strtolower($s, 'UTF-8');
	$s = preg_replace('/[^a-z0-9]+/u', '-', $s);
	$s = trim($s, '-');
	if ($s === '') return null;
	return $s;
}

// -------------------------
// CSRF helpers
function csrf_token(): string {
	if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	if (empty($_SESSION['_csrf_token'])) {
		$_SESSION['_csrf_token'] = bin2hex(random_bytes(24));
	}
	return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool {
	if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	if (empty($token) || empty($_SESSION['_csrf_token'])) return false;
	return hash_equals($_SESSION['_csrf_token'], $token);
}

function xinng_reserved_back_halves(): array {
	return [
		'login', 'signin', 'sign-in', 'register', 'signup', 'sign-up',
		'dashboard', 'admin', 'api', 'settings', 'links', 'qr', 'qrcodes',
		'insights', 'account', 'billing', 'support', 'help', 'logout',
		'assets', 'data', 'uploads', 'includes', 'actions', 'controllers',
		'page', 'pages', 'profile', 'profiles', 'u', 'l'
	];
}

function xinng_normalize_back_half(string $value): ?string {
	$value = trim(mb_strtolower($value, 'UTF-8'));
	$value = preg_replace('/\s+/', '-', $value);
	$value = preg_replace('/[^a-z0-9_-]+/', '', $value);
	$value = trim($value, '-_');
	if ($value === '') return null;
	return $value;
}

function xinng_public_base_url(): string {
	global $PUBLIC_URL;
	if (!empty($PUBLIC_URL)) {
		return (stripos($PUBLIC_URL, 'http') === 0) ? rtrim($PUBLIC_URL, '/') : 'http://' . rtrim($PUBLIC_URL, '/');
	}
	$scheme = 'http';
	if (
		(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
	) {
		$scheme = 'https';
	}
	$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
	$basePath = '';
	$documentRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
	$appRoot = realpath(__DIR__);
	if ($documentRoot && $appRoot && str_starts_with(strtolower($appRoot), strtolower($documentRoot))) {
		$relative = trim(str_replace('\\', '/', substr($appRoot, strlen($documentRoot))), '/');
		$basePath = $relative !== '' ? '/' . $relative : '';
	}
	return rtrim($scheme . '://' . $host . $basePath, '/');
}

function xinng_short_url(string $back_half): string {
	return xinng_public_base_url() . '/' . ltrim($back_half, '/');
}

function xinng_credit_packages(): array {
	return [
		['id' => 'starter', 'name' => 'Starter Pack', 'credits' => 1000, 'price' => 2500, 'description' => 'Good for trying the platform and creating a few pages.'],
		['id' => 'growth', 'name' => 'Growth Pack', 'credits' => 5000, 'price' => 10000, 'description' => 'Best for creators managing several campaigns.'],
		['id' => 'pro', 'name' => 'Pro Pack', 'credits' => 10000, 'price' => 18000, 'description' => 'Ideal for power users and agencies.'],
	];
}

function xinng_credit_package(string $id): ?array {
	foreach (xinng_credit_packages() as $package) {
		if ($package['id'] === $id) {
			return $package;
		}
	}
	return null;
}

function xinng_ensure_credit_tables(PDO $pdo): void {
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS users (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			uuid CHAR(36) NULL,
			name VARCHAR(120) NOT NULL,
			email VARCHAR(255) NOT NULL,
			password_hash VARCHAR(255) NOT NULL,
			credit_balance INT NOT NULL DEFAULT 1000,
			credits_purchased_total INT NOT NULL DEFAULT 0,
			credits_used_total INT NOT NULL DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			UNIQUE KEY unique_users_email (email),
			INDEX idx_users_credit_balance (credit_balance)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS credit_transactions (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			type ENUM('signup_bonus','purchase','deduction','refund','adjustment') NOT NULL,
			amount INT NOT NULL,
			reason VARCHAR(120) NOT NULL,
			reference VARCHAR(120) NULL,
			payment_gateway VARCHAR(80) NULL,
			payment_amount INT NULL,
			payment_currency VARCHAR(10) NULL,
			status ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			CONSTRAINT fk_credit_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
			INDEX idx_credit_transactions_user_id (user_id),
			INDEX idx_credit_transactions_created_at (created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	try {
		$pdo->exec("ALTER TABLE users ADD COLUMN credit_balance INT NOT NULL DEFAULT 1000 AFTER password_hash");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE users ADD COLUMN credits_purchased_total INT NOT NULL DEFAULT 0 AFTER credit_balance");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE users ADD COLUMN credits_used_total INT NOT NULL DEFAULT 0 AFTER credits_purchased_total");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL AFTER updated_at");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE credit_transactions ADD COLUMN payment_gateway VARCHAR(80) NULL AFTER reference");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE credit_transactions ADD COLUMN payment_amount INT NULL AFTER payment_gateway");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE credit_transactions ADD COLUMN payment_currency VARCHAR(10) NULL AFTER payment_amount");
	} catch (PDOException $e) {}
	try {
		$pdo->exec("ALTER TABLE credit_transactions ADD COLUMN status ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed' AFTER payment_currency");
	} catch (PDOException $e) {}
}

function xinng_ensure_credit_balance(PDO $pdo, int $user_id): int {
	$stmt = $pdo->prepare('SELECT credit_balance FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([$user_id]);
	return (int)$stmt->fetchColumn();
}

function xinng_apply_credit_transaction(PDO $pdo, int $user_id, string $type, int $amount, string $reason, ?string $reference = null): bool {
	if ($user_id <= 0 || $amount === 0) {
		return false;
	}

	$pdo->beginTransaction();
	try {
		$stmt = $pdo->prepare('SELECT credit_balance, credits_purchased_total, credits_used_total FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
		$stmt->execute([$user_id]);
		$user = $stmt->fetch();
		if (!$user) {
			$pdo->rollBack();
			return false;
		}

		$newBalance = (int)$user['credit_balance'] + $amount;
		if ($newBalance < 0) {
			$pdo->rollBack();
			return false;
		}

		$updatedPurchased = (int)$user['credits_purchased_total'];
		$updatedUsed = (int)$user['credits_used_total'];
		if ($type === 'purchase') {
			$updatedPurchased += abs($amount);
		} elseif ($type === 'deduction' || $type === 'refund') {
			$updatedUsed += abs($amount);
		}

		$stmt = $pdo->prepare('UPDATE users SET credit_balance = ?, credits_purchased_total = ?, credits_used_total = ?, updated_at = NOW() WHERE id = ?');
		$stmt->execute([$newBalance, $updatedPurchased, $updatedUsed, $user_id]);
		$stmt = $pdo->prepare('INSERT INTO credit_transactions (user_id, type, amount, reason, reference, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
		$stmt->execute([$user_id, $type, $amount, $reason, $reference]);
		$pdo->commit();
		return true;
	} catch (Throwable $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		throw $e;
	}
}

function xinng_charge_credits(PDO $pdo, int $user_id, int $amount, string $reason, ?string $reference = null): bool {
	if ($amount <= 0) {
		return false;
	}
	return xinng_apply_credit_transaction($pdo, $user_id, 'deduction', -abs($amount), $reason, $reference);
}

function xinng_ensure_short_link_tables(PDO $pdo): void {
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS short_links (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(150) NOT NULL,
			destination_url TEXT NOT NULL,
			back_half VARCHAR(64) NOT NULL,
			status ENUM('active','archived','disabled') DEFAULT 'active',
			click_count BIGINT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			CONSTRAINT fk_short_links_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
			UNIQUE KEY unique_short_links_back_half (back_half),
			INDEX idx_short_links_user_id (user_id),
			INDEX idx_short_links_status (status),
			INDEX idx_short_links_deleted_at (deleted_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS short_link_clicks (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			short_link_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			ip_hash VARCHAR(128) NULL,
			user_agent TEXT NULL,
			referer TEXT NULL,
			country VARCHAR(100) NULL,
			device_type VARCHAR(40) NULL,
			clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			CONSTRAINT fk_short_link_clicks_short_link FOREIGN KEY (short_link_id) REFERENCES short_links(id) ON DELETE CASCADE,
			INDEX idx_short_link_clicks_short_link_id (short_link_id),
			INDEX idx_short_link_clicks_clicked_at (clicked_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
}

function xinng_validate_back_half(PDO $pdo, string $raw, ?int $current_short_link_id = null): array {
	$back_half = xinng_normalize_back_half($raw);
	if ($back_half === null) return ['ok' => false, 'error' => 'Back-half is required.'];
	$length = strlen($back_half);
	if ($length < 3) return ['ok' => false, 'error' => 'Back-half must be at least 3 characters.'];
	if ($length > 64) return ['ok' => false, 'error' => 'Back-half must be 64 characters or fewer.'];
	if (in_array($back_half, xinng_reserved_back_halves(), true)) {
		return ['ok' => false, 'error' => 'This back-half is reserved.'];
	}

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
	$stmt->execute([$back_half]);
	if ((int)$stmt->fetchColumn() > 0) {
		return ['ok' => false, 'error' => 'This back-half conflicts with a profile slug.'];
	}

	if ($current_short_link_id) {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM short_links WHERE back_half = ? AND id != ?');
		$stmt->execute([$back_half, $current_short_link_id]);
	} else {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM short_links WHERE back_half = ?');
		$stmt->execute([$back_half]);
	}
	if ((int)$stmt->fetchColumn() > 0) {
		return ['ok' => false, 'error' => 'This back-half is already taken.'];
	}

	return ['ok' => true, 'back_half' => $back_half];
}

function xinng_validate_destination_url(string $url): array {
	$url = trim($url);
	if ($url === '') return ['ok' => false, 'error' => 'Destination URL is required.'];
	if (!preg_match('/^https?:\/\//i', $url)) {
		$url = 'https://' . $url;
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return ['ok' => false, 'error' => 'Enter a valid destination URL.'];
	}
	return ['ok' => true, 'url' => $url];
}

function xinng_validate_hex_color(?string $value, string $fallback): string {
	$value = trim((string)$value);
	if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
		return strtoupper($value);
	}
	return strtoupper($fallback);
}

function xinng_table_has_column(PDO $pdo, string $table, string $column): bool {
	$stmt = $pdo->prepare('
		SELECT COUNT(*)
		FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA = DATABASE()
		  AND TABLE_NAME = ?
		  AND COLUMN_NAME = ?
	');
	$stmt->execute([$table, $column]);
	return (int)$stmt->fetchColumn() > 0;
}

function xinng_ensure_qr_code_tables(PDO $pdo): void {
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS qr_codes (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NULL,
			page_id BIGINT UNSIGNED NULL,
			short_link_id BIGINT UNSIGNED NULL,
			profile_page_id BIGINT UNSIGNED NULL,
			type ENUM('profile_page','website','page','custom') DEFAULT 'website',
			title VARCHAR(150) NOT NULL DEFAULT 'QR Code',
			name VARCHAR(120) DEFAULT 'QR Code',
			destination_url TEXT NULL,
			back_half VARCHAR(64) NULL,
			qr_image_url TEXT NULL,
			qr_image_path TEXT NULL,
			status ENUM('active','archived','disabled') DEFAULT 'active',
			code_color VARCHAR(20) DEFAULT '#000000',
			background_color VARCHAR(20) DEFAULT '#FFFFFF',
			corner_color VARCHAR(20) NULL,
			pattern_style VARCHAR(80) DEFAULT 'default',
			corner_style VARCHAR(80) DEFAULT 'square',
			frame_style VARCHAR(80) NULL,
			frame_text VARCHAR(120) NULL,
			logo_path TEXT NULL,
			remove_xinng_logo BOOLEAN DEFAULT FALSE,
			scan_count BIGINT UNSIGNED DEFAULT 0,
			foreground_color VARCHAR(20) DEFAULT '#000000',
			is_active BOOLEAN DEFAULT TRUE,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			INDEX idx_qr_codes_user_id (user_id),
			INDEX idx_qr_codes_short_link_id (short_link_id),
			INDEX idx_qr_codes_profile_page_id (profile_page_id),
			INDEX idx_qr_codes_status (status),
			INDEX idx_qr_codes_type (type),
			UNIQUE KEY unique_qr_codes_back_half (back_half)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$columns = [
		'user_id' => "ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id",
		'short_link_id' => "ADD COLUMN short_link_id BIGINT UNSIGNED NULL AFTER page_id",
		'profile_page_id' => "ADD COLUMN profile_page_id BIGINT UNSIGNED NULL AFTER short_link_id",
		'type' => "ADD COLUMN type ENUM('profile_page','website','page','custom') DEFAULT 'website' AFTER profile_page_id",
		'title' => "ADD COLUMN title VARCHAR(150) NULL AFTER type",
		'back_half' => "ADD COLUMN back_half VARCHAR(64) NULL AFTER destination_url",
		'qr_image_path' => "ADD COLUMN qr_image_path TEXT NULL AFTER qr_image_url",
		'status' => "ADD COLUMN status ENUM('active','archived','disabled') DEFAULT 'active' AFTER qr_image_path",
		'code_color' => "ADD COLUMN code_color VARCHAR(20) DEFAULT '#000000' AFTER status",
		'background_color' => "ADD COLUMN background_color VARCHAR(20) DEFAULT '#FFFFFF' AFTER code_color",
		'corner_color' => "ADD COLUMN corner_color VARCHAR(20) NULL AFTER background_color",
		'pattern_style' => "ADD COLUMN pattern_style VARCHAR(80) DEFAULT 'default' AFTER corner_color",
		'corner_style' => "ADD COLUMN corner_style VARCHAR(80) DEFAULT 'square' AFTER pattern_style",
		'frame_style' => "ADD COLUMN frame_style VARCHAR(80) NULL AFTER corner_style",
		'frame_text' => "ADD COLUMN frame_text VARCHAR(120) NULL AFTER frame_style",
		'logo_path' => "ADD COLUMN logo_path TEXT NULL AFTER frame_text",
		'remove_xinng_logo' => "ADD COLUMN remove_xinng_logo BOOLEAN DEFAULT FALSE AFTER logo_path",
		'deleted_at' => "ADD COLUMN deleted_at DATETIME NULL AFTER updated_at",
	];
	foreach ($columns as $column => $ddl) {
		if (!xinng_table_has_column($pdo, 'qr_codes', $column)) {
			$pdo->exec("ALTER TABLE qr_codes $ddl");
		}
	}
	try { $pdo->exec('ALTER TABLE qr_codes MODIFY page_id BIGINT UNSIGNED NULL'); } catch (PDOException $e) {}
	try { $pdo->exec('ALTER TABLE qr_codes ADD UNIQUE KEY unique_qr_codes_back_half (back_half)'); } catch (PDOException $e) {}

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS qr_code_scans (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			qr_code_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			ip_hash VARCHAR(128) NULL,
			user_agent TEXT NULL,
			referer TEXT NULL,
			country VARCHAR(100) NULL,
			device_type VARCHAR(40) NULL,
			scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			CONSTRAINT fk_qr_code_scans_qr_code FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
			INDEX idx_qr_code_scans_qr_code_id (qr_code_id),
			INDEX idx_qr_code_scans_scanned_at (scanned_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
}

function xinng_qr_scan_url(int $qr_code_id): string {
	return xinng_public_base_url() . '/q/' . $qr_code_id;
}

function xinng_qr_image_url(int $qr_code_id, string $code_color = '#000000', string $background_color = '#FFFFFF', ?string $data_url = null): string {
	$data = trim((string)$data_url);
	if ($data === '') $data = xinng_qr_scan_url($qr_code_id);
	$fg = ltrim(xinng_validate_hex_color($code_color, '#000000'), '#');
	$bg = ltrim(xinng_validate_hex_color($background_color, '#FFFFFF'), '#');
	return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=16&color=' . rawurlencode($fg) . '&bgcolor=' . rawurlencode($bg) . '&data=' . rawurlencode($data);
}

function xinng_ensure_page_qr_code(PDO $pdo, int $user_id, int $page_id, string $title, string $destination): int {
	xinng_ensure_qr_code_tables($pdo);
	$destination = trim($destination);
	if ($destination === '' || $page_id <= 0) {
		return 0;
	}

	$safeTitle = trim($title) !== '' ? $title : 'Page QR';
	$stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE user_id = ? AND type = "profile_page" AND profile_page_id = ? AND deleted_at IS NULL LIMIT 1');
	$stmt->execute([$user_id, $page_id]);
	$existing = $stmt->fetch();

	if ($existing) {
		$stmt = $pdo->prepare('UPDATE qr_codes SET title = ?, name = ?, destination_url = ?, page_id = ?, updated_at = NOW() WHERE id = ?');
		$stmt->execute([$safeTitle, $safeTitle, $destination, $page_id, (int)$existing['id']]);
		$id = (int)$existing['id'];
	} else {
		$stmt = $pdo->prepare('INSERT INTO qr_codes (user_id, page_id, profile_page_id, type, title, name, destination_url, status, code_color, background_color, pattern_style, corner_style, created_at, updated_at) VALUES (?, ?, ?, "profile_page", ?, ?, ?, "active", "#000000", "#FFFFFF", "default", "square", NOW(), NOW())');
		$stmt->execute([$user_id, $page_id, $page_id, $safeTitle, $safeTitle, $destination]);
		$id = (int)$pdo->lastInsertId();
	}

	$stmt = $pdo->prepare('UPDATE qr_codes SET qr_image_url = ? WHERE id = ?');
	$stmt->execute([xinng_qr_image_url($id, '#000000', '#FFFFFF', $destination), $id]);
	return $id;
}

function xinng_qr_data_url_for_row(array $qr): string {
	$destination = trim((string)($qr['destination_url'] ?? ''));
	if ($destination !== '') return $destination;
	return xinng_qr_scan_url((int)($qr['id'] ?? 0));
}

function xinng_qr_image_url_for_row(array $qr): string {
	$id = (int)($qr['id'] ?? 0);
	$codeColor = $qr['code_color'] ?? ($qr['foreground_color'] ?? '#000000');
	$bgColor = $qr['background_color'] ?? '#FFFFFF';
	return xinng_qr_image_url($id, $codeColor, $bgColor, xinng_qr_data_url_for_row($qr));
}

function xinng_client_ip(): string {
	$ip = trim((string)($_SERVER['HTTP_CLIENT_IP'] ?? ''));
	if ($ip === '') {
		$forwarded = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
		if ($forwarded !== '') {
			$parts = explode(',', $forwarded);
			$ip = trim($parts[0]);
		}
	}
	if ($ip === '') {
		$ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
	}
	return $ip;
}

function xinng_visitor_cookie_name(): string {
	return 'xinng_visitor';
}

function xinng_current_visitor_id(): string {
	$cookieName = xinng_visitor_cookie_name();
	$visitorId = trim((string)($_COOKIE[$cookieName] ?? ''));
	if ($visitorId !== '') {
		return $visitorId;
	}
	$visitorId = bin2hex(random_bytes(16));
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
	setcookie($cookieName, $visitorId, [
		'expires' => time() + 31536000,
		'path' => '/',
		'secure' => $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	]);
	$_COOKIE[$cookieName] = $visitorId;
	return $visitorId;
}

function xinng_parse_user_agent(string $userAgent): array {
	$ua = strtolower(trim($userAgent));
	$deviceType = 'unknown';
	if (preg_match('/tablet|ipad|playbook|silk/', $ua)) {
		$deviceType = 'tablet';
	} elseif (preg_match('/mobile|iphone|android|blackberry|iemobile|kindle|opera mini/', $ua)) {
		$deviceType = 'mobile';
	} elseif ($ua !== '') {
		$deviceType = 'desktop';
	}
	$browser = 'unknown';
	if (strpos($ua, 'edg/') !== false) {
		$browser = 'Edge';
	} elseif (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) {
		$browser = 'Opera';
	} elseif (strpos($ua, 'chrome') !== false) {
		$browser = 'Chrome';
	} elseif (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
		$browser = 'Safari';
	} elseif (strpos($ua, 'firefox') !== false) {
		$browser = 'Firefox';
	} elseif (strpos($ua, 'msie') !== false || strpos($ua, 'trident') !== false) {
		$browser = 'Internet Explorer';
	}
	$os = 'unknown';
	if (strpos($ua, 'windows') !== false) {
		$os = 'Windows';
	} elseif (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac os x') !== false) {
		$os = 'macOS';
	} elseif (strpos($ua, 'android') !== false) {
		$os = 'Android';
	} elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'ipod') !== false) {
		$os = 'iOS';
	} elseif (strpos($ua, 'linux') !== false) {
		$os = 'Linux';
	}
	return ['device_type' => $deviceType, 'browser' => $browser, 'os' => $os];
}

function xinng_ensure_tracking_tables(PDO $pdo): void {
	$pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		page_id BIGINT UNSIGNED NOT NULL,
		visitor_id VARCHAR(120) NULL,
		ip_address VARCHAR(64) NULL,
		user_agent TEXT NULL,
		referrer TEXT NULL,
		country VARCHAR(100) NULL,
		city VARCHAR(100) NULL,
		device_type ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
		browser VARCHAR(100) NULL,
		os VARCHAR(100) NULL,
		viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		CONSTRAINT fk_page_views_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
		INDEX idx_page_views_page_id (page_id),
		INDEX idx_page_views_visitor_id (visitor_id),
		INDEX idx_page_views_device_type (device_type),
		INDEX idx_page_views_viewed_at (viewed_at),
		INDEX idx_page_views_country (country)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	$pdo->exec("CREATE TABLE IF NOT EXISTS link_clicks (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		link_id BIGINT UNSIGNED NULL,
		block_id BIGINT UNSIGNED NULL,
		page_id BIGINT UNSIGNED NOT NULL,
		visitor_id VARCHAR(120) NULL,
		ip_address VARCHAR(64) NULL,
		user_agent TEXT NULL,
		referrer TEXT NULL,
		country VARCHAR(100) NULL,
		city VARCHAR(100) NULL,
		device_type ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
		clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		CONSTRAINT fk_link_clicks_link FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
		CONSTRAINT fk_link_clicks_block FOREIGN KEY (block_id) REFERENCES page_blocks(id) ON DELETE CASCADE,
		CONSTRAINT fk_link_clicks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
		INDEX idx_link_clicks_link_id (link_id),
		INDEX idx_link_clicks_block_id (block_id),
		INDEX idx_link_clicks_page_id (page_id),
		INDEX idx_link_clicks_visitor_id (visitor_id),
		INDEX idx_link_clicks_device_type (device_type),
		INDEX idx_link_clicks_clicked_at (clicked_at),
		INDEX idx_link_clicks_country (country)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	$pdo->exec("CREATE TABLE IF NOT EXISTS qr_scans (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		qr_code_id BIGINT UNSIGNED NOT NULL,
		page_id BIGINT UNSIGNED NOT NULL,
		visitor_id VARCHAR(120) NULL,
		ip_address VARCHAR(64) NULL,
		user_agent TEXT NULL,
		country VARCHAR(100) NULL,
		city VARCHAR(100) NULL,
		scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		CONSTRAINT fk_qr_scans_qr_code FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
		CONSTRAINT fk_qr_scans_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
		INDEX idx_qr_scans_qr_code_id (qr_code_id),
		INDEX idx_qr_scans_page_id (page_id),
		INDEX idx_qr_scans_visitor_id (visitor_id),
		INDEX idx_qr_scans_scanned_at (scanned_at),
		INDEX idx_qr_scans_country (country)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	try { $pdo->exec('ALTER TABLE link_clicks MODIFY link_id BIGINT UNSIGNED NULL'); } catch (PDOException $e) {}
	if (!xinng_table_has_column($pdo, 'link_clicks', 'block_id')) {
		try { $pdo->exec('ALTER TABLE link_clicks ADD COLUMN block_id BIGINT UNSIGNED NULL AFTER link_id'); } catch (PDOException $e) {}
	}
	try { $pdo->exec('ALTER TABLE link_clicks ADD CONSTRAINT fk_link_clicks_block FOREIGN KEY (block_id) REFERENCES page_blocks(id) ON DELETE CASCADE'); } catch (PDOException $e) {}
}

function xinng_ensure_password_reset_table(PDO $pdo): void {
	try {
		$legacyExists = $pdo->query("SHOW TABLES LIKE 'password_reset'")->fetchColumn();
		if ($legacyExists && !$pdo->query("SHOW TABLES LIKE 'password_resets'")->fetchColumn()) {
			$pdo->exec('RENAME TABLE password_reset TO password_resets');
		}
	} catch (PDOException $e) {}

	$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		user_id BIGINT UNSIGNED NOT NULL,
		token_hash CHAR(64) NOT NULL,
		expires_at DATETIME NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
		INDEX idx_password_resets_token_hash (token_hash),
		INDEX idx_password_resets_expires_at (expires_at)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function xinng_ensure_communication_tables(PDO $pdo): void {
	$pdo->exec("CREATE TABLE IF NOT EXISTS emails_log (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		user_id BIGINT UNSIGNED NULL,
		email VARCHAR(255) NOT NULL,
		event_type VARCHAR(64) NOT NULL,
		subject VARCHAR(255) NOT NULL,
		status VARCHAR(32) NOT NULL DEFAULT 'queued',
		provider_message_id VARCHAR(255) NULL,
		error_message TEXT NULL,
		sent_at DATETIME NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_emails_log_user_id (user_id),
		INDEX idx_emails_log_event_type (event_type),
		INDEX idx_emails_log_status (status),
		INDEX idx_emails_log_created_at (created_at)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(40) NOT NULL DEFAULT 'system',
		title VARCHAR(180) NOT NULL,
		message TEXT NOT NULL,
		action_url VARCHAR(255) NULL,
		is_read BOOLEAN NOT NULL DEFAULT FALSE,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		read_at DATETIME NULL,
		CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
		INDEX idx_notifications_user_id (user_id),
		INDEX idx_notifications_is_read (is_read),
		INDEX idx_notifications_created_at (created_at)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

	if (!xinng_table_has_column($pdo, 'notifications', 'action_url')) {
		try { $pdo->exec('ALTER TABLE notifications ADD COLUMN action_url VARCHAR(255) NULL AFTER message'); } catch (PDOException $e) {}
	}
	if (!xinng_table_has_column($pdo, 'emails_log', 'provider_message_id')) {
		try { $pdo->exec('ALTER TABLE emails_log ADD COLUMN provider_message_id VARCHAR(255) NULL AFTER status'); } catch (PDOException $e) {}
	}
	if (!xinng_table_has_column($pdo, 'emails_log', 'error_message')) {
		try { $pdo->exec('ALTER TABLE emails_log ADD COLUMN error_message TEXT NULL AFTER provider_message_id'); } catch (PDOException $e) {}
	}
	if (!xinng_table_has_column($pdo, 'notifications', 'read_at')) {
		try { $pdo->exec('ALTER TABLE notifications ADD COLUMN read_at DATETIME NULL AFTER created_at'); } catch (PDOException $e) {}
	}
}

function xinng_log_email(PDO $pdo, ?int $userId, string $email, string $eventType, string $subject, string $status = 'queued', ?string $providerMessageId = null, ?string $errorMessage = null): int {
	xinng_ensure_communication_tables($pdo);
	$stmt = $pdo->prepare('INSERT INTO emails_log (user_id, email, event_type, subject, status, provider_message_id, error_message, sent_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
	$stmt->execute([
		$userId > 0 ? $userId : null,
		$email,
		$eventType,
		$subject,
		$status,
		$providerMessageId,
		$errorMessage,
		$status === 'sent' ? date('Y-m-d H:i:s') : null,
	]);
	return (int)$pdo->lastInsertId();
}

function xinng_send_transactional_email(?int $userId, string $email, string $eventType, string $subject, string $body, bool $isHtml = false): bool {
	$pdo = get_db_connection();
	if (!$pdo) {
		return false;
	}
	xinng_ensure_communication_tables($pdo);
	$logId = xinng_log_email($pdo, $userId, $email, $eventType, $subject, 'queued');
	$ok = send_mail($email, $subject, $body, $isHtml);
	if ($ok) {
		$stmt = $pdo->prepare('UPDATE emails_log SET status = ?, sent_at = NOW(), error_message = NULL WHERE id = ?');
		$stmt->execute(['sent', $logId]);
		return true;
	}
	$stmt = $pdo->prepare('UPDATE emails_log SET status = ?, error_message = ? WHERE id = ?');
	$stmt->execute(['failed', 'send_mail returned false', $logId]);
	return false;
}

function xinng_create_notification(PDO $pdo, int $userId, string $type, string $title, string $message, ?string $actionUrl = null): int {
	xinng_ensure_communication_tables($pdo);
	$stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, action_url, is_read, created_at) VALUES (?, ?, ?, ?, ?, FALSE, NOW())');
	$stmt->execute([$userId, $type, $title, $message, $actionUrl]);
	return (int)$pdo->lastInsertId();
}

function xinng_get_notifications(PDO $pdo, int $userId, int $limit = 20): array {
	xinng_ensure_communication_tables($pdo);
	$limit = max(1, min((int)$limit, 1000));
	$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . $limit);
	$stmt->execute([$userId]);
	return $stmt->fetchAll();
}

function xinng_unread_notification_count(PDO $pdo, int $userId): int {
	xinng_ensure_communication_tables($pdo);
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE');
	$stmt->execute([$userId]);
	return (int)$stmt->fetchColumn();
}

function xinng_mark_notification_read(PDO $pdo, int $userId, ?int $notificationId = null): void {
	xinng_ensure_communication_tables($pdo);
	if ($notificationId !== null && $notificationId > 0) {
		$stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND id = ?');
		$stmt->execute([$userId, $notificationId]);
		return;
	}
	$stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE');
	$stmt->execute([$userId]);
}

function xinng_send_welcome_email(int $userId, string $email, string $name): bool {
	$pdo = get_db_connection();
	if (!$pdo) return false;
	$firstName = trim(explode(' ', trim($name))[0]);
	if ($firstName === '') $firstName = 'there';
	$subject = 'Welcome to Xinng 🚀';
	$dashboardLink = xinng_public_base_url() . '/dashboard.php';
	$body = "Hi {$firstName},\n\n" .
		"Welcome to Xinng!\n" .
		"Your account has been successfully created.\n\n" .
		"You can now:\n" .
		"- Create QR codes\n" .
		"- Build short links\n" .
		"- Design your landing pages\n" .
		"- Track analytics in real time\n\n" .
		"Get started here:\n{$dashboardLink}\n\n" .
		"If you have any questions, we're here to help.\n\n" .
		"— The Xinng Team";
	$sent = xinng_send_transactional_email($userId, $email, 'account_created', $subject, $body, false);
	if ($sent) {
		xinng_create_notification($pdo, $userId, 'account', 'Welcome to Xinng', 'Your account is ready. Create your first QR code, short link, or page.', 'dashboard.php');
	}
	return $sent;
}

function xinng_send_password_reset_email(int $userId, string $email, string $resetUrl): bool {
	$pdo = get_db_connection();
	if (!$pdo) return false;
	$subject = 'Reset your Xinng password';
	$body = "Hi,\n\nWe received a request to reset your password.\n\nReset it here:\n{$resetUrl}\n\nThis link will expire in 15 minutes.\nIf you didn't request this, you can ignore this email.\n\n— Xinng Security";
	$sent = xinng_send_transactional_email($userId, $email, 'password_reset', $subject, $body, false);
	if ($sent) {
		xinng_create_notification($pdo, $userId, 'security', 'Password reset requested', 'A password reset link was sent to your email address.', 'reset_password.php?token=' . urlencode(parse_url($resetUrl, PHP_URL_QUERY) ?: ''));
	}
	return $sent;
}

function xinng_send_login_alert_email(int $userId, ?string $email, string $name, string $device, string $location, string $time): bool {
	if ($email === null || trim($email) === '') {
		return false;
	}
	$subject = 'New login detected on your Xinng account';
	$firstName = trim(explode(' ', trim($name))[0]);
	if ($firstName === '') $firstName = 'there';
	$securityLink = xinng_public_base_url() . '/forgot_password.php';
	$body = "Hi {$firstName},\n\n" .
		"We detected a new login to your account.\n\n" .
		"Details:\n" .
		"- Device: {$device}\n" .
		"- Location: {$location}\n" .
		"- Time: {$time}\n\n" .
		"If this was you, no action is needed.\n" .
		"If this wasn't you, please reset your password immediately:\n{$securityLink}\n\n" .
		"— Xinng Security";
	return xinng_send_transactional_email($userId, trim($email), 'login_alert', $subject, $body, false);
}

function xinng_send_weekly_analytics_email(int $userId, string $email, string $name, array $metrics): bool {
	$subject = 'Your weekly Xinng performance report 📊';
	$analyticsLink = xinng_public_base_url() . '/dashboard.php';
	$firstName = trim(explode(' ', trim($name))[0]);
	if ($firstName === '') $firstName = 'there';
	$totalScans = (string)($metrics['total_scans'] ?? 0);
	$uniqueVisitors = (string)($metrics['unique_visitors'] ?? 0);
	$topLink = $metrics['top_link'] ?? 'N/A';
	$topLocation = $metrics['top_location'] ?? 'N/A';
	$scanChange = (string)($metrics['scan_change'] ?? 0);
	$engagementChange = (string)($metrics['engagement_change'] ?? 0);
	$body = "Hi {$firstName},\n\n" .
		"Here's your weekly performance summary from Xinng.\n\n" .
		"This week:\n" .
		"- Total scans: {$totalScans}\n" .
		"- Unique visitors: {$uniqueVisitors}\n" .
		"- Top performing link: {$topLink}\n" .
		"- Top location: {$topLocation}\n\n" .
		"Compared to last week:\n" .
		"- Scans: {$scanChange}%\n" .
		"- Engagement: {$engagementChange}%\n\n" .
		"View full analytics:\n{$analyticsLink}\n\n" .
		"Keep optimizing your links and QR codes to improve performance.\n\n" .
		"— Xinng Analytics";
	return xinng_send_transactional_email($userId, $email, 'weekly_analytics', $subject, $body, false);
}

function xinng_record_communication_event(PDO $pdo, int $userId, string $type, string $title, string $message, ?string $actionUrl = null): int {
	return xinng_create_notification($pdo, $userId, $type, $title, $message, $actionUrl);
}

function xinng_percent_change(float $current, float $previous): float {
	if ($previous <= 0) {
		return $current > 0 ? 100.0 : 0.0;
	}
	return (($current - $previous) / $previous) * 100;
}

function xinng_user_weekly_summary_due(PDO $pdo, int $userId): bool {
	xinng_ensure_communication_tables($pdo);
	$stmt = $pdo->prepare('SELECT MAX(sent_at) AS last_sent FROM emails_log WHERE user_id = ? AND event_type = "weekly_analytics" AND status = "sent" LIMIT 1');
	$stmt->execute([$userId]);
	$lastSent = $stmt->fetchColumn();
	if ($lastSent === false || $lastSent === null || $lastSent === '') {
		return true;
	}
	$lastSentTs = strtotime((string)$lastSent);
	if ($lastSentTs === false) {
		return true;
	}
	return (time() - $lastSentTs) >= 7 * 24 * 60 * 60;
}

function xinng_get_user_weekly_metrics(PDO $pdo, int $userId): array {
	$xWindow = date('Y-m-d H:i:s', strtotime('-7 days'));
	$xPrevWindow = date('Y-m-d H:i:s', strtotime('-14 days'));
	$xPrevEnd = date('Y-m-d H:i:s', strtotime('-7 days'));

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_code_scans qcs JOIN qr_codes qc ON qc.id = qcs.qr_code_id WHERE qc.user_id = ? AND qcs.scanned_at >= ?');
	$stmt->execute([$userId, $xWindow]);
	$totalScans = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT COUNT(DISTINCT COALESCE(qcs.user_id, qcs.ip_hash)) FROM qr_code_scans qcs JOIN qr_codes qc ON qc.id = qcs.qr_code_id WHERE qc.user_id = ? AND qcs.scanned_at >= ?');
	$stmt->execute([$userId, $xWindow]);
	$uniqueVisitors = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT sl.title, COUNT(*) AS clicks FROM short_link_clicks slc JOIN short_links sl ON sl.id = slc.short_link_id WHERE sl.user_id = ? AND slc.clicked_at >= ? GROUP BY sl.id, sl.title ORDER BY clicks DESC LIMIT 1');
	$stmt->execute([$userId, $xWindow]);
	$topLinkRow = $stmt->fetch();
	$topLink = $topLinkRow['title'] ?? 'N/A';

	$stmt = $pdo->prepare('SELECT country, COUNT(*) AS cnt FROM (
		SELECT pv.country FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND pv.viewed_at >= ?
		UNION ALL
		SELECT lc.country FROM link_clicks lc JOIN pages p ON p.id = lc.page_id WHERE p.user_id = ? AND lc.clicked_at >= ?
		UNION ALL
		SELECT qcs.country FROM qr_code_scans qcs JOIN qr_codes qc ON qc.id = qcs.qr_code_id WHERE qc.user_id = ? AND qcs.scanned_at >= ?
	) t WHERE country IS NOT NULL AND country != "" GROUP BY country ORDER BY cnt DESC LIMIT 1');
	$stmt->execute([$userId, $xWindow, $userId, $xWindow, $userId, $xWindow]);
	$topLocation = $stmt->fetchColumn() ?: 'N/A';

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_code_scans qcs JOIN qr_codes qc ON qc.id = qcs.qr_code_id WHERE qc.user_id = ? AND qcs.scanned_at >= ? AND qcs.scanned_at < ?');
	$stmt->execute([$userId, $xPrevWindow, $xPrevEnd]);
	$previousScans = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM short_link_clicks slc JOIN short_links sl ON sl.id = slc.short_link_id WHERE sl.user_id = ? AND slc.clicked_at >= ? AND slc.clicked_at < ?');
	$stmt->execute([$userId, $xPrevWindow, $xPrevEnd]);
	$previousLinkClicks = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare('SELECT COUNT(*) FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND pv.viewed_at >= ? AND pv.viewed_at < ?');
	$stmt->execute([$userId, $xPrevWindow, $xPrevEnd]);
	$previousPageViews = (int)$stmt->fetchColumn();

	$thisWeekLinkClicks = (function() use ($pdo, $userId, $xWindow) {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM short_link_clicks slc JOIN short_links sl ON sl.id = slc.short_link_id WHERE sl.user_id = ? AND slc.clicked_at >= ?');
		$stmt->execute([$userId, $xWindow]);
		return (int)$stmt->fetchColumn();
	})();
	$thisWeekTotalEngagement = $totalScans + $thisWeekLinkClicks;
	$thisWeekTotalEngagement += (function() use ($pdo, $userId, $xWindow) {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM page_views pv JOIN pages p ON p.id = pv.page_id WHERE p.user_id = ? AND pv.viewed_at >= ?');
		$stmt->execute([$userId, $xWindow]);
		return (int)$stmt->fetchColumn();
	})();

	$previousWeekTotalEngagement = $previousScans + $previousLinkClicks + $previousPageViews;
	$scanChange = xinng_percent_change((float)$totalScans, (float)$previousScans);
	$engagementChange = xinng_percent_change((float)$thisWeekTotalEngagement, (float)$previousWeekTotalEngagement);

	return [
		'total_scans' => $totalScans,
		'unique_visitors' => $uniqueVisitors,
		'top_link' => $topLink,
		'top_location' => $topLocation,
		'scan_change' => round($scanChange, 1),
		'engagement_change' => round($engagementChange, 1),
	];
}

function xinng_send_due_weekly_analytics_reports(): int {
	$pdo = get_db_connection();
	if (!$pdo) {
		return 0;
	}
	$sentCount = 0;
	$stmt = $pdo->query('SELECT id, email, name FROM users WHERE deleted_at IS NULL ORDER BY id ASC');
	$users = $stmt->fetchAll();
	foreach ($users as $user) {
		$userId = (int)$user['id'];
		if (!xinng_user_weekly_summary_due($pdo, $userId)) {
			continue;
		}
		$metrics = xinng_get_user_weekly_metrics($pdo, $userId);
		$ok = xinng_send_weekly_analytics_email($userId, (string)$user['email'], (string)($user['name'] ?? 'there'), $metrics);
		if ($ok) {
			$sentCount++;
		}
	}
	return $sentCount;
}

function xinng_record_page_view(PDO $pdo, int $page_id): void {
	xinng_ensure_tracking_tables($pdo);
	$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
	$uaMeta = xinng_parse_user_agent((string)$userAgent);
	$stmt = $pdo->prepare('INSERT INTO page_views (page_id, visitor_id, ip_address, user_agent, referrer, country, city, device_type, browser, os, viewed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
	$stmt->execute([
		$page_id,
		xinng_current_visitor_id(),
		xinng_client_ip(),
		$userAgent,
		$_SERVER['HTTP_REFERER'] ?? null,
		null,
		null,
		$uaMeta['device_type'],
		$uaMeta['browser'],
		$uaMeta['os'],
	]);
}

function xinng_record_link_click(PDO $pdo, int $page_id, ?int $block_id = null, ?int $link_id = null): void {
	xinng_ensure_tracking_tables($pdo);
	$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
	$uaMeta = xinng_parse_user_agent((string)$userAgent);
	$stmt = $pdo->prepare('INSERT INTO link_clicks (link_id, block_id, page_id, visitor_id, ip_address, user_agent, referrer, country, city, device_type, clicked_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
	$stmt->execute([
		$link_id,
		$block_id,
		$page_id,
		xinng_current_visitor_id(),
		xinng_client_ip(),
		$userAgent,
		$_SERVER['HTTP_REFERER'] ?? null,
		null,
		null,
		$uaMeta['device_type'],
	]);
}

function xinng_record_qr_scan(PDO $pdo, int $qr_code_id, int $page_id): void {
	xinng_ensure_tracking_tables($pdo);
	$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
	$stmt = $pdo->prepare('INSERT INTO qr_scans (qr_code_id, page_id, visitor_id, ip_address, user_agent, country, city, scanned_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
	$stmt->execute([
		$qr_code_id,
		$page_id,
		xinng_current_visitor_id(),
		xinng_client_ip(),
		$userAgent,
		null,
		null,
	]);
}

function xinng_validate_qr_back_half(PDO $pdo, ?string $raw, ?int $current_qr_id = null, ?int $current_short_link_id = null): array {
	$raw = trim((string)$raw);
	if ($raw === '') return ['ok' => true, 'back_half' => null];
	$base = xinng_validate_back_half($pdo, $raw, $current_short_link_id);
	if (!$base['ok']) return $base;
	if ($current_qr_id) {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_codes WHERE back_half = ? AND id != ?');
		$stmt->execute([$base['back_half'], $current_qr_id]);
	} else {
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_codes WHERE back_half = ?');
		$stmt->execute([$base['back_half']]);
	}
	if ((int)$stmt->fetchColumn() > 0) {
		return ['ok' => false, 'error' => 'This back-half is already used by a QR code.'];
	}
	return ['ok' => true, 'back_half' => $base['back_half']];
}

function xinng_persist_pending_qr(PDO $pdo, array $qrData, ?int $userId = null): ?int {
	xinng_ensure_qr_code_tables($pdo);

	$destination = trim((string)($qrData['destination_url'] ?? ''));
	$qrType = trim((string)($qrData['qr_type'] ?? 'Website'));
	$style = trim((string)($qrData['style'] ?? 'Standard'));
	$title = $qrType !== '' ? $qrType : 'QR Code';
	$patternStyle = 'default';
	$cornerStyle = 'square';
	if (strtolower($style) === 'bold') {
		$patternStyle = 'rounded';
	} elseif (strtolower($style) === 'minimal') {
		$patternStyle = 'dots';
	}
	$type = 'website';
	if (in_array($qrType, ['Product', 'Campaign'], true)) {
		$type = 'custom';
	}

	$stmt = $pdo->prepare(
		'INSERT INTO qr_codes (user_id, page_id, short_link_id, profile_page_id, type, title, name, destination_url, status, code_color, background_color, pattern_style, corner_style, qr_image_url, created_at, updated_at) VALUES (?, NULL, NULL, NULL, ?, ?, ?, ?, "active", "#000000", "#FFFFFF", ?, ?, ?, NOW(), NOW())'
	);
	$stmt->execute([
		$userId,
		$type,
		$title,
		'QR Code',
		$destination !== '' ? $destination : null,
		$patternStyle,
		$cornerStyle,
		$qrData['qr_png_url'] ?? null,
	]);

	return (int)$pdo->lastInsertId();
}

function xinng_ensure_page_builder_tables(PDO $pdo): void {
	xinng_ensure_tracking_tables($pdo);
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS pages (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			page_type VARCHAR(32) NOT NULL DEFAULT 'creator',
			corporate_metadata JSON NULL,
			slug VARCHAR(80) NULL,
			title VARCHAR(150) NOT NULL DEFAULT '',
			bio TEXT NULL,
			description VARCHAR(255) NULL,
			profile_image_url TEXT NULL,
			profile_image_path TEXT NULL,
			cover_image_url TEXT NULL,
			theme VARCHAR(80) DEFAULT 'default',
			layout VARCHAR(80) DEFAULT 'simple',
			font VARCHAR(80) DEFAULT 'system',
			title_color VARCHAR(20) DEFAULT '#26282C',
			description_color VARCHAR(20) DEFAULT '#26282C',
			header_mode VARCHAR(20) DEFAULT 'color',
			header_color VARCHAR(20) DEFAULT '#26282C',
			header_gradient_start VARCHAR(20) DEFAULT '#26282C',
			header_gradient_end VARCHAR(20) DEFAULT '#0A9994',
			header_image_path TEXT NULL,
			header_fit VARCHAR(20) DEFAULT 'cover',
			background_mode VARCHAR(20) DEFAULT 'color',
			background_color VARCHAR(20) DEFAULT '#FFFAF6',
			background_gradient_start VARCHAR(20) DEFAULT '#FFFAF6',
			background_gradient_end VARCHAR(20) DEFAULT '#FFFFFF',
			background_image_path TEXT NULL,
			social_icon_style VARCHAR(20) DEFAULT 'original',
			social_placement VARCHAR(20) DEFAULT 'top',
			block_shape VARCHAR(40) DEFAULT 'rounded',
			block_shadow VARCHAR(40) DEFAULT 'soft',
			block_color VARCHAR(20) DEFAULT '#0A9994',
			block_text_color VARCHAR(20) DEFAULT '#FFFAF6',
			hide_xinng_logo BOOLEAN DEFAULT FALSE,
			is_published BOOLEAN DEFAULT TRUE,
			is_verified BOOLEAN DEFAULT FALSE,
			status VARCHAR(20) DEFAULT 'published',
			published_at DATETIME NULL,
			seo_title VARCHAR(180) NULL,
			seo_description TEXT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			CONSTRAINT fk_pages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
			UNIQUE KEY unique_pages_slug (slug),
			INDEX idx_pages_user_id (user_id),
			INDEX idx_pages_slug (slug),
			INDEX idx_pages_published (is_published),
			INDEX idx_pages_page_type (page_type)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
	$columns = [
		'page_type' => "ADD COLUMN page_type VARCHAR(32) NOT NULL DEFAULT 'creator' AFTER user_id",
		'corporate_metadata' => "ADD COLUMN corporate_metadata JSON NULL AFTER page_type",
		'description' => "ADD COLUMN description VARCHAR(255) NULL AFTER bio",
		'profile_image_path' => "ADD COLUMN profile_image_path TEXT NULL AFTER profile_image_url",
		'theme' => "ADD COLUMN theme VARCHAR(80) DEFAULT 'default' AFTER page_type",
		'layout' => "ADD COLUMN layout VARCHAR(80) DEFAULT 'simple' AFTER theme",
		'font' => "ADD COLUMN font VARCHAR(80) DEFAULT 'system' AFTER layout",
		'title_color' => "ADD COLUMN title_color VARCHAR(20) DEFAULT '#26282C' AFTER font",
		'description_color' => "ADD COLUMN description_color VARCHAR(20) DEFAULT '#26282C' AFTER title_color",
		'header_mode' => "ADD COLUMN header_mode VARCHAR(20) DEFAULT 'color' AFTER description_color",
		'header_color' => "ADD COLUMN header_color VARCHAR(20) DEFAULT '#26282C' AFTER header_mode",
		'header_gradient_start' => "ADD COLUMN header_gradient_start VARCHAR(20) DEFAULT '#26282C' AFTER header_color",
		'header_gradient_end' => "ADD COLUMN header_gradient_end VARCHAR(20) DEFAULT '#0A9994' AFTER header_gradient_start",
		'header_image_path' => "ADD COLUMN header_image_path TEXT NULL AFTER header_gradient_end",
		'header_fit' => "ADD COLUMN header_fit VARCHAR(20) DEFAULT 'cover' AFTER header_image_path",
		'background_mode' => "ADD COLUMN background_mode VARCHAR(20) DEFAULT 'color' AFTER header_fit",
		'background_color' => "ADD COLUMN background_color VARCHAR(20) DEFAULT '#FFFAF6' AFTER background_mode",
		'background_gradient_start' => "ADD COLUMN background_gradient_start VARCHAR(20) DEFAULT '#FFFAF6' AFTER background_color",
		'background_gradient_end' => "ADD COLUMN background_gradient_end VARCHAR(20) DEFAULT '#FFFFFF' AFTER background_gradient_start",
		'background_image_path' => "ADD COLUMN background_image_path TEXT NULL AFTER background_gradient_end",
		'social_icon_style' => "ADD COLUMN social_icon_style VARCHAR(20) DEFAULT 'original' AFTER background_image_path",
		'social_placement' => "ADD COLUMN social_placement VARCHAR(20) DEFAULT 'top' AFTER social_icon_style",
		'block_shape' => "ADD COLUMN block_shape VARCHAR(40) DEFAULT 'rounded' AFTER social_placement",
		'block_shadow' => "ADD COLUMN block_shadow VARCHAR(40) DEFAULT 'soft' AFTER block_shape",
		'block_color' => "ADD COLUMN block_color VARCHAR(20) DEFAULT '#0A9994' AFTER block_shadow",
		'block_text_color' => "ADD COLUMN block_text_color VARCHAR(20) DEFAULT '#FFFAF6' AFTER block_color",
		'hide_xinng_logo' => "ADD COLUMN hide_xinng_logo BOOLEAN DEFAULT FALSE AFTER block_text_color",
		'status' => "ADD COLUMN status VARCHAR(20) DEFAULT 'published' AFTER hide_xinng_logo",
		'published_at' => "ADD COLUMN published_at DATETIME NULL AFTER status",
		'deleted_at' => "ADD COLUMN deleted_at DATETIME NULL AFTER updated_at",
	];
	foreach ($columns as $column => $ddl) {
		if (!xinng_table_has_column($pdo, 'pages', $column)) {
			$pdo->exec("ALTER TABLE pages $ddl");
		}
	}
	try { $pdo->exec("UPDATE pages SET description = COALESCE(description, bio), status = COALESCE(status, IF(is_published = 1, 'published', 'draft'))"); } catch (PDOException $e) {}
	try { $pdo->exec("UPDATE pages SET page_type = 'creator' WHERE page_type IS NULL OR page_type NOT IN ('creator', 'corporate')"); } catch (PDOException $e) {}
	// Map common aliases to 'corporate' before forcing unknowns to 'creator'
	try {
		$pdo->exec("UPDATE pages SET page_type = 'corporate' WHERE LOWER(page_type) IN ('company','company page','company_page','companypage','corp')");
	} catch (PDOException $e) {}
	try { $pdo->exec("UPDATE pages SET page_type = 'creator' WHERE page_type IS NULL OR page_type NOT IN ('creator', 'corporate')"); } catch (PDOException $e) {}
	// Tighten schema: make page_type an ENUM to prevent invalid values (best-effort)
	try { $pdo->exec("ALTER TABLE pages MODIFY page_type ENUM('creator','corporate') NOT NULL DEFAULT 'creator'"); } catch (PDOException $e) {}
	try { $pdo->exec("CREATE INDEX idx_pages_page_type ON pages(page_type)"); } catch (PDOException $e) {}
	try { $pdo->exec("ALTER TABLE pages MODIFY profile_image_path MEDIUMTEXT NULL, MODIFY header_image_path MEDIUMTEXT NULL, MODIFY background_image_path MEDIUMTEXT NULL"); } catch (PDOException $e) {}

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_blocks (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(40) NOT NULL,
			title VARCHAR(150) NULL,
			description TEXT NULL,
			destination_url TEXT NULL,
			image_path TEXT NULL,
			metadata JSON NULL,
			position INT DEFAULT 0,
			is_active BOOLEAN DEFAULT TRUE,
			starts_at DATETIME NULL,
			ends_at DATETIME NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			CONSTRAINT fk_page_blocks_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_blocks_page_id (page_id),
			INDEX idx_page_blocks_user_id (user_id),
			INDEX idx_page_blocks_type (type),
			INDEX idx_page_blocks_position (position),
			INDEX idx_page_blocks_active (is_active)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
	if (!xinng_table_has_column($pdo, 'page_blocks', 'deleted_at')) {
		try { $pdo->exec('ALTER TABLE page_blocks ADD COLUMN deleted_at DATETIME NULL AFTER updated_at'); } catch (PDOException $e) {}
	}

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_socials (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			platform VARCHAR(40) NOT NULL,
			label VARCHAR(120) NULL,
			url TEXT NOT NULL,
			icon VARCHAR(80) NULL,
			position INT DEFAULT 0,
			is_active BOOLEAN DEFAULT TRUE,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			CONSTRAINT fk_page_socials_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_socials_page_id (page_id),
			INDEX idx_page_socials_user_id (user_id),
			INDEX idx_page_socials_platform (platform),
			INDEX idx_page_socials_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
	if (!xinng_table_has_column($pdo, 'page_socials', 'deleted_at')) {
		try { $pdo->exec('ALTER TABLE page_socials ADD COLUMN deleted_at DATETIME NULL AFTER updated_at'); } catch (PDOException $e) {}
	}

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_profiles (
			page_id BIGINT UNSIGNED PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			header_photo_path MEDIUMTEXT NULL,
			logo_path MEDIUMTEXT NULL,
			company_name VARCHAR(100) NULL,
			page_description VARCHAR(200) NULL,
			meeting_link TEXT NULL,
			brochure_link TEXT NULL,
			phone VARCHAR(40) NULL,
			email VARCHAR(120) NULL,
			whatsapp VARCHAR(40) NULL,
			team_title VARCHAR(30) DEFAULT 'Team',
			team_description VARCHAR(150) NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_profiles_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_profiles_user_id (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_facts (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			fact_type ENUM('specialty','location','link') NOT NULL,
			label VARCHAR(80) NOT NULL,
			url TEXT NULL,
			position INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_facts_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_facts_page_type (page_id, fact_type),
			INDEX idx_page_corporate_facts_user_id (user_id),
			INDEX idx_page_corporate_facts_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_socials (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			platform VARCHAR(40) NOT NULL,
			url TEXT NOT NULL,
			position INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_socials_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_socials_page_id (page_id),
			INDEX idx_page_corporate_socials_user_id (user_id),
			INDEX idx_page_corporate_socials_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_events (
			page_id BIGINT UNSIGNED PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(80) NULL,
			description VARCHAR(150) NULL,
			start_at DATETIME NULL,
			end_at DATETIME NULL,
			location_text VARCHAR(120) NULL,
			city VARCHAR(80) NULL,
			countdown_enabled BOOLEAN DEFAULT TRUE,
			book_link TEXT NULL,
			brochure_link TEXT NULL,
			register_enabled BOOLEAN DEFAULT TRUE,
			card_color VARCHAR(20) DEFAULT '#062947',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_events_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_events_user_id (user_id),
			INDEX idx_page_corporate_events_start_at (start_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_cards (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(100) NOT NULL,
			card_type ENUM('text','video','pdf') DEFAULT 'text',
			description TEXT NULL,
			cta_label VARCHAR(30) NULL,
			destination_url TEXT NULL,
			fill_type ENUM('color','gradient','photo') DEFAULT 'color',
			fill_color VARCHAR(20) DEFAULT '#06111E',
			gradient_start VARCHAR(20) DEFAULT '#06111E',
			gradient_end VARCHAR(20) DEFAULT '#0A9994',
			photo_path MEDIUMTEXT NULL,
			outline_color VARCHAR(20) DEFAULT '#0A9994',
			outline_weight TINYINT UNSIGNED DEFAULT 0,
			position INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_cards_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_cards_page_id (page_id),
			INDEX idx_page_corporate_cards_user_id (user_id),
			INDEX idx_page_corporate_cards_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_buttons (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			label VARCHAR(30) NOT NULL,
			destination_url TEXT NOT NULL,
			button_color VARCHAR(20) DEFAULT '#1979BF',
			text_color VARCHAR(20) DEFAULT '#FFFFFF',
			position INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_buttons_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_buttons_page_id (page_id),
			INDEX idx_page_corporate_buttons_user_id (user_id),
			INDEX idx_page_corporate_buttons_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_team_members (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			photo_path MEDIUMTEXT NULL,
			name VARCHAR(120) NOT NULL,
			role_title VARCHAR(30) NULL,
			phone VARCHAR(40) NULL,
			email VARCHAR(120) NULL,
			linkedin_url TEXT NULL,
			position INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_team_members_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_team_page_id (page_id),
			INDEX idx_page_corporate_team_user_id (user_id),
			INDEX idx_page_corporate_team_position (position)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_quote_requests (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(120) NOT NULL,
			company VARCHAR(120) NULL,
			phone VARCHAR(40) NULL,
			email VARCHAR(120) NOT NULL,
			request_text TEXT NULL,
			status VARCHAR(30) DEFAULT 'new',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_quote_requests_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_quote_page_id (page_id),
			INDEX idx_page_corporate_quote_status (status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");

	$pdo->exec("
		CREATE TABLE IF NOT EXISTS page_corporate_event_registrations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			page_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(120) NOT NULL,
			phone VARCHAR(40) NULL,
			email VARCHAR(120) NOT NULL,
			status VARCHAR(30) DEFAULT 'new',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			CONSTRAINT fk_page_corporate_event_registrations_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
			INDEX idx_page_corporate_registration_page_id (page_id),
			INDEX idx_page_corporate_registration_status (status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	");
	try { $pdo->exec("ALTER TABLE page_blocks MODIFY image_path MEDIUMTEXT NULL"); } catch (PDOException $e) {}
	try {
		$stmt = $pdo->query("
			SELECT p.*
			FROM pages p
			LEFT JOIN page_corporate_profiles cp ON cp.page_id = p.id
			WHERE p.page_type = 'corporate'
			  AND p.corporate_metadata IS NOT NULL
			  AND cp.page_id IS NULL
		");
		foreach ($stmt->fetchAll() as $page) {
			$legacy = json_decode((string)$page['corporate_metadata'], true);
			if (is_array($legacy)) {
				xinng_save_corporate_page_data($pdo, (int)$page['id'], (int)$page['user_id'], $legacy);
			}
		}
	} catch (Throwable $e) {
		error_log('Corporate page backfill failed: ' . $e->getMessage());
	}
}

function xinng_default_corporate_page_data(array $page = []): array {
	return [
		'header_photo' => '',
		'logo' => $page['profile_image_path'] ?? ($page['profile_image_url'] ?? ''),
		'company_name' => '',
		'description' => '',
		'cards_title' => '',
		'cards_lede' => '',
		'actions_title' => '',
		'actions_lede' => '',
		'event_register_title' => '',
		'hero_primary_cta_label' => '',
		'hero_primary_cta_url' => '',
		'quote_title' => '',
		'quote_description' => '',
		'quote_button_label' => '',
		'company_website' => '',
		'contact' => ['meeting_link' => '', 'brochure_link' => '', 'phone' => '', 'email' => '', 'whatsapp' => ''],
		'specialties' => [],
		'locations' => [],
		'links' => [],
		'socials' => [],
		'event' => ['title' => '', 'description' => '', 'start_at' => '', 'end_at' => '', 'location' => '', 'city' => '', 'countdown' => false, 'book_link' => '', 'brochure_link' => '', 'register' => false, 'card_color' => '#062947', 'button_label' => ''],
		'cards' => [],
		'buttons' => [],
		'team' => ['title' => '', 'description' => '', 'members' => []],
	];
}

function xinng_load_corporate_page_data(PDO $pdo, int $page_id, array $page = []): array {
	$data = xinng_default_corporate_page_data($page);
	if (!empty($page['corporate_metadata'])) {
		$legacy = json_decode((string)$page['corporate_metadata'], true);
		if (is_array($legacy)) {
			$data = array_replace_recursive($data, $legacy);
		}
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_profiles WHERE page_id = ? LIMIT 1');
	$stmt->execute([$page_id]);
	if ($profile = $stmt->fetch()) {
		$headerPhoto = trim((string)($profile['header_photo_path'] ?? ''));
		$logo = trim((string)($profile['logo_path'] ?? ''));
		$companyName = trim((string)($profile['company_name'] ?? ''));
		$pageDescription = trim((string)($profile['page_description'] ?? ''));
		$meetingLink = trim((string)($profile['meeting_link'] ?? ''));
		$brochureLink = trim((string)($profile['brochure_link'] ?? ''));
		$phone = trim((string)($profile['phone'] ?? ''));
		$email = trim((string)($profile['email'] ?? ''));
		$whatsapp = trim((string)($profile['whatsapp'] ?? ''));

		if ($headerPhoto !== '') {
			$data['header_photo'] = $headerPhoto;
		}
		if ($logo !== '') {
			$data['logo'] = $logo;
		}
		if ($companyName !== '') {
			$data['company_name'] = $companyName;
		}
		if ($pageDescription !== '') {
			$data['description'] = $pageDescription;
		}
		if ($meetingLink !== '') {
			$data['contact']['meeting_link'] = $meetingLink;
		}
		if ($brochureLink !== '') {
			$data['contact']['brochure_link'] = $brochureLink;
		}
		if ($phone !== '') {
			$data['contact']['phone'] = $phone;
		}
		if ($email !== '') {
			$data['contact']['email'] = $email;
		}
		if ($whatsapp !== '') {
			$data['contact']['whatsapp'] = $whatsapp;
		}
		if (trim((string)($profile['team_title'] ?? '')) !== '') {
			$data['team']['title'] = $profile['team_title'];
		}
		if (trim((string)($profile['team_description'] ?? '')) !== '') {
			$data['team']['description'] = $profile['team_description'];
		}
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_facts WHERE page_id = ? ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$facts = $stmt->fetchAll();
	if ($facts) {
		$data['specialties'] = [];
		$data['locations'] = [];
		$data['links'] = [];
		foreach ($facts as $fact) {
			if ($fact['fact_type'] === 'specialty') $data['specialties'][] = $fact['label'];
			if ($fact['fact_type'] === 'location') $data['locations'][] = $fact['label'];
			if ($fact['fact_type'] === 'link') $data['links'][] = ['label' => $fact['label'], 'url' => $fact['url'] ?? ''];
		}
	}
	if (trim((string)($data['company_website'] ?? '')) === '') {
		foreach ((array)($data['links'] ?? []) as $link) {
			$label = strtolower(trim((string)($link['label'] ?? '')));
			if ($label === 'website' || $label === 'company website' || $label === 'company website url') {
				$data['company_website'] = trim((string)($link['url'] ?? ''));
				break;
			}
		}
	}

	$stmt = $pdo->prepare('SELECT platform, url FROM page_corporate_socials WHERE page_id = ? ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$socials = $stmt->fetchAll();
	if ($socials) $data['socials'] = $socials;

	$stmt = $pdo->prepare('SELECT platform, url FROM page_socials WHERE page_id = ? AND deleted_at IS NULL ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$pageSocials = $stmt->fetchAll();
	foreach ($pageSocials as $social) {
		if (!empty($social['platform']) && !empty($social['url'])) {
			$data['socials'][] = ['platform' => $social['platform'], 'url' => $social['url']];
		}
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_events WHERE page_id = ? LIMIT 1');
	$stmt->execute([$page_id]);
	if ($event = $stmt->fetch()) {
		$data['event'] = [
			'title' => $event['title'] ?? '',
			'description' => $event['description'] ?? '',
			'start_at' => $event['start_at'] ?? '',
			'end_at' => $event['end_at'] ?? '',
			'location' => $event['location_text'] ?? '',
			'city' => $event['city'] ?? '',
			'countdown' => !empty($event['countdown_enabled']),
			'book_link' => $event['book_link'] ?? '',
			'brochure_link' => $event['brochure_link'] ?? '',
			'register' => !empty($event['register_enabled']),
			'card_color' => $event['card_color'] ?? '#062947',
		];
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_cards WHERE page_id = ? ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$cards = $stmt->fetchAll();
	if ($cards) {
		$data['cards'] = array_map(static fn($card) => [
			'title' => $card['title'] ?? '',
			'type' => $card['card_type'] ?? 'text',
			'description' => $card['description'] ?? '',
			'cta_label' => $card['cta_label'] ?? '',
			'link' => $card['destination_url'] ?? '',
			'fill_type' => $card['fill_type'] ?? 'color',
			'fill_color' => $card['fill_color'] ?? '#06111E',
			'gradient_start' => $card['gradient_start'] ?? '#06111E',
			'gradient_end' => $card['gradient_end'] ?? '#0A9994',
			'photo' => $card['photo_path'] ?? '',
			'outline_color' => $card['outline_color'] ?? '#0A9994',
			'outline_weight' => (int)($card['outline_weight'] ?? 0),
		], $cards);
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_buttons WHERE page_id = ? ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$buttons = $stmt->fetchAll();
	if ($buttons) {
		$data['buttons'] = array_map(static fn($button) => [
			'label' => $button['label'] ?? '',
			'url' => $button['destination_url'] ?? '',
			'button_color' => $button['button_color'] ?? '#1979BF',
			'text_color' => $button['text_color'] ?? '#FFFFFF',
		], $buttons);
	}

	$stmt = $pdo->prepare('SELECT * FROM page_corporate_team_members WHERE page_id = ? ORDER BY position ASC, id ASC');
	$stmt->execute([$page_id]);
	$members = $stmt->fetchAll();
	if ($members) {
		$data['team']['members'] = array_map(static fn($member) => [
			'photo' => $member['photo_path'] ?? '',
			'name' => $member['name'] ?? '',
			'title' => $member['role_title'] ?? '',
			'phone' => $member['phone'] ?? '',
			'email' => $member['email'] ?? '',
			'linkedin' => $member['linkedin_url'] ?? '',
		], $members);
	}

	return $data;
}

function xinng_normalize_datetime_for_db(?string $value): ?string {
	$value = trim((string)$value);
	if ($value === '') return null;
	$time = strtotime($value);
	return $time ? date('Y-m-d H:i:s', $time) : null;
}

function xinng_save_corporate_page_data(PDO $pdo, int $page_id, int $user_id, array $data): void {
	$contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
	$event = is_array($data['event'] ?? null) ? $data['event'] : [];
	$team = is_array($data['team'] ?? null) ? $data['team'] : [];

	$stmt = $pdo->prepare('
		INSERT INTO page_corporate_profiles
			(page_id, user_id, header_photo_path, logo_path, company_name, page_description, meeting_link, brochure_link, phone, email, whatsapp, team_title, team_description, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
		ON DUPLICATE KEY UPDATE
			user_id=VALUES(user_id), header_photo_path=VALUES(header_photo_path), logo_path=VALUES(logo_path), company_name=VALUES(company_name), page_description=VALUES(page_description), meeting_link=VALUES(meeting_link), brochure_link=VALUES(brochure_link), phone=VALUES(phone), email=VALUES(email), whatsapp=VALUES(whatsapp), team_title=VALUES(team_title), team_description=VALUES(team_description), updated_at=NOW()
	');
	$stmt->execute([$page_id, $user_id, $data['header_photo'] ?? null, $data['logo'] ?? null, $data['company_name'] ?? null, $data['description'] ?? null, $contact['meeting_link'] ?? null, $contact['brochure_link'] ?? null, $contact['phone'] ?? null, $contact['email'] ?? null, $contact['whatsapp'] ?? null, $team['title'] ?? 'Team', $team['description'] ?? null]);

	$companyWebsite = trim((string)($data['company_website'] ?? ''));
	$links = array_values(array_filter((array)($data['links'] ?? []), static fn($link) => is_array($link) && (!empty($link['label']) || !empty($link['url']))));
	if ($companyWebsite !== '') {
		$links = array_values(array_filter($links, static fn($link) => !is_array($link) || !in_array(strtolower(trim((string)($link['label'] ?? ''))), ['website', 'company website', 'company website url'], true)));
		array_unshift($links, ['label' => 'Website', 'url' => $companyWebsite]);
	}

	$pdo->prepare('DELETE FROM page_corporate_facts WHERE page_id = ?')->execute([$page_id]);
	$stmt = $pdo->prepare('INSERT INTO page_corporate_facts (page_id, user_id, fact_type, label, url, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
	foreach (array_slice((array)($data['specialties'] ?? []), 0, 6) as $pos => $label) {
		$label = trim((string)$label);
		if ($label !== '') $stmt->execute([$page_id, $user_id, 'specialty', $label, null, $pos]);
	}
	foreach (array_slice((array)($data['locations'] ?? []), 0, 3) as $pos => $label) {
		$label = trim((string)$label);
		if ($label !== '') $stmt->execute([$page_id, $user_id, 'location', $label, null, $pos]);
	}
	foreach (array_slice($links, 0, 3) as $pos => $link) {
		if (!is_array($link)) continue;
		$label = trim((string)($link['label'] ?? ''));
		$url = trim((string)($link['url'] ?? ''));
		if ($label !== '' || $url !== '') $stmt->execute([$page_id, $user_id, 'link', $label ?: $url, $url, $pos]);
	}

	$pdo->prepare('DELETE FROM page_corporate_socials WHERE page_id = ?')->execute([$page_id]);
	$stmt = $pdo->prepare('INSERT INTO page_corporate_socials (page_id, user_id, platform, url, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
	foreach (array_slice((array)($data['socials'] ?? []), 0, 6) as $pos => $social) {
		if (!is_array($social)) continue;
		$platform = trim((string)($social['platform'] ?? ''));
		$url = trim((string)($social['url'] ?? ''));
		if ($platform !== '' && $url !== '') $stmt->execute([$page_id, $user_id, $platform, $url, $pos]);
	}

	$stmt = $pdo->prepare('
		INSERT INTO page_corporate_events
			(page_id, user_id, title, description, start_at, end_at, location_text, city, countdown_enabled, book_link, brochure_link, register_enabled, card_color, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
		ON DUPLICATE KEY UPDATE
			user_id=VALUES(user_id), title=VALUES(title), description=VALUES(description), start_at=VALUES(start_at), end_at=VALUES(end_at), location_text=VALUES(location_text), city=VALUES(city), countdown_enabled=VALUES(countdown_enabled), book_link=VALUES(book_link), brochure_link=VALUES(brochure_link), register_enabled=VALUES(register_enabled), card_color=VALUES(card_color), updated_at=NOW()
	');
	$stmt->execute([$page_id, $user_id, $event['title'] ?? null, $event['description'] ?? null, xinng_normalize_datetime_for_db($event['start_at'] ?? null), xinng_normalize_datetime_for_db($event['end_at'] ?? null), $event['location'] ?? null, $event['city'] ?? null, !empty($event['countdown']) ? 1 : 0, $event['book_link'] ?? null, $event['brochure_link'] ?? null, !empty($event['register']) ? 1 : 0, $event['card_color'] ?? '#062947']);

	$pdo->prepare('DELETE FROM page_corporate_cards WHERE page_id = ?')->execute([$page_id]);
	$stmt = $pdo->prepare('INSERT INTO page_corporate_cards (page_id, user_id, title, card_type, description, cta_label, destination_url, fill_type, fill_color, gradient_start, gradient_end, photo_path, outline_color, outline_weight, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
	foreach (array_slice((array)($data['cards'] ?? []), 0, 12) as $pos => $card) {
		if (!is_array($card) || empty($card['title'])) continue;
		$stmt->execute([$page_id, $user_id, $card['title'], $card['type'] ?? 'text', $card['description'] ?? null, $card['cta_label'] ?? null, $card['link'] ?? null, $card['fill_type'] ?? 'color', $card['fill_color'] ?? '#06111E', $card['gradient_start'] ?? '#06111E', $card['gradient_end'] ?? '#0A9994', $card['photo'] ?? null, $card['outline_color'] ?? '#0A9994', max(0, min(5, (int)($card['outline_weight'] ?? 0))), $pos]);
	}

	$pdo->prepare('DELETE FROM page_corporate_buttons WHERE page_id = ?')->execute([$page_id]);
	$stmt = $pdo->prepare('INSERT INTO page_corporate_buttons (page_id, user_id, label, destination_url, button_color, text_color, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
	foreach (array_slice((array)($data['buttons'] ?? []), 0, 8) as $pos => $button) {
		if (!is_array($button)) continue;
		$label = trim((string)($button['label'] ?? ''));
		$url = trim((string)($button['url'] ?? ''));
		if ($label !== '' && $url !== '') {
			$stmt->execute([$page_id, $user_id, $label, $url, $button['button_color'] ?? '#1979BF', $button['text_color'] ?? '#FFFFFF', $pos]);
		}
	}

	$pdo->prepare('DELETE FROM page_corporate_team_members WHERE page_id = ?')->execute([$page_id]);
	$stmt = $pdo->prepare('INSERT INTO page_corporate_team_members (page_id, user_id, photo_path, name, role_title, phone, email, linkedin_url, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
	foreach (array_slice((array)($team['members'] ?? []), 0, 12) as $pos => $member) {
		if (!is_array($member) || (empty($member['name']) && empty($member['title']))) continue;
		$stmt->execute([$page_id, $user_id, $member['photo'] ?? null, $member['name'] ?: 'Team Member', $member['title'] ?? null, $member['phone'] ?? null, $member['email'] ?? null, $member['linkedin'] ?? null, $pos]);
	}
}

function xinng_validate_page_slug(PDO $pdo, string $raw, ?int $current_page_id = null): array {
	xinng_ensure_page_builder_tables($pdo);
	xinng_ensure_short_link_tables($pdo);
	xinng_ensure_qr_code_tables($pdo);

	$slug = xinng_normalize_back_half($raw);
	if ($slug === null) return ['ok' => false, 'error' => 'Slug is required.'];
	if (strlen($slug) < 3) return ['ok' => false, 'error' => 'Slug must be at least 3 characters.'];
	if (strlen($slug) > 64) return ['ok' => false, 'error' => 'Slug must be 64 characters or fewer.'];
	if (in_array($slug, xinng_reserved_back_halves(), true)) return ['ok' => false, 'error' => 'This slug is reserved.'];

	$params = [$slug];
	$sql = 'SELECT COUNT(*) FROM pages WHERE slug = ? AND deleted_at IS NULL';
	if ($current_page_id) {
		$sql .= ' AND id != ?';
		$params[] = $current_page_id;
	}
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	if ((int)$stmt->fetchColumn() > 0) return ['ok' => false, 'error' => 'This slug is already taken.'];

	xinng_ensure_short_link_tables($pdo);
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM short_links WHERE back_half = ? AND deleted_at IS NULL');
	$stmt->execute([$slug]);
	if ((int)$stmt->fetchColumn() > 0) return ['ok' => false, 'error' => 'This slug conflicts with a short link.'];

	xinng_ensure_qr_code_tables($pdo);
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM qr_codes WHERE back_half = ? AND deleted_at IS NULL');
	$stmt->execute([$slug]);
	if ((int)$stmt->fetchColumn() > 0) return ['ok' => false, 'error' => 'This slug conflicts with a QR code.'];

	return ['ok' => true, 'slug' => $slug];
}
?>
