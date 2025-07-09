<?php
session_start();
include '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;

    $c_id = $_SESSION['c_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$name || !$c_id || !$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO folders (c_id, name, description, parent_id, created_by, is_protected, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())");
    $stmt->bind_param("sssii", $c_id, $name, $description, $parent_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'folder_id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

    $stmt->close();
    $conn->close();
}
?>
