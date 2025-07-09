<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Connect to database
$conn = new mysqli("localhost", "root", "", "fdms");
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Example user fetching (replace with your actual query)
$sql = "SELECT id, name, email FROM users WHERE c_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['c_id']);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["data" => $data]);
