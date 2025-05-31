<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Get selected items from POST data
$selectedItems = json_decode($_POST['selected_items'] ?? '[]', true);

if (empty($selectedItems)) {
    // If no items selected, redirect back to cart
    header('Location: /alon_at_araw/');
    exit;
}

// Convert array to string for SQL IN clause
$placeholders = str_repeat('?,', count($selectedItems) - 1) . '?';

// Fetch only selected cart items
$stmt = $conn->prepare("
    SELECT c.*, p.product_name, p.product_image, cs.size_name, cs.price as size_price
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN cup_sizes cs ON c.selected_cup_size = cs.cup_size_id
    WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
");

// Combine user_id with selected cart IDs for the query
$params = array_merge([$_SESSION['user_id']], $selectedItems);
$stmt->execute($params);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total for selected items only
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['total_price'];
}

// Store selected items in session for process-order.php
$_SESSION['checkout_items'] = $selectedItems;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/checkout.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
</head>
<body>
    <?php include '../../includes/customer-header.php'; ?>

    <main class="checkout-page">
        <div class="checkout-container">
            <h1>Checkout</h1>

            <!-- Order Summary -->
            <section class="order-summary">
                <h2>Order Summary</h2>
                <?php if (empty($cart_items)): ?>
                    <p class="no-items">No items selected for checkout.</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <img src="/alon_at_araw/assets/uploads/products/<?= htmlspecialchars($item['product_image']) ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div class="item-details">
                                <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                                <p class="size">Size: <?= htmlspecialchars($item['size_name']) ?></p>
                                <?php 
                                if ($item['selected_addons']) {
                                    $addons = json_decode($item['selected_addons'], true);
                                    if (!empty($addons)) {
                                        echo '<p class="addons">Add-ons: ';
                                        $addon_names = [];
                                        foreach ($addons as $addon_id => $quantity) {
                                            $stmt = $conn->prepare("SELECT addon_name FROM addons WHERE addon_id = ?");
                                            $stmt->execute([$addon_id]);
                                            $addon = $stmt->fetch();
                                            if ($addon) {
                                                $addon_names[] = $addon['addon_name'] . " (x$quantity)";
                                            }
                                        }
                                        echo htmlspecialchars(implode(', ', $addon_names));
                                        echo '</p>';
                                    }
                                }

                                if ($item['selected_flavors']) {
                                    $flavors = json_decode($item['selected_flavors'], true);
                                    if (!empty($flavors)) {
                                        echo '<p class="flavors">Flavors: ';
                                        $flavor_names = [];
                                        foreach ($flavors as $flavor_id => $quantity) {
                                            $stmt = $conn->prepare("SELECT flavor_name FROM flavors WHERE flavor_id = ?");
                                            $stmt->execute([$flavor_id]);
                                            $flavor = $stmt->fetch();
                                            if ($flavor) {
                                                $flavor_names[] = $flavor['flavor_name'] . " (x$quantity)";
                                            }
                                        }
                                        echo htmlspecialchars(implode(', ', $flavor_names));
                                        echo '</p>';
                                    }
                                }
                                ?>
                                <p class="quantity">Quantity: <?= $item['quantity'] ?></p>
                                <p class="price">₱<?= number_format($item['total_price'], 2) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total">
                        <h3>Total Amount</h3>
                        <p>₱<?= number_format($total_amount, 2) ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!empty($cart_items)): ?>
            <!-- Checkout Form -->
            <form action="process-order.php" method="POST" class="checkout-form">
                <h2>Order Details</h2>
                
                <div class="form-group">
                    <label for="delivery_method">Delivery Method</label>
                    <select name="delivery_method" id="delivery_method" required>
                        <option value="pickup">Pickup</option>
                        <option value="delivery">Delivery</option>
                    </select>
                </div>

                <div class="form-group delivery-address" style="display: none;">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea name="delivery_address" id="delivery_address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" name="contact_number" id="contact_number" required>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="special_instructions">Special Instructions (Optional)</label>
                    <textarea name="special_instructions" id="special_instructions" rows="3"></textarea>
                </div>

                <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
                
                <button type="submit" class="place-order-btn">Place Order</button>
            </form>
            <?php else: ?>
            <div class="no-items-actions">
                <a href="/alon_at_araw/" class="back-to-cart">Back to Cart</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Show/hide delivery address based on delivery method
        document.getElementById('delivery_method').addEventListener('change', function() {
            const deliveryAddress = document.querySelector('.delivery-address');
            const addressInput = document.getElementById('delivery_address');
            
            if (this.value === 'delivery') {
                deliveryAddress.style.display = 'block';
                addressInput.required = true;
            } else {
                deliveryAddress.style.display = 'none';
                addressInput.required = false;
            }
        });
    </script>
</body>
</html> 