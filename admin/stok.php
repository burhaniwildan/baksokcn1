<?php
session_start();
include 'conn.php';

$upload_dir = '../assets/';

if (isset($_POST['tambah_produk'])) {
    $nama = trim($_POST['nama_produk']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $id_kategori = (int)$_POST['id_kategori'];

    $gambar = null;
    if (!empty($_FILES['gambar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $gambar = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar);
        } else {
            $_SESSION['error'] = "Format gambar tidak didukung. Gunakan JPG, PNG, GIF.";
            header("Location: stok.php");
            exit;
        }
    }

    $check = $conn->query("SELECT id FROM produk WHERE nama_produk = '".$conn->real_escape_string($nama)."'");
    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Nama produk sudah ada.";
        header("Location: stok.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO produk (nama_produk, harga, stok, id_kategori, gambar) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdiis", $nama, $harga, $stok, $id_kategori, $gambar);
    $stmt->execute();

    $_SESSION['sukses'] = "Produk baru berhasil ditambahkan.";
    header("Location: stok.php");
    exit;
}

if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);

    if (empty($nama_kategori)) {
        $_SESSION['error'] = "Nama kategori tidak boleh kosong.";
        header("Location: stok.php");
        exit;
    }

    $check_kategori = $conn->query("SELECT id FROM kategori WHERE nama_kategori = '".$conn->real_escape_string($nama_kategori)."'");
    if ($check_kategori->num_rows > 0) {
        $_SESSION['error'] = "Nama kategori sudah ada.";
        header("Location: stok.php");
        exit;
    }

    $stmt_kategori = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
    $stmt_kategori->bind_param("s", $nama_kategori);
    $stmt_kategori->execute();

    if ($stmt_kategori->affected_rows > 0) {
        $_SESSION['sukses'] = "Kategori baru berhasil ditambahkan.";
    } else {
        $_SESSION['error'] = "Gagal menambahkan kategori baru.";
    }
    header("Location: stok.php");
    exit;
}

