<?php
// Memulai session untuk manajemen state pengguna
session_start();
// Meng-include file konfigurasi untuk koneksi database
include '../includes/config.php';

// Proteksi Halaman - Memastikan hanya user yang login yang dapat mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    // Redirect ke halaman login jika role bukan 'user'
    header("Location:../login.php");
    exit;
}

// Ambil parameter pencarian dari URL
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';


// Ambil data user dari session
$username = $_SESSION['username'];
$userQuery = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
$namaLengkap = $userData['username'] ?? $username;

// Query untuk galeri berita DENGAN FILTER PENCARIAN
$query = "SELECT * FROM berita_galeri WHERE 1=1";
if ($search != '') {
    $query .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$query .= " ORDER BY tanggal DESC";
$result = mysqli_query($conn, $query);

// Hitung total hasil pencarian
$totalGaleri = $result ? mysqli_num_rows($result) : 0;

// Query untuk kategori galeri DENGAN FILTER PENCARIAN
$kategoriQuery = "SELECT DISTINCT kategori FROM berita_galeri WHERE 1=1";
if ($search != '') {
    $kategoriQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$kategoriQuery .= " ORDER BY kategori";
$kategoriResult = mysqli_query($conn, $kategoriQuery);

// Fungsi untuk mendapatkan path gambar lokal
function getLocalImage($imageName, $default = 'logo.png') {
    // Daftar ekstensi yang dicoba
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Jika sudah full path, langsung return
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    // Coba berbagai lokasi penyimpanan gambar
    $possibleLocations = [
        '../uploads/galeri/' . $imageName,
        '../uploads/' . $imageName,
        '../assets/images/galeri/' . $imageName,
        'images/galeri/' . $imageName,
        'galeri/' . $imageName,
        $imageName
    ];
    
    foreach ($possibleLocations as $location) {
        // Cek file dengan ekstensi asli
        if (file_exists($location)) {
            return $location;
        }
        
        // Coba dengan berbagai ekstensi
        foreach ($extensions as $ext) {
            $testPath = $location . '.' . $ext;
            if (file_exists($testPath)) {
                return $testPath;
            }
        }
    }
    
    // Return default jika gambar tidak ditemukan
    return $default;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"> <!-- Set karakter encoding UTF-8 untuk mendukung bahasa Indonesia -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive meta tag -->
    <title>PT INTIM - Galeri</title> <!-- Judul halaman browser -->
    
    <!-- Include Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Include Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Definisi CSS Variables untuk konsistensi warna dan efek */
        :root {
            --primary: #2C3E50; /* Warna primer biru tua */
            --secondary: #f8f9fa; /* Warna sekunder abu-abu muda */
            --accent: #3498db; /* Warna aksen biru */
            --accent-light: #5dade2; /* Warna aksen biru muda */
            --accent-dark: #1b4f72; /* Warna aksen biru gelap */
            --text-dark: #1a252f; /* Warna teks gelap */
            --text-light: #7f8c8d; /* Warna teks terang */
            --white: #ffffff; /* Warna putih */
            --gray-50: #f8fafc; /* Warna abu-abu sangat muda */
            --gray-100: #ecf0f1; /* Warna abu-abu muda */
            --gray-200: #d5dbdb; /* Warna abu-abu medium */
            --gray-300: #bdc3c7; /* Warna abu-abu */
            --gray-800: #1e2a38; /* Warna abu-abu gelap */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1); /* Shadow kecil */
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Shadow standar */
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); /* Shadow besar */
            --shadow-xl: 0 20px 40px -10px rgba(0, 0, 0, 0.2); /* Shadow extra large */
            --radius-sm: 4px; /* Border radius kecil */
            --radius: 8px; /* Border radius standar */
            --radius-lg: 12px; /* Border radius besar */
            --radius-xl: 16px; /* Border radius extra large */
            --radius-2xl: 24px; /* Border radius double extra large */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Transisi cepat */
            --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); /* Transisi lambat */
        }

        /* Reset dasar untuk semua elemen */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; /* Box model yang lebih mudah */
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Styling dasar body */
        body {
            font-family: 'Inter', sans-serif; /* Font utama */
            color: var(--text-dark); /* Warna teks */
            background: var(--white); /* Warna background */
            line-height: 1.6; /* Spasi antar baris */
            overflow-x: hidden; /* Hilangkan scroll horizontal */
        }

        /* ========== NAVBAR STYLE SAMA DENGAN DASHBOARD ========== */
        /* Premium Navigation */
        .header {
            background: rgba(255, 255, 255, 0.98); /* Background semi transparan */
            backdrop-filter: blur(20px); /* Efek blur untuk background */
            -webkit-backdrop-filter: blur(20px); /* Support untuk Safari */
            position: fixed; /* Fixed positioning */
            top: 0; /* Posisi atas */
            width: 100%; /* Lebar penuh */
            z-index: 9999; /* Z-index tinggi */
            padding: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05); /* Border bawah tipis */
            transition: var(--transition); /* Transisi untuk perubahan */
        }

        /* Header ketika di-scroll */
        .header.scrolled {
            box-shadow: var(--shadow); /* Tambah shadow */
            padding: 0;
        }

        /* Container untuk navbar */
        .nav-container {
            max-width: 1400px; /* Lebar maksimum */
            margin: 0 auto; /* Tengah secara horizontal */
            padding: 0 40px; /* Padding horizontal */
            display: flex; /* Flexbox layout */
            justify-content: space-between; /* Space between items */
            align-items: center; /* Center items vertically */
            height: 80px; /* Tinggi navbar */
        }

        /* Styling logo */
        .logo {
            font-family: 'Playfair Display', serif; /* Font khusus untuk logo */
            font-size: 32px; /* Ukuran font */
            font-weight: 700; /* Ketebalan font */
            color: var(--primary); /* Warna logo */
            text-decoration: none; /* Hilangkan underline */
            letter-spacing: -0.5px; /* Spasi huruf */
            position: relative; /* Position relative */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 12px; /* Spasi antara elemen */
        }

        /* Gambar logo */
        .logo img {
            height: 45px; /* Tinggi gambar */
            width: auto; /* Lebar otomatis */
            object-fit: contain; /* Maintain aspect ratio */
        }

        /* Main Navigation */
        .nav-main {
            display: flex; /* Flexbox layout */
            gap: 40px; /* Spasi antar link */
            align-items: center; /* Center items vertically */
        }

        /* Link navigasi */
        .nav-link {
            text-decoration: none; /* Hilangkan underline */
            color: var(--text-dark); /* Warna teks */
            font-weight: 500; /* Ketebalan font */
            font-size: 15px; /* Ukuran font */
            transition: var(--transition); /* Transisi untuk hover */
            position: relative; /* Position relative untuk pseudo-element */
            padding: 8px 0; /* Padding vertical */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 6px; /* Spasi antara icon dan teks */
        }

        /* Underline effect untuk nav-link */
        .nav-link::after {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            bottom: 0; /* Posisi bawah */
            left: 0; /* Posisi kiri */
            width: 0; /* Lebar awal 0 */
            height: 2px; /* Tinggi underline */
            background: var(--accent); /* Warna underline */
            transition: width 0.3s ease; /* Transisi untuk lebar */
        }

        /* Hover dan active state untuk underline */
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%; /* Lebar penuh saat hover/active */
        }

        /* Hover state untuk nav-link */
        .nav-link:hover {
            color: var(--primary); /* Ubah warna teks saat hover */
        }

        /* Active state untuk nav-link */
        .nav-link.active {
            color: var(--accent); /* Warna teks aktif */
            font-weight: 600; /* Ketebalan font aktif */
        }

        /* Enhanced Dropdown */
        .nav-dropdown {
            position: relative; /* Position relative untuk dropdown */
        }

        /* Dropdown toggle (tombol untuk membuka dropdown) */
        .dropdown-toggle {
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 6px; /* Spasi antar elemen */
            cursor: pointer; /* Ubah cursor menjadi pointer */
            user-select: none; /* Prevent text selection */
        }

        /* Konten dropdown */
        .dropdown-content {
            position: absolute; /* Position absolute */
            top: calc(100% + 15px); /* Posisi di bawah toggle */
            left: 50%; /* Tengah secara horizontal */
            transform: translateX(-50%) translateY(-10px); /* Center dan offset awal */
            background: var(--white); /* Background putih */
            min-width: 200px; /* Lebar minimum */
            box-shadow: var(--shadow-xl); /* Shadow untuk dropdown */
            border-radius: var(--radius-lg); /* Border radius */
            z-index: 1001; /* Z-index tinggi */
            padding: 12px 0; /* Padding vertical */
            opacity: 0; /* Awalnya transparan */
            visibility: hidden; /* Awalnya tersembunyi */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Transisi smooth */
            border: 1px solid var(--gray-100); /* Border dropdown */
        }

        /* Dropdown ketika aktif */
        .nav-dropdown.active .dropdown-content {
            opacity: 1; /* Tampilkan sepenuhnya */
            visibility: visible; /* Tampilkan */
            transform: translateX(-50%) translateY(0); /* Posisi akhir */
        }

        /* Form di dalam dropdown */
        .dropdown-content form {
            width: 100%; /* Lebar penuh */
            margin: 0; /* Hilangkan margin */
            padding: 0; /* Hilangkan padding */
        }

        /* Select di dalam dropdown */
        .dropdown-content select {
            width: 100%; /* Lebar penuh */
            padding: 12px 16px; /* Padding */
            border: 1px solid var(--gray-200); /* Border */
            border-radius: var(--radius); /* Border radius */
            font-size: 14px; /* Ukuran font */
            color: var(--text-dark); /* Warna teks */
            background: var(--white); /* Background */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            font-family: 'Inter', sans-serif; /* Font family */
            appearance: none; /* Hilangkan styling default browser */
            /* Custom dropdown arrow */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237f8c8d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat; /* No repeat untuk arrow */
            background-position: right 12px center; /* Posisi arrow */
            background-size: 14px; /* Ukuran arrow */
        }

        /* Focus state untuk select */
        .dropdown-content select:focus {
            outline: none; /* Hilangkan outline default */
            border-color: var(--accent); /* Ubah warna border */
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); /* Tambah glow effect */
        }

        /* Dropdown chevron animation */
        .nav-dropdown .dropdown-toggle .fa-chevron-down {
            transition: transform 0.3s ease; /* Transisi untuk rotasi */
            font-size: 12px; /* Ukuran icon */
        }

        /* Rotasi chevron ketika dropdown aktif */
        .nav-dropdown.active .dropdown-toggle .fa-chevron-down {
            transform: rotate(180deg); /* Rotasi 180 derajat */
        }

        /* Container untuk link-link dropdown */
        .dropdown-links {
            display: flex; /* Flexbox layout */
            flex-direction: column; /* Column direction */
            gap: 4px; /* Spasi antar link */
            padding: 8px 0; /* Padding vertical */
        }

        /* Item di dalam dropdown */
        .dropdown-item {
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 12px; /* Spasi antar icon dan teks */
            padding: 10px 20px; /* Padding */
            color: var(--text-dark); /* Warna teks */
            text-decoration: none; /* Hilangkan underline */
            transition: var(--transition); /* Transisi */
            font-size: 14px; /* Ukuran font */
            position: relative; /* Position relative */
            overflow: hidden; /* Hide overflow */
        }

        /* Hover state untuk dropdown item */
        .dropdown-item:hover {
            background: var(--gray-50); /* Background saat hover */
            color: var(--accent); /* Warna teks saat hover */
            padding-left: 24px; /* Tambah padding kiri */
        }

        /* Active state untuk dropdown item */
        .dropdown-item.active {
            background: var(--accent); /* Background aktif */
            color: var(--white); /* Warna teks aktif */
        }

        /* Hover state untuk dropdown item aktif */
        .dropdown-item.active:hover {
            background: var(--accent-light); /* Background hover untuk aktif */
            color: var(--white); /* Warna teks tetap putih */
        }

        /* Icon di dalam dropdown item */
        .dropdown-item i {
            width: 18px; /* Lebar tetap untuk icon */
            text-align: center; /* Center icon */
            font-size: 14px; /* Ukuran icon */
        }

        /* User Menu - Modern */
        .nav-actions {
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 20px; /* Spasi antar elemen */
        }

        /* Container user menu */
        .user-menu {
            position: relative; /* Position relative untuk dropdown */
        }

        /* Avatar user */
        .user-avatar {
            width: 44px; /* Lebar avatar */
            height: 44px; /* Tinggi avatar */
            border-radius: 50%; /* Bentuk lingkaran */
            background: linear-gradient(135deg, var(--accent), var(--accent-dark)); /* Gradient background */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            justify-content: center; /* Center items horizontally */
            color: var(--white); /* Warna teks putih */
            font-weight: 600; /* Ketebalan font */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            border: 2px solid transparent; /* Border transparan */
            font-size: 16px; /* Ukuran font */
            overflow: hidden; /* Hide overflow */
        }

        /* Hover state untuk avatar */
        .user-avatar:hover {
            transform: translateY(-2px); /* Naik sedikit */
            box-shadow: var(--shadow-lg); /* Tambah shadow */
            border-color: var(--accent-light); /* Ubah warna border */
        }

        /* Gambar di dalam avatar (jika ada) */
        .user-avatar img {
            width: 100%; /* Lebar penuh */
            height: 100%; /* Tinggi penuh */
            object-fit: cover; /* Cover container */
        }

        /* Dropdown user menu */
        .user-dropdown {
            position: absolute; /* Position absolute */
            top: calc(100% + 15px); /* Posisi di bawah avatar */
            right: 0; /* Rata kanan */
            background: var(--white); /* Background putih */
            min-width: 280px; /* Lebar minimum */
            box-shadow: var(--shadow-xl); /* Shadow */
            border-radius: var(--radius-lg); /* Border radius */
            z-index: 1001; /* Z-index tinggi */
            opacity: 0; /* Awalnya transparan */
            visibility: hidden; /* Awalnya tersembunyi */
            transform: translateY(-10px); /* Offset awal */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Transisi smooth */
            border: 1px solid var(--gray-100); /* Border */
        }

        /* User dropdown ketika aktif */
        .user-menu.active .user-dropdown {
            opacity: 1; /* Tampilkan sepenuhnya */
            visibility: visible; /* Tampilkan */
            transform: translateY(0); /* Posisi akhir */
        }

        /* Info user di dalam dropdown */
        .user-info {
            padding: 20px; /* Padding */
            border-bottom: 1px solid var(--gray-100); /* Border bawah */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 16px; /* Spasi antar elemen */
        }

        /* Avatar di dalam user info */
        .user-info-avatar {
            width: 50px; /* Lebar avatar */
            height: 50px; /* Tinggi avatar */
            border-radius: 50%; /* Bentuk lingkaran */
            background: linear-gradient(135deg, var(--accent), var(--accent-dark)); /* Gradient background */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            justify-content: center; /* Center items horizontally */
            color: var(--white); /* Warna teks putih */
            font-weight: 700; /* Ketebalan font */
            font-size: 18px; /* Ukuran font */
        }

        /* Detail user */
        .user-details h4 {
            font-size: 15px; /* Ukuran font */
            font-weight: 600; /* Ketebalan font */
            color: var(--text-dark); /* Warna teks */
            margin-bottom: 4px; /* Margin bawah */
        }

        /* Subtitle user */
        .user-details p {
            font-size: 13px; /* Ukuran font */
            color: var(--text-light); /* Warna teks terang */
            font-weight: 400; /* Ketebalan font normal */
        }

        /* Container untuk aksi user */
        .user-actions {
            padding: 12px; /* Padding */
        }

        /* Item aksi user */
        .user-action-item {
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            gap: 12px; /* Spasi antar icon dan teks */
            padding: 12px; /* Padding */
            text-decoration: none; /* Hilangkan underline */
            color: var(--text-dark); /* Warna teks */
            transition: var(--transition); /* Transisi */
            border-radius: var(--radius); /* Border radius */
            cursor: pointer; /* Pointer cursor */
            font-size: 14px; /* Ukuran font */
        }

        /* Hover state untuk user action item */
        .user-action-item:hover {
            background: var(--gray-50); /* Background saat hover */
            color: var(--accent); /* Warna teks saat hover */
            transform: translateX(4px); /* Geser ke kanan */
        }

        /* Logout item khusus */
        .user-action-item.logout {
            color: #e74c3c; /* Warna merah untuk logout */
            border-top: 1px solid var(--gray-100); /* Border atas */
            margin-top: 8px; /* Margin atas */
        }

        /* Hover state untuk logout */
        .user-action-item.logout:hover {
            background: rgba(231, 76, 60, 0.05); /* Background merah transparan */
        }


        /* Mobile Menu Button - DESAIN BARU SEPERTI GAMBAR */
        .mobile-menu-btn {
            display: none; /* Default hidden untuk desktop */
            background: none; /* Background transparan */
            border: none; /* Hilangkan border */
            font-size: 24px; /* Ukuran icon */
            color: var(--primary); /* Warna icon */
            cursor: pointer; /* Pointer cursor */
            width: 44px; /* Lebar tombol */
            height: 44px; /* Tinggi tombol */
            border-radius: 8px; /* Border radius */
            align-items: center; /* Center icon vertically */
            justify-content: center; /* Center icon horizontally */
            transition: var(--transition); /* Transisi */
            position: relative; /* Position relative */
            z-index: 9999; /* Z-index sangat tinggi */
        }

        /* Hover state untuk mobile menu button */
        .mobile-menu-btn:hover {
            background: var(--gray-100); /* Background abu-abu muda saat hover */
        }

        /* Active state untuk mobile menu button */
        .mobile-menu-btn.active {
            background: var(--accent); /* Background aksen saat aktif */
            color: var(--white); /* Warna icon putih */
        }

        /* Mobile Menu Overlay - DESAIN SEPERTI GAMBAR */
        .mobile-menu-overlay {
            position: fixed; /* Position fixed untuk overlay */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            bottom: 0; /* Posisi bawah */
            background: rgba(255, 255, 255, 0.98); /* Background semi transparan */
            backdrop-filter: blur(20px); /* Efek blur */
            display: none; /* Default hidden */
            flex-direction: column; /* Column direction */
            justify-content: flex-start; /* Start dari atas */
            align-items: center; /* Center items horizontally */
            z-index: 9998; /* Z-index tinggi */
            animation: fadeIn 0.3s ease-out; /* Animasi fade in */
            padding-top: 100px; /* Padding atas */
        }

        /* Mobile menu overlay ketika aktif */
        .mobile-menu-overlay.active {
            display: flex; /* Tampilkan ketika aktif */
        }

        /* Navigation untuk mobile */
        .mobile-nav {
            display: flex; /* Flexbox layout */
            flex-direction: column; /* Column direction */
            gap: 0; /* Tidak ada gap antar item */
            align-items: stretch; /* Stretch items */
            width: 90%; /* Lebar 90% */
            max-width: 400px; /* Lebar maksimum */
        }

        /* Link untuk mobile navigation */
        .mobile-nav-link {
            color: var(--text-dark); /* Warna teks */
            text-decoration: none; /* Hilangkan underline */
            font-size: 16px; /* Ukuran font */
            font-weight: 500; /* Ketebalan font */
            transition: var(--transition); /* Transisi */
            padding: 16px 20px; /* Padding */
            position: relative; /* Position relative */
            width: 100%; /* Lebar penuh */
            text-align: left; /* Rata kiri */
            border-bottom: 1px solid var(--gray-100); /* Border bawah */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center items vertically */
            justify-content: space-between; /* Space between items */
        }

        /* Hover dan active state untuk mobile nav link */
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            color: var(--accent); /* Warna aksen */
            background: var(--gray-50); /* Background abu-abu sangat muda */
        }

        /* Icon di dalam mobile nav link */
        .mobile-nav-link i {
            width: 20px; /* Lebar tetap untuk icon */
            text-align: center; /* Center icon */
            margin-right: 10px; /* Margin kanan */
            font-size: 14px; /* Ukuran icon */
        }

        /* Chevron di dalam mobile nav link */
        .mobile-nav-link .fa-chevron-down {
            font-size: 12px; /* Ukuran icon lebih kecil */
            transition: transform 0.3s ease; /* Transisi untuk rotasi */
        }

        /* Rotasi chevron ketika aktif */
        .mobile-nav-link.active .fa-chevron-down {
            transform: rotate(180deg); /* Rotasi 180 derajat */
        }

        /* Konten dropdown untuk mobile */
        .mobile-dropdown-content {
            display: none; /* Default hidden */
            background: var(--gray-50); /* Background abu-abu sangat muda */
            border-radius: 0 0 var(--radius) var(--radius); /* Border radius hanya di bawah */
            overflow: hidden; /* Hide overflow */
            animation: fadeIn 0.3s ease-out; /* Animasi fade in */
        }

        /* Mobile dropdown content ketika aktif */
        .mobile-dropdown-content.active {
            display: block; /* Tampilkan ketika aktif */
        }

        /* Link di dalam mobile dropdown */
        .mobile-dropdown-link {
            display: block; /* Block display */
            padding: 14px 20px 14px 46px; /* Padding dengan indentasi */
            color: var(--text-dark); /* Warna teks */
            text-decoration: none; /* Hilangkan underline */
            font-size: 14px; /* Ukuran font */
            transition: var(--transition); /* Transisi */
            border-bottom: 1px solid var(--gray-100); /* Border bawah */
        }

        /* Hover dan active state untuk mobile dropdown link */
        .mobile-dropdown-link:hover,
        .mobile-dropdown-link.active {
            color: var(--accent); /* Warna aksen */
            background: var(--white); /* Background putih */
            padding-left: 50px; /* Tambah padding kiri */
        }

        /* Icon di dalam mobile dropdown link */
        .mobile-dropdown-link i {
            width: 20px; /* Lebar tetap untuk icon */
            margin-right: 8px; /* Margin kanan */
            font-size: 12px; /* Ukuran icon */
        }

        /* Tombol close untuk mobile menu */
        .mobile-menu-close {
            position: absolute; /* Position absolute */
            top: 24px; /* Posisi atas */
            right: 24px; /* Posisi kanan */
            background: none; /* Background transparan */
            border: none; /* Hilangkan border */
            color: var(--primary); /* Warna icon */
            font-size: 24px; /* Ukuran icon */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            width: 40px; /* Lebar */
            height: 40px; /* Tinggi */
            border-radius: 50%; /* Bentuk lingkaran */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center icon vertically */
            justify-content: center; /* Center icon horizontally */
        }

        /* Hover state untuk mobile menu close */
        .mobile-menu-close:hover {
            color: var(--accent); /* Ubah warna icon */
            background: var(--gray-100); /* Background abu-abu muda */
            transform: rotate(90deg); /* Rotasi 90 derajat */
        }

        /* ========== GALERI CONTENT STYLE ========== */
        /* Galeri Hero Section */
        .galeri-hero {
            padding: 160px 0 100px; /* Padding vertical besar */
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%); /* Gradient background */
            position: relative; /* Position relative untuk pseudo-element */
            overflow: hidden; /* Hide overflow */
        }

        /* Background effect untuk hero section */
        .galeri-hero-bg {
            position: absolute; /* Position absolute */
            top: 0; /* Posisi atas */
            right: 0; /* Posisi kanan */
            width: 50%; /* Lebar 50% */
            height: 100%; /* Tinggi penuh */
            /* Radial gradient effects */
            background: 
                radial-gradient(circle at 30% 30%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 70%, rgba(44, 62, 80, 0.05) 0%, transparent 50%);
        }

        /* Container untuk hero section */
        .galeri-hero-container {
            max-width: 1400px; /* Lebar maksimum */
            margin: 0 auto; /* Tengah secara horizontal */
            padding: 0 40px; /* Padding horizontal */
            text-align: center; /* Text center */
            position: relative; /* Position relative */
            z-index: 1; /* Z-index di atas background */
        }

        /* Badge untuk galeri hero */
        .galeri-badge {
            display: inline-flex; /* Inline flex */
            align-items: center; /* Center items vertically */
            gap: 8px; /* Spasi antar icon dan teks */
            padding: 8px 16px; /* Padding */
            background: rgba(52, 152, 219, 0.1); /* Background transparan biru */
            border: 1px solid rgba(52, 152, 219, 0.2); /* Border transparan */
            border-radius: 50px; /* Border radius bulat */
            color: var(--accent-dark); /* Warna teks biru gelap */
            font-size: 13px; /* Ukuran font kecil */
            font-weight: 600; /* Ketebalan font */
            letter-spacing: 1px; /* Spasi huruf */
            text-transform: uppercase; /* Huruf kapital */
            margin-bottom: 24px; /* Margin bawah */
        }

        /* Judul galeri hero */
        .galeri-title {
            font-family: 'Playfair Display', serif; /* Font khusus untuk judul */
            font-size: 48px; /* Ukuran font besar */
            font-weight: 700; /* Ketebalan font */
            line-height: 1.1; /* Line height */
            margin-bottom: 20px; /* Margin bawah */
            color: var(--primary); /* Warna teks primer */
            position: relative; /* Position relative untuk pseudo-element */
            display: inline-block; /* Inline block */
        }

        /* Underline effect untuk judul galeri */
        .galeri-title::after {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            bottom: -10px; /* Posisi di bawah judul */
            left: 50%; /* Posisi horizontal tengah */
            transform: translateX(-50%); /* Center horizontally */
            width: 80px; /* Lebar underline */
            height: 4px; /* Tinggi underline */
            background: var(--accent); /* Warna aksen */
            border-radius: 2px; /* Border radius kecil */
        }

        /* Deskripsi galeri hero */
        .galeri-description {
            font-size: 17px; /* Ukuran font */
            color: var(--text-light); /* Warna teks terang */
            margin-bottom: 32px; /* Margin bawah */
            max-width: 700px; /* Lebar maksimum */
            margin-left: auto; /* Margin kiri auto */
            margin-right: auto; /* Margin kanan auto */
            line-height: 1.8; /* Line height */
        }

        /* Galeri Content Section */
        .galeri-section {
            padding: 80px 0; /* Padding vertical besar */
            background: var(--white); /* Background putih */
        }

        /* Alternate galeri section dengan background berbeda */
        .galeri-section-alt {
            background: var(--gray-50); /* Background abu-abu sangat muda */
        }

        /* Container untuk galeri content */
        .galeri-container {
            max-width: 1400px; /* Lebar maksimum */
            margin: 0 auto; /* Tengah secara horizontal */
            padding: 0 40px; /* Padding horizontal */
        }

        /* Header untuk section */
        .section-header {
            text-align: center; /* Text center */
            margin-bottom: 60px; /* Margin bawah besar */
            position: relative; /* Position relative */
        }

        /* Subtitle untuk section */
        .section-subtitle {
            font-size: 13px; /* Ukuran font kecil */
            color: var(--accent); /* Warna aksen */
            text-transform: uppercase; /* Huruf kapital */
            letter-spacing: 2.5px; /* Spasi huruf lebar */
            margin-bottom: 12px; /* Margin bawah */
            font-weight: 600; /* Ketebalan font */
            display: inline-block; /* Inline block */
            position: relative; /* Position relative */
            padding: 6px 20px; /* Padding */
            background: rgba(52, 152, 219, 0.1); /* Background transparan biru */
            border-radius: 50px; /* Border radius bulat */
        }

        /* Judul untuk section */
        .section-title {
            font-family: 'Playfair Display', serif; /* Font khusus untuk judul */
            font-size: 40px; /* Ukuran font besar */
            font-weight: 700; /* Ketebalan font */
            margin-bottom: 20px; /* Margin bawah */
            color: var(--primary); /* Warna teks primer */
            position: relative; /* Position relative untuk pseudo-element */
            display: inline-block; /* Inline block */
        }

        /* Underline effect untuk section title */
        .section-title::after {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            bottom: -12px; /* Posisi di bawah judul */
            left: 50%; /* Posisi horizontal tengah */
            transform: translateX(-50%); /* Center horizontally */
            width: 80px; /* Lebar underline */
            height: 4px; /* Tinggi underline */
            background: var(--accent); /* Warna aksen */
            border-radius: 2px; /* Border radius kecil */
        }

        /* Deskripsi untuk section */
        .section-description {
            font-size: 17px; /* Ukuran font */
            color: var(--text-light); /* Warna teks terang */
            max-width: 700px; /* Lebar maksimum */
            margin: 32px auto 0; /* Margin atas dan auto untuk horizontal */
            line-height: 1.8; /* Line height */
        }

        /* Filter Kategori */
        .kategori-filter {
            margin-bottom: 48px; /* Margin bawah */
            text-align: center; /* Text center */
        }

        /* Container untuk filter buttons */
        .filter-buttons {
            display: flex; /* Flexbox layout */
            flex-wrap: wrap; /* Wrap jika tidak cukup space */
            gap: 12px; /* Spasi antar button */
            justify-content: center; /* Center buttons */
            max-width: 900px; /* Lebar maksimum */
            margin: 0 auto; /* Tengah secara horizontal */
        }

        /* Tombol filter */
        .filter-btn {
            padding: 12px 24px; /* Padding */
            background: var(--white); /* Background putih */
            border: 2px solid var(--gray-200); /* Border */
            border-radius: 50px; /* Border radius bulat */
            color: var(--text-light); /* Warna teks terang */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            font-size: 14px; /* Ukuran font */
            font-weight: 500; /* Ketebalan font */
            position: relative; /* Position relative untuk pseudo-element */
            overflow: hidden; /* Hide overflow */
        }

        /* Pseudo-element untuk ripple effect */
        .filter-btn::before {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            top: 50%; /* Posisi vertikal tengah */
            left: 50%; /* Posisi horizontal tengah */
            width: 0; /* Lebar awal 0 */
            height: 0; /* Tinggi awal 0 */
            border-radius: 50%; /* Bentuk lingkaran */
            background: var(--accent); /* Warna aksen */
            transform: translate(-50%, -50%); /* Center */
            transition: width 0.6s, height 0.6s; /* Transisi untuk animasi ripple */
        }

        /* Ripple effect saat hover dan active */
        .filter-btn:hover::before,
        .filter-btn.active::before {
            width: 300px; /* Lebar akhir */
            height: 300px; /* Tinggi akhir */
        }

        /* Span di dalam filter button (untuk z-index) */
        .filter-btn span {
            position: relative; /* Position relative */
            z-index: 1; /* Z-index di atas pseudo-element */
            transition: color 0.3s; /* Transisi untuk warna teks */
        }

        /* Ubah warna teks saat hover dan active */
        .filter-btn:hover span,
        .filter-btn.active span {
            color: var(--white); /* Warna teks putih */
        }

        /* Hover dan active state untuk filter button */
        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--accent); /* Ubah warna border */
            transform: translateY(-2px); /* Naik sedikit */
            box-shadow: var(--shadow); /* Tambah shadow */
        }

        /* Galeri Grid */
        .galeri-grid {
            display: grid; /* CSS Grid layout */
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); /* Responsive grid */
            gap: 32px; /* Gap antar card */
            margin-bottom: 60px; /* Margin bawah */
        }

        /* Card untuk setiap galeri item */
        .galeri-card {
            background: var(--white); /* Background putih */
            border-radius: var(--radius-xl); /* Border radius besar */
            overflow: hidden; /* Hide overflow */
            box-shadow: var(--shadow); /* Shadow */
            transition: var(--transition); /* Transisi */
            position: relative; /* Position relative */
            animation: fadeIn 0.5s ease-out; /* Animasi fade in */
            border: 1px solid var(--gray-100); /* Border tipis */
        }

        /* Hover effect untuk galeri card */
        .galeri-card:hover {
            transform: translateY(-10px); /* Naik sedikit */
            box-shadow: var(--shadow-xl); /* Shadow lebih besar */
        }

        /* Top border accent untuk card */
        .galeri-card::before {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            height: 4px; /* Tinggi */
            background: linear-gradient(90deg, var(--accent), var(--accent-light)); /* Gradient background */
            z-index: 2; /* Z-index */
        }

        /* Container untuk gambar galeri */
        .galeri-image {
            position: relative; /* Position relative untuk overlay */
            overflow: hidden; /* Hide overflow */
            height: 280px; /* Fixed height */
            background: var(--gray-100); /* Background placeholder */
        }

        /* Gambar di dalam galeri card */
        .galeri-image img {
            width: 100%; /* Lebar penuh */
            height: 100%; /* Tinggi penuh */
            object-fit: cover; /* Cover container */
            transition: var(--transition-slow); /* Transisi lambat */
        }

        /* Zoom effect untuk gambar saat hover */
        .galeri-card:hover .galeri-image img {
            transform: scale(1.08); /* Zoom in sedikit */
        }

        /* Overlay untuk gambar galeri */
        .galeri-overlay {
            position: absolute; /* Position absolute */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            bottom: 0; /* Posisi bawah */
            /* Gradient overlay dari transparan ke gelap */
            background: linear-gradient(to bottom, 
                rgba(44, 62, 80, 0) 0%,
                rgba(44, 62, 80, 0.1) 30%,
                rgba(44, 62, 80, 0.9) 100%);
            display: flex; /* Flexbox layout */
            align-items: flex-end; /* Align items ke bawah */
            justify-content: center; /* Center horizontally */
            opacity: 0; /* Awalnya transparan */
            transition: var(--transition); /* Transisi */
            padding: 24px; /* Padding */
        }

        /* Tampilkan overlay saat hover */
        .galeri-card:hover .galeri-overlay {
            opacity: 1; /* Tampilkan sepenuhnya */
        }

        /* Konten di dalam overlay */
        .overlay-content {
            text-align: center; /* Text center */
            color: var(--white); /* Warna teks putih */
            transform: translateY(30px); /* Offset awal */
            transition: var(--transition); /* Transisi */
            width: 100%; /* Lebar penuh */
        }

        /* Animasi overlay content saat hover */
        .galeri-card:hover .overlay-content {
            transform: translateY(0); /* Posisi akhir */
        }

        /* Judul di dalam overlay */
        .overlay-title {
            font-size: 20px; /* Ukuran font */
            font-weight: 600; /* Ketebalan font */
            margin-bottom: 10px; /* Margin bawah */
            font-family: 'Playfair Display', serif; /* Font khusus */
        }

        /* Deskripsi di dalam overlay */
        .overlay-desc {
            font-size: 13px; /* Ukuran font kecil */
            opacity: 0.9; /* Sedikit transparan */
            margin-bottom: 16px; /* Margin bawah */
            line-height: 1.6; /* Line height */
        }

        /* Container untuk aksi di overlay */
        .overlay-actions {
            display: flex; /* Flexbox layout */
            gap: 10px; /* Spasi antar button */
            justify-content: center; /* Center buttons */
        }

        /* Button di dalam overlay */
        .overlay-btn {
            padding: 10px 20px; /* Padding */
            background: var(--accent); /* Background aksen */
            color: var(--white); /* Warna teks putih */
            border: none; /* Hilangkan border */
            border-radius: 50px; /* Border radius bulat */
            font-weight: 600; /* Ketebalan font */
            font-size: 13px; /* Ukuran font kecil */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            display: inline-flex; /* Inline flex */
            align-items: center; /* Center items vertically */
            gap: 6px; /* Spasi antar icon dan teks */
            text-decoration: none; /* Hilangkan underline */
        }

        /* Hover state untuk overlay button */
        .overlay-btn:hover {
            background: var(--accent-light); /* Background lebih terang */
            transform: translateY(-2px); /* Naik sedikit */
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3); /* Shadow dengan warna aksen */
        }

        /* Secondary overlay button */
        .overlay-btn.secondary {
            background: rgba(255, 255, 255, 0.2); /* Background transparan putih */
            backdrop-filter: blur(10px); /* Efek blur */
        }

        /* Hover state untuk secondary overlay button */
        .overlay-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3); /* Background lebih opaq */
        }

        /* Konten galeri card (di bawah gambar) */
        .galeri-content {
            padding: 24px; /* Padding */
        }

        /* Header di dalam galeri content */
        .galeri-header {
            display: flex; /* Flexbox layout */
            justify-content: space-between; /* Space between items */
            align-items: flex-start; /* Align items ke atas */
            margin-bottom: 16px; /* Margin bawah */
        }

        /* Kategori galeri */
        .galeri-category {
            font-size: 12px; /* Ukuran font kecil */
            color: var(--accent); /* Warna aksen */
            text-transform: uppercase; /* Huruf kapital */
            letter-spacing: 1.2px; /* Spasi huruf */
            font-weight: 600; /* Ketebalan font */
            margin-bottom: 6px; /* Margin bawah */
            display: block; /* Block display */
        }

        /* Judul galeri card */
        .galeri-title-card {
            font-size: 20px; /* Ukuran font */
            font-weight: 600; /* Ketebalan font */
            margin-bottom: 6px; /* Margin bawah */
            color: var(--primary); /* Warna teks primer */
            line-height: 1.3; /* Line height */
            font-family: 'Playfair Display', serif; /* Font khusus */
        }

        /* Tanggal galeri */
        .galeri-date {
            font-size: 13px; /* Ukuran font kecil */
            color: var(--text-light); /* Warna teks terang */
            font-family: 'Courier New', monospace; /* Font monospace */
            background: var(--gray-100); /* Background abu-abu muda */
            padding: 6px 12px; /* Padding */
            border-radius: var(--radius); /* Border radius */
            display: inline-block; /* Inline block */
        }

        /* Deskripsi galeri card */
        .galeri-description-card {
            font-size: 15px; /* Ukuran font */
            color: var(--text-light); /* Warna teks terang */
            line-height: 1.7; /* Line height */
            margin-bottom: 20px; /* Margin bawah */
        }

        /* View button */
        .view-btn {
            display: inline-flex; /* Inline flex */
            align-items: center; /* Center items vertically */
            justify-content: center; /* Center items horizontally */
            gap: 10px; /* Spasi antar icon dan teks */
            padding: 14px 28px; /* Padding */
            background: transparent; /* Background transparan */
            color: var(--accent); /* Warna aksen */
            text-decoration: none; /* Hilangkan underline */
            border-radius: 50px; /* Border radius bulat */
            font-weight: 600; /* Ketebalan font */
            font-size: 14px; /* Ukuran font */
            transition: var(--transition); /* Transisi */
            border: 2px solid var(--accent); /* Border dengan warna aksen */
            width: 100%; /* Lebar penuh */
            font-family: 'Inter', sans-serif; /* Font family */
        }

        /* Hover state untuk view button */
        .view-btn:hover {
            background: var(--accent); /* Background aksen */
            color: var(--white); /* Warna teks putih */
            transform: translateY(-2px); /* Naik sedikit */
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.2); /* Shadow dengan warna aksen */
        }

        /* Icon di dalam view button */
        .view-btn i {
            transition: transform 0.3s ease; /* Transisi untuk animasi */
        }

        /* Animasi icon saat hover */
        .view-btn:hover i {
            transform: translateX(5px); /* Geser ke kanan */
        }

        /* Modal for Image Preview */
        .modal-overlay {
            position: fixed; /* Position fixed untuk overlay */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            bottom: 0; /* Posisi bawah */
            background: rgba(44, 62, 80, 0.95); /* Background gelap semi transparan */
            display: none; /* Default hidden */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            z-index: 99999; /* Z-index sangat tinggi */
            backdrop-filter: blur(10px); /* Efek blur */
        }

        /* Modal overlay ketika aktif */
        .modal-overlay.active {
            display: flex; /* Tampilkan ketika aktif */
            animation: fadeIn 0.3s ease-out; /* Animasi fade in */
        }

        /* Konten modal */
        .modal-content {
            position: relative; /* Position relative */
            max-width: 90vw; /* Lebar maksimum 90% viewport */
            max-height: 90vh; /* Tinggi maksimum 90% viewport */
            background: var(--white); /* Background putih */
            border-radius: var(--radius-2xl); /* Border radius besar */
            overflow: hidden; /* Hide overflow */
            box-shadow: var(--shadow-xl); /* Shadow besar */
            animation: scaleIn 0.3s ease-out; /* Animasi scale in */
        }

        /* Gambar di dalam modal */
        .modal-image {
            width: 100%; /* Lebar penuh */
            height: 70vh; /* Tinggi 70% viewport */
            object-fit: contain; /* Contain gambar */
            background: var(--gray-50); /* Background placeholder */
        }

        /* Info di dalam modal (di bawah gambar) */
        .modal-info {
            padding: 24px; /* Padding */
            background: var(--white); /* Background putih */
        }

        /* Tombol close untuk modal */
        .modal-close {
            position: absolute; /* Position absolute */
            top: 16px; /* Posisi atas */
            right: 16px; /* Posisi kanan */
            width: 40px; /* Lebar */
            height: 40px; /* Tinggi */
            background: var(--white); /* Background putih */
            border: none; /* Hilangkan border */
            border-radius: 50%; /* Bentuk lingkaran */
            font-size: 18px; /* Ukuran icon */
            color: var(--primary); /* Warna icon */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center icon vertically */
            justify-content: center; /* Center icon horizontally */
            box-shadow: var(--shadow); /* Shadow */
            z-index: 1; /* Z-index di atas gambar */
        }

        /* Hover state untuk modal close */
        .modal-close:hover {
            background: var(--accent); /* Background aksen */
            color: var(--white); /* Warna icon putih */
            transform: rotate(90deg); /* Rotasi 90 derajat */
        }

        /* Empty State (ketika tidak ada data) */
        .empty-state {
            grid-column: 1 / -1; /* Span semua kolom grid */
            text-align: center; /* Text center */
            padding: 80px 0; /* Padding vertical besar */
        }

        /* Icon untuk empty state */
        .empty-state-icon {
            font-size: 60px; /* Ukuran icon besar */
            color: var(--gray-200); /* Warna abu-abu */
            margin-bottom: 20px; /* Margin bawah */
            display: inline-block; /* Inline block */
        }

        /* Judul untuk empty state */
        .empty-state h3 {
            font-size: 24px; /* Ukuran font */
            color: var(--text-dark); /* Warna teks gelap */
            margin-bottom: 12px; /* Margin bawah */
            font-weight: 600; /* Ketebalan font */
            font-family: 'Playfair Display', serif; /* Font khusus */
        }

        /* Deskripsi untuk empty state */
        .empty-state p {
            font-size: 16px; /* Ukuran font */
            color: var(--text-light); /* Warna teks terang */
            max-width: 400px; /* Lebar maksimum */
            margin: 0 auto 32px; /* Margin auto untuk horizontal, bawah 32px */
        }

        /* Reset button untuk empty state */
        .reset-btn {
            padding: 14px 32px; /* Padding */
            background: var(--accent); /* Background aksen */
            color: var(--white); /* Warna teks putih */
            border: none; /* Hilangkan border */
            border-radius: 50px; /* Border radius bulat */
            font-weight: 600; /* Ketebalan font */
            font-size: 14px; /* Ukuran font */
            cursor: pointer; /* Pointer cursor */
            transition: var(--transition); /* Transisi */
            display: inline-flex; /* Inline flex */
            align-items: center; /* Center items vertically */
            gap: 8px; /* Spasi antar icon dan teks */
            text-decoration: none; /* Hilangkan underline */
        }

        /* Hover state untuk reset button */
        .reset-btn:hover {
            background: var(--accent-light); /* Background lebih terang */
            transform: translateY(-2px); /* Naik sedikit */
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3); /* Shadow dengan warna aksen */
        }

        /* Loading Animation */
        .loading {
            position: fixed; /* Position fixed */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            bottom: 0; /* Posisi bawah */
            background: var(--white); /* Background putih */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
            z-index: 9999; /* Z-index tinggi */
            transition: opacity 0.3s ease; /* Transisi untuk opacity */
        }

        /* Loading ketika hidden */
        .loading.hidden {
            opacity: 0; /* Transparan */
            pointer-events: none; /* Non-aktifkan pointer events */
        }

        /* Spinner animation */
        .spinner {
            width: 50px; /* Lebar spinner */
            height: 50px; /* Tinggi spinner */
            border: 3px solid var(--gray-200); /* Border abu-abu */
            border-top: 3px solid var(--accent); /* Border atas dengan warna aksen */
            border-radius: 50%; /* Bentuk lingkaran */
            animation: spin 1s linear infinite; /* Animasi spin */
        }

        /* Footer */
        .footer {
            background: var(--primary); /* Background primer */
            color: var(--white); /* Warna teks putih */
            padding: 60px 0 30px; /* Padding vertical */
            position: relative; /* Position relative untuk pseudo-element */
        }

        /* Top border untuk footer */
        .footer::before {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            top: 0; /* Posisi atas */
            left: 0; /* Posisi kiri */
            right: 0; /* Posisi kanan */
            height: 1px; /* Tinggi tipis */
            /* Gradient border */
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        /* Container untuk footer */
        .footer-container {
            max-width: 1400px; /* Lebar maksimum */
            margin: 0 auto; /* Tengah secara horizontal */
            padding: 0 40px; /* Padding horizontal */
            display: grid; /* CSS Grid layout */
            grid-template-columns: 2fr repeat(3, 1fr); /* Grid columns */
            gap: 40px; /* Gap antar kolom */
        }

        /* Judul untuk footer column */
        .footer-col h4 {
            font-size: 18px; /* Ukuran font */
            margin-bottom: 20px; /* Margin bawah */
            font-weight: 600; /* Ketebalan font */
            color: var(--white); /* Warna teks putih */
            position: relative; /* Position relative untuk pseudo-element */
            display: inline-block; /* Inline block */
            font-family: 'Playfair Display', serif; /* Font khusus */
        }

        /* Underline untuk footer column title */
        .footer-col h4::after {
            content: ''; /* Pseudo-element kosong */
            position: absolute; /* Position absolute */
            bottom: -6px; /* Posisi di bawah teks */
            left: 0; /* Posisi kiri */
            width: 40px; /* Lebar underline */
            height: 2px; /* Tinggi underline */
            background: var(--accent); /* Warna aksen */
        }

        /* Paragraf di dalam footer column */
        .footer-col p {
            font-size: 14px; /* Ukuran font kecil */
            opacity: 0.9; /* Sedikit transparan */
            margin-bottom: 20px; /* Margin bawah */
            line-height: 1.7; /* Line height */
        }

        /* Link di dalam footer column */
        .footer-col a {
            color: rgba(255, 255, 255, 0.8); /* Warna teks putih transparan */
            text-decoration: none; /* Hilangkan underline */
            display: block; /* Block display */
            margin-bottom: 12px; /* Margin bawah */
            transition: var(--transition); /* Transisi */
            position: relative; /* Position relative untuk pseudo-element */
            padding-left: 0; /* Padding kiri awal */
            font-size: 14px; /* Ukuran font kecil */
        }

        /* Pseudo-element arrow untuk link */
        .footer-col a::before {
            content: '→'; /* Arrow character */
            position: absolute; /* Position absolute */
            left: 0; /* Posisi kiri */
            opacity: 0; /* Awalnya transparan */
            transition: var(--transition); /* Transisi */
        }

        /* Hover state untuk footer link */
        .footer-col a:hover {
            color: var(--accent); /* Warna aksen */
            padding-left: 20px; /* Tambah padding kiri */
        }

        /* Tampilkan arrow saat hover */
        .footer-col a:hover::before {
            opacity: 1; /* Tampilkan arrow */
        }

        /* Social links container */
        .social-links {
            display: flex; /* Flexbox layout */
            gap: 12px; /* Spasi antar icon */
            margin-top: 20px; /* Margin atas */
        }

        /* Social link item */
        .social-links a {
            width: 40px; /* Lebar tetap */
            height: 40px; /* Tinggi tetap */
            border-radius: 50%; /* Bentuk lingkaran */
            background: rgba(255, 255, 255, 0.1); /* Background transparan */
            display: flex; /* Flexbox layout */
            align-items: center; /* Center icon vertically */
            justify-content: center; /* Center icon horizontally */
            transition: var(--transition); /* Transisi */
            color: var(--white); /* Warna icon putih */
            text-decoration: none; /* Hilangkan underline */
            font-size: 16px; /* Ukuran icon */
            margin-bottom: 0; /* Hilangkan margin bawah */
        }

        /* Hover state untuk social link */
        .social-links a:hover {
            background: var(--accent); /* Background aksen */
            transform: translateY(-3px); /* Naik sedikit */
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3); /* Shadow dengan warna aksen */
        }

        /* Copyright section */
        .copyright {
            text-align: center; /* Text center */
            padding-top: 40px; /* Padding atas */
            margin-top: 40px; /* Margin atas */
            border-top: 1px solid rgba(255, 255, 255, 0.1); /* Border atas tipis */
            font-size: 13px; /* Ukuran font kecil */
            color: rgba(255, 255, 255, 0.6); /* Warna teks transparan */
        }

        /* Animations */
        /* Fade in animation */
        @keyframes fadeIn {
            from {
                opacity: 0; /* Awalnya transparan */
            }
            to {
                opacity: 1; /* Akhirnya opaque */
            }
        }

        /* Fade in up animation */
        @keyframes fadeInUp {
            from {
                opacity: 0; /* Awalnya transparan */
                transform: translateY(40px); /* Offset ke bawah */
            }
            to {
                opacity: 1; /* Akhirnya opaque */
                transform: translateY(0); /* Posisi akhir */
            }
        }

        /* Scale in animation */
        @keyframes scaleIn {
            from {
                opacity: 0; /* Awalnya transparan */
                transform: scale(0.9); /* Scale kecil */
            }
            to {
                opacity: 1; /* Akhirnya opaque */
                transform: scale(1); /* Scale normal */
            }
        }

        /* Spin animation untuk loading spinner */
        @keyframes spin {
            0% { transform: rotate(0deg); } /* Rotasi awal */
            100% { transform: rotate(360deg); } /* Rotasi penuh */
        }

        /* Responsive Design */
        /* Untuk layar hingga 1200px */
        @media (max-width: 1200px) {
            /* Adjust padding untuk container */
            .nav-container,
            .galeri-hero-container,
            .galeri-container,
            .footer-container {
                padding: 0 32px; /* Kurangi padding horizontal */
            }
            
            /* Adjust font size untuk judul */
            .galeri-title,
            .section-title {
                font-size: 42px; /* Kurangi ukuran font */
            }
            
            /* Adjust grid layout */
            .galeri-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Lebar kolom lebih kecil */
                gap: 24px; /* Kurangi gap */
            }
            
            
        }

        /* Untuk layar hingga 992px (tablet) */
        @media (max-width: 992px) {
            /* Ubah grid menjadi 2 kolom */
            .galeri-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 kolom */
            }
            
            /* Ubah footer grid menjadi 2 kolom */
            .footer-container {
                grid-template-columns: repeat(2, 1fr); /* 2 kolom */
                gap: 32px; /* Kurangi gap */
            }
            
            /* NAVBAR MOBILE SEPERTI DASHBOARD */
            .nav-main {
                display: none; /* Sembunyikan main nav untuk mobile */
            }
            
            /* Tampilkan mobile menu button */
            .mobile-menu-btn {
                display: flex; /* Tampilkan mobile menu button */
                order: 3; /* Urutan ketiga */
            }
            
            /* Adjust nav actions */
            .nav-actions {
                order: 2; /* Urutan kedua */
                gap: 15px; /* Kurangi gap */
            }
            
            
            /* Adjust user menu order */
            .user-menu {
                order: 1; /* Urutan pertama */
            }
            
            /* Adjust user avatar size */
            .user-avatar {
                width: 40px; /* Lebar lebih kecil */
                height: 40px; /* Tinggi lebih kecil */
                font-size: 15px; /* Font size lebih kecil */
            }
        }

        /* Untuk layar hingga 768px */
        @media (max-width: 768px) {
            /* Adjust navbar container */
            .nav-container {
                padding: 0 24px; /* Kurangi padding */
                height: 70px; /* Tinggi lebih kecil */
            }
            
            /* Adjust galeri hero padding */
            .galeri-hero {
                padding: 120px 0 60px; /* Kurangi padding */
            }
            
            /* Adjust font size untuk judul */
            .galeri-title,
            .section-title {
                font-size: 32px; /* Ukuran lebih kecil */
            }
            
            /* Adjust section padding */
            .galeri-section {
                padding: 60px 0; /* Kurangi padding */
            }
            
            /* Ubah grid menjadi 1 kolom */
            .galeri-grid {
                grid-template-columns: 1fr; /* 1 kolom */
                gap: 24px; /* Kurangi gap */
            }
            
            /* Adjust filter buttons */
            .filter-buttons {
                justify-content: flex-start; /* Start dari kiri */
                overflow-x: auto; /* Horizontal scroll jika tidak cukup space */
                padding-bottom: 8px; /* Padding bawah untuk scrollbar */
            }
            
            
        }

        /* Untuk layar hingga 576px (mobile kecil) */
        @media (max-width: 576px) {
            /* Adjust container padding */
            .nav-container,
            .galeri-hero-container,
            .galeri-container,
            .footer-container {
                padding: 0 20px; /* Padding lebih kecil */
            }
            
            /* Adjust font size untuk judul */
            .galeri-title,
            .section-title {
                font-size: 28px; /* Ukuran lebih kecil */
            }
            
            /* Adjust galeri badge */
            .galeri-badge {
                font-size: 12px; /* Font size lebih kecil */
                padding: 6px 12px; /* Padding lebih kecil */
            }
            
            /* Adjust description font size */
            .galeri-description,
            .section-description {
                font-size: 15px; /* Font size lebih kecil */
            }
            
            /* Ubah footer grid menjadi 1 kolom */
            .footer-container {
                grid-template-columns: 1fr; /* 1 kolom */
                gap: 32px; /* Gap */
            }
            
            /* Adjust logo image size */
            .logo img {
                height: 35px; /* Tinggi lebih kecil */
            }
            
            /* Adjust filter button */
            .filter-btn {
                padding: 10px 20px; /* Padding lebih kecil */
                font-size: 13px; /* Font size lebih kecil */
            }
        }
    </style>
