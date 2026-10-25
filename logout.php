<?php
// logout.php (di root)

// 1. Memulai atau memanggil sesi yang sedang berjalan.
// Sebelum kita bisa menghapus sesi, PHP harus tahu sesi mana yang sedang aktif.
session_start();

// 2. Mengosongkan semua variabel sesi.
// Fungsi ini menghapus semua data (seperti $_SESSION['user_id']) yang tersimpan dalam sesi tersebut.
session_unset();

// 3. Menghancurkan sesi secara keseluruhan.
// Ini akan menghapus data sesi yang tersimpan di server dan mengakhiri sesi pengguna saat ini.
session_destroy();

// 4. Mengalihkan (redireksi) pengguna kembali ke halaman login.
// Setelah sesi dihapus, pengguna tidak lagi memiliki akses dan dikirim ke login.php.
header("Location: login.php");

// 5. Menghentikan eksekusi skrip.
// Sangat penting untuk memanggil exit atau die() setelah header() agar kode di bawahnya 
// tidak sempat dijalankan oleh sistem.
exit;
?>