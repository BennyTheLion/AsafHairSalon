<?php
$token = $_GET['token'] ?? '';
?>

<form method="POST" action="update-password.php">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <input type="password" name="password" placeholder="New password" required>
    <button type="submit">Update Password</button>
</form>