<?php
// Memulai session untuk menyimpan data pengguna di sisi server
session_start();
// Mengimpor file konfigurasi database (koneksi $conn biasanya ada di sini)
include '../includes/config.php';
// Memuat skrip autentikasi untuk memastikan sistem keamanan berjalan
require_once '../includes/auth.php';

// Proteksi Halaman: Memastikan hanya user yang sudah login dan memiliki role 'user' yang bisa masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    // Jika tidak memenuhi syarat, alihkan secara paksa ke halaman login
    header("Location: ../login.php");
    exit; // Menghentikan eksekusi skrip selanjutnya
}

// Ambil data user dari session yang sedang aktif
$username = $_SESSION['username'];
// Melakukan query ke database untuk memvalidasi keberadaan username
$userQuery = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
// Mengatur nama lengkap; jika data di DB kosong, gunakan username dari session
$namaLengkap = $userData['username'] ?? $username;

// Mengambil parameter dari URL (GET) untuk fitur pencarian, filter motif, dan pengurutan
// mysqli_real_escape_string digunakan untuk mencegah serangan SQL Injection
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
$motifFilter = isset($_GET['motif']) ? mysqli_real_escape_string($conn, $_GET['motif']) : '';
$sortOrder = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'terbaru';

// Menyusun Query SQL secara dinamis dimulai dengan kondisi default (1=1 agar penyambungan AND lebih mudah)
$querySql = "SELECT * FROM produk WHERE 1=1";

// Jika ada input pencarian, tambahkan kondisi filter untuk kolom motif_kain atau kode_inna
if ($search != '') {
    $querySql .= " AND (motif_kain LIKE '%$search%' OR kode_inna LIKE '%$search%')";
}

// Jika ada filter motif yang dipilih, tambahkan kondisi spesifik motif_kain
if ($motifFilter != '') {
    $querySql .= " AND motif_kain = '$motifFilter'";
}

// Menentukan urutan data (Sorting) berdasarkan parameter yang diterima
if ($sortOrder == 'terbaru') {
    $querySql .= " ORDER BY kode_inna DESC"; // Urutkan berdasarkan kode terbaru (ID/Auto-increment)
} elseif ($sortOrder == 'terlama') {
    $querySql .= " ORDER BY kode_inna ASC";
} elseif ($sortOrder == 'nama_az') {
    $querySql .= " ORDER BY motif_kain ASC"; // Urutkan nama motif A ke Z
} elseif ($sortOrder == 'nama_za') {
    $querySql .= " ORDER BY motif_kain DESC"; // Urutkan nama motif Z ke A
}

// Menjalankan query utama untuk mendapatkan daftar produk
$result = mysqli_query($conn, $querySql);
// Menghitung jumlah total produk yang ditemukan
$totalProduk = $result ? mysqli_num_rows($result) : 0;

// Mengambil daftar unik motif kain untuk ditampilkan di menu dropdown/filter
$listMotif = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC");
$listMotifCount = $listMotif ? mysqli_num_rows($listMotif) : 0;

// Mengatur ulang pointer hasil query ke baris pertama (index 0)
mysqli_data_seek($listMotif, 0);
// Melakukan query ulang secara eksplisit untuk keperluan dropdown (opsional jika data_seek sudah digunakan)
$listMotifForDropdown = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC");

// Mengambil 6 data produk pertama untuk ditampilkan di bagian galeri atas (Hero Section)
$heroProducts = mysqli_query($conn, "SELECT * FROM produk LIMIT 6");

/**
 * Fungsi untuk mencari keberadaan file gambar di berbagai folder lokal
 * agar sistem tidak error jika gambar dipindah-pindah.
 */
