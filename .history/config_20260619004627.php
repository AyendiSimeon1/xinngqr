<?php
// QR Link Manager Configuration
// Change these before uploading publicly.

$APP_NAME = "xin_ng";
$BRAND_TAGLINE = "Show your self!";

// Default admin password: changeMe123!
// Generate a new hash at: /admin.php?make_hash=YOUR_NEW_PASSWORD
$ADMIN_PASSWORD_HASH = '$2y$10$7uL.r0Qr52/V9OaMT03yXuVtCr4f1sW53orhjxiiHQxZw.7UqWpf.';

// Optional: set your final public URL here after upload, e.g. https://yourdomain.com/links/
// Leave blank to auto-detect current URL.
$PUBLIC_URL = "127.0.0.1/xinngqr/";

// QR code brand label shown under QR image.
$QR_LABEL = "xinng";

// -------------------------
// Database configuration
// Update `DB_USER` / `DB_PASS` for your environment (XAMPP default: root / empty)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'xin_ng');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

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
?>
