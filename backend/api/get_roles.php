<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$c_id = $_GET['c_id'] ?? null;

if (!$c_id) {
    echo json_encode(['success' => false, 'message' => 'Missing company ID']);
    exit;
}

include '../../config/conn.php';

// Fetch all roles for the given company (c_id)
$stmt = $conn->prepare("SELECT id, name, description, permissions, is_system FROM roles WHERE c_id = ?");
$stmt->bind_param("i", $c_id);
$stmt->execute();
$result = $stmt->get_result();

$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

$stmt->close();
$conn->close();

if (empty($roles)) {
    echo json_encode(['success' => false, 'message' => 'No roles found for this company']);
} else {
    echo json_encode(['success' => true, 'roles' => $roles]);
}
?>
