<?php
include '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Load cart contents
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
        $addon_names = [];
        $addon_data = json_decode($row['selected_addons'], true);
        if (is_array($addon_data)) {
            $addon_ids = array_keys($addon_data);
            if ($addon_ids) {
                $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
                $addon_stmt = $conn->prepare("SELECT addon_id, addon_name FROM addons WHERE addon_id IN ($placeholders)");
                $addon_stmt->execute($addon_ids);
                $addon_results = $addon_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($addon_data as $id => $qty) {
                    if (isset($addon_results[$id])) {
                        $addon_names[] = $addon_results[$id] . " {$qty}x";
                    }
                }
            }
        }

        $flavor_names = [];
        $flavor_data = json_decode($row['selected_flavors'], true);
        if (is_array($flavor_data)) {
            $flavor_ids = array_keys($flavor_data);
            if ($flavor_ids) {
                $placeholders = implode(',', array_fill(0, count($flavor_ids), '?'));
                $flavor_stmt = $conn->prepare("SELECT flavor_id, flavor_name FROM flavors WHERE flavor_id IN ($placeholders)");
                $flavor_stmt->execute($flavor_ids);
                $flavor_results = $flavor_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($flavor_data as $id => $qty) {
                    if (isset($flavor_results[$id])) {
                        $flavor_names[] = $flavor_results[$id] . " {$qty}x";
                    }
                }
            }
        }

        $cup_size_name = 'N/A';
        if (!empty($row['selected_cup_size'])) {
            $cup_stmt = $conn->prepare("SELECT size_name FROM cup_sizes WHERE cup_size_id = ?");
            $cup_stmt->execute([$row['selected_cup_size']]);
            $cup = $cup_stmt->fetch(PDO::FETCH_ASSOC);
            if ($cup) $cup_size_name = $cup['size_name'];
        }

        $item_total = $row['quantity'] * $row['unit_price'];
        $total_quantity += $row['quantity'];
        $total_price += $item_total;

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
            'total_price' => $item_total
        ];
    }
}
?>

<!-- Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<aside class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    <h2>Your Cart (<?= $total_quantity ?>)</h2>
    <button class="cart-close-btn" id="cartCloseBtn">&times;</button>
  </div>

  <div class="cart-items" id="cartSidebarContent">
    <?php if (empty($cart_items)): ?>
      <p>Your cart is empty.</p>
    <?php else: ?>
      <?php foreach ($cart_items as $item): ?>
        <div class="cart-item" data-id="<?= $item['cart_id'] ?>">
          <img src="<?= $item['product_image'] ? '/alon_at_araw/assets/uploads/products/' . htmlspecialchars($item['product_image']) : '/alon_at_araw/assets/images/no-image.png' ?>" alt="">
          <div class="cart-item-details">
            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
            <small>Cup Size: <?= htmlspecialchars($item['cup_size_name']) ?></small><br/>
            <?php if (!empty($item['addons'])): ?>
              <small>Add-ons: <?= htmlspecialchars(implode(', ', $item['addons'])) ?></small><br/>
            <?php endif; ?>
            <?php if (!empty($item['flavors'])): ?>
              <small>Flavors: <?= htmlspecialchars(implode(', ', $item['flavors'])) ?></small><br/>
            <?php endif; ?>

            <div class="cart-item-quantity">
              <button class="qty-btn minus" data-id="<?= $item['cart_id'] ?>">−</button>
              <span class="qty"><?= $item['quantity'] ?></span>
              <button class="qty-btn plus" data-id="<?= $item['cart_id'] ?>">+</button>
            </div>
            <div class="cart-item-price">₱<?= number_format($item['total_price'], 2) ?></div>
            <button class="delete-item-btn" data-id="<?= $item['cart_id'] ?>">
              <i class="fas fa-trash"></i> Delete Item
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cart-footer">
    <span>Total:</span>
    <span>₱<?= number_format($total_price, 2) ?></span>
  </div>

  <div class="cart-actions">
    <?php if (!empty($cart_items)): ?>
      <button class="btn-checkout">Proceed to Checkout</button>
      <button id="clearCartBtn">Clear Cart</button>
    <?php else: ?>
      <a href="/alon_at_araw/dashboard/customer/menus.php" class="btn-checkout">Start Shopping</a>
    <?php endif; ?>
  </div>
</aside>

<!-- Clear Cart Confirmation Modal -->
<div id="clearCartModal" class="md-modal-overlay">
  <div class="md-modal">
    <h3>Clear Cart</h3>
    <p>Are you sure you want to remove all items from your cart?</p>
    <div class="md-modal-actions">
      <button id="cancelClearCart" class="md-btn secondary">Cancel</button>
      <button id="confirmClearCart" class="md-btn primary">Yes, Clear</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
