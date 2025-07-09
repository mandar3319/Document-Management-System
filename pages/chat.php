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
  #searchUserInput {
    border: 1px solid #ced4da; /* Default Bootstrap border color */
    border-radius: 0.25rem; /* Default Bootstrap border radius */
    padding: 0.375rem 0.75rem; /* Default Bootstrap padding */
  }

  #searchUserInput:focus {
    border-color: #80bdff; /* Highlight border color on focus */
    outline: none; /* Remove default outline */
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); /* Add focus shadow */
  }
</style>
</head>

<body class="g-sidenav-show  bg-gray-100">

  <?php include "components/sidebar.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php include "components/navbar.php"; ?>
    <div class="container-fluid py-2">
  <div class="row">

    <!-- Left Chat List Panel -->
    <div class="col-md-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Chats</h6>
          <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#newChatModal">+ New Chat</button>
        </div>
        <div class="card-body p-2 overflow-auto" style="max-height: 80vh;">
          <div class="list-group">
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=1" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Vivek Jadhav</div>
                <small class="text-muted">Online</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
              <img src="https://i.pravatar.cc/40?img=2" class="rounded-circle me-2" width="40" />
              <div>
                <div class="fw-bold">Baburao Apate</div>
                <small class="text-muted">Offline</small>
              </div>
            </a>
            <!-- More users -->
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Chat Panel -->
    <div class="col-md-6">
      <div class="card h-100 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <img src="https://i.pravatar.cc/40?img=1" class="rounded-circle me-2" width="40" />
            <strong>Vivek Jadhav</strong>
          </div>
          <div>
            <i class="fas fa-phone-alt me-3 text-secondary"></i>
            <i class="fas fa-video text-secondary"></i>
          </div>
        </div>
        <div class="card-body" style="height: 65vh; overflow-y: auto;">
          <!-- Chat Messages -->
          <div class="d-flex flex-column gap-3 px-3">
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              Hi! How's the project going?
              <div class="text-muted small">10:02 AM</div>
            </div>
            <div class="align-self-end bg-info text-white p-2 rounded-3 shadow-sm">
              It's going well! I've completed the initial designs.
              <div class="text-white-50 small">10:04 AM</div>
            </div>      
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              That’s great! Could you share the files with me?
              <div class="text-muted small">10:05 AM</div>
            </div>
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              That’s great! Could you share the files with me?
              <div class="text-muted small">10:05 AM</div>
            </div>
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              That’s great! Could you share the files with me?
              <div class="text-muted small">10:05 AM</div>
            </div>
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              That’s great! Could you share the files with me?
              <div class="text-muted small">10:05 AM</div>
            </div>
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              That’s great! Could you share the files with me?
              <div class="text-muted small">10:05 AM</div>
            </div>
            <div class="align-self-end bg-info text-white p-2 rounded-3 shadow-sm">
              Sure! Here’s the latest version.
              <div class="text-white-50 small">10:06 AM</div>
              <div class="mt-2">
                <a href="#" class="text-white text-decoration-underline">Project_Design_X12.zip</a>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-light border-0">
          <form class="d-flex">
            <input type="text" class="form-control me-2 rounded-pill" placeholder="Type your message..." />
            <button class="btn btn-info rounded-pill" type="submit"><i class="fas fa-paper-plane"></i></button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right User Info Panel -->
    <div class="col-md-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <img src="https://i.pravatar.cc/80?img=1" class="rounded-circle mb-3" width="80" />
          <h6>Vivek Jadhav</h6>
          <p class="text-muted mb-1">UI/UX Designer</p>
          <p class="text-muted small">vivek@gmail.com<br>+91 9876543210</p>
        </div>
        <hr />
        <div class="px-3">
          <h6 class="text-muted">Shared Files</h6>
          <ul class="list-group mb-3">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              UI_Kit.pdf
              <i class="fas fa-download text-info"></i>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Design.sketch
              <i class="fas fa-download text-info"></i>
            </li>
          </ul>

          <h6 class="text-muted">Shared Media</h6>
          <div class="d-flex gap-2" style="overflow-y: auto; max-height: 200px;">
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
            <img src="https://i.pravatar.cc/80?img=1" class="rounded-2" />
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include "components/footer.php"; ?>
</div>

  </main>

  <!-- New Chat Modal -->
  <div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newChatModalLabel">Start New Chat</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Search Bar -->
          <input type="text" id="searchUserInput" class="form-control mb-3" placeholder="Search by username..." />

          <!-- User List -->
          <!-- <ul class="list-group" id="userList">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Vivek Jadhav
              <button class="btn btn-sm btn-info start-chat" data-user="Vivek Jadhav">Chat</button>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Baburao Apate
              <button class="btn btn-sm btn-info start-chat" data-user="Baburao Apate">Chat</button>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              John Doe
              <button class="btn btn-sm btn-info start-chat" data-user="John Doe">Chat</button>
            </li>
            Add more users dynamically
          </ul> -->
        </div>
      </div>
    </div>
  </div>

  <!-- Core JS Files -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const searchInput = document.getElementById('searchUserInput');
      const userList = document.getElementById('userList');
      const chatHeader = document.querySelector('.card-header .d-flex.align-items-center strong');
      const chatMessages = document.querySelector('.card-body .d-flex.flex-column');

      // Filter users based on search input
      searchInput.addEventListener('input', function () {
        const filter = searchInput.value.toLowerCase();
        const users = userList.querySelectorAll('.list-group-item');
        users.forEach(user => {
          const userName = user.textContent.toLowerCase();
          if (userName.includes(filter)) {
            user.style.display = '';
          } else {
            user.style.display = 'none';
          }
        });
      });

      // Handle starting a new chat
      userList.addEventListener('click', function (e) {
        if (e.target.classList.contains('start-chat')) {
          const userName = e.target.getAttribute('data-user');

          // Update chat header
          chatHeader.textContent = userName;

          // Clear previous messages and start a new chat
          chatMessages.innerHTML = `
            <div class="align-self-start bg-light p-2 rounded-3 shadow-sm">
              You started a new chat with ${userName}.
              <div class="text-muted small">${new Date().toLocaleTimeString()}</div>
            </div>
          `;

          // Close the modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('newChatModal'));
          modal.hide();
        }
      });
    });
  </script>

</body>
</html>