</head>
<body>

    <!-- Loading Screen - Menampilkan spinner saat loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div> <!-- Spinner animation -->
    </div>

    <!-- Image Preview Modal - Modal untuk preview gambar -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-content">
            <button class="modal-close" id="modalClose">
                <i class="fas fa-times"></i> <!-- Close icon -->
            </button>
            <img src="" alt="Preview" class="modal-image" id="modalImage"> <!-- Gambar preview -->
            <div class="modal-info">
                <h3 id="modalTitle"></h3> <!-- Judul gambar -->
                <p id="modalDescription"></p> <!-- Deskripsi gambar -->
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay - Menu navigasi untuk mobile -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i> <!-- Close icon -->
        </button>
        
        
        
        <nav class="mobile-nav">
            <!-- Navigasi mobile dengan dropdown -->
            <a href="user_dashboard.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-home"></i> <!-- Home icon -->
                    Beranda
                </span>
                <i class="fas fa-chevron-right"></i> <!-- Right chevron -->
            </a>
            
            <a href="koleksi.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'koleksi.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-th-large"></i> <!-- Grid icon -->
                    Koleksi
                </span>
                <i class="fas fa-chevron-right"></i> <!-- Right chevron -->
            </a>
            
            <!-- Acara Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['berita_karir.php', 'berita_kegiatan.php', 'berita_galeri.php']) ? 'active' : '' ?>">
                    <span>
                        <i class="fas fa-calendar-alt"></i> <!-- Calendar icon -->
                        Acara
                    </span>
                    <i class="fas fa-chevron-down"></i> <!-- Dropdown chevron -->
                </a>
                <div class="mobile-dropdown-content" id="mobileAcaraDropdown">
                    <a href="berita_karir.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_karir.php' ? 'active' : '' ?>">
                        <i class="fas fa-briefcase"></i> <!-- Briefcase icon -->
                        Karir
                    </a>
                    <a href="berita_kegiatan.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> <!-- Users icon -->
                        Kegiatan
                    </a>
                    <a href="berita_galeri.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_galeri.php' ? 'active' : '' ?>">
                        <i class="fas fa-image"></i> <!-- Image icon -->
                        Galeri
                    </a>
                </div>
            </div>
            
            <!-- Tentang Kami Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['tentang_profil.php', 'tentang_visimisi.php']) ? 'active' : '' ?>">
                    <span>
                        <i class="fas fa-info-circle"></i> <!-- Info icon -->
                        Tentang Kami
                    </span>
                    <i class="fas fa-chevron-down"></i> <!-- Dropdown chevron -->
                </a>
                <div class="mobile-dropdown-content" id="mobileTentangDropdown">
                    <a href="tentang_profil.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'tentang_profil.php' ? 'active' : '' ?>">
                        <i class="fas fa-building"></i> <!-- Building icon -->
                        Profil Perusahaan
                    </a>
                    <a href="tentang_visimisi.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'tentang_visimisi.php' ? 'active' : '' ?>">
                        <i class="fas fa-bullseye"></i> <!-- Target icon -->
                        Visi & Misi
                    </a>
                </div>
            </div>
            
            <a href="kontak.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-address-book"></i> <!-- Address book icon -->
                    Kontak
                </span>
                <i class="fas fa-chevron-right"></i> <!-- Right chevron -->
            </a>
            
            
            
            <!-- User Menu Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle">
                    <span>
                        <i class="fas fa-user-circle"></i> <!-- User icon -->
                        <?= htmlspecialchars($namaLengkap) ?> <!-- Nama user -->
                    </span>
                    <i class="fas fa-chevron-down"></i> <!-- Dropdown chevron -->
                </a>
                <div class="mobile-dropdown-content">
                    <a href="ganti_password.php" class="mobile-dropdown-link">
                        <i class="fas fa-key"></i> <!-- Key icon -->
                        Ganti Sandi
                    </a>
                    <a href="../logout.php" class="mobile-dropdown-link" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> <!-- Logout icon -->
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Header & Navigation - SAMA DENGAN DASHBOARD -->
    <header class="header" id="header">
        <div class="nav-container">
            <!-- Logo -->
            <a href="user_dashboard.php" class="logo">
                <img src="<?= getLocalImage('logo small black.png') ?>" alt="PT INTIM"> <!-- Logo perusahaan -->
            </a>

            <!-- Main Navigation -->
            <nav class="nav-main">
                <a href="user_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> <!-- Home icon -->
                    <span>Beranda</span>
                </a>
                <a href="koleksi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'koleksi.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> <!-- Grid icon -->
                    <span>Koleksi</span>
                </a>
                
                <!-- Acara Dropdown (Karir + Kegiatan) -->
                <div class="nav-dropdown" id="acaraDropdown">
                    <div class="dropdown-toggle nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['berita_karir.php', 'berita_kegiatan.php', 'berita_galeri.php']) ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> <!-- Calendar icon -->
                        <span>Acara</span>
                        <i class="fas fa-chevron-down"></i> <!-- Dropdown chevron -->
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="berita_karir.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_karir.php' ? 'active' : '' ?>">
                                <i class="fas fa-briefcase"></i> <!-- Briefcase icon -->
                                <span>Karir</span>
                            </a>
                            <a href="berita_kegiatan.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                                <i class="fas fa-users"></i> <!-- Users icon -->
                                <span>Kegiatan</span>
                            </a>
                            <a href="berita_galeri.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_galeri.php' ? 'active' : '' ?>">
                                <i class="fas fa-image"></i> <!-- Image icon -->
                                <span>Galeri</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tentang Kami Dropdown (Profil Perusahaan + Visi & Misi) -->
                <div class="nav-dropdown" id="tentangDropdown">
                    <div class="dropdown-toggle nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['tentang_profil.php', 'tentang_visimisi.php']) ? 'active' : '' ?>">
                        <i class="fas fa-info-circle"></i> <!-- Info icon -->
                        <span>Tentang Kami</span>
                        <i class="fas fa-chevron-down"></i> <!-- Dropdown chevron -->
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="tentang_profil.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'tentang_profil.php' ? 'active' : '' ?>">
                                <i class="fas fa-building"></i> <!-- Building icon -->
                                <span>Profil Perusahaan</span>
                            </a>
                            <a href="tentang_visimisi.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'tentang_visimisi.php' ? 'active' : '' ?>">
                                <i class="fas fa-bullseye"></i> <!-- Target icon -->
                                <span>Visi & Misi</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kontak -->
                <a href="kontak.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : '' ?>">
                    <i class="fas fa-address-book"></i> <!-- Address book icon -->
                    <span>Kontak</span>
                </a>
                
                
            </nav>

            <!-- Navigation Actions -->
            <div class="nav-actions">
                

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i> <!-- Hamburger menu icon -->
                </button>

                <!-- User Menu -->
                <div class="user-menu" id="userMenu">
                    <div class="user-avatar" id="userAvatar">
                        <?= strtoupper(substr($namaLengkap, 0, 1)) ?> <!-- Inisial user -->
                    </div>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-info-avatar">
                                <?= strtoupper(substr($namaLengkap, 0, 1)) ?> <!-- Inisial user -->
                            </div>
                            <div class="user-details">
                                <h4><?= htmlspecialchars($namaLengkap) ?></h4> <!-- Nama user -->
                                <p>User Dashboard</p> <!-- Role user -->
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="ganti_password.php" class="user-action-item">
                                <i class="fas fa-key"></i> <!-- Key icon -->
                                <span>Ganti Sandi</span>
                            </a>
                            <a href="../logout.php" class="user-action-item logout" onclick="return confirm('Yakin ingin logout?')">
                                <i class="fas fa-sign-out-alt"></i> <!-- Logout icon -->
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Galeri Hero - Bagian hero dengan judul dan deskripsi -->
    <section class="galeri-hero" id="galeri">
        <div class="galeri-hero-bg"></div> <!-- Background effect -->
        <div class="galeri-hero-container">
            <div class="galeri-badge">
                <i class="fas fa-images"></i> <!-- Images icon -->
                <span>Koleksi Foto</span>
            </div>
            <h1 class="galeri-title">Galeri Kegiatan</h1> <!-- Judul utama -->
            <p class="galeri-description">
                Jelajahi momen-momen berharga dari berbagai kegiatan dan acara PT INTIM. 
                Setiap foto menceritakan kisah dedikasi, inovasi, dan pencapaian kami.
            </p> <!-- Deskripsi -->
        </div>
    </section>

    
    <!-- Galeri Content - Bagian utama menampilkan galeri -->
    <section class="galeri-section">
        <div class="galeri-container">
            <div class="section-header">
                <span class="section-subtitle">Momen Berharga</span> <!-- Subtitle -->
                <h2 class="section-title">Koleksi Foto Kegiatan</h2> <!-- Judul section -->
                <p class="section-description">
                    Dokumentasi visual dari berbagai acara, kegiatan produksi, kunjungan, dan momen penting lainnya di PT INTIM.
                </p> <!-- Deskripsi section -->
            </div>

            <!-- Filter Kategori - Tombol filter kategori galeri -->
            <div class="kategori-filter">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">
                        <span>Semua Kegiatan</span> <!-- Filter semua -->
                    </button>
                    <?php 
                    if ($kategoriResult && mysqli_num_rows($kategoriResult) > 0): // Jika ada kategori
                        mysqli_data_seek($kategoriResult, 0); // Reset pointer result set
                        while($kategori = mysqli_fetch_assoc($kategoriResult)): // Loop kategori
                            if (!empty($kategori['kategori'])) { // Hanya jika kategori tidak kosong
                    ?>
                        <button class="filter-btn" data-filter="<?= htmlspecialchars($kategori['kategori']) ?>">
                            <span><?= htmlspecialchars($kategori['kategori']) ?></span> <!-- Nama kategori -->
                        </button>
                    <?php 
                            }
                        endwhile;
                    endif; 
                    ?>
                </div>
            </div>

            <!-- Galeri Grid - Grid untuk menampilkan kartu galeri -->
            <div class="galeri-grid" id="galeriGrid">
                <?php if ($result && mysqli_num_rows($result) > 0): // Jika ada data galeri
                    mysqli_data_seek($result, 0); // Reset pointer result set
                    while($row = mysqli_fetch_assoc($result)) : // Loop setiap galeri
                        $gambar = getLocalImage($row['gambar'], 'logo.png'); // Dapatkan path gambar
                        $tanggal = date('d M Y', strtotime($row['tanggal'])); // Format tanggal
                ?>
                    <div class="galeri-card" data-kategori="<?= htmlspecialchars($row['kategori'] ?? '') ?>"> <!-- Data attribute untuk filtering -->
                        <div class="galeri-image">
                            <img src="<?= htmlspecialchars($gambar) ?>" 
                                 alt="<?= htmlspecialchars($row['judul'] ?? '') ?>"> <!-- Gambar galeri -->
                            <div class="galeri-overlay">
                                <div class="overlay-content">
                                    <h3 class="overlay-title"><?= htmlspecialchars($row['judul'] ?? '') ?></h3> <!-- Judul di overlay -->
                                    <p class="overlay-desc"><?= htmlspecialchars($row['kategori'] ?? '') ?></p> <!-- Kategori di overlay -->
                                    <div class="overlay-actions">
                                        <button class="overlay-btn preview-image"
                                                data-img="<?= htmlspecialchars($gambar) ?>"
                                                data-title="<?= htmlspecialchars($row['judul'] ?? '') ?>"
                                                data-desc="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>">
                                            <i class="fas fa-expand"></i> <!-- Expand icon -->
                                            Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="galeri-content">
                            <div class="galeri-header">
                                <div>
                                    <span class="galeri-category"><?= htmlspecialchars($row['kategori'] ?? '') ?></span> <!-- Kategori -->
                                    <h3 class="galeri-title-card"><?= htmlspecialchars($row['judul'] ?? '') ?></h3> <!-- Judul -->
                                </div>
                                <span class="galeri-date"><?= $tanggal ?></span> <!-- Tanggal -->
                            </div>
                            
                            <p class="galeri-description-card">
                                <?= htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 100)) ?>... <!-- Deskripsi singkat -->
                            </p>
                            
                            <button class="view-btn preview-image"
                                    data-img="<?= htmlspecialchars($gambar) ?>"
                                    data-title="<?= htmlspecialchars($row['judul'] ?? '') ?>"
                                    data-desc="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>">
                                Lihat Detail
                                <i class="fas fa-eye"></i> <!-- Eye icon -->
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Company Info - Bagian informasi perusahaan -->
    <section class="galeri-section galeri-section-alt">
        <div class="galeri-container">
            <div class="section-header">
                <span class="section-subtitle">Tentang Kami</span> <!-- Subtitle -->
                <h2 class="section-title">PT INTIM</h2> <!-- Judul -->
                <p class="section-description">
                    PT Innagroup Textile Manufacture merupakan perusahaan tekstil yang berkomitmen untuk 
                    menghasilkan produk berkualitas tinggi dengan standar internasional.
                </p> <!-- Deskripsi perusahaan -->
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <div style="max-width: 800px; margin: 0 auto;">
                    <p style="font-size: 16px; color: var(--text-light); line-height: 1.8; margin-bottom: 24px;">
                        Kami percaya bahwa setiap momen adalah kesempatan untuk belajar, berkembang, dan menciptakan nilai. 
                        Galeri ini menampilkan perjalanan kami dalam mewujudkan visi menjadi perusahaan tekstil terkemuka.
                    </p>
                    <p style="font-size: 16px; color: var(--text-light); line-height: 1.8;">
                        Dari proses produksi yang teliti hingga kegiatan corporate social responsibility, 
                        setiap aktivitas kami dijalankan dengan semangat inovasi dan komitmen terhadap kualitas.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <h4>PT INTIM</h4> <!-- Judul footer column -->
                <p>
                    Menghadirkan kain premium dengan kualitas terbaik sejak 1990. 
                    Komitmen kami adalah memberikan produk terbaik dengan sentuhan seni tradisional.
                </p> <!-- Deskripsi perusahaan -->
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a> <!-- Instagram -->
                    <a href="#"><i class="fab fa-facebook-f"></i></a> <!-- Facebook -->
                    <a href="#"><i class="fab fa-twitter"></i></a> <!-- Twitter -->
                    <a href="#"><i class="fab fa-linkedin-in"></i></a> <!-- LinkedIn -->
                    <a href="#"><i class="fab fa-youtube"></i></a> <!-- YouTube -->
                </div>
            </div>

            <div class="footer-col">
                <h4>Kategori Galeri</h4> <!-- Judul kategori galeri -->
                <a href="#" class="filter-link" data-filter="all">Semua Galeri</a> <!-- Filter semua -->
                <?php 
                if ($kategoriResult && mysqli_num_rows($kategoriResult) > 0): // Jika ada kategori
                    mysqli_data_seek($kategoriResult, 0); // Reset pointer result set
                    while($kategori = mysqli_fetch_assoc($kategoriResult)): // Loop kategori
                        if (!empty($kategori['kategori'])) { // Hanya jika kategori tidak kosong
                ?>
                    <a href="#" class="filter-link" data-filter="<?= htmlspecialchars($kategori['kategori']) ?>">
                        <?= htmlspecialchars($kategori['kategori']) ?> <!-- Nama kategori -->
                    </a>
                <?php 
                        }
                    endwhile;
                endif; 
                ?>
            </div>
            
            <div class="footer-col">
                <h4>Menu</h4> <!-- Judul menu -->
                <a href="user_dashboard.php">Beranda</a>
                <a href="koleksi.php">Koleksi</a>
                <a href="berita_karir.php">Karir</a>
                <a href="berita_kegiatan.php">Kegiatan</a>
                <a href="berita_galeri.php">Galeri</a>
                <a href="tentang_profil.php">Profil Perusahaan</a>
                <a href="tentang_visimisi.php">Visi & Misi</a>
                <a href="kontak.php">Kontak</a>
            </div>
            
            <div class="footer-col">
                <h4>Kontak</h4> <!-- Judul kontak -->
                <p><i class="fas fa-map-marker-alt"></i> Jl. Karangwuni-Dlimas, Ceper, Klaten</p> <!-- Alamat -->
                <p><i class="fas fa-phone"></i> (0272) 5533 488</p> <!-- Telepon -->
                <p><i class="fas fa-envelope"></i> headoffice@innagrouptm.com</p> <!-- Email -->
                <p><i class="fas fa-clock"></i> Senin - Jumat: 08:00 - 17:00</p> <!-- Jam operasional -->
            </div>
            
            
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> PT Innagroup Textile Manufacture. All rights reserved. <!-- Copyright -->
        </div>
    </footer>

    <script>
        // Loading Screen - Script untuk menghilangkan loading screen
        window.addEventListener('load', function() {
            const loading = document.getElementById('loading');
            loading.classList.add('hidden'); // Tambah class hidden
            setTimeout(() => {
                loading.style.display = 'none'; // Sembunyikan setelah transisi
            }, 300);
        });

        // Header Scroll Effect - Efek scroll pada header
        const header = document.getElementById('header');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled'); // Tambah class scrolled saat scroll > 50px
            } else {
                header.classList.remove('scrolled'); // Hapus class scrolled saat scroll <= 50px
            }
        });

        // Enhanced Dropdown Functionality (SAMA DENGAN DASHBOARD)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdown toggles
            const dropdownToggles = document.querySelectorAll('.nav-dropdown .dropdown-toggle');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault(); // Cegah default behavior
                    e.stopPropagation(); // Stop event bubbling
                    
                    const dropdown = this.closest('.nav-dropdown');
                    const isActive = dropdown.classList.contains('active');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.nav-dropdown').forEach(d => {
                        d.classList.remove('active');
                    });
                    
                    // Open current dropdown if not active
                    if (!isActive) {
                        dropdown.classList.add('active');
                    }
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-dropdown')) {
                    document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
            
            // Handle user menu separately
            const userAvatar = document.getElementById('userAvatar');
            const userMenu = document.getElementById('userMenu');
            
            if (userAvatar && userMenu) {
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation(); // Stop event bubbling
                    userMenu.classList.toggle('active'); // Toggle active class
                    
                    // Close other dropdowns
                    document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                });
            }
            
            
        });

        // Mobile Menu Functionality - SAMA DENGAN DASHBOARD
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose = document.getElementById('mobileMenuClose');

        if (mobileMenuBtn && mobileMenuOverlay) {
            mobileMenuBtn.addEventListener('click', function() {
                this.classList.toggle('active'); // Toggle active class untuk button
                mobileMenuOverlay.classList.toggle('active'); // Toggle active class untuk overlay
                document.body.style.overflow = 'hidden'; // Non-aktifkan scroll body
            });
        }

        if (mobileMenuClose && mobileMenuOverlay) {
            mobileMenuClose.addEventListener('click', function() {
                mobileMenuBtn.classList.remove('active'); // Hapus active class dari button
                mobileMenuOverlay.classList.remove('active'); // Hapus active class dari overlay
                document.body.style.overflow = ''; // Aktifkan kembali scroll body
            });
        }

        // Mobile dropdown toggle functionality
        document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault(); // Cegah default behavior
                e.stopPropagation(); // Stop event bubbling
                
                const dropdown = this.closest('.mobile-dropdown');
                const content = dropdown.querySelector('.mobile-dropdown-content');
                const isActive = content.classList.contains('active');
                
                // Close all other dropdowns
                document.querySelectorAll('.mobile-dropdown-content').forEach(dc => {
                    dc.classList.remove('active');
                });
                
                // Close all chevrons
                document.querySelectorAll('.mobile-dropdown-toggle .fa-chevron-down').forEach(chevron => {
                    chevron.style.transform = 'rotate(0deg)'; // Reset rotasi chevron
                });
                
                // Toggle current dropdown
                if (!isActive) {
                    content.classList.add('active'); // Tampilkan dropdown content
                    this.querySelector('.fa-chevron-down').style.transform = 'rotate(180deg)'; // Rotasi chevron
                } else {
                    content.classList.remove('active'); // Sembunyikan dropdown content
                    this.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)'; // Reset rotasi chevron
                }
            });
        });

        // Close mobile menu when clicking on links
        document.querySelectorAll('.mobile-nav-link, .mobile-dropdown-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.classList.contains('mobile-dropdown-toggle')) {
                    setTimeout(() => {
                        mobileMenuBtn.classList.remove('active');
                        mobileMenuOverlay.classList.remove('active');
                        document.body.style.overflow = ''; // Aktifkan kembali scroll body
                    }, 300);
                }
            });
        });

        // Close mobile menu when clicking outside
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', function(e) {
                if (e.target === mobileMenuOverlay) {
                    mobileMenuBtn.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = ''; // Aktifkan kembali scroll body
                }
            });
        }

        // Image Preview Modal - Script untuk membuka modal preview gambar
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');
        const modalClose = document.getElementById('modalClose');

        // Preview image buttons
        document.querySelectorAll('.preview-image').forEach(button => {
            button.addEventListener('click', function() {
                const imgSrc = this.getAttribute('data-img'); // Dapatkan src gambar dari data attribute
                const title = this.getAttribute('data-title'); // Dapatkan judul dari data attribute
                const desc = this.getAttribute('data-desc'); // Dapatkan deskripsi dari data attribute
                
                modalImage.src = imgSrc; // Set src gambar modal
                modalTitle.textContent = title; // Set judul modal
                modalDescription.textContent = desc; // Set deskripsi modal
                imageModal.classList.add('active'); // Tampilkan modal
                document.body.style.overflow = 'hidden'; // Non-aktifkan scroll body
            });
        });

        // Close modal
        if (modalClose && imageModal) {
            modalClose.addEventListener('click', function() {
                imageModal.classList.remove('active'); // Sembunyikan modal
                document.body.style.overflow = ''; // Aktifkan kembali scroll body
            });
        }

        // Close modal when clicking outside
        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    imageModal.classList.remove('active'); // Sembunyikan modal jika klik di luar konten
                    document.body.style.overflow = ''; // Aktifkan kembali scroll body
                }
            });
        };

        // Filter Galeri - Fungsi untuk filter kategori galeri
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter'); // Dapatkan nilai filter
                const galeriCards = document.querySelectorAll('.galeri-card');
                
                let visibleCount = 0; // Counter untuk kartu yang ditampilkan
                
                galeriCards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-kategori') === filter) {
                        // Tampilkan kartu yang sesuai filter
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 10);
                        visibleCount++;
                    } else {
                        // Sembunyikan kartu yang tidak sesuai filter dengan animasi
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
                
                // Cek apakah ada kartu yang ditampilkan
                setTimeout(() => {
                    const galeriGrid = document.getElementById('galeriGrid');
                    const existingEmptyState = galeriGrid.querySelector('.empty-state');
                    
                    // Jika tidak ada kartu yang ditampilkan dan belum ada empty state
                    if (visibleCount === 0 && !existingEmptyState) {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'empty-state';
                        emptyState.innerHTML = `
                            <div class="empty-state-icon">
                                <i class="fas fa-images"></i>
                            </div>
                            <h3>Tidak Ada Galeri</h3>
                            <p>Tidak ada galeri dalam kategori "${this.textContent.trim()}".</p>
                            <button class="reset-btn" id="resetFilterBtn">
                                <i class="fas fa-undo"></i>
                                Tampilkan Semua
                            </button>
                        `;
                        
                        galeriGrid.appendChild(emptyState); // Tambahkan empty state
                        
                        // Reset filter button
                        document.getElementById('resetFilterBtn').addEventListener('click', function() {
                            document.querySelector('.filter-btn[data-filter="all"]').click(); // Klik filter "Semua"
                        });
                    } else if (visibleCount > 0 && existingEmptyState) {
                        existingEmptyState.remove(); // Hapus empty state jika ada kartu yang ditampilkan
                    }
                }, 350);
            });
        });

        // Filter links in footer - Filter kategori dari footer
        document.querySelectorAll('.filter-link').forEach(link => {
            link.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Find and click corresponding filter button
                const filterBtn = document.querySelector(`.filter-btn[data-filter="${filter}"]`);
                if (filterBtn) {
                    filterBtn.classList.add('active');
                    filterBtn.click(); // Trigger click pada filter button yang sesuai
                }
                
                // Smooth scroll to galeri section
                const galeriSection = document.getElementById('galeri');
                if (galeriSection) {
                    window.scrollTo({
                        top: galeriSection.offsetTop - 80, // Offset untuk navbar
                        behavior: 'smooth' // Smooth scroll
                    });
                }
            });
        });

        // Smooth Scroll for Navigation Links - Scroll halus untuk link navigasi
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return; // Abaikan jika href adalah '#'
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault(); // Cegah default anchor behavior
                    window.scrollTo({
                        top: targetElement.offsetTop - 80, // Offset untuk navbar
                        behavior: 'smooth' // Smooth scroll
                    });
                }
            });
        });

        // Initialize on page load - Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Galeri page loaded successfully');
            
            // Animasi kartu saat dimuat
            const cards = document.querySelectorAll('.galeri-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`; // Stagger animation delay
            });
            
            // Tutup menu mobile saat resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) { // Jika lebar layar > 992px (desktop)
                    mobileMenuBtn.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = ''; // Aktifkan scroll body
                }
            });

        });
    </script>
</body>
</html>