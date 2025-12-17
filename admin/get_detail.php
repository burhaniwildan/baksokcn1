<?php
require 'conn.php';

if (!isset($_GET['id'])) {
    echo 'ID transaksi tidak ditemukan.';
    exit;
}

$id = (int) $_GET['id'];

/* ================= AMBIL TRANSAKSI ================= */
$q_transaksi = mysqli_query($conn, "
    SELECT *
    FROM transaksi
    WHERE id = $id
");

if (!$q_transaksi || mysqli_num_rows($q_transaksi) == 0) {
    echo 'Transaksi tidak ditemukan.';
    exit;
}

$transaksi = mysqli_fetch_assoc($q_transaksi);

/* ================= AMBIL DETAIL MENU ================= */
$q_detail = mysqli_query($conn, "
    SELECT
        dt.jumlah,
        dt.harga_satuan,
        dt.subtotal,
        m.nama_menu
    FROM detail_transaksi dt
    JOIN menu m ON dt.id_menu = m.id
    WHERE dt.id_transaksi = $id
");

if (!$q_detail) {
    echo 'Query gagal: ' . mysqli_error($conn);
    exit;
}

$menu_list = '';

while ($row = mysqli_fetch_assoc($q_detail)) {
    $menu_list .= '
        <div>
            ' . htmlspecialchars($row['nama_menu']) . '
            (' . $row['jumlah'] . ' x Rp ' . number_format($row['harga_satuan'], 0, ',', '.') . '
            = <b>Rp ' . number_format($row['subtotal'], 0, ',', '.') . '</b>)
        </div>
    ';
}

/* ================= OUTPUT ================= */
echo '<table class="table table-bordered">';
echo '<tr><th width="30%">ID Transaksi</th><td>' . $transaksi['id'] . '</td></tr>';
echo '<tr><th>Tanggal</th><td>' . $transaksi['tanggal'] . '</td></tr>';
echo '<tr><th>Total Transaksi</th><td>Rp ' . number_format($transaksi['total'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Pembayaran</th><td>Rp ' . number_format($transaksi['pembayaran'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Kembalian</th><td>Rp ' . number_format($transaksi['kembalian'], 0, ',', '.') . '</td></tr>';
echo '<tr><th>Menu</th><td>' . $menu_list . '</td></tr>';
echo '</table>';
