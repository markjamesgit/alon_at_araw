<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db.php';

if (isset($_POST['add_flavor'])) {
    $name = trim($_POST['flavor_name']);
    $quantity = trim($_POST['quantity']);
    $price = trim($_POST['price']);

    $stmt = $conn->prepare("INSERT INTO flavors (flavor_name, quantity, price) VALUES (?, ?, ?)");
    $stmt->execute([$name, $quantity, $price]);

    $_SESSION['toast'] = 'added';
    header("Location: /alon_at_araw/dashboard/admin/manage-products.php?tab=flavors");
    exit;
}

if (isset($_POST['edit_flavor'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_flavor_name']);
    $quantity = trim($_POST['edit_quantity']);
    $price = trim($_POST['edit_price']);

    $stmt = $conn->prepare("UPDATE flavors SET flavor_name = ?, quantity = ?, price = ? WHERE flavor_id = ?");
    $stmt->execute([$name, $quantity, $price, $id]);

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

// Pagination settings
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Get total number of records
$total_records = $conn->query("SELECT COUNT(*) FROM flavors")->fetchColumn();
$total_pages = ceil($total_records / $entries_per_page);

// Fetch flavors with pagination
$flavors = $conn->query("SELECT * FROM flavors ORDER BY flavor_id DESC LIMIT $offset, $entries_per_page")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Flavors - Alon at Araw</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-admin.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/flavors.css"/>

  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">

  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include __DIR__ . '/../../../includes/admin-sidebar.php'; ?>

<main class="users-management">

  <!-- Add Flavor Form -->
  <div class="category-form-wrapper">
    <form method="POST" class="category-form">
      <div class="input-container">
        <label for="flavor-name">Flavor Name</label>
        <input type="text" id="flavor-name" name="flavor_name" placeholder="Enter flavor name" required />
      </div>

      <div class="input-container">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" placeholder="Enter quantity" required min="1" />
      </div>

      <div class="input-container">
        <label for="price">Price</label>
        <input type="number" id="price" name="price" placeholder="Enter price" required min="1" />
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
            <th>Name</th>
            <th>Price</th>
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
              <tr style="background-color: <?= $rowClass === 'out-of-stock' ? '#d3d3d3' : '' ?>" class="<?= $rowClass ?>">
                <td>
                  <label class="md-checkbox">
                    <input type="checkbox" name="selected_ids[]" value="<?= $flavor['flavor_id'] ?>" /><span></span>
                  </label>
                </td>
                <td><?= $flavor['flavor_id'] ?></td>
                <td><?= htmlspecialchars($flavor['flavor_name']) ?></td>
                <td><?= htmlspecialchars($flavor['price']) ?></td>
                <td><?= htmlspecialchars($flavor['quantity']) ?></td>
                 <td>
                <?php if ($isOutOfStock): ?>
                    <button
                    type="button"
                    class="custom-edit-btn edit-btn"
                    data-id="<?= $flavor['flavor_id'] ?>"
                    data-name="<?= htmlspecialchars($flavor['flavor_name'], ENT_QUOTES) ?>"
                    data-price="<?= $flavor['price'] ?>"
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
                    data-price="<?= $flavor['price'] ?>"
                    data-quantity="<?= $flavor['quantity'] ?>"
                    >
                    <i class="fas fa-pen"></i> Edit
                    </button>
                <?php endif; ?>
                <button
                    type="button"
                    class="custom-delete-btn delete-flavor"
                    data-id="<?= $flavor['flavor_id'] ?>"
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

  <?php if ($total_pages > 1): ?>
  <div class="pagination-container">
      <div class="pagination">
          <?php if ($current_page > 1): ?>
              <a href="?tab=flavors&page=1&entries=<?= $entries_per_page ?>" class="page-link first">
                  <i class="fas fa-angle-double-left"></i>
              </a>
              <a href="?tab=flavors&page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>" class="page-link prev">
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
              <a href="?tab=flavors&page=<?= $i ?>&entries=<?= $entries_per_page ?>" 
                 class="page-link <?= $i === $current_page ? 'active' : '' ?>">
                  <?= $i ?>
              </a>
          <?php endfor; ?>

          <?php if ($end_page < $total_pages): ?>
              <span class="page-ellipsis">...</span>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
              <a href="?tab=flavors&page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>" class="page-link next">
                  <i class="fas fa-angle-right"></i>
              </a>
              <a href="?tab=flavors&page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>" class="page-link last">
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

      <div class="input-container">
        <label for="edit_price">Price</label>
        <input type="number" id="edit_price" name="edit_price" placeholder="Enter price" required min="1" />
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
      const price = $(this).data('price');
      const quantity = $(this).data('quantity');

      fillEditModal(id, name, price, quantity);
    });

    // Fill the edit modal fields and show modal
    function fillEditModal(id, name, price, quantity) {
      $('#edit_id').val(id);
      $('#edit_flavor_name').val(name);
      $('#edit_price').val(price);
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

function changeEntries(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('entries', value);
    urlParams.set('page', 1); // Reset to first page when changing entries
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}
</script>
</body>
</html>
