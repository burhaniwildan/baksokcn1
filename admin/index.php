<?php
require '../conn.php';
session_start();

/* Redirect to single login if not already admin */
if (isset($_SESSION['user']) && $_SESSION['user']['role_id'] == 1) {
  header('location:dashboard.php');
  exit;
}

header('location:../login.php');
exit;
?>