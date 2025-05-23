<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['type'] !== 'admin') {
    echo "Access denied. Admins only.";
    exit();
}

require_once __DIR__ . '/../config/db.php';

$stmt = $conn->prepare("SELECT name, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Clean profile image path
$rawPath = $admin['profile_image'] ?? '';
$cleanPath = preg_replace('#^(\.\./)+#', '', $rawPath);
$profileImagePath = $cleanPath 
    ? '/alon_at_araw/' . $cleanPath 
    : '/alon_at_araw/assets/images/avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Alon at Araw</title>
        <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
/>
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/admin-sidebar.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png" />
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-header">
  <h2>Admin Panel</h2>
  <a href="admin-profile.php" class="admin-profile-link">
    <div class="admin-profile">
      <img src="<?= htmlspecialchars($profileImagePath) ?>" alt="Admin Profile" class="profile-pic" />
      <div class="admin-info">
        <span class="admin-name"><?= htmlspecialchars($admin['name']) ?></span>
        <small class="admin-role">Administrator</small>
      </div>
    </div>
  </a>
</div>

  <nav class="nav-links">
    <ul>
      <li><a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="manage-users.php" class="<?= $currentPage === 'manage-users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Manage Users</a></li>    
      <li><a href="manage-categories.php" class="<?= $currentPage === 'manage-categories.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Manage Categories</a></li>
      <li><a href="manage-products.php" class="<?= $currentPage === 'manage-products.php' ? 'active' : '' ?>"><i class="fas fa-box-open"></i> Manage Products</a></li>  
      <li><a href="inventory.php" class="<?= $currentPage === 'inventory.php' ? 'active' : '' ?>"><i class="fas fa-cubes"></i> Manage Inventory</a></li>      
      <li><a href="custom-drinks.php" class="<?= $currentPage === 'custom-drinks.php' ? 'active' : '' ?>"><i class="fas fa-mug-hot"></i> Custom Drinks</a></li>
      <li><a href="view-orders.php" class="<?= $currentPage === 'view-orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> View Orders</a></li>     
      <li><a href="pos.php" class="<?= $currentPage === 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> POS / Queue</a></li>    
      <li><a href="reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Reports</a></li>      
      <li><a href="customize-site.php" class="<?= $currentPage === 'customize-site.php' ? 'active' : '' ?>"><i class="fas fa-paint-brush"></i> Page Customization</a></li>         
      <li><a href="/alon_at_araw/auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>
</aside>
</body>
</html>

