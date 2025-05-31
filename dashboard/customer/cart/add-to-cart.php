<?php
session_start();
include '../../../config/db.php';
header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : null;
$cup_size = isset($_POST['cup_size']) ? (int) $_POST['cup_size'] : null;

if (!$product_id || !$cup_size) {
    echo json_encode(['success' => false, 'message' => 'Missing product or cup size.']);
    exit;
}

$addons = isset($_POST['addons']) && is_array($_POST['addons']) ? $_POST['addons'] : [];
$flavors = isset($_POST['flavors']) && is_array($_POST['flavors']) ? $_POST['flavors'] : [];

$addons = array_filter($addons, fn($qty) => intval($qty) > 0);
$flavors = array_filter($flavors, fn($qty) => intval($qty) > 0);

if (empty($addons) && empty($flavors)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one add-on or flavor.']);
    exit;
}

// Fetch base product price
$stmt = $conn->prepare("SELECT price FROM products WHERE product_id = :product_id");
$stmt->execute(['product_id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$base_price = (float)$product['price'];
$total_price = $base_price;

// Add cup size price
$cup_stmt = $conn->prepare("SELECT price FROM cup_sizes WHERE cup_size_id = :id");
$cup_stmt->execute(['id' => $cup_size]);
$cup_data = $cup_stmt->fetch(PDO::FETCH_ASSOC);
if ($cup_data) {
    $total_price += (float)$cup_data['price'];
}

// Add prices for each addon
if (!empty($addons)) {
    $addon_ids = implode(',', array_map('intval', array_keys($addons)));
    $addon_stmt = $conn->query("SELECT addon_id, price FROM addons WHERE addon_id IN ($addon_ids)");
    while ($row = $addon_stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['addon_id'];
        $price = (float)$row['price'];
        $qty = (int)$addons[$id];
        $total_price += $price * $qty;
    }
}

// Add prices for each flavor
if (!empty($flavors)) {
    $flavor_ids = implode(',', array_map('intval', array_keys($flavors)));
    $flavor_stmt = $conn->query("SELECT flavor_id, price FROM flavors WHERE flavor_id IN ($flavor_ids)");
    while ($row = $flavor_stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['flavor_id'];
        $price = (float)$row['price'];
        $qty = (int)$flavors[$id];
        $total_price += $price * $qty;
    }
}

// Final data
$selected_addons = json_encode($addons);
$selected_flavors = json_encode($flavors);
$quantity = 1;

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
    'unit_price' => $total_price,
    'selected_addons' => $selected_addons,
    'selected_flavors' => $selected_flavors,
    'cup_size' => $cup_size
]);

// Fetch updated cart items for the user — implement this function below
function fetch_cart_items($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT c.cart_id, p.product_name, p.product_image, c.quantity, c.unit_price,
               cs.size_name AS cup_size_name,
               c.selected_addons, c.selected_flavors
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        LEFT JOIN cup_sizes cs ON c.selected_cup_size = cs.cup_size_id
        WHERE c.user_id = :user_id
        ORDER BY c.cart_id DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        // Decode addons and flavors to arrays
        $addons = json_decode($item['selected_addons'], true) ?: [];
        $flavors = json_decode($item['selected_flavors'], true) ?: [];

        // Fetch addon names
        if ($addons) {
            $ids = implode(',', array_map('intval', array_keys($addons)));
            $addonStmt = $conn->query("SELECT addon_id, addon_name FROM addons WHERE addon_id IN ($ids)");
            $names = [];
            while ($row = $addonStmt->fetch(PDO::FETCH_ASSOC)) {
                $names[] = $row['addon_name'] . ($addons[$row['addon_id']] > 1 ? ' x' . $addons[$row['addon_id']] : '');
            }
            $item['addons'] = $names;
        } else {
            $item['addons'] = [];
        }

        // Fetch flavor names
        if ($flavors) {
            $ids = implode(',', array_map('intval', array_keys($flavors)));
            $flavorStmt = $conn->query("SELECT flavor_id, flavor_name FROM flavors WHERE flavor_id IN ($ids)");
            $names = [];
            while ($row = $flavorStmt->fetch(PDO::FETCH_ASSOC)) {
                $names[] = $row['flavor_name'] . ($flavors[$row['flavor_id']] > 1 ? ' x' . $flavors[$row['flavor_id']] : '');
            }
            $item['flavors'] = $names;
        } else {
            $item['flavors'] = [];
        }
    }
    return $items;
}

// Get updated cart items
$cart_items = fetch_cart_items($conn, $user_id);

// Calculate totals
$total_quantity = 0;
$total_price = 0.0;
foreach ($cart_items as $item) {
    $total_quantity += $item['quantity'];
    $total_price += $item['unit_price'] * $item['quantity'];
}

// Render updated cart sidebar HTML with output buffering
ob_start();
include '../../../includes/cart-sidebar.php';  // adjust path if needed
$cartHtml = ob_get_clean();

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart!',
    'totalQuantity' => $total_quantity,
    'cartHtml' => $cartHtml
]);
