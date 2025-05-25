<?php
session_start();
require_once '../../config/db.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Handle image upload
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = '../../assets/uploads/categories/' . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $imageName]);

     $_SESSION['toast'] = 'added';
    header("Location: manage-categories.php");
    exit;
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $description = trim($_POST['edit_description']);

    $imageName = $_POST['current_image'];

    if (!empty($_FILES['edit_image']['name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['edit_image']['name']);
        $targetPath = '../../assets/uploads/categories/' . $imageName;
        move_uploaded_file($_FILES['edit_image']['tmp_name'], $targetPath);
    }

    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $imageName, $id]);

    $_SESSION['toast'] = 'edited';
    header("Location: manage-categories.php");
    exit;
}

// Handle Single Delete
if (isset($_POST['delete_single'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);

    $_SESSION['toast'] = 'deleted';
    header("Location: manage-categories.php");
    exit;
}

// Handle Multiple Delete
if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    $conn->query("DELETE FROM categories WHERE id IN ($ids)");

    $_SESSION['toast'] = 'multiple_deleted';
    header("Location: manage-categories.php");
    exit;
}

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Categories - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/manage-categories.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>
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
      <p class="subtitle">Add categories to your store effortlessly from here.</p>

      <!-- Add Category Form -->
        <div class="category-form-wrapper">
          <!-- Left: Form -->
          <form method="POST" enctype="multipart/form-data" class="category-form">
            <div class="input-container">
              <label for="category-name">Category Name</label>
              <input type="text" id="category-name" name="name" placeholder="Enter category name" required>
            </div>

            <div class="input-container">
              <label for="category-description">Description</label>
              <input type="text" id="category-description" name="description" placeholder="Enter category description" required>
            </div>

            <div class="input-container file-upload">
              <label for="category-image" class="file-label" id="categoryLabel">
                <i class="fas fa-upload upload-icon"></i>
                Upload Image
              </label>
              <input type="file" id="category-image" name="image" accept="image/*">
            </div>

            <button type="submit" name="add_category" class="md-btn md-btn-primary">Add Category</button>
          </form>

          <!-- Right: Live Preview -->
          <div class="image-preview-wrapper">
            <h3>Image Preview</h3>
            <div class="image-preview">
              <img id="preview-img" src="" alt="Image Preview" />
            </div>
          </div>
        </div>

       <div class="user-controls">
      <input type="text" id="searchInput" placeholder="Search categories..." class="search-input" />
  </div>

      <!-- Bulk Delete -->
      <form method="POST">
        <button type="submit" name="delete_selected" class="md-btn danger" onclick="return confirm('Delete selected?')"><i class="fa-solid fa-trash-can"></i> Delete Selected</button>
        <div class="table-container">
         <table class="user-table">
          <thead>
            <tr>
              <th><label class="md-checkbox"><input type="checkbox" id="select-all"><span></span></label></th>
              <th>#</th>
              <th>Image</th>
              <th>Name</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td><label class="md-checkbox">
              <input type="checkbox" name="selected_ids[]" value="<?= $cat['id'] ?>"><span></span>
            </label>
          </td>
              <td><?= $cat['id'] ?></td>
              <td>
              <?php
                $rawImage = $cat['image'] ?? '';
                $cleanImage = preg_replace('#^(\.\./)+#', '', $rawImage);

                $categoryImagePath = $cleanImage
                    ? '/alon_at_araw/assets/uploads/categories/' . $cleanImage
                    : '/alon_at_araw/assets/images/no-image.png'; // default image
              ?>
              <img src="<?= htmlspecialchars($categoryImagePath) ?>" class="profile-pic" alt="Category Image">
            </td>
              <td><?= htmlspecialchars($cat['name']) ?></td>
              <td><?= htmlspecialchars($cat['description']) ?></td>
              <td>
                <button type="button" class="btn-unblock" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['description'], ENT_QUOTES) ?>', '<?= $cat['image'] ?>')">Edit</button>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="btn-unblock" name="delete_single" onclick="return confirm('Delete this category?')">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </form>
    </main>
  </div>
