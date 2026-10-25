<?php
session_start();
include '../includes/config.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user
$username = $_SESSION['username'];
$userQuery = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
$namaLengkap = $userData['username'] ?? $username;

// Tentukan hari ini
$today = date('Y-m-d');

// Ambil parameter pencarian
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';

// Query untuk mendapatkan kegiatan dari database dengan filter pencarian
$kegiatanQuery = "SELECT * FROM kegiatan WHERE 1=1";
if ($search != '') {
    $kegiatanQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$kegiatanQuery .= " ORDER BY tanggal DESC";

$resultKegiatan = mysqli_query($conn, $kegiatanQuery);
$totalKegiatan = $resultKegiatan ? mysqli_num_rows($resultKegiatan) : 0;

// Query untuk kegiatan yang akan datang dengan filter pencarian
$upcomingQuery = "SELECT * FROM kegiatan WHERE tanggal > '$today'";
if ($search != '') {
    $upcomingQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori
     LIKE '%$search%')";
}
$upcomingQuery .= " ORDER BY tanggal ASC LIMIT 3";
$resultUpcoming = mysqli_query($conn, $upcomingQuery);

// Query untuk kegiatan yang sedang berlangsung dengan filter pencarian
$ongoingQuery = "SELECT * FROM kegiatan WHERE tanggal = '$today'";
if ($search != '') {
    $ongoingQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$ongoingQuery .= " ORDER BY waktu ASC";
$resultOngoing = mysqli_query($conn, $ongoingQuery);

// Query untuk kegiatan yang sudah selesai dengan filter pencarian
$completedQuery = "SELECT * FROM kegiatan WHERE tanggal < '$today'";
if ($search != '') {
    $completedQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$completedQuery .= " ORDER BY tanggal DESC LIMIT 6";
$resultCompleted = mysqli_query($conn, $completedQuery);

// Query untuk kategori unik dengan filter pencarian
$kategoriQuery = "SELECT DISTINCT kategori FROM kegiatan WHERE kategori IS NOT NULL AND kategori != ''";
if ($search != '') {
    $kategoriQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$kategoriQuery .= " ORDER BY kategori";
$resultKategori = mysqli_query($conn, $kategoriQuery);

// Query untuk gallery kegiatan dengan filter pencarian
$galleryQuery = "SELECT * FROM kegiatan WHERE tanggal < '$today' AND gambar IS NOT NULL AND gambar != ''";
if ($search != '') {
    $galleryQuery .= " AND (judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%')";
}
$galleryQuery .= " ORDER BY tanggal DESC LIMIT 6";
$resultGallery = mysqli_query($conn, $galleryQuery);
$galleryCount = $resultGallery ? mysqli_num_rows($resultGallery) : 0;

// Hitung jumlah untuk stats
$upcomingCount = $resultUpcoming ? mysqli_num_rows($resultUpcoming) : 0;
$ongoingCount = $resultOngoing ? mysqli_num_rows($resultOngoing) : 0;
$completedCount = $resultCompleted ? mysqli_num_rows($resultCompleted) : 0;

// Fungsi untuk menentukan status berdasarkan tanggal
function getActivityStatus($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return 'mendatang';
    }
    
    $today = date('Y-m-d');
    
    if ($tanggal < $today) {
        return 'selesai';
    } elseif ($tanggal == $today) {
        return 'berlangsung';
    }
        return 'mendatang';
    }

// Fungsi untuk mendapatkan badge warna berdasarkan status
function getStatusBadge($status) {
    $badges = [
        'selesai' => [
            'text' => 'Selesai',
            'color' => '#2ecc71', // Hijau
            'bgColor' => 'rgba(46, 204, 113, 0.1)',
            'icon' => 'fas fa-check-circle'
        ],
        'berlangsung' => [
            'text' => 'Sedang Berlangsung',
            'color' => '#f39c12', // Kuning/Oranye
            'bgColor' => 'rgba(243, 156, 18, 0.1)',
            'icon' => 'fas fa-running'
        ],
        'mendatang' => [
            'text' => 'Akan Datang',
            'color' => '#3498db', // Biru
            'bgColor' => 'rgba(52, 152, 219, 0.1)',
            'icon' => 'fas fa-calendar-alt'
        ]
    ];
    
    return $badges[$status] ?? $badges['mendatang'];
}

// Fungsi untuk mendapatkan path gambar lokal
function getLocalImage($imageName, $default = 'logo.png') {
    // Jika kosong, return default
    if (empty($imageName)) {
        return $default;
    }
    
    // Daftar ekstensi yang dicoba
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Jika sudah full path dan file exist, langsung return
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    // Coba berbagai lokasi
    $possibleLocations = [
        '../uploads/kegiatan/' . $imageName,
        '../uploads/' . $imageName,
        'assets/images/kegiatan/' . $imageName,
        'images/kegiatan/' . $imageName,
        'kegiatan/' . $imageName,
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
    
    // Return default
    return $default;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return 'Tanggal belum ditentukan';
    }
    
    $bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return 'Tanggal tidak valid';
    }
    
    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp) - 1];
    $tahun = date('Y', $timestamp);
    
    return $hari . ' ' . $bulan . ' ' . $tahun;
}

