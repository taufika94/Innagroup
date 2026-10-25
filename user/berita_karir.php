<?php
session_start();
include '../includes/config.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data user
$username = $_SESSION['username'];
$userQuery = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
$namaLengkap = $userData['username'] ?? $username;

// Cek apakah tabel karir ada
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'karir'");
$tableExists = mysqli_num_rows($checkTable) > 0;

// Inisialisasi variabel
$lokasiFilter = isset($_GET['lokasi']) ? mysqli_real_escape_string($conn, $_GET['lokasi']) : '';
$tipeFilter = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : '';
$sortOrder = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'terbaru';

$totalKarir = 0;
$result = false;
$lokasiList = false;
$tipeList = false;

// Jika tabel ada, jalankan query
if ($tableExists) {
    // Query SQL untuk karir dengan filter
    $querySql = "SELECT * FROM karir WHERE 1=1";
    $querySql .= " AND is_active = 1";
    
    
    if ($lokasiFilter != '') {
        $querySql .= " AND location = '$lokasiFilter'";
    }
    if ($tipeFilter != '') {
        $querySql .= " AND type = '$tipeFilter'";
    }
    
    // Sorting
    if ($sortOrder == 'terbaru') {
        $querySql .= " ORDER BY created_at DESC";
    } elseif ($sortOrder == 'deadline') {
        $querySql .= " ORDER BY deadline ASC";
    } elseif ($sortOrder == 'lokasi_az') {
        $querySql .= " ORDER BY location ASC";
    }
    
    $result = mysqli_query($conn, $querySql);
    if ($result) {
        $totalKarir = mysqli_num_rows($result);
    }
    
    // Ambil data untuk dropdown filter
    $lokasiList = mysqli_query($conn, "SELECT DISTINCT location FROM karir WHERE location IS NOT NULL AND location != '' AND is_active = 1 ORDER BY location ASC");
    $tipeList = mysqli_query($conn, "SELECT DISTINCT type FROM karir WHERE type IS NOT NULL AND type != '' AND is_active = 1 ORDER BY type ASC");
} else {
    // Coba alternatif nama tabel dengan huruf kecil semua
    $checkTable2 = mysqli_query($conn, "SHOW TABLES LIKE 'KARIR'");
    $tableExists2 = mysqli_num_rows($checkTable2) > 0;
    
    if ($tableExists2) {
        // Jika tabel ada dengan huruf besar
        $tableExists = true;
        // Gunakan nama tabel dengan case sensitive
        $result = mysqli_query($conn, "SELECT * FROM KARIR WHERE is_active = 1 AND deadline >= CURDATE()");
        if ($result) {
            $totalKarir = mysqli_num_rows($result);
        }
    }
}

// Format tanggal Indonesia
function formatTanggal($date) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') return '-';
    
    // Coba parse tanggal
    $timestamp = strtotime($date);
    if ($timestamp === false) return '-';
    
    $bulan = array(
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    );
    
    $tanggal = date('d', $timestamp);
    $bulanNum = date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulanNum] . ' ' . $tahun;
}

