<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get date parameter (optional)
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    
    $query = "SELECT id, service_id, service_name, service_duration, service_price, 
                     customer_name, customer_phone, customer_email,
                     appointment_date as date, start_time as startTime, end_time as endTime, 
                     status, notes, created_at
              FROM appointments 
              WHERE status != 'cancelled'";
    
    if ($date) {
        $query .= " AND appointment_date = '$date'";
    }
    
    $query .= " ORDER BY appointment_date, start_time";
    
    $result = $conn->query($query);
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    closeDbConnection();
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>