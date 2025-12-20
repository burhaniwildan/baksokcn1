<?php
require 'conn.php';
session_start();

/* ================= CEK LOGIN PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('location:index.php');
    exit;
}

$user = $_SESSION['user'];

$id_pembeli = $user['id'];

$qTotalTransaksi = $conn->query("
    SELECT COUNT(*) AS total_transaksi
    FROM orders
    WHERE id_pembeli = $id_pembeli
      AND status IN ('received','cancelled')
");

$dataTotal = $qTotalTransaksi->fetch_assoc();
$totalTransaksi = $dataTotal['total_transaksi'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembeli</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- GOOGLE ICON -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- STYLE SAMA DENGAN ADMIN -->
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
            color: #ffffff;
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
            color: #ffffff;
        }

        .main-content {
            margin-left: 16.666667%;
            padding: 1.5rem;
        }

        .welcome-banner {
            background: #0d6efd;
            color: #ffffff;
            padding: 1.5rem;
            border-radius: .5rem;
            margin-bottom: 1.5rem;
            box-shadow: 5px 5px 20px #aaa;
        }

        .card {
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
        }

        .cursor-pointer:hover {
            transform: scale(1.02);
            transition: .2s;
            opacity: .95;
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

    <!-- SIDEBAR PEMBELI -->
    <div class="sidebar">
        <h4>Dashboard Pembeli</h4>
        <hr>
        <a class="active" href="dashboard.php">Dashboard</a>
        <a href="transaksi.php">Transaksi</a>
        <a href="status_pesanan.php">Status Pesanan</a>
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME -->
        <div class="welcome-banner">
            <h5>Halo, <strong><?= htmlspecialchars($user['nama']) ?></strong> 👋</h5>
            <p class="mb-0">Siap melakukan transaksi hari ini?</p>
        </div>

        <!-- INFO CARD -->
        <div class="row g-4 mb-4">

            <div class="col-md-4">
                <div class="card text-white bg-primary cursor-pointer"
                    data-bs-toggle="modal"
                    data-bs-target="#modalRiwayat"
                    style="cursor:pointer;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5>RIWAYAT TRANSAKSI</h5>
                            <p class="mb-0">Total Transaksi <?= $totalTransaksi ?></p>
                        </div>
                        <span class="material-icons fs-2">receipt</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-white bg-success cursor-pointer"
                    onclick="window.location.href='transaksi.php'"
                    style="cursor:pointer;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5>Beli Bang!</h5>
                            <p class="mb-0">Klik Untuk Beli</p>
                        </div>
                        <span class="material-icons fs-2">payments</span>
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="card text-white bg-warning cursor-pointer"
                    onclick="window.open('https://wa.me/62895605957450')"
                    style="cursor:pointer;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5>Bantuan</h5>
                            <p class="mb-0">Butuh bantuan?</p>
                        </div>
                        <span class="material-icons fs-2">support_agent</span>
                    </div>
                </div>
            </div>

        </div>

        <script>
            function loadRiwayat() {
                const awal = document.getElementById('tgl_awal').value;
                const akhir = document.getElementById('tgl_akhir').value;

                if (!awal || !akhir) {
                    alert('Silakan pilih rentang tanggal');
                    return;
                }

                fetch(`get_riwayat_transaksi.php?awal=${awal}&akhir=${akhir}`)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('riwayatResult').innerHTML = html;
                    });
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer>
    <!-- MODAL RIWAYAT TRANSAKSI -->
    <div class="modal fade" id="modalRiwayat" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">📜 Riwayat Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- FILTER TANGGAL -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label>Dari Tanggal</label>
                            <input type="date" id="tgl_awal" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label>Sampai Tanggal</label>
                            <input type="date" id="tgl_akhir" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadRiwayat()">
                                Filter
                            </button>
                        </div>
                    </div>

                    <!-- HASIL -->
                    <div id="riwayatResult">
                        <div class="text-muted text-center">
                            Pilih rentang tanggal untuk melihat riwayat transaksi.
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

</footer>

</html>