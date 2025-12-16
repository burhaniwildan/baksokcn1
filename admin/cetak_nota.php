<?php
include 'conn.php';
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
if (!isset($_GET['id'])) {
    die("ID transaksi tidak ditemukan.");
}
$id = (int)$_GET['id'];
$t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transaksi WHERE id = $id"));
$d = mysqli_query($conn, "SELECT dt.*, p.nama_produk FROM detail_transaksi dt JOIN produk p ON dt.id_produk = p.id WHERE id_transaksi = $id");
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
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td,
        th {
            padding: 4px;
            border-bottom: 1px dashed #000;
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
    <h2>TOKO DASHA</h2>
    <p>Tanggal: <?= $t['tanggal'] ?><br>Nota No: #<?= $t['id'] ?></p>
    <table>
        <tr>
            <th>Produk</th>
            <th>Jml</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>
        <?php while ($r = mysqli_fetch_assoc($d)): ?>
            <tr>
                <td><?= htmlspecialchars($r['nama_produk']) ?></td>
                <td><?= $r['jumlah'] ?></td>
                <td><?= formatRupiah($r['harga_satuan']) ?></td>
                <td><?= formatRupiah($r['subtotal']) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr class="total">
            <td colspan="3">Total</td>
            <td><?= formatRupiah($t['total']) ?></td>
        </tr>
        <tr>
            <td colspan="3">Bayar</td>
            <td><?= formatRupiah($t['pembayaran']) ?></td>
        </tr>
        <tr>
            <td colspan="3">Kembali</td>
            <td><?= formatRupiah($t['kembalian']) ?></td>
        </tr>
    </table>
    <div class="footer">Terima kasih telah berbelanja!</div>
</body>

</html>