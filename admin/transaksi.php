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
    $id_menu = (int) $_POST['id_menu'];

    $q = mysqli_query($conn, "SELECT * FROM menu WHERE id = $id_menu");
    $menu = mysqli_fetch_assoc($q);

    if ($menu) {
        $lama = $_SESSION['cart'][$id_menu]['jumlah'] ?? 0;
        $baru = $lama + 1;

        if ($baru > $menu['stok']) {
            $_SESSION['error'] = "Stok tidak mencukupi.";
        } else {
            $_SESSION['cart'][$id_menu] = [
                'nama'     => $menu['nama_menu'],
                'harga'    => $menu['harga'],
                'jumlah'   => $baru,
                'subtotal' => $baru * $menu['harga']
            ];
            $_SESSION['success'] = "Menu ditambahkan ke keranjang.";
        }
    }

    header("Location: transaksi.php");
    exit;
}


/* ================= HAPUS ITEM (SEMUA) ================= */
if (isset($_GET['hapus'])) {
    $id_menu = (int) $_GET['hapus'];

    if (isset($_SESSION['cart'][$id_menu])) {
        unset($_SESSION['cart'][$id_menu]);
    }

    header("Location: transaksi.php");
    exit;
}

/* ================= UPDATE JUMLAH DARI KERANJANG ================= */
if (isset($_POST['update_id'], $_POST['jumlah_baru'])) {

    $id_menu     = (int) $_POST['update_id'];
    $jumlah_baru = (int) $_POST['jumlah_baru'];

    // Jika jumlah < 1 → hapus item
    if ($jumlah_baru < 1) {
        unset($_SESSION['cart'][$id_menu]);
        header("Location: transaksi.php");
        exit;
    }

    // Ambil stok terbaru
    $q = mysqli_query($conn, "SELECT stok FROM menu WHERE id = $id_menu");
    $menu = mysqli_fetch_assoc($q);

    if (!$menu) {
        header("Location: transaksi.php");
        exit;
    }

    // Validasi stok
    if ($jumlah_baru > $menu['stok']) {
        $_SESSION['error'] = "Jumlah melebihi stok tersedia.";
        header("Location: transaksi.php");
        exit;
    }

    // Update cart
    $_SESSION['cart'][$id_menu]['jumlah']   = $jumlah_baru;
    $_SESSION['cart'][$id_menu]['subtotal'] =
        $jumlah_baru * $_SESSION['cart'][$id_menu]['harga'];

    header("Location: transaksi.php");
    exit;
}

/* ================= SIMPAN TRANSAKSI ================= */
if (isset($_POST['bayar'])) {

    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang kosong.";
        header("Location: transaksi.php");
        exit;
    }

    $tanggal    = date('Y-m-d');
    $pembayaran = (float) $_POST['pembayaran'];
    $total      = array_sum(array_column($_SESSION['cart'], 'subtotal'));
    $kembalian  = $pembayaran - $total;

    if ($kembalian < 0) {
        $_SESSION['error'] = "Pembayaran kurang!";
        header("Location: transaksi.php");
        exit;
    }

    $id_admin = $_SESSION['user']['id'];

    /* SIMPAN TRANSAKSI */
    $stmt = $conn->prepare(
        "INSERT INTO transaksi (tanggal, total, pembayaran, kembalian, id_admin)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sdddi", $tanggal, $total, $pembayaran, $kembalian, $id_admin);
    $stmt->execute();
    $id_transaksi = $stmt->insert_id;

    /* SIMPAN DETAIL TRANSAKSI */
    foreach ($_SESSION['cart'] as $id_menu => $item) {

        $conn->query(
            "INSERT INTO detail_transaksi
            (id_transaksi, id_menu, jumlah, harga_satuan, subtotal)
            VALUES (
                $id_transaksi,
                $id_menu,
                {$item['jumlah']},
                {$item['harga']},
                {$item['subtotal']}
            )"
        );

        $conn->query(
            "UPDATE menu
             SET stok = stok - {$item['jumlah']}
             WHERE id = $id_menu"
        );
    }

    $_SESSION['last_receipt'] = [
        'id_transaksi' => $id_transaksi,
        'total'        => $total,
        'pembayaran'   => $pembayaran,
        'kembalian'    => $kembalian
    ];

    $_SESSION['cart'] = [];

    header("Location: transaksi.php?done=1");
    exit;
}

