<?php
session_start();
include '../config/conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

echo $_SESSION['user_id'] ;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include "components/head.php";
  ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <style>
      table#logTable {
    border-collapse: collapse;
    width: 100%;
    border: 1px solid #ddd; 
}

table#logTable th, table#logTable td {
    border: 1px solid #ddd;
    padding: 8px; 
    text-align: center;
}

table#logTable th {
    background-color: #f4f4f4; 
    font-weight: bold; 
} 

#searchForm .form-control, 
#searchForm .form-select {
    border: 1px solid #ddd; 
    border-radius: 4px; 
    padding: 8px;
    box-shadow: none;
}

#searchForm .form-control:focus, 
#searchForm .form-select:focus {
    border-color: #007bff; 
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); 
}
    #addDocumentForm .form-control,
    #addDocumentForm .form-select {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px;
        box-shadow: none;
    }

    #addDocumentForm .form-control:focus,
    #addDocumentForm .form-select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-100">

    <?php
      include "components/sidebar.php";
    ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

        <?php
        include "components/navbar.php";
      ?>
        <div class="container-fluid py-2">
            <div class="row mb-4">
                <div class="col-md-6 d-flex flex-column justify-content-center">
                    <h4>Documents</h4>
                    <p class="text-muted mb-0">Manage your document</p>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
                    <button class="btn btn-primary fw-bold m-1" data-bs-toggle="modal"
                        data-bs-target="#addDocumentModal">
                        <i class="fas fa-plus me-2"></i> Add Document
                    </button>
                </div>
            </div>

            <!-- Search Documents Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Search Documents</h5>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#searchFormCollapse" aria-expanded="false" aria-controls="searchFormCollapse">
                        Toggle Search
                    </button>
                </div>
                <div id="searchFormCollapse" class="collapse">
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <!-- Search Text -->
                            <div class="col-md-4">
                                <label for="searchText" class="form-label">Search Text</label>
                                <input type="text" id="searchText" class="form-control" placeholder="Enter keywords...">
                            </div>

                            <!-- File Type -->
                            <div class="col-md-4">
                                <label for="fileType" class="form-label">File Type</label>
                                <select id="fileType" class="form-select">
                                    <option value="" selected>All Types</option>
                                    <option value="pdf">PDF</option>
                                    <option value="docx">Word</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="txt">Text</option>
                                </select>
                            </div>

                            <!-- Folder -->
                            <div class="col-md-4">
                                <label for="folder" class="form-label">Folder</label>
                                <select id="folder" class="form-select">
                                    <option value="" selected>All Folders</option>
                                    <option value="folderA">Folder A</option>
                                    <option value="folderB">Folder B</option>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-4">
                                <label for="dateRange" class="form-label">Date Range</label>
                                <input type="date" id="startDate" class="form-control mb-2" placeholder="Start Date">
                                <input type="date" id="endDate" class="form-control" placeholder="End Date">
                            </div>

                            <!-- Uploaded By -->
                            <div class="col-md-4">
                                <label for="uploadedBy" class="form-label">Uploaded By</label>
                                <input type="text" id="uploadedBy" class="form-control" placeholder="Enter uploader name...">
                            </div>

                            <!-- Tags -->
                            <div class="col-md-4">
                                <label for="tags" class="form-label">Tags</label>
                                <select id="tags" class="form-select" multiple>
                                    <option value="tag1">Tag 1</option>
                                    <option value="tag2">Tag 2</option>
                                    <option value="tag3">Tag 3</option>
                                </select>
                            </div>

                            <!-- Search Button -->
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" onclick="searchDocuments()">Search</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



            <div class="card-body px-0 pb-2">
                <div class="table-responsive p-0">
                    <table id="logTable" class="table align-items-center mb-0 display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Title</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-15 ps-2">
                                    Tags</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Folder</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Type</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Size</th>

                                    <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    version</th>
                                <th
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-15">
                                    Uploaded By</th>
                                <!-- <th class="text-secondary opacity-7">Size</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex justify-content-center px-2 py-1">
                                        Demo
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">#document</p>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-secondary">Resumnes</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">Excel</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">1.5 Mb</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">1</span>
                                </td>
                                <td class="d-flex justify-content-center align-items-center px-2">
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/img/team-2.jpg"
                                            class="avatar avatar-sm me-3 border-radius-lg" alt="user1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm text-center">Vivek Jadhav</h6>
                                            <p class="text-xs text-secondary mb-0 text-center">vivekjadhav@gmail.com</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Document Modal -->
            <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDocumentModalLabel">Upload Document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addDocumentForm" enctype="multipart/form-data">
                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="documentTitle" class="form-label">Title</label>
                                    <input type="text" id="documentTitle" name="title" class="form-control" required>
                                </div>
                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="documentDescription" class="form-label">Description</label>
                                    <textarea id="documentDescription" name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <!-- Folder -->
                                <div class="mb-3">
                                    <label for="documentFolder" class="form-label">Folder</label>
                                    <select id="documentFolder" name="folder" class="form-select">
                                        <option value="">All Folders</option>
                                        <option value="folderA">Folder A</option>
                                        <option value="folderB">Folder B</option>
                                    </select>
                                </div>
                                <!-- Tags -->
                                <div class="mb-3">
                                    <label class="form-label">Tags</label>
                                    <div id="tagCheckboxes" class="mb-2">
                                        <label class="me-2"><input type="checkbox" value="demo" class="tag-checkbox"> demo</label>
                                        <label class="me-2"><input type="checkbox" value="IFCI" class="tag-checkbox"> IFCI</label>
                                        <label class="me-2"><input type="checkbox" value="iShine" class="tag-checkbox"> iShine</label>
                                        <label class="me-2"><input type="checkbox" value="PO Portal" class="tag-checkbox"> PO Portal</label>
                                        <label class="me-2"><input type="checkbox" value="Sahara" class="tag-checkbox"> <span style='color:#d9534f'>Sahara</span></label>
                                        <label class="me-2"><input type="checkbox" value="VIVEK" class="tag-checkbox"> VIVEK</label>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="addNewTagBtn">+ Add New Tag</button>
                                    <div id="selectedTags" class="mb-2"></div>
                                </div>
                                <!-- File Upload -->
                                <div class="mb-3">
                                    <label for="documentFile" class="form-label">Document File</label>
                                    <input type="file" id="documentFile" name="documentFile" class="form-control" required>
                                    <small class="form-text text-muted">Supported file types: PDF, Word, Excel, PowerPoint, Images</small>
                                </div>
                                <!-- Submit Button -->
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Upload Document</button>
                                </div>
                            </form>
                            <div id="uploadMsg" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Tag selection and badge display
                $(document).on('change', '.tag-checkbox', function() {
                    const selected = [];
                    $('.tag-checkbox:checked').each(function() {
                        selected.push($(this).val());
                    });
                    const badgeContainer = $('#selectedTags');
                    badgeContainer.empty();
                    selected.forEach(tag => {
                        const badge = `<span class="badge bg-primary me-2">${tag} <i class="fas fa-times text-white ms-1" style="cursor:pointer;" onclick="removeTagBadge('${tag}')"></i></span>`;
                        badgeContainer.append(badge);
                    });
                });
                function removeTagBadge(tag) {
                    $(`.tag-checkbox[value='${tag}']`).prop('checked', false).trigger('change');
                }
                $('#addNewTagBtn').on('click', function() {
                    const newTag = prompt('Enter new tag:');
                    if (newTag) {
                        const safeTag = newTag.replace(/'/g, "");
                        $('#tagCheckboxes').append(`<label class='me-2'><input type='checkbox' value='${safeTag}' class='tag-checkbox'> ${safeTag}</label>`);
                    }
                });

                // AJAX form submission
                $('#addDocumentForm').on('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    // Add selected tags
                    const tags = [];
                    $('.tag-checkbox:checked').each(function() { tags.push($(this).val()); });
                    formData.append('tags', JSON.stringify(tags));
                    formData.append('action', 'upload_document');
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#uploadMsg').html(`<div class='alert alert-success'>${response}</div>`);
                            $('#addDocumentForm')[0].reset();
                            $('#selectedTags').empty();
                            $('.tag-checkbox').prop('checked', false);
                        },
                        error: function(xhr) {
                            $('#uploadMsg').html(`<div class='alert alert-danger'>Upload failed: ${xhr.responseText}</div>`);
                        }
                    });
                });

                // Search Documents AJAX
                function searchDocuments() {
                    const formData = {
                        action: 'search_documents',
                        searchText: $('#searchText').val(),
                        fileType: $('#fileType').val(),
                        folder: $('#folder').val(),
                        startDate: $('#startDate').val(),
                        endDate: $('#endDate').val(),
                        uploadedBy: $('#uploadedBy').val(),
                        tags: $('#tags').val() // array
                    };
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            // Clear table body
                            const tbody = $('#logTable tbody');
                            tbody.empty();
                            if (response.length === 0) {
                                tbody.append('<tr><td colspan="7" class="text-center">No documents found.</td></tr>');
                                return;
                            }
                            response.forEach(function(doc) {
                                let tagsHtml = '';
                                if (doc.tags) {
                                    doc.tags.split(',').forEach(function(tag) {
                                        tagsHtml += `<span class='badge bg-secondary me-1'>${tag}</span>`;
                                    });
                                }
                                tbody.append(`
                                    <tr>
                                        <td><div class='d-flex justify-content-center px-2 py-1'>${doc.title}</div></td>
                                        <td>${tagsHtml}</td>
                                        <td class='align-middle text-center text-sm'><span class='text-secondary'>${doc.folder}</span></td>
                                        <td class='align-middle text-center'><span class='text-secondary text-xs font-weight-bold'>${doc.file_type}</span></td>
                                        <td class='align-middle text-center'><span class='text-secondary text-xs font-weight-bold'>${doc.file_size}</span></td>
                                        <td class='align-middle text-center'><span class='text-secondary text-xs font-weight-bold'>${doc.version || 1}</span></td>
                                        <td class='d-flex justify-content-center align-items-center px-2'>
                                            <div class='d-flex align-items-center'>
                                                <img src='../assets/img/team-2.jpg' class='avatar avatar-sm me-3 border-radius-lg' alt='user1'>
                                                <div class='d-flex flex-column justify-content-center'>
                                                    <h6 class='mb-0 text-sm text-center'>${doc.uploader_name || ''}</h6>
                                                    <p class='text-xs text-secondary mb-0 text-center'>${doc.uploader_email || ''}</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                `);
                            });
                        },
                        error: function(xhr) {
                            alert('Search failed.');
                        }
                    });
                }
            </script>

            <?php
        include "components/footer.php";
      ?>
        </div>
    </main>
    <?php
