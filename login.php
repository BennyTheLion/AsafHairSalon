<?php
session_start();
require_once 'config.php';

$error = '';

// If user already logged in, redirect to admin panel
if (isset($_SESSION['user_id'])) {
    header("Location: admin-panel.php");
    exit;
}

// Check "remember me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    header("Location: admin-panel.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    $conn = getDbConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT id, password_hash, full_name, role FROM users WHERE username=? AND is_active=1 LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $password_hash, $full_name, $role);
            $stmt->fetch();

            if (password_verify($password, $password_hash)) {
                // Successful login
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['role'] = $role;

                // Remember me cookie for 30 days
                if ($remember) {
                    setcookie("user_id", $id, time() + 30*24*60*60, "/");
                }

                header("Location: admin-panel.php");
                exit;
            } else {
                $error = "שם המשתמש או הסיסמה אינם נכונים";
            }
        } else {
            $error = "שם המשתמש או הסיסמה אינם נכונים";
        }

        $stmt->close();
        closeDbConnection();
    } else {
        $error = "שגיאת חיבור למסד הנתונים";
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
<meta charset="UTF-8">
<title>Login - Admin Panel</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.login-container {
    background: white;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 0 15px #ccc;
    width: 350px;
}

.login-container h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #444;
}

.login-container input[type="text"],
.login-container input[type="password"] {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

.login-container button.submit-btn {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    border: none;
    border-radius: 5px;
    background: #c8a97e;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.login-container button.submit-btn:hover {
    background: #b89266;
}

.password-container {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 8px 0;
}

.password-container input[type="password"] {
    flex: 1;
}

.password-container button.toggle-btn {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    background: #ccc;
    cursor: pointer;
}

.password-container button.toggle-btn:hover {
    background: #bbb;
}

.login-container .checkbox-container {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 8px 0;
}

.error {
    color: red;
    margin-bottom: 10px;
    text-align: center;
}
</style>
</head>
<body>

<div class="login-container">
    <h2>התחבר למערכת</h2>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="שם משתמש" required>

        <div class="password-container">
            <input type="password" id="password" name="password" placeholder="סיסמה" required>
            <button type="button" class="toggle-btn" onclick="togglePassword()">👁️</button>
        </div>

        <div class="checkbox-container">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">להישאר מחובר</label>
        </div>

        <button type="submit" class="submit-btn">התחבר</button>

        <div style="text-align:center; margin-top:10px;">
            <a href="forgot-password.php"
               style="color:#c8a97e; text-decoration:none; font-size:14px;">
                שכחתי סיסמה
            </a>
        </div>
    </form>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById('password');
    pass.type = pass.type === "password" ? "text" : "password";
}
</script>

</body>
</html>
