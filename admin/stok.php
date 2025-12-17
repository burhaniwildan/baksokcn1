<?php
session_start();
require 'conn.php';

/* ================= PROTEKSI ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    header('location:index.php');
    exit;
}

$upload_dir = '../assets/';

/* ================= TAMBAH MENU ================= */
if (isset($_POST['tambah_menu'])) {
    $nama = trim($_POST['nama_menu']);
    $harga = (float)$_POST['harga'];
    $stok  = (int)$_POST['stok'];
    $id_kategori = (int)$_POST['id_kategori'];

    if ($nama == '' || $harga <= 0) {
        $_SESSION['error'] = "Data menu tidak valid.";
        header("Location: stok.php");
        exit;
    }

    $gambar = null;
    if (!empty($_FILES['gambar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allow = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allow)) {
            $_SESSION['error'] = "Format gambar tidak didukung.";
            header("Location: stok.php");
            exit;
        }

        $gambar = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar);
    }

    $cek = $conn->query("SELECT id FROM menu WHERE nama_menu='" . $conn->real_escape_string($nama) . "'");
    if ($cek->num_rows > 0) {
        $_SESSION['error'] = "Nama menu sudah ada.";
        header("Location: stok.php");
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO menu (nama_menu, harga, stok, id_kategori, gambar)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sdiis", $nama, $harga, $stok, $id_kategori, $gambar);
    $stmt->execute();

    $_SESSION['success'] = "Menu berhasil ditambahkan.";
    header("Location: stok.php");
    exit;
}

/* ================= TAMBAH KATEGORI ================= */
if (isset($_POST['tambah_kategori'])) {
    $nama = trim($_POST['nama_kategori']);

    if ($nama == '') {
        $_SESSION['error'] = "Nama kategori wajib diisi.";
        header("Location: stok.php");
        exit;
    }

    $cek = $conn->query("SELECT id FROM kategori WHERE nama_kategori='" . $conn->real_escape_string($nama) . "'");
    if ($cek->num_rows > 0) {
        $_SESSION['error'] = "Kategori sudah ada.";
        header("Location: stok.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();

    $_SESSION['success'] = "Kategori berhasil ditambahkan.";
    header("Location: stok.php");
    exit;
}

/* ================= RESTOCK ================= */
if (isset($_POST['restock'])) {
    $id_menu = (int)$_POST['id_menu'];
    $jumlah  = (int)$_POST['jumlah'];
    $total   = (float)$_POST['total_harga'];
    $ket     = trim($_POST['keterangan']);
    $id_admin = $_SESSION['user']['id'];
    $tanggal = date('Y-m-d H:i:s');

    if ($jumlah < 1) {
        $_SESSION['error'] = "Jumlah restock tidak valid.";
        header("Location: stok.php");
        exit;
    }

    $conn->query("UPDATE menu SET stok = stok + $jumlah WHERE id = $id_menu");

    $stmt = $conn->prepare(
        "INSERT INTO restock (id_menu, jumlah, total_harga, tanggal, keterangan, id_admin)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iisdsi", $id_menu, $jumlah, $total, $tanggal, $ket, $id_admin);
    $stmt->execute();

    $_SESSION['success'] = "Restock berhasil.";
    header("Location: stok.php");
    exit;
}

/* ================= HAPUS MENU ================= */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $cek1 = $conn->query("SELECT COUNT(*) total FROM detail_transaksi WHERE id_menu=$id");
    if ($cek1->fetch_assoc()['total'] > 0) {
        $_SESSION['error'] = "Menu tidak bisa dihapus (sudah ada transaksi).";
        header("Location: stok.php");
        exit;
    }

    $cek2 = $conn->query("SELECT COUNT(*) total FROM restock WHERE id_menu=$id");
    if ($cek2->fetch_assoc()['total'] > 0) {
        $_SESSION['error'] = "Menu tidak bisa dihapus (ada data restock).";
        header("Location: stok.php");
        exit;
    }

    $img = $conn->query("SELECT gambar FROM menu WHERE id=$id")->fetch_assoc()['gambar'];
    if ($img && file_exists($upload_dir . $img)) unlink($upload_dir . $img);

    $conn->query("DELETE FROM menu WHERE id=$id");

    $_SESSION['success'] = "Menu berhasil dihapus.";
    header("Location: stok.php");
    exit;
}

/* ================= DATA ================= */
$search = $_GET['search'] ?? '';

$sql = "SELECT menu.*, kategori.nama_kategori
        FROM menu
        LEFT JOIN kategori ON menu.id_kategori = kategori.id";

if ($search != '') {
    $sql .= " WHERE menu.nama_menu LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$sql .= " ORDER BY menu.nama_menu";

$menu = $conn->query($sql);
$kategori = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Stok</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f1f3f5;
            font-family: 'Segoe UI', sans-serif;
        }

        /* SIDEBAR (SAMA DENGAN DASHBOARD) */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 16.666667%;
            background-color: rgb(34, 53, 71);
            padding: 25px 15px;
        }

        .sidebar h4,
        .sidebar hr {
            color: white;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            display: block;
            margin-bottom: 6px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgb(95, 168, 241);
            color: #fff;
        }

        /* KONTEN */
        .main-content {
            margin-left: 16.666667%;
            padding: 1.5rem;
        }

        img.produk {
            max-width: 70px;
            max-height: 60px;
            object-fit: contain;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h4>Dashboard Admin</h4>
        <hr>
        <a href="dashboard.php">Dashboard</a>
        <a href="transaksi.php">Transaksi</a>
        <a href="stok.php" class="active">Stok</a>
        <a href="laporan.php">Laporan</a>
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>

    <!-- CONTENT -->
    <div class="main-content">
        <h3>Manajemen Menu & Stok</h3>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#tambahMenu">+ Tambah Menu</button>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#tambahKategori">+ Tambah Kategori</button>

        <form class="row g-2 mb-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Cari menu..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">Cari</button>
            </div>
        </form>

        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $menu->fetch_assoc()): ?>
                    <tr>
                        <td><?= $m['gambar'] ? "<img src='../assets/$m[gambar]' class='produk'>" : "-" ?></td>
                        <td><?= htmlspecialchars($m['nama_menu']) ?></td>
                        <td><?= htmlspecialchars($m['nama_kategori']) ?></td>
                        <td>Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                        <td><?= $m['stok'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#restock<?= $m['id'] ?>">Restock</button>
                            <a href="?hapus=<?= $m['id'] ?>" onclick="return confirm('Hapus menu ini?')" class="btn btn-sm btn-danger">Hapus</a>
                        </td>
                    </tr>

                    <!-- MODAL RESTOCK -->
                    <div class="modal fade" id="restock<?= $m['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Restock <?= htmlspecialchars($m['nama_menu']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id_menu" value="<?= $m['id'] ?>">
                                        <input type="number" name="jumlah" class="form-control mb-2" min="1" placeholder="Jumlah" required>
                                        <input type="number" name="total_harga" class="form-control mb-2" step="0.01" placeholder="Total Harga" required>
                                        <input type="text" name="keterangan" class="form-control" placeholder="Keterangan">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="restock" class="btn btn-primary">Simpan</button>
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

    <!-- MODAL TAMBAH MENU -->
    <div class="modal fade" id="tambahMenu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="nama_menu" class="form-control mb-2" placeholder="Nama Menu" required>
                        <select name="id_kategori" class="form-select mb-2" required>
                            <option disabled selected>Pilih Kategori</option>
                            <?php $kategori->data_seek(0);
                            while ($k = $kategori->fetch_assoc()): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <input type="number" name="harga" class="form-control mb-2" placeholder="Harga" required>
                        <input type="number" name="stok" class="form-control mb-2" placeholder="Stok Awal" required>
                        <input type="file" name="gambar" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah_menu" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH KATEGORI -->
    <div class="modal fade" id="tambahKategori" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Nama Kategori" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah_kategori" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>