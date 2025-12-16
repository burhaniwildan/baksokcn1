<?php
session_start();

/* Hapus session admin saja */
unset($_SESSION['admin']);

session_destroy();

/* Kembali ke login admin */
header('location:index.php');
exit;
