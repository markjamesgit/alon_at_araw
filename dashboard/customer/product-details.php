<?php
include '../../config/db.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['product']) ? (int) $_GET['product'] : null);

if (!$product_id) {
    echo "Invalid product.";
    exit;
}

// Fetch product info with category
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.product_id = :id
");
$stmt->execute(['id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found.";
    exit;
}

// Fetch product components
$component_stmt = $conn->prepare("
    SELECT pc.component_type, pc.component_id, pc.quantity,
           CASE 
               WHEN pc.component_type = 'addons' THEN a.addon_name
               WHEN pc.component_type = 'flavors' THEN f.flavor_name
               WHEN pc.component_type = 'cup_sizes' THEN cs.size_name
           END AS component_name
    FROM product_components pc
    LEFT JOIN addons a ON pc.component_type = 'addons' AND pc.component_id = a.addon_id
    LEFT JOIN flavors f ON pc.component_type = 'flavors' AND pc.component_id = f.flavor_id
    LEFT JOIN cup_sizes cs ON pc.component_type = 'cup_sizes' AND pc.component_id = cs.cup_size_id
    WHERE pc.product_id = :id
    ORDER BY pc.component_type
");
$component_stmt->execute(['id' => $product_id]);
$components = $component_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group components by type
$grouped_components = [];
foreach ($components as $comp) {
    $type = $comp['component_type'];
    $grouped_components[$type][] = $comp;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['product_name']) ?> | Alon at Araw</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/product-details.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
</head>
<body>
  <?php include '../../includes/customer-header.php'; ?>

  <main class="menu-page">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
      <a href="/alon_at_araw/dashboard/customer/dashboard.php">Home</a>
      <span>›</span>
      <a href="/alon_at_araw/dashboard/customer/menus.php">Menu</a>
      <?php if ($product['category_id']): ?>
        <span>›</span>
        <a href="/alon_at_araw/dashboard/customer/menus.php?category=<?= $product['category_id'] ?>">
          <?= htmlspecialchars($product['category_name']) ?>
        </a>
      <?php endif; ?>
      <span>›</span>
      <span><?= htmlspecialchars($product['product_name']) ?></span>
    </nav>

    <!-- Product Info -->
    <section class="product-details">
    <img 
        src="<?= $product['product_image'] ? '/alon_at_araw/assets/uploads/products/' . htmlspecialchars($product['product_image']) : '/alon_at_araw/assets/images/no-image.png' ?>" 
        alt="<?= htmlspecialchars($product['product_name']) ?>"
    >
    <div class="product-details-content">
        <h1>
        <?= htmlspecialchars($product['product_name']) ?>
        <?php if ($product['is_best_seller']): ?>
            <span class="best-seller-badge">Best Seller</span>
        <?php endif; ?>
        </h1>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <p class="price">₱<?= number_format($product['price'], 2) ?></p>
    </div>
    </section>

    <!-- Product Components -->
<form action="/alon_at_araw/order/add-to-cart.php" method="POST">
  <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

  <section class="product-options sbx-style">
    <h2 class="sbx-heading">Customize Your Drink</h2>

    <?php foreach ($grouped_components as $type => $items): ?>
      <div class="sbx-component-group">
        <h3 class="sbx-subheading"><?= ucwords(str_replace('_', ' ', $type)) ?></h3>
        <div class="sbx-component-options <?= $type === 'cup_sizes' ? 'cup-sizes' : '' ?>">
          <?php foreach ($items as $item): ?>
            <?php if ($type === 'cup_sizes'): ?>
              <input 
                type="radio" 
                name="cup_size" 
                id="cup_<?= $item['component_id'] ?>" 
                value="<?= $item['component_id'] ?>" 
                required 
                hidden
              >
              <label class="sbx-cup-option" for="cup_<?= $item['component_id'] ?>">
                <?= htmlspecialchars($item['component_name']) ?>
              </label>
            <?php else: ?>
              <div class="sbx-addons-flavors" data-id="<?= $item['component_id'] ?>" data-type="<?= $type ?>">
                <span class="sbx-minus">−</span>
                <span class="sbx-name"><?= htmlspecialchars($item['component_name']) ?></span>
                <input 
                  type="number" 
                  name="<?= $type ?>[<?= $item['component_id'] ?>]" 
                  value="0" 
                  min="0"
                  class="sbx-quantity-input"
                  readonly
                >
                <span class="sbx-plus">+</span>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="sbx-add-to-cart">Add to Cart</button>
  </section>
</form>
  </main>

<script>
document.querySelectorAll('.sbx-addons-flavors').forEach(container => {
  const minusBtn = container.querySelector('.sbx-minus');
  const plusBtn = container.querySelector('.sbx-plus');
  const input = container.querySelector('.sbx-quantity-input');

  minusBtn.addEventListener('click', () => {
    let val = parseInt(input.value, 10);
    if (val > 0) input.value = val - 1;
  });

  plusBtn.addEventListener('click', () => {
    let val = parseInt(input.value, 10);
    input.value = val + 1;
  });
});
</script>

</body>
</html>
