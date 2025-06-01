<?php
include '../../config/db.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'admin') {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        // Get current payment status
        $check_stmt = $conn->prepare("SELECT payment_status, order_status FROM orders WHERE order_id = ?");
        $check_stmt->execute([$order_id]);
        $order_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $payment_status = $order_info['payment_status'];
        
        // Validate status change based on payment status
        $status_error = false;
        $valid_statuses = [];
        
        switch($payment_status) {
            case 'pending':
                $valid_statuses = ['pending'];
                break;
            case 'paid':
                $valid_statuses = ['preparing', 'ready_for_pickup', 'completed'];
                break;
            case 'failed':
                $valid_statuses = ['cancelled'];
                break;
        }
        
        if (!in_array($new_status, $valid_statuses)) {
            $status_error = true;
            $_SESSION['toast'] = 'status_error_invalid';
        }
        
        if (!$status_error) {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $order_id]);
            $_SESSION['toast'] = 'status_updated';
        }
        
        header("Location: /alon_at_araw/dashboard/admin/view-orders.php");
        exit;
    }
    
    if (isset($_POST['update_payment'])) {
        $order_id = $_POST['order_id'];
        $new_payment_status = $_POST['new_payment_status'];
        
        // Set appropriate order status based on new payment status
        $new_order_status = 'pending';
        switch($new_payment_status) {
            case 'paid':
                $new_order_status = 'preparing';
                break;
            case 'failed':
                $new_order_status = 'cancelled';
                break;
        }
        
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE order_id = ?");
        $stmt->execute([$new_payment_status, $new_order_status, $order_id]);
        
        $_SESSION['toast'] = 'payment_updated';
        header("Location: /alon_at_araw/dashboard/admin/view-orders.php");
        exit;
    }
}

