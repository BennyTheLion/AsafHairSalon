<?php
// Shared email helpers: PHPMailer setup, RTL templates, appointment emails,
// Telegram notifications. Used by send-email.php, manage-appointment.php and
// admin-panel.php. Never throws to callers — mail failures return error strings.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

// Load config constants if not already defined (config.php is optional here so
// this file stays usable in contexts that define their own settings first).
if (!defined('APP_URL') && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('APP_URL')) define('APP_URL', 'https://steelblue-seahorse-742958.hostingersite.com/');
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.hostinger.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'refael-401@nadlanisteam.co.il');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'REVOKED_SMTP_PASSWORD');
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'מספרת אסף בן-נעים');

function getOwnerEmail() {
    global $OWNER_EMAIL;
    return $OWNER_EMAIL ?? 'maimonov@gmail.com';
}

// RTL Hebrew email template wrapper
function emailTemplate($title, $content) {
    return "
    <div style='font-family:Arial;direction:rtl;text-align:right;background:#fafafa;padding:20px'>
        <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>
            <h2 style='color:#333'>{$title}</h2>
            <div style='font-size:15px;color:#444;line-height:1.6'>
                {$content}
            </div>
            <hr>
            <p style='font-size:12px;color:#999'>מספרת אסף בן נעים · <a href='" . APP_URL . "'>האתר שלנו</a></p>
        </div>
    </div>";
}

// Buttons row for cancel/reschedule links
function manageButtons($manageUrl) {
    return "
    <div style='margin:20px 0;text-align:center'>
        <a href='" . htmlspecialchars($manageUrl) . "' style='display:inline-block;background:#c8a97e;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin:5px'>📅 שינוי מועד התור</a>
        <a href='" . htmlspecialchars($manageUrl) . "&cancel=1' style='display:inline-block;background:#e74c3c;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin:5px'>✖ ביטול התור</a>
    </div>";
}

function sendMail($to, $subject, $body) {
    // Test hook: local/CI runs define MAIL_TEST_MODE to skip real SMTP sends.
    if (defined('MAIL_TEST_MODE') && MAIL_TEST_MODE) {
        error_log("MAIL TEST MODE (skipped send to $to): $subject");
        return true;
    }
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $e->getMessage());
        return $e->getMessage();
    }
}

// Send a Telegram notification (e.g., when emails fail or bookings happen)
function notifyTelegram($text) {
    $botToken = "REVOKED_TELEGRAM_BOT_TOKEN";
    $chatId   = "2116909126";
    $url  = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => mb_substr($text, 0, 4000)];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result  = @file_get_contents($url, false, $context);
    return $result !== false;
}

// Builds a manage-appointment.php link for an appointment id + token
function manageLink($id, $token) {
    return APP_URL . 'manage-appointment.php?id=' . intval($id) . '&token=' . rawurlencode($token);
}

