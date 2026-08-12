<?php
require_once 'config.php';

// PHPMailer (manual include)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

// Show errors during development (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$message = '';
$error = '';

// Database connection
$conn = getDbConnection();
if (!$conn) {
    die('Database connection failed.');
}

/**
 * Send password reset email using PHPMailer
 */
function sendPasswordResetEmail(string $email, string $token): bool
{
    // Build base URL
    if (defined('APP_URL')) {
        $baseUrl = rtrim(APP_URL, '/');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    }

    // Create reset link
    $resetLink = $baseUrl . '/reset-password.php?token=' . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Encryption
        if (defined('SMTP_ENCRYPTION') && strtolower(SMTP_ENCRYPTION) === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Sender and recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'איפוס סיסמה';

        $mail->Body = '
        <div style="font-family:Arial,sans-serif; direction:rtl; text-align:right;">
            <h2>איפוס סיסמה</h2>
            <p>התקבלה בקשה לאיפוס הסיסמה שלך.</p>
            <p>
                <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '"
                   style="display:inline-block;padding:12px 24px;
                          background:#c8a97e;color:#ffffff;
                          text-decoration:none;border-radius:6px;">
                    לחץ כאן לאיפוס הסיסמה
                </a>
            </p>
            <p>או העתק את הקישור הבא לדפדפן:</p>
            <p>' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</p>
            <p>הקישור תקף למשך שעה אחת.</p>
            <p>אם לא ביקשת לאפס את הסיסמה, ניתן להתעלם מהודעה זו.</p>
        </div>';

        $mail->AltBody =
            "התקבלה בקשה לאיפוס הסיסמה שלך.\n\n" .
            "לחץ על הקישור הבא:\n" .
            $resetLink . "\n\n" .
            "הקישור תקף למשך שעה אחת.\n\n" .
            "אם לא ביקשת לאפס את הסיסמה, ניתן להתעלם מהודעה זו.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// Show form when accessed directly
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="he">
    <head>
        <meta charset="UTF-8">
        <title>שחזור סיסמה</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .box {
                background: #fff;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 15px #ccc;
                width: 350px;
            }
            input[type="email"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                box-sizing: border-box;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            button {
                width: 100%;
                padding: 10px;
                border: none;
                border-radius: 5px;
                background: #c8a97e;
                color: #fff;
                font-weight: bold;
                cursor: pointer;
            }
            .back-link {
                display: block;
                text-align: center;
                margin-top: 15px;
                color: #c8a97e;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2 style="text-align:center;">שחזור סיסמה</h2>
            <form method="POST">
                <input type="email" name="email" placeholder="הזן כתובת אימייל" required>
                <button type="submit">שלח קישור לאיפוס</button>
            </form>
            <a class="back-link" href="login.php">חזרה להתחברות</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle form submission
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    $error = 'נא להזין כתובת אימייל.';
} else {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Do not reveal whether email exists
    if ($result->num_rows > 0) {
        // Delete old tokens
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Generate token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token
        $stmt = $conn->prepare("
            INSERT INTO password_resets (email, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        // Send email
        if (!sendPasswordResetEmail($email, $token)) {
            $error = 'אירעה שגיאה בשליחת האימייל.';
        }
    }

    if (empty($error)) {
        $message = 'אם כתובת האימייל קיימת במערכת, נשלח אליך קישור לאיפוס סיסמה.';
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <title>שחזור סיסמה</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px #ccc;
            width: 350px;
            text-align: center;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .back-link {
            display: block;
            margin-top: 15px;
            color: #c8a97e;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>שחזור סיסמה</h2>

        <?php if (!empty($message)): ?>
            <div class="success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <a class="back-link" href="login.php">חזרה להתחברות</a>
    </div>
</body>
</html>