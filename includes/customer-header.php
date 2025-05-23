<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Redirect if not customer
if ($_SESSION['type'] !== 'customer') {
    echo "Access denied. Customers only.";
    exit();
}

require_once __DIR__ . '/../config/db.php';

$stmt = $conn->prepare("SELECT name, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Alon at Araw</title>
    <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
/>
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/customer-header.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="icon" type="image/png" href="/alon_at_araw/assets/images/logo/logo.png" />

</head>
<body>

<div class="customer-header-wrapper">
<header class="customer-header">
    <!-- Left -->
    <div class="header-left">
        <img src="../../assets/images/logo/logo.png" alt="Logo" class="logo">
        <span class="shop-name">Alon at Araw</span>
    </div>

    <!-- Center -->
    <div class="header-center">
        <form class="search-bar" action="search-results.php" method="get">
            <div class="input-container">
            <input type="text" id="search" name="q" placeholder="Search drinks or products...">
            </div>
        </form>
        </div>


    <!-- Right -->
    <div class="header-right">
        <div class="cart-icon" title="View Cart">
            <a href="cart.php" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">0</span>
            </a>
        </div>

        <div class="profile-info" id="profileToggle">
            <?php
                // Clean up the stored path by removing ../ or ./ if present
                $rawPath = $customer['profile_image'] ?? '';

                $cleanPath = preg_replace('#^(\.\./)+#', '', $rawPath);

                $profileImagePath = $cleanPath 
                    ? '/alon_at_araw/' . $cleanPath
                    : '/alon_at_araw/assets/images/avatar.png';
                ?>
                <img src="<?= htmlspecialchars($profileImagePath) ?>" class="profile-pic" alt="Profile">

            <span><?= htmlspecialchars($customer['name']) ?></span>
            <i class="fas fa-caret-down dropdown-icon"></i>
        </div>
        <div class="dropdown" id="profileDropdown">
            <a href="profile-settings.php">My Profile</a>
            <a href="purchases.php">My Purchases</a>
            <a href="my-orders.php">My Orders</a>
            <a href="/alon_at_araw/auth/logout.php">Logout</a>
        </div>
    </div>
</header>
</div>
<script>
  // Toggle dropdown on click
  const profileToggle = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');

  profileToggle.addEventListener('click', () => {
    profileDropdown.style.display = (profileDropdown.style.display === 'flex') ? 'none' : 'flex';
  });

  // Close dropdown if clicked outside
  document.addEventListener('click', (e) => {
    if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
      profileDropdown.style.display = 'none';
    }
  });
</script>

