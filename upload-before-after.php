<?php
session_start();
require_once "config.php";
if (!function_exists('logAction')) { function logAction($a,$d=''){} }

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$conn = getDbConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ---------- ADD NEW ----------
if ($action === 'add') {
    $title_he   = $conn->real_escape_string($_POST['title_he'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);

    $before_path = '';
    $after_path  = '';

    if (!empty($_FILES['before_image']['name'])) {
        $before_path = uploadImage($_FILES['before_image'], 'before');
    }
    if (!empty($_FILES['after_image']['name'])) {
        $after_path = uploadImage($_FILES['after_image'], 'after');
    }

    if (!$before_path || !$after_path) {
        http_response_code(400);
        echo json_encode(['error' => 'Both before and after images are required']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO before_after (title_he, before_image, after_image, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title_he, $before_path, $after_path, $sort_order);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    logAction('Added before/after', $title_he);
    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'נוסף בהצלחה']);
    exit();
}

// ---------- DELETE ----------
if ($action === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Get file paths to delete
    $res = $conn->query("SELECT before_image, after_image FROM before_after WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        deleteFile($row['before_image']);
        deleteFile($row['after_image']);
    }

    $conn->query("DELETE FROM before_after WHERE id = $id");
    echo json_encode(['success' => true, 'message' => 'נמחק בהצלחה']);
    exit();
}

// ---------- UPDATE (inline) ----------
if ($action === 'update' && isset($_POST['id'])) {
    $id     = intval($_POST['id']);
    $column = $conn->real_escape_string($_POST['column'] ?? '');
    $value  = $conn->real_escape_string($_POST['value'] ?? '');

    $allowed = ['title_he', 'sort_order', 'is_active'];
    if (!in_array($column, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid column']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE before_after SET $column = ? WHERE id = ?");
    $stmt->bind_param("si", $value, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit();
}

// ---------- UPDATE IMAGE ----------
if ($action === 'update_image' && isset($_POST['id']) && isset($_POST['image_type'])) {
    $id   = intval($_POST['id']);
    $type = $_POST['image_type']; // 'before' or 'after'

    if (!in_array($type, ['before', 'after'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image type']);
        exit();
    }

    $column = $type . '_image';
    $fileKey = $type . '_image';

    if (empty($_FILES[$fileKey]['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No image uploaded']);
        exit();
    }

    // Get old file path
    $res = $conn->query("SELECT $column FROM before_after WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        deleteFile($row[$column]);
    }

    $newPath = uploadImage($_FILES[$fileKey], $type);

    $stmt = $conn->prepare("UPDATE before_after SET $column = ? WHERE id = ?");
    $stmt->bind_param("si", $newPath, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'path' => $newPath]);
    exit();
}

// ---------- LIST (AJAX fetch) ----------
if ($action === 'list') {
    $result = $conn->query("SELECT * FROM before_after ORDER BY sort_order ASC, id DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);

// ============ HELPER FUNCTIONS ============

function uploadImage($file, $prefix) {
    $uploadDir = __DIR__ . '/uploads/before-after/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) {
        return false;
    }

    // Limit to 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        return false;
    }

    $newName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/before-after/' . $newName;
    }
    return false;
}

function deleteFile($relativePath) {
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
}
