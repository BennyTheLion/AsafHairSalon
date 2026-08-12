<?php

define('APP_URL', 'https://steelblue-seahorse-742958.hostingersite.com/');

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'refael-401@nadlanisteam.co.il');
define('SMTP_PASSWORD', 'REVOKED_SMTP_PASSWORD');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'no-reply@yourdomain.com');
define('SMTP_FROM_NAME', 'מספרת אסף בן-נעים');

// Database configuration for Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'u880968607_AsafHairSalon');
define('DB_USER', 'u880968607_Asaf');
define('DB_PASS', 'REVOKED_DB_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Google reCAPTCHA v2 keys
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

function getDbConnection() {
    global $conn;
    if (isset($conn) && is_object($conn) && $conn->ping()) return $conn;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        error_log("Database connection failed");
        return false;
    }
    if ($conn->connect_error) { error_log("Database connection failed"); return false; }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

function closeDbConnection() {
    global $conn;
    if (isset($conn)) { $conn->close(); unset($conn); }
}

function logAction($action, $details = '') {
    if (!isset($_SESSION['user_id'])) return;
    $conn = getDbConnection();
    if (!$conn) return;
    $uid = intval($_SESSION['user_id']);
    $user = $conn->real_escape_string($_SESSION['full_name'] ?? 'Unknown');
    $act = $conn->real_escape_string($action);
    $det = $conn->real_escape_string($details);
    $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
    $conn->query("INSERT INTO admin_logs (user_id, username, action, details, ip) VALUES ($uid, '$user', '$act', '$det', '$ip')");
    closeDbConnection();
}
?>
