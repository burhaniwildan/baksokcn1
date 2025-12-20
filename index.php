<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bakso KCN</title>
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
        <img src="assets/logo.jpeg" alt="Bakso KCN" height="40">
      </a>
      <h2 style="color: #fff;">Bakso KCN</h2>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav gap-5">
          <li class="nav-item">
            <a class="nav-link" href="#produk">Menu</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#galeri">Galeri</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
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
          <h1 class="display-6 fw-bold">Selamat Datang di BAKSO KCN</h1>
          <p class="fs-5">
            Kedai Bakso Kediri yang menyediakan bakso urat terbaik dengan harga yang terjangkau dan rasa yang lezat.
          </p>
          <p class="fs-6">
            Jam Operasional:<br>Senin – Minggu, 07.00 – 21.00 WIB
          </p>

          <!-- Login moved to navbar -->
        </div>

        <div class="col-md-6 text-center">
          <img src="assets/baksokcn.png" alt="Welcome" class="img-fluid rounded">
        </div>
      </div>
    </div>
  </section>

  <!-- Produk Section -->
  <section id="produk" class="py-5" style="background: #fabd42">
    <div class="container">
      <h1 class="text-center text-black">Menu Kami</h1>

      <div class="row align-items-center">
        <div class="col-md-4">
          <img src="assets/bakso.jpg" class="img-fluid rounded">
        </div>
        <div class="col-md-8">
          <h2>Makanan</h2>
          <p class="fs-5">
            Bakso Urat khas Kediri komplit.
          </p>
        </div>
      </div>

      <hr class="mb-5" style="background-color:black; height:1px;">

      <div class="row align-items-center">
        <div class="col-md-8">
          <h2>Minuman</h2>
          <p class="fs-5">
            Es Teh dan Es Jeruk.
          </p>
        </div>
        <div class="col-md-4">
          <img src="assets/es.jpg" class="img-fluid rounded" style="width:80%;">
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
          <img src="assets/baksokcn.png" class="img-fluid rounded border border-dark border-3">
        </div>
        <div class="col-md-4 mb-4">
          <img src="assets/bakso1.jpg" class="img-fluid rounded border border-dark border-3">
        </div>
        <div class="col-md-4 mb-4">
          <img src="assets/es.jpg" class="img-fluid rounded border border-dark border-3">
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
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d996.0823152343397!2d112.69379251203034!3d-7.3754585334487155!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7e360dddf6981%3A0x251de82000e8a6c2!2sBakso%20Urat%20Kediri%20Cak%20noer!5e0!3m2!1sen!2sid!4v1765938755750!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <div class="col-md-4">
          <h4>Hubungi Kami</h4>
          <p>Jl. Raya Sukolegok No.07, Dusun Legok, Suko, Kec. Sukodono, Kabupaten Sidoarjo, Jawa Timur 61258</p>
          <a href="https://wa.me/62895605957450" class="btn btn-success w-100">
            Chat via WhatsApp
          </a>
        </div>
      </div>
    </div>
  </section>

  <footer class="text-center p-3 bg-black text-white">
    © 2025 BAKSO KCN. All rights reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>