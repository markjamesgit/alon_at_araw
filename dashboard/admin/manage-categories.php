<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    $imageName = null;
    if (!empty($_FILES['category_image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['category_image']['name']);
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/alon_at_araw/assets/uploads/categories/';
        $targetPath = $targetDir . $imageName;
        move_uploaded_file($_FILES['category_image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $imageName]);

    $_SESSION['toast'] = 'added';
    header("Location: /alon_at_araw/dashboard/admin/manage-categories.php");
    exit;
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_category_name']);
    $description = trim($_POST['edit_description']);

    $imageName = $_POST['current_image'];

    if (!empty($_FILES['edit_category_image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['edit_category_image']['name']);
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/alon_at_araw/assets/uploads/categories/';
        $targetPath = $targetDir . $imageName;
        move_uploaded_file($_FILES['edit_category_image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $imageName, $id]);

    $_SESSION['toast'] = 'edited';
    header("Location: /alon_at_araw/dashboard/admin/manage-categories.php");
    exit;
}

// Handle Single Delete
if (isset($_POST['delete_single'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);

    $_SESSION['toast'] = 'deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-categories.php");
    exit;
}

// Handle Bulk Delete
if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    $conn->query("DELETE FROM categories WHERE id IN ($ids)");

    $_SESSION['toast'] = 'multiple_deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-categories.php");
    exit;
}

// Fetch categories for display
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Categories - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/manage-categories.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
  <link rel="icon" type="image/png" href="/alon_at_araw/assets/images/logo.png"/>
    <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" rel="stylesheet" />
</head>
<body>
<div class="main-container">
  <?php include '../../includes/admin-sidebar.php'; ?>

  <div class="content-wrapper">

    <main class="user-management">
      <h1>Manage Categories</h1>
    <p class="subtitle">Manage all categories for your store. You can add, edit and delete categories here.</p>

  <!-- Add Category Form -->
  <div class="category-form-wrapper">
    <form method="POST" enctype="multipart/form-data" class="category-form">
      <div class="input-container file-upload">
        <label for="category_image" class="file-label" id="categoryLabel">
          <i class="fas fa-upload upload-icon"></i>
          Upload Image (Optional)
        </label>
        <input type="file" id="category_image" name="category_image" accept="image/*" />
      </div>

      <div class="input-container">
        <label for="category-name">Category Name</label>
        <input type="text" id="category-name" name="category_name" placeholder="Enter category name" required />
      </div>

      <div class="input-container">
        <label for="description">Description</label>
        <input type="text" id="description" name="description" placeholder="Enter category description" />
      </div>

      <button type="submit" name="add_category" class="md-btn md-btn-primary">Add Category</button>
    </form>

    <div class="image-preview-wrapper">
      <h3>Image Preview</h3>
      <div class="image-preview">
        <img id="preview-img" src="" alt="Image Preview" style="display:none;" />
      </div>
    </div>
  </div>

  <div class="user-controls">
    <input type="text" id="searchInput" placeholder="Search categories..." class="search-input" />
  </div>

  <!-- Bulk Delete -->
  <form method="POST">
    <button type="submit" name="delete_selected" class="md-btn danger" onclick="return confirm('Delete selected categories?')">Delete Selected</button>
    <div class="table-container">
      <table class="user-table">
        <thead>
          <tr>
            <th><label class="md-checkbox"><input type="checkbox" id="select-all" /><span></span></label></th>
            <th>#</th>
            <th>Image</th>
            <th>Name</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $cat['id'] ?>" /><span></span>
                  </label>
                </td>
                <td><?= $cat['id'] ?></td>
                <td>
                  <?php
                    $rawImage = $cat['image'] ?? '';
                    $cleanImage = preg_replace('#^(\.\./)+#', '', $rawImage);
                    $categoryImagePath = $cleanImage
                      ? '/alon_at_araw/assets/uploads/categories/' . $cleanImage
                      : '/alon_at_araw/assets/images/no-image.png';
                  ?>
                  <img src="<?= htmlspecialchars($categoryImagePath) ?>" class="profile-pic" alt="Category Image" />
                </td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['description']) ?></td>
                <td>
                  <button
                    type="button"
                    class="custom-edit-btn edit-btn"
                    data-id="<?= $cat['id'] ?>"
                    data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($cat['description'], ENT_QUOTES) ?>"
                    data-image="<?= htmlspecialchars($cat['image'], ENT_QUOTES) ?>"
                  >
                    <i class="fas fa-pen"></i> Edit
                  </button>
                  <button
                    type="button"
                    class="custom-delete-btn delete-category"
                    data-id="<?= $cat['id'] ?>"
                  >
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">
                <p class="no-data-message">No categories found.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>
</main>

<!-- Edit Modal -->
<div id="editModal" class="unblock-modal" style="display:none;">
  <div class="modal-card">
    <h2 class="modal-title">Edit Category</h2>
    <form id="editForm" method="POST" class="modal-form" enctype="multipart/form-data">
      <input type="hidden" id="edit_id" name="edit_id" />
      <input type="hidden" id="current_image" name="current_image" />

      <div class="input-container file-upload">
        <div class="image-preview">
          <img id="currentImagePreview" src="" alt="Current Image" />
        </div>
        <label for="edit_category_image" class="file-label" id="editCategoryLabel">
          <i class="fas fa-upload upload-icon"></i> Change Image (Optional)
        </label>
        <input type="file" id="edit_category_image" name="edit_category_image" accept="image/*" />
      </div>

      <div class="input-container">
        <label for="edit_category_name">Category Name</label>
        <input
          type="text"
          id="edit_category_name"
          name="edit_category_name"
          placeholder="Enter category name"
          required
        />
      </div>

      <div class="input-container">
        <label for="edit_description">Description</label>
        <input
          type="text"
          id="edit_description"
          name="edit_description"
          placeholder="Enter category description"
        />
      </div>

      <div class="modal-actions">
        <button type="submit" name="edit_category" class="md-btn md-btn-primary">Save Changes</button>
        <button type="button" id="cancelEdit" class="md-btn md-btn-secondary">Cancel</button>
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
      <p>Are you sure you want to delete this category?</p>
      <div class="modal-actions">
        <button type="submit" name="delete_single" class="md-btn md-btn-primary">Delete</button>
        <button type="button" id="cancelDelete" class="md-btn md-btn-secondary">Cancel</button>
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
          toastMessage = 'Category added.';
          break;
        case 'edited':
          toastMessage = 'Category updated.';
          break;
        case 'deleted':
          toastMessage = 'Category deleted.';
          break;
        case 'multiple_deleted':
          toastMessage = 'Selected categories deleted.';
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

    $('#category_image').on('change', function () {
      const file = this.files[0];
      const label = $('#categoryLabel');
      const fileName = file?.name || "Upload Image";
      label.html(`<i class="fas fa-image upload-icon"></i> ${fileName}`);
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
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

    // Edit button click handler
    $('.edit-btn').on('click', function () {
      const id = $(this).data('id');
      const name = $(this).data('name');
      const description = $(this).data('description');
      const image = $(this).data('image');

      fillEditModal(id, name, description, image);
    });

    // Fill edit modal inputs and show modal
    function fillEditModal(id, name, description, image) {
      $('#edit_id').val(id);
      $('#edit_category_name').val(name);
      $('#edit_description').val(description);
      $('#current_image').val(image);

      if (image) {
        const imageUrl = '/alon_at_araw/assets/uploads/categories/' + image;
        $('#currentImagePreview').attr('src', imageUrl).show();
        $('#current_image').val(image);
      } else {
        $('#currentImagePreview').attr('src', '/alon_at_araw/assets/images/no-image.png').show();
        $('#current_image').val('');
      }

      $('#edit_category_image').on('change', function () {
        const file = this.files[0];
        const label = $('#editCategoryLabel');
        const fileName = file?.name || "Upload Image";
        label.html(`<i class="fas fa-image upload-icon"></i> ${fileName}`);
      });

      $('#editModal').fadeIn(200);
    }

    // Cancel edit modal
    $('#cancelEdit').on('click', () => $('#editModal').fadeOut(200));

    // Delete button click handler
    $('.delete-category').on('click', function () {
      const id = $(this).data('id');
      $('#delete_id').val(id);
      $('#deleteModal').fadeIn(200);
    });

    // Cancel delete modal
    $('#cancelDelete').on('click', () => $('#deleteModal').fadeOut(200));

    // Search filter
    $('#searchInput').on('input', function () {
      const query = $(this).val().toLowerCase();
      let found = false;
      $('tbody tr').each(function () {
        const name = $(this).find('td:nth-child(4)').text().toLowerCase();
        const description = $(this).find('td:nth-child(5)').text().toLowerCase();
        const isMatch = name.includes(query) || description.includes(query);
        $(this).toggle(isMatch);
        if (isMatch) found = true;
      });

      // show "No categories found" row if no matches
      if (!found) {
        if ($('tbody tr.no-found').length === 0) {
          $('tbody').append('<tr class="no-found"><td colspan="6" class="text-center">No categories found. Try searching again.</td></tr>');
        }
      } else {
        $('tbody tr.no-found').remove();
      }
    });

  });
</script>
</body>
</html>
