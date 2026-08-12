<?php
require_once 'config.php';

$conn = getDbConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validate token
    $stmt = $conn->prepare("
        SELECT email FROM password_resets 
        WHERE token = ? AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Invalid or expired token.";
        exit;
    }

    $row = $result->fetch_assoc();
    $email = $row['email'];

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();

    // Delete token after use
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    echo "Password updated successfully.";
}
?>