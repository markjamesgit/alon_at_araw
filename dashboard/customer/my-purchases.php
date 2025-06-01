<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Fetch user's orders with pagination
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of orders
$total_records = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$total_records->execute([$_SESSION['user_id']]);
$total_records = $total_records->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch orders
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
    LIMIT ?, ?
");
$stmt->bindValue(1, $_SESSION['user_id']);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $entries_per_page, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchases | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/my-purchases.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
    <?php include '../../includes/customer-header.php'; ?>

    <main class="purchases-page">
        <div class="purchases-container">
            <h1>My Purchases</h1>
            <p class="subtitle">View your order history and track your orders</p>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>No orders yet</h2>
                    <p>Start shopping to see your orders here</p>
                    <a href="menus.php" class="shop-now-btn">
                        <i class="fas fa-shopping-bag"></i>
                        Shop Now
                    </a>
                </div>
            <?php else: ?>
                <div class="orders-grid">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>
                                        <i class="fas fa-receipt"></i>
                                        Order #<?= htmlspecialchars($order['order_number']) ?>
                                    </h3>
                                    <p class="order-date">
                                        <i class="far fa-clock"></i>
                                        <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="order-status status-<?= $order['order_status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                                </div>
                            </div>

                            <div class="order-details">
                                <div class="detail-row">
                                    <span class="label">
                                        <i class="fas fa-box"></i>
                                        Items
                                    </span>
                                    <span class="value"><?= $order['item_count'] ?> item(s)</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">
                                        <i class="fas fa-tag"></i>
                                        Total Amount
                                    </span>
                                    <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">
                                        <i class="fas fa-credit-card"></i>
                                        Payment Method
                                    </span>
                                    <span class="value"><?= ucfirst($order['payment_method']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">
                                        <i class="fas fa-money-check-alt"></i>
                                        Payment Status
                                    </span>
                                    <span class="value status-badge payment-<?= $order['payment_status'] ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">
                                        <i class="fas fa-truck"></i>
                                        Delivery Method
                                    </span>
                                    <span class="value"><?= ucfirst($order['delivery_method']) ?></span>
                                </div>
                                <?php if ($order['delivery_method'] === 'delivery'): ?>
                                    <div class="detail-row">
                                        <span class="label">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Delivery Address
                                        </span>
                                        <span class="value"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-actions">
                                <a href="order-confirmation.php?order_id=<?= $order['order_id'] ?>" class="view-details-btn">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=1&entries=<?= $entries_per_page ?>" class="page-link first">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>" class="page-link prev">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<span class="page-ellipsis">...</span>';
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = $i === $current_page ? 'active' : '';
                                echo "<a href='?page=$i&entries=$entries_per_page' class='page-link $active_class'>$i</a>";
                            }

                            if ($end_page < $total_pages) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                            ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>" class="page-link next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>" class="page-link last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 