/* ================= DATA MENU + SEARCH ================= */
$keyword = $_GET['cari'] ?? '';
$keyword_safe = mysqli_real_escape_string($conn, $keyword);

$menu = mysqli_query(
    $conn,
    $keyword
        ? "SELECT * FROM menu WHERE nama_menu LIKE '%$keyword_safe%' ORDER BY nama_menu ASC"
        : "SELECT * FROM menu ORDER BY nama_menu ASC"
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .card img {
            height: 120px;
            object-fit: contain;
        }
    </style>
</head>

<body class="p-4">
    <div class="container-fluid">
        <a href="dashboard.php" class="btn btn-danger mb-3 w-25">Kembali</a>

        <div class="row">

            <!-- MENU -->
            <div class="col-lg-8">
                <form method="get" class="mb-3">
                    <input type="text" name="cari"
                        value="<?= htmlspecialchars($keyword) ?>"
                        class="form-control"
                        placeholder="Cari menu...">
                </form>

                <div class="row row-cols-2 row-cols-md-3 g-3 p-3 border rounded">
                    <?php while ($m = mysqli_fetch_assoc($menu)): ?>
                        <div class="col">
                            <form method="post">
                                <input type="hidden" name="id_menu" value="<?= $m['id'] ?>">
                                <button name="tambah" class="border-0 bg-transparent w-100">
                                    <div class="card p-3 h-100">
                                        <img src="../assets/<?= $m['gambar'] ?? 'default.jpg' ?>">
                                        <div class="card-body text-center">
                                            <h6><?= htmlspecialchars($m['nama_menu']) ?></h6>
                                            <b><?= rupiah($m['harga']) ?></b>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- KERANJANG -->
            <div class="col-lg-4 position-fixed" style="right:0">

                <?php foreach (['error', 'success'] as $msg):
                    if (isset($_SESSION[$msg])): ?>
                        <div class="alert alert-<?= $msg == 'error' ? 'danger' : 'success' ?>">
                            <?= $_SESSION[$msg];
                            unset($_SESSION[$msg]); ?>
                        </div>
                <?php endif;
                endforeach; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">Keranjang</div>
                    <div class="card-body">

                        <?php if ($_SESSION['cart']): ?>
                            <table class="table table-sm">
                                <?php $total = 0;
                                foreach ($_SESSION['cart'] as $id => $c):
                                    $total += $c['subtotal']; ?>
                                    <tr>
                                        <td><?= $c['nama'] ?></td>
                                        <td>
                                            <form method="post" class="d-flex">
                                                <input type="hidden" name="update_id" value="<?= $id ?>">
                                                <input type="number"
                                                    name="jumlah_baru"
                                                    value="<?= $c['jumlah'] ?>"
                                                    min="1"
                                                    class="form-control form-control-sm me-1"
                                                    style="width:60px"
                                                    onchange="this.form.submit()">
                                            </form>

                                        </td>
                                        <td><?= rupiah($c['subtotal']) ?></td>
                                        <td>
                                            <a href="?hapus=<?= $id ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Hapus item ini dari keranjang?')">
                                                🗑️
                                            </a>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <b>Total: <?= rupiah($total) ?></b>

                            <form method="post" class="mt-2">
                                <input type="number"
                                    name="pembayaran"
                                    min="<?= $total ?>"
                                    class="form-control mb-2"
                                    required>
                                <button name="bayar"
                                    class="btn btn-success w-100">Bayar</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">Keranjang kosong</p>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- NOTIFIKASI TRANSAKSI SELESAI -->
    <?php if (isset($_GET['done']) && isset($_SESSION['last_receipt'])): ?>
        <div class="position-fixed bottom-0 start-0 end-0 bg-success text-white p-3 shadow-lg">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>✅ Transaksi Berhasil!</strong><br>
                    Total: <?= rupiah($_SESSION['last_receipt']['total']) ?>,
                    Bayar: <?= rupiah($_SESSION['last_receipt']['pembayaran']) ?>,
                    Kembali: <?= rupiah($_SESSION['last_receipt']['kembalian']) ?>

                </div>
                <div>
                    <a href="cetak_nota.php?id=<?= $_SESSION['last_receipt']['id_transaksi'] ?>" target="_blank" class="btn btn-light btn-sm me-2">🖨️ Cetak Nota</a>
                    <a href="transaksi.php" class="btn btn-outline-light btn-sm">Selesai</a>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['last_receipt']); ?>
    <?php endif; ?>
</body>

</html>

</body>

</html>