// Fungsi untuk format waktu dari format database ke tampilan
function formatWaktuDisplay($waktu) {
    if (empty($waktu) || $waktu == '00:00:00') {
        return '09:00 - 17:00 WIB';
    }
    
    // Jika format sudah seperti "09:00 - 17:00 WIB", return langsung
    if (strpos($waktu, '-') !== false) {
        return $waktu;
    }
    
    // Jika format "HH:MM:SS", konversi ke "HH:MM"
    $timeParts = explode(':', $waktu);
    if (count($timeParts) >= 2) {
        return $timeParts[0] . ':' . $timeParts[1] . ' WIB';
    }
    
    return $waktu;
}

// Fungsi untuk format nama kategori
function formatKategori($kategori) {
    if (empty($kategori)) {
        return 'Umum';
    }
    
    $kategoriMapping = [
        'pameran' => 'Pameran & Expo',
        'pelatihan' => 'Pelatihan & Workshop',
        'sosial' => 'Kegiatan Sosial',
        'industri' => 'Event Industri',
        'internal' => 'Kegiatan Internal',
        'seminar' => 'Seminar',
        'workshop' => 'Workshop',
        'lomba' => 'Lomba/Pertandingan',
        'rapat' => 'Rapat/Meeting',
        'umum' => 'Kegiatan Umum'
    ];
    
    return isset($kategoriMapping[$kategori]) ? $kategoriMapping[$kategori] : ucfirst($kategori);
}

