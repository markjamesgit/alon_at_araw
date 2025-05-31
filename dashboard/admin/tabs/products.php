<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY id")->fetchAll();

// Handle availability toggle
if (isset($_POST['toggle_availability'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE products SET is_available = ? WHERE product_id = ?");
    $stmt->execute([$new_status, $product_id]);
    
    $_SESSION['toast'] = 'availability_updated';
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if (isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category_id = trim($_POST['category_id']);
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;

    $imageName = null;
    if (!empty($_FILES['product_image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['product_image']['name']);
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/alon_at_araw/assets/uploads/products/';
        $targetPath = $targetDir . $imageName;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, product_image, category_id, is_best_seller) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $imageName, $category_id, $is_best_seller]);

    $_SESSION['toast'] = 'added';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=products");
    exit;
}

if (isset($_POST['edit_product'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_product_name']);
    $description = trim($_POST['edit_description']);
    $price = trim($_POST['edit_price']);
    $category_id = trim($_POST['edit_category_id']);
    $is_best_seller = isset($_POST['edit_is_best_seller']) ? 1 : 0;

    $imageName = $_POST['current_image'];

    if (!empty($_FILES['edit_product_image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['edit_product_image']['name']);
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/alon_at_araw/assets/uploads/products/';
        $targetPath = $targetDir . $imageName;
        move_uploaded_file($_FILES['edit_product_image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("UPDATE products SET product_name = ?, description = ?, price = ?, product_image = ?, category_id = ?, is_best_seller = ? WHERE product_id = ?");
    $stmt->execute([$name, $description, $price, $imageName, $category_id, $is_best_seller, $id]);

    $_SESSION['toast'] = 'edited';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=products");
    exit;
}

if (isset($_POST['delete_single'])) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$_POST['delete_id']]);

    $_SESSION['toast'] = 'deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=products");
    exit;
}

if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    $conn->query("DELETE FROM products WHERE product_id IN ($ids)");

    $_SESSION['toast'] = 'multiple_deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=products");
    exit;
}

// Pagination settings
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of records
$total_records = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch products with pagination
$products = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.product_id DESC
    LIMIT $offset, $entries_per_page
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Products - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/products.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include __DIR__ . '/../../../includes/admin-sidebar.php'; ?>

<main class="user-management">

  <!-- Add Product Form -->
  <div class="category-form-wrapper">
    <form method="POST" enctype="multipart/form-data" class="category-form">
      <div class="input-container file-upload">
        <label for="product_image" class="file-label" id="productLabel">
          <i class="fas fa-upload upload-icon"></i>
          Upload Image (Optional)
        </label>
        <input type="file" id="product_image" name="product_image" accept="image/*" />
      </div>

      <div class="input-container">
        <label for="product-name">Product Name</label>
        <input type="text" id="product-name" name="product_name" placeholder="Enter product name" required />
      </div>

      <div class="input-container">
        <label for="description">Description</label>
        <input type="text" id="description" name="description" placeholder="Enter product description" required />
      </div>

      <div class="input-container">
        <label for="price">Price</label>
        <input type="number" id="price" name="price" placeholder="Enter product price" required step="0.01" />
      </div>

      <div class="input-container">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
          <option value="">Select a category</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="input-container">
        <label class="switch-label" for="is_best_seller">Best Seller</label>
        <label class="switch">
          <input type="checkbox" id="is_best_seller" name="is_best_seller" />
          <span class="slider round"></span>
        </label>
      </div>

      <button type="submit" name="add_product" class="md-btn md-btn-primary">Add Product</button>
    </form>

    <div class="image-preview-wrapper">
      <h3>Image Preview</h3>
      <div class="image-preview">
        <img id="preview-img" src="" alt="Image Preview" style="display:none;" />
      </div>
    </div>
  </div>

  <div class="user-controls">
    <input type="text" id="searchInput" placeholder="Search products..." class="search-input" />

    <div class="filter-controls">
      <div class="filter-dropdown">
        <select id="categoryFilter" class="filter-select">
          <option value="all">All Categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $category['name'] ?>"><?= htmlspecialchars($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-dropdown">
        <select id="availabilityFilter" class="filter-select">
          <option value="all">All Status</option>
          <option value="available">Available</option>
          <option value="not_available">Not Available</option>
        </select>
      </div>

      <div class="filter-dropdown">
        <select id="bestSellerFilter" class="filter-select">
          <option value="all">All Products</option>
          <option value="best_seller">Best Sellers</option>
          <option value="not_best_seller">Not Best Sellers</option>
        </select>
      </div>
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
            <th>Image</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Category</th>
            <th>Best Seller</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($products) > 0): ?>
            <?php foreach ($products as $prod): ?>
              <?php 
                $isAvailable = $prod['is_available'] ?? true;
                $rowClass = !$isAvailable ? 'unavailable-product' : '';
              ?>
              <tr class="<?= $rowClass ?>">
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $prod['product_id'] ?>" <?= !$isAvailable ? 'disabled' : '' ?> />
                    <span></span>
                  </label>
                </td>
                <td><?= $prod['product_id'] ?></td>
                <td>
                  <?php
                    $rawImage = $prod['product_image'] ?? '';
                    $cleanImage = preg_replace('#^(\.\./)+#', '', $rawImage);
                    $productImagePath = $cleanImage
                      ? '/alon_at_araw/assets/uploads/products/' . $cleanImage
                      : '/alon_at_araw/assets/images/no-image.png';
                  ?>
                  <img src="<?= htmlspecialchars($productImagePath) ?>" class="profile-pic" alt="Product Image" />
                </td>
                <td><?= htmlspecialchars($prod['product_name']) ?></td>
                <td><?= htmlspecialchars($prod['description']) ?></td>
                <td><?= htmlspecialchars($prod['price']) ?></td>
                <td><?= htmlspecialchars($prod['category_name']) ?></td>
                <td><?= $prod['is_best_seller'] ? 'Yes' : 'No' ?></td>
                <td>
                  <button 
                    type="button" 
                    class="status-btn <?= $isAvailable ? 'available' : 'not-available' ?>"
                    onclick="toggleAvailabilityModal(<?= $prod['product_id'] ?>, <?= $isAvailable ? 'true' : 'false' ?>)"
                  >
                    <?= $isAvailable ? 'Available' : 'Not Available' ?>
                  </button>
                </td>
                <td>
                  <?php if ($isAvailable): ?>
                    <button
                      type="button"
                      class="custom-edit-btn edit-btn"
                      data-id="<?= $prod['product_id'] ?>"
                      data-name="<?= htmlspecialchars($prod['product_name'], ENT_QUOTES) ?>"
                      data-description="<?= htmlspecialchars($prod['description'], ENT_QUOTES) ?>"
                      data-price="<?= $prod['price'] ?>"
                      data-category="<?= $prod['category_id'] ?>"
                      data-best_seller="<?= $prod['is_best_seller'] ?>"
                      data-image="<?= htmlspecialchars($prod['product_image'], ENT_QUOTES) ?>"
                    >
                      <i class="fas fa-pen"></i> Edit
                    </button>
                    <button
                      type="button"
                      class="custom-delete-btn delete-product"
                      data-id="<?= $prod['product_id'] ?>"
                    >
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center">
                <p class="no-data-message">No products found.</p>
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
              <a href="?tab=products&page=1&entries=<?= $entries_per_page ?>" class="page-link first">
                  <i class="fas fa-angle-double-left"></i>
              </a>
              <a href="?tab=products&page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>" class="page-link prev">
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
              <a href="?tab=products&page=<?= $i ?>&entries=<?= $entries_per_page ?>" 
                 class="page-link <?= $i === $current_page ? 'active' : '' ?>">
                  <?= $i ?>
              </a>
          <?php endfor; ?>

          <?php if ($end_page < $total_pages): ?>
              <span class="page-ellipsis">...</span>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
              <a href="?tab=products&page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>" class="page-link next">
                  <i class="fas fa-angle-right"></i>
              </a>
              <a href="?tab=products&page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>" class="page-link last">
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
    <h2 class="modal-title">Edit Product</h2>
    <form id="editForm" method="POST" class="modal-form" enctype="multipart/form-data">
      <input type="hidden" id="edit_id" name="edit_id" />
      <input type="hidden" id="current_image" name="current_image" />

      <div class="input-container file-upload">
        <div class="image-preview">
          <img
            id="currentImagePreview"
            src=""
            alt="Current Image"
          />
        </div>
        <label for="edit_product_image" class="file-label" id="editProductLabel">
          <i class="fas fa-upload upload-icon"></i> Change Image (Optional)
        </label>
        <input type="file" id="edit_product_image" name="edit_product_image" accept="image/*" />
      </div>

      <div class="input-container">
        <label for="edit_product_name">Product Name</label>
        <input
          type="text"
          id="edit_product_name"
          name="edit_product_name"
          placeholder="Enter product name"
          required
        />
      </div>

      <div class="input-container">
        <label for="edit_description">Description</label>
        <input
          type="text"
          id="edit_description"
          name="edit_description"
          placeholder="Enter product description"
          required
        />
      </div>

      <div class="input-row-group">
      <div class="input-container">
        <label for="edit_price">Price</label>
        <input
          type="number"
          id="edit_price"
          name="edit_price"
          placeholder="Enter product price"
          required
          step="0.01"
        />
      </div>

      <div class="input-container">
        <label for="edit_category_id">Category</label>
        <select id="edit_category_id" name="edit_category_id" required>
          <option value="">Select a category</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      </div>

      <div class="input-container">
        <label class="switch-label" for="edit_is_best_seller">Best Seller</label>
        <label class="switch">
          <input type="checkbox" id="edit_is_best_seller" name="edit_is_best_seller" />
          <span class="slider round"></span>
        </label>
      </div>

      <div class="modal-actions">
        <button type="submit" name="edit_product" class="md-btn md-btn-primary">Save Changes</button>
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
      <p>Are you sure you want to delete this product?</p>
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
      <p>Are you sure you want to delete the selected products?</p>
      <div class="modal-actions">
        <button type="submit" class="md-btn md-btn-primary">Yes, Delete All</button>
        <button type="button" id="cancelBulkDelete" class="md-btn md-btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Make Available Modal -->
<div id="makeAvailableModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Make Product Available</h2>
    <form method="POST" class="modal-form">
      <input type="hidden" name="product_id" id="make_available_id">
      <input type="hidden" name="new_status" value="1">
      <p>Are you sure you want to make this product available?</p>
      <p class="modal-subtitle">This will allow customers to view and purchase this product.</p>
      <div class="modal-actions">
        <button type="submit" name="toggle_availability" class="md-btn md-btn-primary">Make Available</button>
        <button type="button" class="md-btn md-btn-secondary" onclick="closeAvailabilityModal('makeAvailableModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Make Unavailable Modal -->
<div id="makeUnavailableModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Make Product Unavailable</h2>
    <form method="POST" class="modal-form">
      <input type="hidden" name="product_id" id="make_unavailable_id">
      <input type="hidden" name="new_status" value="0">
      <p>Are you sure you want to make this product unavailable?</p>
      <p class="modal-subtitle">This will prevent customers from viewing and purchasing this product.</p>
      <div class="modal-actions">
        <button type="submit" name="toggle_availability" class="md-btn md-btn-primary">Make Unavailable</button>
        <button type="button" class="md-btn md-btn-secondary" onclick="closeAvailabilityModal('makeUnavailableModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
  $(document).ready(function () {

    // Show image preview when uploading in Add form
    $('#product_image').on('change', function () {
      const file = this.files[0];
      const label = $('#productLabel');
      const fileName = file?.name || "Upload Image";
      label.html(`<i class="fas fa-image upload-icon"></i> ${fileName}`);
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          $('#preview-img').attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);
      } else {
        $('#preview-img').hide();
      }
    });

    
        // Select All toggle for bulk actions
        $('#select-all').on('change', function () {
          $('input[name="selected_ids[]"]').prop('checked', this.checked);
        });

        $('#triggerBulkDelete').on('click', function () {
      const selected = $('input[name="selected_ids[]"]:checked');
      if (selected.length === 0) {
        $.toast({
          heading: 'No Selection',
          text: 'Please select at least one porduct to delete.',
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
      const name = $(this).data('name');
      const description = $(this).data('description');
      const price = $(this).data('price');
      const category_id = $(this).data('category');
      const is_best_seller = $(this).data('best_seller') == 1 || $(this).data('best_seller') === true;
      const image = $(this).data('image');

      fillEditModal(id, name, description, price, category_id, is_best_seller, image);
    });

    // Fill the edit modal fields and show modal
    function fillEditModal(id, name, description, price, category_id, is_best_seller, image) {
      $('#edit_id').val(id);
      $('#edit_product_name').val(name);
      $('#edit_description').val(description);
      $('#edit_price').val(price);
      $('#edit_category_id').val(category_id);
      $('#edit_is_best_seller').prop('checked', is_best_seller);

      if (image) {
        const imageUrl = '/alon_at_araw/assets/uploads/products/' + image;
        $('#currentImagePreview').attr('src', imageUrl).show();
        $('#current_image').val(image);
      } else {
        $('#currentImagePreview').attr('src', '/alon_at_araw/assets/images/no-image.png').show();
        $('#current_image').val('');
      }

      $('#edit_product_image').on('change', function () {
        const file = this.files[0];
        const label = $('#editProductLabel');
        const fileName = file?.name || "Upload Image";
        label.html(`<i class="fas fa-image upload-icon"></i> ${fileName}`);
      });

      $('#unblockModal').fadeIn(200);
    }

     // Delete button click handler
    $('.delete-product').on('click', function () {
      const id = $(this).data('id');
      $('#delete_id').val(id);
      $('#deleteModal').fadeIn(200);
    });

    // Cancel delete modal
    $('#cancelDelete').on('click', () => $('#deleteModal').fadeOut(200));

    // Close modal function
    window.closeModal = function () {
      $('#unblockModal').fadeOut(200);
    };

    // Close modal on clicking outside the modal card
    $('#unblockModal').on('click', function (e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Search products function
    $(document).ready(function() {
    $('#searchInput').on('keyup', function () {
        const search = $(this).val().toLowerCase();
        let found = false;
        $('.user-table tbody tr').each(function () {
          const name = $(this).find('td:eq(3)').text().toLowerCase();
          const email = $(this).find('td:eq(4)').text().toLowerCase();
          const match = name.includes(search) || email.includes(search);
          $(this).toggle(match);
          if (match) found = true;
        });
        if (!found) {
          $('.user-table tbody').append('<tr class="no-found"><td colspan="6">No product found. Try searching again.</td></tr>');
        } else {
          $('.user-table tbody .no-found').remove();
        }
      });
    });

        // Filter by category
    $('#filterSelect').on('change', function () {
      const selectedCategory = $(this).val().toLowerCase();
      let found = false;

      $('.user-table tbody tr').each(function () {
        const category = $(this).find('td:eq(6)').text().toLowerCase();
        const match = selectedCategory === 'all' || category === selectedCategory;

        $(this).toggle(match);

        if (match) found = true;
      });

      $('.user-table tbody .no-found').remove();

      if (!found) {
        $('.user-table tbody').append('<tr class="no-found"><td colspan="9" class="text-center">No products found in this category.</td></tr>');
      }
    });

    // Availability toggle modal functions
    window.toggleAvailabilityModal = function(productId, isCurrentlyAvailable) {
      const modalId = isCurrentlyAvailable ? 'makeUnavailableModal' : 'makeAvailableModal';
      const inputId = isCurrentlyAvailable ? 'make_unavailable_id' : 'make_available_id';
      
      document.getElementById(inputId).value = productId;
      const modal = document.getElementById(modalId);
      modal.style.display = 'flex'; // Changed to flex for centering
    };

    window.closeAvailabilityModal = function(modalId) {
      document.getElementById(modalId).style.display = 'none';
    };

    // Close modals when clicking outside
    $('.unblock-modal').on('click', function(e) {
      if (e.target === this) {
        $(this).hide();
      }
    });

    // Toast messages
    <?php if (isset($_SESSION['toast'])): ?>
      let toastMessage = '';
      switch ('<?= $_SESSION['toast'] ?>') {
        case 'added':
          toastMessage = 'Product added.';
          break;
        case 'edited':
          toastMessage = 'Product updated.';
          break;
        case 'deleted':
          toastMessage = 'Product deleted.';
          break;
        case 'multiple_deleted':
          toastMessage = 'Selected products deleted.';
          break;
        case 'availability_updated':
          toastMessage = 'Product availability has been updated.';
          break;
      }
      $.toast({
        heading: toastMessage.includes('deleted') ? 'Deleted' : 'Success',
        text: toastMessage,
        icon: toastMessage.includes('deleted') ? 'info' : 'success',
        showHideTransition: 'slide',
        position: 'top-right',
        loaderBg: '#5ba035',
      });
      <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    // Combined filter function
    function applyFilters() {
      const categoryFilter = $('#categoryFilter').val().toLowerCase();
      const availabilityFilter = $('#availabilityFilter').val();
      const bestSellerFilter = $('#bestSellerFilter').val();
      const searchText = $('#searchInput').val().toLowerCase();
      
      let found = false;

      $('.user-table tbody tr').each(function () {
        const category = $(this).find('td:eq(6)').text().toLowerCase();
        const name = $(this).find('td:eq(3)').text().toLowerCase();
        const description = $(this).find('td:eq(4)').text().toLowerCase();
        const isAvailable = !$(this).hasClass('unavailable-product');
        const isBestSeller = $(this).find('td:eq(7)').text().trim() === 'Yes';

        const matchCategory = categoryFilter === 'all' || category === categoryFilter;
        const matchAvailability = availabilityFilter === 'all' || 
                               (availabilityFilter === 'available' && isAvailable) || 
                               (availabilityFilter === 'not_available' && !isAvailable);
        const matchBestSeller = bestSellerFilter === 'all' || 
                              (bestSellerFilter === 'best_seller' && isBestSeller) || 
                              (bestSellerFilter === 'not_best_seller' && !isBestSeller);
        const matchSearch = name.includes(searchText) || description.includes(searchText);

        const showRow = matchCategory && matchAvailability && matchBestSeller && matchSearch;
        $(this).toggle(showRow);

        if (showRow) found = true;
      });

      if (!found) {
        $('.user-table tbody .no-found').remove();
        $('.user-table tbody').append('<tr class="no-found"><td colspan="10" class="text-center">No products found with the selected filters.</td></tr>');
      } else {
        $('.user-table tbody .no-found').remove();
      }
    }

    // Attach filter function to all filter changes
    $('#categoryFilter, #availabilityFilter, #bestSellerFilter').on('change', applyFilters);
    $('#searchInput').on('keyup', applyFilters);

    // Entries per page change handler
    function changeEntries(value) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('entries', value);
        urlParams.set('page', 1); // Reset to first page when changing entries
        window.location.href = window.location.pathname + '?' + urlParams.toString();
    }
  });
</script>

<style>
/* Modal Styles */
.unblock-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-title {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-size: 1.5rem;
}

.modal-subtitle {
    color: #666;
    font-size: 0.9rem;
    margin: 0.5rem 0 1.5rem 0;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.filter-controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.unavailable-product {
    background-color: #f5f5f5;
    opacity: 0.7;
}

.unavailable-product td:not(:nth-child(9)) {
    pointer-events: none;
}

.status-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.status-btn.available {
    background-color: #4CAF50;
    color: white;
}

.status-btn.not-available {
    background-color: #f44336;
    color: white;
}

.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.entries-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.entries-control select {
    padding: 0.4rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0 0.3rem;
}

.table-info {
    color: #666;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 0.5rem;
    border-radius: 6px;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}

.page-link:hover {
    background: #f5f5f5;
    color: #333;
}

.page-link.active {
    background: var(--color-primary);
    color: white;
}

.page-ellipsis {
    color: #666;
    padding: 0 0.3rem;
}

.first, .last, .prev, .next {
    font-size: 0.8rem;
}
</style>
</body>
</html>
