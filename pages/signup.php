<?php

include "../classes/Email.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "fdms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$showSuccess = false;
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $companyName = trim($_POST['companydomain']);
    $companyDomain = strtolower($companyName);
    $ip_addr = $_SERVER['REMOTE_ADDR'];
    $logoPath = "";

    // File upload
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === 0) {
        $file = $_FILES['company_logo'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'svg'];
        if (in_array(strtolower($ext), $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $fileName = uniqid() . '.' . $ext;
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $errorMsg = "Failed to upload logo.";
            } else {
                $logoPath = $filePath;
            }
        } else {
            $errorMsg = "Invalid file format or size too big.";
        }
    }

    if (empty($errorMsg)) {
        $conn->begin_transaction();

        try {
            // Check if company name or domain exists
            $stmt = $conn->prepare("SELECT * FROM company WHERE c_name = ? OR c_domain = ?");
            $stmt->bind_param("ss", $companyName, $companyDomain);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) throw new Exception("Company name or domain already exists.");
            $stmt->close();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) throw new Exception("Email already registered.");
            $stmt->close();

            // Insert into company
            $c_storage = 0;
            $stmt = $conn->prepare("INSERT INTO company (c_name, c_domain, c_logo, logo_path, c_storage, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $companyName, $companyDomain, $logoPath, $logoPath, $c_storage);
            $stmt->execute();
            $c_id = $stmt->insert_id;
            $stmt->close();

            // Insert into users
            $role_id = 1;
            $is_active = 1;
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, c_id, role_id, is_active, last_login_ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssiiis", $name, $email, $password, $c_id, $role_id, $is_active, $ip_addr);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            // ✅ Send email (use object method)
            $mailer = new Mailer();
            $mailer->sendCompanyCreatedEmail($email, $companyName);

            // Insert into logs
            $action = "create";
            $entity_type = "user";
            $entity_id = $user_id;
            $description = "$name created the company and registered as admin.";
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iississ", $user_id, $c_id, $action, $entity_type, $entity_id, $description, $ip_addr);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $showSuccess = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = $e->getMessage();
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
      <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
    <!-- SweetAlert2 CSS and JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <title>Sign Up</title>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap");

    * {
      padding: 0;
      margin: 0;
      box-sizing: border-box;
    }

    body {
      background-color: #fff;
      font-family: "Outfit", sans-serif;
    }

    .main_header {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .img_section img {
      height: 400px;
      width: auto;
    }

    .container {
      max-width: 500px;
      margin: 0 20px;
      background-color: #fff;
      padding: 40px;
      border-radius: 5px;
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }

    .input-group input-group-outline my-3 {
      margin-bottom: 20px;
    }



    .btn-primary:hover {
      background-color: #262626;
      border-color:  #262626;
    }

    .box,
    .domain {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .domain-text {
      margin-left: 5px;
    }

    .upload-box {
      border: 2px dashed #262626;
      border-radius: 5px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      background-color: #f8f9fa;
    }

    .upload-box.dragover {
      background-color: #e0f0ff;
      border-color: #262626;
    }

    .upload-link {
      color: #262626;
      text-decoration: underline;
      cursor: pointer;
    }
    .swal2-confirm swal2-styled{
      background-color:green;
      border-color: green;
    }
  </style>
</head>

<body>

<?php if ($showSuccess): ?>
<script>
Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'Company and user registered successfully!',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
}).then(() => {
    window.location.href = 'domain.php';
});
</script>
<?php elseif (!empty($errorMsg)): ?>
<script>
Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'error',
    title: <?= json_encode($errorMsg) ?>,
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true
});
</script>
<?php endif; ?>



  <div class="main_header">
    <div class="img_section">
      <img src="images/image 1.png" alt="" />
    </div>
    <div class="signup_section">
      <div class="container">
        <h2 class="text-center mb-4">Sign Up for DMS</h2>
          <form id="signupForm" method="post" enctype="multipart/form-data">
            <div class="box">
              <div class="input-group input-group-outline">
                <label for="name">Admin Name</label>
                <input type="text" class="form-control w-100 m-auto" id="name" name="name" required />
              </div>
              <div class="input-group input-group-outline">
                <label for="email">Admin Email</label>
                <input type="email" class="form-control w-100 m-1" id="email" name="email" required />
              </div>
            </div>

            <div class="input-group input-group-outline my-3">
              <label for="password">Admin Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required />
                </span>
              </div>
            </div>

            <div class="input-group input-group-outline my-3">
              <label for="company">Company Name</label>
              <div class="input-group">
              <input type="text" class="form-control" id="companydomain" name="companydomain" required />

              </div>
            </div>

            <div class="input-group input-group-outline my-3">
              <label for="company">Company Domain</label>
              <div class="input-group">
                <input type="text" class="form-control" id="company" name="company" required />

                </div>
            </div>


            <!-- Logo Upload -->
            <div class="mb-3">
              <label class="form-label">Company Logo</label>
              <div class="upload-box" id="dropArea">
                <p id="dropText">Drop your logo here or <span class="upload-link">browse</span></p>
                <small>Supported formats: PNG, JPG, SVG (max 5MB)</small>
                <input type="file" id="fileInput" name="company_logo" class="form-control mt-2" accept=".png,.jpg,.jpeg,.svg" hidden />
                <div id="fileNameDisplay" class="mt-2 text-info fw-semibold"></div>
              </div>
            </div>

            <button type="submit" name="submit" class="btn btn-success btn-block rounded-0 w-100">Sign Up</button>
            <center>
              <a class="mt-1 d-block" href="domain.php">Already have an account? <span class="text-info text-gradient font-weight-bold">Sign In.</span> </a>
            </center>
          </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
   
    const dropArea = document.getElementById("dropArea");
    const fileInput = document.getElementById("fileInput");
    const fileNameDisplay = document.getElementById("fileNameDisplay");

    dropArea.addEventListener("click", () => fileInput.click());

    dropArea.addEventListener("dragover", (e) => {
      e.preventDefault();
      dropArea.classList.add("dragover");
    });

    dropArea.addEventListener("dragleave", () => {
      dropArea.classList.remove("dragover");
    });

    dropArea.addEventListener("drop", (e) => {
      e.preventDefault();
      dropArea.classList.remove("dragover");
      const file = e.dataTransfer.files[0];
      if (file) {
        fileInput.files = e.dataTransfer.files;
        displayFileName(file);
      }
    });

    fileInput.addEventListener("change", () => {
      const file = fileInput.files[0];
      if (file) {
        displayFileName(file);
      }
    });

    function displayFileName(file) {
      fileNameDisplay.textContent = `📁 ${file.name}`;
    }
  </script>

</body>
</html>
