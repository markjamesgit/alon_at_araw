<?php
session_start();
include '../../../config/db.php';
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate required input
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : null;
$cup_size = isset($_POST['cup_size']) ? (int) $_POST['cup_size'] : null;

if (!$product_id || !$cup_size) {
    echo json_encode(['success' => false, 'message' => 'Missing product or cup size.']);
    exit;
}

// Parse addons and flavors (as associative arrays with component_id => quantity)
$addons = isset($_POST['addons']) && is_array($_POST['addons']) ? $_POST['addons'] : [];
$flavors = isset($_POST['flavors']) && is_array($_POST['flavors']) ? $_POST['flavors'] : [];

// Filter out any zero or negative values
$addons = array_filter($addons, fn($qty) => intval($qty) > 0);
$flavors = array_filter($flavors, fn($qty) => intval($qty) > 0);

// Validate that at least one addon or flavor is selected
if (empty($addons) && empty($flavors)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one add-on or flavor.']);
    exit;
}

// Fetch product price
$stmt = $conn->prepare("SELECT price FROM products WHERE product_id = :product_id");
$stmt->execute(['product_id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$unit_price = $product['price'];
$quantity = 1;

// Store selected components as JSON
$selected_addons = json_encode($addons);
$selected_flavors = json_encode($flavors);

// Insert into cart
$insert = $conn->prepare("
    INSERT INTO cart (
        user_id, product_id, quantity, unit_price, selected_addons, selected_flavors, selected_cup_size
    ) VALUES (
        :user_id, :product_id, :quantity, :unit_price, :selected_addons, :selected_flavors, :cup_size
    )
");

$insert->execute([
    'user_id' => $user_id,
    'product_id' => $product_id,
    'quantity' => $quantity,
    'unit_price' => $unit_price,
    'selected_addons' => $selected_addons,
    'selected_flavors' => $selected_flavors,
    'cup_size' => $cup_size
]);

// Get updated cart count
$countStmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = :user_id");
$countStmt->execute(['user_id' => $user_id]);
$totalQty = (int) $countStmt->fetchColumn();

// Respond with success
echo json_encode([
    'success' => true,
    'message' => 'Product added to cart!',
    'totalQuantity' => $totalQty
]);
exit;
