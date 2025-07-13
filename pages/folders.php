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

    $allIds = [$folder_id];
    $descendants = getAllDescendantFolderIds($conn, $folder_id);
    if (!empty($descendants)) { 
        $allIds = array_merge($allIds, $descendants);
    }

    $placeholders = implode(',', array_fill(0, count($allIds), '?'));
    $types = str_repeat('i', count($allIds));

    $now = date('Y-m-d H:i:s');
    $query = "UPDATE folders SET is_deleted = 1, deleted_at = ? WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    $params = array_merge([$now], $allIds);
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

// AJAX: Share folder functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'share_folder') {
    $folder_id = intval($_POST['folder_id']);
    $user_id = intval($_POST['user_id']);
    $can_view = isset($_POST['can_view']) ? 1 : 0;
    $can_write = isset($_POST['can_write']) ? 1 : 0;
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_delete = isset($_POST['can_delete']) ? 1 : 0;

    // Check if permission already exists
    $stmt = $conn->prepare("SELECT id FROM folder_permission WHERE folder_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $folder_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Permission already exists for this user']);
        exit();
    }

    // Get role_id and email from users table
    $userStmt = $conn->prepare("SELECT role_id, email FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    $role_id = $user['role_id'];
    $user_email = $user['email'];

    // Insert new permission with role_id
    $stmt = $conn->prepare("
        INSERT INTO folder_permission 
        (folder_id, user_id, role_id, can_view, can_write, can_edit, can_delete, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("iiiiiii", $folder_id, $user_id, $role_id, $can_view, $can_write, $can_edit, $can_delete);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'permission_id' => $stmt->insert_id,
            'username' => $user_email,
            'can_view' => $can_view,
            'can_write' => $can_write,
            'can_edit' => $can_edit,
            'can_delete' => $can_delete,
            'role_id' => $role_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    exit();
}


// AJAX: Get existing permissions for a folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_folder_permissions') {
    $folder_id = intval($_POST['folder_id']);
    
    $stmt = $conn->prepare("
        SELECT fp.id, u.email, fp.can_view, fp.can_write, fp.can_edit, fp.can_delete 
        FROM folder_permission fp
        JOIN users u ON fp.user_id = u.id
        WHERE fp.folder_id = ?
    ");
    $stmt->bind_param("i", $folder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    echo json_encode(['success' => true, 'permissions' => $permissions]);
    exit();
}



// AJAX: Remove permission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_permission') {
    $permission_id = intval($_POST['permission_id']);
    
    $stmt = $conn->prepare("DELETE FROM folder_permission WHERE id = ?");
    $stmt->bind_param("i", $permission_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => $stmt->affected_rows > 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit();
}

// AJAX: Remove permission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_permission') {
    $permission_id = intval($_POST['permission_id']);
    
    $stmt = $conn->prepare("DELETE FROM folder_permission WHERE id = ?");
    $stmt->bind_param("i", $permission_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => $stmt->affected_rows > 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
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
        // Get existing permissions for this folder
        $permStmt = $conn->prepare("
            SELECT fp.id, u.email, fp.can_view, fp.can_write, fp.can_edit, fp.can_delete 
            FROM folder_permission fp
            JOIN users u ON fp.user_id = u.id
            WHERE fp.folder_id = ?
        ");
        $permStmt->bind_param("i", $folder['id']);
        $permStmt->execute();
        $permResult = $permStmt->get_result();
        $existingPermissions = [];
        while ($perm = $permResult->fetch_assoc()) {
            $existingPermissions[] = $perm;
        }
        ?>
        <div class="card" data-folder-id="<?= $folder['id'] ?>">
            <div class="card-header" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z"/>
                </svg>
            </div>
            <div class="card-body" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <div class="card-title"><?= htmlspecialchars($folder['name']) ?></div>
                <div class="card-user">
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
                    <?php if (Utils::canShareDocuments($conn, $user_id)) { ?>
                    <i class="fas fa-share-alt" title="Share" data-bs-toggle="modal" data-bs-target="#shareFolderModal_<?= $folder['id'] ?>" style="cursor:pointer;"></i>
                    <?php } 
                    if (Utils::canDeleteDocuments($conn, user_id: $user_id)){
                    ?>
                    <i class="fas fa-trash-alt" title="Delete" onclick="event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, this)"></i>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Share Folder Modal -->
        <div class="modal fade" id="shareFolderModal_<?= $folder['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Share Folder: <?= htmlspecialchars($folder['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Current Permissions -->
                        <div class="mb-4">
                            <h6>Current Permissions</h6>
                            <div class="table-responsive">
                                <table class="table table-hover" id="currentPermissions_<?= $folder['id'] ?>">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th class="text-center">View</th>
                                            <th class="text-center">Write</th>
                                            <th class="text-center">Edit</th>
                                            <th class="text-center">Delete</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($existingPermissions as $perm): ?>
                                        <tr data-permission-id="<?= $perm['id'] ?>">
                                            <td><?= htmlspecialchars($perm['email']) ?></td>
                                            <td class="text-center"><?= $perm['can_view'] ? '✅' : '❌' ?></td>
                                            <td class="text-center"><?= $perm['can_write'] ? '✅' : '❌' ?></td>
                                            <td class="text-center"><?= $perm['can_edit'] ? '✅' : '❌' ?></td>
                                            <td class="text-center"><?= $perm['can_delete'] ? '✅' : '❌' ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-permission" 
                                                        data-permission-id="<?= $perm['id'] ?>">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Add New Permission -->
                        <div class="border-top pt-3">
                            <h6>Add New Permission</h6>
                            <form id="shareForm_<?= $folder['id'] ?>">
                                <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                                <input type="hidden" name="action" value="share_folder">
                                
                                <div class="mb-3">
                                    <label for="user_id_<?= $folder['id'] ?>" class="form-label">Select User</label>
                                    <select class="form-select" id="user_id_<?= $folder['id'] ?>" name="user_id" required>
                                        <option value="" selected disabled>Select a user...</option>
                                        <?= $userOptions ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a user</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Permissions</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="custom-check d-block">
                                                <input type="checkbox" id="can-view_<?= $folder['id'] ?>" name="can_view" class="permission-check">
                                                <span class="icon-box"></span>
                                                Can View <small class="text-muted">(User can view folder contents)</small>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="custom-check d-block">
                                                <input type="checkbox" id="can-write_<?= $folder['id'] ?>" name="can_write" class="permission-check">
                                                <span class="icon-box"></span>
                                                Can Write <small class="text-muted">(User can upload documents)</small>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="custom-check d-block">
                                                <input type="checkbox" id="can-edit_<?= $folder['id'] ?>" name="can_edit" class="permission-check">
                                                <span class="icon-box"></span>
                                                Can Edit <small class="text-muted">(User can edit folder and document details)</small>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="custom-check d-block">
                                                <input type="checkbox" id="can-delete_<?= $folder['id'] ?>" name="can_delete" class="permission-check">
                                                <span class="icon-box"></span>
                                                Can Delete <small class="text-muted">(User can delete folder contents)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="submitBtn_<?= $folder['id'] ?>" onclick="submitShareForm(<?= $folder['id'] ?>)">
                            <span class="submit-text">Add Permission</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
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
$stmt->bind_param("i", $c_id);
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
      background-color: #28a745;
      border-color: #28a745;
      color: white;
    }

    .custom-check input:checked + .icon-box::before {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
    }

    .custom-check small {
      font-weight: normal;
      color: #777;
    }

    /* Animation for new permission row */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .new-permission-row {
      animation: fadeIn 0.5s ease-out forwards;
    }
    
    /* Toast notification */
    .toast-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1100;
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
          </h4>
          <p class="text-muted mb-0">Manage your documents in folders</p>
        </div>
        <?php if (Utils::isSuperadmin($conn, $user_id)): ?>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
          <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addFolderModal">
            <i class="fas fa-plus me-2"></i> Add Folder
          </button>
        </div>
        <?php endif; ?>
      </div>

      <div class="folder-grid" id="folderList">
        <?php foreach ($folders as $folder): ?>
          <div class="card" data-folder-id="<?= $folder['id'] ?>">
            <div class="card-header" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z"/>
                </svg>
            </div>
            <div class="card-body" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
                <div class="card-title"><?= htmlspecialchars($folder['name']) ?></div>
                <div class="card-user">
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
                    <?php if (Utils::canShareDocuments($conn, $user_id)): ?>
                    <i class="fas fa-share-alt" title="Share" data-bs-toggle="modal" data-bs-target="#shareFolderModal_<?= $folder['id'] ?>" style="cursor:pointer;"></i>
                    <?php endif; ?>
                    <?php if (Utils::canDeleteDocuments($conn, user_id: $user_id)): ?>
                    <i class="fas fa-trash-alt" title="Delete" onclick="event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, this)"></i>
                    <?php endif; ?>
                </div>
            </div>
          </div>

          <!-- Share Folder Modal -->
          <div class="modal fade" id="shareFolderModal_<?= $folder['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Share Folder: <?= htmlspecialchars($folder['name']) ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                  <!-- Current Permissions -->
                  <div class="mb-4">
                    <h6>Current Permissions</h6>
                    <div class="table-responsive">
                      <table class="table table-hover" id="currentPermissions_<?= $folder['id'] ?>">
                        <thead class="table-light">
                          <tr>
                            <th>User</th>
                            <th class="text-center">View</th>
                            <th class="text-center">Write</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          // Get existing permissions for this folder
                          $permStmt = $conn->prepare("
                              SELECT fp.id, u.email, fp.can_view, fp.can_write, fp.can_edit, fp.can_delete 
                              FROM folder_permission fp
                              JOIN users u ON fp.user_id = u.id
                              WHERE fp.folder_id = ?
                          ");
                          $permStmt->bind_param("i", $folder['id']);
                          $permStmt->execute();
                          $permResult = $permStmt->get_result();
                          while ($perm = $permResult->fetch_assoc()): ?>
                          <tr data-permission-id="<?= $perm['id'] ?>">
                            <td><?= htmlspecialchars($perm['email']) ?></td>
                            <td class="text-center"><?= $perm['can_view'] ? '✅' : '❌' ?></td>
                            <td class="text-center"><?= $perm['can_write'] ? '✅' : '❌' ?></td>
                            <td class="text-center"><?= $perm['can_edit'] ? '✅' : '❌' ?></td>
                            <td class="text-center"><?= $perm['can_delete'] ? '✅' : '❌' ?></td>
                            <td>
                              <button type="button" class="btn btn-sm btn-outline-danger remove-permission" 
                                      data-permission-id="<?= $perm['id'] ?>">
                                Remove
                              </button>
                            </td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Add New Permission -->
                  <div class="border-top pt-3">
                    <h6>Add New Permission</h6>
                    <form id="shareForm_<?= $folder['id'] ?>">
                      <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                      <input type="hidden" name="action" value="share_folder">
                      
                      <div class="mb-3">
                        <label for="user_id_<?= $folder['id'] ?>" class="form-label">Select User</label>
                        <select class="form-select" id="user_id_<?= $folder['id'] ?>" name="user_id" required>
                          <option value="" selected disabled>Select a user...</option>
                          <?= $userOptions ?>
                        </select>
                        <div class="invalid-feedback">Please select a user</div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="custom-check d-block">
                              <input type="checkbox" id="can-view_<?= $folder['id'] ?>" name="can_view" class="permission-check">
                              <span class="icon-box"></span>
                              Can View <small class="text-muted">(User can view folder contents)</small>
                            </label>
                          </div>
                          <div class="col-md-6">
                            <label class="custom-check d-block">
                              <input type="checkbox" id="can-write_<?= $folder['id'] ?>" name="can_write" class="permission-check">
                              <span class="icon-box"></span>
                              Can Write <small class="text-muted">(User can upload documents)</small>
                            </label>
                          </div>
                          <div class="col-md-6">
                            <label class="custom-check d-block">
                              <input type="checkbox" id="can-edit_<?= $folder['id'] ?>" name="can_edit" class="permission-check">
                              <span class="icon-box"></span>
                              Can Edit <small class="text-muted">(User can edit folder and document details)</small>
                            </label>
                          </div>
                          <div class="col-md-6">
                            <label class="custom-check d-block">
                              <input type="checkbox" id="can-delete_<?= $folder['id'] ?>" name="can_delete" class="permission-check">
                              <span class="icon-box"></span>
                              Can Delete <small class="text-muted">(User can delete folder contents)</small>
                            </label>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="submitBtn_<?= $folder['id'] ?>" onclick="submitShareForm(<?= $folder['id'] ?>)">
                    <span class="submit-text">Add Permission</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Add Folder Modal -->
      <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="folderForm">
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
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
  </main>

  <!-- JS -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script>
    const form = document.getElementById('folderForm');
    const folderList = document.getElementById('folderList');

    // Helper function to show toast notifications
    function showToast(message, type = 'success') {
      const toastContainer = document.querySelector('.toast-container');
      const toast = document.createElement('div');
      toast.className = `toast show align-items-center text-white bg-${type} border-0`;
      toast.setAttribute('role', 'alert');
      toast.setAttribute('aria-live', 'assertive');
      toast.setAttribute('aria-atomic', 'true');
      
      toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      
      toastContainer.appendChild(toast);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 5000);
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
      
      // Handle remove permission button clicks
      if (e.target.classList.contains('remove-permission') || 
          (e.target.parentElement && e.target.parentElement.classList.contains('remove-permission'))) {
        const button = e.target.classList.contains('remove-permission') ? e.target : e.target.parentElement;
        const permissionId = button.getAttribute('data-permission-id');
        removePermission(permissionId, button.closest('tr'));
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
                  Swal.fire('Deleted!', 'Folder has been deleted.', 'success');
                  updateFolderList();
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

    // Submit share folder form
    function submitShareForm(folderId) {
      const form = document.getElementById(`shareForm_${folderId}`);
      const submitBtn = document.getElementById(`submitBtn_${folderId}`);
      const submitText = submitBtn.querySelector('.submit-text');
      const spinner = submitBtn.querySelector('.spinner-border');
      
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }
      
      // Show loading state
      submitText.textContent = 'Adding...';
      spinner.classList.remove('d-none');
      submitBtn.disabled = true;
      
      const formData = new FormData(form);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          
          const newRow = document.createElement('tr');
          newRow.className = 'new-permission-row';
          newRow.setAttribute('data-permission-id', data.permission_id);
          newRow.innerHTML = `
            <td>${data.username}</td>
            <td class="text-center">${data.can_view ? '✅' : '❌'}</td>
            <td class="text-center">${data.can_write ? '✅' : '❌'}</td>
            <td class="text-center">${data.can_edit ? '✅' : '❌'}</td>
            <td class="text-center">${data.can_delete ? '✅' : '❌'}</td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-danger remove-permission" 
                      data-permission-id="${data.permission_id}">
                Remove
              </button>
            </td>
          `;
          
          document.querySelector(`#currentPermissions_${folderId} tbody`).prepend(newRow);
          
          // Reset form
          form.reset();
          form.classList.remove('was-validated');
          document.querySelectorAll(`#shareForm_${folderId} .permission-check`).forEach(cb => cb.checked = false);
          
          showToast('Permission added successfully!');
        } else {
          showToast(data.message || 'Error adding permission', 'danger');
        }
      })
      .catch(() => {
        showToast('An error occurred. Please try again.', 'danger');
      })
      .finally(() => {
        // Reset button state
        submitText.textContent = 'Add Permission';
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
      });
    }

    // Remove permission
    function removePermission(permissionId, rowElement) {
      if (!confirm('Are you sure you want to remove this permission?')) return;
      
      const formData = new FormData();
      formData.append('action', 'remove_permission');
      formData.append('permission_id', permissionId);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          rowElement.classList.add('table-danger');
          rowElement.style.transition = 'opacity 0.3s ease';
          rowElement.style.opacity = '0';
          
          setTimeout(() => {
            rowElement.remove();
            showToast('Permission removed successfully!');
          }, 300);
        } else {
          showToast('Failed to remove permission', 'danger');
        }
      })
      .catch(() => {
        showToast('An error occurred. Please try again.', 'danger');
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
      .then(data => {
        if (data.status === 'success') {
          form.reset();
          bootstrap.Modal.getInstance(document.getElementById('addFolderModal')).hide();
          updateFolderList();
          showToast('Folder created successfully!');
        } else {
          showToast("Error: " + data.message, 'danger');
        }
      })
      .catch(err => showToast("Error: " + err, 'danger'));
    });

        // Remove permission
    function removePermission(permissionId, rowElement) {
      if (!confirm('Are you sure you want to remove this permission?')) return;
      
      const formData = new FormData();
      formData.append('action', 'remove_permission');
      formData.append('permission_id', permissionId);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          rowElement.classList.add('table-danger');
          rowElement.style.transition = 'opacity 0.3s ease';
          rowElement.style.opacity = '0';
          
          setTimeout(() => {
            rowElement.remove();
            showToast('Permission removed successfully!');
          }, 300);
        } else {
          showToast('Failed to remove permission', 'danger');
        }
      })
      .catch(() => {
        showToast('An error occurred. Please try again.', 'danger');
      });
    }
  </script>
</body>
</html>