?>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>

    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

    <!-- jQuery & DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- Initialize DataTable -->
    <script>
$(document).ready(function() {
    $('#logTable').DataTable({
        dom: 'Bfrtip',
        buttons: [], 
        responsive: true,
        info: false, 
        paging: false 
    });
});
</script>
</body>

</html>

<?php
// Backend handler for document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $folder = $_POST['folder'] ?? '';
    $tags = isset($_POST['tags']) ? json_decode($_POST['tags'], true) : [];
    $user_id = $_SESSION['user_id'] ?? 0;
    $c_id = $_SESSION['c_id'] ?? 0;
    // File upload
    if (isset($_FILES['documentFile']) && $_FILES['documentFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['documentFile']['tmp_name'];
        $fileName = basename($_FILES['documentFile']['name']);
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif'];
        if (!in_array(strtolower($fileType), $allowed)) {
            echo "Invalid file type.";
            exit;
        }
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $targetPath = $uploadDir . uniqid() . '_' . $fileName;
        if (move_uploaded_file($fileTmp, $targetPath)) {
            // Save to DB (example, adjust table/fields as needed)
            $stmt = $conn->prepare("INSERT INTO documents (title, description, folder, tags, file_path, file_type, uploaded_by, c_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $tagsStr = implode(',', $tags);
            $stmt->bind_param('ssssssii', $title, $description, $folder, $tagsStr, $targetPath, $fileType, $user_id, $c_id);
            if ($stmt->execute()) {
                echo "Document uploaded successfully!";
            } else {
                echo "Database error: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "Failed to move uploaded file.";
        }
    } else {
        echo "No file uploaded or upload error.";
    }
    exit;
}

// Backend handler for document search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_documents') {
    $searchText = $_POST['searchText'] ?? '';
    $fileType = $_POST['fileType'] ?? '';
    $folder = $_POST['folder'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $uploadedBy = $_POST['uploadedBy'] ?? '';
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    if (is_string($tags)) $tags = json_decode($tags, true);
    $where = [];
    $params = [];
    $types = '';
    if ($searchText) {
        $where[] = '(title LIKE ? OR description LIKE ?)';
        $params[] = "%$searchText%";
        $params[] = "%$searchText%";
        $types .= 'ss';
    }
    if ($fileType) {
        $where[] = 'file_type = ?';
        $params[] = $fileType;
        $types .= 's';
    }
    if ($folder) {
        $where[] = 'folder = ?';
        $params[] = $folder;
        $types .= 's';
    }
    if ($startDate) {
        $where[] = 'created_at >= ?';
        $params[] = $startDate . ' 00:00:00';
        $types .= 's';
    }
    if ($endDate) {
        $where[] = 'created_at <= ?';
        $params[] = $endDate . ' 23:59:59';
        $types .= 's';
    }
    if ($uploadedBy) {
        $where[] = 'uploaded_by IN (SELECT id FROM users WHERE name LIKE ? OR email LIKE ?)';
        $params[] = "%$uploadedBy%";
        $params[] = "%$uploadedBy%";
        $types .= 'ss';
    }
    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $where[] = 'FIND_IN_SET(?, tags)';
            $params[] = $tag;
            $types .= 's';
        }
    }
    $sql = 'SELECT d.*, u.name as uploader_name, u.email as uploader_email FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY d.id DESC LIMIT 100';
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $docs = [];
    while ($row = $result->fetch_assoc()) {
        $row['file_size'] = isset($row['file_path']) && file_exists($row['file_path']) ? round(filesize($row['file_path'])/1048576,2).' Mb' : '';
        $docs[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($docs);
    exit;
}
?>