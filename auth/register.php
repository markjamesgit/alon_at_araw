<?php
require '../config/db.php';
require '../mail/MailSender.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // Default admin password 
    $defaultAdminPassword = 'admin123';

    // Determine account type and password
    if (strtolower($email) === 'alonatarawcoffeeshop@gmail.com') {
        $type = 'admin';
        $password = password_hash($defaultAdminPassword, PASSWORD_DEFAULT);
    } else {
        $type = 'customer';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $code = rand(100000, 999999); 

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $message = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, account_type, verification_code) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $type, $code])) {
            $subject = "Verify Your Email - Alon at Araw";
            $body = "Hi $name,<br><br>Your email verification code is: <strong>$code</strong><br><br>"
                  . "Please enter this code on the verification page to activate your account.<br><br>Thank you!";
            if (sendEmail($email, $subject, $body)) {
                header("Location: verify-code.php?email=" . urlencode($email));
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Alon at Araw</title>
</head>
<body>
    <h2>Register</h2>

    <?php if (!empty($message)) : ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="name">Full Name</label><br>
        <input type="text" name="name" id="name" required><br><br>

        <label for="email">Email Address</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label for="password">Password</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
