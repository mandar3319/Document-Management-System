<?php

include '../../config/conn.php'; 

$sql = "SELECT al.*, u.name, u.email, u.image 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => date("M d, Y H:i:s", strtotime($row['created_at'])),
        'name' => $row['name'],
        'email' => $row['email'],
        'image' => $row['image'] ?? 'default.jpg',
        'action' => $row['action'],
        'description' => $row['description'],
        'ip_addr' => $row['ip_addr'],
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
