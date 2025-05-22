<?php
require '../config/db.php';
require '../mail/MailSender.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate 6-digit numeric reset code
        $code = rand(100000, 999999);

        // Save reset code in DB for that user
        $stmt = $conn->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        if ($stmt->execute([$code, $email])) {
            // Prepare email body with reset code
            $body = "
                Hi {$user['name']},<br><br>
                Your password reset code is: <strong>$code</strong><br><br>
                Enter this code on the reset page to reset your password.<br><br>
                If you did not request a password reset, please ignore this email.
            ";

            // Send email
            if (sendEmail($email, "Password Reset Code", $body)) {
                // Redirect to reset-password.php with email as GET parameter
                header("Location: reset-password.php?email=" . urlencode($email));
                exit();
            } else {
                $message = "Failed to send reset email. Please try again.";
            }
        } else {
            $message = "Failed to set reset code. Try again later.";
        }
    } else {
        // Generic message to prevent email enumeration
        $message = "If the email exists in our system, you will receive a reset code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Alon at Araw</title>
</head>
<body>
     <h2>Forgot Password</h2>

    <?php if (!empty($message)) : ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">Enter your email address:</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <button type="submit">Send Reset Code</button>
    </form>

    <p><a href="login.php">Back to Login</a></p>
</body>
</html>
