<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Fetch user's completed orders with pagination
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of completed orders
$total_records = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND payment_status = 'paid' AND order_status = 'completed'");
$total_records->execute([$_SESSION['user_id']]);
$total_records = $total_records->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch completed orders
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    AND o.payment_status = 'paid' 
    AND o.order_status = 'completed'
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
    <title>Order History | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/my-purchases.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        /* Additional styles specific to order history */
        .order-history-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .order-history-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .order-history-header .subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .completed-badge {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .completed-badge i {
            font-size: 0.8rem;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-date i {
            color: #95a5a6;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 2rem 0;
        }

        .no-orders i {
            font-size: 3rem;
            color: #95a5a6;
            margin-bottom: 1rem;
        }

        .no-orders h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .no-orders p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
        }

        .shop-now-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .shop-now-btn:hover {
            background-color: #0056b3;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-info h3 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-info h3 i {
            color: #3498db;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        .detail-row:not(:last-child) {
            border-bottom: 1px solid #f8f9fa;
        }

        .label {
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .label i {
            color: #95a5a6;
            width: 20px;
            text-align: center;
        }

        .value {
            color: #2c3e50;
            font-weight: 500;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-badge.payment-paid {
            background-color: #28a745;
            color: white;
        }

        .view-details-btn {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
        }

        .view-details-btn:hover {
            background-color: #0056b3;
        }

        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: white;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid #dee2e6;
        }

        .page-link:hover {
            background: #f8f9fa;
            border-color: #007bff;
            color: #007bff;
        }

        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .page-ellipsis {
            color: #6c757d;
            padding: 0 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/customer-header.php'; ?>

    <main class="purchases-page">
        <div class="purchases-container">
            <div class="order-history-header">
                <h1>Order History</h1>
                <p class="subtitle">View your completed orders and past purchases</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-history"></i>
                    <h2>No completed orders yet</h2>
                    <p>Your completed orders will appear here</p>
                    <a href="menus.php" class="shop-now-btn">
                        <i class="fas fa-shopping-bag"></i>
                        Browse Menu
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
                                <div class="completed-badge">
                                    <i class="fas fa-check-circle"></i>
                                    Completed
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