<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    exit('Akses ditolak');
}

$awal  = $_GET['awal']  ?? '';
$akhir = $_GET['akhir'] ?? '';
$id_pembeli = (int) $_SESSION['user']['id'];

if (!$awal || !$akhir) {
    echo "<div class='alert alert-warning'>Tanggal belum dipilih.</div>";
    exit;
}

/* ================= QUERY RIWAYAT ================= */
$data = $conn->query("
    SELECT o.id, o.status, t.id AS id_transaksi, t.total, t.tanggal
    FROM orders o
    JOIN transaksi t ON o.id_transaksi = t.id
    WHERE o.id_pembeli = $id_pembeli
      AND o.status IN ('received','cancelled')
      AND DATE(t.tanggal) BETWEEN '$awal' AND '$akhir'
    ORDER BY t.tanggal DESC
");

/* ================= CEK ERROR QUERY ================= */
if (!$data) {
    echo "<div class='alert alert-danger'>Query error: {$conn->error}</div>";
    exit;
}

/* ================= CEK DATA ================= */
if ($data->num_rows == 0) {
    echo "<div class='alert alert-info'>Tidak ada transaksi.</div>";
    exit;
}
?>

<table class="table table-bordered table-sm">
    <thead class="table-light">
        <tr>
            <th>Nota</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $data->fetch_assoc()): ?>
            <tr>
                <td>#<?= $r['id_transaksi'] ?></td>
                <td><?= $r['tanggal'] ?></td>
                <td>Rp <?= number_format($r['total'], 0, ',', '.') ?></td>
                <td>
                    <span class="badge bg-<?= $r['status'] == 'received' ? 'success' : 'danger' ?>">
                        <?= $r['status'] ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>