<?php
require 'conn.php';
session_start();

/* ================= CEK LOGIN PEMBELI ================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('location:index.php');
    exit;
}

$user = $_SESSION['user'];
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
        <a href="logout.php" class="text-danger mt-4">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- WELCOME -->
        <div class="welcome-banner">
            <h5>Halo, <strong><?= htmlspecialchars($user['nama']) ?></strong> 👋</h5>
            <p class="mb-0">Siap melakukan transaksi hari ini?</p>
        </div>

        <!-- INFO CARD (OPSIONAL, BISA DIISI DATA NANTI) -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h5>-</h5>
                            <p>Total Transaksi</p>
                        </div>
                        <span class="material-icons">receipt</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h5>-</h5>
                            <p>Total Belanja</p>
                        </div>
                        <span class="material-icons">payments</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <h5>-</h5>
                            <p>Transaksi Terakhir</p>
                        </div>
                        <span class="material-icons">schedule</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK ACTION -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card p-4">
                    <h5>Mulai Transaksi</h5>
                    <p class="text-muted">Lakukan pembelian produk.</p>
                    <a href="transaksi.php" class="btn btn-primary">Mulai</a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4">
                    <h5>Bantuan</h5>
                    <p class="text-muted">Butuh bantuan saat transaksi?</p>
                    <button class="btn btn-outline-secondary" disabled>Hubungi Admin</button>
                </div>
            </div>
        </div>

    </div>

</body>

</html>