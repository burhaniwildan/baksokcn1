<?php
require 'conn.php';
session_start();

/* ================= CEK LOGIN PEMBELI ================= */
if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role_id'] != 2
) {
    header('location:index.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Pembeli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-body text-center">
                <h3>Halo, <?= htmlspecialchars($user['nama']) ?> 👋</h3>
                <p class="mt-3">Selamat datang di sistem pembelian Toko Dasha.</p>

                <div class="d-grid gap-3 mt-4">
                    <a href="transaksi.php" class="btn btn-primary btn-lg">
                        Mulai Transaksi
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>