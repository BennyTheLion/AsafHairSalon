<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
if (!function_exists('clientLog')) { function clientLog($a,$d=''){} }

try {

    $conn = getDbConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception("No input data");
    }

    // =========================
    // Extract data safely
    // =========================
    $date = $data['date'];
    $startTime = $data['startTime'];

    $service = $data['service'];
    $customer = $data['customer'];

    $durationMinutes = intval($service['duration']);

    // =========================
    // Calculate end time
    // =========================
    $startTimestamp = strtotime("$date $startTime");
    $endTimestamp = $startTimestamp + ($durationMinutes * 60);

    $endTime = date("H:i:s", $endTimestamp);

    // =========================
    // Check overlapping appointments
    // =========================
    $stmt = $conn->prepare("
        SELECT start_time, end_time 
        FROM appointments 
        WHERE appointment_date = ? 
        AND status = 'confirmed'
    ");

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $existingStart = strtotime("$date {$row['start_time']}");
        $existingEnd   = strtotime("$date {$row['end_time']}");

        // OVERLAP RULE
        if ($startTimestamp < $existingEnd && $endTimestamp > $existingStart) {
            throw new Exception("This time slot is already booked");
        }
    }

    // =========================
    // Insert appointment safely
    // =========================
    $insert = $conn->prepare("
        INSERT INTO appointments 
        (service_id, service_name, service_duration, service_price,
         customer_name, customer_phone, customer_email,
         appointment_date, start_time, end_time, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
    ");

    $insert->bind_param(
        "isidssssss",
        $service['id'],
        $service['name'],
        $service['duration'],
        $service['price'],
        $customer['name'],
        $customer['phone'],
        $customer['email'],
        $date,
        $startTime,
        $endTime
    );

    if (!$insert->execute()) {
        throw new Exception("Insert failed: " . $conn->error);
    }

    $appointmentId = $conn->insert_id;

    // Logging is best-effort: clientLog() never throws (config.php catches
    // failures and creates the admin_logs table if missing), so a log problem
    // can never turn a successful booking into an error response.
    clientLog('Booked appointment', $customer['name'] . ' - ' . $service['name'] . ' on ' . $date . ' at ' . $startTime);

    echo json_encode([
        "success" => true,
        "message" => "Appointment booked successfully",
        "appointment_id" => $appointmentId,
        "end_time" => $endTime
    ]);

} catch(Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}