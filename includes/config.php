<?php
// --- Konfigurasi Database ---

// Nama host/server database. Di sini menggunakan port khusus 3307 (biasanya bawaan MariaDB di XAMPP baru)
$host = "localhost:3307"; 

// Username default untuk database MySQL (biasanya 'root')
$user = "root"; 

// Password untuk database (kosong adalah default untuk XAMPP)
$pass = ""; 

// Nama database yang ingin dihubungkan/digunakan
$db   = "db_intim"; 

// --- Proses Koneksi ---

// Membuat koneksi ke database menggunakan fungsi mysqli_connect
$conn = mysqli_connect($host, $user, $pass, $db);

// Mengecek apakah koneksi berhasil atau gagal
if (!$conn) {
    // Jika gagal, hentikan program dan tampilkan pesan kesalahannya
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Mengatur charset (kumpulan karakter) ke utf8mb4 agar mendukung semua simbol dan karakter khusus
mysqli_set_charset($conn, 'utf8mb4');

// --- Pengaturan Lingkungan (Environment) ---

// Mengatur zona waktu default ke Asia/Jakarta agar fungsi waktu (date) sesuai dengan waktu lokal kita
date_default_timezone_set('Asia/Jakarta');

// Mengaktifkan pelaporan error agar semua jenis kesalahan PHP ditampilkan (sangat berguna saat tahap pengembangan/coding)
error_reporting(E_ALL);

// Memastikan error ditampilkan langsung di layar browser
ini_set('display_errors', 1);
?>