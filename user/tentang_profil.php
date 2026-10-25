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

// Ambil parameter filter dan sorting (untuk search)
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';

// Ambil data motif untuk dropdown
$listMotif = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC");
$listMotifForDropdown = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC");

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
        '../assets/images/' . $imageName,
        '../images/' . $imageName,
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
    <title>Profil Perusahaan - PT INTIM</title>
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

        .nav-dropdown .dropdown-toggle .fa-chevron-down {
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .nav-dropdown.active .dropdown-toggle .fa-chevron-down {
            transform: rotate(180deg);
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

        /* User Menu */
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

        /* Profile Hero */
        .profile-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
            color: var(--white);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('<?= getLocalImage('pattern.png', '') ?>');
            opacity: 0.05;
            pointer-events: none;
        }

        .profile-hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }

        .profile-hero-content {
            max-width: 800px;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
            color: var(--white);
        }

        .profile-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--white);
        }

        .profile-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            line-height: 1.8;
            max-width: 600px;
        }

        .profile-stats {
            display: flex;
            gap: 40px;
            margin-top: 48px;
            flex-wrap: wrap;
        }

        .profile-stat {
            text-align: center;
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
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 500;
        }

        /* Company Info Section */
        .company-info {
            padding: 80px 0;
            background: var(--white);
        }

        .company-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .company-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .company-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .company-content h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .company-content p {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .company-highlights {
            margin-top: 32px;
        }

        .highlight-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .highlight-icon {
            width: 40px;
            height: 40px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 18px;
            flex-shrink: 0;
        }

        .highlight-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--primary);
        }

        .highlight-content p {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .company-image {
            position: relative;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            height: relative;
        }

        .company-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .company-image:hover img {
            transform: scale(1.05);
        }

        /* Vision Mission Section */
        .vision-mission {
            padding: 80px 0;
            background: var(--gray-50);
        }

        .vm-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .vm-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .vm-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .vm-header p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .vm-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
        }

        .vm-card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .vm-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .vm-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
        }

        .vm-icon {
            width: 60px;
            height: 60px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            font-size: 24px;
            color: var(--accent);
        }

        .vm-card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }

        .vm-card p {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .vm-list {
            list-style: none;
            margin-top: 16px;
        }

        .vm-list li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 10px;
            color: var(--text-light);
            font-size: 15px;
        }

        .vm-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-weight: bold;
        }

        /* History Section */
        .history-section {
            padding: 80px 0;
            background: var(--white);
        }

        .history-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .history-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .history-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .history-header p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .history-timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .history-timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background: var(--accent);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            width: 45%;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
        }

        .timeline-item:nth-child(even) {
            left: 55%;
        }

        .timeline-year {
            position: absolute;
            top: 0;
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            font-family: 'Playfair Display', serif;
        }

        .timeline-item:nth-child(odd) .timeline-year {
            right: -100px;
        }

        .timeline-item:nth-child(even) .timeline-year {
            left: -100px;
        }

        .timeline-content {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .timeline-content h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .timeline-content p {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Facilities Section */
        .facilities-section {
            padding: 80px 0;
            background: var(--gray-50);
        }

        .facilities-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .facilities-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .facilities-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .facility-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .facility-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .facility-image {
            height: 200px;
            overflow: hidden;
        }

        .facility-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .facility-card:hover .facility-image img {
            transform: scale(1.1);
        }

        .facility-content {
            padding: 24px;
        }

        .facility-content h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .facility-content p {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Contact Section */
        .contact-section {
            padding: 80px 0;
            background: var(--white);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .contact-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .contact-card {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: var(--accent);
        }

        .contact-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .contact-card p {
            font-size: 15px;
            color: var(--text-light);
            margin-bottom: 0;
            line-height: 1.6;
        }

        .contact-card a {
            color: var(--accent);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-card a:hover {
            color: var(--accent-dark);
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .nav-container,
            .company-container,
            .vm-container,
            .history-container,
            .facilities-container,
            .contact-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .profile-title {
                font-size: 42px;
            }
            
            .company-content h2,
            .vm-header h2,
            .history-header h2,
            .facilities-header h2,
            .contact-header h2 {
                font-size: 32px;
            }
        }

        @media (max-width: 992px) {
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
            
            .company-grid,
            .vm-grid,
            .facilities-grid,
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .profile-stats {
                gap: 30px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .timeline-item:nth-child(odd),
            .timeline-item:nth-child(even) {
                left: 0;
                width: 100%;
            }
            
            .timeline-item:nth-child(odd) .timeline-year,
            .timeline-item:nth-child(even) .timeline-year {
                position: relative;
                left: 0;
                right: auto;
                margin-bottom: 16px;
            }
            
            .history-timeline::before {
                left: 30px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .profile-hero,
            .company-info,
            .vision-mission,
            .history-section,
            .facilities-section,
            .contact-section {
                padding: 60px 0;
            }
            
            .profile-title {
                font-size: 32px;
            }
            
            .profile-subtitle {
                font-size: 16px;
            }
            
            .company-content h2,
            .vm-header h2,
            .history-header h2,
            .facilities-header h2,
            .contact-header h2 {
                font-size: 28px;
            }
            
            .company-grid {
                gap: 40px;
            }
            
            .profile-stats {
                gap: 20px;
            }
            
            .stat-number {
                font-size: 28px;
            }
        }

        @media (max-width: 576px) {
            .nav-container,
            .company-container,
            .vm-container,
            .history-container,
            .facilities-container,
            .contact-container,
            .footer-container {
                padding: 0 20px;
            }
            
            .profile-title {
                font-size: 28px;
            }
            
            .company-content h2,
            .vm-header h2,
            .history-header h2,
            .facilities-header h2,
            .contact-header h2 {
                font-size: 24px;
            }
            
            .vm-card,
            .contact-card {
                padding: 24px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 24px;
            }
        }
    </style>
</head>
<body>

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
                            <a href="berita_galeri.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'berita_kegiatan.php' ? 'active' : '' ?>">
                                <i class="fas fa-image"></i>
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
                        <a href="#">Tentang Kami</a>
                    </li>
                    <li class="breadcrumb-separator">/</li>
                    <li class="breadcrumb-item active">
                        Profil Perusahaan
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profile Hero Section -->
        <section class="profile-hero">
            <div class="profile-hero-container">
                <div class="profile-hero-content">
                    <div class="profile-badge">
                        <i class="fas fa-star"></i>
                        <span>Profil Perusahaan</span>
                    </div>
                    <h1 class="profile-title">PT Innagroup Textile Manufacture</h1>
                    <p class="profile-subtitle">
                        Perusahaan yang bergerak di bidang produksi textile (PI TPT) dengan produk utama kain Fabric 
                        (100% Polyester), dan melakukan diversifikasi produk dengan bahan dasar rayon dan katun.
                    </p>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="stat-number">2017</span>
                            <span class="stat-label">Tahun Berdiri</span>
                        </div>
                        <div class="profile-stat">
                            <span class="stat-number">ISO 9001</span>
                            <span class="stat-label">Sertifikasi</span>
                        </div>
                        <div class="profile-stat">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Kualitas</span>
                        </div>
                        <div class="profile-stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Klien</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Company Info Section -->
        <section class="company-info">
            <div class="company-container">
                <div class="company-grid">
                    <div class="company-content">
                        <h2>Tentang Perusahaan</h2>
                        <p>
                            PT Innagroup Textile Manufacture (PT INTIM) merupakan perusahaan yang bergerak di bidang produksi textile (PI TPT) 
                            dengan produk utama kain Fabric (100% Polyester), selain itu kami melakukan diversifikasi produk dengan bahan dasar rayon dan katun.
                        </p>
                        <p>
                            Meskipun kami baru berproduksi pada tahun 2017, namun dengan waktu yang tidak terlalu lama perusahaan kami telah menjadi salah satu 
                            perusahaan yang diperhitungkan secara kualitas serta memiliki daya saing yang kompetitif di kancah market nasional.
                        </p>
                        <p>
                            Perusahaan kami berada di Klaten, Jawa Tengah yang merupakan salah satu sentra tekstil terbesar di Indonesia.
                        </p>
                        
                        <div class="company-highlights">
                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="highlight-content">
                                    <h4>Kualitas Terjamin</h4>
                                    <p>Setiap produk melalui proses quality control yang ketat untuk menjamin kualitas terbaik</p>
                                </div>
                            </div>
                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <div class="highlight-content">
                                    <h4>Teknologi Modern</h4>
                                    <p>Menggunakan mesin-mesin berteknologi terkini untuk hasil produksi yang maksimal</p>
                                </div>
                            </div>
                            <div class="highlight-item">
                                <div class="highlight-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="highlight-content">
                                    <h4>Tim Profesional</h4>
                                    <p>Didukung oleh tim yang berpengalaman dan kompeten di bidangnya</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="company-image">
                        <img src="<?= getLocalImage( 'logo small black.png') ?>" alt="PT INTIM logo">
                    </div>
                </div>
            </div>
        </section>

        <!-- Vision Mission Section -->
        <section class="vision-mission">
            <div class="vm-container">
                <div class="vm-header">
                    <h2>Visi & Misi Perusahaan</h2>
                    <p>Pedoman dasar yang mengarahkan setiap langkah perusahaan dalam mencapai tujuan</p>
                </div>
                
                <div class="vm-grid">
                    <div class="vm-card">
                        <div class="vm-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Visi</h3>
                        <p>Menjadi perusahaan tekstil terkemuka yang menghasilkan produk berkualitas tinggi dengan inovasi berkelanjutan dan berkomitmen terhadap kepuasan pelanggan serta kesejahteraan masyarakat.</p>
                    </div>
                    
                    <div class="vm-card">
                        <div class="vm-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Misi</h3>
                        <ul class="vm-list">
                            <li>Menghasilkan produk kain berkualitas tinggi dengan standar internasional</li>
                            <li>Mengembangkan inovasi produk tekstil yang ramah lingkungan</li>
                            <li>Memberikan pelayanan terbaik kepada pelanggan</li>
                            <li>Menciptakan lingkungan kerja yang aman dan nyaman bagi karyawan</li>
                            <li>Berkontribusi aktif dalam pengembangan masyarakat sekitar</li>
                            <li>Menjalin kemitraan yang saling menguntungkan dengan semua stakeholder</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- History Section -->
        <section class="history-section">
            <div class="history-container">
                <div class="history-header">
                    <h2>Sejarah Perusahaan</h2>
                    <p>Perjalanan PT INTIM dari awal berdiri hingga menjadi salah satu perusahaan tekstil terpercaya</p>
                </div>
                
                <div class="history-timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2017</div>
                        <div class="timeline-content">
                            <h4>Pendirian Perusahaan</h4>
                            <p>PT Innagroup Textile Manufacture resmi didirikan dan memulai produksi perdana dengan fokus pada kain polyester berkualitas.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2018</div>
                        <div class="timeline-content">
                            <h4>Ekspansi Pasar</h4>
                            <p>Memperluas jaringan distribusi ke berbagai daerah di Jawa dan Bali, serta mulai mengekspor ke beberapa negara tetangga.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2019</div>
                        <div class="timeline-content">
                            <h4>Diversifikasi Produk</h4>
                            <p>Memulai produksi kain dengan bahan dasar rayon dan katun untuk memenuhi kebutuhan pasar yang semakin beragam.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2020</div>
                        <div class="timeline-content">
                            <h4>Sertifikasi ISO 9001</h4>
                            <p>Mendapatkan sertifikasi ISO 9001:2015 sebagai bukti komitmen terhadap sistem manajemen mutu yang terstandarisasi.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2022</div>
                        <div class="timeline-content">
                            <h4>Modernisasi Mesin</h4>
                            <p>Menginvestasikan dana untuk pembaruan mesin produksi dengan teknologi terkini guna meningkatkan efisiensi dan kualitas.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">
                            <h4>Ekspansi Pabrik</h4>
                            <p>Memperluas area produksi untuk meningkatkan kapasitas produksi dan melayani permintaan pasar yang semakin meningkat.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Facilities Section -->
        <section class="facilities-section">
            <div class="facilities-container">
                <div class="facilities-header">
                    <h2>Fasilitas & Teknologi</h2>
                    <p>Didukung dengan fasilitas produksi modern dan teknologi terkini untuk hasil terbaik</p>
                </div>
                
                <div class="facilities-grid">
                    <div class="facility-card">
                        <div class="facility-image">
                            <img src="<?= getLocalImage('weaving.jpg', 'logo.png') ?>" alt="Weaving Machines">
                        </div>
                        <div class="facility-content">
                            <h3>Mesin Tenun Modern</h3>
                            <p>Menggunakan mesin tenun berteknologi tinggi yang mampu menghasilkan kain dengan kerapatan dan kualitas konsisten.</p>
                        </div>
                    </div>
                    
                    <div class="facility-card">
                        <div class="facility-image">
                            <img src="<?= getLocalImage('dyeing.jpg', 'logo.png') ?>" alt="Dyeing Facility">
                        </div>
                        <div class="facility-content">
                            <h3>Unit Pencelupan</h3>
                            <p>Fasilitas pencelupan modern dengan sistem pengolahan limbah yang ramah lingkungan dan hemat energi.</p>
                        </div>
                    </div>
                    
                    <div class="facility-card">
                        <div class="facility-image">
                            <img src="<?= getLocalImage('printing.jpg', 'logo.png') ?>" alt="Printing Machine">
                        </div>
                        <div class="facility-content">
                            <h3>Mesin Printing</h3>
                            <p>Digital printing dengan presisi tinggi untuk motif yang detail dan warna yang cerah serta tahan lama.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section">
            <div class="contact-container">
                <div class="contact-header">
                    <h2>Kontak & Lokasi</h2>
                    <p>Hubungi kami untuk informasi lebih lanjut mengenai produk dan layanan</p>
                </div>
                
                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Lokasi Pabrik</h3>
                        <p>
                            Jl. Karangwuni-Dlimas, Dlimas, Ceper,<br>
                            Klaten, Jawa Tengah, Indonesia
                        </p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Telepon</h3>
                        <p>
                            <a href="tel:02725533488">(0272) 5533 488</a><br>
                            <a href="tel:081226122611">0812 2612 2611</a>
                        </p>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email</h3>
                        <p>
                            <a href="mailto:headoffice@innagrouptm.com">headoffice@innagrouptm.com</a><br>
                            <a href="mailto:marketing@innagrouptm.com">marketing@innagrouptm.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <h4>PT INTIM</h4>
                <p>
                    Menghadirkan kain premium dengan kualitas terbaik sejak 2017. 
                    Komitmen kami adalah memberikan produk terbaik dengan sentuhan inovasi terkini.
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
            
            // Header scroll effect
            const header = document.getElementById('header');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
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

        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                mobileMenuBtn.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    </script>
</body>
</html>