$(document).ready(function () {
  function updateCartSummary(quantity, totalPrice) {
    $('.cart-header h2').text(`Your Cart (${quantity})`);
    $('.cart-footer span:last-child').text(`₱${parseFloat(totalPrice).toFixed(2)}`);
  }

  // Checkout button click handler
  $('.btn-checkout').on('click', function(e) {
    if ($(this).text() === 'Proceed to Checkout') {
      e.preventDefault();
      // Check if user is logged in
      $.get('/alon_at_araw/auth/check-auth.php', function(response) {
        if (response.logged_in) {
          // Check if cart is empty
          if ($('.cart-item').length === 0) {
            $.toast({
              heading: 'Empty Cart',
              text: 'Please add items to your cart before checking out.',
              icon: 'warning',
              position: 'bottom-left',
              hideAfter: 3000,
              stack: false
            });
          } else {
            // Proceed to checkout
            window.location.href = '/alon_at_araw/dashboard/customer/checkout.php';
          }
        } else {
          // Redirect to login
          $.toast({
            heading: 'Login Required',
            text: 'Please login to proceed with checkout.',
            icon: 'warning',
            position: 'bottom-left',
            hideAfter: 3000,
            stack: false
          });
          window.location.href = '/alon_at_araw/auth/login.php';
        }
      }, 'json');
    }
  });

  // Use event delegation for quantity buttons
  $(document).on('click', '.qty-btn', function () {
    const cartId = $(this).data('id');
    const type = $(this).hasClass('plus') ? 'increase' : 'decrease';
    const $cartItem = $(`.cart-item[data-id="${cartId}"]`);
    const $qtyElem = $cartItem.find('.qty');

    $.ajax({
      url: '/alon_at_araw/dashboard/customer/cart/update-cart.php',
      type: 'POST',
      dataType: 'json',
      data: {
        cart_id: cartId,
        type: type
      },
      success: function (data) {
        if (data.success) {
          $.toast({
            heading: 'Quantity Updated',
            text: 'Item quantity has been updated.',
            icon: 'success',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
          $qtyElem.text(data.new_quantity);
          $cartItem.find('.cart-item-price').text(`₱${parseFloat(data.item_total).toFixed(2)}`);
          updateCartSummary(data.cart_total_quantity, data.cart_total_price);
          $('#cartCount').text(data.cart_total_quantity);
        } else {
          $.toast({
            heading: 'Error',
            text: data.message || 'Failed to update quantity.',
            icon: 'error',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
        }
      }
    });
  });

  // Use event delegation for delete buttons
  $(document).on('click', '.delete-item-btn', function () {
    const cartId = $(this).data('id');

    $.ajax({
      url: '/alon_at_araw/dashboard/customer/cart/delete-cart-item.php',
      type: 'POST',
      dataType: 'json',
      data: { cart_id: cartId },
      success: function (data) {
        if (data.success) {
          $.toast({
            heading: 'Item Removed',
            text: 'Item has been removed from your cart.',
            icon: 'info',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
          $(`.cart-item[data-id="${cartId}"]`).remove();
          updateCartSummary(data.cart_total_quantity, data.cart_total_price);
          $('#cartCount').text(data.cart_total_quantity); 
          if ($('.cart-item').length === 0) {
            $('.cart-items').html('<p>Your cart is empty.</p>');
            // Update cart actions
            $('.cart-actions').html('<a href="/alon_at_araw/dashboard/customer/menus.php" class="btn-checkout">Start Shopping</a>');
          }
        }
      }
    });
  });

  $('#clearCartBtn').on('click', function () {
    $('#clearCartModal').addClass('show');
  });

  $('#cancelClearCart').on('click', function () {
    $('#clearCartModal').removeClass('show');
  });

  $('#confirmClearCart').on('click', function () {
    $.ajax({
      url: '/alon_at_araw/dashboard/customer/cart/clear-cart.php',
      type: 'POST',
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          $.toast({
            heading: 'Cart Cleared',
            text: 'All items removed from your cart.',
            icon: 'warning',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
          $('.cart-items').html('<p>Your cart is empty.</p>');
          updateCartSummary(0, 0);
          $('#cartCount').text(0);
          // Update cart actions
          $('.cart-actions').html('<a href="/alon_at_araw/dashboard/customer/menus.php" class="btn-checkout">Start Shopping</a>');
        }
        $('#clearCartModal').removeClass('show');
      }
    });
  });

  $('#cartIcon').on('click', function () {
    $('#cartSidebar, #cartOverlay').addClass('active');
    $('body').addClass('cart-open');
  });

  $('#cartCloseBtn, #cartOverlay').on('click', function () {
    $('#cartSidebar, #cartOverlay').removeClass('active');
    $('body').removeClass('cart-open');
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('#cartSidebar, #cartOverlay').removeClass('active');
      $('body').removeClass('cart-open');
    }
  });
});
</script>

