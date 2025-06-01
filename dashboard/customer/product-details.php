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
    SELECT 
        pc.component_type, 
        pc.component_id, 
        pc.quantity as product_component_quantity,
        pc.active,
        CASE 
            WHEN pc.component_type = 'addons' THEN a.addon_name
            WHEN pc.component_type = 'flavors' THEN f.flavor_name
            WHEN pc.component_type = 'cup_sizes' THEN cs.size_name
        END AS component_name,
        CASE 
            WHEN pc.component_type = 'addons' THEN a.quantity
            WHEN pc.component_type = 'flavors' THEN f.quantity
            WHEN pc.component_type = 'cup_sizes' THEN cs.quantity
        END AS global_quantity
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
    $isOutOfStock = $comp['product_component_quantity'] <= 0 || $comp['global_quantity'] <= 0;
    $comp['is_out_of_stock'] = $isOutOfStock;
    $grouped_components[$type][] = $comp;
}

// Function to display stock status
function getStockStatus($productComponentQty, $globalQty) {
    if ($productComponentQty <= 0) {
        return '<span class="badge badge-danger">Out of Stock for this Product</span>';
    } elseif ($globalQty <= 0) {
        return '<span class="badge badge-warning">Out of Stock (Global)</span>';
    } else {
        return '<span class="badge badge-success">In Stock</span>';
    }
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
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/cart-sidebar.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
  
  <style>
    .sbx-cup-option.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      background-color: #f0f0f0;
    }

    .sbx-addons-flavors.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      background-color: #f0f0f0;
    }

    .sbx-addons-flavors.disabled .sbx-minus,
    .sbx-addons-flavors.disabled .sbx-plus {
      pointer-events: none;
      background-color: #ddd;
    }

    .unavailable {
      color: #ff4444;
      font-size: 0.8em;
      margin-left: 5px;
    }

    input[type="radio"]:disabled + label {
      pointer-events: none;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    .badge-warning {
        background-color: #ffc107;
        color: black;
    }
    .component-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .component-table th, .component-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .component-table th {
        background-color: #f5f5f5;
    }
    .component-section {
        margin-bottom: 30px;
    }
    .component-section h3 {
        color: #333;
        margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <?php include '../../includes/customer-header.php'; ?>
  <?php include '../../includes/cart-sidebar.php'; ?>

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

     <div class="product-grid">
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
                <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>
              >
              <label class="sbx-cup-option <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>" for="cup_<?= $item['component_id'] ?>">
                <?= htmlspecialchars($item['component_name']) ?>
                <?= ($item['is_out_of_stock']) ? '<span class="unavailable">(Out of Stock)</span>' : '' ?>
              </label>
            <?php else: ?>
              <div class="sbx-addons-flavors <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>" data-id="<?= $item['component_id'] ?>" data-type="<?= $type ?>">
                <span class="sbx-minus <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>">−</span>
                <span class="sbx-name">
                  <?= htmlspecialchars($item['component_name']) ?>
                  <?= ($item['is_out_of_stock']) ? '<span class="unavailable">(Out of Stock)</span>' : '' ?>
                </span>
                <input 
                  type="number" 
                  name="<?= $type ?>[<?= $item['component_id'] ?>]" 
                  value="<?= ($item['is_out_of_stock']) ? '0' : '1' ?>" 
                  min="0"
                  class="sbx-quantity-input"
                  readonly
                  <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>
                >
                <span class="sbx-plus <?= ($item['is_out_of_stock']) ? 'disabled' : '' ?>">+</span>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="sbx-add-to-cart">Add to Cart</button>
  </section>
</form>
</div>
  </main>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>


<script>
// Add this function at the top of your script
function formatPrice(number) {
  // Remove any existing peso sign and commas before formatting
  if (typeof number === 'string') {
    number = number.replace(/[₱,]/g, '');
  }
  // Handle NaN, undefined, or invalid numbers
  number = parseFloat(number) || 0;
  return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Function to update button states based on selections
function updateButtonStates() {
  const hasSelectedItems = $('.item-checkbox:checked').length > 0;
  const hasItems = $('.cart-item').length > 0;
  
  // Enable/disable delete button based on selections
  $('#deleteSelectedBtn').prop('disabled', !hasSelectedItems);
  
  // Always enable checkout if there are items
  $('.btn-checkout').prop('disabled', false);
  
  // Update select all checkbox
  const totalCheckboxes = $('.item-checkbox').length;
  const checkedCheckboxes = $('.item-checkbox:checked').length;
  if (totalCheckboxes > 0) {
    $('#selectAllItems').prop('checked', totalCheckboxes === checkedCheckboxes);
  }
}

// Function to bind cart action events
function bindCartEvents() {
  // Bind checkout button click handler
  $('.btn-checkout').off('click').on('click', function(e) {
    e.preventDefault();
    
    // Get selected items
    const selectedItems = $('.item-checkbox:checked');
    
    // Check if any items are selected when checkboxes exist
    if ($('.item-checkbox').length > 0 && selectedItems.length === 0) {
      $.toast({
        heading: 'Selection Required',
        text: 'Please select at least one item to checkout.',
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
          // If items are selected, pass them as query parameter
          let checkoutUrl = '/alon_at_araw/dashboard/customer/checkout.php';
          if (selectedItems.length > 0) {
            const selectedIds = selectedItems.map(function() {
              return $(this).data('id');
            }).get();
            checkoutUrl += '?items=' + selectedIds.join(',');
          }
          // Proceed to checkout
          window.location.href = checkoutUrl;
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
  });

  // Bind delete selected items functionality
  $('#deleteSelectedBtn').off('click').on('click', function() {
    if ($('.item-checkbox:checked').length > 0) {
      $('#clearCartModal').css('display', 'flex');
    }
  });

  // Update button states when checkboxes change
  $(document).off('change', '.item-checkbox').on('change', '.item-checkbox', function() {
    updateButtonStates();
  });

  // Select All functionality
  $('#selectAllItems').off('change').on('change', function() {
    const isChecked = $(this).prop('checked');
    $('.item-checkbox').prop('checked', isChecked);
    updateButtonStates();
  });

  // Cancel delete
  $('#cancelClearCart').off('click').on('click', function() {
    $('#clearCartModal').css('display', 'none');
  });

  // Confirm delete selected items
  $('#confirmClearCart').off('click').on('click', function() {
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
          
          // Clean and format the total price
          const cleanTotal = formatPrice(data.cart_total_price);
          $('.cart-footer span:last-child').text(`₱${cleanTotal}`);
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
          
          // Update button states after deletion
          updateButtonStates();
          
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
      },
      error: function() {
        $.toast({
          heading: 'Error',
          text: 'Failed to delete items. Please try again.',
          icon: 'error',
          position: 'bottom-left',
          hideAfter: 3000,
          stack: false
        });
      }
    });
  });
}

// Call bindCartEvents and updateButtonStates on page load and when returning to the page
$(document).ready(function() {
  bindCartEvents();
  updateButtonStates();
});

// Handle visibility change (when user returns to the page)
document.addEventListener('visibilitychange', function() {
  if (!document.hidden) {
    bindCartEvents();
    updateButtonStates();
  }
});

// Handle Add to Cart form with AJAX
$('form').on('submit', function(e) {
  e.preventDefault();
  const form = $(this);

  // Validate cup size
  if (!$('input[name="cup_size"]:checked').length) {
    $.toast({
      heading: 'Selection Required',
      text: 'Please select a cup size.',
      icon: 'error',
      position: 'top-right',
      hideAfter: 3000,
      stack: false
    });
    return;
  }

  // Validate at least one addon or flavor quantity > 0
  let addonsSelected = false;
  $('.sbx-quantity-input').each(function() {
    if (parseInt($(this).val(), 10) > 0) {
      addonsSelected = true;
      return false;
    }
  });

  if (!addonsSelected) {
    $.toast({
      heading: 'Selection Required',
      text: 'Please select at least one add-on or flavor.',
      icon: 'error',
      position: 'top-right',
      hideAfter: 2000,
      stack: false
    });
    return;
  }

  // All good, proceed with AJAX submit
  const formData = form.serialize();

  $.post('/alon_at_araw/dashboard/customer/cart/add-to-cart.php', formData)
    .done(function(response) {
      try {
        // Parse response if it's a string
        if (typeof response === 'string') {
          response = JSON.parse(response);
        }

        if (response.success) {
          $.toast({
            heading: 'Added to Cart',
            text: response.message || 'Item added to cart successfully.',
            icon: 'success',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });

          // Reset form
          form.trigger('reset');
          form.find('.sbx-quantity-input').val(1);
          form.find('.sbx-cup-option').removeClass('selected');
          form.find('input[name="cup_size"]').prop('checked', false);
          
          // Update cart badge - safely handle undefined
          if (response.totalQuantity !== undefined) {
            $('#cartCount').text(response.totalQuantity);
          }
          
          // Parse the cart HTML response if it exists
          if (response.cartHtml) {
            const $cartHtml = $(response.cartHtml);
            $('#cartSidebarContent').html($cartHtml.find('#cartSidebarContent').html());
          }

          // Update cart header if totalQuantity exists
          if (response.totalQuantity !== undefined) {
            $('.cart-header h2').text(`Your Cart (${response.totalQuantity})`);
          }

          // Safely handle price formatting
          if (response.totalPrice) {
            const totalPrice = formatPrice(response.totalPrice);
            $('.cart-footer span:last-child').text(`₱${totalPrice}`);
          }
          
          // Update cart actions
          if (response.totalQuantity > 0) {
            $('.cart-actions').html(`
              <button class="btn-checkout">Proceed to Checkout</button>
              <button id="deleteSelectedBtn" disabled>Delete Selected Items</button>
            `);
            // Rebind events and update states after updating cart actions
            bindCartEvents();
            updateButtonStates();
          } else {
            $('.cart-actions').empty();
          }
        } else if (response.error === 'not_logged_in') {
          // Handle not logged in case
          $.toast({
            heading: 'Login Required',
            text: 'Please login to add items to your cart.',
            icon: 'warning',
            position: 'bottom-left',
            hideAfter: 3000,
            stack: false
          });
          setTimeout(function() {
            window.location.href = '/alon_at_araw/auth/login.php';
          }, 2000);
        } else {
          // Handle other errors
          $.toast({
            heading: 'Error',
            text: response.message || 'Something went wrong.',
            icon: 'error',
            position: 'bottom-left',
            hideAfter: 2000,
            stack: false
          });
        }
      } catch (e) {
        console.error('Error processing response:', e);
        $.toast({
          heading: 'Error',
          text: 'Failed to process the response. Please try again.',
          icon: 'error',
          position: 'bottom-left',
          hideAfter: 3000,
          stack: false
        });
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      console.error('AJAX Error:', textStatus, errorThrown);
      $.toast({
        heading: 'Error',
        text: 'Failed to add item to cart. Please try again.',
        icon: 'error',
        position: 'bottom-left',
        hideAfter: 3000,
        stack: false
      });
    });
});

document.querySelectorAll('.sbx-addons-flavors').forEach(container => {
  // Skip if the container is disabled
  if (container.classList.contains('disabled')) return;

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
