<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI ================= */
if (!isset($_SESSION['user'])) {
  header('location:index.php');
  exit;
}

/* ================= FILTER ================= */
$start  = $_GET['start'] ?? date('Y-m-01');
$end    = $_GET['end']   ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

/* ================= RINGKASAN ================= */
$q_penjualan = mysqli_query($conn, "
  SELECT SUM(t.total) AS total
  FROM transaksi t
  JOIN orders o ON o.id_transaksi = t.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
");
$total_penjualan = mysqli_fetch_assoc($q_penjualan)['total'] ?? 0;

$q_produk = mysqli_query($conn, "
  SELECT SUM(dt.jumlah) AS total
  FROM detail_transaksi dt
  JOIN transaksi t ON dt.id_transaksi = t.id
  JOIN orders o ON o.id_transaksi = t.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
");
$jumlah_produk = mysqli_fetch_assoc($q_produk)['total'] ?? 0;

$q_trans_count = mysqli_query($conn, "
  SELECT COUNT(*) AS total
  FROM orders o
  JOIN transaksi t ON o.id_transaksi = t.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
");
$total_transaksi = mysqli_fetch_assoc($q_trans_count)['total'] ?? 0;

/* ================= CHART DATA ================= */
$day_labels = $day_counts = [];
$q_chart = mysqli_query($conn, "
  SELECT DATE(t.tanggal) AS hari, COUNT(*) jumlah
  FROM transaksi t
  JOIN orders o ON o.id_transaksi = t.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
  GROUP BY hari
  ORDER BY hari
");
while ($r = mysqli_fetch_assoc($q_chart)) {
  $day_labels[] = $r['hari'];
  $day_counts[] = (int)$r['jumlah'];
}

$kategori_labels = $kategori_data = [];
$q_kategori = mysqli_query($conn, "
  SELECT k.nama_kategori, SUM(dt.jumlah) total
  FROM detail_transaksi dt
  JOIN transaksi t ON dt.id_transaksi = t.id
  JOIN orders o ON o.id_transaksi = t.id
  JOIN menu m ON dt.id_menu = m.id
  JOIN kategori k ON m.id_kategori = k.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
  GROUP BY k.id
");
while ($r = mysqli_fetch_assoc($q_kategori)) {
  $kategori_labels[] = $r['nama_kategori'];
  $kategori_data[] = (int)$r['total'];
}

/* ================= SEARCH ================= */
$search_filter = '';
if ($search) {
  $safe = mysqli_real_escape_string($conn, $search);
  $search_filter = "AND (t.id LIKE '%$safe%' OR t.tanggal LIKE '%$safe%')";
}

/* ================= RIWAYAT ================= */
$q_riwayat = mysqli_query($conn, "
  SELECT t.id, t.tanggal, t.total, COUNT(dt.id) item
  FROM transaksi t
  JOIN orders o ON o.id_transaksi = t.id
  LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id
  WHERE o.status = 'received'
    AND DATE(t.tanggal) BETWEEN '$start' AND '$end'
    $search_filter
  GROUP BY t.id
  ORDER BY t.tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Laporan Penjualan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
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

    .main {
      margin-left: 16%;
      padding: 20px
    }

    .card {
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15)
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <h4 class="text-white">Dashboard Admin</h4>
    <hr class="text-white">
    <a href="dashboard.php">Dashboard</a>
    <a href="transaksi.php">Transaksi</a>
    <a href="pesanan.php">Pesanan</a>
    <a href="stok.php">Stok</a>
    <a href="metode_pembayaran.php">Metode Pembayaran</a>
    <a class="active" href="laporan.php">Laporan</a>
    <a href="logout.php" class="text-danger mt-4">Logout</a>
  </div>


  <div class="main">
    <h3>Laporan Penjualan</h3>

    <form class="row g-2 mb-3">
      <div class="col-auto"><input type="date" name="start" value="<?= $start ?>" class="form-control"></div>
      <div class="col-auto"><input type="date" name="end" value="<?= $end ?>" class="form-control"></div>
      <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
    </form>

    <table class="table table-bordered mb-4">
      <tr>
        <th>Produk Terjual</th>
        <td><?= $jumlah_produk ?></td>
      </tr>
      <tr>
        <th>Total Transaksi</th>
        <td><?= $total_transaksi ?></td>
      </tr>
      <tr>
        <th>Total Penjualan</th>
        <td>Rp <?= number_format($total_penjualan, 0, ',', '.') ?></td>
      </tr>
    </table>

    <div class="row mb-4">
      <div class="col-md-8">
        <div class="card p-3">
          <h6>Grafik Transaksi</h6>
          <div style="height:350px"><canvas id="lineChart"></canvas></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <h6>Kategori Produk</h6>
          <div style="height:350px"><canvas id="pieChart"></canvas></div>
        </div>
      </div>
    </div>

    <h5>Riwayat Transaksi</h5>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tanggal</th>
          <th>Total</th>
          <th>Item</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($r = mysqli_fetch_assoc($q_riwayat)): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['tanggal'] ?></td>
            <td>Rp <?= number_format($r['total'], 0, ',', '.') ?></td>
            <td><?= $r['item'] ?></td>
            <td><button class="btn btn-sm btn-secondary view-detail" data-id="<?= $r['id'] ?>">Detail</button></td>
          </tr>
          <tr class="detail-row d-none" id="detail-<?= $r['id'] ?>">
            <td colspan="5">
              <div class="detail-content">Memuat...</div>
            </td>
          </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    new Chart(lineChart, {
      type: 'line',
      data: {
        labels: <?= json_encode($day_labels) ?>,
        datasets: [{
          label: 'Transaksi',
          data: <?= json_encode($day_counts) ?>,
          borderWidth: 2,
          tension: .3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });
    new Chart(pieChart, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($kategori_labels) ?>,
        datasets: [{
          data: <?= json_encode($kategori_data) ?>
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });

    $('.view-detail').click(function() {
      let id = $(this).data('id');
      let row = $('#detail-' + id);
      if (!row.hasClass('d-none')) {
        row.addClass('d-none');
        return;
      }
      $('.detail-row').addClass('d-none');
      row.removeClass('d-none');
      $.get('get_detail.php', {
        id: id
      }, res => row.find('.detail-content').html(res));
    });
  </script>
</body>

</html>