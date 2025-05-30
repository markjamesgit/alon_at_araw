<?php
include '../../config/db.php'; 

// Fetch all categories
$category_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : null;
$category_name = null;
$category_image = null;
$products = [];

if ($category_id) {
    // Fetch selected category details
    $cat_stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
    $cat_stmt->execute(['id' => $category_id]);
    $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);

    if ($cat) {
        $category_name = $cat['name'];
        $category_image = $cat['image'];
    }

    // Fetch products in the selected category
    $product_stmt = $conn->prepare("SELECT * FROM products WHERE category_id = :id");
    $product_stmt->execute(['id' => $category_id]);
    $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Menu | Alon at Araw</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/menus.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/cart-sidebar.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <?php include '../../includes/customer-header.php'; ?>
  <?php include '../../includes/cart-sidebar.php'; ?>

  <main class="menu-page">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
      <a href="/alon_at_araw/dashboard/customer/dashboard.php">Home</a>
      <span>›</span>
      <a href="menus.php">Menu</a>
      <?php if ($category_name): ?>
        <span>›</span>
        <span><?= htmlspecialchars($category_name) ?></span>
      <?php endif; ?>
    </nav>

    <div class="menu-layout">
      <!-- Left: Categories -->
      <aside class="menu-categories">
        <h3>Categories</h3>
        <ul>
          <?php foreach ($categories as $cat): ?>
            <li>
              <a href="?category=<?= $cat['id'] ?>" class="<?= $category_id == $cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <!-- Right: Products -->
<section class="menu-products">
  <?php if (!$category_id): ?>
    <h2>Browse Categories</h2>
    <div class="products-grid">
      <?php foreach ($categories as $cat): ?>
        <a href="?category=<?= $cat['id'] ?>" class="category-card">
          <img 
            src="<?= $cat['image'] ? '/alon_at_araw/assets/uploads/categories/' . htmlspecialchars($cat['image']) : '/alon_at_araw/assets/images/no-image.png' ?>" 
            alt="<?= htmlspecialchars($cat['name']) ?>">
          <h4><?= htmlspecialchars($cat['name']) ?></h4>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <h2><?= htmlspecialchars($category_name) ?></h2>
    <div class="products-grid">
      <?php if ($products): ?>
        <?php foreach ($products as $product): ?>
          <a href="product-details.php?id=<?= $product['product_id'] ?>" class="product-card <?= $product['is_best_seller'] ? 'highlight' : '' ?>">
            <?php if ($product['is_best_seller']): ?>
              <span class="badge">Best Seller</span>
            <?php endif; ?>
            <img 
              src="<?= $product['product_image'] ? '/alon_at_araw/assets/uploads/products/' . htmlspecialchars($product['product_image']) : '/alon_at_araw/assets/images/no-image.png' ?>"  
              alt="<?= htmlspecialchars($product['product_name']) ?>">
            <div class="card-content">
              <h4><?= htmlspecialchars($product['product_name']) ?></h4>
              <p><?= htmlspecialchars($product['description']) ?></p>
              <span class="price">₱<?= number_format($product['price'], 2) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No products found in this category.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
    </div>
  </main>
</body>
</html>
