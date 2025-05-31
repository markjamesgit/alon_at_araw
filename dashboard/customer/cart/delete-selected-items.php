<?php
include '../../../config/db.php';
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
    $conn->beginTransaction();

    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    
    // Delete selected items
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
    $params = array_merge([$user_id], $cart_ids);
    $stmt->execute($params);

    // Get updated cart totals
    $stmt = $conn->prepare("SELECT COUNT(*) as total_quantity, COALESCE(SUM(quantity * unit_price), 0) as total_price FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Selected items removed successfully',
        'cart_total_quantity' => (int)$totals['total_quantity'],
        'cart_total_price' => (float)$totals['total_price']
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error removing items from cart'
    ]);
} 