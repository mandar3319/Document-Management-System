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
  <?php include "components/head.php"; ?>
 
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
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
      box-shadow: 0 10px 25px rgba(0,0,0,0.07);
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
  </style>
</head>

<body class="g-sidenav-show  bg-gray-100">

  <?php include "components/sidebar.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php include "components/navbar.php"; ?>
    <div class="container-fluid py-2">

<div class="row mb-4">
  <div class="col-md-6 d-flex flex-column justify-content-center">
    <h4>Files and Folders</h4>
  </div>
</div>

<div class="folder-grid" id="folderList">
    
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Create Folder</button>
        </div>
      </form>
    </div>
  </div>
</div>

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

</body>
</html>
