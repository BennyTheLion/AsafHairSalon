<?php
require_once 'config.php';

$conn = getDbConnection();

$date = $_GET['date'];
$duration = intval($_GET['duration']);

$startHour = 9;
$endHour = 18;
$slotStep = 30;

// get active appointments
$stmt = $conn->prepare("
    SELECT start_time, end_time 
    FROM appointments 
    WHERE appointment_date = ? 
    AND status = 'confirmed'
");

$stmt->bind_param("s", $date);
$stmt->execute();

$result = $stmt->get_result();

$booked = [];

while ($row = $result->fetch_assoc()) {
    $booked[] = [
        'start' => strtotime($row['start_time']),
        'end' => strtotime($row['end_time'])
    ];
}

$slots = [];

for ($h = $startHour; $h < $endHour; $h++) {
    for ($m = 0; $m < 60; $m += $slotStep) {

        $start = strtotime(sprintf("%02d:%02d", $h, $m));
        $end = $start + ($duration * 60);

        if ($end > strtotime("$endHour:00")) continue;

        $isAvailable = true;

        foreach ($booked as $b) {

            // ❗ overlap check
            if ($start < $b['end'] && $end > $b['start']) {
                $isAvailable = false;
                break;
            }
        }

        if ($isAvailable) {
            $slots[] = date("H:i", $start);
        }
    }
}

echo json_encode(["slots" => $slots]);