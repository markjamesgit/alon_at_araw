<?php
require '../config/db.php';

$email = $_GET['email'] ?? '';
$message = "";
$success = false;

$emailError = false;
$codeError = false;
$passwordError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);
    $newPass = $_POST['password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ?");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE id = ?");
        if ($stmt->execute([$hashedPass, $user['id']])) {
            $success = true;
        } else {
            $message = "Failed to update password. Please try again.";
        }
    } else {
        $message = "Invalid email or reset code.";
        $emailError = true;
        $codeError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/reset-password.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <div class="login-container">
    <h2>Reset Password</h2>

<form method="POST" action="" onsubmit="return combineCodeInputs()">
  <div class="input-container">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" required
           value="<?= htmlspecialchars($email) ?>"
           class="<?= $emailError ? 'error' : '' ?>">
    <?php if ($emailError): ?>
      <div class="error-text">Invalid or unrecognized email.</div>
    <?php endif; ?>
  </div>

  <div class="input-container">
    <label>Reset Code</label>
    <div id="code-inputs" style="display:flex; gap:8px;">
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
        <input type="text" maxlength="1" class="code-input" pattern="\d" inputmode="numeric" required />
    </div>
    <input type="hidden" name="code" id="combined-code" />
    
    <?php if ($codeError): ?>
      <div class="error-text">Reset code is incorrect or expired.</div>
    <?php endif; ?>
  </div>

  <div class="input-container">
    <label for="password">New Password</label>
    <input type="password" name="password" id="password" required
           class="<?= $passwordError ? 'error' : '' ?>">
  </div>

  <button type="submit">Reset Password</button>
</form>

  </div>

  <div class="toast" id="successToast">Password reset successful! Redirecting to login...</div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function () {
      <?php if ($success): ?>
        $('#successToast').fadeIn(300);

        setTimeout(function () {
          $('#successToast').fadeOut(400);
        }, 2000);

        setTimeout(function () {
          window.location.href = 'login.php';
        }, 3000);
      <?php elseif (!empty($message)): ?>
        $('#errorToast').fadeIn(300).delay(3000).fadeOut(400);
      <?php endif; ?>
    });

     // Automatically focus next input when typing, backspace support
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

        // Only allow digits
        input.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key)) {
                e.preventDefault();
            }
        });
    });

    // On submit, combine inputs into hidden field
    function combineCodeInputs() {
        let code = '';
        inputs.forEach(input => code += input.value);
        if (code.length !== inputs.length) {
            alert('Please enter all 6 digits of the verification code.');
            return false;
        }
        document.getElementById('combined-code').value = code;
        return true; // allow form submit
    }
  </script>
</body>
</html>
