<?php
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    $query = "INSERT INTO users (name, email, password, role_id, c_id, is_active) 
              VALUES (?, ?, ?, ?, ?, 1)"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssis", $name, $email, $password, $role_id, $c_id);
    
    if ($stmt->execute()) {
        // Return success response
        echo json_encode(['status' => 'success']);
    } else {
        // Return error response
        echo json_encode(['status' => 'error', 'message' => 'Failed to add user']);
    }
}
?>
