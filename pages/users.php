<?php
session_start();
include '../config/conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

$c_id = $_SESSION['c_id'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

// var_dump($currentUserId); // Debug: show what ID is
if ($currentUserId !== null && is_numeric($currentUserId)) {
    $currentUserId = (int) $currentUserId;
    $getUserName = "SELECT name FROM users WHERE id = $currentUserId";

    $resultUserName = mysqli_query($conn, $getUserName);

    if (!$resultUserName) {
    } elseif (mysqli_num_rows($resultUserName) > 0) {
        $row = mysqli_fetch_assoc($resultUserName);
        $currentUserName = $row['name'];
    }
}




$query = "SELECT DISTINCT users.id, users.name AS user_name, users.email, users.profile, users.is_active, users.last_login, users.role_id, roles.name AS role_name 
          FROM users 
          LEFT JOIN roles ON users.role_id = roles.id 
          WHERE users.c_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $c_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];

$swalScript = "";

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
ob_start(); // Start buffering early

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {

    header('Content-Type: application/json');
    file_put_contents(__DIR__ . '/users_debug.log', "----\nPOST: " . print_r($_POST, true) . "\nSESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

    $response = [];
    $c_id = $_SESSION['c_id'] ?? 0;

    if (
        isset($_POST['name']) &&
        isset($_POST['email']) &&
        isset($_POST['Password']) &&
        isset($_POST['role_id'])
    ) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['Password'];
        $role_id = intval($_POST['role_id']);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $created_at = date('Y-m-d H:i:s');

        // Log values before DB actions
        file_put_contents(__DIR__ . '/users_debug.log', "Prepared values: name=$name, email=$email, role_id=$role_id, created_at=$created_at\n", FILE_APPEND);

        $checkQuery = $conn->prepare("SELECT id FROM users WHERE email = ? AND c_id = ?");
        if (!$checkQuery) {
            file_put_contents(__DIR__ . '/users_debug.log', "Prepare failed (checkQuery): " . $conn->error . "\n", FILE_APPEND);
        }
        $checkQuery->bind_param("si", $email, $c_id);
        $checkQuery->execute();
        $checkQuery->store_result();

        if ($checkQuery->num_rows > 0) {
            $response = [
                'icon' => 'error',
                'title' => 'User Already Exists',
                'text' => 'A user with this email already exists.'
            ];
            file_put_contents(__DIR__ . '/users_debug.log', "User already exists: $email\n", FILE_APPEND);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, c_id, role_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)");
            if (!$stmt) {
                file_put_contents(__DIR__ . '/users_debug.log', "Prepare failed (insert): " . $conn->error . "\n", FILE_APPEND);
            }
            $stmt->bind_param("sssiss", $name, $email, $hashedPassword, $c_id, $role_id, $created_at);

            if ($stmt->execute()) {
                file_put_contents(__DIR__ . '/users_debug.log', "Insert success: user $email\n", FILE_APPEND);
                // Activity log for user creation
                $log_user_id = $_SESSION['user_id'];
                $log_user_name = $_SESSION['name'] ?? '';
                $log_ip = $_SERVER['REMOTE_ADDR'];
                $new_user_id = $stmt->insert_id;
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $log_action = 'create';
                $log_entity_type = 'user';
                $log_description = "Created user: $name ($email) by $log_user_name";
                $log_stmt->bind_param("iississ", $log_user_id, $c_id, $log_action, $log_entity_type, $new_user_id, $log_description, $log_ip);
                $log_stmt->execute();
                $log_stmt->close();

                $response = [
                    'icon' => 'success',
                    'title' => 'User Added',
                    'text' => 'User has been added successfully.'
                ];
            } else {
                file_put_contents(__DIR__ . '/users_debug.log', "Insert failed: " . $stmt->error . "\n", FILE_APPEND);
                $response = [
                    'icon' => 'error',
                    'title' => 'Database Error',
                    'text' => $stmt->error
                ];
            }
            $stmt->close();
        }

        $checkQuery->close();
        $conn->close();
    } else {
        file_put_contents(__DIR__ . '/users_debug.log', "Invalid data: " . print_r($_POST, true) . "\n", FILE_APPEND);
        $response = [
            'icon' => 'error',
            'title' => 'Invalid Data',
            'text' => 'Please fill all required fields.'
        ];
    }

    ob_clean(); // Clear any unexpected output
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    $response = [];
    $user_id = intval($_POST['user_id'] ?? 0);
    $c_id = $_SESSION['c_id'] ?? 0;

    if ($user_id > 0 && $c_id > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND c_id = ?");
        $stmt->bind_param("ii", $user_id, $c_id);
        if ($stmt->execute()) {
            // Activity log for user deletion
            $log_user_id = $_SESSION['user_id'];
            $log_user_name = $_SESSION['name'];
            $log_ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $log_action = 'delete';
            $log_entity_type = 'user';
            $log_description = "Deleted user ID: $user_id by $currentUserName";
            $log_stmt->bind_param("iississ", $log_user_id, $c_id, $log_action, $log_entity_type, $user_id, $log_description, $log_ip);
            $log_stmt->execute();
            $log_stmt->close();

            $response = [
                'icon' => 'success',
                'title' => 'User Deleted',
                'text' => 'User has been deleted successfully.'
            ];
        } else {
            $response = [
                'icon' => 'error',
                'title' => 'Delete Failed',
                'text' => $stmt->error
            ];
        }
        $stmt->close();
        $conn->close();
    } else {
        $response = [
            'icon' => 'error',
            'title' => 'Invalid Request',
            'text' => 'Invalid user or company ID.'
        ];
    }
    ob_clean();
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_edit'])) {
    header('Content-Type: application/json');
    $response = [];
    $user_id = intval($_POST['user_id'] ?? 0);
    $c_id = $_SESSION['c_id'] ?? 0;

    // Debug: Log incoming POST data and session
    file_put_contents(__DIR__ . '/users_debug.log', "----\nPOST: " . print_r($_POST, true) . "\nSESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

    if (
        $user_id > 0 &&
        isset($_POST['name']) &&
        isset($_POST['email']) &&
        isset($_POST['role_id'])
    ) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role_id = intval($_POST['role_id']);
        $password = $_POST['Password'] ?? '';
        $updateFields = "name = ?, email = ?, role_id = ?";
        $params = [$name, $email, $role_id];
        $types = "ssi";

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateFields .= ", password = ?";
            $params[] = $hashedPassword;
            $types .= "s";
        }

        $params[] = $user_id;
        $params[] = $c_id;
        $types .= "ii";

        $sql = "UPDATE users SET $updateFields WHERE id = ? AND c_id = ?";
        // Debug: Log SQL and params
        file_put_contents(__DIR__ . '/users_debug.log', "SQL: $sql\nPARAMS: " . print_r($params, true) . "\nTYPES: $types\n", FILE_APPEND);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Debug: Log prepare error
            file_put_contents(__DIR__ . '/users_debug.log', "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
            $response = [
                'icon' => 'error',
                'title' => 'Prepare Failed',
                'text' => $conn->error
            ];
            ob_clean();
            echo json_encode($response);
            exit;
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Activity log for user update
            $log_user_id = $_SESSION['user_id'];
            $log_user_name = $_SESSION['name'];
            $log_ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $log_action = 'update';
            $log_entity_type = 'user';
            $log_description = "Updated user: $name ($email) by $currentUserName";
            $log_stmt->bind_param("iississ", $log_user_id, $c_id, $log_action, $log_entity_type, $user_id, $log_description, $log_ip);
            $log_stmt->execute();
            $log_stmt->close();

            $response = [
                'icon' => 'success',
                'title' => 'User Updated',
                'text' => 'User has been updated successfully.'
            ];
        } else {
            // Debug: Log execute error
            file_put_contents(__DIR__ . '/users_debug.log', "Execute failed: " . $stmt->error . "\n", FILE_APPEND);
            $response = [
                'icon' => 'error',
                'title' => 'Update Failed',
                'text' => $stmt->error
            ];
        }
        $stmt->close();
        $conn->close();
    } else {
        // Debug: Log invalid data
        file_put_contents(__DIR__ . '/users_debug.log', "Invalid data: user_id=$user_id, name=" . ($_POST['name'] ?? '') . ", email=" . ($_POST['email'] ?? '') . ", role_id=" . ($_POST['role_id'] ?? '') . "\n", FILE_APPEND);
        $response = [
            'icon' => 'error',
            'title' => 'Invalid Data',
            'text' => 'Please fill all required fields.'
        ];
    }
    ob_clean();
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_deactivate'])) {
    header('Content-Type: application/json');
    $response = [];
    $user_id = intval($_POST['user_id'] ?? 0);
    $c_id = $_SESSION['c_id'] ?? 0;

    if ($user_id > 0 && $c_id > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND c_id = ?");
        $stmt->bind_param("ii", $user_id, $c_id);
        if ($stmt->execute()) {
            // Activity log for user deactivation
            $log_user_id = $_SESSION['user_id'];
            $log_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
            $log_ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $log_action = 'deactivate';
            $log_entity_type = 'user';
            $log_description = "Deactivated user ID: $user_id by $log_user_name";
            $log_stmt->bind_param("iississ", $log_user_id, $c_id, $log_action, $log_entity_type, $user_id, $log_description, $log_ip);
            $log_stmt->execute();
            $log_stmt->close();

            $response = [
                'icon' => 'success',
                'title' => 'User Deactivated',
                'text' => 'User has been deactivated successfully.'
            ];
        } else {
            $response = [
                'icon' => 'error',
                'title' => 'Deactivation Failed',
                'text' => $stmt->error
            ];
        }
        $stmt->close();
        $conn->close();
    } else {
        $response = [
            'icon' => 'error',
            'title' => 'Invalid Request',
            'text' => 'Invalid user or company ID.'
        ];
    }
    ob_clean();
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_activate'])) {
    header('Content-Type: application/json');
    $response = [];
    $user_id = intval($_POST['user_id'] ?? 0);
    $c_id = $_SESSION['c_id'] ?? 0;

    if ($user_id > 0 && $c_id > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND c_id = ?");
        $stmt->bind_param("ii", $user_id, $c_id);
        if ($stmt->execute()) {
            // Activity log for user activation
            $log_user_id = $_SESSION['user_id'];
            // Fetch user name from DB using user_id
            $log_user_name = '';
            $user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $log_user_id);
            $user_stmt->execute();
            $user_stmt->bind_result($fetched_name);
            if ($user_stmt->fetch()) {
                $log_user_name = $fetched_name;
            }
            $user_stmt->close();
            $log_ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $log_action = 'activate';
            $log_entity_type = 'user';
            $log_description = "Activated user ID: $user_id by $currentUserName";
            $log_stmt->bind_param("iississ", $log_user_id, $c_id, $log_action, $log_entity_type, $user_id, $log_description, $log_ip);
            $log_stmt->execute();
            $log_stmt->close();

            $response = [
                'icon' => 'success',
                'title' => 'User Activated',
                'text' => 'User has been activated successfully.'
            ];
        } else {
            $response = [
                'icon' => 'error',
                'title' => 'Activation Failed',
                'text' => $stmt->error
            ];
        }
        $stmt->close();
        $conn->close();
    } else {
        $response = [
            'icon' => 'error',
            'title' => 'Invalid Request',
            'text' => 'Invalid user or company ID.'
        ];
    }
    ob_clean();
    echo json_encode($response);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include "components/head.php";
    ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body class="g-sidenav-show  bg-gray-100">

    <?php
    ob_start();
    include "components/sidebar.php";
    ob_end_flush();


    ?>


    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

        <?php
        include "components/navbar.php";
        ?>
        <div class="container-fluid py-2">

            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6 d-flex flex-column justify-content-center">
                    <h4>Users</h4>
                    <p class="text-muted mb-0">  Manage application users</p>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
                    <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="userForm" method="post">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="input-group input-group-outline mb-3">
                                    <input type="text" class="form-control" name="name" placeholder="Full Name" required />
                                </div>
                                <div class="input-group input-group-outline mb-3">
                                    <input type="email" class="form-control" name="email" placeholder="Email" required />
                                </div>
                                <div class="input-group input-group-outline mb-3">

                                    <select class="form-control" id="roleSelect" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <?php
                                        $query = "SELECT * FROM roles WHERE c_id = $c_id";
                                        $result = mysqli_query($conn, $query);

                                        if ($result) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
                                            }
                                        } else {
                                            echo "<option disabled>Error loading roles</option>";
                                        }
                                        ?>
                                    </select>

                                </div>
                                <div class="input-group input-group-outline mb-3">
                                    <input type="password" class="form-control" name="Password" placeholder="Password" required />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning">Add User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editUserForm" method="post">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="user_id" id="editUserId" />
                                <div class="input-group input-group-outline mb-3">
                                    <input type="text" class="form-control" name="name" id="editUserName" placeholder="Full Name" required />
                                </div>
                                <div class="input-group input-group-outline mb-3">
                                    <input type="email" class="form-control" name="email" readonly id="editUserEmail" placeholder="Email" required />
                                </div>
                                <div class="input-group input-group-outline mb-3">
                                    <select class="form-control" id="editRoleSelect" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <?php
                                        $query = "SELECT * FROM roles WHERE c_id = $c_id";
                                        $result = mysqli_query($conn, $query);
                                        if ($result) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
                                            }
                                        } else {
                                            echo "<option disabled>Error loading roles</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="input-group input-group-outline mb-3">
                                    <input type="password" class="form-control" name="Password" placeholder="New Password (leave blank to keep current)" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card my-4 p-1">

                        <div class="card-body px-0 pb-2">
                            <div class="table-responsive p-0">
                                <table id="logTable" class="table align-items-center mb-0 display nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Role</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Last Login</th>
                                            <th class="text-secondary opacity-7">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): 
    // Skip current user
    if ($user['id'] == $currentUserId) continue;
    // Only allow actions if current user has a lower role_id (higher number means lower privilege)
    $myRoleId = $users[array_search($currentUserId, array_column($users, 'id'))]['role_id'];
    $canAct = ($user['role_id'] >= $myRoleId); // strictly greater only
