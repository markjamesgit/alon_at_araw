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
$emailError = false;
$passwordError = false;
$blockedError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
    // Ensure admin is never blocked
    if ($user['account_type'] === 'admin') {
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        $stmt->execute([$user['id']]);
    }

    if ($user['is_blocked'] && $user['account_type'] !== 'admin') {
        $emailError = true;
        $blockedError = true;
        if ($user['account_type'] === 'customer') {
            $message = "Your account has been blocked due to too many failed login attempts. Please contact the admin.";
        }
    } elseif (!password_verify($password, $user['password'])) {
        if ($user['account_type'] !== 'admin') {
            // Only increment attempts and block if not admin
            $stmt = $conn->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);

            if ($user['failed_attempts'] + 1 >= 5) {
                $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
                $stmt->execute([$user['id']]);
                if ($user['account_type'] === 'customer') {
                    $message = "Your account has been blocked due to too many failed login attempts.";
                }
                $emailError = true;
                $blockedError = true;
            }
        }
        $passwordError = true;
    } elseif ($user['email_verified'] == 0) {
        $emailError = true;
        $message = "Please verify your email first. <a href='verify-code.php'>Click here to verify</a>";
    } else {
        // Successful login, reset failed attempts
        if ($user['account_type'] !== 'admin') {
            $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE id = ?");
            $stmt->execute([$user['id']]);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['type'] = $user['account_type'];

        echo "<script>
            localStorage.setItem('loginSuccess', '1');
            localStorage.setItem('accountType', '" . $user['account_type'] . "');
        </script>";
    }
}
 else {
        $emailError = true;
        $passwordError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/login.css"/>

    <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png"/>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <div class="login-container">
    <h2>Login</h2>

    <form method="POST" action="">
        <div class="input-container">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="example@gmail.com" required class="<?= $emailError ? 'error' : '' ?>">
            <?php if ($blockedError): ?>
                <div class="error-text"><?= $message ?></div>
            <?php elseif ($emailError && empty($message)): ?>
                <div class="error-text">Email not found</div>
            <?php elseif (!empty($message)): ?>
                <div class="error-text"><?= $message ?></div>
            <?php endif; ?>
        </div>

        <div class="input-container">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required class="<?= $passwordError ? 'error' : '' ?>">
            <?php if ($passwordError && !$blockedError): ?>
                <div class="error-text">Incorrect password</div>
            <?php endif; ?>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="links">
        <p>Don’t have an account? <a href="register.php">Register here</a></p>
        <p><a href="forgot-password.php">Forgot Password?</a></p>
    </div>
</div>

<div class="toast" id="successToast">Login successful! Redirecting...</div>

<div class="toast" id="verifyToast" style="display:none;">
  Email verified successfully. You may now log in.
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        if (localStorage.getItem('verificationSuccess') === '1') {
            $('#verifyToast').fadeIn(300);
            setTimeout(() => $('#verifyToast').fadeOut(400), 3000);
            localStorage.removeItem('verificationSuccess');
        }

        if (localStorage.getItem('loginSuccess') === '1') {
            $('#successToast').fadeIn(300);
            setTimeout(() => $('#successToast').fadeOut(400), 2000);
            setTimeout(() => {
                let type = localStorage.getItem('accountType');
                if (type === 'admin') {
                    window.location.href = '../dashboard/admin/dashboard.php';
                } else {
                    window.location.href = '../dashboard/customer/dashboard.php';
                }
                localStorage.removeItem('loginSuccess');
                localStorage.removeItem('accountType');
            }, 3000);
        }
    });
</script>

</body>
</html>