// Pagination setup
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of records
$total_records = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch orders with customer details
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT :offset, :limit
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $entries_per_page, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/view-orders.css"/>
    <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
    <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<div class="main-container">
    <?php include '../../includes/admin-sidebar.php'; ?>
    <div class="content-wrapper">
        <main class="orders-management">
            <h1>View Orders</h1>
            <p class="subtitle">View and manage customer orders. Update order status and track delivery progress.</p>

            <div class="user-controls">
                <input type="text" 
                       id="searchInput" 
                       class="search-input" 
                       placeholder="Search by order #, customer name or contact..."
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                
                <div class="filter-dropdown">
                    <select id="paymentMethodFilter" class="filter-select">
                        <option value="">All Payment Methods</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                    </select>
                </div>

                <div class="filter-dropdown">
                    <select id="paymentStatusFilter" class="filter-select">
                        <option value="">All Payment Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="filter-dropdown">
                    <select id="orderStatusFilter" class="filter-select">
                        <option value="">All Order Status</option>
                        <option value="pending">Pending</option>
                        <option value="preparing">Preparing</option>
                        <option value="ready_for_pickup">Ready for Pickup</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="filter-dropdown">
                    <select id="deliveryMethodFilter" class="filter-select">
                        <option value="">All Delivery Methods</option>
                        <option value="pickup">Pickup</option>
                        <option value="delivery">Delivery</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <div class="table-controls">
                    <div class="entries-control">
                        <label>Show 
                            <select id="entriesSelect" onchange="changeEntries(this.value)">
                                <option value="10" <?= $entries_per_page == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $entries_per_page == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $entries_per_page == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $entries_per_page == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                            entries
                        </label>
                    </div>
                    <div class="table-info">
                        Showing <?= min(($current_page - 1) * $entries_per_page + 1, $total_records) ?> to 
                        <?= min($current_page * $entries_per_page, $total_records) ?> of <?= $total_records ?> entries
                    </div>
                </div>

                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Delivery Method</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="no-results" style="display: none;">
                            <td colspan="9" class="no-records">
                                No orders found. Try searching again.
                            </td>
                        </tr>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="#" onclick="viewOrderDetails(<?= $order['order_id'] ?>)" class="order-link">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['contact_number']) ?></td>
                                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></td>
                                    <td>
                                        <form method="POST" class="status-form payment-form">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <select name="new_payment_status" 
                                                    onchange="if(handlePaymentStatusChange(this, <?= $order['order_id'] ?>)) this.form.submit()" 
                                                    class="status-select payment-<?= $order['payment_status'] ?>"
                                                    data-payment-status="<?= $order['order_id'] ?>"
                                                    value="<?= $order['payment_status'] ?>">
                                                <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                            </select>
                                            <input type="hidden" name="update_payment" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-form order-form">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <select name="new_status" 
                                                    onchange="if(handleOrderStatusChange(this, <?= $order['order_id'] ?>)) this.form.submit()" 
                                                    class="status-select status-<?= $order['order_status'] ?>"
                                                    data-order-id="<?= $order['order_id'] ?>"
                                                    value="<?= $order['order_status'] ?>">
                                                <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="preparing" <?= $order['order_status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                <option value="ready_for_pickup" <?= $order['order_status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                                                <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td><?= ucfirst($order['delivery_method']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="no-records">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=1&entries=<?= $entries_per_page ?>" class="page-link first">First</a>
                                <a href="?page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>" class="page-link prev">Previous</a>
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
                                <a href="?page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>" class="page-link next">Next</a>
                                <a href="?page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>" class="page-link last">Last</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="orderDetailsContent"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
function changeEntries(value) {
    window.location.href = `?entries=${value}`;
}

function viewOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading state
    content.innerHTML = '<div class="loading">Loading order details...</div>';
    modal.style.display = 'block';
    
    // Fetch order details
    fetch(`get-order-details.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="error">Error loading order details</div>';
        });
}

// Close modal when clicking the X or outside the modal
document.querySelector('.close').onclick = function() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Show toast message if status was updated
<?php if (isset($_SESSION['toast'])): ?>
    $.toast({
        heading: <?php 
            switch ($_SESSION['toast']) {
                case 'status_error_invalid':
                    echo "'Invalid Status Change'";
                    break;
                default:
                    echo "'Success'";
            }
        ?>,
        text: <?php 
            switch ($_SESSION['toast']) {
                case 'status_updated':
                    echo "'Order status has been updated successfully'";
                    break;
                case 'payment_updated':
                    echo "'Payment and order status have been updated successfully'";
                    break;
                case 'status_error_invalid':
                    echo "'Order status cannot be changed. Please update payment status first.'";
                    break;
                default:
                    echo "'Operation completed successfully'";
            }
        ?>,
        showHideTransition: 'slide',
        icon: <?php 
            echo (strpos($_SESSION['toast'], 'error') !== false) ? "'error'" : "'success'";
        ?>,
        position: 'top-right'
    });
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

// Function to get valid order statuses based on payment status
function getValidOrderStatuses(paymentStatus) {
    switch(paymentStatus) {
        case 'pending':
            return ['pending'];
        case 'paid':
            return ['preparing', 'ready_for_pickup', 'completed'];
        case 'failed':
            return ['cancelled'];
        default:
            return ['pending', 'preparing', 'ready_for_pickup', 'completed', 'cancelled'];
    }
}

// Function to update order status options in the table
function updateTableOrderStatus(row, paymentStatus) {
    const orderStatusSelect = row.find('select[name="new_status"]');
    const validStatuses = getValidOrderStatuses(paymentStatus);
    
    // Hide all options first
    orderStatusSelect.find('option').each(function() {
        if (!validStatuses.includes($(this).val())) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });

    // If current value is not valid, set to first valid status
    if (!validStatuses.includes(orderStatusSelect.val())) {
        orderStatusSelect.val(validStatuses[0]);
    }
}

// Function to apply filters and search
function applyFilters() {
    const searchQuery = $('#searchInput').val().toLowerCase();
    const paymentMethod = $('#paymentMethodFilter').val();
    const paymentStatus = $('#paymentStatusFilter').val();
    const orderStatus = $('#orderStatusFilter').val();
    const deliveryMethod = $('#deliveryMethodFilter').val();

    let hasVisibleRows = false;

    $('.orders-table tbody tr:not(.no-results)').each(function() {
        const row = $(this);
        const orderNumber = row.find('td:eq(0)').text().toLowerCase();
        const customerName = row.find('td:eq(1)').text().toLowerCase();
        const contact = row.find('td:eq(2)').text().toLowerCase();
        const rowPaymentMethod = row.find('td:eq(4)').text().toLowerCase();
        const rowPaymentStatus = row.find('td:eq(5) select').val();
        const rowOrderStatus = row.find('td:eq(6) select').val();
        const rowDeliveryMethod = row.find('td:eq(7)').text().toLowerCase();

        const matchesSearch = searchQuery === '' || 
                            orderNumber.includes(searchQuery) || 
                            customerName.includes(searchQuery) || 
                            contact.includes(searchQuery);

        const matchesPaymentMethod = !paymentMethod || rowPaymentMethod === paymentMethod;
        const matchesPaymentStatus = !paymentStatus || rowPaymentStatus === paymentStatus;
        let matchesOrderStatus = true;
        if (orderStatus) {
            // Check if the order status is valid for the current payment status
            const validStatuses = getValidOrderStatuses(rowPaymentStatus);
            matchesOrderStatus = validStatuses.includes(orderStatus) && rowOrderStatus === orderStatus;
        }
        const matchesDeliveryMethod = !deliveryMethod || rowDeliveryMethod === deliveryMethod;

        if (matchesSearch && matchesPaymentMethod && matchesPaymentStatus && 
            matchesOrderStatus && matchesDeliveryMethod) {
            row.show();
            hasVisibleRows = true;
            // Update the order status options based on the row's payment status
            updateTableOrderStatus(row, rowPaymentStatus);
        } else {
            row.hide();
        }
    });

    // Show/hide no results message
    $('.no-results').toggle(!hasVisibleRows);
    
    // Update table info
    updateTableInfo();

    // Update order status filter options based on payment status filter
    const orderStatusFilter = $('#orderStatusFilter');
    const validFilterStatuses = getValidOrderStatuses(paymentStatus);
    
    orderStatusFilter.find('option').each(function() {
        const option = $(this);
        if (option.val() === '') return; // Skip "All Order Status" option
        
        if (!validFilterStatuses.includes(option.val())) {
            option.hide();
        } else {
            option.show();
        }
    });

    // Reset order status filter if current value is not valid
    if (paymentStatus && !validFilterStatuses.includes(orderStatusFilter.val())) {
        orderStatusFilter.val('');
    }
}

// Function to update table info
function updateTableInfo() {
    const visibleRows = $('.orders-table tbody tr:visible:not(.no-results)').length;
    const totalRows = $('.orders-table tbody tr:not(.no-results)').length;
    $('.table-info').text(`Showing ${visibleRows} of ${totalRows} entries`);
}

// Bind events to filters
$('#searchInput').on('input', applyFilters);
$('#paymentMethodFilter, #paymentStatusFilter, #orderStatusFilter, #deliveryMethodFilter').on('change', applyFilters);

// Handle payment status changes in the table
$(document).on('change', 'select[name="new_payment_status"]', function() {
    const row = $(this).closest('tr');
    updateTableOrderStatus(row, $(this).val());
});

// Function to handle order status changes
function handleOrderStatusChange(selectElement, orderId) {
    const paymentStatus = document.querySelector(`[data-payment-status="${orderId}"]`).value;
    const validStatuses = getValidOrderStatuses(paymentStatus);
    
    if (!validStatuses.includes(selectElement.value)) {
        $.toast({
            heading: 'Invalid Status Change',
            text: 'Order status cannot be changed. Please update payment status first.',
            icon: 'error',
            position: 'top-right'
        });
        selectElement.value = validStatuses[0];
        return false;
    }
    return true;
}

// Function to handle payment status changes
function handlePaymentStatusChange(selectElement, orderId) {
    const newPaymentStatus = selectElement.value;
    const orderStatusSelect = document.querySelector(`[data-order-id="${orderId}"]`);
    
    // Update available order statuses
    updateOrderStatusOptions(newPaymentStatus);
    
    return true;
}

// Initialize order status options on page load
document.addEventListener('DOMContentLoaded', function() {
    const paymentSelects = document.querySelectorAll('select[name="new_payment_status"]');
    paymentSelects.forEach(select => {
        const orderId = select.closest('form').querySelector('[name="order_id"]').value;
        const orderStatusSelect = document.querySelector(`[data-order-id="${orderId}"]`);
        if (orderStatusSelect) {
            updateOrderStatusOptions(select.value);
        }
    });
});

$(document).ready(function() {
    // Initialize filters
    applyFilters();

    // Function to update select colors based on value
    function updateSelectColors() {
        // Update payment status colors
        $('select[name="new_payment_status"]').each(function() {
            $(this).removeClass('payment-pending payment-paid payment-failed')
                  .addClass('payment-' + $(this).val());
        });

        // Update order status colors
        $('select[name="new_status"]').each(function() {
            $(this).removeClass('status-pending status-preparing status-ready_for_pickup status-completed status-cancelled')
                  .addClass('status-' + $(this).val());
        });
    }

    // Update colors on page load
    updateSelectColors();

    // Update colors when status changes
    $(document).on('change', 'select[name="new_payment_status"], select[name="new_status"]', function() {
        updateSelectColors();
    });

});
</script>
</body>
</html>

