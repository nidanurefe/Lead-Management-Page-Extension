

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '/vendor/autoload.php'; // composer
session_start();
require 'config.php';

$sendMailMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];

    // Check both tables for the email
    $stmt = $conn->prepare("SELECT * FROM sales_agents WHERE email = ? UNION SELECT * FROM companies WHERE email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate and store verification code
        $verification_code = rand(10000, 99999);
        $_SESSION['verification_code'] = $verification_code;
        $_SESSION['reset_email'] = $email;

        // Send the verification code via email
        $mail = new PHPMailer(true);
        
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'HOST_NAME';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'USERNAME';                     //SMTP username
            $mail->Password   = 'PASSWORD';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        
            //Recipients
            $mail->setFrom('FROM_MAIL', 'NAME');
            $mail->addAddress($email);     //Add a recipient
        
        
            //Content
            $mail->isHTML(true);                                
            
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = '<p>Your verification code is <b>' . $verification_code . '</b>. Please use this code to verify your email address.</p>';
            
        
            $mail->send();
            echo 'Message has been sent';
            header('Location: /verify-code.php');
        } catch (Exception $e) {
            $sendMailMessage = "An unknown error occurred while sending the e-mail";
        }
    } 
    else {
        
        $sendMailMessage = "E-mail address not found.";
    }
    
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Your Password</title>
    <link rel="stylesheet" href="password-reset-styles.css">
</head>
<body>
    <h1>Enter your e-mail address</h1>
    <form method="POST">
        <input type="text" name="email" placeholder="E-mail" required>
        <button type="submit">Send Code</button>
        <div><?php echo isset($sendMailMessage) ? $sendMailMessage : ''; ?></div>
    </form>
</body>
</html>
