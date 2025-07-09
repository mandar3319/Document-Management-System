<?php
session_start();
ob_start();
ini_set('display_errors', 0); // Don't show errors in AJAX
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../php-error.log');


include '../config/conn.php';
include_once(dirname(__DIR__) . "/classes/Utils.php");


if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$c_id = $_SESSION['c_id'];

// Create folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? intval($_GET['parent_id']) : null;
    $created_by = $user_id;
    $is_protected = 0;

    if (!empty($name)) {
        $query = "INSERT INTO folders (c_id, name, description, parent_id, created_by, is_protected, created_at, updated_at, deleted_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NULL)";
        $stmt = $conn->prepare($query);

        // If $parent_id is null, bind as null
        if ($parent_id === null) {
            $stmt->bind_param("issiii", $c_id, $name, $description, $parent_id, $created_by, $is_protected);
        } else {
            $stmt->bind_param("issiii", $c_id, $name, $description, $parent_id, $created_by, $is_protected);
        }

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'folder_id' => $stmt->insert_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Insert failed: ' . $stmt->error
            ]);
        }
        exit();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Folder name is required'
        ]);
        exit();
    }
}

// Helper function to get all descendant folder IDs recursively
function getAllDescendantFolderIds($conn, $folder_id) {
    $ids = [];
    $stmt = $conn->prepare("SELECT id FROM folders WHERE parent_id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $folder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
        $ids = array_merge($ids, getAllDescendantFolderIds($conn, $row['id']));
    }
    return $ids;
}

// Delete folder (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_folder') {
    $folder_id = intval($_POST['folder_id']);

    // Get all descendant folder IDs
    $allIds = [$folder_id];
    $descendants = getAllDescendantFolderIds($conn, $folder_id);
    if (!empty($descendants)) { 
        $allIds = array_merge($allIds, $descendants);
    }

    // Prepare the placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($allIds), '?'));
    $types = str_repeat('i', count($allIds));

    $now = date('Y-m-d H:i:s');
    $query = "UPDATE folders SET is_deleted = 1, deleted_at = ? WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    // Merge $now and all folder IDs for binding
    $params = array_merge([$now], $allIds);

    // Bind parameters dynamically
    $bind_names[] = $types = 's' . $types;
    foreach ($params as $k => $param) {
        $bind_names[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed or not permitted.']);
    }
    exit();
}

// AJAX: Return folder count
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_folder_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM folders WHERE c_id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $_SESSION['c_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;
    if ($row = $result->fetch_assoc()) {
        $count = $row['cnt'];
    }
    echo json_encode(['count' => $count]);
    exit();
}

