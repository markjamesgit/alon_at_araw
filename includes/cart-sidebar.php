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

// Function to format price with commas
function formatPrice($price) {
    return number_format($price, 2, '.', ',');
}
?>

<!-- Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<aside class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    <div class="cart-header-left">
      <input type="checkbox" id="selectAllItems" class="cart-checkbox">
      <h2>Your Cart (<?= $total_quantity ?>)</h2>
    </div>
    <button class="cart-close-btn" id="cartCloseBtn">&times;</button>
  </div>

  <div class="cart-items" id="cartSidebarContent">
    <?php if (empty($cart_items)): ?>
      <p>Your cart is empty.</p>
    <?php else: ?>
      <?php foreach ($cart_items as $item): ?>
        <div class="cart-item" data-id="<?= $item['cart_id'] ?>">
          <div class="cart-item-checkbox">
            <input type="checkbox" class="item-checkbox" data-id="<?= $item['cart_id'] ?>">
          </div>
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
            <div class="cart-item-price">₱<?= formatPrice($item['total_price']) ?></div>
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
    <span>₱<?= formatPrice($total_price) ?></span>
  </div>

  <div class="cart-actions">
    <?php if (!empty($cart_items)): ?>
      <button class="btn-checkout" disabled>Proceed to Checkout</button>
      <button id="deleteSelectedBtn" disabled>Delete Selected Items</button>
      <small class="selection-hint">Select items to checkout or delete</small>
    <?php endif; ?>
  </div>
</aside>

<!-- Delete Selected Items Modal -->
<div id="clearCartModal" class="md-modal-overlay">
  <div class="md-modal">
    <h3>Delete Selected Items</h3>
    <p>Are you sure you want to remove the selected items from your cart?</p>
    <div class="md-modal-actions">
      <button id="cancelClearCart" class="md-btn secondary">Cancel</button>
      <button id="confirmClearCart" class="md-btn primary">Yes, Delete</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
