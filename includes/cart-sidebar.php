<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/alon_at_araw/config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$cart_items = [];
$total_quantity = 0;
$total_price = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT c.cart_id, c.product_id, c.quantity, c.unit_price, c.selected_addons, c.selected_flavors, c.selected_cup_size,
                   p.product_name, p.product_image
            FROM cart c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      // Add-ons with quantity
        $addon_data = json_decode($row['selected_addons'], true);
        $addon_names = [];

        if (is_array($addon_data) && count($addon_data) > 0) {
            $addon_ids = array_keys($addon_data);
            $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
            $addon_stmt = $conn->prepare("SELECT addon_id, addon_name FROM addons WHERE addon_id IN ($placeholders)");
            $addon_stmt->execute($addon_ids);
            $addon_results = $addon_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name]

            foreach ($addon_data as $id => $qty) {
                if (isset($addon_results[$id])) {
                    $addon_names[] = $addon_results[$id] . ' ' . (int)$qty . 'x';
                }
            }
        }

        // Flavors with quantity
        $flavor_data = json_decode($row['selected_flavors'], true);
        $flavor_names = [];

        if (is_array($flavor_data) && count($flavor_data) > 0) {
            $flavor_ids = array_keys($flavor_data);
            $placeholders = implode(',', array_fill(0, count($flavor_ids), '?'));
            $flavor_stmt = $conn->prepare("SELECT flavor_id, flavor_name FROM flavors WHERE flavor_id IN ($placeholders)");
            $flavor_stmt->execute($flavor_ids);
            $flavor_results = $flavor_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name]

            foreach ($flavor_data as $id => $qty) {
                if (isset($flavor_results[$id])) {
                    $flavor_names[] = $flavor_results[$id] . ' ' . (int)$qty . 'x';
                }
            }
        }

        $cup_size_name = 'N/A';
        if (!empty($row['selected_cup_size'])) {
            $cup_stmt = $conn->prepare("SELECT size_name FROM cup_sizes WHERE cup_size_id = ?");
            $cup_stmt->execute([$row['selected_cup_size']]);
            $cup_res = $cup_stmt->fetch(PDO::FETCH_ASSOC);
            if ($cup_res) $cup_size_name = $cup_res['size_name'];
        }

        $item_total_price = $row['quantity'] * $row['unit_price'];
        $total_price += $item_total_price;
        $total_quantity += $row['quantity'];

        $cart_items[] = [
    'cart_id' => $row['cart_id'],
    'product_id' => $row['product_id'],
    'quantity' => $row['quantity'],
    'unit_price' => $row['unit_price'],
    'product_name' => $row['product_name'],
    'product_image' => $row['product_image'],
    'addons' => $addon_names,
    'flavors' => $flavor_names,
    'cup_size_name' => $cup_size_name,
    'total_price' => $item_total_price
];

    }
}
?>

<!-- Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<aside class="cart-sidebar" id="cartSidebar" aria-label="Shopping Cart">
  <div class="cart-header">
    <h2>Your Cart (<?= $total_quantity ?>)</h2>
    <button class="cart-close-btn" id="cartCloseBtn" aria-label="Close Cart">&times;</button>
  </div>

  <div class="cart-items">
    <?php if (empty($cart_items)): ?>
      <p>Your cart is empty.</p>
    <?php else: ?>
      <?php foreach ($cart_items as $item): ?>
        <div class="cart-item" data-id="<?= htmlspecialchars($item['cart_id']) ?>">
          <img src="<?= $item['product_image'] ? '/alon_at_araw/assets/uploads/products/' . htmlspecialchars($item['product_image']) : '/alon_at_araw/assets/images/no-image.png' ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
          <div class="cart-item-details">
            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
            <small>Cup Size: <?= htmlspecialchars($item['cup_size_name']) ?></small><br/>
            <?php if (!empty($item['addons'])): ?>
                <small>Add-ons: <?= htmlspecialchars(implode(', ', $item['addons'])) ?></small><br/>
              <?php endif; ?>
            <?php if (!empty($item['flavors'])): ?>
              <small>Flavors: <?= htmlspecialchars(implode(', ', $item['flavors'])) ?></small><br/>
            <?php endif; ?>
            <div class="cart-item-quantity">Qty: <?= (int)$item['quantity'] ?></div>
            <div class="cart-item-price">₱<?= number_format($item['total_price'], 2) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cart-footer">
    <span>Total:</span>
    <span>₱<?= number_format($total_price, 2) ?></span>
  </div>

  <button class="btn-checkout">Proceed to Checkout</button>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cartIcon = document.querySelector('#cartIcon');
  const cartSidebar = document.getElementById('cartSidebar');
  const cartOverlay = document.getElementById('cartOverlay');
  const cartCloseBtn = document.getElementById('cartCloseBtn');

  function openCart() {
    cartSidebar.classList.add('active');
    cartOverlay.classList.add('active');
    document.body.classList.add('cart-open');
  }

  function closeCart() {
    cartSidebar.classList.remove('active');
    cartOverlay.classList.remove('active');
    document.body.classList.remove('cart-open');
  }

  if (cartIcon) cartIcon.addEventListener('click', openCart);
  if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeCart);
  if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && cartSidebar.classList.contains('active')) {
      closeCart();
    }
  });
});
</script>