// Fungsi untuk mendapatkan path gambar lokal
function getLocalImage($imageName, $default = 'logo.png') {
    // Daftar ekstensi yang dicoba
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Jika sudah full path, langsung return
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    // Coba berbagai lokasi
    $possibleLocations = [
        '../uploads/' . $imageName,
        'assets/images/' . $imageName,
        'images/' . $imageName,
        'img/' . $imageName,
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT INTIM - Lowongan Karir</title>
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
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
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

        /* Warning jika tabel tidak ada */
        .table-warning {
            max-width: 1400px;
            margin: 120px auto 40px;
            padding: 40px;
            text-align: center;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: var(--radius-xl);
            color: #856404;
        }

        .table-warning h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #856404;
        }

        .table-warning p {
            margin-bottom: 16px;
        }

        .table-warning code {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
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

        

        /* Hero Section Karir */
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
            position: relative;
            z-index: 1;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
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

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .hero-description {
            font-size: 18px;
            color: var(--text-light);
            margin-bottom: 32px;
            line-height: 1.8;
        }

        /* Karir Stats */
        .karir-stats {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 48px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 24px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            min-width: 150px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent);
            display: block;
            line-height: 1;
            margin-bottom: 8px;
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Filter System Karir */
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
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            color: var(--text-dark);
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            min-width: 180px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .reset-btn {
            padding: 10px 24px;
            background: transparent;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reset-btn:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        /* Lowongan Grid */
        .lowongan-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px 100px;
        }

        .lowongan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 32px;
        }

        @media (max-width: 768px) {
            .lowongan-grid {
                grid-template-columns: 1fr;
            }
        }

        .lowongan-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--gray-100);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .lowongan-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent);
        }

        .lowongan-header {
            background: linear-gradient(135deg, var(--primary), var(--gray-800));
            color: var(--white);
            padding: 24px;
            position: relative;
        }

        .lowongan-department {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.9;
            margin-bottom: 8px;
            display: block;
        }

        .lowongan-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.3;
            font-family: 'Playfair Display', serif;
        }

        .lowongan-meta {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            opacity: 0.9;
        }

        .meta-item i {
            font-size: 12px;
        }

        .lowongan-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-fulltime {
            background: var(--success);
            color: white;
        }

        .badge-parttime {
            background: var(--warning);
            color: white;
        }

        .badge-contract {
            background: var(--accent);
            color: white;
        }

        .lowongan-content {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .lowongan-description {
            color: var(--text-light);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .lowongan-requirements {
            margin-bottom: 24px;
        }

        .requirements-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .requirements-list {
            list-style: none;
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.6;
        }

        .requirements-list li {
            margin-bottom: 4px;
            padding-left: 16px;
            position: relative;
        }

        .requirements-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--accent);
        }

        .lowongan-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .deadline-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 13px;
        }

        .deadline-warning {
            color: var(--danger);
            font-weight: 500;
        }

        .apply-btn {
            padding: 10px 24px;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .apply-btn:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3);
        }

        .apply-btn.disabled {
            background: var(--gray-300);
            cursor: not-allowed;
        }

        .apply-btn.disabled:hover {
            transform: none;
            box-shadow: none;
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

        /* Modal Detail Lowongan */
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
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            position: relative;
            max-width: 800px;
            max-height: 90vh;
            background: var(--white);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            animation: scaleIn 0.3s ease-out;
            width: 100%;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--gray-800));
            color: var(--white);
            padding: 32px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            font-size: 18px;
            color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 32px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-section {
            margin-bottom: 32px;
        }

        .modal-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary);
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-100);
        }

        .modal-section p {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .modal-footer {
            padding: 24px 32px;
            border-top: 1px solid var(--gray-100);
            display: flex;
            justify-content: flex-end;
            gap: 16px;
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .nav-container,
            .hero-container,
            .filter-system,
            .lowongan-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .hero-title {
                font-size: 42px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
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
            
            
            /* User menu di mobile */
            .user-menu {
                order: 1;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 15px;
            }
            
            .filter-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .filter-controls {
                flex-direction: column;
                gap: 16px;
            }
            
            .filter-group {
                flex-wrap: wrap;
            }
            
            .filter-select {
                flex: 1;
                min-width: auto;
            }
            
            .karir-stats {
                gap: 24px;
            }
            
            .stat-item {
                min-width: 120px;
                padding: 16px;
            }
            
            .stat-number {
                font-size: 28px;
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
            
            .lowongan-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .filter-wrapper {
                padding: 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        @media (max-width: 576px) {
            .nav-container,
            .hero-container,
            .filter-system,
            .lowongan-container,
            .footer-container {
                padding: 0 20px;
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .karir-stats {
                flex-direction: column;
                align-items: center;
            }
            
            .stat-item {
                width: 100%;
                max-width: 200px;
            }
            
            .modal-body {
                padding: 24px;
            }
            
            .modal-header {
                padding: 24px;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer .apply-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
        
        
        
        <nav class="mobile-nav">
            <a href="user_dashboard.php" class="mobile-nav-link">
                <span>
                    <i class="fas fa-home"></i>
                    Beranda
                </span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="koleksi.php" class="mobile-nav-link">
                <span>
                    <i class="fas fa-th-large"></i>
                    Koleksi
                </span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <!-- Acara Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle active">
                    <span>
                        <i class="fas fa-calendar-alt"></i>
                        Acara
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mobile-dropdown-content" id="mobileAcaraDropdown">
                    <a href="berita_karir.php" class="mobile-dropdown-link active">
                        <i class="fas fa-briefcase"></i>
                        Karir
                    </a>
                    <a href="berita_kegiatan.php" class="mobile-dropdown-link">
                        <i class="fas fa-users"></i>
                        Kegiatan
                    </a>
                    <a href="berita_galeri.php" class="mobile-dropdown-link">
                        <i class="fas fa-image"></i>
                        Galeri
                    </a>
                </div>
            </div>
            
            <!-- Tentang Kami Dropdown Mobile -->
            <div class="mobile-dropdown">
                <a href="#" class="mobile-nav-link mobile-dropdown-toggle">
                    <span>
                        <i class="fas fa-info-circle"></i>
                        Tentang Kami
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="mobile-dropdown-content" id="mobileTentangDropdown">
                    <a href="tentang_profil.php" class="mobile-dropdown-link">
                        <i class="fas fa-building"></i>
                        Profil Perusahaan
                    </a>
                    <a href="tentang_visimisi.php" class="mobile-dropdown-link">
                        <i class="fas fa-bullseye"></i>
                        Visi & Misi
                    </a>
                </div>
            </div>
            
            <a href="kontak.php" class="mobile-nav-link">
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

    <!-- Modal Detail Lowongan -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </button>
                <span class="lowongan-department" id="modalDepartment"></span>
                <h2 class="lowongan-title" id="modalTitle"></h2>
                <div class="lowongan-meta">
                    <span class="meta-item" id="modalLocation"></span>
                    <span class="meta-item" id="modalType"></span>
                    <span class="meta-item" id="modalDeadline"></span>
                </div>
            </div>
            <div class="modal-body">
                <div class="modal-section">
                    <h3>Deskripsi Pekerjaan</h3>
                    <p id="modalDescription"></p>
                </div>
                <div class="modal-section">
                    <h3>Kualifikasi & Persyaratan</h3>
                    <div id="modalRequirements"></div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="apply-btn" id="modalApplyBtn">
                    <i class="fas fa-paper-plane"></i>
                    Lamar Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- Header & Navigation -->
    <header class="header" id="header">
        <div class="nav-container">
            <!-- Logo -->
            <a href="user_dashboard.php" class="logo">
                <img src="<?= getLocalImage('logo small black.png') ?>" alt="PT INTIM">
            </a>

            <!-- Main Navigation -->
            <nav class="nav-main">
                <a href="user_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Beranda</span>
                </a>
                <a href="koleksi.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Koleksi</span>
                </a>
                
                <!-- Acara Dropdown -->
                <div class="nav-dropdown" id="acaraDropdown">
                    <div class="dropdown-toggle nav-link active">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Acara</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="berita_karir.php" class="dropdown-item active">
                                <i class="fas fa-briefcase"></i>
                                <span>Karir</span>
                            </a>
                            <a href="berita_kegiatan.php" class="dropdown-item">
                                <i class="fas fa-users"></i>
                                <span>Kegiatan</span>
                            </a>
                            <a href="berita_galeri.php" class="dropdown-item">
                                <i class="fas fa-image"></i>
                                <span>Galeri</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tentang Kami Dropdown -->
                <div class="nav-dropdown" id="tentangDropdown">
                    <div class="dropdown-toggle nav-link">
                        <i class="fas fa-info-circle"></i>
                        <span>Tentang Kami</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-content">
                        <div class="dropdown-links">
                            <a href="tentang_profil.php" class="dropdown-item">
                                <i class="fas fa-building"></i>
                                <span>Profil Perusahaan</span>
                            </a>
                            <a href="tentang_visimisi.php" class="dropdown-item">
                                <i class="fas fa-bullseye"></i>
                                <span>Visi & Misi</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kontak -->
                <a href="kontak.php" class="nav-link">
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

    <?php if (!$tableExists): ?>
        <!-- Warning jika tabel tidak ada -->
        <div class="table-warning">
            <h2><i class="fas fa-exclamation-triangle"></i> Tabel Karir Tidak Ditemukan</h2>
            <p>Tabel <code>karir</code> tidak ditemukan di database <code>db_intim</code>.</p>
            <p>Silakan buat tabel terlebih dahulu dengan struktur berikut:</p>
            <pre style="text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; overflow-x: auto;">

            </pre>
            <p>Atau gunakan data dummy:</p>
            <pre style="text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; overflow-x: auto;">

            </pre>
            <a href="user_dashboard.php" class="apply-btn">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Dashboard
            </a>
        </div>
    <?php else: ?>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-bg-pattern"></div>
            <div class="hero-container">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-briefcase"></i>
                        <span>Karir & Lowongan</span>
                    </div>
                    <h1 class="hero-title">Bergabunglah dengan Tim Kami</h1>
                    <p class="hero-description">
                        PT INTIM membuka kesempatan bagi talenta-talenta terbaik untuk bergabung bersama kami. 
                        Temukan lowongan yang sesuai dengan keahlian dan passion Anda.
                    </p>
                    
                    <div class="karir-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= $totalKarir ?></span>
                            <span class="stat-label">Lowongan Aktif</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">5+</span>
                            <span class="stat-label">Departemen</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">3+</span>
                            <span class="stat-label">Lokasi Kerja</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">24/7</span>
                            <span class="stat-label">Aplikasi Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filter System -->
        <div class="filter-system">
            <div class="filter-wrapper">
                <div class="filter-header">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filter Lowongan
                    </div>
                    <div class="filter-controls">
                        <form method="GET" action="" id="filterForm" class="filter-group">
                            <!-- Sort Dropdown -->
                            <select name="sort" id="sortSelect" class="filter-select" onchange="this.form.submit()">
                                <option value="terbaru" <?= ($sortOrder == 'terbaru') ? 'selected' : '' ?>>Sortir: Terbaru</option>
                                <option value="deadline" <?= ($sortOrder == 'deadline') ? 'selected' : '' ?>>Sortir: Deadline Terdekat</option>
                                <option value="lokasi_az" <?= ($sortOrder == 'lokasi_az') ? 'selected' : '' ?>>Sortir: Lokasi A-Z</option>
                            </select>
                            
                            <!-- Lokasi Filter -->
                            <select name="lokasi" id="lokasiSelect" class="filter-select" onchange="this.form.submit()">
                                <option value="">Semua Lokasi</option>
                                <?php if ($lokasiList): ?>
                                    <?php mysqli_data_seek($lokasiList, 0); ?>
                                    <?php while($lokasi = mysqli_fetch_assoc($lokasiList)): ?>
                                        <option value="<?= htmlspecialchars($lokasi['location']) ?>" 
                                            <?= ($lokasiFilter == $lokasi['location']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lokasi['location']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            
                            <!-- Tipe Filter -->
                            <select name="tipe" id="tipeSelect" class="filter-select" onchange="this.form.submit()">
                                <option value="">Semua Tipe</option>
                                <?php if ($tipeList): ?>
                                    <?php mysqli_data_seek($tipeList, 0); ?>
                                    <?php while($tipe = mysqli_fetch_assoc($tipeList)): ?>
                                        <option value="<?= htmlspecialchars($tipe['type']) ?>" 
                                            <?= ($tipeFilter == $tipe['type']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipe['type']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            
                            
                        </form>
                        
                        <!-- Reset Button -->
                        <a href="?" class="reset-btn">
                            <i class="fas fa-redo"></i>
                            Reset Filter
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lowongan Grid -->
        <div class="lowongan-container">
            <?php if ($totalKarir > 0): ?>
                <div class="lowongan-grid" id="lowonganGrid">
                    <?php mysqli_data_seek($result, 0); ?>
                    <?php while($karir = mysqli_fetch_assoc($result)) : ?>
                        <?php
                        // Tentukan badge class berdasarkan tipe
                        $badgeClass = '';
                        if ($karir['type'] == 'Full-time') {
                            $badgeClass = 'badge-fulltime';
                        } elseif ($karir['type'] == 'Part-time') {
                            $badgeClass = 'badge-parttime';
                        } elseif ($karir['type'] == 'Contract') {
                            $badgeClass = 'badge-contract';
                        }
                        
                        // Cek apakah deadline sudah lewat
                        $deadline = strtotime($karir['deadline']);
                        $today = strtotime(date('Y-m-d'));
                        $isExpired = $deadline < $today;
                        
                        // Format requirements untuk preview
                        $requirementsPreview = !empty($karir['requirements']) ? 
                            implode(' ', array_slice(explode(' ', $karir['requirements']), 0, 20)) . '...' : 
                            'Tidak ada persyaratan khusus.';
                        ?>
                        
                        <div class="lowongan-card" data-id="<?= $karir['id'] ?>">
                            <div class="lowongan-header">
                                <span class="lowongan-department"><?= htmlspecialchars($karir['department']) ?></span>
                                <h3 class="lowongan-title"><?= htmlspecialchars($karir['position']) ?></h3>
                                <div class="lowongan-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($karir['location'] ?? 'Tidak ditentukan') ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars($karir['type']) ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= formatTanggal($karir['deadline']) ?>
                                    </span>
                                </div>
                                <span class="lowongan-badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($karir['type']) ?>
                                </span>
                            </div>
                            
                            <div class="lowongan-content">
                                <p class="lowongan-description">
                                    <?= htmlspecialchars(substr($karir['description'] ?? '', 0, 150)) . '...' ?>
                                </p>
                                
                                <div class="lowongan-requirements">
                                    <h4 class="requirements-title">Persyaratan:</h4>
                                    <p style="font-size: 13px; color: var(--text-light);">
                                        <?= htmlspecialchars($requirementsPreview) ?>
                                    </p>
                                </div>
                                
                                <div class="lowongan-footer">
                                    <div class="deadline-info">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Tutup: <strong><?= formatTanggal($karir['deadline']) ?></strong></span>
                                        <?php if ($isExpired): ?>
                                            <span class="deadline-warning">(Telah berakhir)</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="apply-btn detail-btn <?= $isExpired ? 'disabled' : '' ?>" 
                                            data-id="<?= $karir['id'] ?>"
                                            data-department="<?= htmlspecialchars($karir['department']) ?>"
                                            data-position="<?= htmlspecialchars($karir['position']) ?>"
                                            data-location="<?= htmlspecialchars($karir['location'] ?? '') ?>"
                                            data-type="<?= htmlspecialchars($karir['type']) ?>"
                                            data-deadline="<?= formatTanggal($karir['deadline']) ?>"
                                            data-description="<?= htmlspecialchars($karir['description'] ?? '') ?>"
                                            data-requirements="<?= htmlspecialchars($karir['requirements'] ?? '') ?>">
                                        <i class="fas fa-eye"></i>
                                        <?= $isExpired ? 'Lowongan Berakhir' : 'Lihat Detail' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Tidak Ada Lowongan Saat Ini</h3>
                    <p>Maaf, tidak ada lowongan yang sesuai dengan filter pencarian Anda.</p>
                    <a href="?" class="apply-btn">
                        <i class="fas fa-redo"></i>
                        Reset Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
                <h4>Lowongan Terbaru</h4>
                <?php if ($tableExists): ?>
                    <?php
                    // Ambil 5 lowongan terbaru untuk footer
                    $recentKarir = mysqli_query($conn, "SELECT * FROM karir WHERE is_active = 1 AND deadline >= CURDATE() ORDER BY created_at DESC LIMIT 5");
                    if ($recentKarir && mysqli_num_rows($recentKarir) > 0):
                        while($recent = mysqli_fetch_assoc($recentKarir)):
                    ?>
                    <a href="#" class="recent-job" data-id="<?= $recent['id'] ?>">
                        <strong><?= htmlspecialchars($recent['position']) ?></strong><br>
                        <small><?= htmlspecialchars($recent['department']) ?> • <?= htmlspecialchars($recent['type']) ?></small>
                    </a>
                    <?php 
                        endwhile;
                    else:
                        echo '<p>Tidak ada lowongan saat ini</p>';
                    endif;
                    ?>
                <?php else: ?>
                    <p>Tabel karir belum tersedia</p>
                <?php endif; ?>
            </div>
            
            <div class="footer-col">
                <h4>Tautan Cepat</h4>
                <a href="user_dashboard.php">Beranda</a>
                <a href="koleksi.php">Koleksi</a>
                <a href="berita_karir.php" class="active">Karir</a>
                <a href="berita_kegiatan.php">Kegiatan</a>
                <a href="tentang_profil.php">Profil Perusahaan</a>
                <a href="tentang_visimisi.php">Visi & Misi</a>
                <a href="kontak.php">Kontak</a>
            </div>
            
            <div class="footer-col">
                <h4>Info Karir</h4>
                <p><i class="fas fa-phone"></i> (0272) 5533 488</p>
                <p><i class="fas fa-envelope"></i> karir@innagrouptm.com</p>
                <p><i class="fas fa-clock"></i> Senin - Jumat: 08:00 - 16:00</p>
                <p><i class="fas fa-map-marker-alt"></i> Jl. Karangwuni-Dlimas, Klaten</p>
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
        });

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

        // Mobile menu auto-close on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                if (mobileMenuBtn && mobileMenuOverlay) {
                    mobileMenuBtn.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Modal Functionality
        const detailModal = document.getElementById('detailModal');
        const modalClose = document.getElementById('modalClose');
        const detailButtons = document.querySelectorAll('.detail-btn');
        
        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return;
                
                const id = this.getAttribute('data-id');
                const department = this.getAttribute('data-department');
                const position = this.getAttribute('data-position');
                const location = this.getAttribute('data-location');
                const type = this.getAttribute('data-type');
                const deadline = this.getAttribute('data-deadline');
                const description = this.getAttribute('data-description');
                const requirements = this.getAttribute('data-requirements');
                
                // Set modal content
                document.getElementById('modalDepartment').textContent = department;
                document.getElementById('modalTitle').textContent = position;
                document.getElementById('modalLocation').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${location}`;
                document.getElementById('modalType').innerHTML = `<i class="fas fa-clock"></i> ${type}`;
                document.getElementById('modalDeadline').innerHTML = `<i class="fas fa-calendar-alt"></i> Tutup: ${deadline}`;
                document.getElementById('modalDescription').textContent = description;
                
                // Format requirements as list
                const requirementsDiv = document.getElementById('modalRequirements');
                requirementsDiv.innerHTML = '';
                
                if (requirements.trim() !== '') {
                    const requirementsList = requirements.split('\n').filter(item => item.trim() !== '');
                    if (requirementsList.length > 0) {
                        const ul = document.createElement('ul');
                        ul.className = 'requirements-list';
                        requirementsList.forEach(req => {
                            const li = document.createElement('li');
                            li.textContent = req.trim();
                            ul.appendChild(li);
                        });
                        requirementsDiv.appendChild(ul);
                    } else {
                        requirementsDiv.innerHTML = '<p>Tidak ada persyaratan khusus.</p>';
                    }
                } else {
                    requirementsDiv.innerHTML = '<p>Tidak ada persyaratan khusus.</p>';
                }
                
                // Set apply button link
                const applyBtn = document.getElementById('modalApplyBtn');
                applyBtn.href = `lamar.php?id=${id}`;
                
                // Show modal
                detailModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close modal
        if (modalClose && detailModal) {
            modalClose.addEventListener('click', function() {
                detailModal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close modal when clicking outside
        if (detailModal) {
            detailModal.addEventListener('click', function(e) {
                if (e.target === detailModal) {
                    detailModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Auto-submit filter forms
        const filterSelects = document.querySelectorAll('.filter-select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                if (this.id !== 'sortSelect') {
                    this.form.submit();
                }
            });
        });

        // Recent jobs in footer click handler
        document.querySelectorAll('.recent-job').forEach(job => {
            job.addEventListener('click', function(e) {
                e.preventDefault();
                const jobId = this.getAttribute('data-id');
                
                // Find and click the corresponding detail button
                const detailBtn = document.querySelector(`.detail-btn[data-id="${jobId}"]`);
                if (detailBtn && !detailBtn.classList.contains('disabled')) {
                    detailBtn.click();
                }
            });
        });

        
    </script>
</body>
</html>