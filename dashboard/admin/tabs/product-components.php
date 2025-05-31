<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db.php';

// Fetch products for the dropdown
$products = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_id")->fetchAll(PDO::FETCH_ASSOC);

// Add active column if it doesn't exist
try {
    $conn->query("ALTER TABLE product_components ADD COLUMN IF NOT EXISTS active TINYINT(1) DEFAULT 1 AFTER quantity");
} catch (PDOException $e) {
    // Column might already exist, continue
}

// Function to check component stock and update active status
function updateComponentActiveStatus($conn) {
    // Get all product components
    $components = $conn->query("SELECT * FROM product_components")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($components as $component) {
        $table = '';
        $id_col = '';
        
        switch ($component['component_type']) {
            case 'cup_sizes':
                $table = 'cup_sizes';
                $id_col = 'cup_size_id';
                break;
            case 'flavors':
                $table = 'flavors';
                $id_col = 'flavor_id';
                break;
            case 'addons':
                $table = 'addons';
                $id_col = 'addon_id';
                break;
        }
        
        if ($table && $id_col) {
            // Check stock quantity
            $stmt = $conn->prepare("SELECT quantity FROM $table WHERE $id_col = ?");
            $stmt->execute([$component['component_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update active status based on stock
            $active = ($result && $result['quantity'] > 0) ? 1 : 0;
            $conn->prepare("UPDATE product_components SET active = ? WHERE id = ?")
                 ->execute([$active, $component['id']]);
        }
    }
}

// Update active status for all components
updateComponentActiveStatus($conn);

// Fetch component IDs based on component type
$component_types = ['cup_sizes', 'flavors', 'addons'];
$components = [];

// Handle AJAX request to fetch component IDs
if (isset($_GET['component_type'])) {
    $component_type = $_GET['component_type'];
    $column_map = [
        'cup_sizes' => ['id' => 'cup_size_id', 'name' => 'size_name', 'quantity' => 'quantity'],
        'flavors' => ['id' => 'flavor_id', 'name' => 'flavor_name', 'quantity' => 'quantity'],
        'addons' => ['id' => 'addon_id', 'name' => 'addon_name', 'quantity' => 'quantity'],
    ];

    if (array_key_exists($component_type, $column_map)) {
        $id_col = $column_map[$component_type]['id'];
        $name_col = $column_map[$component_type]['name'];
        $qty_col = $column_map[$component_type]['quantity'];
        $stmt = $conn->prepare("SELECT $id_col AS id, $name_col AS name, $qty_col AS quantity FROM $component_type ORDER BY $id_col");
        $stmt->execute();
        $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($components);
        exit;
    }
}


if (isset($_POST['add_product_component'])) {
    $product_id = trim($_POST['product_id']);
    $component_type = trim($_POST['component_type']);
    $component_id = trim($_POST['component_id']);
    $quantity = trim($_POST['quantity']) ?: 1; 

    // Validate quantity against available stock
    $column_map = [
        'cup_sizes' => ['table' => 'cup_sizes', 'id' => 'cup_size_id'],
        'flavors' => ['table' => 'flavors', 'id' => 'flavor_id'],
        'addons' => ['table' => 'addons', 'id' => 'addon_id'],
    ];

    if (isset($column_map[$component_type])) {
        $table = $column_map[$component_type]['table'];
        $id_col = $column_map[$component_type]['id'];
        
        // Check available quantity
        $stmt = $conn->prepare("SELECT quantity FROM $table WHERE $id_col = ?");
        $stmt->execute([$component_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['quantity'] < $quantity) {
            $_SESSION['toast'] = 'insufficient_stock';
            header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
            exit;
        }

        // Set initial active status based on stock
        $active = ($result && $result['quantity'] > 0) ? 1 : 0;
    }

    $stmt = $conn->prepare("INSERT INTO product_components (product_id, component_type, component_id, quantity, active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$product_id, $component_type, $component_id, $quantity, $active]);

    $_SESSION['toast'] = 'added';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
    exit;
}

if (isset($_POST['edit_product_component'])) {
    $id = $_POST['edit_id'];
    $product_id = trim($_POST['edit_product_id']);
    $component_type = trim($_POST['edit_component_type']);
    $component_id = trim($_POST['edit_component_id']);
    $quantity = trim($_POST['edit_quantity']) ?: 1; 

    // Validate quantity against available stock
    $column_map = [
        'cup_sizes' => ['table' => 'cup_sizes', 'id' => 'cup_size_id'],
        'flavors' => ['table' => 'flavors', 'id' => 'flavor_id'],
        'addons' => ['table' => 'addons', 'id' => 'addon_id'],
    ];

    if (isset($column_map[$component_type])) {
        $table = $column_map[$component_type]['table'];
        $id_col = $column_map[$component_type]['id'];
        
        // Check available quantity
        $stmt = $conn->prepare("SELECT quantity FROM $table WHERE $id_col = ?");
        $stmt->execute([$component_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['quantity'] < $quantity) {
            $_SESSION['toast'] = 'insufficient_stock';
            header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE product_components SET product_id = ?, component_type = ?, component_id = ?, quantity = ? WHERE id = ?");
    $stmt->execute([$product_id, $component_type, $component_id, $quantity, $id]);

    $_SESSION['toast'] = 'edited';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
    exit;
}

if (isset($_POST['delete_single'])) {
    $stmt = $conn->prepare("DELETE FROM product_components WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);

    $_SESSION['toast'] = 'deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
    exit;
}

if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    $conn->query("DELETE FROM product_components WHERE id IN ($ids)");

    $_SESSION['toast'] = 'multiple_deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=product-components");
    exit;
}

// Pagination settings
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of records
$total_records = $conn->query("SELECT COUNT(*) FROM product_components")->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch product components with pagination and global quantities
$product_components = $conn->query("
    SELECT 
        pc.*,
        CASE 
            WHEN pc.component_type = 'addons' THEN a.quantity
            WHEN pc.component_type = 'flavors' THEN f.quantity
            WHEN pc.component_type = 'cup_sizes' THEN cs.quantity
        END AS global_quantity
    FROM product_components pc
    LEFT JOIN addons a ON pc.component_type = 'addons' AND pc.component_id = a.addon_id
    LEFT JOIN flavors f ON pc.component_type = 'flavors' AND pc.component_id = f.flavor_id
    LEFT JOIN cup_sizes cs ON pc.component_type = 'cup_sizes' AND pc.component_id = cs.cup_size_id
    ORDER BY pc.id DESC 
    LIMIT $offset, $entries_per_page
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch product names for mapping
$product_map = [];
$products = $conn->query("SELECT product_id, product_name FROM products")->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as $p) {
    $product_map[$p['product_id']] = $p['product_name'];
}

// Fetch component names for mapping
function getComponentMap($conn, $table, $id_col, $name_col) {
    $stmt = $conn->prepare("SELECT $id_col AS id, $name_col AS name FROM $table");
    $stmt->execute();
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['id']] = $row['name'];
    }
    return $map;
}

$cup_size_map = getComponentMap($conn, 'cup_sizes', 'cup_size_id', 'size_name');
$flavor_map = getComponentMap($conn, 'flavors', 'flavor_id', 'flavor_name');
$addon_map = getComponentMap($conn, 'addons', 'addon_id', 'addon_name');

// Function to get stock status badge
function getStockStatusBadge($productQty, $globalQty) {
    if ($productQty <= 0) {
        return '<span class="status-badge danger">Out of Stock for this Product</span>';
    } elseif ($globalQty <= 0) {
        return '<span class="status-badge warning">Out of Stock (Global)</span>';
    }
    return '<span class="status-badge success">In Stock</span>';
}

// Add CSS for status badges
echo '
<style>
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
    margin: 2px;
}
.status-badge.success {
    background-color: #28a745;
    color: white;
}
.status-badge.danger {
    background-color: #dc3545;
    color: white;
}
.status-badge.warning {
    background-color: #ffc107;
    color: black;
}
</style>
';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Product Components - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/product-components.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<body>
<?php include __DIR__ . '/../../../includes/admin-sidebar.php'; ?>

<main class="users-management">

  <!-- Add Product Component Form -->
  <div class="category-form-wrapper">
    <form method="POST" class="category-form">
      <div class="input-container">
        <label for="product-id">Product</label>
        <select id="product-id" name="product_id" required>
          <option value="">Select a product</option>
          <?php foreach ($products as $product): ?>
            <option value="<?= $product['product_id'] ?>"><?= htmlspecialchars($product['product_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="input-container">
        <label for="component-type">Component Type</label>
        <select id="component-type" name="component_type" required>
          <option value="">Select a component type</option>
          <?php foreach ($component_types as $type): ?>
            <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

       <div class="input-container">
            <label for="component-id">Component ID</label>
            <select id="component-id" name="component_id" required>
                <option value="">Select a component</option>
            </select>
          </div>

      <div class="input-container">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" placeholder="Enter quantity" required min="1" />
      </div>

      <button type="submit" name="add_product_component" class="md-btn md-btn-primary">Add Product Component</button>
    </form>
  </div>

  <div class="user-controls">
    <input type="text" id="searchInput" placeholder="Search product components..." class="search-input" />

    <div class="filter-dropdown">
    <select id="filterSelect" class="filter-select">
        <option value="all">All Product Components</option>
        <option value="active">Active Components</option>
        <option value="disabled">Disabled Components</option>
    </select>
    </div>
  </div>

  <!-- Bulk Delete -->
  <form method="POST">
    <button type="button" id="triggerBulkDelete" class="md-btn danger">Delete Selected</button>
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
      <table class="user-table">
        <thead>
          <tr>
            <th><label class="md-checkbox"><input type="checkbox" id="select-all" /><span></span></label></th>
            <th>#</th>
            <th>Product Name</th>
            <th>Component Type</th>
            <th>Component Name</th>
            <th>Product Stock</th>
            <th>Global Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($product_components) > 0): ?>
            <?php foreach ($product_components as $component): ?>
              <?php
                    $isOutOfStock = $component['quantity'] <= 0 || $component['global_quantity'] <= 0;
                    $rowClass = $isOutOfStock ? 'out-of-stock' : '';
                ?>
              <tr style="background-color: <?= $rowClass === 'out-of-stock' ? '#f0f0f0' : '' ?>" class="<?= $rowClass ?>">
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $component['id'] ?>" <?= $isOutOfStock ? 'disabled' : '' ?> /><span></span>
                  </label>
                </td>
                <td><?= $component['id'] ?></td>
                <td><?= htmlspecialchars($product_map[$component['product_id']] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($component['component_type']) ?></td>
                    <td>
                        <?php
                            switch ($component['component_type']) {
                                case 'cup_sizes':
                                    echo htmlspecialchars($cup_size_map[$component['component_id']] ?? 'Unknown');
                                    break;
                                case 'flavors':
                                    echo htmlspecialchars($flavor_map[$component['component_id']] ?? 'Unknown');
                                    break;
                                case 'addons':
                                    echo htmlspecialchars($addon_map[$component['component_id']] ?? 'Unknown');
                                    break;
                                default:
                                    echo 'Unknown';
                            }
                        ?>
                    </td>
                <td><?= htmlspecialchars($component['quantity']) ?></td>
                <td><?= htmlspecialchars($component['global_quantity']) ?></td>
                <td><?= getStockStatusBadge($component['quantity'], $component['global_quantity']) ?></td>
                <td>
                   <?php if ($isOutOfStock): ?>
                     <span class="status-badge disabled">Disabled (Out of Stock)</span>
                   <?php else: ?>  
                  <button
                    type="button"
                    class="custom-edit-btn edit-btn"
                    data-id="<?= $component['id'] ?>"
                    data-product-id="<?= $component['product_id'] ?>"
                    data-component-type="<?= htmlspecialchars($component['component_type'], ENT_QUOTES) ?>"
                    data-component-id="<?= $component['component_id'] ?>"
                    data-quantity="<?= $component['quantity'] ?>"
                  >
                    <i class="fas fa-pen"></i> Edit
                  </button>
                  <?php endif; ?> 
                  <button
                    type="button"
                    class="custom-delete-btn delete-product-component"
                    data-id="<?= $component['id'] ?>"
                  >
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center">
                <p class="no-data-message">No product components found.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <?php if ($total_pages > 1): ?>
  <div class="pagination-container">
      <div class="pagination">
          <?php if ($current_page > 1): ?>
              <a href="?tab=product-components&page=1&entries=<?= $entries_per_page ?>" class="page-link first">
                  <i class="fas fa-angle-double-left"></i>
              </a>
              <a href="?tab=product-components&page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>" class="page-link prev">
                  <i class="fas fa-angle-left"></i>
              </a>
          <?php endif; ?>

          <?php
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $current_page + 2);

          if ($start_page > 1) {
              echo '<span class="page-ellipsis">...</span>';
          }

          for ($i = $start_page; $i <= $end_page; $i++):
          ?>
              <a href="?tab=product-components&page=<?= $i ?>&entries=<?= $entries_per_page ?>" 
                 class="page-link <?= $i === $current_page ? 'active' : '' ?>">
                  <?= $i ?>
              </a>
          <?php endfor; ?>

          <?php if ($end_page < $total_pages): ?>
              <span class="page-ellipsis">...</span>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
              <a href="?tab=product-components&page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>" class="page-link next">
                  <i class="fas fa-angle-right"></i>
              </a>
              <a href="?tab=product-components&page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>" class="page-link last">
                  <i class="fas fa-angle-double-right"></i>
              </a>
          <?php endif; ?>
      </div>
  </div>
  <?php endif; ?>
</main>

<!-- Edit Modal -->
<div id="unblockModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Edit Product Component</h2>
    <form id="editForm" method="POST" class="modal-form">
      <input type="hidden" id="edit_id" name="edit_id" />

      <div class="input-container">
        <label for="edit_product_id">Product</label>
        <select id="edit_product_id" name="edit_product_id" required>
          <option value="">Select a product</option>
          <?php foreach ($products as $product): ?>
            <option value="<?= $product['product_id'] ?>"><?= htmlspecialchars($product['product_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="input-container">
        <label for="edit_component_type">Component Type</label>
        <select id="edit_component_type" name="edit_component_type" required>
        <option value="cup_sizes" <?= $component['component_type'] === 'cup_sizes' ? 'selected' : '' ?>>Cup Size</option>
        <option value="flavors" <?= $component['component_type'] === 'flavors' ? 'selected' : '' ?>>Flavor</option>
        <option value="addons" <?= $component['component_type'] === 'addons' ? 'selected' : '' ?>>Addon</option>
    </select>
      </div>

      <div class="input-container">
        <label for="edit_component_id">Component ID</label>
        <select id="edit_component_id" name="edit_component_id" required>
          <option value="">Select a component</option>
          <?php if (isset($components) && count($components)): ?>
            <?php foreach ($components as $component): ?>
              <option value="<?= $component['id'] ?>"><?= htmlspecialchars($component['name']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="input-container">
        <label for="edit_quantity">Quantity</label>
        <input type="number" id="edit_quantity" name="edit_quantity" placeholder="Enter quantity" required min="1" />
      </div>

      <div class="modal-actions">
        <button type="submit" name="edit_product_component" class="md-btn md-btn-primary">Save Changes</button>
        <button type="button" class="md-btn md-btn-secondary" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Confirm Deletion</h2>
    <form id="deleteForm" method="POST">
      <input type="hidden" name="delete_id" id="delete_id" />
      <p>Are you sure you want to delete this product component?</p>
      <div class="modal-actions">
        <button type="submit" name="delete_single" class="md-btn md-btn-primary">Delete</button>
        <button type="button" id="cancelDelete" class="md-btn md-btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Confirm Bulk Deletion</h2>
    <form id="bulkDeleteForm" method="POST">
      <input type="hidden" name="delete_selected" value="1" />
      <p>Are you sure you want to delete the selected product components?</p>
      <div class="modal-actions">
        <button type="submit" class="md-btn md-btn-primary">Yes, Delete All</button>
        <button type="button" id="cancelBulkDelete" class="md-btn md-btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
  $(document).ready(function () {
    // Toast messages
    <?php if (isset($_SESSION['toast'])): ?>
      let toastMessage = '';
      switch ('<?= $_SESSION['toast'] ?>') {
        case 'added':
          toastMessage = 'Product component added.';
          break;
        case 'edited':
          toastMessage = 'Product component updated.';
          break;
        case 'deleted':
          toastMessage = 'Product component deleted.';
          break;
        case 'multiple_deleted':
          toastMessage = 'Selected product components deleted.';
          break;
        case 'insufficient_stock':
          toastMessage = 'Insufficient stock available for the selected quantity.';
          break;
      }
      $.toast({
        heading: toastMessage.includes('deleted') ? 'Deleted' : (toastMessage.includes('Insufficient') ? 'Error' : 'Success'),
        text: toastMessage,
        icon: toastMessage.includes('deleted') ? 'info' : (toastMessage.includes('Insufficient') ? 'error' : 'success'),
        showHideTransition: 'slide',
        position: 'top-right',
        loaderBg: '#5ba035',
      });
      <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    // Fetch component IDs based on selected component type
    $('#component-type').on('change', function () {
      const componentType = $(this).val();
      const componentIdSelect = $('#component-id');
      componentIdSelect.empty().append('<option value="">Select a component</option>'); // Clear previous options
      if (componentType) {
        $.get('/alon_at_araw/dashboard/admin/tabs/product-components.php?component_type=' + componentType, function(data) {
          const components = JSON.parse(data);
          components.forEach(function(component) {
            const disabled = component.quantity <= 0;
            componentIdSelect.append(
              `<option value="${component.id}" ${disabled ? 'disabled' : ''} data-quantity="${component.quantity}">
                ${component.name} ${disabled ? '(Out of Stock)' : '- Stock: ' + component.quantity}
              </option>`
            );
          });
        });
      }
    });

    // Update quantity max value based on available stock
    $('#component-id').on('change', function() {
      const selectedOption = $(this).find('option:selected');
      const availableStock = parseInt(selectedOption.data('quantity'), 10) || 0;
      $('#quantity').attr('max', availableStock);
    });

    // Fill the edit modal with data
    function fillEditModal(id, productId, componentType, componentId, quantity) {
      $('#edit_id').val(id);
      $('#edit_product_id').val(productId);
      $('#edit_component_type').val(componentType);
      $('#edit_quantity').val(quantity);

      // Load components based on the selected component type
      $.get('/alon_at_araw/dashboard/admin/tabs/product-components.php?component_type=' + componentType, function(data) {
        const components = JSON.parse(data);
        const componentIdSelect = $('#edit_component_id');
        componentIdSelect.empty().append('<option value="">Select a component</option>');

        components.forEach(function(component) {
          const disabled = component.quantity <= 0 && component.id != componentId;
          componentIdSelect.append(
            `<option value="${component.id}" ${disabled ? 'disabled' : ''} data-quantity="${component.quantity}" ${component.id == componentId ? 'selected' : ''}>
              ${component.name} ${disabled ? '(Out of Stock)' : '- Stock: ' + component.quantity}
            </option>`
          );
        });

        // Set max quantity for the edit form
        const selectedOption = componentIdSelect.find('option:selected');
        const availableStock = parseInt(selectedOption.data('quantity'), 10) || 0;
        $('#edit_quantity').attr('max', availableStock);
      });

      $('#unblockModal').fadeIn(200);
    }

    // Close modal function
    window.closeModal = function () {
      $('#unblockModal').fadeOut(200);
    };

    // Select All toggle for bulk actions
    $('#select-all').on('change', function () {
      $('input[name="selected_ids[]"]').prop('checked', this.checked);
    });

    // Trigger bulk delete modal
    $('#triggerBulkDelete').on('click', function () {
      const selected = $('input[name="selected_ids[]"]:checked');
      if (selected.length === 0) {
        $.toast({
          heading: 'No Selection',
          text: 'Please select at least one product component to delete.',
          icon: 'warning',
          showHideTransition: 'slide',
          position: 'top-right',
          loaderBg: '#f0ad4e',
        });
        return;
      }

      // Clear and append hidden inputs to the correct form
      const hiddenContainer = $('#bulkDeleteForm');
      hiddenContainer.find('input[name="selected_ids[]"]').remove();

      selected.each(function () {
        hiddenContainer.append(
          $('<input>', {
            type: 'hidden',
            name: 'selected_ids[]',
            value: $(this).val()
          })
        );
      });

      $('#bulkDeleteModal').fadeIn(200);
    });

    // Cancel bulk delete modal
    $('#cancelBulkDelete').on('click', () => $('#bulkDeleteModal').fadeOut(200));

    // Edit button click handler
    $('.edit-btn').on('click', function () {
      const id = $(this).data('id');
      const productId = $(this).data('product-id');
      const componentType = $(this).data('component-type');
      const componentId = $(this).data('component-id');
      const quantity = $(this).data('quantity');

      fillEditModal(id, productId, componentType, componentId, quantity);
    });

    // Delete button click handler
    $('.delete-product-component').on('click', function () {
      const id = $(this).data('id');
      $('#delete_id').val(id);
      $('#deleteModal').fadeIn(200);
    });

    // Cancel delete modal
    $('#cancelDelete').on('click', () => $('#deleteModal').fadeOut(200));

    // Close modal on clicking outside the modal card
    $('#unblockModal').on('click', function (e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Search product components function
    $('#searchInput').on('keyup', function () {
      const search = $(this).val().toLowerCase();
      let found = false;
      $('.user-table tbody tr').each(function () {
        const productId = $(this).find('td:eq(2)').text().toLowerCase();
        const componentType = $(this).find('td:eq(3)').text().toLowerCase();
        const componentId = $(this).find('td:eq(4)').text().toLowerCase();
        const match = productId.includes(search) || componentType.includes(search) || componentId.includes(search);
        $(this).toggle(match);
        if (match) found = true;
      });
      if (!found) {
        $('.user-table tbody').append('<tr class="no-found"><td colspan="9">No product component found. Try searching again.</td></tr>');
      } else {
        $('.user-table tbody .no-found').remove();
      }
    });

    // Stock filter logic
    $('#filterSelect').on('change', function () {
      const filter = $(this).val();
      let found = false;
      let visibleCount = 0;

      $('.user-table tbody tr').each(function () {
        const isDisabled = $(this).hasClass('out-of-stock');
        const showRow =
          filter === 'all' ||
          (filter === 'active' && !isDisabled) ||
          (filter === 'disabled' && isDisabled);

        $(this).toggle(showRow);

        if (showRow) {
          found = true;
          visibleCount++;
        }
      });

      // Update table info
      $('.table-info').text(`Showing ${visibleCount} filtered entries`);

      if (!found) {
        $('.user-table tbody').append('<tr class="no-found"><td colspan="9" class="text-center">No product components found in this category.</td></tr>');
      } else {
        $('.user-table tbody .no-found').remove();
      }
    });

    // Preserve filter and entries when navigating pagination
    document.querySelectorAll('.page-link').forEach(link => {
      link.addEventListener('click', function(e) {
        const filterValue = document.getElementById('filterSelect').value;
        if (filterValue !== 'all') {
          e.preventDefault();
          const url = new URL(this.href);
          url.searchParams.set('filter', filterValue);
          window.location.href = url.toString();
        }
      });
    });
  });

  function changeEntries(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('entries', value);
    urlParams.set('page', 1); // Reset to first page when changing entries
    window.location.href = window.location.pathname + '?' + urlParams.toString();
  }
</script>
</body>
</html>

