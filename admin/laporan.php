<?php
require 'conn.php';
session_start();

if (!isset($_SESSION["user"])) {
    header('location:index.php');
    exit;
}

// Ambil filter tanggal dan pencarian dari GET
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Query laporan penjualan berdasarkan filter tanggal
$q_penjualan = mysqli_query($conn, "
  SELECT SUM(total) AS total_penjualan
  FROM transaksi
  WHERE tanggal BETWEEN '$start' AND '$end'
");

// Filter tambahan untuk pencarian transaksi
$search_filter = '';
if (!empty($search)) {
  $safe_search = mysqli_real_escape_string($conn, $search);
  $search_filter = " AND (t.id LIKE '%$safe_search%' OR t.tanggal LIKE '%$safe_search%')";
}

// Riwayat transaksi 10 terbaru (dengan filter pencarian)
$q_transaksi = mysqli_query($conn, "
SELECT t.id, t.tanggal, t.total, t.pembayaran, COUNT(dt.id) AS jumlah_item
FROM transaksi t
LEFT JOIN detail_transaksi dt ON t.id = dt.id_transaksi
WHERE t.tanggal BETWEEN '$start' AND '$end' $search_filter
GROUP BY t.id
ORDER BY t.tanggal DESC, t.id DESC
");

$q_total_transaction = mysqli_query($conn, "SELECT * FROM transaksi");
$q_jumlah_produk_terjual = mysqli_query($conn, "SELECT SUM(jumlah) AS total FROM detail_transaksi");
$q_total_pengeluaran = mysqli_query($conn, "SELECT SUM(total_harga) AS total FROM restock");

$total_penjualan = mysqli_fetch_assoc($q_penjualan)['total_penjualan'] ?? 0;
$jumlah_produk_terjual = mysqli_fetch_assoc($q_jumlah_produk_terjual)['total'] ?? 0;
$total_transaction = mysqli_num_rows($q_total_transaction);
$total_pengeluaran = mysqli_fetch_assoc($q_total_pengeluaran)['total'] ?? 0;

$total_kotor = $total_penjualan;
$pengeluaran = $total_pengeluaran;
$laba_bersih = $total_kotor - $pengeluaran;

$_SESSION['laporan_data'] = [
    'jumlah_produk_terjual' => $jumlah_produk_terjual,
    'total_transaction' => $total_transaction,
    'total_penjualan' => $total_penjualan,
    'total_pengeluaran' => $pengeluaran,
    'laba_bersih' => $laba_bersih,
    'start' => $start,
    'end' => $end
];

//query untuk line chart
$day_labels = [];
$day_counts = [];
$q_trans_per_day = mysqli_query($conn, "
    SELECT tanggal, COUNT(*) AS jumlah 
    FROM transaksi 
    WHERE tanggal BETWEEN '$start' AND '$end' 
    GROUP BY tanggal
    ORDER BY tanggal
");

while ($row = mysqli_fetch_assoc($q_trans_per_day)) {
    $day_labels[] = $row['tanggal'];
    $day_counts[] = (int)$row['jumlah'];
}


// query untuk pie chart
$kategori_labels = [];
$kategori_data = [];
$q_kategori = mysqli_query($conn, "
    SELECT kategori.nama_kategori, SUM(detail_transaksi.jumlah) AS total_terjual
    FROM detail_transaksi
    JOIN produk ON detail_transaksi.id_produk = produk.id
    JOIN kategori ON produk.id_kategori = kategori.id
    GROUP BY kategori.id
    ORDER BY total_terjual DESC
");
while ($row = mysqli_fetch_assoc($q_kategori)) {
    $kategori_labels[] = $row['nama_kategori'];
    $kategori_data[] = (int)$row['total_terjual'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Penjualan - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }
    .sidebar {
      position: fixed;
      height: 100%;
      background-color: rgb(34, 53, 71);
      padding: 25px 15px;
    }
    .sidebar .nav-link {
      color: #adb5bd;
      border-radius: 5px;
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
    <!-- Sidebar -->
    <div class="col-md-2 sidebar">
      <h4 class="text-white mb-4">Dashboard Dasha</h4>
      <hr style="color: white;">
      <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="transaksi.php" class="nav-link">Transaksi</a>
        <a href="stok.php" class="nav-link">Manajemen Stok</a>
        <a href="laporan.php" class="nav-link active">Laporan Penjualan</a>
        <a href="logout.php" class="nav-link text-danger mt-auto">Keluar</a>
      </nav>
    </div>

    <!-- Main content -->
    <div class="col-md-10 offset-md-2 content">
      <h3 class="mb-4">Laporan Penjualan</h3>

      <!-- Filter tanggal -->
      <form class="row mb-4" method="get">
        <div class="col-auto">
          <label for="start" class="form-label">Dari:</label>
          <input type="date" class="form-control" name="start" value="<?= $start ?>">
        </div>
        <div class="col-auto">
          <label for="end" class="form-label">Sampai:</label>
          <input type="date" class="form-control" name="end" value="<?= $end ?>">
        </div>
        <div class="col-auto align-self-end">
          <button type="submit" class="btn btn-primary">Terapkan</button>
        </div>
      </form>

      <!-- Ringkasan -->
      <table class="table table-bordered w-100">
        <tbody>
          <tr><th>Jumlah Produk Terjual</th><td class="text-end"><?=$jumlah_produk_terjual?></td></tr>
          <tr><th>Total Transaksi</th><td class="text-end"><?=$total_transaction?></td></tr>
          <tr><th>Total Penjualan Kotor</th><td class="text-end">Rp <?= number_format($total_kotor, 0, ',', '.') ?></td></tr>
          <tr><th>Total Pengeluaran</th><td class="text-end">Rp <?= number_format($pengeluaran, 0, ',', '.') ?></td></tr>
          <tr><th class="bg-success-subtle">Laba Bersih</th><td class="text-end fw-bold <?= $laba_bersih >= 0 ? 'text-success' : 'text-danger' ?>">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></td></tr>
        </tbody>
      </table>
      <div class="mt-4 mb-5 text-end">
        <a href="export_excel.php" class="btn btn-success">Export ke Excel</a>
      </div>

      <!-- grafik -->
        <div class="mt-5">
          <h4 class="mb-3">Statistik Visual</h4>
          <div class="row">
            <!-- line chart transaksi -->
            <div class="col-md-8 mb-4">
              <div class="card shadow-sm p-4" style="height: 400px;">
                <h5 class="fw-bold mb-3">Grafik Transaksi</h5>
                <canvas id="dailyChart" height="300"></canvas>
              </div>
            </div>

            <!-- pie chart kategori produk -->
            <div class="col-md-4 mb-4">
              <div class="card shadow-sm p-4" style="height: 400px;">
                <h5 class="fw-bold mb-3">Kategori Terlaris</h5>
                <canvas id="pieChart" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>

      <!-- Riwayat Transaksi -->
      <h4 class="mb-3">Riwayat Transaksi Terbaru</h4>
      <form method="get" class="row g-3 align-items-center mb-3">
        <div class="col-auto">
          <label for="search" class="col-form-label">Cari Transaksi:</label>
        </div>
        <div class="col-auto">
          <input type="text" class="form-control" name="search" id="search" placeholder="ID atau tanggal" value="<?= htmlspecialchars($search) ?>">
        </div>
        <input type="hidden" name="start" value="<?= $start ?>">
        <input type="hidden" name="end" value="<?= $end ?>">
          <button type="submit" class="btn btn-primary w-auto px-5">Cari</button>
      </form>
      <table class="table table-striped w-100">
        <thead class="table-light">
          <tr><th>ID</th><th>Tanggal</th><th>Total</th><th>Jumlah Item</th><th>Pembayaran</th></tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($q_transaksi)): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['tanggal'] ?></td>
            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
            <td><?= $row['jumlah_item'] ?></td>
            <td>Rp <?= number_format($row['pembayaran'], 0, ',', '.') ?></td>
             <td><button class="btn btn-secondary btn-sm view-detail" data-id="<?= $row['id'] ?>">Detail</button></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailModalLabel">Detail Transaksi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="detailContent">
            Memuat data...
          </div>
        </div>
      </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.view-detail').click(function() {
        var id = $(this).data('id');
        $('#detailContent').html('Memuat data...');

        $.ajax({
            url: 'get_detail.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#detailContent').html(response);
                var detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
                detailModal.show();
            },
            error: function() {
                $('#detailContent').html('Terjadi kesalahan saat memuat data.');
            }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Dummy data transaksi per bulan
    // const dailyLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    // const dailyData = [12, 19, 7, 15, 20, 10];

const ctx1 = document.getElementById('dailyChart').getContext('2d');
const dailyChart = new Chart(ctx1, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($day_labels); ?>, // label = tanggal
    datasets: [{
      label: 'Jumlah Transaksi',
      data: <?php echo json_encode($day_counts); ?>,
      backgroundColor: 'rgba(53, 92, 128, 0.7)',
      borderColor: 'rgba(53, 92, 128, 1)',
      borderWidth: 1,
      borderRadius: 4
    }]
  },
  options: {
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0
        }
      }
    }
  }
});


    // Dummy data pie chart kategori produk
    // const kategoriLabels = ['Makanan', 'Minuman', 'ATK', 'Lainnya'];
    // const kategoriData = [40, 25, 20, 15];

    const ctx2 = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode($kategori_labels); ?>,
        datasets: [{
          data: <?php echo json_encode($kategori_data); ?>,
          backgroundColor: [
            'rgba(255, 0, 55, 0.8)',
            'rgba(0, 255, 55, 0.8)',
            'rgba(236, 169, 0, 0.8)',
            'rgba(0, 64, 202, 0.8)'
          ],
          borderColor: '#fff',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
  </div>
</div>
</body>
</html>
