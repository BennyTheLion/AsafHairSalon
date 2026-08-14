<?php
// ================== CONFIG ==================
ini_set('display_errors', 0); // Disable direct errors for JSON
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';
if (!function_exists('clientLog')) { function clientLog($a,$d=''){} }

// CORS (optional)
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

// ================== VALIDATE REQUEST ==================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'שגיאה: רק POST מותר']);
    exit;
}

function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// POST INPUTS
$name = cleanInput($_POST['name'] ?? '');
$phone = cleanInput($_POST['phone'] ?? '');
$email = cleanInput($_POST['email'] ?? '');
$service = cleanInput($_POST['serviceSelect'] ?? '');
$date = cleanInput($_POST['date'] ?? '');
$time = cleanInput($_POST['time'] ?? '');
$service_label = $_POST['serviceLabel'] ?? '';
$service_price = $_POST['servicePrice'] ?? '';
$service_duration = $_POST['serviceDuration'] ?? '';
$message_text = cleanInput($_POST['message'] ?? 'לא צוינו הערות');

// ================== reCAPTCHA VERIFICATION ==================
require_once __DIR__ . '/config.php';
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptcha_response)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'נא לאמת שאתה לא רובוט']);
    exit;
}
$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
$verify_data = [
    'secret'   => RECAPTCHA_SECRET_KEY,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
];
$verify_options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($verify_data),
        'timeout' => 10
    ]
];
$verify_context = stream_context_create($verify_options);
$verify_result = @file_get_contents($verify_url, false, $verify_context);
$verify_json = json_decode($verify_result, true);

if (!$verify_json || empty($verify_json['success'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'אימות reCAPTCHA נכשל. נסה שוב.']);
    exit;
}

$errors = [];
if (strlen($name) < 2) $errors[] = 'נא להזין שם מלא תקין';
if (!preg_match('/^[0-9\-\+\s()]{9,15}$/', $phone)) $errors[] = 'נא להזין מספר טלפון תקין';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'נא להזין אימייל תקין';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit;
}

// ================== PREPARE DATA ==================
$service_info = $service_label . " — " . $service_price . " ₪ / " . $service_duration . " דקות";
$email_body = "
<h2>📩 פנייה חדשה מאתר העסק שלך</h2>
<p><strong>שם:</strong> {$name}</p>
<p><strong>טלפון:</strong> {$phone}</p>
<p><strong>אימייל:</strong> {$email}</p>
<p><strong>טיפול:</strong> {$service_info}</p>
<p><strong>תאריך:</strong> {$date}</p>
<p><strong>שעה:</strong> {$time}</p>
<p><strong>הודעה:</strong><br>{$message_text}</p>
<p><strong>נשלח בתאריך:</strong> " . date('d/m/Y H:i:s') . "</p>";

// ================== SEND EMAIL USING PHPMailer ==================
$response = ['success' => true, 'message' => '✅ תודה! פנייתך נשלחה בהצלחה.'];

try {
    // ---------- Lead email ----------
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'refael-401@nadlanisteam.co.il';
    $mail->Password   = 'REVOKED_SMTP_PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->setFrom('refael-401@nadlanisteam.co.il', 'מספרת אסף בן נעים');
    $mail->addAddress('maimonov@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = "פנייה חדשה מאתר של אסף בן נעים - {$name}";
    $mail->Body    = $email_body;
    $mail->send();

    // ---------- Auto-reply to user ----------
    $auto = new PHPMailer(true);
    $auto->isSMTP();
    $auto->Host       = 'smtp.hostinger.com';
    $auto->SMTPAuth   = true;
    $auto->Username   = 'refael-401@nadlanisteam.co.il';
    $auto->Password   = 'REVOKED_SMTP_PASSWORD';
    $auto->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $auto->Port       = 587;
    $auto->CharSet = 'UTF-8';
    $auto->Encoding = 'base64';
    
    $auto->setFrom('refael-401@nadlanisteam.co.il', 'מספרה אסף בן נעים');
    $auto->addAddress($email);
    $auto->isHTML(true);
    $auto->Subject = "תודה על פנייתך למספרה של אסף";
    $auto->Body = "
        <h2>שלום {$name},</h2>
        <p>תודה שפנית אלינו! נחזור אליך תוך שעה.</p>
        <p>הפרטים שהשארת:</p>
        <p>📞 {$phone}<br>📧 {$email}<br>{$service_info}</p>
        <hr>
        <p>בברכה, אסף בן-נעים</p>
    ";
    $auto->send();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = '❌ שגיאה בשליחת המייל: ' . $e->getMessage();
}

// ================== SAVE LEAD TO CSV ==================
$csvFile = __DIR__ . '/leads.csv';
if (!file_exists($csvFile)) {
    $headers = ['timestamp','name','phone','email','service','date','time','message'];
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, $headers);
    fclose($fp);
}

$cleanCsv = fn($v) => str_replace(["\r","\n"],' ', trim($v));
$line = [
    date('Y-m-d H:i:s'),
    $cleanCsv($name),
    $cleanCsv($phone),
    $cleanCsv($email),
    $cleanCsv($service_info),
    $cleanCsv($date),
    $cleanCsv($time),
    $cleanCsv($message_text)
];

if (($fp = fopen($csvFile, 'a')) !== false) {
    fputcsv($fp, $line);
    fclose($fp);
}

// ================== SEND TELEGRAM MESSAGE ==================
try {
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_CHAT_ID')) {
        error_log('Telegram notify skipped: TELEGRAM_BOT_TOKEN/TELEGRAM_CHAT_ID not defined');
    } else {
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = TELEGRAM_CHAT_ID;
    $messageText = "🆕 New lead!\nName: {$name}\nPhone: {$phone}\nEmail: {$email}\nService: {$service_info}";

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $messageText];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        $response['message'] .= "\n⚠️ Failed to send Telegram notification";
    }
    } // end TELEGRAM_BOT_TOKEN check

} catch (Exception $e) {
    $response['message'] .= "\n⚠️ Telegram Error: " . $e->getMessage();
}

// ================== RETURN JSON ==================
if ($response['success']) {
    clientLog('Contact form submitted', $name . ' - ' . $phone . ' - ' . ($service_label ?: 'no service'));
}
echo json_encode($response);
exit;
?>
