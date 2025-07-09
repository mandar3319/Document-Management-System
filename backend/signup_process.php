<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/conn.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $c_name = $_POST['company_name'];
    $c_logo = $_FILES['company_logo'];
    $c_domain = $_POST['company_domain'];
    $c_storage = $_POST['company_storage'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role_id = 1; 
    $is_active = 1;
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');

    // Upload logo
    $logo_path = '';
    if ($c_logo['error'] == 0) {
        $ext = pathinfo($c_logo['name'], PATHINFO_EXTENSION);
        $logo_path = 'uploads/logos/' . uniqid() . '.' . $ext;
        move_uploaded_file($c_logo['tmp_name'], $logo_path);
    }

    // Insert into company table
    $stmt = $conn->prepare("INSERT INTO company (c_name, c_logo, c_domain, c_storage, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $c_name, $logo_path, $c_domain, $c_storage, $now);
    $stmt->execute();
    $c_id = $stmt->insert_id;
    $stmt->close();

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, c_id, role_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiis", $name, $email, $password, $c_id, $role_id, $is_active, $now);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();

    // Insert into activity_logs table
    $action = "Sign up";
    $entity_type = "User";
    $entity_id = $user_id;
    $description = "$name registered company $c_name";
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, cerated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssss", $user_id, $c_id, $action, $entity_type, $entity_id, $description, $ip, $now);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success']);
}
?>
