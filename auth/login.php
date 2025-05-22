<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['type'] === 'admin') {
        header("Location: ../dashboard/admin/dashboard.php");
        exit();
    } elseif ($_SESSION['type'] === 'customer') {
        header("Location: ../dashboard/customer/dashboard.php");
        exit();
    }
}

require '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['email_verified'] == 0) {
                $message = "Please verify your email first. <a href='verify-code.php'>Click here to verify</a>";
            }
            else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['type'] = $user['account_type'];

            if ($user['account_type'] === 'admin') {
                header("Location: ../dashboard/admin/dashboard.php");
            } else {
                header("Location: ../dashboard/customer/dashboard.php");
            }
            exit;
        }
    } else {
        $message = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Alon at Araw</title>
    <link rel="stylesheet" href="../assets/styles.css"> 
</head>
<body>
    <h2>Login</h2>

    <?php if (!empty($message)) : ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">Email</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label for="password">Password</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Login</button>
    </form>

    <p>Don’t have an account? <a href="register.php">Register here</a></p>
    <p><a href="forgot-password.php">Forgot Password?</a></p>
</body>
</html>
