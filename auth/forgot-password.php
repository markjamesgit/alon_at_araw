<?php
require '../config/db.php';
require '../mail/MailSender.php';

$message = "";
$emailError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $code = rand(100000, 999999);

        $stmt = $conn->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        if ($stmt->execute([$code, $email])) {
            $body = "
                Hi {$user['name']},<br><br>
                Your password reset code is: <strong>$code</strong><br><br>
                Enter this code on the reset page to reset your password.<br><br>
                If you did not request a password reset, please ignore this email.
            ";

            if (sendEmail($email, "Password Reset Code", $body)) {
                header("Location: reset-password.php?email=" . urlencode($email));
                exit();
            } else {
                $message = "Failed to send reset email. Please try again.";
                $emailError = true;
            }
        } else {
            $message = "Failed to set reset code. Try again later.";
            $emailError = true;
        }
    } else {
        // Generic message to prevent email enumeration
        $message = "If the email exists in our system, you will receive a reset code.";
        $emailError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Alon at Araw</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/styles/forgot-password.css">
    <link rel="stylesheet" href="../assets/global.css">
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png" />
</head>
<body>
    <div class="login-container">
        <h2>Forgot Password</h2>

        <form method="POST" action="">
            <div class="input-container">
                <label for="email">Enter your email address</label>
                <input type="email" name="email" id="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="<?= $emailError ? 'error' : '' ?>">
                <?php if ($emailError): ?>
                    <div class="error-text"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit">Send Reset Code</button>
        </form>

        <div class="links">
            <p>
                Back to
                <a href="login.php">Login</a>
            </p>
        </div>
    </div>
</body>
</html>
