<?php
require '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$stmt = $conn->query("SELECT id, name, email, account_type, failed_attempts, is_blocked FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Users - Alon at Araw</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/manage-users.css"/>
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>
</head>
<body>
<div class="main-container">
  <?php include '../../includes/admin-sidebar.php'; ?>
<div class="content-wrapper">
  <main class="user-management">
    <h1>Manage Users</h1>
    <p class="subtitle">Manage your store easily from here.</p>

    <div class="user-controls">
      <input type="text" id="searchInput" placeholder="Search users..." class="search-input" />
      
      <div class="filter-dropdown">
        <select id="filterSelect" class="filter-select">
          <option value="all">All Users</option>
          <option value="blocked">Blocked Users</option>
          <option value="unblocked">Unblocked Users</option>
        </select>
      </div>

      <button class="btn-generate-report" onclick="generatePDF()">Generate Report</button>
</div>

    <div class="table-container">
      <table class="user-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Account Type</th>
            <th>Attempts</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr class="<?= $user['is_blocked'] && $user['account_type'] !== 'admin' ? 'blocked-row' : '' ?>">
              <td><?= $user['id'] ?></td>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= ucfirst($user['account_type']) ?></td>
              <td><?= $user['failed_attempts'] ?></td>
              <td class="status-cell"><?= $user['is_blocked'] && $user['account_type'] !== 'admin' ? 'Blocked' : 'Active' ?></td>
              <td>
              <?php if ($user['account_type'] !== 'admin'): ?>
                <?php if ($user['is_blocked']): ?>
                  <button class="btn-unblock" data-user-id="<?= $user['id'] ?>">Unblock</button>
                <?php else: ?>
                  <button class="btn-block" data-user-id="<?= $user['id'] ?>">Block</button>
                <?php endif; ?>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</div>

<!-- Unblock Modal -->
<div id="unblockModal" class="modal-backdrop" style="display: none;">
  <div class="modal-card">
    <h3 class="modal-title">Confirm Unblock</h3>
    <p class="modal-text">Enter your password to unblock this user.</p>
    <div class="modal-field">
      <input type="password" id="adminPassword" placeholder="Admin password" class="modal-input" />
      <input type="hidden" id="userIdToUnblock" />
    </div>
    <div id="modalError" class="modal-error"></div>
    <div class="modal-actions">
      <button class="btn-confirm" onclick="confirmUnblock()">Confirm</button>
      <button class="btn-cancel" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Block Modal -->
<div id="blockModal" class="modal-backdrop" style="display: none;">
  <div class="modal-card">
    <h3 class="modal-title">Confirm Block</h3>
    <p class="modal-text">Enter your password to block this user.</p>
    <div class="modal-field">
      <input type="password" id="adminPasswordBlock" placeholder="Admin password" class="modal-input" />
      <input type="hidden" id="userIdToBlock" />
    </div>
    <div id="modalErrorBlock" class="modal-error"></div>
    <div class="modal-actions">
      <button class="btn-confirm" onclick="confirmBlock()">Confirm</button>
      <button class="btn-cancel" onclick="closeBlockModal()">Cancel</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
  let selectedUserId = null;
  let selectedUserIdBlock = null;

    $('#filterSelect').on('change', function () {
    const filter = $(this).val();
    $('.user-table tbody tr').each(function () {
      const isBlocked = $(this).hasClass('blocked-row');
      if (filter === 'blocked' && !isBlocked) {
        $(this).hide();
      } else if (filter === 'unblocked' && isBlocked) {
        $(this).hide();
      } else {
        $(this).show();
      }
    });
  });

  $('#searchInput').on('keyup', function () {
    const search = $(this).val().toLowerCase();
    let found = false;
    $('.user-table tbody tr').each(function () {
      const name = $(this).find('td:eq(2)').text().toLowerCase();
      const email = $(this).find('td:eq(3)').text().toLowerCase();
      const match = name.includes(search) || email.includes(search);
      $(this).toggle(match);
      if (match) found = true;
    });
    if (!found) {
      $('.user-table tbody').append('<tr class="no-found"><td colspan="5">No users found</td></tr>');
    } else {
      $('.user-table tbody .no-found').remove();
    }
  });

  async function generatePDF(adminName = "Admin", logoUrl = null) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const marginLeft = 14;
  let currentY = 15;
  if (logoUrl) {
    doc.addImage(logoUrl, 'PNG', marginLeft, currentY - 10, 30, 30);
  }

  doc.setFontSize(18);
  const nameX = logoUrl ? marginLeft + 35 : marginLeft;
  doc.text("Alon At Araw", nameX, currentY);

  doc.setFontSize(11);
  currentY += 10;
  const now = new Date();
  const formattedDate = now.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });

  doc.text(`Generated by: ${adminName}`, marginLeft, currentY);
  doc.text(`Date: ${formattedDate}`, marginLeft + 90, currentY);

  currentY += 10;
  doc.setFontSize(12);
  const description = "This report contains a list of users (active and blocked) and their account details.";
  doc.text(description, marginLeft, currentY);

  // Table Headers
  const headers = [["ID", "Name", "Email", "Account Type", "Attempts", "Status"]];
  const data = [];

  // Collect visible rows data
  $('.user-table tbody tr:visible').each(function () {
    const cols = $(this).find('td');
    const row = [
      $(cols[0]).text().trim(),
      $(cols[1]).text().trim(),
      $(cols[2]).text().trim(),
      $(cols[3]).text().trim(),
      $(cols[4]).text().trim(),
      $(cols[5]).text().trim()
    ];
    data.push(row);
  });

  // Draw table below description
  const startY = currentY + 10;

  if (doc.autoTable) {
    doc.autoTable({
      head: headers,
      body: data,
      startY: startY,
      styles: {
        fontSize: 10,
        cellPadding: 3
      },
      headStyles: {
        fillColor: [41, 128, 185]
      }
    });
  } else {
    let y = startY;
    data.forEach(row => {
      doc.text(row.join(" | "), marginLeft, y);
      y += 6;
    });
  }

  doc.save("user_report.pdf");
}


  $('.btn-block').on('click', function () {
  selectedUserIdBlock = $(this).data('user-id');
  $('#userIdToBlock').val(selectedUserIdBlock);
  $('#blockModal').fadeIn(200);
});

