<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

$customer = null;
$cartQty = 0;

if (isset($_SESSION['user_id']) && $_SESSION['type'] === 'customer') {
    require_once __DIR__ . '/../config/db.php';

    $stmt = $conn->prepare("SELECT name, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();

    // Get cart count
    $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartQty = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/customer-header.css"/>

    <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body style="padding-top: 60px;">

<header class="customer-header" id="customerHeader">
  <!-- Left -->
  <a href="/alon_at_araw/dashboard/customer/dashboard.php" class="header-left" aria-label="Go to homepage">
    <img src="/alon_at_araw/assets/images/logo/logo.png" alt="Alon at Araw logo" class="logo" />
    <span class="shop-name">Alon at Araw</span>
  </a>

  <!-- Center -->
  <nav class="header-nav" role="navigation" aria-label="Primary navigation">
    <ul>
      <li><a href="/alon_at_araw/dashboard/customer/dashboard.php">Home</a></li>
      <li><a href="/alon_at_araw/dashboard/customer/menus.php">Menu</a></li>
      <li><a href="/alon_at_araw/dashboard/customer/dashboard.php#featured">Featured</a></li>
      <li><a href="/alon_at_araw/dashboard/customer/dashboard.php#about">About</a></li>
      <li><a href="/alon_at_araw/dashboard/customer/dashboard.php#contact">Contact</a></li>
    </ul>
  </nav>

  <!-- Right -->
  <?php if ($customer): ?>
    <!-- Cart Icon -->
  <a href="#" class="cart-icon" id="cartIcon" aria-label="Cart">
      <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount"><?= $cartQty ?></span>
    </a>
    <div class="profile-info" id="profileToggle" tabindex="0" aria-haspopup="true" aria-expanded="false" role="button" aria-label="User menu">   
      <?php
        $rawPath = $customer['profile_image'] ?? '';
        $cleanPath = preg_replace('#^(\.\./)+#', '', $rawPath);
        $profileImagePath = $cleanPath 
            ? '/alon_at_araw/' . $cleanPath
            : '/alon_at_araw/assets/images/avatar.png';
      ?>
      <img src="<?= htmlspecialchars($profileImagePath) ?>" class="profile-pic" alt="Profile picture" />
      <span class="profile-name"><?= htmlspecialchars($customer['name']) ?></span>
      <i class="fas fa-caret-down dropdown-icon"></i>
    </div>

    <nav class="dropdown" id="profileDropdown" role="menu" aria-label="User menu">
      <a href="/alon_at_araw/dashboard/customer/profile-settings.php" role="menuitem">Account Settings</a>
      <a href="/alon_at_araw/dashboard/customer/order-history.php" role="menuitem">Order History</a>
      <a href="/alon_at_araw/dashboard/customer/my-purchases.php" role="menuitem">My Purchases</a>
      <a href="/alon_at_araw/auth/logout.php" role="menuitem">Sign Out</a>
    </nav>
  <?php else: ?>
    <a href="/alon_at_araw/auth/login.php" style="color:white; font-weight:600; text-decoration:none;">Login</a>
  <?php endif; ?>
</header>

<script>
  const header = document.getElementById('customerHeader');

  let lastScrollY = window.scrollY;
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const currentScrollY = window.scrollY;

        if (currentScrollY === 0) {
          // Always show header at the top
          header.classList.remove('hidden');
        } else if (currentScrollY > lastScrollY) {
          // Scrolling down - hide header
          header.classList.add('hidden');
        } else {
          // Scrolling up - show header
          header.classList.remove('hidden');
        }

        lastScrollY = currentScrollY;
        ticking = false;
      });

      ticking = true;
    }
  });

  // Profile dropdown toggle
  const profileToggle = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', () => {
      const isOpen = profileDropdown.style.display === 'flex';
      profileDropdown.style.display = isOpen ? 'none' : 'flex';
      profileToggle.classList.toggle('open', !isOpen);
      profileToggle.setAttribute('aria-expanded', !isOpen);
    });

    document.addEventListener('click', (e) => {
      if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.style.display = 'none';
        profileToggle.classList.remove('open');
        profileToggle.setAttribute('aria-expanded', false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && profileDropdown.style.display === 'flex') {
        profileDropdown.style.display = 'none';
        profileToggle.classList.remove('open');
        profileToggle.setAttribute('aria-expanded', false);
        profileToggle.focus();
      }
    });
  }
</script>

</body>
</html>
