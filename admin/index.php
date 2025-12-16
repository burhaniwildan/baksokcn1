<?php
require 'conn.php';
session_start();

/* ================= CEK SUDAH LOGIN ================= */
if (isset($_SESSION['user']) && $_SESSION['user']['role_id'] == 1) {
  header('location:dashboard.php');
  exit;
}

/* ================= PROSES LOGIN ADMIN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = md5($_POST['password']); // SESUAI DB

  $query = mysqli_query($conn, "
        SELECT users.*
        FROM users
        JOIN role ON users.role_id = role.id
        WHERE users.username = '$username'
        AND role.nama_role = 'admin'
        LIMIT 1
    ");

  if (mysqli_num_rows($query) === 1) {
    $user = mysqli_fetch_assoc($query);

    if ($password === $user['password']) {
      $_SESSION['user'] = $user;
      header('location:dashboard.php');
      exit;
    } else {
      $_SESSION['error'] = "Username atau Password salah";
    }
  } else {
    $_SESSION['error'] = "Username atau Password salah";
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<style>
  body {
    background: linear-gradient(rgba(0, 0, 0, .7), rgba(0, 0, 0, .7)),
      url('../assets/welcome.jpg') no-repeat center center fixed;
    background-size: cover;
  }
</style>

<body>

  <!-- ERROR MODAL -->
  <div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Login Gagal</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php
          if (isset($_SESSION['error'])) {
            echo $_SESSION['error'];
            unset($_SESSION['error']);
          }
          ?>
        </div>
      </div>
    </div>
  </div>

  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="max-width:400px;width:100%">
      <h4 class="text-center mb-3">Masuk Dashboard Admin</h4>

      <form method="post">
        <div class="mb-3">
          <label>Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Login</button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      let modalBody = document.querySelector('#errorModal .modal-body').innerText.trim();
      if (modalBody !== "") {
        new bootstrap.Modal(document.getElementById('errorModal')).show();
      }
    });
  </script>
</body>

</html>