<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
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

/* ================= HAPUS ITEM (SEMUA) ================= */
if (isset($_GET['hapus'])) {
    $id_menu = (int) $_GET['hapus'];

    if (isset($_SESSION['cart'][$id_menu])) {
        unset($_SESSION['cart'][$id_menu]);
    }

    header("Location: transaksi.php");
    exit;
}



/* ================= TAMBAH KE CART (+1 SAJA) ================= */
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
        }
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

    // Ambil stok & harga terbaru
    $q = mysqli_query($conn, "SELECT stok, harga FROM menu WHERE id = $id_menu");
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
if (isset($_POST['pesan'])) {

    // pastikan benar-benar dari modal
    if (!isset($_POST['jenis_pesanan'])) {
        $_SESSION['error'] = "Jenis pesanan belum dipilih.";
        header("Location: transaksi.php");
        exit;
    }

    $jenis_pesanan = $_POST['jenis_pesanan'];

    // validasi metode pembayaran khusus delivery (nanti dipakai)
    if ($jenis_pesanan === 'delivery') {
        if (empty($_POST['metode_pembayaran'])) {
            $_SESSION['error'] = "Metode pembayaran wajib dipilih untuk delivery.";
            header("Location: transaksi.php");
            exit;
        }
    }

    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang kosong.";
        header("Location: transaksi.php");
        exit;
    }

    $tanggal    = date('Y-m-d');
    $total      = array_sum(array_column($_SESSION['cart'], 'subtotal'));
    $pembayaran = $total; // otomatis sama dengan total
    $kembalian  = 0;

    /* === SIMPAN KE TABEL TRANSAKSI === */
    $stmt = $conn->prepare(
        "INSERT INTO transaksi (tanggal, total, pembayaran, kembalian)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("sddd", $tanggal, $total, $pembayaran, $kembalian);
    $stmt->execute();
    $id_transaksi = $stmt->insert_id;

    /* === SIMPAN DETAIL & UPDATE STOK === */
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

    /* === SIMPAN KE TABEL ORDERS (TANPA VERIFIKASI) === */
    $id_pembeli = $_SESSION['user']['id'];
    $jenis_pesanan = $_POST['jenis_pesanan'] ?? 'dine_in';

    $stmt2 = $conn->prepare(
        "INSERT INTO orders (id_transaksi, id_pembeli, jenis_pesanan, status)
     VALUES (?, ?, ?, 'pending')"
    );
    $stmt2->bind_param("iis", $id_transaksi, $id_pembeli, $jenis_pesanan);
    $stmt2->execute();

    /* === STRUK NOTA === */
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


/* ================= DATA MENU ================= */
$menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_menu ASC");

$metode_pembayaran = mysqli_query(
    $conn,
    "SELECT id, nama_metode, keterangan, gambar_qr 
     FROM metode_pembayaran 
     WHERE status = 'aktif'
     ORDER BY created_at ASC"
);


?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi Pembeli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
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

        .card img {
            height: 120px;
            object-fit: contain;
        }


        .main-content {
            margin-left: 16.666667%;
            padding: 2.5rem 1.5rem 1.5rem;
        }
    </style>
</head>

<script>
    function konfirmasiPesan() {
        return confirm(
            "Apakah Anda yakin ingin memesan?\n\n" +
            "Pesanan akan langsung diproses."
        );
    };
</script>

<script>
    function toggleDelivery(isDelivery) {
        const box = document.getElementById('deliveryOption');
        const radios = box.querySelectorAll('input[name="metode_pembayaran"]');

        if (isDelivery) {
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
            radios.forEach(r => r.checked = false);
        }
    }
</script>



<body class="p-4">
    <div class="sidebar">
        <h4>Dashboard Pembeli</h4>
        <hr>
        <a href="dashboard.php">Dashboard</a>
        <a class="active" href="transaksi.php">Transaksi</a>
        <a href="status_pesanan.php">Status Pesanan</a>
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>
    <div class="main-content">
        <div class="row">
            <!-- MENU -->
            <div class="col-lg-8">
                <form class="mb-3">
                    <input type="text" class="form-control" placeholder="Cari menu..." disabled>
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
                                            <b><?= rupiah($m['harga']) ?></b><br>
                                            <small class="text-muted">Stok: <?= $m['stok'] ?></small>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- KERANJANG -->
            <div class="col-lg-4" style="right:0">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">Keranjang</div>
                    <div class="card-body">

                        <?php if ($_SESSION['cart']): ?>
                            <?php
                            $total = 0;
                            foreach ($_SESSION['cart'] as $c) {
                                $total += $c['subtotal'];
                            }
                            ?>

                            <table class="table table-sm">
                                <?php foreach ($_SESSION['cart'] as $id => $c): ?>
                                    <tr>
                                        <td><?= $c['nama'] ?></td>
                                        <td>
                                            <form method="post" class="d-flex">
                                                <input type="hidden" name="update_id" value="<?= $id ?>">
                                                <input type="number"
                                                    name="jumlah_baru"
                                                    value="<?= $c['jumlah'] ?>"
                                                    min="1"
                                                    class="form-control form-control-sm"
                                                    style="width:65px"
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
                            <button class="btn btn-success w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#modalPesan">
                                🛒 Pesan
                            </button>
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
                    <strong>✅ Pembayaran Berhasil!</strong><br>
                    Total: <?= rupiah($_SESSION['last_receipt']['total']) ?>,
                    Bayar: <?= rupiah($_SESSION['last_receipt']['pembayaran']) ?>,
                </div>
                <div>
                    <a href="cetak_nota.php?id=<?= $_SESSION['last_receipt']['id_transaksi'] ?>"
                        target="_blank"
                        class="btn btn-light btn-sm me-2">🖨️ Cetak Bukti</a>
                    <a href="transaksi.php"
                        class="btn btn-outline-light btn-sm">Selesai</a>
                </div>
            </div>
        </div>
    <?php unset($_SESSION['last_receipt']);
    endif; ?>

    <!-- MODAL PILIH JENIS PESANAN -->
    <div class="modal fade" id="modalPesan" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="pesan" value="1">

                    <label class="form-label fw-bold">Jenis Pesanan</label>
                    <div class="form-check">
                        <input class="form-check-input"
                            type="radio"
                            name="jenis_pesanan"
                            value="dine_in"
                            id="dinein"
                            checked
                            onclick="toggleDelivery(false)">
                        <label class="form-check-label" for="dinein">
                            🍽️ Makan di Tempat
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input"
                            type="radio"
                            name="jenis_pesanan"
                            value="delivery"
                            id="delivery"
                            onclick="toggleDelivery(true)">
                        <label class="form-check-label" for="delivery">
                            🛵 Delivery
                        </label>
                    </div>

                    <!-- TEMPAT KOSONG UNTUK PENGEMBANGAN -->
                    <div id="deliveryOption" class="border rounded p-3 d-none mt-3">

                        <label class="form-label fw-bold mb-2">Metode Pembayaran</label>

                        <?php while ($mp = mysqli_fetch_assoc($metode_pembayaran)): ?>
                            <div class="form-check mb-3">

                                <!-- RADIO -->
                                <input class="form-check-input metode-radio"
                                    type="radio"
                                    name="metode_pembayaran"
                                    id="mp<?= $mp['id'] ?>"
                                    value="<?= $mp['id'] ?>"
                                    data-target="qr<?= $mp['id'] ?>">

                                <!-- LABEL + KETERANGAN (SELALU TAMPIL) -->
                                <label class="form-check-label" for="mp<?= $mp['id'] ?>">
                                    <strong><?= htmlspecialchars($mp['nama_metode']) ?></strong>

                                    <?php if (!empty($mp['keterangan'])): ?>
                                        <div class="text-muted small mt-1">
                                            <?= htmlspecialchars($mp['keterangan']) ?>
                                        </div>
                                    <?php endif; ?>
                                </label>

                                <!-- QR SAJA YANG DISEMBUNYIKAN -->
                                <?php if (!empty($mp['gambar_qr'])): ?>
                                    <div id="qr<?= $mp['id'] ?>" class="qr-box d-none mt-2 ms-4">
                                        <img src="../assets/qr/<?= htmlspecialchars($mp['gambar_qr']) ?>"
                                            class="img-fluid rounded border"
                                            style="max-width:160px;">
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endwhile; ?>



                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-success">
                        Lanjutkan Pesanan
                    </button>
                </div>


            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.metode-radio').forEach(radio => {
            radio.addEventListener('change', function() {

                // sembunyikan semua QR dulu
                document.querySelectorAll('.qr-box').forEach(qr => {
                    qr.classList.add('d-none');
                });

                // tampilkan QR milik radio ini
                const target = this.dataset.target;
                if (target) {
                    const qr = document.getElementById(target);
                    if (qr) qr.classList.remove('d-none');
                }
            });
        });
    </script>

</body>

</html>