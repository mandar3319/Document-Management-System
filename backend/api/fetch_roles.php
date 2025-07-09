<?php
include '../config/conn.php';

$query = "SELECT id, name FROM roles"; // Fetch id and name of roles
$result = $conn->query($query);

$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}

echo json_encode($roles);
?>
