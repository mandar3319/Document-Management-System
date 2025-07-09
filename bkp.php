<?php
session_start();
include '../config/conn.php';
include_once(dirname(__DIR__) . "/classes/Utils.php");

ini_set('display_errors', 0); // Hide errors from browser
ini_set('log_errors', 1);     // Enable error logging
error_reporting(E_ALL);       // Report all errors
ini_set('error_log', __DIR__ . '/../php-error.log'); // Log file path



if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
  header("Location: domain.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$c_id = $_SESSION['c_id'];
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

// Create folder (AJAX, same as folders.php, but parent_id from URL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
  $name = trim($_POST['name']);
  $description = trim($_POST['description']);
  $created_by = $user_id;
  $is_protected = 0;

  if (!empty($name)) {
    $query = "INSERT INTO folders (c_id, name, description, parent_id, created_by, is_protected, created_at, updated_at, deleted_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NULL)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issiii", $c_id, $name, $description, $parent_id, $created_by, $is_protected);

    if ($stmt->execute()) {
      echo json_encode([
        'status' => 'success',
        'folder_id' => $stmt->insert_id
      ]);
    } else {
      echo json_encode([
        'status' => 'error',
        'message' => 'Insert failed: ' . $stmt->error
      ]);
    }
    exit();
  } else {
    echo json_encode([
      'status' => 'error',
      'message' => 'Folder name is required'
    ]);
    exit();
  }
}

// Helper function to get all descendant folder IDs recursively
function getAllDescendantFolderIds($conn, $folder_id)
{
  $ids = [];
  $stmt = $conn->prepare("SELECT id FROM folders WHERE parent_id = ? AND is_deleted = 0");
  $stmt->bind_param("i", $folder_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $ids[] = $row['id'];
    $ids = array_merge($ids, getAllDescendantFolderIds($conn, $row['id']));
  }
  return $ids;
}

// Delete folder (AJAX, optional for subfolders)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_folder') {
  $folder_id = intval($_POST['folder_id']);

  // Get all descendant folder IDs
  $allIds = [$folder_id];
  $descendants = getAllDescendantFolderIds($conn, $folder_id);
  if (!empty($descendants)) {
    $allIds = array_merge($allIds, $descendants);
  }

  // Prepare the placeholders for the IN clause
  $placeholders = implode(',', array_fill(0, count($allIds), '?'));
  $types = str_repeat('i', count($allIds));

  $now = date('Y-m-d H:i:s');
  $query = "UPDATE folders SET is_deleted = 1, deleted_at = ? WHERE id IN ($placeholders)";
  $stmt = $conn->prepare($query);

  // Merge $now and all folder IDs for binding
  $params = array_merge([$now], $allIds);

  // Bind parameters dynamically
  $bind_names[] = $types = 's' . $types;
  foreach ($params as $k => $param) {
    $bind_names[] = &$params[$k];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_names);

  if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Delete failed or not permitted.']);
  }
  exit();
}

