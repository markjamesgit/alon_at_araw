<?php
require '../config/db.php';

$message = "";
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);
    $newPass = $_POST['password'];

    // Verify email + reset_code
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ?");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        // Update password and clear reset_code
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE id = ?");
        if ($stmt->execute([$hashedPass, $user['id']])) {
            $message = "Password updated successfully! You can now <a href='login.php'>login</a>.";
        } else {
            $message = "Failed to update password. Please try again.";
        }
    } else {
        $message = "Invalid email or reset code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Alon at Araw</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <h2>Reset Password</h2>

    <?php if (!empty($message)) : ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <?php if (empty($message) || strpos($message, 'successfully') === false) : ?>
    <form method="POST" action="">
        <label for="email">Email Address</label><br>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($email) ?>"><br><br>

        <label for="code">Reset Code</label><br>
        <input type="text" name="code" id="code" maxlength="6" required><br><br>

        <label for="password">New Password</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</body>
</html>
