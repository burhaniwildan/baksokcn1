<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('location:index.php');
    exit;
}

function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ================= TAMBAH ================= */
if (isset($_POST['tambah'])) {
    $id_menu = (int)$_POST['id_menu'];

    $q = mysqli_query($conn, "SELECT * FROM menu WHERE id=$id_menu");
    $m = mysqli_fetch_assoc($q);

    if ($m) {
        $jml = $_SESSION['cart'][$id_menu]['jumlah'] ?? 0;
        if ($jml + 1 <= $m['stok']) {
            $_SESSION['cart'][$id_menu] = [
                'nama' => $m['nama_menu'],
                'harga' => $m['harga'],
                'jumlah' => $jml + 1,
                'subtotal' => ($jml + 1) * $m['harga']
            ];
        }
    }
    header("Location: transaksi.php");
    exit;
}

/* ================= CHECKOUT ================= */
if (isset($_POST['checkout'])) {
    $tanggal = date('Y-m-d');
    $total = array_sum(array_column($_SESSION['cart'], 'subtotal'));

    $stmt = $conn->prepare(
        "INSERT INTO transaksi (tanggal, total)
         VALUES (?, ?)"
    );
    $stmt->bind_param("sd", $tanggal, $total);
    $stmt->execute();
    $id_transaksi = $stmt->insert_id;

    foreach ($_SESSION['cart'] as $id_menu => $item) {
        $conn->query("INSERT INTO detail_transaksi
        (id_transaksi, id_menu, jumlah, harga_satuan, subtotal)
        VALUES (
            $id_transaksi,
            $id_menu,
            {$item['jumlah']},
            {$item['harga']},
            {$item['subtotal']}
        )");

        $conn->query("UPDATE menu SET stok = stok - {$item['jumlah']} WHERE id = $id_menu");
    }

    $_SESSION['cart'] = [];
    $_SESSION['success'] = "Pesanan berhasil!";
    header("Location: dashboard.php");
    exit;
}

$menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_menu ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi Pembeli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">

    <a href="dashboard.php" class="btn btn-secondary mb-3">⬅ Dashboard</a>

    <div class="row">
        <div class="col-md-8">
            <h4>Menu</h4>
            <div class="row row-cols-3 g-3">
                <?php while ($m = mysqli_fetch_assoc($menu)): ?>
                    <form method="post" class="col">
                        <input type="hidden" name="id_menu" value="<?= $m['id'] ?>">
                        <button name="tambah" class="btn w-100 border">
                            <?= htmlspecialchars($m['nama_menu']) ?><br>
                            <?= rupiah($m['harga']) ?>
                        </button>
                    </form>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="col-md-4">
            <h4>Keranjang</h4>
            <?php if (!empty($_SESSION['cart'])): ?>
                <ul class="list-group mb-3">
                    <?php foreach ($_SESSION['cart'] as $c): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= $c['nama'] ?>
                            <span><?= rupiah($c['subtotal']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form method="post">
                    <button name="checkout" class="btn btn-success w-100">Checkout</button>
                </form>
            <?php else: ?>
                <p>Keranjang kosong</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>