</div>

    <!-- Edit Modal -->
<div id="unblockModal" class="modal-backdrop" style="display: none;">
  <div class="modal-card">
    <h3 class="modal-title">Edit Category</h3>
    <form method="POST" enctype="multipart/form-data" class="modal-form">
      
      <input type="hidden" name="edit_id" id="edit_id">
      <input type="hidden" name="current_image" id="current_image">

      <!-- Current Image Display -->
      <div class="input-container">
        <label>Current Image:</label>
        <img id="currentImagePreview" src="" alt="Current Image" class="current-image-preview">
      </div>

      <div class="input-container">
        <label for="edit_name">Category Name</label>
        <input type="text" name="edit_name" id="edit_name" required />
      </div>

       <div class="input-container">
        <label for="edit_description">Description</label>
        <input type="text" name="edit_description" id="edit_description" required />
      </div>

      <div class="input-container file-upload">
        <label for="edit_image" id="profileLabel">
          <i class="fas fa-image upload-icon"></i> Upload New Image
        </label>
        <input type="file" name="edit_image" accept="image/*" class="md-input-file" id="edit_image">
      </div>

      <div class="modal-actions">
        <button type="submit" class="md-btn md-btn-primary" name="edit_category">Update</button>
        <button type="button" class="md-btn md-btn-secondary" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<?php if (isset($_SESSION['toast'])): ?>
<script>
function editCategory(id, name, description, image) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_description').value = description;
  document.getElementById('current_image').value = image;

    let imagePath = image ? '/alon_at_araw/assets/uploads/categories/' + image : '/alon_at_araw/assets/images/no-image.png';
  document.getElementById('currentImagePreview').src = imagePath;
  document.getElementById("unblockModal").style.display = "flex";
}

function closeModal() {
    $('#unblockModal').fadeOut(200);
  }

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
      $('.user-table tbody').append('<tr class="no-found"><td colspan="6">No categories found. Try searching again.</td></tr>');
    } else {
      $('.user-table tbody .no-found').remove();
    }
  });
});

document.getElementById('select-all').addEventListener('click', function () {
  document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = this.checked);
});

document.addEventListener('DOMContentLoaded', function () {
  // Edit Modal Image Label
  const editImageInput = document.getElementById('edit_image');
  if (editImageInput) {
    editImageInput.addEventListener('change', function () {
      const label = document.getElementById('profileLabel');
      const fileName = this.files[0]?.name || "Upload New Image";
      label.innerHTML = `<i class="fas fa-image upload-icon"></i> ${fileName}`;
    });
  }

  // Add Category Image Label + Preview
  const categoryImageInput = document.getElementById('category-image');
  if (categoryImageInput) {
    categoryImageInput.addEventListener('change', function (event) {
      const file = event.target.files[0];
      const preview = document.getElementById('preview-img');
      const label = document.getElementById('categoryLabel');
      const fileName = file?.name || "Upload Image";
      label.innerHTML = `<i class="fas fa-upload upload-icon"></i> ${fileName}`;

      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.src = '';
        preview.style.display = 'none';
      }
    });
  }
});

 $(document).ready(function () {
    let action = "<?= $_SESSION['toast'] ?>";
    let message = "";
    let bgColor = "#28a745"; // green for success

    if (action === "added") message = "Category successfully added!";
    if (action === "edited") message = "Category successfully updated!";
    if (action === "deleted") {
      message = "Category successfully deleted!";
      bgColor = "#dc3545"; 
    }
    if (action === "multiple_deleted") {
      message = "Categories successfully deleted!";
      bgColor = "#dc3545";
    }

    $.toast({
      heading: 'Success',
      text: message,
      icon: 'success',
      position: 'top-right',
      loaderBg: bgColor,
      hideAfter: 2000,
      stack: 3
    });
  });
</script>
<?php unset($_SESSION['toast']); endif; ?>
</body>
</html>