// Fungsi untuk mendapatkan bulan singkat (untuk upcoming events)
function getMonthShort($monthNumber) {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return isset($months[$monthNumber - 1]) ? $months[$monthNumber - 1] : 'Jan';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Kegiatan - PT INTIM</title>
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
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #3498db;
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

        .user-action-item.logout {
            color: #e74c3c;
            border-top: 1px solid var(--gray-100);
            margin-top: 8px;
        }

        .user-action-item.logout:hover {
            background: rgba(231, 76, 60, 0.05);
        }

        /* Mobile Menu Button */
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

        /* Mobile Menu Overlay */
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

        /* Main Content */
        .main-content {
            padding-top: 120px;
            min-height: 100vh;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: var(--gray-50);
            padding: 20px 0;
            margin-bottom: 40px;
        }

        .breadcrumb-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }

        .breadcrumb-item {
            font-size: 14px;
            color: var(--text-light);
        }

        .breadcrumb-item a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--accent);
        }

        .breadcrumb-item.active {
            color: var(--accent);
            font-weight: 600;
        }

        .breadcrumb-separator {
            color: var(--gray-300);
            font-size: 12px;
        }

        /* Activities Hero */
        .activities-hero {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: var(--white);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .activities-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('<?= getLocalImage('pattern.png', '') ?>');
            opacity: 0.1;
            pointer-events: none;
        }

        .activities-hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .activities-hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .activities-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
            color: var(--white);
        }

        .activities-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--white);
        }

        .activities-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .activities-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 5px;
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        
        /* Activities Content */
        .activities-content {
            padding: 80px 0;
            background: var(--white);
        }

        .activities-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        /* Category Filter */
        .category-filter {
            margin-bottom: 60px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-title i {
            color: var(--accent);
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .category-btn {
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-btn:hover,
        .category-btn.active {
            background: var(--accent);
            color: var(--white);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid;
            white-space: nowrap;
        }

        .status-selesai {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border-color: #2ecc71;
        }

        .status-berlangsung {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
            border-color: #f39c12;
        }

        .status-mendatang {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border-color: #3498db;
        }

        /* Activities Grid - Kartu Kegiatan Utama */
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        .activity-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            animation: fadeIn 0.5s ease-out;
            border: 1px solid var(--gray-100);
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .activity-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .activity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            z-index: 2;
        }

        .activity-image {
            position: relative;
            overflow: hidden;
            height: 250px;
            background: var(--gray-100);
        }

        .activity-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .activity-card:hover .activity-image img {
            transform: scale(1.1);
        }

        .activity-badge {
            position: absolute;
            top: 20px;
            right: 20px;
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

        /* Direct Information Container - TEMPAT INFORMASI DETAIL LANGSUNG */
        .activity-direct-info {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 25px;
            margin: 20px 0;
            border: 1px solid var(--gray-200);
            animation: slideDown 0.3s ease-out;
        }

        .direct-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .direct-info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .direct-info-item i {
            width: 24px;
            height: 24px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .direct-info-content {
            flex: 1;
        }

        .direct-info-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .direct-info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .activity-content {
            padding: 30px;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            font-size: 13px;
            color: var(--text-light);
        }

        .activity-date,
        .activity-location {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .activity-date i,
        .activity-location i {
            font-size: 14px;
            color: var(--accent);
        }

        .activity-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary);
            line-height: 1.4;
            font-family: 'Playfair Display', serif;
        }

        .activity-description {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .activity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        .activity-category {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Style untuk kartu khusus (Upcoming, Ongoing, Completed) */
        .special-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .special-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .special-card-header {
            padding: 25px 30px 20px;
            color: var(--white);
            position: relative;
        }

        .upcoming .special-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
        }

        .ongoing .special-card-header {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
        }

        .completed .special-card-header {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
        }

        .special-card-date {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .date-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .date-text {
            display: flex;
            flex-direction: column;
        }

        .date-day {
            font-size: 24px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }

        .date-month {
            font-size: 14px;
            opacity: 0.9;
        }

        .special-card-title {
            font-size: 20px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .special-card-content {
            padding: 25px 30px 30px;
        }

        .special-card-description {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        /* Section Styles */
        .section-header {
            text-align: center;
            margin-bottom: 60px;
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

        /* Grids for Sections */
        .upcoming-grid,
        .ongoing-grid,
        .completed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 40px;
        }

        /* Gallery */
        .gallery-section {
            padding: 80px 0;
            background: var(--white);
        }

        .gallery-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .gallery-item {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 250px;
            cursor: pointer;
            background: var(--gray-100);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.1);
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

        /* Pesan Tidak Ada Kegiatan */
        .no-activities-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 0;
            animation: fadeIn 0.5s ease-out;
        }

        .no-activities-message .empty-state-icon {
            font-size: 60px;
            color: var(--gray-200);
            margin-bottom: 20px;
            display: inline-block;
        }

        .no-activities-message h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 12px;
            font-weight: 600;
            font-family: 'Playfair Display', serif;
        }

        .no-activities-message p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto 32px;
        }

        #resetFilterBtn {
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            max-width: 200px;
            margin-left: auto;
            margin-right: auto;
        }

        #resetFilterBtn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .nav-container,
            .activities-hero-container,
            .activities-container,
            .breadcrumb-container,
            .gallery-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .activities-title {
                font-size: 42px;
            }
            
            .section-title {
                font-size: 36px;
            }
            
            .search-box {
                width: 280px;
            }
        }

        @media (max-width: 992px) {
            .activities-grid,
            .upcoming-grid,
            .ongoing-grid,
            .completed-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            
            /* NAVBAR MOBILE SEPERTI USER_DASHBOARD.PHP */
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
            
            
            
            /* User menu di mobile */
            .user-menu {
                order: 1;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 15px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 32px;
            }
        }

        @media (max-width: 768px) {
            .activities-hero,
            .activities-content,
            .gallery-section {
                padding: 60px 0;
            }
            
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .activities-title {
                font-size: 32px;
            }
            
            .section-title {
                font-size: 32px;
            }
            
            .activities-grid,
            .upcoming-grid,
            .ongoing-grid,
            .completed-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .direct-info-grid {
                grid-template-columns: 1fr;
            }
            
            .breadcrumb-container,
            .activities-hero-container,
            .activities-container,
            .gallery-container {
                padding: 0 24px;
            }
        }

        @media (max-width: 576px) {
            .activities-title {
                font-size: 28px;
            }
            
            .section-title {
                font-size: 28px;
            }
            
            .nav-container {
                padding: 0 20px;
            }
            
            .logo img {
                height: 35px;
            }
            
            .breadcrumb-container,
            .activities-hero-container,
            .activities-container,
            .gallery-container,
            .footer-container {
                padding: 0 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .activities-stats {
                flex-direction: column;
                gap: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header class="header" id="header">
        <div class="nav-container">
            <!-- Logo -->
            <a href="user_dashboard.php" class="logo">
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
                
                <!-- Acara Dropdown -->
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
                            <a href="berita_galeri.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_galeri.php' ? 'active' : '' ?>">
                                <i class="fas fa-briefcase"></i>
                                <span>Galeri</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tentang Kami Dropdown -->
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

    <!-- Mobile Menu Overlay -->
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
                    <a href="berita_galeri.php" class="mobile-dropdown-link <?= basename($_SERVER['PHP_SELF']) == 'berita_galeriphp' ? 'active' : '' ?>">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <div class="breadcrumb-container">
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="user_dashboard.php"><i class="fas fa-home"></i> Beranda</a>
                    </li>
                    <li class="breadcrumb-separator">/</li>
                    <li class="breadcrumb-item">
                        <a href="#">Acara</a>
                    </li>
                    <li class="breadcrumb-separator">/</li>
                    <li class="breadcrumb-item active">
                        Berita Kegiatan
                    </li>
                </ul>
            </div>
        </div>

        <!-- Activities Hero -->
        <section class="activities-hero">
            <div class="activities-hero-container">
                <div class="activities-hero-content">
                    <div class="activities-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Berita Kegiatan</span>
                    </div>
                    <h1 class="activities-title">Kegiatan & Event PT INTIM</h1>
                    <p class="activities-subtitle">
                        Ikuti perkembangan terbaru dari berbagai kegiatan dan event yang diselenggarakan 
                        oleh PT INTIM. Semua informasi detail tersedia langsung pada kartu kegiatan.
                    </p>
                    
                    <div class="activities-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $totalKegiatan ?></div>
                            <div class="stat-label">Total Kegiatan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $upcomingCount ?></div>
                            <div class="stat-label">Akan Datang</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $ongoingCount ?></div>
                            <div class="stat-label">Sedang Berlangsung</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $completedCount ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        

        <!-- Activities Content -->
        <section class="activities-content">
            <div class="activities-container">
                <!-- Category Filter -->
                <div class="category-filter">
                    <h3 class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filter Status & Kategori
                    </h3>
                    <div class="filter-buttons">
                        <button class="category-btn active" data-category="all" data-status="all">
                            Semua Kegiatan
                        </button>
                        <button class="category-btn" data-category="all" data-status="mendatang">
                            <i class="fas fa-calendar-alt"></i> Akan Datang
                        </button>
                        <button class="category-btn" data-category="all" data-status="berlangsung">
                            <i class="fas fa-running"></i> Sedang Berlangsung
                        </button>
                        <button class="category-btn" data-category="all" data-status="selesai">
                            <i class="fas fa-check-circle"></i> Selesai
                        </button>
                        <?php 
                        if ($resultKategori && mysqli_num_rows($resultKategori) > 0):
                            mysqli_data_seek($resultKategori, 0);
                            while($kategori = mysqli_fetch_assoc($resultKategori)):
                                if (!empty($kategori['kategori'])):
                        ?>
                        <button class="category-btn" data-category="<?= htmlspecialchars($kategori['kategori']) ?>" data-status="all">
                            <?= formatKategori(htmlspecialchars($kategori['kategori'])) ?>
                        </button>
                        <?php 
                                endif;
                            endwhile;
                        endif; 
                        ?>
                    </div>
                </div>

                <!-- Activities Grid - SEMUA INFORMASI LANGSUNG TAMPIL DI SINI -->
                <div class="activities-grid" id="activitiesGrid">
                    <?php 
                    if ($resultKegiatan && mysqli_num_rows($resultKegiatan) > 0): 
                        mysqli_data_seek($resultKegiatan, 0);
                        while($kegiatan = mysqli_fetch_assoc($resultKegiatan)): 
                            $gambar = !empty($kegiatan['gambar']) ? getLocalImage($kegiatan['gambar']) : getLocalImage('default_event.jpg');
                            $tanggal = formatTanggalIndo($kegiatan['tanggal']);
                            $waktu = formatWaktuDisplay($kegiatan['waktu'] ?? '09:00:00');
                            $kategori = $kegiatan['kategori'] ?? 'umum';
                            $status = getActivityStatus($kegiatan['tanggal']);
                            $statusBadge = getStatusBadge($status);
                            $lokasi = $kegiatan['lokasi'] ?? 'Klaten, Jawa Tengah';
                            $kategori = $kegiatan['kategori'] ?? 'Terbuka untuk Umum';
                    ?>
                    <div class="activity-card" data-category="<?= htmlspecialchars($kategori) ?>" data-status="<?= $status ?>">
                        <div class="activity-image">
                            <img src="<?= htmlspecialchars($gambar) ?>" 
                                 alt="<?= htmlspecialchars($kegiatan['judul']) ?>">
                            <span class="activity-badge">
                                <?= formatKategori($kategori) ?>
                            </span>
                            <!-- Status Badge -->
                            <span class="status-badge" style="
                                position: absolute;
                                top: 20px;
                                left: 20px;
                                background: <?= $statusBadge['bgColor'] ?>;
                                color: <?= $statusBadge['color'] ?>;
                                border-color: <?= $statusBadge['color'] ?>;
                            ">
                                <i class="<?= $statusBadge['icon'] ?>"></i>
                                <span><?= $statusBadge['text'] ?></span>
                            </span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-meta">
                                <div class="activity-date">
                                    <i class="far fa-calendar"></i>
                                    <span><?= $tanggal ?></span>
                                </div>
                                <div class="activity-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($lokasi) ?></span>
                                </div>
                            </div>
                            <h3 class="activity-title">
                                <?= htmlspecialchars($kegiatan['judul']) ?>
                            </h3>
                            
                            <!-- INFORMASI DETAIL LANGSUNG TAMPIL DI SINI -->
                            <div class="activity-direct-info">
                                <div class="direct-info-grid">
                                    <div class="direct-info-item">
                                        <i class="far fa-clock"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Waktu</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($waktu) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-users"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($kategori) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-tag"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= formatKategori($kategori) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-info-circle"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Status</div>
                                            <div class="direct-info-value"><?= $statusBadge['text'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Deskripsi Kegiatan -->
                            <?php if (!empty($kegiatan['deskripsi'])): ?>
                            <p class="activity-description">
                                <?= htmlspecialchars($kegiatan['deskripsi']) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="activity-footer">
                                <span class="activity-category">
                                    <i class="fas fa-tag"></i>
                                    <?= formatKategori($kategori) ?>
                                </span>
                                
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Ongoing Events (if any) -->
        <?php if ($ongoingCount > 0): ?>
        <section class="activities-content" style="background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);">
            <div class="activities-container">
                <div class="section-header">
                    <span class="section-subtitle">Sedang Berlangsung</span>
                    <h2 class="section-title">Kegiatan Hari Ini</h2>
                    <p class="section-description">
                        Kegiatan yang sedang berlangsung hari ini. Semua informasi tersedia langsung di bawah ini.
                    </p>
                </div>

                <div class="ongoing-grid">
                    <?php 
                    mysqli_data_seek($resultOngoing, 0);
                    while($event = mysqli_fetch_assoc($resultOngoing)):
                        $gambar = !empty($event['gambar']) ? getLocalImage($event['gambar']) : getLocalImage('default_event.jpg');
                        $tanggal = formatTanggalIndo($event['tanggal']);
                        $waktu = formatWaktuDisplay($event['waktu'] ?? '09:00:00');
                        $kategori = $event['kategori'] ?? 'umum';
                        $lokasi = $event['lokasi'] ?? 'Klaten, Jawa Tengah';
                        $kategori = $event['kategori'] ?? 'Terbuka untuk Umum';
                    ?>
                    <div class="special-card ongoing">
                        <div class="special-card-header">
                            <div class="special-card-date">
                                <div class="date-icon">
                                    <i class="far fa-calendar"></i>
                                </div>
                                <div class="date-text">
                                    <span class="date-day"><?= date('d', strtotime($event['tanggal'])) ?></span>
                                    <span class="date-month"><?= getMonthShort(date('m', strtotime($event['tanggal']))) . ' ' . date('Y', strtotime($event['tanggal'])) ?></span>
                                </div>
                            </div>
                            <h3 class="special-card-title"><?= htmlspecialchars($event['judul']) ?></h3>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($lokasi) ?></span>
                            </div>
                        </div>
                        <div class="special-card-content">
                            <!-- Informasi Detail Langsung -->
                            <div class="activity-direct-info">
                                <div class="direct-info-grid">
                                    <div class="direct-info-item">
                                        <i class="far fa-clock"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Waktu</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($waktu) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-users"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($kategori) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-tag"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= formatKategori($kategori) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['deskripsi'])): ?>
                            <p class="special-card-description">
                                <?= htmlspecialchars(substr($event['deskripsi'], 0, 200)) ?>
                                <?php if (strlen($event['deskripsi']) > 200): ?>...<?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="text-align: center; padding-top: 20px; color: var(--text-light); font-size: 13px;">
                                <i class="fas fa-info-circle"></i> Kegiatan sedang berlangsung
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Upcoming Events -->
        <?php if ($upcomingCount > 0): ?>
        <section class="activities-content">
            <div class="activities-container">
                <div class="section-header">
                    <span class="section-subtitle">Acara Mendatang</span>
                    <h2 class="section-title">Kegiatan yang Akan Datang</h2>
                    <p class="section-description">
                        Jadwal kegiatan dan event yang akan diselenggarakan oleh PT INTIM dalam waktu dekat.
                    </p>
                </div>

                <div class="upcoming-grid">
                    <?php 
                    mysqli_data_seek($resultUpcoming, 0);
                    while($event = mysqli_fetch_assoc($resultUpcoming)):
                        $gambar = !empty($event['gambar']) ? getLocalImage($event['gambar']) : getLocalImage('default_event.jpg');
                        $tanggal = formatTanggalIndo($event['tanggal']);
                        $waktu = formatWaktuDisplay($event['waktu'] ?? '09:00:00');
                        $kategori = $event['kategori'] ?? 'umum';
                        $lokasi = $event['lokasi'] ?? 'Klaten, Jawa Tengah';
                        $kategori = $event['kategori'] ?? 'Terbuka untuk Umum';
                    ?>
                    <div class="special-card upcoming">
                        <div class="special-card-header">
                            <div class="special-card-date">
                                <div class="date-icon">
                                    <i class="far fa-calendar"></i>
                                </div>
                                <div class="date-text">
                                    <span class="date-day"><?= date('d', strtotime($event['tanggal'])) ?></span>
                                    <span class="date-month"><?= getMonthShort(date('m', strtotime($event['tanggal']))) . ' ' . date('Y', strtotime($event['tanggal'])) ?></span>
                                </div>
                            </div>
                            <h3 class="special-card-title"><?= htmlspecialchars($event['judul']) ?></h3>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($lokasi) ?></span>
                            </div>
                        </div>
                        <div class="special-card-content">
                            <!-- Informasi Detail Langsung -->
                            <div class="activity-direct-info">
                                <div class="direct-info-grid">
                                    <div class="direct-info-item">
                                        <i class="far fa-clock"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Waktu</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($waktu) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-users"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($kategori) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-tag"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= formatKategori($kategori) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['deskripsi'])): ?>
                            <p class="special-card-description">
                                <?= htmlspecialchars(substr($event['deskripsi'], 0, 200)) ?>
                                <?php if (strlen($event['deskripsi']) > 200): ?>...<?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="text-align: center; padding-top: 20px; color: var(--text-light); font-size: 13px;">
                                <i class="fas fa-calendar-check"></i> Segera hadiri kegiatan ini
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Completed Events -->
        <?php if ($completedCount > 0): ?>
        <section class="activities-content" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
            <div class="activities-container">
                <div class="section-header">
                    <span class="section-subtitle">Arsip Kegiatan</span>
                    <h2 class="section-title">Kegiatan yang Telah Selesai</h2>
                    <p class="section-description">
                        Dokumentasi dari berbagai kegiatan dan event yang telah kami selenggarakan.
                    </p>
                </div>

                <div class="completed-grid">
                    <?php 
                    mysqli_data_seek($resultCompleted, 0);
                    while($event = mysqli_fetch_assoc($resultCompleted)):
                        $gambar = !empty($event['gambar']) ? getLocalImage($event['gambar']) : getLocalImage('default_event.jpg');
                        $tanggal = formatTanggalIndo($event['tanggal']);
                        $waktu = formatWaktuDisplay($event['waktu'] ?? '09:00:00');
                        $kategori = $event['kategori'] ?? 'umum';
                        $lokasi = $event['lokasi'] ?? 'Klaten, Jawa Tengah';
                        $kategori = $event['kategori'] ?? 'Terbuka untuk Umum';
                    ?>
                    <div class="special-card completed">
                        <div class="special-card-header">
                            <div class="special-card-date">
                                <div class="date-icon">
                                    <i class="far fa-calendar"></i>
                                </div>
                                <div class="date-text">
                                    <span class="date-day"><?= date('d', strtotime($event['tanggal'])) ?></span>
                                    <span class="date-month"><?= getMonthShort(date('m', strtotime($event['tanggal']))) . ' ' . date('Y', strtotime($event['tanggal'])) ?></span>
                                </div>
                            </div>
                            <h3 class="special-card-title"><?= htmlspecialchars($event['judul']) ?></h3>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($lokasi) ?></span>
                            </div>
                        </div>
                        <div class="special-card-content">
                            <!-- Informasi Detail Langsung -->
                            <div class="activity-direct-info">
                                <div class="direct-info-grid">
                                    <div class="direct-info-item">
                                        <i class="far fa-clock"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Waktu</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($waktu) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-users"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= htmlspecialchars($kategori) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="direct-info-item">
                                        <i class="fas fa-tag"></i>
                                        <div class="direct-info-content">
                                            <div class="direct-info-label">Kategori</div>
                                            <div class="direct-info-value"><?= formatKategori($kategori) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['deskripsi'])): ?>
                            <p class="special-card-description">
                                <?= htmlspecialchars(substr($event['deskripsi'], 0, 150)) ?>
                                <?php if (strlen($event['deskripsi']) > 150): ?>...<?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="text-align: center; padding-top: 20px; color: var(--text-light); font-size: 13px;">
                                <i class="fas fa-check-circle"></i> Kegiatan telah selesai dilaksanakan
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Gallery Section -->
        <?php if ($galleryCount > 0): ?>
        <section class="gallery-section">
            <div class="gallery-container">
                <div class="section-header">
                    <span class="section-subtitle">Galeri Kegiatan</span>
                    <h2 class="section-title">Momen Terbaik Kegiatan Kami</h2>
                    <p class="section-description">
                        Dokumentasi visual dari berbagai kegiatan dan event yang telah kami selenggarakan.
                    </p>
                </div>
                
                <div class="gallery-grid">
                    <?php 
                    mysqli_data_seek($resultGallery, 0);
                    while($gallery = mysqli_fetch_assoc($resultGallery)):
                        $gambar = !empty($gallery['gambar']) ? getLocalImage($gallery['gambar']) : getLocalImage('default_event.jpg');
                    ?>
                    <div class="gallery-item">
                        <img src="<?= htmlspecialchars($gambar) ?>" 
                             alt="<?= htmlspecialchars($gallery['judul']) ?>" 
                             class="gallery-img">
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <h4>PT INTIM</h4>
                <p>
                    Menghadirkan kain premium dengan kualitas terbaik. Terus aktif dalam berbagai kegiatan 
                    untuk pengembangan industri dan masyarakat.
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
                <h4>Kategori Kegiatan</h4>
                <a href="#" class="category-link" data-category="all" data-status="all">Semua Kegiatan</a>
                <a href="#" class="category-link" data-category="all" data-status="mendatang">Akan Datang</a>
                <a href="#" class="category-link" data-category="all" data-status="selesai">Selesai</a>
                <?php 
                if ($resultKategori && mysqli_num_rows($resultKategori) > 0):
                    mysqli_data_seek($resultKategori, 0);
                    while($kategori = mysqli_fetch_assoc($resultKategori)):
                        if (!empty($kategori['kategori'])):
                ?>
                <a href="#" class="category-link" data-category="<?= htmlspecialchars($kategori['kategori']) ?>" data-status="all">
                    <?= formatKategori(htmlspecialchars($kategori['kategori'])) ?>
                </a>
                <?php 
                        endif;
                    endwhile;
                endif; 
                ?>
            </div>
            
            <div class="footer-col">
                <h4>Tautan Cepat</h4>
                <a href="user_dashboard.php">Beranda</a>
                <a href="koleksi.php">Koleksi</a>
                <a href="berita_karir.php">Karir</a>
                <a href="berita_kegiatan.php">Kegiatan</a>
                <a href="tentang_profil.php">Profil Perusahaan</a>
                <a href="tentang_visimisi.php">Visi & Misi</a>
                <a href="kontak.php">Kontak</a>
            </div>
            
            <div class="footer-col">
                <h4>Kontak Kegiatan</h4>
                <p>Jl. Karangwuni-Dlimas, Klaten</p>
                <p>Email: event@innagrouptm.com</p>
                <p>Telepon: (0272) 5533 488 ext. 201</p>
                <p>Jam: Senin - Jumat, 08:00 - 16:00</p>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> PT Innagroup Textile Manufacture. All rights reserved.
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced Dropdown Functionality
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
            
            // Handle user menu
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
            
            // Mobile Menu Functionality
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
            
            // Header scroll effect
            const header = document.getElementById('header');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
            
            // Category filter functionality dengan error handling
            const categoryButtons = document.querySelectorAll('.category-btn');
            const activityCards = document.querySelectorAll('.activity-card');
            const activitiesGrid = document.getElementById('activitiesGrid');
            
            // Fungsi untuk menampilkan pesan "tidak ada kegiatan"
            function showNoActivitiesMessage(message = 'Tidak ada kegiatan yang sesuai dengan filter yang dipilih.') {
                // Hapus pesan sebelumnya jika ada
                const existingMessage = activitiesGrid.querySelector('.no-activities-message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                
                // Buat elemen pesan
                const messageDiv = document.createElement('div');
                messageDiv.className = 'no-activities-message empty-state';
                messageDiv.innerHTML = `
                    <div class="empty-state-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>Tidak Ada Kegiatan yang Tersedia</h3>
                    <p>${message}</p>
                    <button class="category-btn" id="resetFilterBtn" style="margin: 0 auto; text-decoration: none;">
                        <i class="fas fa-undo"></i>
                        Reset Filter
                    </button>
                `;
                
                // Tambahkan pesan ke grid
                activitiesGrid.appendChild(messageDiv);
                
                // Tambahkan event listener untuk tombol reset
                document.getElementById('resetFilterBtn').addEventListener('click', function() {
                    document.querySelector('.category-btn[data-category="all"][data-status="all"]').click();
                });
            }
            
            // Fungsi untuk menghapus pesan "tidak ada kegiatan"
            function removeNoActivitiesMessage() {
                const messageDiv = activitiesGrid.querySelector('.no-activities-message');
                if (messageDiv) {
                    messageDiv.remove();
                }
            }
            
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const category = this.getAttribute('data-category');
                    const status = this.getAttribute('data-status');
                    
                    let visibleCount = 0;
                    
                    // Filter activities
                    activityCards.forEach(card => {
                        const cardCategory = card.getAttribute('data-category');
                        const cardStatus = card.getAttribute('data-status');
                        
                        const categoryMatch = category === 'all' || cardCategory === category;
                        const statusMatch = status === 'all' || cardStatus === status;
                        
                        if (categoryMatch && statusMatch) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, 10);
                            visibleCount++;
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                    
                    // Cek apakah ada kartu yang ditampilkan
                    setTimeout(() => {
                        if (visibleCount === 0) {
                            let message = 'Tidak ada kegiatan yang sesuai dengan filter yang dipilih.';
                            if (category !== 'all' || status !== 'all') {
                                if (category !== 'all') {
                                    const categoryName = this.textContent.trim();
                                    message = `Tidak ada kegiatan dalam kategori "${categoryName}"`;
                                    if (status !== 'all') {
                                        const statusText = this.querySelector('i') ? this.querySelector('i').nextSibling.textContent.trim() : '';
                                        message += ` dengan status "${statusText}"`;
                                    }
                                } else if (status !== 'all') {
                                    const statusText = this.querySelector('i') ? this.querySelector('i').nextSibling.textContent.trim() : '';
                                    message = `Tidak ada kegiatan dengan status "${statusText}"`;
                                }
                            }
                            showNoActivitiesMessage(message);
                        } else {
                            removeNoActivitiesMessage();
                        }
                    }, 350);
                });
            });
            
            // Footer category links
            const footerCategoryLinks = document.querySelectorAll('.category-link');
            footerCategoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const category = this.getAttribute('data-category');
                    const status = this.getAttribute('data-status');
                    
                    // Find and click corresponding filter button
                    const correspondingButton = document.querySelector(`.category-btn[data-category="${category}"][data-status="${status}"]`);
                    if (correspondingButton) {
                        correspondingButton.click();
                        window.scrollTo({
                            top: document.querySelector('.category-filter').offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Mobile menu auto-close on resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    mobileMenuBtn.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            

            // Ambil parameter pencarian dari URL untuk JavaScript filter
            const urlParams = new URLSearchParams(window.location.search);
            const searchTerm = urlParams.get('cari');

            });
    </script>
</body>
</html>