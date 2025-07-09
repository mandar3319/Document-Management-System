<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $profile_photo = $_FILES['profile_photo'];

    if (!empty($name)) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create uploads folder if it doesn't exist
        }

        if ($profile_photo['error'] === UPLOAD_ERR_OK) {
            $file_name = basename($profile_photo['name']);
            $file_path = $upload_dir . $file_name;

            // Move uploaded file to the uploads directory
            if (move_uploaded_file($profile_photo['tmp_name'], $file_path)) {
                $query = "UPDATE users SET name = ?, profile = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $name, $file_path, $user_id);
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: 'Failed to upload the profile photo. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    });
                </script>";
                exit();
            }
        } else {
            $query = "UPDATE users SET name = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $name, $user_id);
        }

        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Profile Updated',
                        text: 'Your profile has been successfully updated.',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: 'There was an error updating your profile. Please try again.',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        }
    }
}

// Fetch user details from the database
$query = "SELECT name, email, profile FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $email = $user['email'];
    $profile_path = $user['profile'];
    $initial = strtoupper(substr($name, 0, 1));
    $has_profile_photo = !empty($profile_path);
} else {
    $name = "Unknown";
    $email = "Unknown";
    $initial = "U";
    $has_profile_photo = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
    <?php include "components/head.php"; ?>
    <style>
    body {
        background-color: #f8f9fa;
    }

    .profile-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .profile-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .profile-header h1 {
        font-size: 2.5rem;
        margin: 0;
    }

    .profile-header p {
        color: #6c757d;
    }

    .profile-info {
        margin-bottom: 20px;
    }

    .profile-info label {
        font-weight: bold;
    }

    .btn-update {
        background-color: #007bff;
        color: white;
    }

    .form-control {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px;
        box-shadow: none;
    }

    .form-control:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .profile-photo-placeholder {
        width: 100px;
        height: 100px;
        background: linear-gradient(to right, rgb(2, 0, 3), rgb(41, 44, 49));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        border-radius: 50%;
    }
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
<?php include "components/sidebar.php"; ?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php include "components/navbar.php"; ?>

    <div class="container-fluid py-2">
        <div class="profile-container">

            <form method="POST" id="profileForm" enctype="multipart/form-data">
                <div class="profile-info mb-3 text-center">
                    <label for="profilePhoto">Profile Photo:</label><br>
                    <?php if ($has_profile_photo): ?>
                        <img src="<?= htmlspecialchars($profile_path) ?>" alt="Profile Photo" width="100" height="100" class="rounded mb-2">
                    <?php else: ?>
                        <div class="text-center">
                              <div class="profile-photo-placeholder"><?= $initial ?></div>
                        </div>
                      
                    <?php endif; ?>
                    <input type="file" name="profile_photo" id="profilePhoto" class="form-control mt-2">
                </div>

                <div class="profile-info mb-3">
                    <label for="name">Name:</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="profile-info mb-3">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <!-- <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button> -->
                    <a href="logout.php" class="btn btn-danger">Logout</a>  
                </div>
            </form>

            <!-- Change Password Modal -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" method="POST" action="change_password.php">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="oldPassword" class="form-label">Old Password</label>
                                <input type="password" class="form-control" id="oldPassword" name="old_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</main>

<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>