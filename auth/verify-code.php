<?php
require '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ?");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        // Redirect to login page after successful verification
        header("Location: login.php");
        exit();
    } else {
        $message = "Invalid verification code or email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Alon at Araw</title>
</head>
<body>
     <h2>Email Verification</h2>

    <?php if (!empty($message)) : ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">Email Address</label><br>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        <br><br>

        <label for="code">Verification Code</label><br>
        <input type="text" name="code" id="code" maxlength="6" required><br><br>

        <button type="submit">Verify</button>
    </form>
</body>
</html>
