<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY id")->fetchAll();

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

$products = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.product_id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Products - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/products.css" />
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css" />
  <link rel="icon" type="image/png" href="../../../assets/images/logo/logo.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" rel="stylesheet" />
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

    <div class="filter-dropdown">
      <select id="filterSelect" class="filter-select">
        <option value="all">All Categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= $category['name'] ?>"><?= htmlspecialchars($category['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Bulk Delete -->
  <form method="POST">
    <button type="submit" name="delete_selected" class="md-btn danger" onclick="return confirm('Delete selected?')">Delete Selected</button>
    <div class="table-container">
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($products) > 0): ?>
            <?php foreach ($products as $prod): ?>
              <tr>
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $prod['product_id'] ?>" /><span></span>
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
              </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center">
                <p class="no-data-message">No products found.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>
</main>

<!-- Edit Modal -->
<div id="unblockModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Edit Product</h2>
    <form id="editForm" method="POST" class="modal-form" enctype="multipart/form-data" onsubmit="return confirm('Save changes?')">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script>
  $(document).ready(function () {

    // Handle single product delete with confirmation
  $(document).on('click', '.delete-product', function () {
    const productId = $(this).data('id');
    if (confirm('Delete this product?')) {
      const form = $('<form>', {
        method: 'POST',
        style: 'display: none;'
      });

      form.append($('<input>', {
        type: 'hidden',
        name: 'delete_id',
        value: productId
      }));

      form.append($('<input>', {
        type: 'hidden',
        name: 'delete_single',
        value: '1'
      }));

      $('body').append(form);
      form.submit();
    }
  });

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

    // Handle "Select All" checkbox for bulk delete
    $('#select-all').on('change', function () {
      $('input[name="selected_ids[]"]').prop('checked', this.checked);
    });

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

    // Toast messages
    <?php if (isset($_SESSION['toast'])) : ?>
      let toastMessage = '';
      switch ('<?= $_SESSION['toast'] ?>') {
        case 'added':
          toastMessage = 'Product successfully added!';
          break;
        case 'edited':
          toastMessage = 'Product successfully updated!';
          break;
        case 'deleted':
          toastMessage = 'Product successfully deleted!';
          break;
        case 'multiple_deleted':
          toastMessage = 'Selected products successfully deleted!';
          break;
      }
      $.toast({
        heading: 'Success',
        text: toastMessage,
        showHideTransition: 'slide',
        icon: 'success',
        position: 'top-right',
        loaderBg: '#5ba035',
      });
      <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
  });
</script>
</body>
</html>
