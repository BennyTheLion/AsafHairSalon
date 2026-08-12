<?php

define('APP_URL', 'https://steelblue-seahorse-742958.hostingersite.com/');

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'refael-401@nadlanisteam.co.il');
define('SMTP_PASSWORD', 'REVOKED_SMTP_PASSWORD');
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl
define('SMTP_FROM_EMAIL', 'no-reply@yourdomain.com');
define('SMTP_FROM_NAME', 'מספרת אסף בן-נעים');


// Database configuration for Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'AsafHairSalon');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Google reCAPTCHA v2 keys — get yours at https://www.google.com/recaptcha/admin
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');   // TODO: replace with your real site key
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // TODO: replace with your real secret key
// (current keys are Google's test keys — always pass verification)

// פונקציה לחיבור עם mysqli
function getDbConnection() {
    global $conn;
    
    if (isset($conn) && $conn->ping()) {
        return $conn;
    }
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // בדיקת חיבור
    if ($conn->connect_error) {
        // נחזיר שגיאה בלי לעצור את התוכנית
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    // הגדרת קידוד
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

// פונקציה לסגירת חיבור
function closeDbConnection() {
    global $conn;
    if (isset($conn)) {
        $conn->close();
        unset($conn);
    }
}
?>