<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI LOGIN ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
  header('location:index.php');
  exit;
}

$admin = $_SESSION['user'];

// Ensure orders table exists before aggregations
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_pembeli INT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ================= CARD INFO ================= */
// Only consider transactions that have been confirmed received by buyers
$q_income = mysqli_query($conn, "SELECT SUM(t.total) AS income FROM transaksi t JOIN orders o ON o.id_transaksi = t.id WHERE o.status = 'received'");
$total_income = mysqli_fetch_assoc($q_income)['income'] ?? 0;

$q_sold = mysqli_query($conn, "SELECT SUM(dt.jumlah) AS total FROM detail_transaksi dt JOIN orders o ON o.id_transaksi = dt.id_transaksi WHERE o.status = 'received'");
$total_sold = mysqli_fetch_assoc($q_sold)['total'] ?? 0;

$q_trans = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'received'");
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
  <!-- META -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin</title>

  <!-- BOOTSTRAP -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- GOOGLE ICON -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- CSS DASHBOARD (VERSI LAMA - DIPERTAHANKAN) -->
  <style>
    body {
      background: #f1f3f5;
      font-family: 'Segoe UI', sans-serif;
    }

    /* SIDEBAR */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 16.666667%;
      height: 100%;
      background: rgb(34, 53, 71);
      padding: 25px 15px;
    }

    .sidebar h4,
    .sidebar hr {
      color: #ffffff;
    }

    .sidebar a {
      color: #adb5bd;
      text-decoration: none;
      display: block;
      padding: 8px 12px;
      border-radius: 5px;
      margin-bottom: 6px;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: rgb(95, 168, 241);
      color: #ffffff;
    }

    /* KONTEN UTAMA */
    .main-content {
      margin-left: 16.666667%;
      padding: 1.5rem;
    }

    /* WELCOME BANNER */
    .welcome-banner {
      background: #0d6efd;
      color: #ffffff;
      padding: 1.5rem;
      border-radius: .5rem;
      margin-bottom: 1.5rem;
      box-shadow: 5px 5px 20px #aaa;
    }

    /* CARD */
    .card {
      border-radius: .5rem;
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    }

    .card-footer {
      background: rgba(0, 0, 0, .05);
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
      }

      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>


<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <h4 class="text-white">Dashboard Admin</h4>
    <hr class="text-white">
    <a class="active" href="dashboard.php">Dashboard</a>
    <a href="transaksi.php">Transaksi</a>
    <a href="pesanan.php">Pesanan</a>
    <a href="stok.php">Stok</a>
    <a href="metode_pembayaran.php">Metode Pembayaran</a>
    <a href="laporan.php">Laporan</a>
    <a href="logout.php" class="text-danger mt-4">Logout</a>
  </div>

  <div class="main-content">

    <!-- WELCOME -->
    <div class="welcome-banner mb-4">
      <h5>Selamat datang kembali, <strong><?= htmlspecialchars($admin['nama']) ?></strong></h5>
      <p class="mb-0">Kelola sistem dengan cepat dan mudah.</p>
    </div>

    <!-- CARD INFO + TREND -->
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card text-white bg-success">
          <div class="card-body d-flex justify-content-between">
            <div>
              <h5>Rp <?= number_format($total_income, 0, ',', '.') ?></h5>
              <p>Total Pendapatan</p>
            </div>
            <span class="material-icons">payments</span>
          </div>
          <div class="card-footer d-flex justify-content-between">
            <small>+10%</small><small>Hari Ini</small>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-white bg-primary">
          <div class="card-body d-flex justify-content-between">
            <div>
              <h5><?= number_format($total_sold) ?></h5>
              <p>Item Terjual</p>
            </div>
            <span class="material-icons">local_shipping</span>
          </div>
          <div class="card-footer d-flex justify-content-between">
            <small>+1%</small><small>Hari Ini</small>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-white bg-warning">
          <div class="card-body d-flex justify-content-between">
            <div>
              <h5><?= number_format($total_transaction) ?></h5>
              <p>Total Transaksi</p>
            </div>
            <span class="material-icons">receipt</span>
          </div>
          <div class="card-footer d-flex justify-content-between">
            <small>+4%</small><small>Hari Ini</small>
          </div>
        </div>
      </div>
    </div>

    <!-- QUICK ACTION -->
    <div class="row g-3 mb-5">
      <div class="col-md-6">
        <div class="card p-4">
          <h5>Lihat Laporan</h5>
          <p class="text-muted">Monitor penjualan dan performa.</p>
          <a href="laporan.php" class="btn btn-primary">Laporan</a>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-4">
          <h5>Mulai Transaksi</h5>
          <p class="text-muted">Masuk ke mode kasir.</p>
          <a href="transaksi.php" class="btn btn-primary">Mulai</a>
        </div>
      </div>
    </div>

    <!-- GRAFIK TRANSAKSI -->
    <div class="card p-4 mb-5">
      <h5 class="fw-bold mb-3">Grafik Transaksi Mingguan</h5>
      <canvas id="dailyChart" height="120"></canvas>
    </div>

    <!-- RESTOK -->
    <div class="card p-4">
      <h4 class="mb-3">Produk Butuh Restok</h4>

      <form class="row g-2 mb-3">
        <div class="col-auto">
          <input type="number" name="min_stok" class="form-control" value="<?= $min_stok ?>" min="1">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Terapkan</button>
        </div>
      </form>

      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Nama Menu</th>
            <th>Kategori</th>
            <th>Stok</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($produk_restock): foreach ($produk_restock as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['nama_menu']) ?></td>
                <td><?= htmlspecialchars($p['nama_kategori']) ?></td>
                <td><?= $p['stok'] ?></td>
              </tr>
            <?php endforeach;
          else: ?>
            <tr>
              <td colspan="3" class="text-center text-muted">Aman</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <a href="stok.php" class="btn btn-primary fw-bold">+ Restok Sekarang</a>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('dailyChart');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        datasets: [{
            label: 'Minggu Ini',
            data: [12, 19, 3, 5, 2, 3, 7],
            borderWidth: 2
          },
          {
            label: 'Minggu Lalu',
            data: [8, 14, 5, 2, 3, 7, 4],
            borderWidth: 2
          }
        ]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>

</body>

</html>