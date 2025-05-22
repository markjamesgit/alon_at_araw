<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not admin
if ($_SESSION['type'] !== 'admin') {
    echo "Access denied. Admins only.";
    exit();
}

// Optional: Connect DB for fetching admin info
require '../../config/db.php';

$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alon at Araw</title>
</head>
<body>
     <h1>Welcome, <?= htmlspecialchars($admin['name']) ?>!</h1>
    <p>Email: <?= htmlspecialchars($admin['email']) ?></p>
    <p>Role: Admin</p>

    <nav>
        <ul>
            <li><a href="manage-users.php">Manage Users</a></li>
            <li><a href="manage-products.php">Manage Products</a></li>
            <li><a href="view-orders.php">View Orders</a></li>
            <!-- Add more links as needed -->
        </ul>
    </nav>

    <p><a href="../../auth/logout.php">Logout</a></p>
</body>
</html>