function closeBlockModal() {
  $('#blockModal').fadeOut(200);
  $('#adminPasswordBlock').val('');
  $('#modalErrorBlock').text('');
}

function confirmBlock() {
  const password = $('#adminPasswordBlock').val();
  const userId = $('#userIdToBlock').val();

  if (!password) {
    $('#modalErrorBlock').text("Please enter your password.");
    return;
  }

  $.ajax({
    url: '../../auth/block-user.php',
    method: 'POST',
    data: {
      admin_id: <?= $_SESSION['user_id'] ?>,
      password: password,
      user_id: userId
    },
    success: function (res) {
      if (res.trim() === 'success') {
        localStorage.setItem('blockSuccess', '1');
        $('#blockModal').fadeOut(200, function () {
          $('#adminPasswordBlock').val('');
          $('#modalErrorBlock').text('');
          location.reload(); 
        });
      } else {
        $('#modalErrorBlock').text(res);
      }
    }
  });
}

$(document).ready(function () {
  if (localStorage.getItem('blockSuccess') === '1') {
    $.toast({
      heading: 'Success',
      text: 'User has been blocked successfully.',
      icon: 'success',
      position: 'top-right',
      hideAfter: 2000,
      stack: 3
    });
    localStorage.removeItem('blockSuccess');
  }
});

  $('.btn-unblock').on('click', function () {
    selectedUserId = $(this).data('user-id');
    $('#userIdToUnblock').val(selectedUserId);
    $('#unblockModal').fadeIn(200);
  });

  function closeModal() {
    $('#unblockModal').fadeOut(200);
    $('#adminPassword').val('');
    $('#modalError').text('');
  }

  function confirmUnblock() {
  const password = $('#adminPassword').val();
  const userId = $('#userIdToUnblock').val();

  if (!password) {
    $('#modalError').text("Please enter your password.");
    return;
  }

  $.ajax({
    url: '../../auth/unblock-user.php',
    method: 'POST',
    data: {
      admin_id: <?= $_SESSION['user_id'] ?>,
      password: password,
      user_id: userId
    },
    success: function (res) {
      if (res.trim() === 'success') {
        localStorage.setItem('unblockSuccess', '1');
        $('#unblockModal').fadeOut(200, function () {
          $('#adminPassword').val('');
          $('#modalError').text('');
          location.reload(); 
        });
      } else {
        $('#modalError').text(res);
      }
    }
  });
}

$(document).ready(function () {
  if (localStorage.getItem('unblockSuccess') === '1') {
    $.toast({
      heading: 'Success',
      text: 'User has been unblocked successfully.',
      icon: 'success',
      position: 'top-right',
      hideAfter: 2000,
      stack: 3
    });
    localStorage.removeItem('unblockSuccess');
  }
});

</script>
</body>
</html>
