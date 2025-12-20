<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('location:index.php');
    exit;
}

$id_pembeli = $_SESSION['user']['id'];

/* ================= PESANAN DITERIMA PEMBELI ================= */
if (isset($_POST['terima_pesanan'])) {

    $order_id = (int) $_POST['order_id'];

    // Validasi: milik pembeli & status completed
    $order = $conn->query(
        "SELECT * FROM orders
         WHERE id = $order_id
         AND id_pembeli = $id_pembeli
         AND status = 'completed'"
    )->fetch_assoc();

    if ($order) {

        // Ubah ke received (FINAL)
        $conn->query(
            "UPDATE orders
             SET status = 'received'
             WHERE id = $order_id"
        );

        $_SESSION['success'] = "Pesanan berhasil dikonfirmasi diterima.";
    }

    header("Location: status_pesanan.php");
    exit;
}


/* ================= BATAL PESANAN ================= */
if (isset($_POST['batal_pesanan'])) {

    $order_id = (int) $_POST['order_id'];

    // Ambil pesanan milik user & status pending
    $order = $conn->query(
        "SELECT * FROM orders 
         WHERE id = $order_id 
         AND id_pembeli = $id_pembeli 
         AND status = 'pending'"
    )->fetch_assoc();

    if ($order) {

        // Kembalikan stok
        $details = $conn->query(
            "SELECT id_menu, jumlah 
             FROM detail_transaksi 
             WHERE id_transaksi = " . (int)$order['id_transaksi']
        );

        while ($d = $details->fetch_assoc()) {
            $conn->query(
                "UPDATE menu 
                 SET stok = stok + {$d['jumlah']} 
                 WHERE id = {$d['id_menu']}"
            );
        }

        // Update status pesanan
        $conn->query(
            "UPDATE orders 
             SET status = 'cancelled' 
             WHERE id = $order_id"
        );

        $_SESSION['success'] = "Pesanan berhasil dibatalkan.";
    }

    header("Location: status_pesanan.php");
    exit;
}

/* ================= DATA PESANAN ================= */
$orders = $conn->query(
    "SELECT o.*, t.tanggal, t.total, t.pembayaran, t.kembalian
     FROM orders o
     JOIN transaksi t ON o.id_transaksi = t.id
     WHERE o.id_pembeli = $id_pembeli
       AND o.status IN ('pending', 'processing','completed')
     ORDER BY o.created_at DESC"
);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f1f3f5;
            font-family: 'Segoe UI', sans-serif;
        }

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
            color: #fff;
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
            color: #fff;
        }

        .main-content {
            margin-left: 16.666667%;
            padding: 1.5rem;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h4>Dashboard Pembeli</h4>
        <hr>
        <a href="dashboard.php">Dashboard</a>
        <a href="transaksi.php">Transaksi</a>
        <a class="active" href="status_pesanan">Status Pesanan</a>
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>

    <div class="main-content">
        <h3 class="mb-3">📦 Pesanan Saya</h3>
        <button class="btn btn-secondary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#modalRiwayat">
            📜 Lihat Riwayat Transaksi
        </button>


        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($orders->num_rows == 0): ?>
            <div class="alert alert-info">Belum ada pesanan.</div>
        <?php else: ?>

            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nota</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($o = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td>#<?= $o['id_transaksi'] ?></td>
                            <td><?= $o['tanggal'] ?></td>
                            <td>Rp <?= number_format($o['total'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-<?=
                                                        $o['status'] === 'pending' ? 'warning' : ($o['status'] === 'processing' ? 'primary' : ($o['status'] === 'completed' ? 'info' : ($o['status'] === 'received' ? 'success' : 'danger')))
                                                        ?>">
                                    <?= $o['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($o['status'] === 'completed'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button name="terima_pesanan"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Yakin pesanan sudah diterima?')">
                                            Terima Pesanan
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-info"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#detail<?= $o['id'] ?>">
                                    Detail
                                </button>

                                <a href="cetak_nota.php?id=<?= $o['id_transaksi'] ?>"
                                    target="_blank"
                                    class="btn btn-sm btn-light">
                                    Cetak
                                </a>

                                <?php if ($o['status'] === 'pending'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button name="batal_pesanan"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Batalkan pesanan ini?')">
                                            Batal
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="collapse" id="detail<?= $o['id'] ?>">
                                    <div class="p-3 bg-white border-top">
                                        <?php
                                        $details = $conn->query(
                                            "SELECT m.nama_menu, dt.jumlah, dt.harga_satuan, dt.subtotal
                                 FROM detail_transaksi dt
                                 JOIN menu m ON dt.id_menu = m.id
                                 WHERE dt.id_transaksi = " . (int)$o['id_transaksi']
                                        );
                                        ?>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Menu</th>
                                                    <th>Qty</th>
                                                    <th>Harga</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($d = $details->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= $d['nama_menu'] ?></td>
                                                        <td><?= $d['jumlah'] ?></td>
                                                        <td>Rp <?= number_format($d['harga_satuan'], 0, ',', '.') ?></td>
                                                        <td>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>

                                        <p class="mb-0"><strong>Total:</strong>
                                            Rp <?= number_format($o['total'], 0, ',', '.') ?></p>
                                        <p class="mb-0"><strong>Pembayaran:</strong>
                                            Rp <?= number_format($o['pembayaran'], 0, ',', '.') ?>
                                            | <strong>Kembali:</strong>
                                            Rp <?= number_format($o['kembalian'], 0, ',', '.') ?></p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadRiwayat() {
            const awal = document.getElementById('tgl_awal').value;
            const akhir = document.getElementById('tgl_akhir').value;

            if (!awal || !akhir) {
                alert('Silakan pilih rentang tanggal');
                return;
            }

            fetch(`get_riwayat_transaksi.php?awal=${awal}&akhir=${akhir}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('riwayatResult').innerHTML = html;
                });
        }
    </script>

</body>
<footer>
    <!-- MODAL RIWAYAT TRANSAKSI -->
    <div class="modal fade" id="modalRiwayat" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">📜 Riwayat Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- FILTER TANGGAL -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label>Dari Tanggal</label>
                            <input type="date" id="tgl_awal" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label>Sampai Tanggal</label>
                            <input type="date" id="tgl_akhir" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadRiwayat()">
                                Filter
                            </button>
                        </div>
                    </div>

                    <!-- HASIL -->
                    <div id="riwayatResult">
                        <div class="text-muted text-center">
                            Pilih rentang tanggal untuk melihat riwayat transaksi.
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

</footer>

</html>