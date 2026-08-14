<?php

// ============================================================
// Local secrets override — loaded FIRST so it can supply any
// secret below. This file is gitignored and never committed.
// On Hostinger, create config.local.php manually.
// ============================================================
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

define('APP_URL', 'https://steelblue-seahorse-742958.hostingersite.com/');

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'refael-401@nadlanisteam.co.il');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'no-reply@yourdomain.com');
define('SMTP_FROM_NAME', 'מספרת אסף בן-נעים');

// Owner inbox — receives booking/cancel/reschedule/lead notifications.
// Not a secret; can be overridden in config.local.php if needed.
if (!defined('OWNER_EMAIL')) define('OWNER_EMAIL', 'noreply@landingflow.co.il');

// Database configuration for Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'u880968607_AsafHairSalon');
define('DB_USER', 'u880968607_Asaf');
if (!defined('DB_PASS')) define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Google reCAPTCHA v2 keys
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

function getDbConnection() {
    global $conn;
    if (isset($conn) && is_object($conn)) {
        try {
            if ($conn->ping()) return $conn;
        } catch (Throwable $e) {
            // Stale/closed handle — fall through and reconnect.
        }
    }
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
    if (isset($GLOBALS['conn']) && is_object($GLOBALS['conn'])) {
        try { $GLOBALS['conn']->close(); } catch (Throwable $e) {}
        unset($GLOBALS['conn']);
    }
}

// Creates the admin_logs table if it does not exist yet (self-healing).
// Runs at most once per request; never throws.
function ensureAdminLogsTable($conn) {
    static $attempted = false;
    if ($attempted) return true;
    $attempted = true;
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) DEFAULT NULL,
            username VARCHAR(100) DEFAULT NULL,
            action VARCHAR(255) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// Adds the cancel_token column to appointments if missing (self-healing).
// Used for customer cancel/reschedule links in emails. Never throws.
function ensureAppointmentCancelToken($conn) {
    static $attempted = false;
    if ($attempted) return true;
    $attempted = true;
    try {
        $r = $conn->query("SHOW COLUMNS FROM appointments LIKE 'cancel_token'");
        if ($r && $r->num_rows === 0) {
            $conn->query("ALTER TABLE appointments ADD COLUMN cancel_token VARCHAR(64) DEFAULT NULL");
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// Logging must never break the main flow (booking, forms, admin actions).
function logAction($action, $details = '') {
    if (!isset($_SESSION['user_id'])) return;
    $conn = getDbConnection();
    if (!$conn) return;
    try {
        ensureAdminLogsTable($conn);
        $uid = intval($_SESSION['user_id']);
        $user = $conn->real_escape_string($_SESSION['full_name'] ?? 'Unknown');
        $act = $conn->real_escape_string($action);
        $det = $conn->real_escape_string($details);
        $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        $conn->query("INSERT INTO admin_logs (user_id, username, action, details, ip) VALUES ($uid, '$user', '$act', '$det', '$ip')");
    } catch (Throwable $e) {
        error_log("admin_logs write failed: " . $e->getMessage());
    }
    closeDbConnection();
}

function clientLog($action, $details = '') {
    $conn = getDbConnection();
    if (!$conn) return;
    try {
        ensureAdminLogsTable($conn);
        $act = $conn->real_escape_string($action);
        $det = $conn->real_escape_string($details);
        $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        $conn->query("INSERT INTO admin_logs (user_id, username, action, details, ip) VALUES (0, 'Client', '$act', '$det', '$ip')");
    } catch (Throwable $e) {
        error_log("admin_logs write failed: " . $e->getMessage());
    }
    closeDbConnection();
}
?>
