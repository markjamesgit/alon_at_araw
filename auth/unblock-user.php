<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_POST['admin_id'];
    $password = $_POST['password'];
    $user_id = $_POST['user_id'];

    // Validate admin password
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND account_type = 'admin'");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password'])) {
        echo "Incorrect admin password.";
        exit();
    }

    // Unblock user
    $stmt = $conn->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE id = ?");
    $stmt->execute([$user_id]);

    echo "success";
    exit();
}
?>
