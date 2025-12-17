<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('location:index.php');
    exit;
}

/* ================= HELPER ================= */
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/* ================= VALIDASI ID ================= */
if (!isset($_GET['id'])) {
    die("ID Pembayaran tidak ditemukan.");
}

$id = (int) $_GET['id'];

/* ================= AMBIL TRANSAKSI ================= */
$t = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT id, tanggal, total, pembayaran, kembalian
         FROM transaksi
         WHERE id = $id"
    )
);

if (!$t) {
    die("Pembayaran berhasil.");
}

/* ================= AMBIL DETAIL PEMBAYARAN ================= */
$d = mysqli_query(
    $conn,
    "SELECT 
        m.nama_menu,
        dt.jumlah,
        dt.harga_satuan,
        dt.subtotal
     FROM detail_transaksi dt
     JOIN menu m ON dt.id_menu = m.id
     WHERE dt.id_transaksi = $id"
);

$nama_pembeli = $_SESSION['user']['nama'] ?? 'Pembeli';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Nota</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            font-size: 14px;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        .info {
            margin-bottom: 10px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 4px;
            border-bottom: 1px dashed #000;
        }

        th {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .total {
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>

<body onload="window.print()">

    <h2>BAKSO KCN</h2>

    <div class="info">
        Tanggal : <?= htmlspecialchars($t['tanggal']) ?><br>
        Nota : #<?= $t['id'] ?><br>
        Pembeli : <?= htmlspecialchars($nama_pembeli) ?>
    </div>

    <table>
        <tr>
            <th>Menu</th>
            <th>Qty</th>
            <th class="right">Harga</th>
            <th class="right">Subtotal</th>
        </tr>

        <?php while ($r = mysqli_fetch_assoc($d)): ?>
            <tr>
                <td><?= htmlspecialchars($r['nama_menu']) ?></td>
                <td><?= $r['jumlah'] ?></td>
                <td class="right"><?= formatRupiah($r['harga_satuan']) ?></td>
                <td class="right"><?= formatRupiah($r['subtotal']) ?></td>
            </tr>
        <?php endwhile; ?>

        <tr class="total">
            <td colspan="3">Total</td>
            <td class="right"><?= formatRupiah($t['total']) ?></td>
        </tr>
        <tr>
            <td colspan="3">Bayar</td>
            <td class="right"><?= formatRupiah($t['pembayaran']) ?></td>
        </tr>
        <tr>
            <td colspan="3">Kembali</td>
            <td class="right"><?= formatRupiah($t['kembalian']) ?></td>
        </tr>
    </table>

    <div class="footer">
        Selamat Menikmati 🙏<br>
        Simpan nota ini sebagai bukti pembayaran
    </div>

</body>

</html>