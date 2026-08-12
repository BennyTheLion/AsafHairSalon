<?php
session_start();
require "config.php";

$conn = getDbConnection();
if (!$conn) {
    header("Location: login.php?error=Database error");
    exit;
}

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";

// Fetch user
$stmt = $conn->prepare("SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Check if active
    if (!$user["is_active"]) {
        header("Location: login.php?error=User is disabled");
        exit;
    }

    // Verify password
    if (password_verify($password, $user["password_hash"])) {

        // Save session
        $_SESSION["user_logged_in"] = true;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        header("Location: admin-panel.php");
        exit;
    }
}

header("Location: login.php?error=Wrong username or password");
exit;
?>
