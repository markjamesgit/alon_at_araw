<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
");
$stmt->execute([
    'order_id' => $_GET['order_id'],
    'user_id' => $_SESSION['user_id']
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, p.product_name, p.product_image, cs.size_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN cup_sizes cs ON oi.selected_cup_size = cs.cup_size_id
    WHERE oi.order_id = :order_id
");
$stmt->execute(['order_id' => $_GET['order_id']]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/checkout.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
    <style>
        .confirmation-message {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--success-light);
            border-radius: 8px;
            color: var(--success);
        }

        .confirmation-message h2 {
            color: var(--success);
            margin-bottom: 1rem;
        }

        .order-details {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .order-details h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            padding: 1rem;
            background: var(--background);
            border-radius: 6px;
        }

        .detail-item strong {
            display: block;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .back-to-shop {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--primary);
            color: var(--background);
            text-decoration: none;
            border-radius: 8px;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .back-to-shop:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include '../../includes/customer-header.php'; ?>

    <main class="checkout-page">
        <div class="checkout-container">
            <div class="confirmation-message">
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your order. Your order number is: <strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
            </div>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-group">
                    <div class="detail-item">
                        <strong>Order Status</strong>
                        <span><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Payment Method</strong>
                        <span><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Payment Status</strong>
                        <span><?= ucfirst(htmlspecialchars($order['payment_status'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Delivery Method</strong>
                        <span><?= ucfirst(htmlspecialchars($order['delivery_method'])) ?></span>
                    </div>
                </div>

                <?php if ($order['delivery_method'] === 'delivery'): ?>
                    <div class="detail-item">
                        <strong>Delivery Address</strong>
                        <span><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($order['special_instructions']): ?>
                    <div class="detail-item">
                        <strong>Special Instructions</strong>
                        <span><?= nl2br(htmlspecialchars($order['special_instructions'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <section class="order-summary">
                <h3>Order Items</h3>
                <?php foreach ($order_items as $item): ?>
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
                            <p class="price">₱<?= number_format($item['subtotal'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="total">
                    <h3>Total Amount</h3>
                    <p>₱<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </section>

            <div style="text-align: center; grid-column: 1 / -1;">
                <a href="menus.php" class="back-to-shop">Continue Shopping</a>
            </div>
        </div>
    </main>
</body>
</html> 