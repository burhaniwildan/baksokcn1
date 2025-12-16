<?php
$conn = mysqli_connect('localhost', 'root', '', 'baksokcn');


if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}
