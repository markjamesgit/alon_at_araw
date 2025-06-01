<?php
include '../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Enable error logging
    error_log("Starting order process for user: " . $_SESSION['user_id']);

    // Generate order number (format: ORD-YYYYMMDD-XXXX)
    $order_number = 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));

    // Insert into orders table
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, 
            order_number, 
            total_amount, 
            payment_method, 
            delivery_method, 
            delivery_address, 
            contact_number, 
            special_instructions
        ) VALUES (
            :user_id,
            :order_number,
            :total_amount,
            :payment_method,
            :delivery_method,
            :delivery_address,
            :contact_number,
            :special_instructions
        )
    ");

    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'order_number' => $order_number,
        'total_amount' => $_POST['total_amount'],
        'payment_method' => $_POST['payment_method'],
        'delivery_method' => $_POST['delivery_method'],
        'delivery_address' => $_POST['delivery_method'] === 'delivery' ? $_POST['delivery_address'] : null,
        'contact_number' => $_POST['contact_number'],
        'special_instructions' => $_POST['special_instructions']
    ]);

    $order_id = $conn->lastInsertId();
    error_log("Created order with ID: " . $order_id);

    // Get selected cart items from session
    if (!isset($_SESSION['checkout_items']) || empty($_SESSION['checkout_items'])) {
        throw new Exception("No items selected for checkout");
    }

    // Create placeholders for IN clause
    $placeholders = str_repeat('?,', count($_SESSION['checkout_items']) - 1) . '?';
    
    // Get only selected cart items
    $stmt = $conn->prepare("
        SELECT c.*, p.product_name 
        FROM cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
    ");
    
    // Combine user_id with selected cart IDs
    $params = array_merge([$_SESSION['user_id']], $_SESSION['checkout_items']);
    $stmt->execute($params);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($cart_items) . " selected items for checkout");

    // Clear checkout items from session after fetching
    unset($_SESSION['checkout_items']);

    // Insert cart items into order_items and deduct quantities
    $stmt = $conn->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            quantity,
            unit_price,
            subtotal,
            selected_cup_size,
            selected_addons,
            selected_flavors
        ) VALUES (
            :order_id,
            :product_id,
            :quantity,
            :unit_price,
            :subtotal,
            :selected_cup_size,
            :selected_addons,
            :selected_flavors
        )
    ");

    foreach ($cart_items as $item) {
        error_log("Processing item: " . $item['product_name']);

        // First check all quantities before making any deductions
        $insufficient_stock = false;
        $error_message = '';

        // Check cup size stock
        if (!empty($item['selected_cup_size'])) {
            // Check global stock
            $check_stmt = $conn->prepare("
                SELECT quantity FROM cup_sizes 
                WHERE cup_size_id = ? AND quantity >= ?
            ");
            $check_stmt->execute([$item['selected_cup_size'], $item['quantity']]);
            if (!$check_stmt->fetch()) {
                $insufficient_stock = true;
                $error_message = "Insufficient global stock for cup size in {$item['product_name']}";
                error_log($error_message);
                break;
            }

            // Check product-specific stock
            $check_stmt = $conn->prepare("
                SELECT quantity FROM product_components 
                WHERE product_id = ? 
                AND component_type = 'cup_sizes' 
                AND component_id = ? 
                AND quantity >= ?
            ");
            $check_stmt->execute([$item['product_id'], $item['selected_cup_size'], $item['quantity']]);
            if (!$check_stmt->fetch()) {
                $insufficient_stock = true;
                $error_message = "Insufficient product-specific stock for cup size in {$item['product_name']}";
                error_log($error_message);
                break;
            }
        }

        // Check addons stock
        if (!empty($item['selected_addons'])) {
            $addons = json_decode($item['selected_addons'], true);
            foreach ($addons as $addon_id => $addon_qty) {
                $total_addon_qty = $addon_qty * $item['quantity'];
                
                // Check global stock
                $check_stmt = $conn->prepare("
                    SELECT quantity FROM addons 
                    WHERE addon_id = ? AND quantity >= ?
                ");
                $check_stmt->execute([$addon_id, $total_addon_qty]);
                if (!$check_stmt->fetch()) {
                    $insufficient_stock = true;
                    $error_message = "Insufficient global stock for add-on in {$item['product_name']}";
                    error_log($error_message);
                    break 2;
                }

                // Check product-specific stock
                $check_stmt = $conn->prepare("
                    SELECT quantity FROM product_components 
                    WHERE product_id = ? 
                    AND component_type = 'addons' 
                    AND component_id = ? 
                    AND quantity >= ?
                ");
                $check_stmt->execute([$item['product_id'], $addon_id, $total_addon_qty]);
                if (!$check_stmt->fetch()) {
                    $insufficient_stock = true;
                    $error_message = "Insufficient product-specific stock for add-on in {$item['product_name']}";
                    error_log($error_message);
                    break 2;
                }
            }
        }

        // Check flavors stock
        if (!empty($item['selected_flavors'])) {
            $flavors = json_decode($item['selected_flavors'], true);
            foreach ($flavors as $flavor_id => $flavor_qty) {
                $total_flavor_qty = $flavor_qty * $item['quantity'];
                
                // Check global stock
                $check_stmt = $conn->prepare("
                    SELECT quantity FROM flavors 
                    WHERE flavor_id = ? AND quantity >= ?
                ");
                $check_stmt->execute([$flavor_id, $total_flavor_qty]);
                if (!$check_stmt->fetch()) {
                    $insufficient_stock = true;
                    $error_message = "Insufficient global stock for flavor in {$item['product_name']}";
                    error_log($error_message);
                    break 2;
                }

                // Check product-specific stock
                $check_stmt = $conn->prepare("
                    SELECT quantity FROM product_components 
                    WHERE product_id = ? 
                    AND component_type = 'flavors' 
                    AND component_id = ? 
                    AND quantity >= ?
                ");
                $check_stmt->execute([$item['product_id'], $flavor_id, $total_flavor_qty]);
                if (!$check_stmt->fetch()) {
                    $insufficient_stock = true;
                    $error_message = "Insufficient product-specific stock for flavor in {$item['product_name']}";
                    error_log($error_message);
                    break 2;
                }
            }
        }

        if ($insufficient_stock) {
            throw new Exception($error_message);
        }

        // If we get here, we know we have enough stock for everything
        error_log("Stock check passed for item: " . $item['product_name']);

        // Now proceed with deductions
        // Deduct cup size quantity
        if (!empty($item['selected_cup_size'])) {
            // Deduct from cup_sizes table
            $cup_stmt = $conn->prepare("
                UPDATE cup_sizes 
                SET quantity = quantity - :order_qty 
                WHERE cup_size_id = :cup_size_id
            ");
            $cup_stmt->execute([
                'order_qty' => $item['quantity'],
                'cup_size_id' => $item['selected_cup_size']
            ]);
            error_log("Deducted {$item['quantity']} from cup_sizes table for cup_size_id: {$item['selected_cup_size']}");

            // Deduct from product_components table
            $pc_cup_stmt = $conn->prepare("
                UPDATE product_components 
                SET quantity = quantity - :order_qty 
                WHERE product_id = :product_id 
                AND component_type = 'cup_sizes' 
                AND component_id = :component_id
                AND quantity >= :order_qty
            ");
            $result = $pc_cup_stmt->execute([
                'order_qty' => $item['quantity'],
                'product_id' => $item['product_id'],
                'component_id' => $item['selected_cup_size']
            ]);
            if (!$result) {
                throw new Exception("Failed to update cup size quantity in product components");
            }
            error_log("Product components cup size update result: Success for product_id: {$item['product_id']}, cup_size_id: {$item['selected_cup_size']}");
        }

        // Deduct addon quantities
        if (!empty($item['selected_addons'])) {
            $addons = json_decode($item['selected_addons'], true);
            foreach ($addons as $addon_id => $addon_qty) {
                $total_addon_qty = $addon_qty * $item['quantity'];
                
                // Deduct from addons table
                $addon_stmt = $conn->prepare("
                    UPDATE addons 
                    SET quantity = quantity - :order_qty 
                    WHERE addon_id = :addon_id
                    AND quantity >= :order_qty
                ");
                $addon_stmt->execute([
                    'order_qty' => $total_addon_qty,
                    'addon_id' => $addon_id
                ]);
                error_log("Deducted {$total_addon_qty} from addons table for addon_id: {$addon_id}");

                // Deduct from product_components table
                $pc_addon_stmt = $conn->prepare("
                    UPDATE product_components 
                    SET quantity = quantity - :order_qty 
                    WHERE product_id = :product_id 
                    AND component_type = 'addons' 
                    AND component_id = :component_id
                    AND quantity >= :order_qty
                ");
                $result = $pc_addon_stmt->execute([
                    'order_qty' => $total_addon_qty,
                    'product_id' => $item['product_id'],
                    'component_id' => $addon_id
                ]);
                if (!$result) {
                    throw new Exception("Failed to update addon quantity in product components");
                }
                error_log("Product components addon update result: Success for product_id: {$item['product_id']}, addon_id: {$addon_id}");
            }
        }

        // Deduct flavor quantities
        if (!empty($item['selected_flavors'])) {
            $flavors = json_decode($item['selected_flavors'], true);
            foreach ($flavors as $flavor_id => $flavor_qty) {
                $total_flavor_qty = $flavor_qty * $item['quantity'];
                
                // Deduct from flavors table
                $flavor_stmt = $conn->prepare("
                    UPDATE flavors 
                    SET quantity = quantity - :order_qty 
                    WHERE flavor_id = :flavor_id
                    AND quantity >= :order_qty
                ");
                $flavor_stmt->execute([
                    'order_qty' => $total_flavor_qty,
                    'flavor_id' => $flavor_id
                ]);
                error_log("Deducted {$total_flavor_qty} from flavors table for flavor_id: {$flavor_id}");

                // Deduct from product_components table
                $pc_flavor_stmt = $conn->prepare("
                    UPDATE product_components 
                    SET quantity = quantity - :order_qty 
                    WHERE product_id = :product_id 
                    AND component_type = 'flavors' 
                    AND component_id = :component_id
                    AND quantity >= :order_qty
                ");
                $result = $pc_flavor_stmt->execute([
                    'order_qty' => $total_flavor_qty,
                    'product_id' => $item['product_id'],
                    'component_id' => $flavor_id
                ]);
                if (!$result) {
                    throw new Exception("Failed to update flavor quantity in product components");
                }
                error_log("Product components flavor update result: Success for product_id: {$item['product_id']}, flavor_id: {$flavor_id}");
            }
        }

        error_log("Successfully deducted quantities for item: " . $item['product_name']);

        // Insert order item
        $stmt->execute([
            'order_id' => $order_id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['quantity'] * $item['unit_price'],
            'selected_cup_size' => $item['selected_cup_size'],
            'selected_addons' => $item['selected_addons'],
            'selected_flavors' => $item['selected_flavors']
        ]);
        error_log("Inserted order item for: " . $item['product_name']);
    }

    // Clear only the ordered items from cart
    $placeholders = str_repeat('?,', count($cart_items) - 1) . '?';
    $cart_ids = array_column($cart_items, 'cart_id');
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
    $params = array_merge([$_SESSION['user_id']], $cart_ids);
    $stmt->execute($params);
    error_log("Cleared ordered items from cart for user: " . $_SESSION['user_id']);

    // Commit transaction
    $conn->commit();
    error_log("Successfully committed transaction for order: " . $order_number);

    // Set success message
    $_SESSION['success_message'] = "Order placed successfully! Your order number is $order_number";
    
    // Redirect to order confirmation page
    header("Location: order-confirmation.php?order_id=$order_id");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Order failed: " . $e->getMessage());
    
    $_SESSION['error_message'] = "Failed to place order: " . $e->getMessage();
    header('Location: checkout.php');
    exit;
} 