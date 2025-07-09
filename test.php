<?php
// AJAX: Move folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_folder') {
  $folder_id = intval($_POST['folder_id']);
  $new_parent_id = isset($_POST['new_parent_id']) ? intval($_POST['new_parent_id']) : null;
  $stmt = $conn->prepare("UPDATE folders SET parent_id=?, updated_at=NOW() WHERE id=? AND is_deleted=0");
  if ($new_parent_id) {
    $stmt->bind_param("ii", $new_parent_id, $folder_id);
  } else {
    $null = null;
    $stmt->bind_param("ii", $null, $folder_id);
  }
  if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Folder moved.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Move failed.']);
  }
  exit();
}

// AJAX: Move file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_file') {
  $file_id = intval($_POST['file_id']);
  $new_folder_id = intval($_POST['new_folder_id']);
  $stmt = $conn->prepare("UPDATE documents SET folder_id=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
  $stmt->bind_param("ii", $new_folder_id, $file_id);
  if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'File moved.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Move failed.']);
  }
  exit();
}