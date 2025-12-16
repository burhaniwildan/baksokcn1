<?php
session_start();

/* Hapus session pembeli */
unset($_SESSION['pembeli']);

session_destroy();

/* Kembali ke login pembeli */
header('location:index.php');
exit;
