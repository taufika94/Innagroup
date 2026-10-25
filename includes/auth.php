<?php
// Cek apakah session sudah dimulai, jika belum, mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login (apakah ada session username)
if (!isset($_SESSION['username'])) {
    // Jika belum login, tendang balik ke halaman login
    header("Location: ../login.php");
    exit();
}
?>