// AJAX: Return folder list HTML for this parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_folder_list') {
  $folders = [];
  if (is_null($parent_id)) {
    $stmt = $conn->prepare("SELECT * FROM folders WHERE c_id = ? AND parent_id IS NULL AND is_deleted = 0 ORDER BY created_at DESC");
    $stmt->bind_param("i", $c_id);
  } else {
    $stmt = $conn->prepare("SELECT * FROM folders WHERE c_id = ? AND parent_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
    $stmt->bind_param("ii", $c_id, $parent_id);
  }
  $stmt->execute();
  $result = $stmt->get_result();

  // Fetch users for dropdown (for all modals)
  $userOptions = '';
  $userStmt = $conn->prepare("SELECT id, email FROM users WHERE c_id = ? AND is_active = 1");
  $userStmt->bind_param("i", $c_id);
  $userStmt->execute();
  $userRes = $userStmt->get_result();
  while ($u = $userRes->fetch_assoc()) {
    $userOptions .= '<option value="' . htmlspecialchars($u['id']) . '">' . htmlspecialchars($u['email']) . '</option>';
  }

  ob_start();
  while ($folder = $result->fetch_assoc()) {
?>
    <div class="card" data-folder-id="<?= $folder['id'] ?>">
      <div class="card-header" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z" />
        </svg>
      </div>
      <div class="card-body" onclick="window.location.href='folderinfo.php?parent_id=<?= $folder['id'] ?>'">
        <div class="card-title"><?= htmlspecialchars($folder['name']) ?></div>
        <div class="card-user">
          <?php
          $created = $folder['created_by'];
          $userQuery = "SELECT name, profile FROM users WHERE id = '$created'";
          $userResult = mysqli_query($conn, $userQuery);
          $userName = "Unknown";
          $profileImage = "https://i.pravatar.cc/40?img=" . rand(1, 70);
          if ($userResult && mysqli_num_rows($userResult) > 0) {
            $user = mysqli_fetch_assoc($userResult);
            if (!empty($user['name'])) $userName = $user['name'];
            if (!empty($user['profile'])) $profileImage = $user['profile'];
          }
          $user_id = $_SESSION['user_id'] ?? null;
          $displayName = ($created == $user_id) ? "You" : $userName;
          ?>
          <img src="<?= htmlspecialchars($profileImage) ?>" alt="User" width="40" height="40" style="border-radius:50%;">
          <span><?= htmlspecialchars($displayName) ?></span>
        </div>
        <div class="tags">
          <div class="tag">Folder</div>
          <div class="tag">From DB</div>
        </div>
      </div>
      <div class="card-footer">
        <div><?= date("d M Y", strtotime($folder['created_at'])) ?></div>
        <div class="actions">
          <!-- Share Button -->
          <i class="fas fa-share-alt" title="Share" data-bs-toggle="modal" data-bs-target="#shareFolderModal_<?= $folder['id'] ?>" style="cursor:pointer;"></i>
          <i class="fas fa-trash-alt" title="Delete" onclick="event.stopPropagation(); deleteFolder(<?= $folder['id'] ?>, this)"></i>
        </div>
      </div>
    </div>
    <!-- Share Modal (copied from folders.php) -->
    <div class="modal fade" id="shareFolderModal_<?= $folder['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">Share Folder: <?= htmlspecialchars($folder['name']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <!-- Permissions Table -->
            <table class="table text-center mb-4">
              <thead class="table-light">
                <tr>
                  <th>View</th>
                  <th>Write</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="perm-icon" id="perm-view"><span class="icon">❌</span></td>
                  <td class="perm-icon" id="perm-write"><span class="icon">❌</span></td>
                  <td class="perm-icon" id="perm-edit"><span class="icon">❌</span></td>
                  <td class="perm-icon" id="perm-delete"><span class="icon">❌</span></td>
                </tr>
              </tbody>
            </table>

            <!-- Add New Permission -->
            <h6>Add New Permission</h6>

            <div class="mb-3">
              <label class="form-label">Select User</label>
              <select class="form-select is-invalid">
                <option selected disabled>Select a user...</option>
                <?= $userOptions ?>
              </select>
            </div>

            <!-- Permission Checkboxes -->
            <div class="mb-3">
              <label class="form-label">Permissions</label>

              <label class="custom-check">
                <input type="checkbox" id="can-view">
                <span class="icon-box"></span>
                Can View <small class="text-muted">(User can view folder contents)</small>
              </label>

              <label class="custom-check">
                <input type="checkbox" id="can-write">
                <span class="icon-box"></span>
                Can Write <small class="text-muted">(User can upload documents)</small>
              </label>

              <label class="custom-check">
                <input type="checkbox" id="can-edit">
                <span class="icon-box"></span>
                Can Edit <small class="text-muted">(User can edit folder and document details)</small>
              </label>

              <label class="custom-check">
                <input type="checkbox" id="can-delete">
                <span class="icon-box"></span>
                Can Delete <small class="text-muted">(User can delete folder contents)</small>
              </label>
            </div>

          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-danger">Add Permission</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }
  $html = ob_get_clean();
  echo json_encode(['html' => $html]);
  exit();
}

// Handle file upload (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
  $folder_id = $parent_id;
  $uploaded_by = $user_id;
  $title = trim($_POST['fileTitle'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $responses = [];

  if (!isset($_FILES['files'])) {
    echo json_encode(['status' => 'error', 'message' => 'No files uploaded.']);
    exit();
  }

  foreach ($_FILES['files']['tmp_name'] as $i => $tmp_name) {
    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;

    $originalName = $_FILES['files']['name'][$i];
    $fileType = $_FILES['files']['type'][$i];
    $fileSize = $_FILES['files']['size'][$i];
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $uniqueName = uniqid('doc_', true) . '.' . $ext;
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $filePath = $uploadDir . $uniqueName;

    if (move_uploaded_file($tmp_name, $filePath)) {
      $query = "INSERT INTO documents (title, description, file_path, file_type, file_size, folder_id, uploaded_by, version, status, is_public, created_at, updated_at, deleted_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'active', 0, NOW(), NOW(), NULL)";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("ssssiii", $title, $description, $filePath, $fileType, $fileSize, $folder_id, $uploaded_by);
      if ($stmt->execute()) {
        $document_id = $stmt->insert_id;
        // Save version info
        $change_summary = 'Initial upload';
        $ver_stmt = $conn->prepare("INSERT INTO document_versions (document_id, version, file_path, file_size, created_by, change_summary, created_at) VALUES (?, 1, ?, ?, ?, ?, NOW())");
        $ver_stmt->bind_param("isiss", $document_id, $filePath, $fileSize, $uploaded_by, $change_summary);
        $ver_stmt->execute();
        $responses[] = ['status' => 'success', 'file' => $originalName];
      } else {
        $responses[] = ['status' => 'error', 'file' => $originalName, 'message' => $stmt->error];
      }
    } else {
      $responses[] = ['status' => 'error', 'file' => $originalName, 'message' => 'Upload failed'];
    }
  }
  echo json_encode(['results' => $responses]);
  exit();
}

// AJAX: Return file version history for a document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_file_versions') {
  $doc_id = intval($_POST['document_id']);

  // Get latest version number
  $stmt_latest = $conn->prepare("SELECT MAX(version) as max_ver FROM document_versions WHERE document_id = ?");
  $stmt_latest->bind_param("i", $doc_id);
  $stmt_latest->execute();
  $res_latest = $stmt_latest->get_result();
  $latest_version = 1;
  if ($row = $res_latest->fetch_assoc()) {
    $latest_version = intval($row['max_ver']);
  }

  // Fetch all previous versions (exclude latest)
  $stmt = $conn->prepare("SELECT id, document_id, version, file_path, file_size, created_by, change_summary, created_at FROM document_versions WHERE document_id = ? AND version < ? ORDER BY version DESC");
  $stmt->bind_param("ii", $doc_id, $latest_version);
  $stmt->execute();
  $result = $stmt->get_result();

  ob_start();
  if ($result->num_rows > 0) {
  ?>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Version</th>
        <th>File</th>
        <th>Info</th>
        <?php if (Utils::canDownloadDocuments($conn, $user_id)) { ?>
        <th>Download</th>
        <?php } ?>
        <th>Delete</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($ver = $result->fetch_assoc()): ?>
        <?php
          // Prepare metadata for tooltip
          $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM users WHERE id=".(int)$ver['created_by']));
          $meta = [
            "Uploaded By" => htmlspecialchars($u ? $u['name'] : 'User#'.$ver['created_by']),
            "File Size" => round($ver['file_size']/1024, 2) . " KB",
            "Uploaded At" => htmlspecialchars($ver['created_at']),
            "Change Summary" => htmlspecialchars($ver['change_summary']),
            "File Path" => htmlspecialchars($ver['file_path']),
          ];
          $metaText = "";
          foreach ($meta as $k => $v) $metaText .= "<b>$k:</b> $v<br>";
        ?>
        <tr>
          <td><?= htmlspecialchars($ver['version']) ?></td>
          <td><?= basename($ver['file_path']) ?></td>
          <td>
            <i class="fas fa-info-circle text-info" 
               data-bs-toggle="tooltip" 
               data-bs-html="true" 
               title="<?= htmlspecialchars($metaText) ?>"></i>
          </td>
          <?php if (Utils::canDownloadDocuments($conn, $user_id)): ?>
          <td>
            <a href="<?= htmlspecialchars($ver['file_path']) ?>" download class="btn btn-sm btn-outline-primary">
              <i class="fas fa-download"></i>
            </a>
          </td>
          <?php endif; ?>
          <td>
            <button class="btn btn-sm btn-danger" onclick="deleteVersion(<?= $ver['id'] ?>, <?= $ver['document_id'] ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <div class="text-end">
    <button class="btn btn-danger btn-sm" onclick="deleteAllVersions(<?= $doc_id ?>)">
      <i class="fas fa-trash"></i> Delete All Versions
    </button>
  </div>
  <script>
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
  </script>
  <?php
  } else {
    echo '<div class="text-center text-muted py-4">No version history found.</div>';
  }
  $html = ob_get_clean();
  echo json_encode(['html' => $html]);
  exit();
}

// AJAX: Return file list HTML for this folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_file_list') {
  $folder_id = $parent_id;
  $stmt = $conn->prepare("SELECT id, title, description, file_path, file_type, file_size, uploaded_by, created_at FROM documents WHERE folder_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
  $stmt->bind_param("i", $folder_id);
  $stmt->execute();
  $result = $stmt->get_result();

  ob_start();
  ?>


<?php if ($result && $result->num_rows > 0): ?>
  <table class="table table-hover align-middle text-center">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Title</th>
        <th>Type</th>
        <th>Size (KB)</th>
        <th>Uploaded At</th>
        <th>Version</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = 1;
      while ($doc = $result->fetch_assoc()) {
        $file_ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
        $is_ppt = in_array($file_ext, ['ppt', 'pptx']);
        $is_word = in_array($file_ext, ['doc', 'docx']);

        // Version fetch
        $doc_version = 1;
        if (isset($doc['id'])) {
          $ver_q = mysqli_query($conn, "SELECT version FROM documents WHERE id=".(int)$doc['id']);
          if ($ver_q && $ver_row = mysqli_fetch_assoc($ver_q)) {
            $doc_version = (int)$ver_row['version'];
          }
        }
      ?>
        <tr>
          <td><?= $count++ ?></td>
          <td><?= htmlspecialchars($doc['title']) ?></td>
          <td class="text-uppercase"><?= htmlspecialchars($file_ext) ?></td>
          <td><?= round($doc['file_size'] / 1024, 1) ?></td>
          <td><?= date("d M Y H:i", strtotime($doc['created_at'])) ?></td>
          <td>v<?= $doc_version ?></td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($is_ppt): ?>
                  <li><a class="dropdown-item" href="#" onclick="presentPPTOffline('<?= htmlspecialchars($doc['file_path']) ?>'); return false;">Present</a></li>
                <?php elseif ($is_word): ?>
                  <li><a class="dropdown-item" href="#" onclick="viewWordOffline('<?= htmlspecialchars($doc['file_path']) ?>'); return false;">View</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="#" onclick="viewDocument('<?= htmlspecialchars($doc['file_path']) ?>','<?= htmlspecialchars($doc['file_type']) ?>'); return false;">View</a></li>
                <?php endif; ?>

                <?php if (Utils::canDownloadDocuments($conn, $user_id)): ?>
                  <li><a class="dropdown-item" href="<?= htmlspecialchars($doc['file_path']) ?>" download>Download</a></li>
                <?php endif; ?>

                <li><a class="dropdown-item" href="#" onclick="showFileVersions(<?= $doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['title'])) ?>'); return false;">Versions</a></li>
                <li><a class="dropdown-item" href="#" onclick="showUploadVersionModal(<?= $doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['title'])) ?>'); return false;">Upload Version</a></li>
                <li>
                  <a class="dropdown-item text-danger" href="#" onclick="deleteDocument(<?= $doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['title'])) ?>'); return false;">
                    <i class="fas fa-trash-alt"></i> Delete
                  </a>
                </li>
              </ul>
            </div>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
<?php else: ?>
  <div class="alert text-center">No files present.</div>
<?php endif; ?>


<?php
  $html = ob_get_clean();
  echo json_encode(['html' => $html]);
  exit();
}

// Handle upload of a new version for an existing document (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_version') {
  $document_id = intval($_POST['document_id']);
  $change_summary = trim($_POST['change_summary'] ?? '');
  $uploaded_by = $user_id;

  if (!isset($_FILES['version_file']) || $_FILES['version_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
    exit();
  }

  // Get current version number
  $ver_stmt = $conn->prepare("SELECT MAX(version) as max_ver FROM document_versions WHERE document_id = ?");
  $ver_stmt->bind_param("i", $document_id);
  $ver_stmt->execute();
  $ver_res = $ver_stmt->get_result();
  $max_ver = 1;
  if ($row = $ver_res->fetch_assoc()) {
    $max_ver = intval($row['max_ver']) + 1;
  }

  $originalName = $_FILES['version_file']['name'];
  $fileType = $_FILES['version_file']['type'];
  $fileSize = $_FILES['version_file']['size'];
  $ext = pathinfo($originalName, PATHINFO_EXTENSION);
  $uniqueName = uniqid('doc_', true) . '.' . $ext;
  $uploadDir = '../uploads/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
  $filePath = $uploadDir . $uniqueName;

  if (move_uploaded_file($_FILES['version_file']['tmp_name'], $filePath)) {
    // Insert into document_versions
    $ver_ins = $conn->prepare("INSERT INTO document_versions (document_id, version, file_path, file_size, created_by, change_summary, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $ver_ins->bind_param("iisiss", $document_id, $max_ver, $filePath, $fileSize, $uploaded_by, $change_summary);
    $ver_ins->execute();

    // Update main document record
    $doc_upd = $conn->prepare("UPDATE documents SET file_path=?, file_type=?, file_size=?, version=?, updated_at=NOW() WHERE id=?");
    $doc_upd->bind_param("ssiii", $filePath, $fileType, $fileSize, $max_ver, $document_id);
    $doc_upd->execute();

    echo json_encode(['status' => 'success', 'message' => 'Version uploaded successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
  }
  exit();
}

// Delete specific version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_version') {
  $version_id = intval($_POST['version_id']);
  $stmt = $conn->prepare("DELETE FROM document_versions WHERE id = ?");
  $stmt->bind_param("i", $version_id);
  $stmt->execute();
  echo json_encode(['status' => 'success']);
  exit();
}

// Delete all versions for a document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all_versions') {
  $doc_id = intval($_POST['document_id']);
  $stmt = $conn->prepare("DELETE FROM document_versions WHERE document_id = ?");
  $stmt->bind_param("i", $doc_id);
  $stmt->execute();
  echo json_encode(['status' => 'success']);
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include "components/head.php"; ?>
  <!-- Add SweetAlert2 CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pptxgenjs/3.11.0/pptxgen.bundle.js"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,500,600&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
      padding: 2rem;
    }

    .form-container {
      max-width: 600px;
      margin: 0 auto 2rem;
      background: #fff;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-container input {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .form-container button {
      background-color: #fbbf24;
      border: none;
      color: white;
      padding: 0.75rem 1.5rem;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
    }

    .folder-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .card {
      width: 222px;
      height: 250px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .card-header {
      background-color: #fef3c7;
      height: 90px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card-header svg {
      width: 50px;
      height: 50px;
      fill: #fbbf24;
    }

    .card-body {
      padding: 0.75rem 1rem;
      flex-grow: 1;
    }

    .card-title {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 0.3rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card-user {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 0.5rem;
    }

    .card-user img {
      width: 24px;
      height: 24px;
      border-radius: 50%;
    }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 0.25rem;
    }

    .tag {
      background-color: #f3f4f6;
      color: #4b5563;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 0.65rem;
    }

    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.7rem;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
      padding: 0.5rem 0.75rem;
    }

    .card-footer i {
      margin-left: 10px;
      cursor: pointer;
      color: #6b7280;
    }

    .card-footer i:hover {
      color: #111827;
    }

    .actions {
      display: flex;
      align-items: center;
    }

    /* Custom Checkbox Styling */
    .custom-check {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      cursor: pointer;
      user-select: none;
    }

    .custom-check input {
      display: none;
    }

    .custom-check .icon-box {
      width: 24px;
      height: 24px;
      border-radius: 6px;
      background-color: #f8d7da;
      border: 2px solid #dc3545;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      transition: all 0.3s ease;
      font-size: 14px;
      color: #dc3545;
    }

    .custom-check input:checked+.icon-box {
      background-color: #dc3545;
      color: white;
      transform: scale(1.1);
    }

    .custom-check input:checked+.icon-box::before {
      content: '\f00c';
      /* FontAwesome check */
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
    }

    .custom-check input:not(:checked)+.icon-box::before {
      content: '\f00d';
      /* FontAwesome times */
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
    }

    .perm-icon .icon {
      font-size: 2rem;
      color: #e74c3c;
      /* red by default */
      transition: all 0.3s ease;
    }

    .perm-icon .icon.active {
      color: #27ae60;
      /* green when active */
      transform: scale(1.2);
    }

    .custom-check {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .custom-check input[type="checkbox"] {
      display: none;
    }

    .custom-check .check-icon {
      width: 24px;
      height: 24px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8d7da;
      color: #e74c3c;
      border: 1px solid #e74c3c;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .custom-check input[type="checkbox"]:checked+.check-icon {
      background: #d4edda;
      color: #28a745;
      border-color: #28a745;
      transform: scale(1.1);
    }

    .custom-check small {
      font-weight: normal;
      color: #777;
    }


    .form-control,
    .form-select {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 8px;
      box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .form-control,
    .form-select {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 8px;
      box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .file-card {
      border: 1px solid #f3f4f6;
      transition: box-shadow 0.2s;
    }

    .file-card:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }

    /* Add file grid styles */
    .file-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 24px;
      margin-top: 10px;
    }

    .file-card.attractive-card {
      min-width: 0;
      min-height: 210px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
      border: 1px solid #f3f4f6;
      transition: box-shadow 0.2s, transform 0.2s;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #f8fafc 60%, #fff 100%);
    }

    .file-card.attractive-card:hover {
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.13);
      transform: translateY(-2px) scale(1.02);
      border-color: #e0e7ef;
    }

    .file-icon {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      background: #f8fafc;
    }

    .file-badge {
      padding: 0.35em 0.7em;
      font-weight: 600;
      letter-spacing: 0.03em;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    }
  </style>
</head>

<body class="g-sidenav-show  bg-gray-100">

  <?php include "components/sidebar.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php include "components/navbar.php"; ?>
    <div class="container-fluid py-2">

      <!-- Folder Header and Add Button Row -->
      <div class="row mb-4">
        <div class="col-md-6 d-flex flex-column justify-content-center">
          <h4>Folders</h4>
          <p class="text-muted mb-0">Manage your document in folders</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
             <?php 
                if (Utils::canManageFolders($conn, $user_id) && Utils::isSuperadmin($conn, $user_id)) {
              ?>
          <button class="btn btn-warning fw-bold me-2" data-bs-toggle="modal" data-bs-target="#addFolderModal">
            <i class="fas fa-plus me-2"></i> Add Folder
          </button>
          <?php } ?>
           <?php 
                if (Utils::canUploadDocuments($conn, $user_id)) {
          ?>
          <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
            <i class="fas fa-upload me-2"></i> Upload Files
          </button>
          <?php } ?>
        </div>
      </div>

      <!-- Folder Cards -->
      <div class="folder-grid" id="folderList">
        <!-- Dynamic folder cards will appear here -->
      </div>

      <!-- Uploaded Files List -->
      <div class="mt-4">
        <h5>Files in this Folder</h5>
        <div id="fileList"></div>
      </div>

      <!-- Add Folder Modal -->
      <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="folderForm">
              <div class="modal-header">
                <h5 class="modal-title" id="addFolderModalLabel">Create New Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="input-group input-group-outline mb-3">
                  <input type="text" id="folderName" class="form-control" placeholder="Enter folder name..." required />

                </div>
                <div class="mb-3">
                  <input id="folderDesc" class="form-control" placeholder="Description (optional)">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Create Folder</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Upload Files Modal -->
      <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="uploadFileForm" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="uploadFileModalLabel">Upload Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="input-group input-group-outline mb-3">
                  <input type="text" id="fileTitle" name="fileTitle" class="form-control" placeholder="Enter Document Title" required />
                </div>
                <div class="mb-3">
                  <input id="fileDesc" name="description" class="form-control" placeholder="Description (optional)">
                </div>
                <div class="input-group input-group-outline mb-3">
                  <input type="file" id="fileInput" name="files[]" class="form-control" multiple required />
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Global Version History Modal -->
      <div class="modal fade" id="fileVersionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="fileVersionsModalTitle">Version History</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fileVersionsBody">
              <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Global Upload Version Modal -->
      <div class="modal fade" id="uploadVersionModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="uploadVersionForm">
              <div class="modal-header">
                <h5 class="modal-title" id="uploadVersionModalTitle">Upload New Version</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Select File</label>
                  <input type="file" name="version_file" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Change Summary</label>
                  <input type="text" name="change_summary" class="form-control" placeholder="Describe the change..." required>
                </div>
                <input type="hidden" name="document_id" id="uploadVersionDocId">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Upload Version</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- end global modals -->

      <?php include "components/footer.php"; ?>
    </div>
  </main>

  <!-- Core JS Files -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
  <script src="https://unpkg.com/mammoth/mammoth.browser.min.js"></script>


  <script>
    const form = document.getElementById('folderForm');
    const folderList = document.getElementById('folderList');
    const fileList = document.getElementById('fileList');
    const uploadFileForm = document.getElementById('uploadFileForm');

    // Fetch and update folder list/grid
    function updateFolderList() {
      const formData = new FormData();
      formData.append('action', 'get_folder_list');
      fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.html !== undefined) {
            folderList.innerHTML = data.html;
          }
        });
    }

    // Delegate delete click to folderList for dynamic elements
    folderList.addEventListener('click', function(e) {
      if (e.target.classList.contains('fa-trash-alt')) {
        e.stopPropagation();
        const card = e.target.closest('.card');
        const folderId = card.getAttribute('data-folder-id');
        deleteFolder(folderId, card);
      }
    });

    function deleteFolder(folderId, cardElement) {
      // Use SweetAlert2 for confirmation
      Swal.fire({
        title: 'Are you absolutely sure?',
        text: "This action cannot be undone. The folder and its contents will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Final Confirmation',
            text: "Please confirm again to delete the folder.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
          }).then((finalResult) => {
            if (finalResult.isConfirmed) {
              const formData = new FormData();
              formData.append('action', 'delete_folder');
              formData.append('folder_id', folderId);

              fetch(window.location.href, {
                  method: 'POST',
                  body: formData
                })
                .then(res => res.json())
                .then(data => {
                  if (data.status === 'success') {
                    Swal.fire('Deleted!', 'Folder has been deleted.', 'success');
                    updateFolderList();
                  } else {
                    Swal.fire('Error', data.message || 'Delete failed.', 'error');
                  }
                })
                .catch(() => Swal.fire('Error', 'Network or server error.', 'error'));
            }
          });
        }
      });
    }

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const name = document.getElementById('folderName').value.trim();
      const desc = document.getElementById('folderDesc').value.trim();
      if (!name) return;

      const formData = new FormData();
      formData.append('action', 'create_folder');
      formData.append('name', name);
      formData.append('description', desc);

      fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then (data => {
          if (data.status === 'success') {
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('addFolderModal')).hide();
            updateFolderList();
            Swal.fire('Success', 'Folder created successfully.', 'success');
          } else {
            Swal.fire('Error', data.message, 'error');
          }
        })
        .catch(err => Swal.fire('Error', err, 'error'));
    });

    // Fetch and update file list for this folder
    function updateFileList() {
      const formData = new FormData();
      formData.append('action', 'get_file_list');
      fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.html !== undefined) {
            fileList.innerHTML = data.html;
          }
        });
    }

    // Handle file upload
    uploadFileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(uploadFileForm);
      formData.append('action', 'upload_file');
      fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          let msg = '';
          if (data.results) {
            data.results.forEach(r => {
              if (r.status === 'success') {
                msg += `✅ ${r.file} uploaded successfully.<br>`;
              } else {
                msg += `❌ ${r.file}: ${r.message}<br>`;
              }
            });
          }
          Swal.fire('Upload Result', msg, 'info');
          uploadFileForm.reset();
          bootstrap.Modal.getInstance(document.getElementById('uploadFileModal')).hide();
          updateFileList();
        })
        .catch(() => Swal.fire('Error', 'Upload failed.', 'error'));
    });

    // View document (PDF, images, etc.)
    function viewDocument(filePath, fileType) {
      const ext = filePath.split('.').pop().toLowerCase();
      // List of supported types for in-browser viewing
      const supportedExts = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
      const unsupportedTypes = [
        'application/octet-stream',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/csv'
      ];
      if (supportedExts.includes(ext)) {
        Swal.fire({
          html: `
            <div style="position:relative;width:100%;height:70vh;">
              <iframe id="docFrame" src="${filePath}" style="width:100%;height:100%;border:none;"></iframe>
              <button onclick="fullscreenIframe('docFrame')" style="position:absolute;top:10px;right:10px;z-index:10;" class="btn btn-sm btn-dark"><i class="fas fa-expand"></i> Fullscreen</button>
            </div>
          `,
          width: '80vw',
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: 'swal-wide'
          }
        });
      } else if (
        unsupportedTypes.includes(fileType) || ['csv'].includes(ext)
      ) {
        Swal.fire({
          icon: 'info',
          title: 'Preview Not Supported',
          html: `
            <div>
              This file type cannot be viewed in the browser.<br>
              Please download and open it with the appropriate application.
            </div>
            <a href="${filePath}" class="btn btn-primary mt-3" download>Download File</a>
          `,
          showConfirmButton: false,
          showCloseButton: true
        });
      } else {
        // For other types, just open in new tab (don't force download)
        window.open(filePath, '_blank');
      }
    }

    // Present PPTX/PPT using PptxGenJS (client-side, offline)
    function presentPPTOffline(filePath) {
      const ext = filePath.split('.').pop().toLowerCase();
      if (ext === 'pptx' || ext === 'ppt') {
        fetch(filePath)
          .then(response => response.blob())
          .then(blob => {
            const reader = new FileReader();
            reader.onload = function(e) {
              // PptxGenJS only supports creating presentations, not rendering existing ones.
              // So, we show a message and offer download/open in PowerPoint.
              Swal.fire({
                html: `
                  <div style="width:100%;height:70vh;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <div class="mb-3">
                      <i class="fas fa-file-powerpoint fa-3x text-warning"></i>
                    </div>
                    <div class="mb-2">In-browser PPTX viewing is not natively supported.<br>
                    <b>Please <a href="${filePath}" download>download</a> and open in PowerPoint or use <a href="https://www.libreoffice.org/download/download/" target="_blank">LibreOffice</a>.</b></div>
                  </div>
                `,
                width: '40vw',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                  popup: 'swal-wide'
                }
              });
            };
            reader.readAsArrayBuffer(blob);
          })
          .catch(() => {
            Swal.fire('Error', 'Could not load the PPTX file.', 'error');
          });
      }
    }

    // View Word offline and allow fullscreen of the rendered content
    function viewWordOffline(filePath) {
      fetch(filePath)
        .then(response => response.arrayBuffer())
        .then(arrayBuffer => {
          mammoth.convertToHtml({
              arrayBuffer: arrayBuffer
            })
            .then(result => {
              Swal.fire({
                html: `
              <div style="position:relative;">
                <button onclick="fullscreenElementById('wordContentWrapper')" 
                        style="position:absolute;top:10px;right:10px;z-index:10;" 
                        class="btn btn-sm btn-dark">
                  <i class="fas fa-expand"></i> Fullscreen
                </button>
                <div id="wordContentWrapper" style="height:70vh;overflow:auto; padding: 10px; border: 1px solid #ccc;">
                  ${result.value}
                </div>
              </div>
            `,
                width: '80vw',
                showCloseButton: true,
                showConfirmButton: false
              });
            })
            .catch(err => {
              Swal.fire('Error', 'Could not display document.', 'error');
            });
        });
    }

    // Generic fullscreen function for a given element ID
    function fullscreenElementById(id) {
      const element = document.getElementById(id);
      if (element.requestFullscreen) {
        element.requestFullscreen();
      } else if (element.mozRequestFullScreen) { // Firefox
        element.mozRequestFullScreen();
      } else if (element.webkitRequestFullscreen) { // Chrome, Safari, Opera
        element.webkitRequestFullscreen();
      } else if (element.msRequestFullscreen) { // IE/Edge
        element.msRequestFullscreen();
      }
    }


    // After folders and files loaded
    updateFolderList();
    updateFileList();

    // Show file version history in global modal
    function showFileVersions(docId, docTitle) {
      const modalEl = document.getElementById('fileVersionsModal');
      const bodyEl = document.getElementById('fileVersionsBody');
      const titleEl = document.getElementById('fileVersionsModalTitle');
      if (!modalEl || !bodyEl) return;
      if (titleEl) titleEl.textContent = 'Version History: ' + (docTitle || '');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      bodyEl.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
      const formData = new FormData();
      formData.append('action', 'get_file_versions');
      formData.append('document_id', docId);
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        bodyEl.innerHTML = data.html || '<div class="text-danger">No version history found.</div>';
        // Re-initialize tooltips after AJAX content loads
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      })
      .catch(() => {
        bodyEl.innerHTML = '<div class="text-danger">Failed to load version history.</div>';
      });
    }

    // Show upload version modal (global)
    function showUploadVersionModal(docId, docTitle) {
      const modalEl = document.getElementById('uploadVersionModal');
      const titleEl = document.getElementById('uploadVersionModalTitle');
      if (!modalEl) return;
      if (titleEl) titleEl.textContent = 'Upload New Version: ' + (docTitle || '');
      document.getElementById('uploadVersionForm').reset();
      document.getElementById('uploadVersionDocId').value = docId;
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }

    // Handle upload version form submit (global)
    document.getElementById('uploadVersionForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const form = e.target;
      const modalEl = document.getElementById('uploadVersionModal');
      const formData = new FormData(form);
      formData.append('action', 'upload_version');
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          bootstrap.Modal.getInstance(modalEl).hide();
          Swal.fire('Success', data.message, 'success');
          updateFileList();
        } else {
          Swal.fire('Error', data.message, 'error');
        }
      })
      .catch(() => Swal.fire('Error', 'Upload failed.', 'error'));
    });

    function deleteVersion(versionId, docId) {
      Swal.fire({
        title: 'Delete this version?',
        text: "This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('action', 'delete_version');
          formData.append('version_id', versionId);
          fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then (data => {
            if (data.status === 'success') {
              Swal.fire('Deleted!', 'Version deleted.', 'success');
              showFileVersions(docId);
            } else {
              Swal.fire('Error', 'Could not delete version.', 'error');
            }
          });
        }
      });
    }

    function deleteAllVersions(docId) {
      Swal.fire({
        title: 'Delete ALL versions?',
        text: "This will remove all version history for this document!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete All',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('action', 'delete_all_versions');
          formData.append('document_id', docId);
          fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              Swal.fire('Deleted!', 'All versions deleted.', 'success');
              showFileVersions(docId);
            } else {
              Swal.fire('Error', 'Could not delete all versions.', 'error');
            }
          });
        }
      });
    }
  </script>

</body>
</html>