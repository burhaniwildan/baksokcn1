<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    header('location:index.php');
    exit;
}

/* ================= HELPER ================= */
function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/* ================= CART ================= */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ================= TAMBAH KE CART ================= */
if (isset($_POST['tambah'])) {
    $id_menu = (int)$_POST['id_menu'];

    $q = mysqli_query($conn, "SELECT * FROM menu WHERE id=$id_menu");
    $menu = mysqli_fetch_assoc($q);

    if ($menu) {
        $lama = $_SESSION['cart'][$id_menu]['jumlah'] ?? 0;
        $baru = $lama + 1;

        if ($baru <= $menu['stok']) {
            $_SESSION['cart'][$id_menu] = [
                'nama' => $menu['nama_menu'],
                'harga' => $menu['harga'],
                'jumlah' => $baru,
                'subtotal' => $baru * $menu['harga']
            ];
        }
    }
    header("Location: transaksi.php");
    exit;
}

/* ================= HAPUS ================= */
if (isset($_GET['hapus'])) {
    unset($_SESSION['cart'][(int)$_GET['hapus']]);
    header("Location: transaksi.php");
    exit;
}

/* ================= SIMPAN TRANSAKSI ================= */
if (isset($_POST['bayar'])) {
    $tanggal = date('Y-m-d');
    $pembayaran = (float)$_POST['pembayaran'];
    $total = array_sum(array_column($_SESSION['cart'], 'subtotal'));
    $kembalian = $pembayaran - $total;

    if ($kembalian < 0) {
        $_SESSION['error'] = "Pembayaran kurang!";
        header("Location: transaksi.php");
        exit;
    }

    $id_admin = $_SESSION['user']['id'];

    $stmt = $conn->prepare(
        "INSERT INTO transaksi (tanggal, total, pembayaran, kembalian, id_admin)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sdddi", $tanggal, $total, $pembayaran, $kembalian, $id_admin);
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
    $_SESSION['success'] = "Transaksi berhasil!";
    header("Location: transaksi.php");
    exit;
}

/* ================= DATA MENU ================= */
$menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_menu ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">

    <a href="dashboard.php" class="btn btn-danger mb-3">⬅ Dashboard</a>

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
                    <?php foreach ($_SESSION['cart'] as $id => $c): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= $c['nama'] ?>
                            <span><?= rupiah($c['subtotal']) ?></span>
                            <a href="?hapus=<?= $id ?>">❌</a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form method="post">
                    <input type="number" name="pembayaran" class="form-control mb-2" required>
                    <button name="bayar" class="btn btn-success w-100">Bayar</button>
                </form>
            <?php else: ?>
                <p>Keranjang kosong</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>