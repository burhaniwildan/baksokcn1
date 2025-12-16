<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Toko Dasha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Mega:wght@100..900&display=swap" rel="stylesheet">
</head>

<style>
  html {
    scroll-behavior: smooth;
    scroll-padding-top: 50px;
  }

  body {
    font-family: "Lexend Mega", sans-serif;
  }

  .navbar {
    background-color: rgb(0, 0, 0);
  }

  h1,
  h2,
  h3 {
    font-weight: 700;
  }

  .navbar .nav-link {
    color: #fff;
  }

  .navbar .nav-link:hover {
    color: #e0e0e0 !important;
  }

  #beranda {
    background: linear-gradient(to bottom right,
        rgba(0, 0, 0, 0.9),
        rgba(0, 0, 0, 0.7)),
      url('assets/gmbr1.jpg') center/cover no-repeat;
    color: white;
  }
</style>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <img src="assets/logo.JPG" alt="Toko Dasha" height="40">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav gap-5">
          <li class="nav-item">
            <a class="nav-link" href="#produk">Produk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#galeri">Galeri</a>
          </li>
          <li class="nav-item me-5">
            <a class="nav-link" href="#kontak">Kontak</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Welcome Section -->
  <section id="beranda" class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1 class="display-6 fw-bold">Selamat Datang di TOKO DASHA</h1>
          <p class="fs-5">
            Toko terbaik untuk kebutuhan harian Anda! Temukan berbagai macam produk berkualitas
            dengan harga terjangkau.
          </p>
          <p class="fs-6">
            Jam Operasional:<br>Senin – Minggu, 07.00 – 21.00 WIB
          </p>

          <!-- Pilihan Login -->
          <div class="mt-4 d-flex gap-3">
            <a href="admin/index.php" class="btn btn-primary btn-lg px-4">
              Login Admin
            </a>
            <a href="pembeli/index.php" class="btn btn-outline-light btn-lg px-4">
              Login Pembeli
            </a>
          </div>
        </div>

        <div class="col-md-6 text-center">
          <img src="assets/gmbr2.jpg" alt="Welcome" class="img-fluid rounded">
        </div>
      </div>
    </div>
  </section>

  <!-- Produk Section -->
  <section id="produk" class="py-5" style="background: #fabd42">
    <div class="container">
      <h1 class="text-center text-black">Produk Kami</h1>

      <div class="row align-items-center">
        <div class="col-md-4">
          <img src="assets/gmbr-sembako.png" class="img-fluid rounded">
        </div>
        <div class="col-md-8">
          <h2>Sembako</h2>
          <p class="fs-5">
            Kami menyediakan berbagai kebutuhan pokok seperti beras, gula, minyak goreng,
            mie instan, dan lainnya.
          </p>
        </div>
      </div>

      <hr class="mb-5" style="background-color:black; height:1px;">

      <div class="row align-items-center">
        <div class="col-md-8">
          <h2>Gas Elpiji & Air Galon</h2>
          <p class="fs-5">
            Tersedia Gas LPG 3kg & 12kg serta air galon isi ulang.
          </p>
        </div>
        <div class="col-md-4">
          <img src="assets/gmbr-galon.png" class="img-fluid rounded" style="width:80%;">
        </div>
      </div>
    </div>
  </section>

  <!-- Galeri Section -->
  <section id="galeri" class="py-5 bg-light">
    <div class="container">
      <h1 class="text-center mb-4 text-black">Galeri</h1>

      <div class="row">
        <div class="col-md-4 mb-4">
          <img src="assets/gmbr1.jpg" class="img-fluid rounded border border-dark border-3">
        </div>
        <div class="col-md-4 mb-4">
          <img src="assets/gmbr2.jpg" class="img-fluid rounded border border-dark border-3">
        </div>
        <div class="col-md-4 mb-4">
          <img src="assets/gmbr3.jpg" class="img-fluid rounded border border-dark border-3">
        </div>
      </div>
    </div>
  </section>

  <!-- Kontak -->
  <section id="kontak" class="py-5" style="background:#fabd42">
    <div class="container">
      <h1 class="text-center text-black mb-4">Lokasi & Kontak</h1>
      <div class="row">
        <div class="col-md-8 mb-4">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!..."
            width="100%" height="450" style="border:0;" loading="lazy">
          </iframe>
        </div>
        <div class="col-md-4">
          <h4>Hubungi Kami</h4>
          <p>Jl. Tambak Medokan Ayu X No.20, Surabaya</p>
          <a href="https://wa.me/6281234567890" class="btn btn-success w-100">
            Chat via WhatsApp
          </a>
        </div>
      </div>
    </div>
  </section>

  <footer class="text-center p-3 bg-black text-white">
    © 2025 TOKO DASHA. All rights reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>