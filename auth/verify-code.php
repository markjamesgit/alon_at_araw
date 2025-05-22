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
        echo "<script>
            localStorage.setItem('verificationSuccess', '1');
            window.location.href = 'login.php';
        </script>";
        exit();
    } else {
        $message = "Invalid verification code or email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verify Email - Alon at Araw</title>
    <link rel="stylesheet" href="../assets/styles/login.css" />
    <link rel="stylesheet" href="../assets/global.css" />
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png" />
</head>
<body>
    <div class="login-container">
        <h2>Email Verification</h2>

        <form method="POST" action="">
            <div class="input-container">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    required
                    value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                />
            </div>

            <div class="input-container">
                <label>Verification Code</label>
                <div id="code-inputs" style="display:flex; gap:8px;">
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                    <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
                </div>
                <!-- Hidden input to hold combined code -->
                <input type="hidden" name="code" id="combined-code" />
            </div>

            <?php if (!empty($message)) : ?>
                <p class="error-text"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <button type="submit">Verify</button>
        </form>

        <div class="links">
            <p>
                Go back to 
                <a href="register.php">Registration</a>
            </p>
        </div>

    </div>

    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.code-input');

            inputs.forEach((input, i) => {
                input.addEventListener('input', () => {
                    if (input.value.length === 1 && i < inputs.length - 1) {
                        inputs[i + 1].focus();
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && input.value === '' && i > 0) {
                        inputs[i - 1].focus();
                    }
                });

                input.addEventListener('keypress', (e) => {
                    if (!/\d/.test(e.key)) {
                        e.preventDefault();
                    }
                });
            });

            // Combine code inputs and validate on form submit
            document.querySelector('form').onsubmit = function () {
                let code = '';
                inputs.forEach(input => code += input.value);
                if (code.length !== inputs.length) {
                    alert('Please enter all 6 digits of the verification code.');
                    return false;
                }
                document.getElementById('combined-code').value = code;
                return true; // allow form submit
            };

        });
    </script>
</body>
</html>
