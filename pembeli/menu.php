<?php
require '../conn.php';

/* HANYA MENU YANG STOK > 0 */
$menu = mysqli_query(
    $conn,
    "SELECT id, nama_menu, harga, stok, gambar
     FROM menu
     WHERE stok > 0
     ORDER BY nama_menu ASC"
);

function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            transition: transform .2s ease;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .menu-img {
            height: 150px;
            object-fit: contain;
        }
    </style>
</head>

<body class="p-4">

    <div class="container-fluid">

        <h4 class="mb-3">Daftar Menu</h4>

        <?php if (mysqli_num_rows($menu) === 0): ?>
            <div class="alert alert-warning">
                Tidak ada menu tersedia.
            </div>
        <?php endif; ?>

        <div class="row row-cols-2 row-cols-md-3 g-3">
            <?php while ($m = mysqli_fetch_assoc($menu)): ?>
                <div class="col">
                    <div class="card h-100 text-center p-2 shadow-sm">

                        <!-- GAMBAR -->
                        <img src="../assets/<?= htmlspecialchars($m['gambar'] ?: 'default.jpg') ?>"
                            alt="<?= htmlspecialchars($m['nama_menu']) ?>"
                            class="card-img-top menu-img mb-2">

                        <div class="card-body p-2">
                            <h6 class="card-title mb-1">
                                <?= htmlspecialchars($m['nama_menu']) ?>
                            </h6>

                            <div class="fw-bold text-success">
                                <?= rupiah($m['harga']) ?>
                            </div>

                            <div class="text-muted small">
                                Stok: <?= (int)$m['stok'] ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>

</body>

</html>