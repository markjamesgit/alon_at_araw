<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db.php';

if (isset($_POST['add_flavor'])) {
    $name = trim($_POST['flavor_name']);
    $quantity = trim($_POST['quantity']);

    $stmt = $conn->prepare("INSERT INTO flavors (flavor_name, quantity) VALUES (?, ?)");
    $stmt->execute([$name, $quantity]);

    $_SESSION['toast'] = 'added';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=flavors");
    exit;
}

if (isset($_POST['edit_flavor'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_flavor_name']);
    $quantity = trim($_POST['edit_quantity']);

    $stmt = $conn->prepare("UPDATE flavors SET flavor_name = ?, quantity = ? WHERE flavor_id = ?");
    $stmt->execute([$name, $quantity, $id]);

    $_SESSION['toast'] = 'edited';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=flavors");
    exit;
}

if (isset($_POST['delete_single'])) {
    $stmt = $conn->prepare("DELETE FROM flavors WHERE flavor_id = ?");
    $stmt->execute([$_POST['delete_id']]);

    $_SESSION['toast'] = 'deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=flavors");
    exit;
}

if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    $conn->query("DELETE FROM flavors WHERE flavor_id IN ($ids)");

    $_SESSION['toast'] = 'multiple_deleted';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=flavors");
    exit;
}

$flavors = $conn->query("SELECT * FROM flavors ORDER BY flavor_id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Flavors - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/flavors.css" />
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css" />
  <link rel="icon" type="image/png" href="../../../assets/images/logo/logo.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" rel="stylesheet" />
</head>
<body>
<?php include __DIR__ . '/../../../includes/admin-sidebar.php'; ?>

<main class="users-management">

  <!-- Add Flavor Form -->
  <div class="category-form-wrapper">
    <form method="POST" class="flavor-form">
      <div class="input-container">
        <label for="flavor-name">Flavor Name</label>
        <input type="text" id="flavor-name" name="flavor_name" placeholder="Enter flavor name" required />
      </div>

      <div class="input-container">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" placeholder="Enter quantity" required min="1" />
      </div>

      <button type="submit" name="add_flavor" class="md-btn md-btn-primary">Add Flavor</button>
    </form>
  </div>

  <div class="user-controls">
    <input type="text" id="searchInput" placeholder="Search flavors..." class="search-input" />

    <div class="filter-dropdown">
    <select id="filterSelect" class="filter-select">
        <option value="all">All Flavors</option>
        <option value="in_stock">In Stock</option>
        <option value="out_of_stock">Out of Stock</option>
    </select>
    </div>
  </div>

  <!-- Bulk Delete -->
  <form method="POST">
    <button type="button" id="triggerBulkDelete" class="md-btn danger">Delete Selected</button>
    <div class="table-container">
      <table class="user-table">
        <thead>
          <tr>
            <th><label class="md-checkbox"><input type="checkbox" id="select-all" /><span></span></label></th>
            <th>#</th>
            <th>Name</th>
            <th>Quantity</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($flavors) > 0): ?>
            <?php foreach ($flavors as $flavor): ?>
                <?php
                    $isOutOfStock = $flavor['quantity'] == 0;
                    $rowClass = $isOutOfStock ? 'out-of-stock' : '';
                ?>
              <tr>
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $flavor['flavor_id'] ?>" /><span></span>
                  </label>
                </td>
                <td><?= $flavor['flavor_id'] ?></td>
                <td><?= htmlspecialchars($flavor['flavor_name']) ?></td>
                <td><?= htmlspecialchars($flavor['quantity']) ?></td>
                 <td>
                <?php if ($isOutOfStock): ?>
                    <button
                    type="button"
                    class="custom-edit-btn edit-btn"
                    data-id="<?= $flavor['flavor_id'] ?>"
                    data-name="<?= htmlspecialchars($flavor['flavor_name'], ENT_QUOTES) ?>"
                    data-quantity="<?= $flavor['quantity'] ?>"
                    >
                    <i class="fas fa-plus"></i> Add Stock
                    </button>
                <?php else: ?>      
                    <button
                    type="button"
                    class="custom-edit-btn edit-btn"
                    data-id="<?= $flavor['flavor_id'] ?>"
                    data-name="<?= htmlspecialchars($flavor['flavor_name'], ENT_QUOTES) ?>"
                    data-quantity="<?= $flavor['quantity'] ?>"
                    >
                    <i class="fas fa-pen"></i> Edit
                    </button>
                <?php endif; ?>
                <button
                    type="button"
                    class="custom-delete-btn delete-flavor"
                    data-id="<?= $flavor['flavor_id'] ?>"
                    <?= $isOutOfStock ? 'disabled' : '' ?>
                >
                    <i class="fas fa-trash"></i> Delete
                </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center">
                <p class="no-data-message">No flavors found.</p>
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
    <h2 class="modal-title">Edit Flavor</h2>
    <form id="editForm" method="POST" class="modal-form">
      <input type="hidden" id="edit_id" name="edit_id" />

      <div class="input-container">
        <label for="edit_flavor_name">Flavor Name</label>
        <input
          type="text"
          id="edit_flavor_name"
          name="edit_flavor_name"
          placeholder="Enter flavor name"
          required
        />
      </div>

      <div class="input-container">
        <label for="edit_quantity">Quantity</label>
        <input type="number" id="edit_quantity" name="edit_quantity" placeholder="Enter quantity" required min="1" />
      </div>

      <div class="modal-actions">
        <button type="submit" name="edit_flavor" class="md-btn md-btn-primary">Save Changes</button>
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
      <p>Are you sure you want to delete this flavor?</p>
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
      <p>Are you sure you want to delete the selected flavors?</p>
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

    // Select All toggle for bulk actions
    $('#select-all').on('change', function () {
      $('input[name="selected_ids[]"]').prop('checked', this.checked);
    });

    $('#triggerBulkDelete').on('click', function () {
      const selected = $('input[name="selected_ids[]"]:checked');
      if (selected.length === 0) {
        $.toast({
          heading: 'No Selection',
          text: 'Please select at least one flavor to delete.',
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
      const quantity = $(this).data('quantity');

      fillEditModal(id, name, quantity);
    });

    // Fill the edit modal fields and show modal
    function fillEditModal(id, name, quantity) {
      $('#edit_id').val(id);
      $('#edit_flavor_name').val(name);
      $('#edit_quantity').val(quantity);
      $('#unblockModal').fadeIn(200);
    }

    // Delete button click handler
    $('.delete-flavor').on('click', function () {
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

    // Search flavors function
    $('#searchInput').on('keyup', function () {
      const search = $(this).val().toLowerCase();
      let found = false;
      $('.user-table tbody tr').each(function () {
        const name = $(this).find('td:eq(2)').text().toLowerCase();
        const match = name.includes(search);
        $(this).toggle(match);
        if (match) found = true;
      });
      if (!found) {
        $('.user-table tbody').append('<tr class="no-found"><td colspan="5">No flavor found. Try searching again.</td></tr>');
      } else {
        $('.user-table tbody .no-found').remove();
      }
    });

    // Toast messages
    <?php if (isset($_SESSION['toast'])): ?>
      let toastMessage = '';
      switch ('<?= $_SESSION['toast'] ?>') {
        case 'added':
          toastMessage = 'Flavor added.';
          break;
        case 'edited':
          toastMessage = 'Flavor updated.';
          break;
        case 'deleted':
          toastMessage = 'Flavor deleted.';
          break;
        case 'multiple_deleted':
          toastMessage = 'Selected flavors deleted.';
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

    // Stock filter logic
    $('#filterSelect').on('change', function () {
      const filter = $(this).val();
      let found = false;

      $('.user-table tbody tr').each(function () {
        const quantity = parseInt($(this).find('td:eq(3)').text(), 10);
        const showRow =
          filter === 'all' ||
          (filter === 'in_stock' && quantity > 0) ||
          (filter === 'out_of_stock' && quantity === 0);

        $(this).toggle(showRow);

        if (showRow) found = true;
      });

      if (!found) {
        $('.user-table tbody').append('<tr class="no-found"><td colspan="5" class="text-center">No flavors found in this category.</td></tr>');
      } else {
        $('.user-table tbody .no-found').remove();
      }
    });
  });
</script>
</body>
</html>
