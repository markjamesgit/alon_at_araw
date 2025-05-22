<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Redirect if not customer
if ($_SESSION['type'] !== 'customer') {
    echo "Access denied. Customers only.";
    exit();
}

// Optional: Fetch customer info from DB
require '../../config/db.php';

$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Alon at Araw</title>
</head>
<body>
     <h1>Welcome, <?= htmlspecialchars($customer['name']) ?>!</h1>
    <p>Email: <?= htmlspecialchars($customer['email']) ?></p>
    <p>Role: Customer</p>

    <nav>
        <ul>
            <li><a href="browse-products.php">Browse Products</a></li>
            <li><a href="my-orders.php">My Orders</a></li>
            <li><a href="profile-settings.php">Profile Settings</a></li>
        </ul>
    </nav>

    <p><a href="../../auth/logout.php">Logout</a></p>
</body>
</html>
