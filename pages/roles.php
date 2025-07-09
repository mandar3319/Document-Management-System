<?php
session_start();
include '../config/conn.php';
include_once(dirname(__DIR__) . "/classes/Utils.php");


if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

// Handle AJAX POST for adding a role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $input = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($input['c_id']) ||
        !isset($input['name']) ||
        !isset($input['permissions'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $c_id = $input['c_id'];
    $name = strtolower(trim($input['name']));
    $description = $input['description'] ?? '';
    $permissions = json_encode($input['permissions']);

    // Check if role exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE c_id = ? AND LOWER(name) = ?");
    $stmt->bind_param("is", $c_id, $name);
    $stmt->execute();
    $stmt->bind_result($role_exists);
    $stmt->fetch();
    $stmt->close();

    if ($role_exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Role already exists for this company']);
        exit;
    }

    // Get next id
    $stmt = $conn->prepare("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM roles WHERE c_id = ?");
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $stmt->bind_result($next_id);
    $stmt->fetch();
    $stmt->close();

    // Insert role
    $stmt = $conn->prepare("INSERT INTO roles (id, c_id, name, description, permissions, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $next_id, $c_id, $name, $description, $permissions);

    if ($stmt->execute()) {
        $role_id = $next_id;
        // Activity log
        $user_id = $_SESSION['user_id'] ?? null;
        $action = "create";
        $entity_type = "role";
        $entity_id = $role_id;
        $log_description = "Created role: $name";
        $ip_addr = $_SERVER['REMOTE_ADDR'];

        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("iississ", $user_id, $c_id, $action, $entity_type, $entity_id, $log_description, $ip_addr);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Handle AJAX PUT for editing a role
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $input = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($input['c_id']) ||
        !isset($input['id']) ||
        !isset($input['name']) ||
        !isset($input['permissions'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $c_id = $input['c_id'];
    $id = $input['id'];
    $name = strtolower(trim($input['name']));
    $description = $input['description'] ?? '';
    $permissions = json_encode($input['permissions']);

    // Check if another role with the same name exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE c_id = ? AND LOWER(name) = ? AND id != ?");
    $stmt->bind_param("isi", $c_id, $name, $id);
    $stmt->execute();
    $stmt->bind_result($role_exists);
    $stmt->fetch();
    $stmt->close();

    if ($role_exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Role name already exists for this company']);
        exit;
    }

    // Update role
    $stmt = $conn->prepare("UPDATE roles SET name = ?, description = ?, permissions = ?, updated_at = NOW() WHERE c_id = ? AND id = ?");
    $stmt->bind_param("sssii", $name, $description, $permissions, $c_id, $id);

    if ($stmt->execute()) {
        // Activity log
        $user_id = $_SESSION['user_id'] ?? null;
        $action = "update";
        $entity_type = "role";
        $entity_id = $id;
        $log_description = "Updated role: $name";
        $ip_addr = $_SERVER['REMOTE_ADDR'];

        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("iississ", $user_id, $c_id, $action, $entity_type, $entity_id, $log_description, $ip_addr);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Handle AJAX DELETE for deleting a role
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['c_id']) || !isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $c_id = $input['c_id'];
   
    $id = $input['id'];

    // Check if any users are assigned to this role
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE c_id = ? AND role_id = ?");
    $stmt->bind_param("ii", $c_id, $id);
    $stmt->execute();
    $stmt->bind_result($user_count);
    $stmt->fetch();
    $stmt->close();

    if ($user_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: Users are assigned to this role.']);
        exit;
    }

    // Delete the role
    $stmt = $conn->prepare("DELETE FROM roles WHERE c_id = ? AND id = ?");
    $stmt->bind_param("ii", $c_id, $id);

    if ($stmt->execute()) {
        // Activity log
        $user_id = $_SESSION['user_id'] ?? null;
        $action = "delete";
        $entity_type = "role";
        $entity_id = $id;
        $log_description = "Deleted role ID: $id";
        $ip_addr = $_SERVER['REMOTE_ADDR'];

        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("iississ", $user_id, $c_id, $action, $entity_type, $entity_id, $log_description, $ip_addr);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Handle AJAX GET for fetching a single role (for edit)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_role']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $c_id = $_SESSION['c_id'];
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, name, description, permissions FROM roles WHERE c_id = ? AND id = ?");
    $stmt->bind_param("ii", $c_id, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();
    if ($role) {
        $role['permissions'] = json_decode($role['permissions'], true);
        echo json_encode(['success' => true, 'role' => $role]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Role not found']);
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "components/head.php"; ?>
    <title>Roles Management</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <style>
        table#logTable {
            border-collapse: collapse;
            width: 100%;
            border: 1px solid #ddd;
        }

        table#logTable th,
        table#logTable td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        table#logTable th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        #addRoleModal .form-control {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            box-shadow: none;
        }

        #addRoleModal .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-100">
    <?php include "components/sidebar.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php include "components/navbar.php"; ?>
        <div class="container-fluid py-2">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                          <?php
                if (Utils::isSuperadmin($conn, $user_id)) {
                ?>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Roles</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add Role</button>
                        </div>

                <?php } ?>
                        <div class="card-body">
                            <table id="logTable" class="table align-items-center mb-0 display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                            Role Name</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-15 ps-2">
                                            Description</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                            Users</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                            Created </th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                            Updated</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    # Permissions</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $c_id = $_SESSION['c_id'];
                            $query = "
                                SELECT 
                                    roles.*, 
                                    (
                                        SELECT COUNT(*) 
                                        FROM users 
                                        WHERE users.role_id = roles.id 
                                        AND users.c_id = ?
                                    ) AS user_count
                                FROM roles 
                                WHERE roles.c_id = ?
                                ORDER BY roles.c_id ASC
                            ";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ii", $c_id, $c_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()):
                                $permissions = json_decode($row['permissions'], true);
                                $perm_count = is_array($permissions) ? count($permissions) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-center px-2 py-1 text-xs font-weight-bold mb-0">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($row['description']) ?></p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="text-secondary font-weight-bold"><?= $row['user_count'] ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?= htmlspecialchars($row['created_at']) ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?= htmlspecialchars($row['updated_at']) ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?= $perm_count ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php if (Utils::isSuperadmin($conn, $_SESSION['user_id'])): ?>
                                            <!-- Edit Button -->
                                            <button
                                                class="btn btn-sm btn-primary edit-role-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editRoleModal"
                                                data-role-id="<?= $row['id'] ?>">
                                                Edit
                                            </button>
                                            <!-- Delete Button -->
                                            <button
                                                class="btn btn-sm btn-danger delete-role-btn"
                                                data-role-id="<?= $row['id'] ?>">
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <!-- View Button -->
                                            <button
                                                class="btn btn-sm btn-info view-role-btn"
                                                data-role-id="<?= $row['id'] ?>">
                                                View
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Role Modal -->
        <div class="modal fade" id="viewRoleModal" tabindex="-1" aria-labelledby="viewRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewRoleModalLabel">View Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" id="viewRoleName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="viewRoleDescription" class="form-control" rows="3" readonly></textarea>
                        </div>
                        <label class="form-label">Permissions</label>
                        <ul id="viewRolePermissions" class="list-group mb-3"></ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Role Modal -->
        <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editRoleForm">
                            <input type="hidden" id="editRoleId">
                            <div class="mb-3">
                                <label for="editRoleName" class="form-label">Role Name</label>
                                <input type="text" id="editRoleName" class="form-control" placeholder="Enter role name.." required>
                            </div>
                            <div class="mb-3">
                                <label for="editRoleDescription" class="form-label">Description</label>
                                <textarea id="editRoleDescription" class="form-control" rows="3" placeholder="Enter role description" required></textarea>
                            </div>
                            <label class="form-label">Permissions</label>
                            <div class="mb-3" style="display: flex; flex-wrap: wrap;">
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editUploadDocuments" value="uploadDocuments">
                                    <label class="form-check-label" for="editUploadDocuments">Upload Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editDownloadDocuments" value="downloadDocuments">
                                    <label class="form-check-label" for="editDownloadDocuments">Download Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editCommentDocuments" value="commentDocuments">
                                    <label class="form-check-label" for="editCommentDocuments">Comment on Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editShareDocuments" value="shareDocuments">
                                    <label class="form-check-label" for="editShareDocuments">Share Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editManageDocuments" value="manageDocuments">
                                    <label class="form-check-label" for="editManageDocuments">Manage Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editManageFolders" value="manageFolders">
                                    <label class="form-check-label" for="editManageFolders">Manage Folders</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editManageTags" value="manageTags">
                                    <label class="form-check-label" for="editManageTags">Manage Tags</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="editDeleteDocuments" value="deleteDocuments">
                                    <label class="form-check-label" for="editDeleteDocuments">Delete Documents</label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Update Role</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Role Modal -->
        <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addRoleForm">
                            <div class="mb-3">
                                <label for="roleName" class="form-label">Role Name</label>
                                <input type="text" id="roleName" class="form-control" placeholder="Enter role name.." required>
                            </div>
                            <div class="mb-3">
                                <label for="roleDescription" class="form-label">Description</label>
                                <textarea id="roleDescription" class="form-control" rows="3" placeholder="Enter role description" required></textarea>
                            </div>
                            <label class="form-label">Permissions</label>
                            <div class="mb-3" style="display: flex; flex-wrap: wrap;">
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="uploadDocuments" value="uploadDocuments">
                                    <label class="form-check-label" for="uploadDocuments">Upload Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="downloadDocuments" value="downloadDocuments">
                                    <label class="form-check-label" for="downloadDocuments">Download Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="commentDocuments" value="commentDocuments">
                                    <label class="form-check-label" for="commentDocuments">Comment on Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="shareDocuments" value="shareDocuments">
                                    <label class="form-check-label" for="shareDocuments">Share Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="manageDocuments" value="manageDocuments">
                                    <label class="form-check-label" for="manageDocuments">Manage Documents</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="manageFolders" value="manageFolders">
                                    <label class="form-check-label" for="manageFolders">Manage Folders</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="manageTags" value="manageTags">
                                    <label class="form-check-label" for="manageTags">Manage Tags</label>
                                </div>
                                <div class="form-check" style="flex: 0 0 48%;">
                                    <input class="form-check-input" type="checkbox" id="deleteDocuments" value="deleteDocuments">
                                    <label class="form-check-label" for="deleteDocuments">Delete Documents</label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Add Role</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include "components/footer.php"; ?>
    </main>

    <!-- Core JS Files -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Make sure c_id is passed from PHP session to JavaScript
        const c_id = <?php echo json_encode($_SESSION['c_id'] ?? null); ?>;
        let logTableDT;

        $(document).ready(function() {
            logTableDT = $('#logTable').DataTable({
                dom: 'Bfrtip',
                buttons: ['csv', 'excel', 'print'],
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                language: {
                    lengthMenu: "Show _MENU_ rows per page"
                },
                responsive: true
            });
        });

        document.getElementById("addRoleForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const roleName = document.getElementById("roleName").value.trim();
            const roleDescription = document.getElementById("roleDescription").value.trim();
            const checkboxes = document.querySelectorAll("#addRoleForm input[type='checkbox']:checked");
            const permissions = Array.from(checkboxes).map(cb => cb.value);

            fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    c_id: c_id,
                    name: roleName,
                    description: roleDescription,
                    permissions: permissions
                })
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    throw new Error("Invalid JSON: " + text);
                }

                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Role added successfully',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    document.getElementById("addRoleForm").reset();
                    // Hide modal
                    var addRoleModal = bootstrap.Modal.getInstance(document.getElementById('addRoleModal'));
                    if (addRoleModal) addRoleModal.hide();
                    // Reload table (simple way: reload page, better: reload via AJAX)
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: data.message || 'Failed to add role',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'An error occurred',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        });

        // Edit Role Modal: populate and show
        $(document).on('click', '.edit-role-btn', function() {
            const roleId = $(this).data('role-id');
            fetch('?get_role=1&id=' + roleId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $('#editRoleId').val(data.role.id);
                    $('#editRoleName').val(data.role.name);
                    $('#editRoleDescription').val(data.role.description);
                    // Uncheck all first
                    $('#editRoleForm input[type="checkbox"]').prop('checked', false);
                    if (Array.isArray(data.role.permissions)) {
                        data.role.permissions.forEach(function(perm) {
                            $('#editRoleForm input[type="checkbox"][value="' + perm + '"]').prop('checked', true);
                        });
                    }
                    var editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
                    editModal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: data.message || 'Failed to fetch role',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        });

        // Handle Edit Role form submit
        document.getElementById("editRoleForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const id = document.getElementById("editRoleId").value;
            const roleName = document.getElementById("editRoleName").value.trim();
            const roleDescription = document.getElementById("editRoleDescription").value.trim();
            const checkboxes = document.querySelectorAll("#editRoleForm input[type='checkbox']:checked");
            const permissions = Array.from(checkboxes).map(cb => cb.value);

            fetch("", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    c_id: c_id,
                    id: id,
                    name: roleName,
                    description: roleDescription,
                    permissions: permissions
                })
            })
            .then(async res => {
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    throw new Error("Invalid JSON: " + text);
                }

                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Role updated successfully',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    document.getElementById("editRoleForm").reset();
                    var editRoleModal = bootstrap.Modal.getInstance(document.getElementById('editRoleModal'));
                    if (editRoleModal) editRoleModal.hide();
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: data.message || 'Failed to update role',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'An error occurred',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        });

        // Delete Role functionality
        $(document).on('click', '.delete-role-btn', function() {
            const roleId = $(this).data('role-id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the role. Users assigned to this role must be reassigned first.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("", {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: JSON.stringify({
                            c_id: c_id,
                            id: roleId
                        })
                    })
                    .then(async res => {
                        const text = await res.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (err) {
                            throw new Error("Invalid JSON: " + text);
                        }
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Role deleted successfully',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            setTimeout(() => {
                                location.reload();
                            }, 1200);
                        } else {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: data.message || 'Failed to delete role',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'An error occurred',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    });
                }
            });
        });

        // View Role Modal: populate and show
        $(document).on('click', '.view-role-btn', function() {
            const roleId = $(this).data('role-id');
            fetch('?get_role=1&id=' + roleId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $('#viewRoleName').val(data.role.name);
                    $('#viewRoleDescription').val(data.role.description);
                    $('#viewRolePermissions').empty();
                    if (Array.isArray(data.role.permissions)) {
                        data.role.permissions.forEach(function(perm) {
                            $('#viewRolePermissions').append(`
                                <li class="list-group-item">
                                    ${perm}
                                </li>
                            `);
                        });
                    }
                    var viewModal = new bootstrap.Modal(document.getElementById('viewRoleModal'));
                    viewModal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: data.message || 'Failed to fetch role',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        });
    </script>
</body>
</html>
