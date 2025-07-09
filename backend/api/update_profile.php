<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

// ✅ Adjust path based on your structure
require_once __DIR__ . '/../../db.php';

// ✅ Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// ✅ Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $updated_at = date('Y-m-d H:i:s');

        // ✅ Check if profile photo uploaded
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $file_tmp = $_FILES['profile_photo']['tmp_name'];

            // Read as BLOB
            $profile_blob = file_get_contents($file_tmp);

            // Optional: Save to uploads/profile/ directory
            $upload_dir = __DIR__ . '/../../uploads/profile/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $unique_name = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
            move_uploaded_file($file_tmp, $upload_dir . $unique_name);

            // ✅ Update name + profile
            $stmt = $pdo->prepare("UPDATE users SET name = ?, profile = ?, updated_at = ? WHERE id = ?");
            $stmt->bindParam(1, $name);
            $stmt->bindParam(2, $profile_blob, PDO::PARAM_LOB);
            $stmt->bindParam(3, $updated_at);
            $stmt->bindParam(4, $user_id);
            $stmt->execute();

        } else {
            // ✅ Update name only
            $stmt = $pdo->prepare("UPDATE users SET name = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$name, $updated_at, $user_id]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Server Error: ' . $e->getMessage(),
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
