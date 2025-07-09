<?php
session_start();
include '../config/conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

// AJAX handler to return logs in JSON
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fetch_logs') {
    header('Content-Type: application/json');

    $c_id = $_SESSION['c_id'];
    $query = "SELECT `id`, `user_id`, `c_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_addr`, `created_at` FROM `activity_logs` WHERE c_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch user details
    $userDetails = [];
    foreach ($logs as &$log) {
        $user_id = $log['user_id'];
        if (!isset($userDetails[$user_id])) {
            $userQuery = "SELECT `id`, `name`, `email`, `profile` FROM `users` WHERE `id` = ?";
            $userStmt = $conn->prepare($userQuery);
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userDetails[$user_id] = $userResult->fetch_assoc();
            $userStmt->close();
        }

        $user = $userDetails[$user_id];
        $log['user_name'] = $user['name'] ?? 'Unknown';
        $log['user_email'] = $user['email'] ?? 'N/A';
        $log['user_profile'] = $user['profile'] ?? '';
    }

    echo json_encode(['data' => $logs]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include "components/head.php"; ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
</head>

<body class="g-sidenav-show bg-gray-100">
  <?php include "components/sidebar.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php include "components/navbar.php"; ?>

    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-3">
                <table id="logTable" class="table align-items-center mb-0 display nowrap" style="width:100%">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>User</th>
                      <th class="text-center">Action</th>
                      <th>Description</th>
                      <th class="text-center">IP</th>
                    </tr>
                  </thead>
                  <tbody></tbody> <!-- Populated by DataTables via AJAX -->
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include "components/footer.php"; ?>
    </div>
  </main>

  <!-- Core JS -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

  <!-- DataTables & jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <!-- DataTable Init -->
  <script>
    $(document).ready(function () {
      $('#logTable').DataTable({
        ajax: '?ajax=fetch_logs',
        columns: [
          {
            data: 'created_at',
            render: function (data) {
              return `<p class="text-xs text-secondary mb-0">${data}</p>`;
            }
          },
          {
            data: null,
            render: function (row) {
              const initials = row.user_name ? row.user_name.charAt(0).toUpperCase() : 'U';
              if (row.user_profile) {
                return `
                  <div class="d-flex px-2 py-1">
                    <div>
                      <img src="${row.user_profile}" class="avatar avatar-sm me-3 border-radius-lg" alt="user">
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-sm">${row.user_name}</h6>
                      <p class="text-xs text-secondary mb-0">${row.user_email}</p>
                    </div>
                  </div>`;
              } else {
                return `
                  <div class="d-flex px-2 py-1">
                    <div>
                      <div class="avatar avatar-sm me-3 border-radius-lg" style="background: linear-gradient(45deg, #000, #fff); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        ${initials}
                      </div>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-sm">${row.user_name}</h6>
                      <p class="text-xs text-secondary mb-0">${row.user_email}</p>
                    </div>
                  </div>`;
              }
            }
          },
          {
            data: 'action',
            className: 'text-center',
            render: function (data) {
              const badgeClass = data === 'Login' ? 'bg-gradient-success' : 'bg-gradient-secondary';
              return `<span class="badge badge-sm ${badgeClass}">${data}</span>`;
            }
          },
          {
            data: 'description',
            render: function (data) {
              return `<p class="text-xs text-secondary mb-0">${data}</p>`;
            }
          },
          {
            data: 'ip_addr',
            className: 'text-center',
            render: function (data) {
              return `<span class="text-secondary text-xs font-weight-bold">${data}</span>`;
            }
          }
        ],
        dom: 'Bfrtip',
        buttons: ['csv', 'excel', 'print'],
        pageLength: 10,
        lengthMenu: [
          [5, 10, 25, 50, -1],
          [5, 10, 25, 50, "All"]
        ],
        responsive: true
      });
    });
  </script>
</body>
</html>
