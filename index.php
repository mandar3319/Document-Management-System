<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DocuManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    .hero {
      padding: 80px 0;
    }
    .section-title {
      margin-bottom: 50px;
    }
    .pricing-card-highlight {
      border: 2px solid #198754;
    }
    .footer-links a {
      color: #fff;
      text-decoration: none;
        margin: 0 10px;
    }
 
    .pricing-card {
  transition: all 0.3s ease-in-out;
  border-radius: 1rem;
  border: 2px solid transparent;
}

.pricing-card:hover {
  border: 2px solid #198754;
  background-color: #e6f4ea;
  transform: scale(1.03); 
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.pricing-card:hover .btn {
  background-color: #198754;
  color: #fff;
  border: none;
}

  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">DocuManager</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav me-3">
        <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="#">About</a></li>
      </ul>
      <a class="btn btn-outline-success me-2" href="./pages/domain.php">Login</a>
      <a class="btn btn-success" href="./pages/signup.php">Get Started</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero bg-light text-center">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-md-start">
        <h1 class="display-5 fw-bold">Secure, Smart, Scalable Document Management for Your Business</h1>
        <p class="lead">Organize, track, and collaborate on documents easily.</p>
        <a href="pages/signup.php" class="btn btn-success">Get Started</a>
      </div>
      <div class="col-md-6">
        <img src="images/hero.png" class="img-fluid" alt="Hero Image">
      </div>
    </div>
  </div>
</section>

<!-- How it Works -->
<section class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="mb-5 fw-bold">How It Works</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <div class="mb-3 text-success">
                <i class="bi bi-file-earmark-plus" style="font-size: 2rem;"></i>
              </div>
              <h5 class="card-title fw-semibold">Create Documents</h5>
              <p class="card-text">Easily create or upload documents in one secure location.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <div class="mb-3 text-success">
                <i class="bi bi-people" style="font-size: 2rem;"></i>
              </div>
              <h5 class="card-title fw-semibold">Collaborate Securely</h5>
              <p class="card-text">Invite team members and manage permissions effortlessly.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <div class="mb-3 text-success">
                <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
              </div>
              <h5 class="card-title fw-semibold">Track Everything</h5>
              <p class="card-text">Stay updated with document history and activity logs.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  

<!-- Powerful Features -->
<section class="py-5">
    <div class="container text-center">
      <h2 class="mb-5 fw-bold">Powerful Features</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="p-4 border rounded shadow-sm h-100">
            <i class="bi bi-search text-success mb-3" style="font-size: 2rem;"></i>
            <h5 class="fw-semibold">Smart Search</h5>
            <p>Quickly find documents using intelligent filtering and tags.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-4 border rounded shadow-sm h-100">
            <i class="bi bi-clock-history text-success mb-3" style="font-size: 2rem;"></i>
            <h5 class="fw-semibold">Version Control</h5>
            <p>Access previous versions and maintain full document history.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-4 border rounded shadow-sm h-100">
            <i class="bi bi-lock text-success mb-3" style="font-size: 2rem;"></i>
            <h5 class="fw-semibold">Access Control</h5>
            <p>Control who sees what with role-based permissions.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  

<!-- See It In Action -->
<section class="py-5 bg-light text-center">
    <div class="container">
      <h2 class="mb-5 fw-bold">See It In Action</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <img src="images/dashboard.png" class="card-img-top" alt="Demo 1">
            <div class="card-body">
              <p class="card-text">Document upload and management interface in action.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <img src="images/smart-organizer.png" class="card-img-top" alt="Demo 2">
            <div class="card-body">
              <p class="card-text">Collaborative workspace and team interactions.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <img src="images/doc-viewer.png" class="card-img-top" alt="Demo 3">
            <div class="card-body">
              <p class="card-text">Tracking activity logs and document analytics.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  

<!-- Pricing -->
<section class="bg-light py-5 text-center">
  <div class="container">
    <h2 class="section-title">Simple, Transparent Pricing</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card pricing-card shadow-sm border-0 h-100">
              <div class="card-body text-center">
                <h5 class="card-title">Basic</h5>
                <h6 class="card-price">$10 <small>/month</small></h6>
                <ul class="list-unstyled mt-3 mb-4">
                  <li>5 GB Storage</li>
                  <li>Email Support</li>
                </ul>
                <a href="#" class="btn btn-outline-success">Choose Plan</a>
              </div>
            </div>
          </div>
          
          <!-- Active/Featured -->
          <div class="col-md-4">
            <div class="card pricing-card active-plan shadow-lg border-0 h-100">
              <div class="card-body text-center">
                <h5 class="card-title">Standard</h5>
                <h6 class="card-price">$25 <small>/month</small></h6>
                <ul class="list-unstyled mt-3 mb-4">
                  <li>20 GB Storage</li>
                  <li>Priority Support</li>
                </ul>
                <a href="#" class="btn btn-outline-success">Get Started</a>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="card pricing-card shadow-sm border-0 h-100">
              <div class="card-body text-center">
                <h5 class="card-title">Basic</h5>
                <h6 class="card-price">$10 <small>/month</small></h6>
                <ul class="list-unstyled mt-3 mb-4">
                  <li>5 GB Storage</li>
                  <li>Email Support</li>
                </ul>
                <a href="#" class="btn btn-outline-success">Choose Plan</a>
              </div>
            </div>
          </div>
         
          
    </div>
  </div>
</section>



<!-- Footer -->
<footer class="bg-dark text-white py-4">
  <div class="container text-center">
    <p class="mb-2">&copy; 2025 DocuManager. All rights reserved.</p>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms</a>
      <a href="#">Support</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
