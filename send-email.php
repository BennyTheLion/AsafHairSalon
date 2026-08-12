<?php
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

// ================== CORS ==================
$allowed_origins = [
    'https://steelblue-seahorse-742958.hostingersite.com',
    'https://nadlanisteam.co.il',
    'http://localhost',
    'http://127.0.0.1'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin) && in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// ================== VALIDATION ==================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

// ================== INPUT ==================
$action = $_POST['action'] ?? '';
$json   = $_POST['data'] ?? '';

if (!$action || !$json) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// ================== SAFE DATA EXTRACTION ==================
$customer = $data['customer'] ?? [];

$name  = htmlspecialchars($customer['name'] ?? '', ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$email = trim($customer['email'] ?? '');

$service = $data['service'] ?? [];

$serviceText = '';
if (!empty($service)) {
    $serviceText = "שירות: {$service['name']} ({$service['duration']} דקות) - מחיר: {$service['price']}₪";
}

$date      = $data['date'] ?? '';
$startTime = $data['startTime'] ?? '';
$endTime   = $data['endTime'] ?? '';
$status    = $data['status'] ?? 'confirmed';

// ================== SMTP CONFIG ==================
$ownerEmail = 'maimonov@gmail.com';
$smtpUser   = 'refael-401@nadlanisteam.co.il';
$smtpPass   = 'REVOKED_SMTP_PASSWORD';

// ================== EMAIL TEMPLATE ==================
function emailTemplate($title, $content) {
    return "
    <div style='font-family:Arial;direction:rtl;text-align:right;background:#fafafa;padding:20px'>
        <div style='max-width:600px;margin:auto;background:white;padding:20px;border-radius:10px'>
            <h2 style='color:#333'>{$title}</h2>
            <div style='font-size:15px;color:#444;line-height:1.6'>
                {$content}
            </div>
            <hr>
            <p style='font-size:12px;color:#999'>מספרת אסף בן נעים</p>
        </div>
    </div>";
}

// ================== MAIL FUNCTION ==================
function sendMail($to, $subject, $body) {

    global $smtpUser, $smtpPass;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($smtpUser, 'מספרת אסף בן נעים');
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

// ================== ACTIONS ==================
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
        ");

        break;

    case 'cancel':

        $ownerSubject = "תור בוטל - {$name}";
        $ownerBody = emailTemplate("תור בוטל", "
            <p><strong>שם:</strong> {$name}</p>
            <p><strong>{$serviceText}</strong></p>
            <p><strong>תאריך:</strong> {$date}</p>
            <p><strong>שעה:</strong> {$startTime}</p>
        ");

        $customerSubject = "התור שלך בוטל";
        $customerBody = emailTemplate("ביטול תור", "
            <p>שלום {$name},</p>
            <p>התור שלך בוטל.</p>
        ");

        break;

    case 'delete':

        $ownerSubject = "תור נמחק - {$name}";
        $ownerBody = emailTemplate("תור נמחק", "
            <p><strong>שם:</strong> {$name}</p>
            <p><strong>טלפון:</strong> {$phone}</p>
            <p><strong>תאריך:</strong> {$date}</p>
        ");

        $customerSubject = "התור שלך נמחק";
        $customerBody = emailTemplate("מחיקת תור", "
            <p>התור שלך נמחק מהמערכת.</p>
        ");

        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// ================== SEND EMAILS ==================
$ownerResult = sendMail($ownerEmail, $ownerSubject, $ownerBody);

$customerResult = true;
if (!empty($email)) {
    $customerResult = sendMail($email, $customerSubject, $customerBody);
}

// ================== RESPONSE ==================
if ($ownerResult === true && $customerResult === true) {

    echo json_encode([
        'success' => true,
        'message' => 'Emails sent successfully'
    ]);

} else {

    http_response_code(500);

    $errors = [];

    if ($ownerResult !== true) {
        $errors[] = "Owner: $ownerResult";
    }

    if ($customerResult !== true) {
        $errors[] = "Customer: $customerResult";
    }

    echo json_encode([
        'success' => false,
        'message' => implode(' | ', $errors)
    ]);
}
?>