if (isset($_POST['restock'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $total_harga = (float)$_POST['total_harga'];
    $keterangan = trim($_POST['keterangan']);
    $id_admin = $_SESSION['id_admin'] ?? 1;
    $tanggal = date('Y-m-d H:i:s');

    if ($jumlah < 1) {
        $_SESSION['error'] = "Jumlah restock harus lebih dari 0.";
        header("Location: stok.php?id=$id_produk");
        exit;
    }

    $conn->query("UPDATE produk SET stok = stok + $jumlah WHERE id = $id_produk");

    $stmt = $conn->prepare("INSERT INTO restock (id_produk, jumlah, total_harga, tanggal, keterangan, id_admin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdsi", $id_produk, $jumlah, $total_harga, $tanggal, $keterangan, $id_admin);
    $stmt->execute();

    $_SESSION['sukses'] = "Produk berhasil di-restock.";
    header("Location: stok.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $res = $conn->query("SELECT COUNT(*) AS total FROM detail_transaksi WHERE id_produk = $id");
    if ($res->fetch_assoc()['total'] > 0) {
        $_SESSION['error'] = "Produk tidak bisa dihapus karena masih ada transaksi terkait.";
        header("Location: stok.php");
        exit;
    }

    $res = $conn->query("SELECT COUNT(*) AS total FROM restock WHERE id_produk = $id");
    if ($res->fetch_assoc()['total'] > 0) {
        $_SESSION['error'] = "Produk tidak bisa dihapus karena masih ada data restock terkait.";
        header("Location: stok.php");
        exit;
    }

    $res = $conn->query("SELECT gambar FROM produk WHERE id = $id");
    $gambar = $res->fetch_assoc()['gambar'] ?? null;
    if ($gambar && file_exists($upload_dir . $gambar)) {
        unlink($upload_dir . $gambar);
    }

    $conn->query("DELETE FROM produk WHERE id = $id");

    $_SESSION['sukses'] = "Produk berhasil dihapus.";
    header("Location: stok.php");
    exit;
}

$search = $_GET['search'] ?? '';
if (!empty($search)) {
    // Jika ada pencarian
    $result = $conn->query("SELECT produk.id, produk.nama_produk, produk.harga, produk.stok, produk.id_kategori, produk.gambar, kategori.nama_kategori 
                            FROM produk 
                            LEFT JOIN kategori ON produk.id_kategori = kategori.id 
                            WHERE produk.nama_produk LIKE '%" . $conn->real_escape_string($search) . "%' 
                            ORDER BY produk.nama_produk");
} else {
    // Jika tidak ada pencarian
    $result = $conn->query("SELECT produk.id, produk.nama_produk, produk.harga, produk.stok, produk.id_kategori, produk.gambar, kategori.nama_kategori 
                            FROM produk 
                            LEFT JOIN kategori ON produk.id_kategori = kategori.id 
                            ORDER BY produk.nama_produk");
}

$kategori_result = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Manajemen Produk & Stok - TOKO DASHA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        img.produk-gambar {
            max-width: 80px;
            max-height: 60px;
            object-fit: contain;
        }

        .sidebar {
            position: fixed;
            height: 100%;
            background-color: rgb(34, 53, 71);
            padding: 25px 15px;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            border-radius: 5px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: rgb(95, 168, 241);
            color: #fff;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="text-white mb-4"> Dashboard Dasha</h4>
                <hr style="color: white;">
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="transaksi.php" class="nav-link">Transaksi</a>
                    <a href="#" class="nav-link active">Manajemen Stok</a>
                    <a href="laporan.php" class="nav-link">Laporan Penjualan</a>
                    <a href="logout.php" class="nav-link text-danger mt-auto">Keluar</a>
                </nav>
            </div>

            <div class="col-md-10 offset-md-2 p-4">
                <h1 class="mb-4">Manajemen Produk & Stok</h1>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['sukses'])): ?>
                <div class="alert alert-success"><?= $_SESSION['sukses']; unset($_SESSION['sukses']); ?></div>
                <?php endif; ?>

                <!-- Tombol untuk Tambah Produk -->
                <div class="d-flex text-center" style="height: 80px;">
                    <button class="btn btn-primary m-3 w-50" data-bs-toggle="modal"
                        data-bs-target="#modalTambahProduk">+ Tambah Produk Baru</button>
                    <button class="btn btn-success m-3 w-50" data-bs-toggle="modal"
                        data-bs-target="#modalTambahKategori">+ Tambah Kategori Baru</button>
                </div>
                <!-- Modal Tambah Produk -->
                <div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-labelledby="modalTambahProdukLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTambahProdukLabel">Tambah Produk Baru</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label>Nama Produk</label>
                                        <input type="text" name="nama_produk" class="form-control" required />
                                    </div>
                                    <div class="mb-3">
                                        <label>Kategori</label>
                                        <select name="id_kategori" class="form-select" required>
                                            <option value="" disabled selected>Pilih kategori</option>
                                            <?php
                    $kategori_result->data_seek(0);
                    while($kat = $kategori_result->fetch_assoc()): ?>
                                            <option value="<?= $kat['id'] ?>">
                                                <?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Harga</label>
                                        <input type="number" step="0.01" name="harga" class="form-control" required />
                                    </div>
                                    <div class="mb-3">
                                        <label>Stok Awal</label>
                                        <input type="number" name="stok" class="form-control" min="0" required />
                                    </div>
                                    <div class="mb-3">
                                        <label>Gambar Produk (jpg, png, gif)</label>
                                        <input type="file" name="gambar" class="form-control"
                                            accept=".jpg,.jpeg,.png,.gif" />
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="tambah_produk" class="btn btn-primary">Tambah
                                        Produk</button>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Tambah Kategori -->
                <div class="modal fade" id="modalTambahKategori" tabindex="-1"
                    aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTambahKategoriLabel">Tambah Kategori Baru</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                                            required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="tambah_kategori" class="btn btn-primary">Tambah
                                        Kategori</button>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


                <form method="get" class="row g-3 align-items-center mb-4 mt-2">
                    <div class="col-auto text-end">
                        <label for="search" class="col-form-label">Cari Produk:</label>
                    </div>
                    <div class="col-auto w-75">
                        <input type="text" class="form-control" name="search" id="search" placeholder="Nama Produk"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-auto px-5">Cari</button>
                </form>
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada produk.</td>
                        </tr> <?php endif; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center align-middle">
                                <?php if ($row['gambar'] && file_exists($upload_dir . $row['gambar'])): ?>
                                <img src="<?= $upload_dir . htmlspecialchars($row['gambar']) ?>" class="produk-gambar"
                                    alt="Gambar <?= htmlspecialchars($row['nama_produk']) ?>" />
                                <?php else: ?>
                                <small><i>Tidak ada gambar</i></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td><?= $row['stok'] ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                    data-bs-target="#modalRestock<?= $row['id'] ?>">Restock</a>
                                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Hapus produk ini?')">Hapus</a>
                            </td>
                        </tr>
                        <!-- Modal Restock -->
                        <div class="modal fade" id="modalRestock<?= $row['id'] ?>" tabindex="-1"
                            aria-labelledby="modalRestockLabel<?= $row['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalRestockLabel<?= $row['id'] ?>">Restock:
                                                <?= htmlspecialchars($row['nama_produk']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_produk" value="<?= $row['id'] ?>" />
                                            <div class="mb-3">
                                                <label>Jumlah Tambah</label>
                                                <input type="number" name="jumlah" min="1" class="form-control"
                                                    required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Total Biaya</label>
                                                <input type="number" name="total_harga" min="1" step="0.01"
                                                    class="form-control" required />
                                            </div>
                                            <div class="mb-3">
                                                <label>Keterangan (opsional)</label>
                                                <input type="text" name="keterangan" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="restock"
                                                class="btn btn-primary">Restock</button>
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>




            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>