<?php
// KONFIGURASI DATABASE
// Menentukan host database (server MySQL)
$host = "localhost:3307";
// Username untuk mengakses database MySQL
$username = 'root';
// Password untuk mengakses database MySQL (kosong karena menggunakan XAMPP default)
$password = '';
// Nama database yang akan digunakan
$database = 'db_intim';

// MEMBUAT KONEKSI KE DATABASE
// Fungsi mysqli_connect() digunakan untuk membuka koneksi ke server MySQL
$conn = mysqli_connect($host, $username, $password, $database);

// MENGECEK KONEKSI
// Jika koneksi gagal (!$conn bernilai true), maka tampilkan pesan error
if (!$conn) {
    // Fungsi die() menghentikan eksekusi script dan menampilkan pesan
    // mysqli_connect_error() mengembalikan deskripsi error koneksi terakhir
    die("Connection failed: " . mysqli_connect_error());
}

// MENGATUR ENCODING CHARACTER SET
// Mengatur charset koneksi database ke UTF-8 untuk mendukung karakter internasional
// Ini mencegah masalah dengan karakter khusus seperti bahasa Indonesia (é, ñ, dll)
mysqli_set_charset($conn, "utf8");

// PENGATURAN ZONA WAKTU
// Mengatur zona waktu default untuk fungsi tanggal/waktu PHP ke Asia/Jakarta (WIB)
// Ini memastikan tanggal dan waktu yang disimpan/ditampilkan sesuai dengan waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// PENGATURAN ERROR REPORTING
// Mengaktifkan pelaporan semua jenis error (E_ALL mencakup semua level error)
// Berguna untuk development/debugging
error_reporting(E_ALL);
// Mengaktifkan display error di browser (untuk debugging)
// CATATAN: Harus dinonaktifkan (di-set ke 0) di production untuk keamanan
ini_set('display_errors', 1);

// MULAI SESSION PHP
// Mengecek apakah session belum dimulai (PHP_SESSION_NONE)
if (session_status() === PHP_SESSION_NONE) {
    // Memulai session PHP untuk menyimpan data user antar halaman
    // Session digunakan untuk login, shopping cart, preferences, dll.
    session_start();
}

// PENUTUP TAG PHP
// Menandakan akhir dari script PHP
?>