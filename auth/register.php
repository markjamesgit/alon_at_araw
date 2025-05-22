<?php
require '../config/db.php';
require '../mail/MailSender.php';

$message = "";
$success = false;
$emailError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $passwordInput = $_POST['password'];

    $defaultAdminPassword = 'admin123';

    if (strtolower($email) === 'alonatarawcoffeeshop@gmail.com') {
        $type = 'admin';
        $password = password_hash($defaultAdminPassword, PASSWORD_DEFAULT);
    } else {
        $type = 'customer';
        $password = password_hash($passwordInput, PASSWORD_DEFAULT);
    }

    $code = rand(100000, 999999);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $message = "Email already registered.";
        $emailError = true;
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, account_type, verification_code) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $type, $code])) {
            $subject = "Verify Your Email - Alon at Araw";
            $body = "Hi $name,<br><br>Your email verification code is: <strong>$code</strong><br><br>"
                  . "Please enter this code on the verification page to activate your account.<br><br>Thank you!";
            if (sendEmail($email, $subject, $body)) {
                $success = true;
                echo "<script>
                    localStorage.setItem('registerSuccess', '1');
                    window.location.href = 'verify-code.php?email=" . urlencode($email) . "';
                </script>";
                exit();
            } else {
                $message = "Registration successful but failed to send verification email.";
            }
        } else {
            $message = "Error during registration.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Alon at Araw</title>
    <link rel="stylesheet" href="../assets/styles/register.css" />
    <link rel="stylesheet" href="../assets/global.css" />
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png" />
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>

        <form method="POST" action="">
            <div class="input-container">
                <label for="name">Username</label>
                <input type="text" name="name" id="name" placeholder="Juander" required />
            </div>

            <div class="input-container">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="example@gmail.com"
                    required
                    class="<?= $emailError ? 'error' : '' ?>"
                />
                <?php if ($emailError): ?>
                <div class="error-text">Email already registered.</div>
                <?php endif; ?>
            </div>

            <div class="input-container">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required />
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="links">
            <p>
                Already have an account?
                <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
