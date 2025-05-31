<?php
include '../../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'], $_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_id = $_POST['cart_id'];

    // Delete item
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);

    // Recalculate cart summary
    $summary_stmt = $conn->prepare("SELECT SUM(quantity) AS total_qty, SUM(quantity * unit_price) AS total_price FROM cart WHERE user_id = ?");
    $summary_stmt->execute([$user_id]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'cart_total_quantity' => (int)($summary['total_qty'] ?? 0),
        'cart_total_price' => (float)($summary['total_price'] ?? 0)
    ]);
}
?>
