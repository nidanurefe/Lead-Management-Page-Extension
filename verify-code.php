<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];

    if ($code == $_SESSION['verification_code']) {
        $_SESSION['code_verified'] = true;
        header("Location: reset-password.php");
        exit();
    } else {
        $verificationMessage = "Wrong verification code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Code Verification</title>
    <link rel="stylesheet" href="password-reset-styles.css">
</head>
<body>
    <h1>Enter Verification Code</h1>
    <form method="POST">
        <input type="text" name="code" placeholder="Verification Code" required>
        <button type="submit">Verify</button>
        <div><?php echo isset($verificationMessage) ? $verificationMessage : ''; ?></div>
    </form>
</body>
</html>