// Send the owner + customer emails for an appointment action.
// Returns ['success' => bool, 'message' => string]
function sendAppointmentEmails($action, $data) {
    $customer = $data['customer'] ?? [];
    $name     = htmlspecialchars($customer['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $email    = trim($customer['email'] ?? '');

    $service = $data['service'] ?? [];
    $serviceText = '';
    if (!empty($service)) {
        $serviceText = "שירות: " . htmlspecialchars($service['name'] ?? '', ENT_QUOTES, 'UTF-8')
                     . " (" . intval($service['duration'] ?? 0) . " דקות) - מחיר: "
                     . htmlspecialchars($service['price'] ?? '', ENT_QUOTES, 'UTF-8') . "₪";
    }

    $date      = $data['date'] ?? '';
    $startTime = $data['startTime'] ?? '';
    $endTime   = $data['endTime'] ?? '';
    $status    = $data['status'] ?? 'confirmed';
    $apptId    = intval($data['appointmentId'] ?? $data['id'] ?? 0);
    $token     = $data['cancelToken'] ?? $data['cancel_token'] ?? '';

    $manageUrl = ($apptId && $token) ? manageLink($apptId, $token) : '';
    $ownerEmail = getOwnerEmail();

    switch ($action) {
        case 'booking_confirmation':
            $ownerSubject = "תור חדש - {$name}";
            $ownerBody = emailTemplate("תור חדש הוזמן", "
                <p><strong>שם:</strong> {$name}</p>
                <p><strong>טלפון:</strong> {$phone}</p>
                <p><strong>{$serviceText}</strong></p>
                <p><strong>תאריך:</strong> {$date}</p>
                <p><strong>שעה:</strong> {$startTime} - {$endTime}</p>
                <p><strong>סטטוס:</strong> {$status}</p>
            ");
            $customerSubject = "תור נקבע בהצלחה - אסף מספרה";
            $customerBody = emailTemplate("תודה על ההזמנה!", "
                <p>שלום {$name},</p>
                <p>התור שלך נקבע בהצלחה:</p>
                <p><strong>{$serviceText}</strong></p>
                <p><strong>תאריך:</strong> {$date}</p>
                <p><strong>שעה:</strong> {$startTime} - {$endTime}</p>
                <p>נשמח לראותך 🙏</p>
                <p style='font-size:13px;color:#888'>לא יכול/ה להגיע? ניתן לשנות את המועד או לבטל את התור בלחיצה:</p>
                " . ($manageUrl ? manageButtons($manageUrl) : ''));
            break;

        case 'cancel':
            $ownerSubject = "תור בוטל - {$name}";
            $ownerBody = emailTemplate("תור בוטל", "
                <p><strong>שם:</strong> {$name}</p>
                <p><strong>טלפון:</strong> {$phone}</p>
                <p><strong>{$serviceText}</strong></p>
                <p><strong>תאריך:</strong> {$date}</p>
                <p><strong>שעה:</strong> {$startTime} - {$endTime}</p>
            ");
            $customerSubject = "התור שלך בוטל";
            $customerBody = emailTemplate("ביטול תור", "
                <p>שלום {$name},</p>
                <p>התור שלך בוטל בהצלחה.</p>
                <p>נשמח לראותך בפעם אחרת! ניתן לקבוע תור חדש באתר שלנו.</p>
            ");
            break;

        case 'reschedule':
            $ownerSubject = "תור שונה - {$name}";
            $ownerBody = emailTemplate("תור שונה למועד חדש", "
                <p><strong>שם:</strong> {$name}</p>
                <p><strong>טלפון:</strong> {$phone}</p>
                <p><strong>{$serviceText}</strong></p>
                <p><strong>תאריך חדש:</strong> {$date}</p>
                <p><strong>שעה חדשה:</strong> {$startTime} - {$endTime}</p>
            ");
            $customerSubject = "התור שלך שונה בהצלחה";
            $customerBody = emailTemplate("שינוי מועד תור", "
                <p>שלום {$name},</p>
                <p>התור שלך שונה למועד חדש:</p>
                <p><strong>{$serviceText}</strong></p>
                <p><strong>תאריך:</strong> {$date}</p>
                <p><strong>שעה:</strong> {$startTime} - {$endTime}</p>
                <p>נתראה! 🙏</p>
                " . ($manageUrl ? manageButtons($manageUrl) : ''));
            break;

        case 'delete':
            $ownerSubject = "תור נמחק - {$name}";
            $ownerBody = emailTemplate("תור נמחק", "
                <p><strong>שם:</strong> {$name}</p>
                <p><strong>טלפון:</strong> {$phone}</p>
                <p><strong>תאריך:</strong> {$date}</p>
                <p><strong>שעה:</strong> {$startTime} - {$endTime}</p>
            ");
            $customerSubject = "התור שלך נמחק";
            $customerBody = emailTemplate("מחיקת תור", "
                <p>התור שלך נמחק מהמערכת.</p>
            ");
            break;

        default:
            return ['success' => false, 'message' => 'Invalid action'];
    }

    $ownerResult = sendMail($ownerEmail, $ownerSubject, $ownerBody);

    $customerResult = true;
    if (!empty($email)) {
        $customerResult = sendMail($email, $customerSubject, $customerBody);
    }

    if ($ownerResult === true && $customerResult === true) {
        return ['success' => true, 'message' => 'Emails sent successfully'];
    }

    $errors = [];
    if ($ownerResult !== true) $errors[] = "Owner: $ownerResult";
    if ($customerResult !== true) $errors[] = "Customer: $customerResult";
    $msg = implode(' | ', $errors);

    // Make failures visible: Telegram ping so the salon knows emails failed.
    notifyTelegram("⚠️ שליחת אימייל נכשלה ({$action} ל-{$name}): {$msg}");

    return ['success' => false, 'message' => $msg];
}
