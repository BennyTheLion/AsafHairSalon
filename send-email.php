<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/email-helpers.php';

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

// ================== SEND EMAILS (shared helper) ==================
$result = sendAppointmentEmails($action, $data);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Emails sent successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
