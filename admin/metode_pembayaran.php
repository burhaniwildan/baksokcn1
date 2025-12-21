<?php
require 'conn.php';
session_start();

/* ================= PROTEKSI ADMIN ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    header('location:index.php');
    exit;
}

/* ================= TAMBAH METODE ================= */
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $ket  = trim($_POST['keterangan']);
    $qr   = null;

    if (!empty($_FILES['gambar_qr']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar_qr']['name'], PATHINFO_EXTENSION));
        $qr  = 'qris_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['gambar_qr']['tmp_name'], '../assets/qr/' . $qr);
    }

    $stmt = $conn->prepare(
        "INSERT INTO metode_pembayaran (nama_metode, keterangan, gambar_qr)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $nama, $ket, $qr);
    $stmt->execute();

    header('Location: metode_pembayaran.php');
    exit;
}

/* ================= TOGGLE STATUS ================= */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("
        UPDATE metode_pembayaran
        SET status = IF(status='aktif','nonaktif','aktif')
        WHERE id = $id
    ");
    header('Location: metode_pembayaran.php');
    exit;
}

/* ================= EDIT METODE ================= */
if (isset($_POST['update'])) {
    $id   = (int)$_POST['id'];
    $nama = $_POST['nama'];
    $ket  = $_POST['keterangan'];

    $qr_sql = '';
    if (!empty($_FILES['gambar_qr']['name'])) {
        $ext = strtolower(pathinfo($_FILES['gambar_qr']['name'], PATHINFO_EXTENSION));
        $qr  = 'qris_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['gambar_qr']['tmp_name'], '../assets/qr/' . $qr);
        $qr_sql = ", gambar_qr='$qr'";
    }

    $conn->query("
        UPDATE metode_pembayaran
        SET nama_metode='$nama', keterangan='$ket' $qr_sql
        WHERE id=$id
    ");

    header('Location: metode_pembayaran.php');
    exit;
}

/* ================= HAPUS METODE ================= */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $q = $conn->query("SELECT gambar_qr FROM metode_pembayaran WHERE id=$id");
    if ($r = $q->fetch_assoc()) {
        if (!empty($r['gambar_qr'])) {
            @unlink('../assets/qris/' . $r['gambar_qr']);
        }
    }

    $conn->query("DELETE FROM metode_pembayaran WHERE id=$id");
    header('Location: metode_pembayaran.php');
    exit;
}

/* ================= DATA ================= */
$data = $conn->query("SELECT * FROM metode_pembayaran ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Metode Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f1f3f5;
            font-family: 'Segoe UI', sans-serif;
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

        .main-content {
            margin-left: 16.666667%;
            padding: 1.5rem;
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
        <a href="pesanan.php">Pesanan</a>
        <a href="stok.php">Stok</a>
        <a class="active" href="metode_pembayaran.php">Metode Pembayaran</a>
        <a href="laporan.php">Laporan</a>
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>💳 Kelola Metode Pembayaran</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                + Tambah Metode
            </button>
        </div>

        <table class="table table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Metode</th>
                    <th>Keterangan</th>
                    <th width="12%">Status</th>
                    <th width="22%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($r = $data->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['nama_metode']) ?></td>
                        <td>
                            <?= htmlspecialchars($r['keterangan']) ?>
                            <?php if ($r['gambar_qr']): ?>
                                <button class="btn btn-sm btn-outline-primary ms-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#qrModal<?= $r['id'] ?>">
                                    QR
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $r['status'] == 'aktif' ? 'success' : 'secondary' ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $r['id'] ?>">
                                Edit
                            </button>

                            <a href="?toggle=<?= $r['id'] ?>"
                                class="btn btn-sm btn-<?= $r['status'] == 'aktif' ? 'warning' : 'success' ?>">
                                <?= $r['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </a>

                            <a href="?hapus=<?= $r['id'] ?>"
                                onclick="return confirm('Hapus metode ini?')"
                                class="btn btn-sm btn-danger">
                                Hapus
                            </a>
                        </td>
                    </tr>

                    <!-- MODAL QR -->
                    <?php if ($r['gambar_qr']): ?>
                        <div class="modal fade" id="qrModal<?= $r['id'] ?>">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content text-center">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?= $r['nama_metode'] ?></h5>
                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="../assets/qr/<?= htmlspecialchars($r['gambar_qr']) ?>" class="img-fluid">

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- MODAL EDIT -->
                    <div class="modal fade" id="editModal<?= $r['id'] ?>">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Metode Pembayaran</h5>
                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Metode</label>
                                            <input type="text" name="nama" class="form-control" value="<?= $r['nama_metode'] ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Keterangan</label>
                                            <input type="text" name="keterangan" class="form-control" value="<?= $r['keterangan'] ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Ganti QR</label>
                                            <input type="file" name="gambar_qr" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button name="update" class="btn btn-primary">Simpan</button>
                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Metode Pembayaran</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Metode</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">QR (Opsional)</label>
                            <input type="file" name="gambar_qr" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>