// AJAX: Return folder list HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_folder_list') {
    $folders = [];
    $stmt = $conn->prepare("SELECT * FROM folders WHERE c_id = ? AND is_deleted = 0 AND parent_id IS NULL ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['c_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch users for dropdown (for all modals)
    $userOptions = '';
    $userStmt = $conn->prepare("SELECT id, email FROM users WHERE c_id = ? AND is_active = 1");
    $userStmt->bind_param("i", $_SESSION['c_id']);
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    while ($u = $userRes->fetch_assoc()) {
        $userOptions .= '<option value="' . htmlspecialchars($u['id']) . '">' . htmlspecialchars($u['email']) . '</option>';
    }

    ob_start();
    while ($folder = $result->fetch_assoc()) {
        // Render the card HTML (copy from below, but PHP only)
        ?>
        <div class="card" data-folder-id="<?= $folder['id'] ?>" >
            <div class="card-header" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z"/>
                </svg>
            </div>
            <div class="card-body" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <div class="card-title"><?= htmlspecialchars($folder['name']) ?></div>
                <div class="card-user" >
                <?php
                    $created = $folder['created_by'];
                    $userQuery = "SELECT name, profile FROM users WHERE id = '$created'";
                    $userResult = mysqli_query($conn, $userQuery);
                    $userName = "Unknown";
                    $profileImage = "https://i.pravatar.cc/40?img=" . rand(1, 70);
                    if ($userResult && mysqli_num_rows($userResult) > 0) {
                        $user = mysqli_fetch_assoc($userResult);
                        if (!empty($user['name'])) $userName = $user['name'];
                        if (!empty($user['profile'])) $profileImage = $user['profile'];
                    }
                    $user_id = $_SESSION['user_id'] ?? null;
                    $displayName = ($created == $user_id) ? "You" : $userName;
                ?>
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="User" width="40" height="40" style="border-radius:50%;">
                <span><?= htmlspecialchars($displayName) ?></span>
                </div>
                <div class="tags">
                    <div class="tag">Folder</div>
                    <div class="tag">From DB</div>
                </div>
            </div>
            <div class="card-footer">
                <div><?= date("d M Y", strtotime($folder['created_at'])) ?></div>
                <div class="actions">
                    <!-- Share Button (only if user has can_share permission) -->
                    <?php
                    if (Utils::canShareDocuments($conn, $user_id)) {
                    ?>
                    <i class="fas fa-share-alt" title="Share" data-bs-toggle="modal" data-bs-target="#shareFolderModal_<?= $folder['id'] ?>" style="cursor:pointer;"></i>
                    <?php } 
                    if (Utils::canDeleteDocuments($conn, user_id: $user_id)){
                    ?>
                    <!-- <i class="fas fa-code-branch" title="Version Control"></i> -->
                    <i class="fas fa-trash-alt" title="Delete" onclick="event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, this)"></i>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="shareFolderModal_<?= $folder['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title">Share Folder: <?= htmlspecialchars($folder['name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">

                <!-- Permissions Table -->
                <table class="table text-center mb-4">
                  <thead class="table-light">
                    <tr>
                      <th>View</th>
                      <th>Write</th>
                      <th>Edit</th>
                      <th>Delete</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="perm-icon" id="perm-view"><span class="icon">❌</span></td>
                      <td class="perm-icon" id="perm-write"><span class="icon">❌</span></td>
                      <td class="perm-icon" id="perm-edit"><span class="icon">❌</span></td>
                      <td class="perm-icon" id="perm-delete"><span class="icon">❌</span></td>
                    </tr>
                  </tbody>
                </table>

                <!-- Add New Permission -->
                <h6>Add New Permission</h6>

                <div class="mb-3">
                  <label class="form-label">Select User</label>
                  <select class="form-select is-invalid">
                    <option selected disabled>Select a user...</option>
                    <?= $userOptions ?>
                  </select>
                </div>

                <!-- Permission Checkboxes -->
                <div class="mb-3">
                  <label class="form-label">Permissions</label>

                  <label class="custom-check">
                    <input type="checkbox" id="can-view">
                    <span class="icon-box"></span>
                    Can View <small class="text-muted">(User can view folder contents)</small>
                  </label>

                  <label class="custom-check">
                    <input type="checkbox" id="can-write">
                    <span class="icon-box"></span>
                    Can Write <small class="text-muted">(User can upload documents)</small>
                  </label>

                  <label class="custom-check">
                    <input type="checkbox" id="can-edit">
                    <span class="icon-box"></span>
                    Can Edit <small class="text-muted">(User can edit folder and document details)</small>
                  </label>

                  <label class="custom-check">
                    <input type="checkbox" id="can-delete">
                    <span class="icon-box"></span>
                    Can Delete <small class="text-muted">(User can delete folder contents)</small>
                  </label>
                </div>

              </div>

              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger">Add Permission</button>
              </div>
            </div>
          </div>
        </div>
        <?php
    }
    $html = ob_get_clean();
    echo json_encode(['html' => $html]);
    exit();
}

// Fetch folders
$folders = [];

$stmt = $conn->prepare("SELECT * FROM folders WHERE c_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
$stmt->bind_param("i", $c_id);  // Only one parameter expected here
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $folders[] = $row;
}

// Fetch users for dropdown (for all modals)
$userOptions = '';
$userStmt = $conn->prepare("SELECT id, email FROM users WHERE c_id = ? AND is_active = 1");
$userStmt->bind_param("i", $c_id);
$userStmt->execute();
$userRes = $userStmt->get_result();
while ($u = $userRes->fetch_assoc()) {
    $userOptions .= '<option value="' . htmlspecialchars($u['id']) . '">' . htmlspecialchars($u['email']) . '</option>';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <?php include "components/head.php"; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,500,600&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
      padding: 2rem;
    }

    .form-container {
      max-width: 600px;
      margin: 0 auto 2rem;
      background: #fff;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .form-container input {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .form-container button {
      background-color: #fbbf24;
      border: none;
      color: white;
      padding: 0.75rem 1.5rem;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
    }

    .folder-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .card {
      width: 222px;
      height: 250px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.07);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .card-header {
      background-color: #fef3c7;
      height: 90px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card-header svg {
      width: 50px;
      height: 50px;
      fill: #fbbf24;
    }

    .card-body {
      padding: 0.75rem 1rem;
      flex-grow: 1;
    }

    .card-title {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 0.3rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card-user {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 0.5rem;
    }

    .card-user img {
      width: 24px;
      height: 24px;
      border-radius: 50%;
    }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 0.25rem;
    }

    .tag {
      background-color: #f3f4f6;
      color: #4b5563;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 0.65rem;
    }

    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.7rem;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
      padding: 0.5rem 0.75rem;
    }

    .card-footer i {
      margin-left: 10px;
      cursor: pointer;
      color: #6b7280;
    }

    .card-footer i:hover {
      color: #111827;
    }

    .actions {
      display: flex;
      align-items: center;
    }

    .form-control, 
.form-select {
    border: 1px solid #ddd; 
    border-radius: 4px; 
    padding: 8px;
    box-shadow: none;
}

.form-control:focus, 
 .form-select:focus {
    border-color: #007bff; 
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); 
}
    .form-control,
   .form-select {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px;
        box-shadow: none;
    }

     .form-control:focus,
     .form-select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    
/* Custom Checkbox Styling */
.custom-check {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  cursor: pointer;
  user-select: none;
}

.custom-check input {
  display: none;
}

.custom-check .icon-box {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background-color: #f8d7da;
  border: 2px solid #dc3545;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  transition: all 0.3s ease;
  font-size: 14px;
  color: #dc3545;
}

.custom-check input:checked + .icon-box {
  background-color: #dc3545;
  color: white;
  transform: scale(1.1);
}

.custom-check input:checked + .icon-box::before {
  content: '\f00c'; /* FontAwesome check */
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
}

.custom-check input:not(:checked) + .icon-box::before {
  content: '\f00d'; /* FontAwesome times */
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
}

 .perm-icon .icon {
    font-size: 2rem;
    color: #e74c3c; /* red by default */
    transition: all 0.3s ease;
  }

  .perm-icon .icon.active {
    color: #27ae60; /* green when active */
    transform: scale(1.2);
  }

  .custom-check {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-weight: 500;
  }

  .custom-check input[type="checkbox"] {
    display: none;
  }

  .custom-check .check-icon {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8d7da;
    color: #e74c3c;
    border: 1px solid #e74c3c;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .custom-check input[type="checkbox"]:checked + .check-icon {
    background: #d4edda;
    color: #28a745;
    border-color: #28a745;
    transform: scale(1.1);
  }

  .custom-check small {
    font-weight: normal;
    color: #777;
  }
  </style>
</head>
<body class="g-sidenav-show bg-gray-100">
  <?php include "components/sidebar.php"; ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php include "components/navbar.php"; ?>
    <div class="container-fluid py-2">
      <div class="row mb-4">
        <div class="col-md-6 d-flex flex-column justify-content-center">
          <h4>
            Folders
            <!-- <span class="badge bg-secondary" id="folderCount"><?= count($folders) ?></span> -->
          </h4>
          <p class="text-muted mb-0"><?php  echo $c_id; echo $user_id ?>Manage your documents in folders</p>
        </div>
        <?php 
                if (Utils::isSuperadmin($conn, $user_id) ) {


?>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
          <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addFolderModal">
            <i class="fas fa-plus me-2"></i> Add Folder
          </button>
        </div>

        <?php } ?>
      </div>

      <div class="folder-grid" id="folderList">
      <?php foreach ($folders as $folder): ?>
    <div class="card" data-folder-id="<?= $folder['id'] ?>" >
        <div class="card-header" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z"/>
            </svg>
        </div>
        <div class="card-body" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
    <div class="card-title"><?= htmlspecialchars($folder['name']) ?></div>
    <div class="card-user" >
    <?php
        // Get created_by from the folder
        $created = $folder['created_by'];

        // Debug: Print the created user ID
        // echo "Created by user ID: " . $created;

        // Run the query
        $userQuery = "SELECT name, profile FROM users WHERE id = '$created'";
        $userResult = mysqli_query($conn, $userQuery);

        // Check for SQL errors
        if (!$userResult) {
            echo "MySQL Error: " . mysqli_error($conn);
        }

        // Default fallback
        $userName = "Unknown";
        $profileImage = "https://i.pravatar.cc/40?img=" . rand(1, 70); // fallback avatar

        // If user is found
        if ($userResult && mysqli_num_rows($userResult) > 0) {
            $user = mysqli_fetch_assoc($userResult);

            // Now fetch from DB safely
            if (!empty($user['name'])) {
                $userName = $user['name'];
            }

            if (!empty($user['profile'])) {
                $profileImage = $user['profile'];
            }
        } else {
            // Debug if no user found
            echo "<!-- No user found with ID: $created -->";
        }

        // Optional: get current session user ID
        $user_id = $_SESSION['user_id'] ?? null;
        $displayName = ($created == $user_id) ? "You" : $userName;
    ?>

    <img src="<?= htmlspecialchars($profileImage) ?>" alt="User" width="40" height="40" style="border-radius:50%;">
    <span><?= htmlspecialchars($displayName) ?></span>
</div>

    <div class="tags">
        <div class="tag">Folder</div>
        <div class="tag">From DB</div>
    </div>
</div>

        <div class="card-footer">
            <div><?= date("d M Y", strtotime($folder['created_at'])) ?></div>
            <div class="actions">
                <!-- Share Button (only if user has can_share permission) -->
                <?php
                // include_once(dirname(__DIR__) . "/classes/Utils.php");
                if (Utils::canShareDocuments($conn, $user_id)) {
                ?>
                <i class="fas fa-share-alt" title="Share" data-bs-toggle="modal" data-bs-target="#shareFolderModal_<?= $folder['id'] ?>" style="cursor:pointer;"></i>
                <?php } ?>
                <i class="fas fa-code-branch" title="Version Control"></i>
                <i class="fas fa-trash-alt" title="Delete" onclick="event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, this)"></i>
            </div>
        </div>
    </div>
    <!-- ...existing code... -->
<?php endforeach; ?>

      </div>

      <!-- Modal -->
      <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="folderForm">
              <?php

              ?>
              <div class="modal-header">
                <h5 class="modal-title" id="addFolderModalLabel">Create New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <input type="text" id="folderName" class="form-control" placeholder="Enter folder name..." required />
                </div>
                <div class="mb-3">
                  <input id="folderDesc" class="form-control" placeholder="Description (optional)">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Create Folder</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php include "components/footer.php"; ?>
    </div>
  </main>

  <!-- JS -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script>
    const form = document.getElementById('folderForm');
    const folderList = document.getElementById('folderList');
    const folderCount = document.getElementById('folderCount');

    // Fetch and update folder count
    function updateFolderCount() {
      const formData = new FormData();
      formData.append('action', 'get_folder_count');
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (folderCount && typeof data.count !== 'undefined') {
          folderCount.textContent = data.count;
        }
      });
    }

    // Fetch and update folder list/grid
    function updateFolderList() {
      const formData = new FormData();
      formData.append('action', 'get_folder_list');
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.html !== undefined) {
          folderList.innerHTML = data.html;
          // Optionally re-attach event listeners if needed
        }
      });
    }

    // Delegate delete click to folderList for dynamic elements
    folderList.addEventListener('click', function(e) {
      if (e.target.classList.contains('fa-trash-alt')) {
        e.stopPropagation();
        const card = e.target.closest('.card');
        const folderId = card.getAttribute('data-folder-id');
        deleteFolder(folderId, card);
      }
    });

    function deleteFolder(folderId, cardElement) {
      Swal.fire({
        title: 'Are you absolutely sure?',
        text: "This action cannot be undone. The folder and its contents will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Final Confirmation',
            text: "Please confirm again to delete the folder.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
          }).then((finalResult) => {
            if (finalResult.isConfirmed) {
              const formData = new FormData();
              formData.append('action', 'delete_folder');
              formData.append('folder_id', folderId);

              fetch(window.location.href, {
                method: 'POST',
                body: formData
              })
              .then(res => res.json())
              .then(data => {
                if (data.status === 'success') {
                  // cardElement.remove(); // Remove this line
                  Swal.fire('Deleted!', 'Folder has been deleted.', 'success');
                  updateFolderCount();
                  updateFolderList(); // Refresh folder grid
                } else {
                  Swal.fire('Error', data.message || 'Delete failed.', 'error');
                }
              })
              .catch(() => Swal.fire('Error', 'Network or server error.', 'error'));
            }
          });
        }
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const name = document.getElementById('folderName').value.trim();
      const desc = document.getElementById('folderDesc').value.trim();
      if (!name) return;

      const formData = new FormData();
      formData.append('action', 'create_folder');
      formData.append('name', name);
      formData.append('description', desc);

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(   data => {
        if (data.status === 'success') {
          // Remove manual card creation, just refresh list
          form.reset();
          bootstrap.Modal.getInstance(document.getElementById('addFolderModal')).hide();
          updateFolderCount();
          updateFolderList();
        } else {
           alert("Error: " + data.message);
        }
      })
     .catch(err => alert("Error: " + err));
    });

    
    updateFolderCount();
    updateFolderList();

   
  </script>
</body>
</html>