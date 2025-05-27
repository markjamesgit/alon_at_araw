<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /alon_at_araw/dashboard/customer/dashboard.php");
    exit();
}

// Redirect if not a customer
if ($_SESSION['type'] !== 'customer') {
    echo "Access denied. Customers only.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Dashboard - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/customer-dashboard.css">
  <link rel="icon" type="image/png" href="/alon_at_araw/assets/images/logo/logo.png" />
</head>
<body>

  <?php include 'includes/customer-sidebar.php'; ?>

  <main class="customer-dashboard-main">
    <?php include 'dashboard/customer/dashboard.php'; ?>
  </main>

</body>
</html>
