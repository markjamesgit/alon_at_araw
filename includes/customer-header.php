<?php
session_start();

// Only fetch customer data if logged in as a customer
$customer = null;
if (isset($_SESSION['user_id']) && $_SESSION['type'] === 'customer') {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $conn->prepare("SELECT name, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Header - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/customer-header.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>

<div class="customer-header-wrapper">
<header class="customer-header">
  <!-- Left -->
  <div class="header-left">
    <img src="/alon_at_araw/assets/images/logo/logo.png" alt="Logo" class="logo" />
    <span class="shop-name">Alon at Araw</span>
  </div>

  <!-- Center -->
  <div class="header-center">
    <form class="search-bar" action="search-results.php" method="get">
      <div class="input-container">
        <input type="text" id="search" name="q" placeholder="Search drinks or products..." />
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

    <?php if ($customer): ?>
      <!-- Profile Info if Logged In -->
      <div class="profile-info" id="profileToggle">
        <?php
          $rawPath = $customer['profile_image'] ?? '';
          $cleanPath = preg_replace('#^(\.\./)+#', '', $rawPath);
          $profileImagePath = $cleanPath 
              ? '/alon_at_araw/' . $cleanPath
              : '/alon_at_araw/assets/images/avatar.png';
        ?>
        <img src="<?= htmlspecialchars($profileImagePath) ?>" class="profile-pic" alt="Profile" />
        <span><?= htmlspecialchars($customer['name']) ?></span>
        <i class="fas fa-caret-down dropdown-icon"></i>
      </div>
      <div class="dropdown" id="profileDropdown">
        <a href="profile-settings.php">My Profile</a>
        <a href="purchases.php">My Purchases</a>
        <a href="my-orders.php">My Orders</a>
        <a href="/alon_at_araw/auth/logout.php">Logout</a>
      </div>
    <?php else: ?>
      <!-- Login Button if Not Logged In -->
      <a href="/alon_at_araw/auth/login.php" class="login-button">Login</a>
    <?php endif; ?>
  </div>
</header>
</div>

<script>
  const profileToggle = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', () => {
      profileDropdown.style.display = (profileDropdown.style.display === 'flex') ? 'none' : 'flex';
    });

    document.addEventListener('click', (e) => {
      if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.style.display = 'none';
      }
    });
  }
</script>

</body>
</html>
