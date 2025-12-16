<?php
require 'conn.php';

if (!isset($_GET['id'])) {
    echo 'ID transaksi tidak ditemukan.';
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil transaksi utama
$q_transaksi = mysqli_query($conn, "
SELECT * FROM transaksi WHERE id = '$id'
");

if (!$q_transaksi || mysqli_num_rows($q_transaksi) == 0) {
    echo 'Transaksi tidak ditemukan.';
    exit;
}

$transaksi = mysqli_fetch_assoc($q_transaksi);

// Ambil detail produk
$q_detail = mysqli_query($conn, "
SELECT dt.jumlah, dt.harga_satuan, dt.subtotal, p.nama_produk
FROM detail_transaksi dt
JOIN produk p ON dt.id_produk = p.id
WHERE dt.id_transaksi = '$id'
");

if (!$q_detail) {
    echo 'Query gagal: ' . mysqli_error($conn);
    exit;
}

$produk_list = '';
$total = 0;

while ($row = mysqli_fetch_assoc($q_detail)) {
    $subtotal = $row['subtotal'];
    $total += $subtotal;

    $produk_list .= '<div>' . htmlspecialchars($row['nama_produk']) . ' (' . $row['jumlah'] . ' x Rp ' . number_format($row['harga_satuan'], 0, ',', '.') . ' = Rp ' . number_format($subtotal, 0, ',', '.') . ')</div>';
}

echo '<table class="table table-bordered">';
echo '<tr><th width="30%">ID Transaksi</th><td>' . $transaksi['id'] . '</td></tr>';
echo '<tr><th>Tanggal</th><td>' . $transaksi['tanggal'] . '</td></tr>';
echo '<tr><th>Total Transaksi</th><td>Rp ' . number_format($transaksi['total'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Pembayaran</th><td>Rp ' . number_format($transaksi['pembayaran'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Kembalian</th><td>Rp ' . number_format($transaksi['kembalian'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Produk</th><td>' . $produk_list . '</td></tr>';
echo '</table>';
?>
