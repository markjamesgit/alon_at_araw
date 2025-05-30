<?php
include '../../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'], $_POST['type'], $_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_id = $_POST['cart_id'];
    $type = $_POST['type'];

    // Get current cart item
    $stmt = $conn->prepare("SELECT quantity, unit_price FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        $unit_price = (float)$cart_item['unit_price'];
        $newQty = $type === 'increase' ? $cart_item['quantity'] + 1 : max(1, $cart_item['quantity'] - 1);
        $newTotal = $newQty * $unit_price;

        // Update quantity
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
        $update_stmt->execute([$newQty, $cart_id, $user_id]);

        // Recalculate total cart quantity and price
        $summary_stmt = $conn->prepare("SELECT SUM(quantity) AS total_qty, SUM(quantity * unit_price) AS total_price FROM cart WHERE user_id = ?");
        $summary_stmt->execute([$user_id]);
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'new_quantity' => $newQty,
            'item_total' => $newTotal,
            'cart_total_quantity' => (int)$summary['total_qty'],
            'cart_total_price' => (float)$summary['total_price']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    }
}
?>
