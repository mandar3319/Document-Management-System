<?php
session_start();

include '../config/conn.php';
include_once(dirname(__DIR__) . '../classes/Utils.php');

$userId = null;
$loginSuccess = false;
$errorMsg = "";

$companyName = "Company"; // fallback
$c_id = isset($_GET['c_id']) ? intval($_GET['c_id']) : 0;

if ($c_id) {
    $stmt = $conn->prepare("SELECT c_name FROM company WHERE c_id = ?");
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $stmt->bind_result($companyName);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare SQL to get user data, including is_active
    $stmt = $conn->prepare("SELECT id, password, is_active FROM users WHERE email = ? AND c_id = ?");
    if (!$stmt) {
        $errorMsg = "Network or server error. Please try again.";
        Utils::logActivity($conn, 0, 'login_failed', 'user', 0, 'DB error for email: ' . $email);
    } else {
        $stmt->bind_param("si", $email, $c_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $hashedPassword, $isActive);
            $stmt->fetch();

            if (!$isActive) {
                $errorMsg = "User is deactivated. Please contact admin.";
                Utils::logActivity($conn, $userId, 'login_failed', 'user', $userId, 'User deactivated');
            } else if (password_verify($password, $hashedPassword)) {
                // Successful login
                $_SESSION['user_id'] = $userId;
                $_SESSION['c_id'] = $c_id;
                $loginSuccess = true;

                // Update the last_login field in the database
                date_default_timezone_set('Asia/Kolkata');
                $currentTime = date("Y-m-d H:i:s");
                $updateStmt = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                $updateStmt->bind_param("si", $currentTime, $userId);
                $updateStmt->execute();
                $updateStmt->close();

                Utils::logActivity($conn, $userId, 'login_success', 'user', $userId, 'User logged in successfully');
            } else {
                $errorMsg = "Incorrect credentials.";
                Utils::logActivity($conn, $userId, 'login_failed', 'user', $userId, 'Incorrect credentials');
            }
        } else {
            $errorMsg = "User not found.";
            Utils::logActivity($conn, 0, 'login_failed', 'user', 0, 'User not found: ' . $email);
        }
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <title>Sign In</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');

    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
    }

    body {
        background-color: aliceblue;
        font-family: "Outfit", sans-serif;
    }

    .main_header {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        max-width: 500px;
        margin: 0 20px;
        background-color: #fff;
        padding: 40px;
        border-radius: 5px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #007bff;
        border-color: #007bff;
    }
    </style>
</head>

<body>
    <div class="main_header">
        <div class="signup_section">
            <div class="container">
                <h2 class="text-center mb-4">Sign In for <?= htmlspecialchars($companyName) ?></h2>
                <form id="signinForm" method="POST" class="text-start">
                    <div class="input-group input-group-outline my-3">
                        <!-- <label class="form-label">Email</label> -->
                        <input type="email" name="email" placeholder="Email" required class="form-control">
                    </div>
                    <div class="input-group input-group-outline mb-3">
                        <!-- <label class="form-label">Password</label> -->
                        <input type="password" name="password" placeholder="Password" required class="form-control">
                    </div>
                    <div class="form-check form-switch d-flex align-items-center mb-3">
                        <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                        <label class="form-check-label mb-0 ms-3" for="rememberMe">Remember me</label>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Sign in</button>
                    </div>
                    <p class="mt-4 text-sm text-center">
                        Don't have an account?
                        <a href="signup.php" class="text-info text-gradient font-weight-bold">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($loginSuccess): ?>
    <script>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Login successful!',
        showConfirmButton: false,
        timer: 1000
    });
    setTimeout(() => {
        window.location.href = 'dashboard.php';
    }, 1000);
    </script>
    <?php elseif (!empty($errorMsg)): ?>
    <script>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: '<?= $errorMsg ?>',
        showConfirmButton: false,
        timer: <?= ($errorMsg === "User is deactivated. Please contact admin.") ? 2000 : 1000 ?> 
    });
    </script>
    <?php endif; ?>

    <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? 'Show' : 'Hide';
    });

    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? 'Show' : 'Hide';
    });
    </script>
</body>

</html>