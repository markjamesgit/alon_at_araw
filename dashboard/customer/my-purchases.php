<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    
    // Verify order belongs to user and is in a cancellable state
    $check_stmt = $conn->prepare("SELECT order_status, payment_status FROM orders WHERE order_id = ? AND user_id = ?");
    $check_stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order_info && $order_info['payment_status'] === 'pending' && $order_info['order_status'] === 'pending') {
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
        if ($update_stmt->execute([$order_id])) {
            $_SESSION['toast'] = 'order_cancelled';
        } else {
            $_SESSION['toast'] = 'cancel_error';
        }
    } else {
        $_SESSION['toast'] = 'cancel_invalid';
    }
    
    header("Location: /alon_at_araw/dashboard/customer/my-purchases.php");
    exit;
}

// Fetch user's orders with pagination
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of active orders (excluding paid and completed)
$total_records = $conn->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE user_id = ? 
    AND NOT (payment_status = 'paid' AND order_status = 'completed')
");
$total_records->execute([$_SESSION['user_id']]);
$total_records = $total_records->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch active orders (excluding paid and completed)
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    AND NOT (o.payment_status = 'paid' AND o.order_status = 'completed')
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
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
                    <h2>No active orders</h2>
                    <p>Your active orders will appear here. Completed orders can be found in Order History.</p>
                    <div class="action-buttons">
                        <a href="menus.php" class="shop-now-btn">
                            <i class="fas fa-shopping-bag"></i>
                            Shop Now
                        </a>
                        <a href="order-history.php" class="view-history-btn">
                            <i class="fas fa-history"></i>
                            View Order History
                        </a>
                    </div>
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
                                <?php if ($order['payment_status'] === 'pending' && $order['order_status'] === 'pending'): ?>
                                    <form method="POST" class="cancel-order-form" onsubmit="return confirmCancel(event)">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="cancel_order" value="1">
                                        <button type="submit" class="cancel-order-btn">
                                            <i class="fas fa-times-circle"></i>
                                            Cancel Order
                                        </button>
                                    </form>
                                <?php endif; ?>
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

    <script>
    function confirmCancel(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            event.target.submit();
        }
        return false;
    }

    // Show toast messages
    <?php if (isset($_SESSION['toast'])): ?>
        $.toast({
            heading: <?php 
                switch ($_SESSION['toast']) {
                    case 'order_cancelled':
                        echo "'Order Cancelled'";
                        break;
                    case 'cancel_error':
                        echo "'Error'";
                        break;
                    case 'cancel_invalid':
                        echo "'Invalid Action'";
                        break;
                    default:
                        echo "'Notification'";
                }
            ?>,
            text: <?php 
                switch ($_SESSION['toast']) {
                    case 'order_cancelled':
                        echo "'Your order has been cancelled successfully.'";
                        break;
                    case 'cancel_error':
                        echo "'Failed to cancel order. Please try again.'";
                        break;
                    case 'cancel_invalid':
                        echo "'This order cannot be cancelled. Only pending orders can be cancelled.'";
                        break;
                    default:
                        echo "'Operation completed.'";
                }
            ?>,
            showHideTransition: 'slide',
            icon: <?php 
                echo ($_SESSION['toast'] === 'order_cancelled') ? "'success'" : "'error'";
            ?>,
            position: 'top-right'
        });
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
    </script>

    <style>
    /* Add these styles to your existing CSS */
    .order-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .cancel-order-form {
        margin: 0;
    }

    .cancel-order-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: background-color 0.2s;
    }

    .cancel-order-btn:hover {
        background-color: #c82333;
    }

    .cancel-order-btn i {
        font-size: 1rem;
    }

    .view-details-btn {
        background-color: #007bff;
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: background-color 0.2s;
    }

    .view-details-btn:hover {
        background-color: #0056b3;
    }

    .view-details-btn i {
        font-size: 1rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.5rem;
    }

    .view-history-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #6c757d;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .view-history-btn:hover {
        background-color: #5a6268;
    }

    .view-history-btn i {
        font-size: 1rem;
    }

    .no-orders p {
        max-width: 400px;
        margin: 0 auto 1.5rem;
        line-height: 1.5;
    }
    </style>
</body>
</html> 