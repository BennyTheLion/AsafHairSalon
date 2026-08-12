<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include your database configuration
require_once 'config.php';

try {
    // Get database connection using your function
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Fetch only active services with category info - Using correct column name 'name_he'
    $query = "
        SELECT 
            s.id, 
            s.title as name, 
            s.description, 
            s.short_description,
            s.duration, 
            s.base_price as price,
            COALESCE(s.materials_fee, 0) as materials_fee,
            s.popular,
            s.featured,
            s.requires_consultation,
            s.max_clients_per_slot,
            s.is_active,
            s.category_id,
            c.name_he as category_name,
            c.icon,
            c.color,
            c.sort_order
        FROM services s
        LEFT JOIN categories c ON s.category_id = c.id
        WHERE s.is_active = 1 
        ORDER BY c.sort_order, s.category_id, s.id
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    // Fetch categories for filtering - Using 'name_he' as the name
    $categoriesQuery = "
        SELECT 
            id, 
            name_he as name,
            icon,
            color,
            sort_order
        FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order, id
    ";
    $categoriesResult = $conn->query($categoriesQuery);
    
    $categories = [];
    if ($categoriesResult) {
        while ($row = $categoriesResult->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    // Close connection
    closeDbConnection();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'services' => $services,
        'categories' => $categories,
        'total_services' => count($services),
        'total_categories' => count($categories)
    ]);
    
} catch(Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>