function getLocalImage($imageName, $default = 'logo.png') {
    // Daftar ekstensi file gambar yang umum digunakan
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validasi apakah input sudah merupakan path file yang valid dan ada di folder
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    // Daftar folder kemungkinan tempat gambar disimpan
    $possibleLocations = [
        '../uploads/' . $imageName,
        '../assets/images/' . $imageName,
        '../images/' . $imageName,
        'img/' . $imageName,
        $imageName
    ];
    
    // Melakukan pengecekan folder satu per satu
    foreach ($possibleLocations as $location) {
        // Cek apakah file ada dengan nama asli
        if (file_exists($location)) {
            return $location;
        }
        
        // Jika tidak ada, coba tambahkan ekstensi secara otomatis (misal: nama_file.jpg)
        foreach ($extensions as $ext) {
            $testPath = $location . '.' . $ext;
            if (file_exists($testPath)) {
                return $testPath;
            }
        }
    }
    
    // Jika semua pengecekan gagal, kembalikan gambar default (biasanya logo atau placeholder)
    return $default;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT INTIM - Dashboard User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --secondary: #f8f9fa;
            --accent: #3498db;
            --accent-light: #5dade2;
            --accent-dark: #1b4f72;
            --text-dark: #1a252f;
            --text-light: #7f8c8d;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #ecf0f1;
            --gray-200: #d5dbdb;
            --gray-300: #bdc3c7;
            --gray-800: #1e2a38;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
            --radius-sm: 4px;
            --radius: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Premium Navigation */
        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 9999;
            padding: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .header.scrolled {
            box-shadow: var(--shadow);
            padding: 0;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        /* Main Navigation */
        .nav-main {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 15px;
            transition: var(--transition);
            position: relative;
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-link.active {
            color: var(--accent);
            font-weight: 600;
        }

        /* Enhanced Dropdown */
        .nav-dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
        }

        .dropdown-content {
            position: absolute;
            top: calc(100% + 15px);
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
            background: var(--white);
            min-width: 200px;
            box-shadow: var(--shadow-xl);
            border-radius: var(--radius-lg);
            z-index: 1001;
            padding: 12px 0;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--gray-100);
        }

        .nav-dropdown.active .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .dropdown-content form {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .dropdown-content select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text-dark);
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237f8c8d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
        }

        .dropdown-content select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Dropdown chevron animation */
        .nav-dropdown .dropdown-toggle .fa-chevron-down {
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .nav-dropdown.active .dropdown-toggle .fa-chevron-down {
            transform: rotate(180deg);
        }

        /* Dropdown links container */
        .dropdown-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .dropdown-item:hover {
            background: var(--gray-50);
            color: var(--accent);
            padding-left: 24px;
        }

        .dropdown-item.active {
            background: var(--accent);
            color: var(--white);
        }

        .dropdown-item.active:hover {
            background: var(--accent-light);
            color: var(--white);
        }

        .dropdown-item i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        /* User Menu - Modern */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-menu {
            position: relative;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            font-size: 16px;
            overflow: hidden;
        }

        .user-avatar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-light);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            background: var(--white);
            min-width: 280px;
            box-shadow: var(--shadow-xl);
            border-radius: var(--radius-lg);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--gray-100);
        }

        .user-menu.active .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-info {
            padding: 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-info-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 18px;
        }

        .user-details h4 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .user-details p {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 400;
        }

        .user-actions {
            padding: 12px;
        }

        .user-action-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 14px;
        }

        .user-action-item:hover {
            background: var(--gray-50);
            color: var(--accent);
            transform: translateX(4px);
        }

        .user-action-item i {
            width: 18px;
            text-align: center;
            font-size: 16px;
        }

        .user-action-item.logout {
            color: #e74c3c;
            border-top: 1px solid var(--gray-100);
            margin-top: 8px;
        }

        .user-action-item.logout:hover {
            background: rgba(231, 76, 60, 0.05);
        }

        /* Search Box */
        .search-box {
            position: relative;
            width: 320px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 48px;
            border: 1px solid var(--gray-200);
            border-radius: 50px;
            font-size: 14px;
            transition: var(--transition);
            background: var(--white);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-input::placeholder {
            color: var(--gray-300);
        }

        .search-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 15px;
            transition: var(--transition);
        }

        .search-btn:hover {
            color: var(--accent);
        }

        /* Sort Dropdown */
        .sort-dropdown {
            position: relative;
            min-width: 200px;
        }

        .sort-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .sort-wrapper i {
            position: absolute;
            left: 16px;
            color: var(--text-light);
            font-size: 15px;
            z-index: 2;
            pointer-events: none;
        }

        .sort-select {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text-dark);
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237f8c8d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 14px;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Mobile Menu Button - DESAIN BARU SEPERTI GAMBAR */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary);
            cursor: pointer;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            position: relative;
            z-index: 9999;
        }

        .mobile-menu-btn:hover {
            background: var(--gray-100);
        }

        .mobile-menu-btn.active {
            background: var(--accent);
            color: var(--white);
        }

        /* Modern Hero Section with Gallery */
        .hero-section {
            padding: 160px 0 100px;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-bg-pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: 
                radial-gradient(circle at 30% 30%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 70%, rgba(44, 62, 80, 0.05) 0%, transparent 50%);
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 50px;
            color: var(--accent-dark);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .hero-badge i {
            font-size: 11px;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--primary);
            position: relative;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .hero-description {
            font-size: 17px;
            color: var(--text-light);
            margin-bottom: 32px;
            max-width: 500px;
            line-height: 1.8;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-item {
            text-align: left;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent);
            display: block;
            line-height: 1;
            margin-bottom: 6px;
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 500;
        }

        /* Hero Gallery */
        .hero-gallery {
            position: relative;
            height: 500px;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 12px;
            height: 100%;
            width: 100%;
        }

        .gallery-item {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
            background: var(--gray-100);
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .gallery-item:nth-child(1) {
            grid-column: 1 / 3;
            grid-row: 1 / 3;
        }

        .gallery-item:nth-child(2) {
            grid-column: 3 / 4;
            grid-row: 1 / 2;
        }

        .gallery-item:nth-child(3) {
            grid-column: 3 / 4;
            grid-row: 2 / 3;
        }

        .gallery-item:nth-child(4) {
            grid-column: 1 / 2;
            grid-row: 3 / 4;
        }

        .gallery-item:nth-child(5) {
            grid-column: 2 / 4;
            grid-row: 3 / 4;
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(44, 62, 80, 0.8) 100%);
            display: flex;
            align-items: flex-end;
            padding: 16px;
            opacity: 0;
            transition: var(--transition);
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-caption {
            color: var(--white);
            transform: translateY(20px);
            transition: var(--transition);
        }

        .gallery-item:hover .gallery-caption {
            transform: translateY(0);
        }

        .gallery-caption h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .gallery-caption p {
            font-size: 11px;
            opacity: 0.8;
        }

        /* Modern Products Section */
        .section {
            padding: 100px 0;
            position: relative;
        }

        .section-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--gray-50) 0%, transparent 100%);
            z-index: -1;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }

        .section-subtitle {
            font-size: 13px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
            font-weight: 600;
            display: inline-block;
            position: relative;
            padding: 6px 20px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .section-description {
            font-size: 17px;
            color: var(--text-light);
            max-width: 700px;
            margin: 32px auto 0;
            line-height: 1.8;
        }

        /* Advanced Filter System */
        .filter-system {
            max-width: 1400px;
            margin: 0 auto 60px;
            padding: 0 40px;
        }

        .filter-wrapper {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-100);
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-title i {
            color: var(--accent);
        }

        .filter-controls {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: 12px 24px;
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: 50px;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: var(--accent);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .filter-btn:hover::before,
        .filter-btn.active::before {
            width: 300px;
            height: 300px;
        }

        .filter-btn span {
            position: relative;
            z-index: 1;
            transition: color 0.3s;
        }

        .filter-btn:hover span,
        .filter-btn.active span {
            color: var(--white);
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Modern Product Grid */
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 32px;
        }

        .product-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            animation: fadeIn 0.5s ease-out;
            border: 1px solid var(--gray-100);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            z-index: 2;
        }

        .product-image {
            position: relative;
            overflow: hidden;
            height: 280px;
            background: var(--gray-100);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        /* Product Overlay Effects */
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, 
                rgba(44, 62, 80, 0) 0%,
                rgba(44, 62, 80, 0.1) 30%,
                rgba(44, 62, 80, 0.9) 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            padding: 24px;
        }

        .product-card:hover .product-overlay {
            opacity: 1;
        }

        .overlay-content {
            text-align: center;
            color: var(--white);
            transform: translateY(30px);
            transition: var(--transition);
            width: 100%;
        }

        .product-card:hover .overlay-content {
            transform: translateY(0);
        }

        .overlay-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .overlay-desc {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .overlay-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .overlay-btn {
            padding: 10px 20px;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .overlay-btn:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3);
        }

        .overlay-btn.secondary {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .overlay-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .product-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--accent);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 3;
            box-shadow: var(--shadow);
        }

        .product-content {
            padding: 24px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .product-category {
            font-size: 12px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .product-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--primary);
            line-height: 1.3;
            font-family: 'Playfair Display', serif;
        }

        .product-code {
            font-size: 13px;
            color: var(--text-light);
            font-family: 'Courier New', monospace;
            background: var(--gray-100);
            padding: 6px 12px;
            border-radius: var(--radius);
            display: inline-block;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .product-colors {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .colors-label {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
        }

        .color-dots {
            display: flex;
            gap: 6px;
        }

        .color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid var(--white);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .color-dot:hover {
            transform: scale(1.2);
            box-shadow: var(--shadow);
        }

        .color-dot[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: var(--white);
            padding: 6px 12px;
            border-radius: var(--radius);
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            margin-bottom: 8px;
            z-index: 1000;
        }

        .color-dot[data-tooltip]:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            background: transparent;
            color: var(--accent);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            border: 2px solid var(--accent);
            width: 100%;
            font-family: 'Inter', sans-serif;
        }

        .view-btn:hover {
            background: var(--accent);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.2);
        }

        .view-btn i {
            transition: transform 0.3s ease;
        }

        .view-btn:hover i {
            transform: translateX(5px);
        }

        /* Modal for Image Preview */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(44, 62, 80, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            backdrop-filter: blur(10px);
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            background: var(--white);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            animation: scaleIn 0.3s ease-out;
        }

        .modal-image {
            width: 100%;
            height: 70vh;
            object-fit: contain;
            background: var(--gray-50);
        }

        .modal-info {
            padding: 24px;
            background: var(--white);
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            background: var(--white);
            border: none;
            border-radius: 50%;
            font-size: 18px;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            z-index: 1;
        }

        .modal-close:hover {
            background: var(--accent);
            color: var(--white);
            transform: rotate(90deg);
        }

        /* About Section */
        .about-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
            position: relative;
            color: var(--white);
        }

        .about-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
        }

        .about-content p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 32px;
        }

        .about-stat {
            text-align: center;
            padding: 24px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .about-stat .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent);
            display: block;
            margin-bottom: 6px;
            font-family: 'Playfair Display', serif;
        }

        .about-stat .text {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.9;
        }

        .about-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .about-gallery-item {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 200px;
            cursor: pointer;
            background: var(--gray-800);
        }

        .about-gallery-item:nth-child(1) {
            grid-column: 1 / 3;
            height: 240px;
        }

        .about-gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .about-gallery-item:hover .about-gallery-img {
            transform: scale(1.1);
        }

        /* Contact Section */
        .contact-section {
            padding: 80px 0;
            background: var(--white);
        }

        .contact-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        .contact-info h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .contact-info p {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .contact-details {
            margin-top: 32px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            transition: var(--transition);
        }

        .contact-item:hover {
            background: var(--gray-100);
            transform: translateX(8px);
        }

        .contact-icon {
            width: 48px;
            height: 48px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 20px;
            flex-shrink: 0;
        }

        .contact-text h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--primary);
        }

        .contact-text p {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .contact-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            height: 100%;
        }

        .contact-gallery-item {
            position: relative;
            border-radius: var(--radius-xl);
            overflow: hidden;
            height: 180px;
            cursor: pointer;
            background: var(--gray-100);
        }

        .contact-gallery-item:nth-child(3) {
            grid-column: 1 / 3;
            height: 200px;
        }

        .contact-gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .contact-gallery-item:hover .contact-gallery-img {
            transform: scale(1.1);
        }

        .contact-gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(44, 62, 80, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }

        .contact-gallery-item:hover .contact-gallery-overlay {
            opacity: 1;
        }

        .contact-gallery-text {
            color: var(--white);
            text-align: center;
            transform: translateY(20px);
            transition: var(--transition);
        }

        .contact-gallery-item:hover .contact-gallery-text {
            transform: translateY(0);
        }

        /* Footer */
        .footer {
            background: var(--primary);
            color: var(--white);
            padding: 60px 0 30px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 40px;
        }

        .footer-col h4 {
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--white);
            position: relative;
            display: inline-block;
            font-family: 'Playfair Display', serif;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--accent);
        }

        .footer-col p {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .footer-col a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            transition: var(--transition);
            position: relative;
            padding-left: 0;
            font-size: 14px;
        }

        .footer-col a::before {
            content: '→';
            position: absolute;
            left: 0;
            opacity: 0;
            transition: var(--transition);
        }

        .footer-col a:hover {
            color: var(--accent);
            padding-left: 20px;
        }

        .footer-col a:hover::before {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            color: var(--white);
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 0;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3);
        }

        .copyright {
            text-align: center;
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--gray-200);
            border-top: 3px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 0;
        }

        .empty-state-icon {
            font-size: 60px;
            color: var(--gray-200);
            margin-bottom: 20px;
            display: inline-block;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 12px;
            font-weight: 600;
            font-family: 'Playfair Display', serif;
        }

        .empty-state p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto 32px;
        }

        .reset-btn {
            padding: 14px 32px;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .reset-btn:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .nav-container,
            .hero-container,
            .about-container,
            .contact-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .hero-title {
                font-size: 42px;
            }
            
            .section-title {
                font-size: 36px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 24px;
            }
            
            .sort-dropdown {
                min-width: 180px;
            }
        }

        @media (max-width: 992px) {
            .hero-container,
            .about-container,
            .contact-container {
                grid-template-columns: 1fr;
                gap: 48px;
                text-align: center;
            }
            
            .hero-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 32px;
            }
            
            /* NAVBAR MOBILE SEPERTI GAMBAR */
            .nav-main {
                display: none;
            }
            
            /* Tampilkan tombol hamburger */
            .mobile-menu-btn {
                display: flex;
                order: 3;
            }
            
            /* Atur ulang layout nav-actions untuk mobile */
            .nav-actions {
                order: 2;
                gap: 15px;
            }
            
            /* Sembunyikan search box di mobile */
            .search-box {
                display: none;
            }
            
            /* User menu di mobile */
            .user-menu {
                order: 1;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 15px;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .about-stats {
                grid-template-columns: 1fr;
            }
            
            .filter-controls {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .sort-dropdown {
                width: 100%;
            }
            
            .search-box {
                width: 100%;
            }
            
            .hero-gallery {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .hero-section {
                padding: 120px 0 60px;
            }
            
            .hero-title {
                font-size: 32px;
            }
            
            .section {
                padding: 60px 0;
            }
            
            .section-title {
                font-size: 32px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .filter-wrapper {
                padding: 20px;
            }
            
            .filter-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .filter-buttons {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 8px;
            }
            
            .gallery-grid {
                height: 300px;
                gap: 8px;
            }
            
            .hero-gallery {
                height: 300px;
            }
            
            .sort-wrapper i {
                left: 14px;
            }
            
            .sort-select {
                padding-left: 40px;
            }
        }

        @media (max-width: 576px) {
            .nav-container,
            .hero-container,
            .about-container,
            .contact-container,
            .footer-container {
                padding: 0 20px;
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .section-title {
                font-size: 28px;
            }
            
            .stat-number {
                font-size: 32px;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 24px;
            }
            
            .filter-btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .logo img {
                height: 35px;
            }
            
            .sort-dropdown {
                min-width: 100%;
            }
        }

        /* Mobile Menu Overlay - DESAIN SEPERTI GAMBAR */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            display: none;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            z-index: 9998;
            animation: fadeIn 0.3s ease-out;
            padding-top: 100px;
        }

        .mobile-menu-overlay.active {
            display: flex;
        }

        .mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 0;
            align-items: stretch;
            width: 90%;
            max-width: 400px;
        }

        .mobile-nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition);
            padding: 16px 20px;
            position: relative;
            width: 100%;
            text-align: left;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            color: var(--accent);
            background: var(--gray-50);
        }

        .mobile-nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 14px;
        }

        .mobile-nav-link .fa-chevron-down {
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .mobile-nav-link.active .fa-chevron-down {
            transform: rotate(180deg);
        }

        .mobile-dropdown-content {
            display: none;
            background: var(--gray-50);
            border-radius: 0 0 var(--radius) var(--radius);
            overflow: hidden;
            animation: fadeIn 0.3s ease-out;
        }

        .mobile-dropdown-content.active {
            display: block;
        }

        .mobile-dropdown-link {
            display: block;
            padding: 14px 20px 14px 46px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            border-bottom: 1px solid var(--gray-100);
        }

        .mobile-dropdown-link:hover,
        .mobile-dropdown-link.active {
            color: var(--accent);
            background: var(--white);
            padding-left: 50px;
        }

        .mobile-dropdown-link i {
            width: 20px;
            margin-right: 8px;
            font-size: 12px;
        }

        .mobile-menu-close {
            position: absolute;
            top: 24px;
            right: 24px;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 24px;
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-close:hover {
            color: var(--accent);
            background: var(--gray-100);
            transform: rotate(90deg);
        }

        /* Search Box Mobile */
        .mobile-search-box {
            width: 90%;
            max-width: 400px;
            margin-bottom: 30px;
            padding: 0 20px;
        }

        .mobile-search-input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 1px solid var(--gray-200);
            border-radius: 50px;
            font-size: 15px;
            background: var(--white);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
        }

        .mobile-search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .mobile-search-btn {
            position: absolute;
            left: 35px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
        }

        .mobile-search-btn:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-content">
            <button class="modal-close" id="modalClose">
                <i class="fas fa-times"></i>
            </button>
            <img src="" alt="Preview" class="modal-image" id="modalImage">
            <div class="modal-info">
                <h3 id="modalTitle"></h3>
                <p id="modalDescription"></p>
            </div>
        </div>
    </div>

    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
        
        
        <nav class="mobile-nav">
            <a href="user_dashboard.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-home"></i>
                    Beranda
                </span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="koleksi.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'koleksi.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-th-large"></i>
                    Koleksi
                </span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <!-- Acara Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['berita_karir.php', 'berita_kegiatan.php']) ? 'active' : '' ?>">
                    <span>
                        <i class="fas fa-calendar-alt"></i>
                        Acara
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mobile-dropdown-content" id="mobileAcaraDropdown">
                    <a href="berita_karir.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_karir.php' ? 'active' : '' ?>">
                        <i class="fas fa-briefcase"></i>
                        Karir
                    </a>
                    <a href="berita_kegiatan.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        Kegiatan
                    </a>
                    <a href="berita_galeri.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                        <i class="fas fa-image"></i>
                        Galeri
                    </a>
                </div>
            </div>
            
            <!-- Tentang Kami Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['tentang_profil.php', 'tentang_visimisi.php']) ? 'active' : '' ?>">
                    <span>
                        <i class="fas fa-info-circle"></i>
                        Tentang Kami
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mobile-dropdown-content" id="mobileTentangDropdown">
                    <a href="tentang_profil.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'tentang_profil.php' ? 'active' : '' ?>">
                        <i class="fas fa-building"></i>
                        Profil Perusahaan
                    </a>
                    <a href="tentang_visimisi.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'tentang_visimisi.php' ? 'active' : '' ?>">
                        <i class="fas fa-bullseye"></i>
                        Visi & Misi
                    </a>
                </div>
            </div>
            
            <a href="kontak.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : '' ?>">
                <span>
                    <i class="fas fa-address-book"></i>
                    Kontak
                </span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            
            
            <!-- User Menu Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle">
                    <span>
                        <i class="fas fa-user-circle"></i>
                        <?= htmlspecialchars($namaLengkap) ?>
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mobile-dropdown-content">
                    <a href="ganti_password.php" class="mobile-dropdown-link">
                        <i class="fas fa-key"></i>
                        Ganti Sandi
                    </a>
                    <a href="../logout.php" class="mobile-dropdown-link" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </div>

    
    <header class="header" id="header">
        <div class="nav-container">
            <!-- Logo -->
            <a href="#beranda" class="logo">
                <img src="<?= getLocalImage('logo small black.png') ?>" alt="PT INTIM">
            </a>

            <!-- Main Navigation -->
            <nav class="nav-main">
                <a href="user_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Beranda</span>
                </a>
                <a href="koleksi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'koleksi.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Koleksi</span>
                </a>
                
                <!-- Acara Dropdown (Karir + Kegiatan) -->
                <div class="nav-dropdown" id="acaraDropdown">
                    <div class="dropdown-toggle nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['berita_karir.php', 'berita_kegiatan.php']) ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Acara</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="berita_karir.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_karir.php' ? 'active' : '' ?>">
                                <i class="fas fa-briefcase"></i>
                                <span>Karir</span>
                            </a>
                            <a href="berita_kegiatan.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                                <i class="fas fa-users"></i>
                                <span>Kegiatan</span>
                            </a>
                             <a href="berita_galeri.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                                <i class="fas fa-image"></i>
                                <span>Galeri</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tentang Kami Dropdown (Profil Perusahaan + Visi & Misi) -->
                <div class="nav-dropdown" id="tentangDropdown">
                    <div class="dropdown-toggle nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['tentang_profil.php', 'tentang_visimisi.php']) ? 'active' : '' ?>">
                        <i class="fas fa-info-circle"></i>
                        <span>Tentang Kami</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="tentang_profil.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'tentang_profil.php' ? 'active' : '' ?>">
                                <i class="fas fa-building"></i>
                                <span>Profil Perusahaan</span>
                            </a>
                            <a href="tentang_visimisi.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'tentang_visimisi.php' ? 'active' : '' ?>">
                                <i class="fas fa-bullseye"></i>
                                <span>Visi & Misi</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kontak -->
                <a href="kontak.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : '' ?>">
                    <i class="fas fa-address-book"></i>
                    <span>Kontak</span>
                </a>
                
                
            </nav>

            <!-- Navigation Actions -->
            <div class="nav-actions">
                

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- User Menu -->
                <div class="user-menu" id="userMenu">
                    <div class="user-avatar" id="userAvatar">
                        <?= strtoupper(substr($namaLengkap, 0, 1)) ?>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-info-avatar">
                                <?= strtoupper(substr($namaLengkap, 0, 1)) ?>
                            </div>
                            <div class="user-details">
                                <h4><?= htmlspecialchars($namaLengkap) ?></h4>
                                <p>User Dashboard</p>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="ganti_password.php" class="user-action-item">
                                <i class="fas fa-key"></i>
                                <span>Ganti Sandi</span>
                            </a>
                            <a href="../logout.php" class="user-action-item logout" onclick="return confirm('Yakin ingin logout?')">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section" id="beranda">
        <div class="hero-bg-pattern"></div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    <span>Premium Quality</span>
                </div>
                <h1 class="hero-title">Kain Premium dengan Sentuhan Seni Tradisional</h1>
                <p class="hero-description">
                    PT INTIM menghadirkan koleksi kain eksklusif dengan motif yang menginspirasi. 
                    Setiap helai kain adalah karya seni yang dibuat dengan ketelitian tinggi.
                </p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= $totalProduk ?></span>
                        <span class="stat-label">Produk</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $listMotifCount ?></span>
                        <span class="stat-label">Motif Unik</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">15+</span>
                        <span class="stat-label">Varian Warna</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-gallery">
                <div class="gallery-grid">
                    <?php 
                    $heroIndex = 0;
                    mysqli_data_seek($heroProducts, 0);
                    
                    // Tampilkan produk dari database
                    while($heroRow = mysqli_fetch_assoc($heroProducts)) {
                        if ($heroIndex >= 5) break;
                        
                        $imgs = isset($heroRow['gambar']) ? explode(",", $heroRow['gambar']) : [];
                        $heroImg = !empty($imgs[0]) ? getLocalImage(trim($imgs[0]), 'logo.png') : 'logo.png';
                        
                        $motifKain = $heroRow['motif_kain'] ?? 'Kain Premium';
                        $tipeKain = $heroRow['tipe_kain'] ?? 'Kain Berkualitas';
                    ?>
                    <div class="gallery-item" data-index="<?= $heroIndex ?>">
                        <img src="<?= htmlspecialchars($heroImg) ?>" 
                             alt="<?= htmlspecialchars($motifKain) ?>" 
                             class="gallery-img"
                             data-title="<?= htmlspecialchars($motifKain) ?>"
                             data-desc="<?= htmlspecialchars($tipeKain) ?>">
                        <div class="gallery-overlay">
                            <div class="gallery-caption">
                                <h4><?= htmlspecialchars($motifKain) ?></h4>
                                <p><?= htmlspecialchars($tipeKain) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php 
                        $heroIndex++;
                    }
                    
                    // Jika kurang dari 5 gambar, tambahkan gambar lokal
                    $localImages = [
                        'gambar1.jpg', 'gambar2.jpg', 'gambar3.jpg', 
                        'gambar4.jpg', 'gambar5.jpg'
                    ];
                    
                    $localTitles = [
                        'Kain Premium Polyester',
                        'Kain Rayon Berkualitas',
                        'Kain Katun Eksklusif',
                        'Kain Spesial Desain',
                        'Kain Tradisional'
                    ];
                    
                    $localDesc = [
                        '100% Polyester Premium',
                        'Rayon High Quality',
                        'Katun Grade A',
                        'Limited Edition',
                        'Traditional Pattern'
                    ];
                    
                    while ($heroIndex < 5) {
                        $imgIndex = $heroIndex % 5;
                        $localImg = getLocalImage($localImages[$imgIndex], 'logo.png');
                    ?>
                    <div class="gallery-item" data-index="<?= $heroIndex ?>">
                        <img src="<?= htmlspecialchars($localImg) ?>" 
                             alt="<?= htmlspecialchars($localTitles[$imgIndex]) ?>" 
                             class="gallery-img"
                             data-title="<?= htmlspecialchars($localTitles[$imgIndex]) ?>"
                             data-desc="<?= htmlspecialchars($localDesc[$imgIndex]) ?>">
                        <div class="gallery-overlay">
                            <div class="gallery-caption">
                                <h4><?= htmlspecialchars($localTitles[$imgIndex]) ?></h4>
                                <p><?= htmlspecialchars($localDesc[$imgIndex]) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php 
                        $heroIndex++;
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section" id="koleksi">
        <div class="section-bg"></div>
        <div class="section-header">
            <span class="section-subtitle">Koleksi Eksklusif</span>
            <h2 class="section-title">Produk Unggulan Kami</h2>
            <p class="section-description">
                Temukan koleksi kain premium dengan kualitas terbaik dan desain yang timeless. 
                Setiap produk dibuat dengan material pilihan dan teknik produksi terkini.
            </p>
        </div>

        <!-- Filter System -->
        <div class="filter-system">
            <div class="filter-wrapper">
                <div class="filter-header">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filter Produk
                    </div>
                    <div class="filter-controls">
                        <!-- Sort Dropdown -->
                        <div class="sort-dropdown">
                            <form method="GET" action="" id="sortForm">
                                <?php if($search != ''): ?>
                                    <input type="hidden" name="cari" value="<?= htmlspecialchars($search) ?>">
                                <?php endif; ?>
                                <?php if($motifFilter != ''): ?>
                                    <input type="hidden" name="motif" value="<?= htmlspecialchars($motifFilter) ?>">
                                <?php endif; ?>
                                <div class="sort-wrapper">
                                    <i class="fas fa-sort-amount-down-alt"></i>
                                    <select name="sort" id="sortSelect" class="sort-select">
                                        <option value="terbaru" <?= ($sortOrder == 'terbaru') ? 'selected' : '' ?>>Terbaru</option>
                                        <option value="terlama" <?= ($sortOrder == 'terlama') ? 'selected' : '' ?>>Terlama</option>
                                        <option value="nama_az" <?= ($sortOrder == 'nama_az') ? 'selected' : '' ?>>Nama A-Z</option>
                                        <option value="nama_za" <?= ($sortOrder == 'nama_za') ? 'selected' : '' ?>>Nama Z-A</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Search Box -->
                        <div class="search-box">
                            <form action="" method="GET" id="filterForm">
                                <?php if($motifFilter != ''): ?>
                                    <input type="hidden" name="motif" value="<?= htmlspecialchars($motifFilter) ?>">
                                <?php endif; ?>
                                <?php if($sortOrder != 'terbaru'): ?>
                                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortOrder) ?>">
                                <?php endif; ?>
                                <input type="text" name="cari" placeholder="Cari berdasarkan nama atau kode..." 
                                       value="<?= htmlspecialchars($search) ?>" class="search-input">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">
                        <span>Semua Produk</span>
                    </button>
                    <?php 
                    mysqli_data_seek($listMotif, 0);
                    while($motif = mysqli_fetch_assoc($listMotif)): 
                    ?>
                        <button class="filter-btn" data-filter="<?= htmlspecialchars($motif['motif_kain']) ?>">
                            <span><?= htmlspecialchars($motif['motif_kain']) ?></span>
                        </button>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-container">
            <div class="products-grid" id="productsGrid">
                <?php if ($totalProduk > 0): 
                    mysqli_data_seek($result, 0);
                    while($row = mysqli_fetch_assoc($result)) : ?>
                        <?php 
                        $imgs = isset($row['gambar']) ? explode(",", $row['gambar']) : [];
                        $thumb = !empty($imgs[0]) ? getLocalImage(trim($imgs[0]), 'logo.png') : 'logo.png';
                        
                        // Parse warna
                        $warnaArray = [];
                        if (!empty($row['warna_kain'])) {
                            $warnaData = explode(",", $row['warna_kain']);
                            foreach ($warnaData as $warna) {
                                $parts = explode(":", $warna);
                                if (isset($parts[1])) {
                                    $warnaArray[] = trim($parts[1]);
                                }
                            }
                        }
                        ?>
                        
                        <div class="product-card" data-motif="<?= htmlspecialchars($row['motif_kain'] ?? '') ?>">
                            <div class="product-image">
                                <img src="<?= htmlspecialchars($thumb) ?>" 
                                     alt="<?= htmlspecialchars($row['motif_kain'] ?? '') ?>"
                                     data-full="<?= htmlspecialchars($thumb) ?>">
                                <div class="product-overlay">
                                    <div class="overlay-content">
                                        <h3 class="overlay-title"><?= htmlspecialchars($row['motif_kain'] ?? '') ?></h3>
                                        <p class="overlay-desc"><?= htmlspecialchars($row['tipe_kain'] ?? '') ?></p>
                                        <div class="overlay-actions">
                                            <button class="overlay-btn view-detail" 
                                                    data-id="<?= $row['kode_inna'] ?? '' ?>">
                                                <i class="fas fa-eye"></i>
                                                Detail
                                            </button>
                                            <button class="overlay-btn secondary preview-image"
                                                    data-img="<?= htmlspecialchars($thumb) ?>"
                                                    data-title="<?= htmlspecialchars($row['motif_kain'] ?? '') ?>"
                                                    data-desc="<?= htmlspecialchars($row['tipe_kain'] ?? '') ?>">
                                                <i class="fas fa-expand"></i>
                                                Preview
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="product-content">
                                <div class="product-header">
                                    <div>
                                        <span class="product-category"><?= htmlspecialchars($row['tipe_kain'] ?? '') ?></span>
                                        <h3 class="product-title"><?= htmlspecialchars($row['motif_kain'] ?? '') ?></h3>
                                    </div>
                                    <span class="product-code"><?= htmlspecialchars($row['kode_inna'] ?? '') ?></span>
                                </div>
                                
                                <?php if (!empty($warnaArray)): ?>
                                    <div class="product-meta">
                                        <div class="product-colors">
                                            <span class="colors-label">Warna:</span>
                                            <div class="color-dots">
                                                <?php foreach(array_slice($warnaArray, 0, 4) as $color): ?>
                                                    <div class="color-dot" 
                                                         style="background-color: <?= htmlspecialchars($color) ?>"
                                                         data-tooltip="<?= htmlspecialchars($color) ?>"></div>
                                                <?php endforeach; ?>
                                                <?php if (count($warnaArray) > 4): ?>
                                                    <div class="color-dot" 
                                                         style="background: linear-gradient(45deg, var(--accent), var(--accent-light))"
                                                         data-tooltip="+<?= count($warnaArray) - 4 ?> warna lainnya">
                                                        +<?= count($warnaArray) - 4 ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="detail_produk.php?id=<?= $row['kode_inna'] ?? '' ?>" class="view-btn">
                                    Lihat Detail Lengkap
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Maaf, tidak ada produk yang sesuai dengan filter pencarian Anda.</p>
                        <a href="?" class="reset-btn">
                            <i class="fas fa-redo"></i>
                            Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="tentang">
        <div class="about-container">
            <div class="about-content">
                <h2>Tentang PT INTIM</h2>
                <p>
                    PT Innagroup Textile Manufacture (PT INTIM) merupakan perusahaan yang bergerak di bidang produksi textile (PI TPT) 
                    dengan produk utama kain Fabric (100% Polyester), selain itu kami melakukan diversifikasi produk dengan bahan dasar rayon dan katun.
                </p>
                <p>
                    Meskipun kami baru berproduksi pada tahun 2017, namun dengan waktu yang tidak terlalu lama perusahaan kami telah menjadi salah satu 
                    perusahaan yang diperhitungkan secara kualitas serta memiliki daya saing yang kompetitif di kancah market nasional.
                </p>
                
                <div class="about-stats">
                    <div class="about-stat">
                        <span class="number">ISO 9001</span>
                        <span class="text">Sertifikasi</span>
                    </div>
                    <div class="about-stat">
                        <span class="number">100%</span>
                        <span class="text">Kualitas</span>
                    </div>
                    <div class="about-stat">
                        <span class="number">24/7</span>
                        <span class="text">Support</span>
                    </div>
                    <div class="about-stat">
                        <span class="number">500+</span>
                        <span class="text">Klien</span>
                    </div>
                </div>
            </div>
            
            <div class="about-gallery">
                <div class="about-gallery-item">
                    <img src="<?= getLocalImage('about1.jpg', 'logo.png') ?>" alt="Factory" class="about-gallery-img">
                </div>
                <div class="about-gallery-item">
                    <img src="<?= getLocalImage('about2.jpg', 'logo.png') ?>" alt="Production" class="about-gallery-img">
                </div>
                <div class="about-gallery-item">
                    <img src="<?= getLocalImage('about3.jpg', 'logo.png') ?>" alt="Quality Control" class="about-gallery-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="kontak">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Hubungi Kami</h2>
                <p>
                    Kami siap melayani kebutuhan kain premium Anda. Hubungi kami untuk konsultasi dan penawaran khusus.
                    Kepuasan pelanggan merupakan prioritas utama kami.
                </p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Head Office</h4>
                            <p>
                                Jl. Karangwuni-Dlimas, Dlimas, Ceper,<br>
                                Klaten, Jawa Tengah, Indonesia
                            </p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Telepon</h4>
                            <p>
                                (0272) 5533 488<br>
                                0812 2612 2611
                            </p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Email</h4>
                            <p>headoffice@innagrouptm.com</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-gallery">
                <div class="contact-gallery-item">
                    <img src="<?= getLocalImage('contact1.jpg', 'logo.png') ?>" alt="Showroom" class="contact-gallery-img">
                    <div class="contact-gallery-overlay">
                        <div class="contact-gallery-text">
                            <h4>Showroom</h4>
                            <p>Tampilan Produk</p>
                        </div>
                    </div>
                </div>
                <div class="contact-gallery-item">
                    <img src="<?= getLocalImage('contact2.jpg', 'logo.png') ?>" alt="Production" class="contact-gallery-img">
                    <div class="contact-gallery-overlay">
                        <div class="contact-gallery-text">
                            <h4>Produksi</h4>
                            <p>Proses Pembuatan</p>
                        </div>
                    </div>
                </div>
                <div class="contact-gallery-item">
                    <img src="<?= getLocalImage('contact3.jpg', 'logo.png') ?>" alt="Quality Control" class="contact-gallery-img">
                    <div class="contact-gallery-overlay">
                        <div class="contact-gallery-text">
                            <h4>Quality Control</h4>
                            <p>Pemeriksaan Kualitas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <h4>PT INTIM</h4>
                <p>
                    Menghadirkan kain premium dengan kualitas terbaik sejak 1990. 
                    Komitmen kami adalah memberikan produk terbaik dengan sentuhan seni tradisional.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            
            
            <div class="footer-col">
                <h4>Tautan</h4>
                <a href="#beranda">Beranda</a>
                <a href="#koleksi">Koleksi</a>
                <a href="berita_karir.php">Karir</a>
                <a href="berita_kegiatan.php">Kegiatan</a>
                <a href="tentang_profil.php">Profil Perusahaan</a>
                <a href="tentang_visimisi.php">Visi & Misi</a>
                <a href="#kontak">Kontak</a>
            </div>
            
            <div class="footer-col">
                <h4>Jam Operasional</h4>
                <p>Senin - Jumat: 08:00 - 17:00</p>
                <p>Sabtu: 08:00 - 12:00</p>
                <p>Minggu & Hari Libur: Tutup</p>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> PT Innagroup Textile Manufacture. All rights reserved.
        </div>
    </footer>

    <script>
        // Loading Screen
        window.addEventListener('load', function() {
            const loading = document.getElementById('loading');
            loading.classList.add('hidden');
            setTimeout(() => {
                loading.style.display = 'none';
            }, 300);
        });

        // Header Scroll Effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Enhanced Dropdown Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdown toggles
            const dropdownToggles = document.querySelectorAll('.nav-dropdown .dropdown-toggle');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
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
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                    
                    // Close other dropdowns
                    document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                });
            }
            
            // Auto-submit motif select
            const motifSelect = document.getElementById('motifSelect');
            if (motifSelect) {
                motifSelect.addEventListener('change', function() {
                    document.getElementById('motifForm').submit();
                });
            }
            
            // Auto-submit sort select
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    document.getElementById('sortForm').submit();
                });
            }
        });

        // Mobile Menu Functionality - DESAIN SEPERTI GAMBAR
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose = document.getElementById('mobileMenuClose');

        if (mobileMenuBtn && mobileMenuOverlay) {
            mobileMenuBtn.addEventListener('click', function() {
                this.classList.toggle('active');
                mobileMenuOverlay.classList.toggle('active');
                document.body.style.overflow = 'hidden';
            });
        }

        if (mobileMenuClose && mobileMenuOverlay) {
            mobileMenuClose.addEventListener('click', function() {
                mobileMenuBtn.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        // Mobile dropdown toggle functionality
        document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = this.closest('.mobile-dropdown');
                const content = dropdown.querySelector('.mobile-dropdown-content');
                const isActive = content.classList.contains('active');
                
                // Close all other dropdowns
                document.querySelectorAll('.mobile-dropdown-content').forEach(dc => {
                    dc.classList.remove('active');
                });
                
                // Close all chevrons
                document.querySelectorAll('.mobile-dropdown-toggle .fa-chevron-down').forEach(chevron => {
                    chevron.style.transform = 'rotate(0deg)';
                });
                
                // Toggle current dropdown
                if (!isActive) {
                    content.classList.add('active');
                    this.querySelector('.fa-chevron-down').style.transform = 'rotate(180deg)';
                } else {
                    content.classList.remove('active');
                    this.querySelector('.fa-chevron-down').style.transform = 'rotate(0deg)';
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
                        document.body.style.overflow = '';
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
                    document.body.style.overflow = '';
                }
            });
        }

        // Image Preview Modal
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');
        const modalClose = document.getElementById('modalClose');

        // Preview image buttons
        document.querySelectorAll('.preview-image').forEach(button => {
            button.addEventListener('click', function() {
                const imgSrc = this.getAttribute('data-img');
                const title = this.getAttribute('data-title');
                const desc = this.getAttribute('data-desc');
                
                modalImage.src = imgSrc;
                modalTitle.textContent = title;
                modalDescription.textContent = desc;
                imageModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        // Gallery items click for preview
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', function() {
                const img = this.querySelector('img');
                const imgSrc = img.src;
                const title = img.getAttribute('data-title');
                const desc = img.getAttribute('data-desc');
                
                modalImage.src = imgSrc;
                modalTitle.textContent = title;
                modalDescription.textContent = desc;
                imageModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        // Close modal
        if (modalClose && imageModal) {
            modalClose.addEventListener('click', function() {
                imageModal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        // Close modal when clicking outside
        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    imageModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Filter Products
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const productCards = document.querySelectorAll('.product-card');
                
                productCards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-motif') === filter) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 10);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });

                // Update URL without reload
                const url = new URL(window.location);
                if (filter === 'all') {
                    url.searchParams.delete('motif');
                } else {
                    url.searchParams.set('motif', filter);
                }
                window.history.replaceState({}, '', url);
            });
        });

        // Smooth Scroll for Navigation Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Update active nav link on scroll
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        function updateActiveLink() {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            // Update desktop nav links
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveLink);

        // View detail buttons
        document.querySelectorAll('.view-detail').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                window.location.href = `detail_produk.php?id=${productId}`;
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateActiveLink();
            
            // Set active filter button based on current motif
            const currentMotif = '<?= $motifFilter ?>';
            if (currentMotif) {
                const activeBtn = document.querySelector(`.filter-btn[data-filter="${currentMotif}"]`);
                const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
                
                if (activeBtn && allBtn) {
                    allBtn.classList.remove('active');
                    activeBtn.classList.add('active');
                }
            }
            
            // Mobile menu auto-close on resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    mobileMenuBtn.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>