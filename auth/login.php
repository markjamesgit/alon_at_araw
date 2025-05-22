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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!password_verify($password, $user['password'])) {
            $passwordError = true;
        } elseif ($user['email_verified'] == 0) {
            $emailError = true;
            $message = "Please verify your email first. <a href='verify-code.php'>Click here to verify</a>";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['type'] = $user['account_type'];

            echo "<script>
                localStorage.setItem('loginSuccess', '1');
                localStorage.setItem('accountType', '" . $user['account_type'] . "');
            </script>";
        }
    } else {
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
    <link rel="stylesheet" href="../assets/styles/login.css">
    <link rel="stylesheet" href="../assets/global.css">
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png"/>
</head>
<body>
  <div class="login-container">
    <h2>Login</h2>

    <form method="POST" action="">
        <div class="input-container">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="example@gmail.com" required class="<?= $emailError ? 'error' : '' ?>">
            <?php if ($emailError && empty($message)): ?><div class="error-text">Email not found</div><?php endif; ?>
        </div>

        <div class="input-container">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required class="<?= $passwordError ? 'error' : '' ?>">
            <?php if ($passwordError): ?><div class="error-text">Incorrect password</div><?php endif; ?>
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
        // Show success toast for verified email
        if (localStorage.getItem('verificationSuccess') === '1') {
            $('#verifyToast').fadeIn(300);
            setTimeout(function () {
                $('#verifyToast').fadeOut(400);
            }, 3000);
            localStorage.removeItem('verificationSuccess');
        }

        // Show success toast and redirect after login
        if (localStorage.getItem('loginSuccess') === '1') {
            $('#successToast').fadeIn(300);

            setTimeout(function () {
                $('#successToast').fadeOut(400);
            }, 2000);

            setTimeout(function () {
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

