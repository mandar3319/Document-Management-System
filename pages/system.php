<?php 
session_start();
include '../config/conn.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

$c_id = $_SESSION['c_id'];
$user_id = $_SESSION['user_id'];

// Step 1: Define required setting keys
$settings_keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'upload_size_limit'];

// ✅   Step 2: Insert missing keys with default empty values
foreach ($settings_keys as $key) {
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE c_id = $c_id AND setting_key = '$key'");
    if ($check && mysqli_num_rows($check) === 0) {
        $insert = mysqli_query($conn, "
            INSERT INTO settings (c_id, setting_key, setting_value, updated_by, created_at, updated_at)
            VALUES ($c_id, '$key', '', $user_id, NOW(), NOW())
        ");
        if (!$insert) {
            error_log("Insert failed for key '$key': " . mysqli_error($conn));
        }
    }
}

// ✅ Step 3: Handle AJAX Save Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['status' => 'error', 'message' => 'Unknown error'];
    $conn->autocommit(false);

    try {
        foreach ($settings_keys as $key) {
            if (!isset($_POST[$key])) {
                throw new Exception("Missing parameter: $key");
            }

            $value = mysqli_real_escape_string($conn, $_POST[$key]);

            $update = mysqli_query($conn, "
                UPDATE settings 
                SET setting_value = '$value', updated_by = $user_id, updated_at = NOW() 
                WHERE c_id = $c_id AND setting_key = '$key'
            ");

            if (!$update) {
                throw new Exception("Update failed for $key: " . mysqli_error($conn));
            }
        }

        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Settings saved successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[".date('Y-m-d H:i:s')."] Settings Error: " . $e->getMessage() . "\n", 3, "settings_errors.log");
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        $conn->autocommit(true);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Step 4: Fetch current    values to show in form
$settings_result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE c_id = $c_id");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "components/head.php"; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
     <!-- Add SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        #systemSettingsForm .form-control,
        #systemSettingsForm .form-select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            box-shadow: none;
        }

        #systemSettingsForm .form-control:focus,
        #systemSettingsForm .form-select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-100">
    <?php include "components/sidebar.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "components/navbar.php"; ?>
        <div class="container-fluid py-2">
            <div class="card">
                <div class="card-header">
                    <h5>System Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="systemSettingsForm">
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '') ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="smtp_user" class="form-label">SMTP User</label>
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="smtp_pass" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="upload_size_limit" class="form-label">Upload Size Limit (MB)</label>
                            <input type="number" class="form-control" id="upload_size_limit" name="upload_size_limit" value="<?= htmlspecialchars($settings['upload_size_limit'] ?? '') ?>">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php include "components/footer.php"; ?>
        </div>
    </main>

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- AJAX Save Script -->
    <script>
        document.getElementById("systemSettingsForm").addEventListener("submit", function (e) {
            e.preventDefault();
            const form = new FormData(this);
            form.append("ajax", 1);

            Swal.fire({
                title: 'Saving Settings',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("", {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to save settings'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Something went wrong. Please try again.'
                });
                console.error("Fetch error:", error);
            });
        });
    </script>
</body>
</html>