?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex px-2 py-1">
                                                        <div>
                                                            <?php if ($user['profile']): ?>
                                                                <img src="<?= htmlspecialchars($user['profile']) ?>" class="avatar avatar-sm me-3 border-radius-lg" alt="user">
                                                            <?php else: ?>
                                                                <?php
                                                                $initials = isset($user['user_name']) ? strtoupper(substr($user['user_name'], 0, 1)) : 'U';
                                                                $gradientStyle = "background: linear-gradient(45deg, #000, #fff); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; width: 40px; height: 40px; border-radius: 50%;";
                                                                ?>
                                                                <div class="avatar avatar-sm me-3 border-radius-lg" style="<?= $gradientStyle ?>">
                                                                    <?= htmlspecialchars($initials) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($user['user_name']) ?></h6>
                                                            <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($user['role_name']) ?></p>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge badge-sm bg-gradient-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-sm bg-gradient-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="text-secondary text-xs font-weight-bold">
                                                        <?= date("d/m/Y", strtotime($user['last_login'])) ?>
                                                    </span>
                                                </td>
                                                <td class="align-middle">
                                                    <?php if ($canAct): ?>
                                                        <a href="#" data-user-id="<?= $user['id'] ?>" 
                                                           data-user-name="<?= htmlspecialchars($user['user_name']) ?>"
                                                           data-user-email="<?= htmlspecialchars($user['email']) ?>"
                                                           data-user-role="<?= htmlspecialchars($user['role_name']) ?>"
                                                           data-role-id="<?= htmlspecialchars($user['role_id'] ?? '') ?>"
                                                           class="text-secondary font-weight-bold text-xs mx-1 edit-user-btn" 
                                                           title="Edit User">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($user['is_active']): ?>
                                                            <a href="#" data-user-id="<?= $user['id'] ?>" class="text-secondary font-weight-bold text-xs mx-1 deactivate-user-btn" title="Deactivate User">
                                                                <i class="fas fa-user-slash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="#" data-user-id="<?= $user['id'] ?>" class="text-success font-weight-bold text-xs mx-1 activate-user-btn" title="Activate User">
                                                                <i class="fas fa-user-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="#" class="text-secondary font-weight-bold text-xs mx-1 delete-user-btn" data-user-id="<?= $user['id'] ?>" title="Delete User">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted text-xs">No Action</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <?php
            include "components/footer.php";
            ?>
        </div>
    </main>
    <?php
    ?>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>

    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                                        

    <!-- jQuery & DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function() {
            $('#logTable').DataTable({
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

            // AJAX submission for Add User
            $('#userForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize() + '&ajax=1';

    $.ajax({
        url: 'users.php', // or the correct path to your file
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                icon: response.icon,
                title: response.title,
                text: response.text
            }).then(function() {
                if (response.icon === 'success') {
                    var modalEl = document.getElementById('addUserModal');
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    $('#userForm')[0].reset();
                    $('#addUserModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
                }
            });
        },
        error: function(xhr) {
            let msg = 'Something went wrong!';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json && json.title && json.text) {
                    msg = json.text;
                }
            } catch (e) {
                // fallback to default message
            }
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: msg
            });
        }
    });
});

        // AJAX delete user
        $(document).on('click', '.delete-user-btn', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            Swal.fire({
                icon: 'warning',
                title: 'Are you sure?',
                text: 'This will permanently delete the user.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Second confirmation
                    Swal.fire({
                        icon: 'error',
                        title: 'Are you absolutely sure?',
                        text: 'This action cannot be undone.',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete permanently',
                        cancelButtonText: 'Cancel'
                    }).then(function(secondResult) {
                        if (secondResult.isConfirmed) {
                            $.ajax({
                                url: 'users.php',
                                type: 'POST',
                                data: { ajax_delete: 1, user_id: userId },
                                dataType: 'json',
                                success: function(response) {
                                    Swal.fire({
                                        icon: response.icon,
                                        title: response.title,
                                        text: response.text
                                    }).then(function() {
                                        if (response.icon === 'success') {
                                            location.reload();
                                        }
                                    });
                                },
                                error: function(xhr) {
                                    let msg = 'Something went wrong!';
                                    try {
                                        var json = JSON.parse(xhr.responseText);
                                        if (json && json.title && json.text) {
                                            msg = json.text;
                                        }
                                    } catch (e) {}
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Request Failed',
                                        text: msg
                                    });
                                }
                            });
                        }
                    });
                }
            });
        });

    // Edit User button click
    $(document).on('click', '.edit-user-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var userEmail = $(this).data('user-email');
        var roleId = $(this).data('role-id');

        $('#editUserId').val(userId);
        $('#editUserName').val(userName);
        $('#editUserEmail').val(userEmail);
        $('#editRoleSelect').val(roleId);

        $('#editUserModal').modal('show');
    });

    // AJAX submission for Edit User
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&ajax_edit=1';

        $.ajax({
            url: 'users.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                Swal.fire({
                    icon: response.icon,
                    title: response.title,
                    text: response.text
                }).then(function() {
                    if (response.icon === 'success') {
                        var modalEl = document.getElementById('editUserModal');
                        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        $('#editUserForm')[0].reset();
                        $('#editUserModal').on('hidden.bs.modal', function () {
                            location.reload();
                        });
                    }
                });
            },
            error: function(xhr) {
                let msg = 'Something went wrong!';
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json && json.title && json.text) {
                        msg = json.text;
                    }
                } catch (e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: msg
                });
            }
        });
    });

    // Deactivate User
    $(document).on('click', '.deactivate-user-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            text: 'This will deactivate the user.',
            showCancelButton: true,
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'users.php',
                    type: 'POST',
                    data: { ajax_deactivate: 1, user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            icon: response.icon,
                            title: response.title,
                            text: response.text
                        }).then(function() {
                            if (response.icon === 'success') {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let msg = 'Something went wrong!';
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (json && json.title && json.text) {
                                msg = json.text;
                            }
                        } catch (e) {}
                        Swal.fire({
                            icon: 'error',
                            title: 'Request Failed',
                            text: msg
                        });
                    }
                });
            }
        });
    });

    // Activate User
    $(document).on('click', '.activate-user-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        Swal.fire({
            icon: 'question',
            title: 'Are you sure?',
            text: 'This will activate the user.',
            showCancelButton: true,
            confirmButtonText: 'Yes, activate',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'users.php',
                    type: 'POST',
                    data: { ajax_activate: 1, user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            icon: response.icon,
                            title: response.title,
                            text: response.text
                        }).then(function() {
                            if (response.icon === 'success') {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        let msg = 'Something went wrong!';
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (json && json.title && json.text) {
                                msg = json.text;
                            }
                        } catch (e) {}
                        Swal.fire({
                            icon: 'error',
                            title: 'Request Failed',
                            text: msg
                        });
                    }
                });
            }
        });
    });

    });
    </script>
    <?php
        if (isset($_SESSION['swal'])) {
        $swal = $_SESSION['swal'];
        echo "<script>
            Swal.fire({
                icon: '" . addslashes($swal['icon']) . "',
                title: '" . addslashes($swal['title']) . "',
                text: '" . addslashes($swal['text']) . "'
            });
        </script>";
        unset($_SESSION['swal']);
    }?>
</body>
</html>
