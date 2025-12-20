<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    header('location:index.php');
    exit;
}

/* ================= HANDLE CHANGE STATUS ================= */
if (isset($_POST['change_status'])) {
    $order_id   = (int) $_POST['order_id'];
    $new_status = $_POST['status'];

    $o = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
    if (!$o) {
        $_SESSION['error'] = 'Pesanan tidak ditemukan.';
        header('Location: pesanan.php');
        exit;
    }

    // Jika dibatalkan, kembalikan stok
    if ($new_status === 'cancelled' && $o['status'] !== 'cancelled') {
        $details = $conn->query(
            "SELECT id_menu, jumlah 
             FROM detail_transaksi 
             WHERE id_transaksi = " . intval($o['id_transaksi'])
        );
        while ($d = $details->fetch_assoc()) {
            $conn->query(
                "UPDATE menu 
                 SET stok = stok + " . (int)$d['jumlah'] . " 
                 WHERE id = " . (int)$d['id_menu']
            );
        }
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();

    $_SESSION['success'] = 'Status pesanan diperbarui.';
    header('Location: pesanan.php');
    exit;
}

/* ================= DATA PESANAN ================= */
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_pembeli INT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(32) DEFAULT NULL,
    payment_proof VARCHAR(255) DEFAULT NULL,
    received_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$orders = $conn->query(
    "SELECT o.*, t.tanggal, t.total, t.pembayaran, t.kembalian,
            u.nama AS pembeli_nama, u.username AS pembeli_username
     FROM orders o
     JOIN transaksi t ON o.id_transaksi = t.id
     LEFT JOIN users u ON u.id = o.id_pembeli
     WHERE o.status IN ('pending','processing')
     ORDER BY o.created_at DESC"
);

$open_orders = (int) (
    $conn->query(
        "SELECT COUNT(*) AS c 
         FROM orders 
         WHERE status IN ('pending','processing')"
    )->fetch_assoc()['c'] ?? 0
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f1f3f5; font-family:'Segoe UI',sans-serif; }
        .sidebar {
            position:fixed; top:0; left:0; width:16.666667%; height:100%;
            background:rgb(34,53,71); padding:25px 15px;
        }
        .sidebar h4,.sidebar hr { color:#fff; }
        .sidebar a {
            color:#adb5bd; text-decoration:none; display:block;
            padding:8px 12px; border-radius:5px; margin-bottom:6px;
        }
        .sidebar a:hover,.sidebar a.active {
            background:rgb(95,168,241); color:#fff;
        }
        .main-content { margin-left:16.666667%; padding:1.5rem; }
        .welcome-banner {
            background:#0d6efd; color:#fff; padding:1.5rem;
            border-radius:.5rem; margin-bottom:1.5rem;
            box-shadow:5px 5px 20px #aaa;
        }
        .card { border-radius:.5rem; box-shadow:0 .5rem 1rem rgba(0,0,0,.15); }
    </style>
</head>
<body>

<div class="sidebar">
    <h4>Dashboard Admin</h4>
    <hr>
    <a href="dashboard.php">Dashboard</a>
    <a href="transaksi.php">Transaksi</a>
    <a class="active" href="pesanan.php">Pesanan</a>
    <a href="stok.php">Stok</a>
    <a href="laporan.php">Laporan</a>
    <a href="logout.php" class="text-danger mt-4">Logout</a>
</div>

<div class="main-content">
    <div class="welcome-banner">
        <h5>Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']['nama']) ?></strong></h5>
        <p class="mb-0">Kelola pesanan pembeli.</p>
    </div>

    <h3>Manajemen Pesanan</h3>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card p-3">
                <h6>Pesanan Berjalan</h6>
                <h4><?= $open_orders ?></h4>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nota</th>
                <th>Pembeli</th>
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
                <td><?= htmlspecialchars($o['pembeli_nama'] ?? $o['pembeli_username'] ?? 'Guest') ?></td>
                <td><?= htmlspecialchars($o['tanggal']) ?></td>
                <td>Rp <?= number_format($o['total'],0,',','.') ?></td>
                <td>
                    <span class="badge bg-<?= $o['status']==='pending'?'warning':($o['status']==='processing'?'primary':'success') ?>">
                        <?= htmlspecialchars($o['status']) ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info"
                            data-bs-toggle="collapse"
                            data-bs-target="#details<?= $o['id'] ?>">
                        Detail
                    </button>
                    <button class="btn btn-sm btn-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#status<?= $o['id'] ?>">
                        Ubah Status
                    </button>
                </td>
            </tr>

            <tr>
                <td colspan="7" class="p-0">
                    <div class="collapse" id="details<?= $o['id'] ?>">
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
                                    <tr><th>Menu</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>
                                </thead>
                                <tbody>
                                <?php while ($d = $details->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['nama_menu']) ?></td>
                                        <td><?= $d['jumlah'] ?></td>
                                        <td>Rp <?= number_format($d['harga_satuan'],0,',','.') ?></td>
                                        <td>Rp <?= number_format($d['subtotal'],0,',','.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>

                            <p><strong>Total:</strong> Rp <?= number_format($o['total'],0,',','.') ?></p>
                            <p><strong>Pembayaran:</strong> Rp <?= number_format($o['pembayaran'],0,',','.') ?>
                               | <strong>Kembali:</strong> Rp <?= number_format($o['kembalian'],0,',','.') ?></p>

                            <?php if (!empty($o['payment_proof'])): ?>
                                <?php $proof = '../uploads/payments/' . basename($o['payment_proof']); ?>
                                <img src="<?= $proof ?>" class="img-fluid" style="max-width:300px">
                            <?php endif; ?>

                            <a href="cetak_nota.php?id=<?= $o['id_transaksi'] ?>" target="_blank"
                               class="btn btn-sm btn-light mt-2">Cetak Nota</a>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- MODAL STATUS -->
            <div class="modal fade" id="status<?= $o['id'] ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header">
                                <h5 class="modal-title">Ubah Status Pesanan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $o['status']==='pending'?'selected':'' ?>>pending</option>
                                    <option value="processing" <?= $o['status']==='processing'?'selected':'' ?>>processing</option>
                                    <option value="completed" <?= $o['status']==='completed'?'selected':'' ?>>completed</option>
                                    <option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>cancelled</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button name="change_status" class="btn btn-primary">Simpan</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
