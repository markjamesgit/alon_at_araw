<?php
require_once '../../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if (!isset($_POST['cart_ids']) || !is_array($_POST['cart_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_ids = $_POST['cart_ids'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Prepare the delete statement
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    $sql = "DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)";
    
    // Combine user_id with cart_ids for the execute array
    $params = array_merge([$user_id], $cart_ids);
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Get updated cart totals
    $sql = "SELECT COUNT(*) as total_quantity, COALESCE(SUM(quantity * unit_price), 0) as total_price 
            FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'cart_total_quantity' => (int)$totals['total_quantity'],
        'cart_total_price' => (float)$totals['total_price']
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete items from cart'
    ]);
} 