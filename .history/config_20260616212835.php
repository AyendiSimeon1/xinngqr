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
$PUBLIC_URL = "localhost/xinngqr/";

// QR code brand label shown under QR image.
$QR_LABEL = "xinng";

// -----------------------------
// Database settings (adjust for your environment)
// -----------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'xin_ng');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Return a PDO connection or null on failure.
 * Usage: $pdo = get_db_connection();
 */
function get_db_connection()
{
	static $pdo = null;
	if ($pdo instanceof PDO) {
		return $pdo;
	}

	$host = DB_HOST;
	$db = DB_NAME;
	$user = DB_USER;
	$pass = DB_PASS;
	$charset = DB_CHARSET;

	$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	try {
		$pdo = new PDO($dsn, $user, $pass, $options);
		return $pdo;
	} catch (PDOException $e) {
		error_log('DB Connection error: ' . $e->getMessage());
		return null;
	}
}

/**
 * Generate a RFC4122 v4 UUID.
 */
function generate_uuid_v4()
{
	$data = random_bytes(16);
	$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
	$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

?>
