<?php
session_start();
require 'config.php';

$resetPasswordMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if($new_password === $confirm_new_password) {
        $new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE sales_agents SET password_hash = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE companies SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $resetPasswordMessage = "Password reset successfully.";
            session_unset();
            session_destroy();
            header("Location: /EXTENSION_NAME");
            exit();
        } else {
            $resetPasswordMessage = "An unknown error was encountered.";
        }
        $stmt->close();
    } else {
        $resetPasswordMessage = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="password-reset-styles.css">
</head>
<body>
    <h1>Enter Your New Password</h1>
    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_new_password" placeholder="Re-enter New Password" required>
        <button type="submit">Reset Password</button>
        <div><?php echo isset($resetPasswordMessage) ? $resetPasswordMessage : ''; ?></div>
    </form>
</body>
</html>
