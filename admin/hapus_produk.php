<?php
// Mengambil file koneksi database agar variabel $conn bisa digunakan
include 'config.php';

// Mengambil ID (kode_inna) yang dikirim melalui URL (contoh: hapus.php?id=123)
$id = $_GET['id'];

/* --- PROSES HAPUS FILE GAMBAR DARI SERVER --- */

// Mengambil nama/path gambar dari database berdasarkan ID sebelum datanya dihapus
$result = mysqli_query($conn, "SELECT gambar FROM produk WHERE kode_inna = '$id'");

// Mengubah hasil query menjadi array asosiatif agar bisa diambil kolom 'gambar'-nya
$row = mysqli_fetch_assoc($result);

// Mengecek apakah file gambar tersebut benar-benar ada di dalam folder server
if (file_exists($row['gambar'])) {
    // Jika ada, hapus file fisik tersebut menggunakan fungsi unlink()
    unlink($row['gambar']);
}

/* --- PROSES HAPUS DATA DARI DATABASE --- */

// Menghapus baris data produk dari tabel 'produk' berdasarkan kode_inna
mysqli_query($conn, "DELETE FROM produk WHERE kode_inna = '$id'");

// Setelah selesai, pindahkan halaman (redirect) kembali ke dashboard admin
header("Location: admin_dashboard.php");
?>