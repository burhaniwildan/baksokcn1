<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI LOGIN ================= */
if (!isset($_SESSION['user'])) {
  header('location:index.php');
  exit;
}

/* ================= FILTER ================= */
$start  = $_GET['start']  ?? date('Y-m-01');
$end    = $_GET['end']    ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

/* ================= TOTAL PENJUALAN ================= */
$q_penjualan = mysqli_query($conn, "
  SELECT SUM(total) AS total_penjualan
  FROM transaksi
  WHERE tanggal BETWEEN '$start' AND '$end'
");
$total_penjualan = mysqli_fetch_assoc($q_penjualan)['total_penjualan'] ?? 0;

/* ================= JUMLAH PRODUK TERJUAL ================= */
$q_produk = mysqli_query($conn, "
  SELECT SUM(jumlah) AS total
  FROM detail_transaksi
");
$jumlah_produk_terjual = mysqli_fetch_assoc($q_produk)['total'] ?? 0;

/* ================= TOTAL TRANSAKSI ================= */
$q_total_transaction = mysqli_query($conn, "
  SELECT COUNT(*) AS total
  FROM transaksi
  WHERE tanggal BETWEEN '$start' AND '$end'
");
$total_transaction = mysqli_fetch_assoc($q_total_transaction)['total'] ?? 0;

/* ================= TOTAL RESTOCK ASLI ================= */
$q_restock = mysqli_query($conn, "
  SELECT SUM(total_harga) AS total
  FROM restock
");
$total_restock = mysqli_fetch_assoc($q_restock)['total'] ?? 0;

/* ================= MODAL 30% ================= */
$modal_maksimal = $total_penjualan * 0.3;
$total_pengeluaran = min($total_restock, $modal_maksimal);

/* ================= LABA BERSIH (70%) ================= */
$laba_bersih = $total_penjualan - $total_pengeluaran;

/* ================= SIMPAN SESSION (EXPORT) ================= */
$_SESSION['laporan_data'] = [
  'jumlah_produk_terjual' => $jumlah_produk_terjual,
  'total_transaction'    => $total_transaction,
  'total_penjualan'      => $total_penjualan,
  'total_pengeluaran'    => $total_pengeluaran,
  'laba_bersih'          => $laba_bersih,
  'start'                => $start,
  'end'                  => $end
];

/* ================= FILTER SEARCH ================= */
$search_filter = '';
if (!empty($search)) {
  $safe = mysqli_real_escape_string($conn, $search);
  $search_filter = "AND (t.id LIKE '%$safe%' OR t.tanggal LIKE '%$safe%')";
}

/* ================= RIWAYAT TRANSAKSI ================= */
$q_transaksi = mysqli_query($conn, "
  SELECT t.id, t.tanggal, t.total, t.pembayaran,
         COUNT(dt.id) AS jumlah_item
  FROM transaksi t
  LEFT JOIN detail_transaksi dt ON t.id = dt.id_transaksi
  WHERE t.tanggal BETWEEN '$start' AND '$end'
  $search_filter
  GROUP BY t.id
  ORDER BY t.tanggal DESC, t.id DESC
");

/* ================= LINE CHART ================= */
$day_labels = [];
$day_counts = [];

$q_chart = mysqli_query($conn, "
  SELECT tanggal, COUNT(*) AS jumlah
  FROM transaksi
  WHERE tanggal BETWEEN '$start' AND '$end'
  GROUP BY tanggal
  ORDER BY tanggal
");
while ($r = mysqli_fetch_assoc($q_chart)) {
  $day_labels[] = $r['tanggal'];
  $day_counts[] = (int)$r['jumlah'];
}

/* ================= PIE CHART ================= */
$kategori_labels = [];
$kategori_data   = [];

$q_kategori = mysqli_query($conn, "
  SELECT k.nama_kategori, SUM(dt.jumlah) AS total
  FROM detail_transaksi dt
  JOIN menu m ON dt.id_menu = m.id
  JOIN kategori k ON m.id_kategori = k.id
  GROUP BY k.id
  ORDER BY total DESC
");
while ($r = mysqli_fetch_assoc($q_kategori)) {
  $kategori_labels[] = $r['nama_kategori'];
  $kategori_data[]   = (int)$r['total'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Laporan Penjualan</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }

    .sidebar {
      position: fixed;
      height: 100%;
      width: 16%;
      background-color: rgb(34, 53, 71);
      padding: 25px 15px;
    }

    .sidebar .nav-link {
      color: #adb5bd;
      border-radius: 5px;
      padding: 8px 12px;
      margin-bottom: 5px;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: rgb(95, 168, 241);
      color: #fff;
    }

    .content {
      margin-left: 17%;
      padding: 40px;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row">

      <!-- SIDEBAR -->
      <div class="sidebar">
        <h4 class="text-white mb-4">Dashboard Admin</h4>
        <hr class="text-white">
        <nav class="nav flex-column">
          <a href="dashboard.php" class="nav-link">Dashboard</a>
          <a href="transaksi.php" class="nav-link">Transaksi</a>
          <a href="stok.php" class="nav-link">Manajemen Stok</a>
          <a href="laporan.php" class="nav-link active">Laporan Penjualan</a>
          <a href="logout.php" class="nav-link text-danger mt-auto">Keluar</a>
        </nav>
      </div>

      <!-- CONTENT -->
      <div class="content">
        <h3 class="mb-4">Laporan Penjualan</h3>

        <!-- FILTER -->
        <form method="get" class="row mb-4">
          <div class="col-auto">
            <label>Dari</label>
            <input type="date" name="start" class="form-control" value="<?= $start ?>">
          </div>
          <div class="col-auto">
            <label>Sampai</label>
            <input type="date" name="end" class="form-control" value="<?= $end ?>">
          </div>
          <div class="col-auto align-self-end">
            <button class="btn btn-primary">Terapkan</button>
          </div>
        </form>

        <!-- RINGKASAN -->
        <table class="table table-bordered">
          <tr>
            <th>Produk Terjual</th>
            <td><?= $jumlah_produk_terjual ?></td>
          </tr>
          <tr>
            <th>Total Transaksi</th>
            <td><?= $total_transaction ?></td>
          </tr>
          <tr>
            <th>Total Penjualan</th>
            <td>Rp <?= number_format($total_penjualan, 0, ',', '.') ?></td>
          </tr>
          <tr>
            <th>Modal Bahan Baku (30%)</th>
            <td>Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></td>
          </tr>
          <tr class="table-success">
            <th>Laba Bersih (70%)</th>
            <td><b>Rp <?= number_format($laba_bersih, 0, ',', '.') ?></b></td>
          </tr>
        </table>

        <a href="export_excel.php" class="btn btn-success mb-4">Export ke Excel</a>

        <!-- CHART -->
        <div class="row mb-5">
          <div class="col-md-8"><canvas id="lineChart"></canvas></div>
          <div class="col-md-4"><canvas id="pieChart"></canvas></div>
        </div>

        <!-- SEARCH -->
        <form method="get" class="row g-3 mb-3">
          <div class="col-auto">
            <input type="text" name="search" class="form-control"
              placeholder="Cari ID / tanggal"
              value="<?= htmlspecialchars($search) ?>">
          </div>
          <input type="hidden" name="start" value="<?= $start ?>">
          <input type="hidden" name="end" value="<?= $end ?>">
          <div class="col-auto">
            <button class="btn btn-primary">Cari</button>
          </div>
        </form>

        <!-- RIWAYAT -->
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Item</th>
              <th>Pembayaran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($t = mysqli_fetch_assoc($q_transaksi)): ?>
              <tr>
                <td><?= $t['id'] ?></td>
                <td><?= $t['tanggal'] ?></td>
                <td>Rp <?= number_format($t['total'], 0, ',', '.') ?></td>
                <td><?= $t['jumlah_item'] ?></td>
                <td>Rp <?= number_format($t['pembayaran'], 0, ',', '.') ?></td>
                <td>
                  <button class="btn btn-secondary btn-sm view-detail"
                    data-id="<?= $t['id'] ?>">Detail</button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL -->
  <div class="modal fade" id="detailModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5>Detail Transaksi</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="detailContent">Memuat...</div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    new Chart(document.getElementById('lineChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode($day_labels) ?>,
        datasets: [{
          label: 'Transaksi',
          data: <?= json_encode($day_counts) ?>,
          borderWidth: 2
        }]
      }
    });
    new Chart(document.getElementById('pieChart'), {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($kategori_labels) ?>,
        datasets: [{
          data: <?= json_encode($kategori_data) ?>
        }]
      }
    });
    $('.view-detail').click(function() {
      let id = $(this).data('id');
      $('#detailContent').html('Memuat...');
      $.get('get_detail.php', {
        id: id
      }, function(res) {
        $('#detailContent').html(res);
        new bootstrap.Modal('#detailModal').show();
      });
    });
  </script>
</body>

</html>