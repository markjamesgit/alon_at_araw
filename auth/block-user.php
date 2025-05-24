<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'admin') {
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_POST['admin_id'];
    $password = $_POST['password'];
    $user_id = $_POST['user_id'];

    // Verify admin password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id AND account_type = 'admin'");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password'])) {
        echo "Invalid admin password.";
        exit;
    }

    // Block user (but never block an admin)
    $stmt2 = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = :id AND account_type != 'admin'");
    $stmt2->execute(['id' => $user_id]);

    if ($stmt2->rowCount() > 0) {
        echo "success";
    } else {
        echo "Failed to block user or user is an admin.";
    }
} else {
    echo "Invalid request.";
}
