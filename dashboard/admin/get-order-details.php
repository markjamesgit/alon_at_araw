<?php
include '../../config/db.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Order ID is required';
    exit;
}

// Fetch order details with customer info
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
");
$stmt->execute([$_GET['order_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('HTTP/1.1 404 Not Found');
    echo 'Order not found';
    exit;
}

// Fetch order items with product details
$stmt = $conn->prepare("
    SELECT oi.*, p.product_name, p.product_image, cs.size_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN cup_sizes cs ON oi.selected_cup_size = cs.cup_size_id
    WHERE oi.order_id = ?
");
$stmt->execute([$_GET['order_id']]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<head>
<link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
<link rel="stylesheet" href="/alon_at_araw/assets/global.css">
<link rel="stylesheet" href="/alon_at_araw/assets/styles/get-order-details.css">
</head>


<div class="order-details-container">
    <h2>Order #<?= htmlspecialchars($order['order_number']) ?></h2>
    
    <div class="order-info">
        <div class="info-section">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
        </div>

        <div class="info-section">
            <h3>Order Status</h3>
            <p><strong>Order Status:</strong> <span class="status-badge status-<?= $order['order_status'] ?>"><?= ucfirst($order['order_status']) ?></span></p>
            <p><strong>Payment Status:</strong> <span class="status-badge payment-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span></p>
            <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?></p>
            <p><strong>Order Date:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
        </div>

        <?php if ($order['delivery_method'] === 'delivery'): ?>
            <div class="info-section">
                <h3>Delivery Information</h3>
                <p><strong>Delivery Address:</strong> <?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($order['special_instructions']): ?>
            <div class="info-section">
                <h3>Special Instructions</h3>
                <p><?= nl2br(htmlspecialchars($order['special_instructions'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="order-items">
        <h3>Order Items</h3>
        <div class="order-items-container">
            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <img src="/alon_at_araw/assets/uploads/products/<?= htmlspecialchars($item['product_image']) ?>" 
                         alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <div class="item-details">
                        <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                        <div class="item-info">
                            <p class="size">Size: <?= htmlspecialchars($item['size_name']) ?></p>
                            
                            <?php if ($item['selected_addons']): ?>
                                <?php 
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
                                ?>
                            <?php endif; ?>

                            <?php if ($item['selected_flavors']): ?>
                                <?php 
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
                                ?>
                            <?php endif; ?>

                            <p class="quantity">Quantity: <?= $item['quantity'] ?></p>
                        </div>
                        <div class="item-price">₱<?= number_format($item['subtotal'], 2) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-total">
            <h3>Total Amount</h3>
            <p class="total-amount">₱<?= number_format($order['total_amount'], 2) ?></p>
        </div>
    </div>
</div>