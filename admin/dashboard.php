<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI LOGIN ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
  header('location:index.php');
  exit;
}

$admin = $_SESSION['user'];

/* ================= CARD INFO ================= */
$q_income = mysqli_query($conn, "SELECT SUM(total) AS income FROM transaksi");
$total_income = mysqli_fetch_assoc($q_income)['income'] ?? 0;

$q_sold = mysqli_query($conn, "SELECT SUM(jumlah) AS total FROM detail_transaksi");
$total_sold = mysqli_fetch_assoc($q_sold)['total'] ?? 0;

$q_trans = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
$total_transaction = mysqli_fetch_assoc($q_trans)['total'] ?? 0;

/* ================= RESTOK ================= */
$min_stok = isset($_GET['min_stok']) ? (int)$_GET['min_stok'] : 10;

$q_restock = mysqli_query($conn, "
    SELECT 
        menu.nama_menu, 
        kategori.nama_kategori, 
        menu.stok
    FROM menu
    LEFT JOIN kategori ON menu.id_kategori = kategori.id
    WHERE menu.stok < $min_stok
");

$produk_restock = [];
while ($row = mysqli_fetch_assoc($q_restock)) {
  $produk_restock[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <div class="container-fluid">
    <div class="row">

      <!-- SIDEBAR -->
      <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
        <h4>Dashboard Admin</h4>
        <hr>
        <a href="dashboard.php" class="text-white d-block mb-2">Dashboard</a>
        <a href="transaksi.php" class="text-white d-block mb-2">Transaksi</a>
        <a href="stok.php" class="text-white d-block mb-2">Stok</a>
        <a href="laporan.php" class="text-white d-block mb-2">Laporan</a>
        <a href="logout.php" class="text-danger d-block mt-4">Logout</a>
      </div>

      <!-- CONTENT -->
      <div class="col-md-10 p-4">

        <div class="alert alert-primary">
          Selamat datang, <strong><?= htmlspecialchars($admin['nama']) ?></strong>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="card text-white bg-success">
              <div class="card-body">
                <h5>Rp <?= number_format($total_income, 0, ',', '.') ?></h5>
                <p>Total Pendapatan</p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card text-white bg-primary">
              <div class="card-body">
                <h5><?= number_format($total_sold) ?></h5>
                <p>Item Terjual</p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card text-white bg-warning">
              <div class="card-body">
                <h5><?= number_format($total_transaction) ?></h5>
                <p>Total Transaksi</p>
              </div>
            </div>
          </div>
        </div>

        <hr>

        <h4>Produk Butuh Restok</h4>
        <form class="row g-2 mb-3">
          <div class="col-auto">
            <input type="number" name="min_stok" class="form-control"
              value="<?= $min_stok ?>" min="1">
          </div>
          <div class="col-auto">
            <button class="btn btn-primary">Terapkan</button>
          </div>
        </form>

        <table class="table table-bordered">
          <tr>
            <th>Nama Menu</th>
            <th>Kategori</th>
            <th>Stok</th>
          </tr>

          <?php if ($produk_restock): ?>
            <?php foreach ($produk_restock as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['nama_menu']) ?></td>
                <td><?= htmlspecialchars($p['nama_kategori']) ?></td>
                <td><?= $p['stok'] ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="text-center">Aman</td>
            </tr>
          <?php endif; ?>
        </table>

      </div>
    </div>
  </div>
</body>

</html>