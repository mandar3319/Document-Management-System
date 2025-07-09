<?php

include "../config/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_check']) && $_POST['ajax_check'] === '1') {
  header('Content-Type: application/json');

  $domain = strtolower(trim($_POST['domain']));
  $stmt = $conn->prepare("SELECT c_id FROM company WHERE c_domain = ?");
  $stmt->bind_param("s", $domain);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
      echo json_encode(['exists' => true, 'c_id' => $row['c_id']]);
  } else {
      echo json_encode(['exists' => false]);
  }

  $stmt->close();
  $conn->close();
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>DMS</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');

body {
    background-color: aliceblue;
}

.login-page {
    width: 360px;
    padding: 8% 0 0;
    margin: auto;
}

.form {
    position: relative;
    z-index: 1;
    background: #FFFFFF;
    border-radius: 25px;
    max-width: 360px;
    margin: 0 auto 100px;
    padding: 45px;
    text-align: center;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
}

.form input {
    font-family: "Roboto", sans-serif;
    outline: 0;
    background: #f2f2f2;
    width: 100%;
    border: 0;
    margin: 0 0 15px;
    padding: 15px;
    box-sizing: border-box;
    font-size: 14px;
}

.form .btn {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}

.form .message {
    margin: 15px 0 0;
    color: #b3b3b3;
    font-size: 12px;
}

.form .message a {
    text-decoration: none;
}

.form .register-form {
    display: none;
}

body {
    font-family: "Outfit", sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.mannuel {
    display: flex;
    align-items: center;
}
</style>

<body>
    <div class="login-page">
        <div class="form">

            <form id="domainForm" role="form" class="text-start">
                <h1 class="text-center">DMS</h1>
                <h6 class="text-center">Enter your DMS Domain to login.</h6>
                <div class="input-group input-group-outline">
                    <input type="text" id="domainInput" class="form-control w-50"
                        placeholder="Enter domain (e.g., mycompany)">
                </div>
                <div class="text-center mt-3">
                    <button type="submit" class="btn bg-gradient-info w-50">Login</button>
                </div>
                <p class="mt-3 text-sm text-center">
                    Don't have an account?
                    <a href="signup.php" class="text-info text-gradient font-weight-bold">Sign up</a>
                </p>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('domainForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const domain = document.getElementById('domainInput').value.trim();

        if (!domain) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Please enter a domain.',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }

        fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'ajax_check=1&domain=' + encodeURIComponent(domain)
            })
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    window.location.href = 'signin.php?c_id=' + data.c_id;
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Domain not found.',
                        showConfirmButton: false,
                        timer: 1000
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Request failed.',
                    showConfirmButton: false,
                    timer: 1000
                });
            });
    });
    </script>

</body>

</html>