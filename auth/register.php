<?php
require '../config/db.php';
require '../mail/MailSender.php';

$message = "";
$success = false;

// Error flags
$emailError = $nameError = $fullNameError = $passwordError = $birthdayError = $addressError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $passwordInput = $_POST['password'];
    $fullName = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $birthday = $_POST['birthday'];

    // Calculate age
    $today = new DateTime();
    $birthDate = new DateTime($birthday);
    $calculatedAge = $today->diff($birthDate)->y;

    // Validation
    if (empty($name)) {
        $message = "Username is required.";
        $nameError = true;
    } elseif (empty($fullName)) {
        $message = "Full name is required.";
        $fullNameError = true;
    } elseif (empty($address)) {
        $message = "Address is required.";
        $addressError = true;
    } elseif (empty($birthday)) {
        $message = "Birthday is required.";
        $birthdayError = true;
    } elseif (empty($passwordInput)) {
        $message = "Password is required.";
        $passwordError = true;
    } elseif ($calculatedAge < 18) {
        $message = "You must be at least 18 years old to register.";
        $birthdayError = true;
    } else {
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
            $profileImagePath = '';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $imgName = basename($_FILES['profile_image']['name']);
                $targetDir = "../assets/uploads/profile_images/";
                $profileImagePath = $targetDir . uniqid() . "_" . $imgName;
                move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath);
            }

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, account_type, verification_code, full_name, address, age, birthday, profile_image)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $password, $type, $code, $fullName, $address, $calculatedAge, $birthday, $profileImagePath])) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="input-container file-upload">
            <label for="profile_image" id="profileLabel">
                <i class="fas fa-image upload-icon"></i> Upload Profile Image
            </label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*">
            </div>

            <div class="input-container">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" placeholder="Juan Dela Cruz"
                       required class="<?= $fullNameError ? 'error' : '' ?>" value="<?= htmlspecialchars($fullName ?? '') ?>" />
                <?php if ($fullNameError): ?>
                    <div class="error-text">Full name is required.</div>
                <?php endif; ?>
            </div>

            <div class="input-row-group">
                <div class="input-container">
                    <label for="name">Username</label>
                    <input type="text" name="name" id="name" placeholder="Juander"
                           required class="<?= $nameError ? 'error' : '' ?>" value="<?= htmlspecialchars($name ?? '') ?>" />
                    <?php if ($nameError): ?>
                        <div class="error-text">Username is required.</div>
                    <?php endif; ?>
                </div>

                <div class="input-container">
                    <label for="birthday">Birthday</label>
                    <input type="date" name="birthday" id="birthday"
                           required class="<?= $birthdayError ? 'error' : '' ?>" value="<?= htmlspecialchars($birthday ?? '') ?>"
                           onchange="calculateAge()" />
                    <?php if ($birthdayError): ?>
                        <div class="error-text"><?= $calculatedAge < 18 ? 'You must be at least 18 years old.' : 'Birthday is required.' ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-container">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" readonly required value="<?= $calculatedAge ?? '' ?>" />
                </div>
            </div>

            <div class="input-container">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" placeholder="123 Street, City, Country"
                       required class="<?= $addressError ? 'error' : '' ?>" value="<?= htmlspecialchars($address ?? '') ?>" />
                <?php if ($addressError): ?>
                    <div class="error-text">Address is required.</div>
                <?php endif; ?>
            </div>

            <div class="input-container">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="example@gmail.com"
                       required class="<?= $emailError ? 'error' : '' ?>" value="<?= htmlspecialchars($email ?? '') ?>" />
                <?php if ($emailError): ?>
                    <div class="error-text">Email already registered.</div>
                <?php endif; ?>
            </div>

            <div class="input-container">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                       required class="<?= $passwordError ? 'error' : '' ?>" />
                <?php if ($passwordError): ?>
                    <div class="error-text">Password is required.</div>
                <?php endif; ?>
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
     document.getElementById('profile_image').addEventListener('change', function () {
    const label = document.getElementById('profileLabel');
    const fileName = this.files[0]?.name || "Upload Profile Image";
    label.innerHTML = `<i class="fas fa-image upload-icon"></i> ${fileName}`;
  });

    function calculateAge() {
        const birthday = document.getElementById('birthday').value;
        if (!birthday) return;

        const birthDate = new Date(birthday);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        document.getElementById('age').value = age;
    }
    </script>
</body>
</html>