$(document).ready(function () {
  function formatPrice(number) {
    // Remove any existing peso sign and commas before formatting
    if (typeof number === 'string') {
      number = parseFloat(number.replace(/[₱,]/g, ''));
    }
    // Handle NaN or invalid numbers
    if (isNaN(number)) {
      number = 0;
    }
    // Format number with commas and 2 decimal places
    return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function updateCartSummary(quantity, totalPrice) {
    $('.cart-header h2').text(`Your Cart (${quantity})`);
    // Clean and format the total price
    const cleanTotal = parseFloat(String(totalPrice).replace(/[₱,]/g, ''));
    $('.cart-footer span:last-child').text(`₱${formatPrice(cleanTotal)}`);
  }

  function updateSelectedTotal() {
    let selectedTotal = 0;
    let selectedCount = 0;
    
    $('.item-checkbox:checked').each(function() {
      const cartId = $(this).data('id');
      const priceText = $(`.cart-item[data-id="${cartId}"]`).find('.cart-item-price').text();
      const price = parseFloat(priceText.replace(/[₱,]/g, ''));
      if (!isNaN(price)) {
        selectedTotal += price;
        selectedCount++;
      }
    });

    // Update the total display with selected items total
    if (selectedCount > 0) {
      const formattedTotal = formatPrice(selectedTotal);
      $('.cart-footer span:last-child').text(`₱${formattedTotal} (${selectedCount} selected)`);
      $('.btn-checkout').prop('disabled', false);
    } else {
      // Show original total if nothing selected
      const originalTotal = parseFloat($('.cart-footer span:last-child').data('original-total') || 0);
      const formattedOriginal = formatPrice(originalTotal);
      $('.cart-footer span:last-child').text(`₱${formattedOriginal}`);
      $('.btn-checkout').prop('disabled', true);
    }
  }

  // Select All functionality
  $('#selectAllItems').on('change', function() {
    const isChecked = $(this).prop('checked');
    $('.item-checkbox').prop('checked', isChecked);
    updateDeleteButtonState();
    updateSelectedTotal();
  });

  // Individual checkbox change
  $(document).on('change', '.item-checkbox', function() {
    updateDeleteButtonState();
    updateSelectedTotal();
    
    // Update select all checkbox
    const totalCheckboxes = $('.item-checkbox').length;
    const checkedCheckboxes = $('.item-checkbox:checked').length;
    $('#selectAllItems').prop('checked', totalCheckboxes === checkedCheckboxes);
  });

  // Update delete button state
  function updateDeleteButtonState() {
    const hasSelectedItems = $('.item-checkbox:checked').length > 0;
    $('#deleteSelectedBtn').prop('disabled', !hasSelectedItems);
  }

  // Checkout button click handler
  $(document).on('click', '.btn-checkout', function(e) {
    e.preventDefault();
    
    const selectedItems = $('.item-checkbox:checked').map(function() {
      return $(this).data('id');
    }).get();

    if (selectedItems.length === 0) {
      $.toast({
        heading: 'Selection Required',
        text: 'Please select items to checkout',
        icon: 'warning',
        position: 'bottom-left',
        hideAfter: 3000,
        stack: false
      });
      return;
    }

    // Check if user is logged in
    $.get('/alon_at_araw/auth/check-auth.php', function(response) {
      if (response.logged_in) {
        // Proceed directly to checkout with selected items
        const form = $('<form>', {
          'method': 'POST',
          'action': '/alon_at_araw/dashboard/customer/checkout.php'
        });

        // Add selected items as hidden input
        $('<input>').attr({
          'type': 'hidden',
          'name': 'selected_items',
          'value': JSON.stringify(selectedItems)
        }).appendTo(form);

        // Append form to body and submit
        form.appendTo('body').submit();
      } else {
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
          
          // Clean and format item price
          const cleanItemTotal = parseFloat(String(data.item_total).replace(/[₱,]/g, ''));
          $cartItem.find('.cart-item-price').text(`₱${formatPrice(cleanItemTotal)}`);
          
          // Update cart summary with clean total
          const cleanCartTotal = parseFloat(String(data.cart_total_price).replace(/[₱,]/g, ''));
          updateCartSummary(data.cart_total_quantity, cleanCartTotal);
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
            // Update cart actions to be empty
            $('.cart-actions').empty();
          }
        }
      }
    });
  });

  // Delete selected items
  $('#deleteSelectedBtn').on('click', function() {
    if ($('.item-checkbox:checked').length > 0) {
      $('#clearCartModal').css('display', 'flex');
    }
  });

  // Cancel delete
  $('#cancelClearCart').on('click', function() {
    $('#clearCartModal').css('display', 'none');
  });

  // Confirm delete selected items
  $('#confirmClearCart').on('click', function() {
    const selectedItems = $('.item-checkbox:checked').map(function() {
      return $(this).data('id');
    }).get();

    $.ajax({
      url: '/alon_at_araw/dashboard/customer/cart/delete-cart-items.php',
      type: 'POST',
      dataType: 'json',
      data: {
        cart_ids: selectedItems
      },
      success: function(data) {
        if (data.success) {
          selectedItems.forEach(cartId => {
            $(`.cart-item[data-id="${cartId}"]`).remove();
          });
          
          updateCartSummary(data.cart_total_quantity, data.cart_total_price);
          $('#cartCount').text(data.cart_total_quantity);
          
          $.toast({
            heading: 'Items Deleted',
            text: 'Selected items have been removed from your cart.',
            icon: 'success',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });

          // Hide modal
          $('#clearCartModal').css('display', 'none');
          
          // If cart is empty, refresh the page
          if (data.cart_total_quantity === 0) {
            location.reload();
          }
        } else {
          $.toast({
            heading: 'Error',
            text: data.message || 'Failed to delete items.',
            icon: 'error',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
        }
      }
    });
  });

  // Format all prices on page load
  function formatAllPrices() {
    // Format individual item prices
    $('.cart-item-price').each(function() {
      const priceText = $(this).text();
      const cleanPrice = parseFloat(priceText.replace(/[₱,]/g, ''));
      if (!isNaN(cleanPrice)) {
        $(this).text(`₱${formatPrice(cleanPrice)}`);
      }
    });

    // Format total price
    const totalPriceElement = $('.cart-footer span:last-child');
    const totalPriceText = totalPriceElement.text();
    const cleanTotalPrice = parseFloat(totalPriceText.replace(/[₱,]/g, ''));
    if (!isNaN(cleanTotalPrice)) {
      totalPriceElement.text(`₱${formatPrice(cleanTotalPrice)}`);
    }
  }

  // Call formatAllPrices on page load
  formatAllPrices();

  // Bind formatAllPrices to cart updates
  $(document).on('cartUpdated', formatAllPrices);

  // Close modal when clicking outside
  $(document).on('click', '.md-modal-overlay', function(e) {
    if (e.target === this) {
      $(this).css('display', 'none');
    }
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

  // Store original total on page load
  const originalTotal = parseFloat($('.cart-footer span:last-child').text().replace(/[₱,]/g, ''));
  $('.cart-footer span:last-child').data('original-total', originalTotal);

  // Initialize button states and totals
  updateSelectedTotal();
  updateDeleteButtonState();
});
</script>

