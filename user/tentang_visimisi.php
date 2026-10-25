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
    <title>Visi & Misi - PT INTIM</title>
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

        /* Vision Mission Hero */
        .vm-hero {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: var(--white);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .vm-hero::before {
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

        .vm-hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .vm-hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .vm-badge {
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

        .vm-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--white);
        }

        .vm-hero-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Vision Mission Content */
        .vm-content {
            padding: 80px 0;
            background: var(--white);
        }

        .vm-content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .vm-intro {
            text-align: center;
            margin-bottom: 60px;
        }

        .vm-intro h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .vm-intro p {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Vision Section */
        .vision-section {
            margin-bottom: 80px;
        }

        .vision-container {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            border-radius: var(--radius-2xl);
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .vision-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent), var(--accent-light));
        }

        .vision-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .vision-icon {
            width: 80px;
            height: 80px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 32px;
            flex-shrink: 0;
            box-shadow: var(--shadow-lg);
        }

        .vision-header-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .vision-header-content p {
            font-size: 16px;
            color: var(--text-light);
        }

        .vision-statement {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            margin-bottom: 40px;
            position: relative;
        }

        .vision-statement::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 80px;
            color: var(--accent);
            opacity: 0.2;
            font-family: 'Playfair Display', serif;
            line-height: 1;
        }

        .vision-statement p {
            font-size: 18px;
            color: var(--text-dark);
            line-height: 1.8;
            position: relative;
            z-index: 1;
            font-style: italic;
        }

        .vision-details {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
        }

        .vision-details h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .vision-details h3::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--accent);
            border-radius: 2px;
        }

        .vision-details p {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        /* Mission Section */
        .mission-section {
            margin-bottom: 80px;
        }

        .mission-container {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: 60px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--gray-100);
            box-shadow: var(--shadow);
        }

        .mission-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent-light), var(--accent));
        }

        .mission-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .mission-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 32px;
            flex-shrink: 0;
            box-shadow: var(--shadow-lg);
        }

        .mission-header-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .mission-header-content p {
            font-size: 16px;
            color: var(--text-light);
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .mission-card {
            background: var(--gray-50);
            padding: 40px;
            border-radius: var(--radius-xl);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--gray-100);
        }

        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            background: var(--white);
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
        }

        .mission-card-number {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
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

        .mission-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary);
            padding-right: 50px;
        }

        .mission-card p {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Values Section */
        .values-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
        }

        .values-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .values-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .values-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .values-header p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .value-card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-xl);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
            color: var(--accent);
            transition: var(--transition);
        }

        .value-card:hover .value-icon {
            background: var(--accent);
            color: var(--white);
            transform: scale(1.1);
        }

        .value-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .value-card p {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Commitment Section */
        .commitment-section {
            padding: 80px 0;
            background: var(--white);
        }

        .commitment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .commitment-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .commitment-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .commitment-header p {
            font-size: 16px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .commitment-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
            border-radius: var(--radius-2xl);
            padding: 60px;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .commitment-content::before {
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

        .commitment-content h3 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 32px;
            color: var(--white);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .commitment-list {
            list-style: none;
            position: relative;
            z-index: 1;
        }

        .commitment-list li {
            position: relative;
            padding-left: 40px;
            margin-bottom: 24px;
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.9);
        }

        .commitment-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--white);
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
            .vm-hero-container,
            .vm-content-container,
            .values-container,
            .commitment-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .vm-hero-title {
                font-size: 42px;
            }
            
            .vm-intro h2,
            .values-header h2,
            .commitment-header h2 {
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
            
            .vision-header,
            .mission-header {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            
            .mission-grid {
                grid-template-columns: 1fr;
            }
            
            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .vision-container,
            .mission-container,
            .commitment-content {
                padding: 40px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .vm-hero,
            .vm-content,
            .values-section,
            .commitment-section {
                padding: 60px 0;
            }
            
            .vm-hero-title {
                font-size: 32px;
            }
            
            .vm-hero-subtitle {
                font-size: 16px;
            }
            
            .vm-intro h2,
            .values-header h2,
            .commitment-header h2 {
                font-size: 28px;
            }
            
            .vision-header-content h2,
            .mission-header-content h2 {
                font-size: 28px;
            }
            
            .values-grid {
                grid-template-columns: 1fr;
            }
            
            .vision-icon,
            .mission-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .vision-statement,
            .vision-details,
            .mission-card,
            .value-card {
                padding: 30px;
            }
        }

        @media (max-width: 576px) {
            .nav-container,
            .vm-hero-container,
            .vm-content-container,
            .values-container,
            .commitment-container,
            .footer-container {
                padding: 0 20px;
            }
            
            .vm-hero-title {
                font-size: 28px;
            }
            
            .vm-intro h2,
            .values-header h2,
            .commitment-header h2 {
                font-size: 24px;
            }
            
            .vision-header-content h2,
            .mission-header-content h2 {
                font-size: 24px;
            }
            
            .vision-container,
            .mission-container,
            .commitment-content {
                padding: 30px 20px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
            }
            
            .vision-statement p {
                font-size: 16px;
            }
            
            .mission-card-number {
                top: 15px;
                right: 15px;
                width: 30px;
                height: 30px;
                font-size: 14px;
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
                        Visi & Misi
                    </li>
                </ul>
            </div>
        </div>

        <!-- Vision Mission Hero -->
        <section class="vm-hero">
            <div class="vm-hero-container">
                <div class="vm-hero-content">
                    <div class="vm-badge">
                        <i class="fas fa-bullseye"></i>
                        <span>Visi & Misi Perusahaan</span>
                    </div>
                    <h1 class="vm-hero-title">Panduan Masa Depan Kami</h1>
                    <p class="vm-hero-subtitle">
                        Visi dan Misi kami adalah fondasi yang mengarahkan setiap langkah perusahaan menuju kesuksesan 
                        dan keunggulan dalam industri tekstil.
                    </p>
                </div>
            </div>
        </section>

        <!-- Vision Mission Content -->
        <section class="vm-content">
            <div class="vm-content-container">
                <!-- Introduction -->
                <div class="vm-intro">
                    <h2>Mengenal Visi & Misi PT INTIM</h2>
                    <p>
                        Visi dan Misi PT Innagroup Textile Manufacture (PT INTIM) merupakan kompas yang mengarahkan 
                        seluruh aktivitas perusahaan. Kami berkomitmen untuk tidak hanya menjadi penyedia kain berkualitas, 
                        tetapi juga menjadi mitra terpercaya yang berkontribusi positif bagi industri dan masyarakat.
                    </p>
                </div>

                <!-- Vision Section -->
                <div class="vision-section">
                    <div class="vision-container">
                        <div class="vision-header">
                            <div class="vision-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="vision-header-content">
                                <h2>Visi Perusahaan</h2>
                                <p>Pandangan jangka panjang yang menjadi tujuan akhir perusahaan</p>
                            </div>
                        </div>

                        <div class="vision-statement">
                            <p>
                                "Menjadi perusahaan tekstil terkemuka yang menghasilkan produk berkualitas tinggi dengan 
                                inovasi berkelanjutan dan berkomitmen terhadap kepuasan pelanggan serta kesejahteraan masyarakat."
                            </p>
                        </div>

                        <div class="vision-details">
                            <h3>Makna Visi Kami</h3>
                            <p>
                                Visi kami mencerminkan ambisi untuk menjadi pemimpin dalam industri tekstil dengan fokus 
                                pada tiga pilar utama: <strong>kualitas produk</strong>, <strong>inovasi berkelanjutan</strong>, 
                                dan <strong>tanggung jawab sosial</strong>.
                            </p>
                            <p>
                                Kami percaya bahwa keberhasilan perusahaan tidak hanya diukur dari keuntungan finansial, 
                                tetapi juga dari kontribusi positif yang diberikan kepada pelanggan, karyawan, dan masyarakat 
                                secara luas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mission Section -->
                <div class="mission-section">
                    <div class="mission-container">
                        <div class="mission-header">
                            <div class="mission-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="mission-header-content">
                                <h2>Misi Perusahaan</h2>
                                <p>Langkah-langkah strategis untuk mewujudkan visi perusahaan</p>
                            </div>
                        </div>

                        <div class="mission-grid">
                            <div class="mission-card">
                                <div class="mission-card-number">1</div>
                                <h3>Produk Berkualitas Tinggi</h3>
                                <p>
                                    Menghasilkan kain dengan standar kualitas internasional melalui proses produksi 
                                    yang terkontrol dan pengawasan mutu yang ketat.
                                </p>
                            </div>

                            <div class="mission-card">
                                <div class="mission-card-number">2</div>
                                <h3>Inovasi Berkelanjutan</h3>
                                <p>
                                    Mengembangkan teknologi dan metode produksi baru untuk menciptakan produk tekstil 
                                    yang inovatif, ramah lingkungan, dan sesuai dengan tren terkini.
                                </p>
                            </div>

                            <div class="mission-card">
                                <div class="mission-card-number">3</div>
                                <h3>Kepuasan Pelanggan</h3>
                                <p>
                                    Memberikan pelayanan terbaik dengan memahami kebutuhan pelanggan, memberikan solusi 
                                    tepat waktu, dan membangun hubungan jangka panjang yang saling menguntungkan.
                                </p>
                            </div>

                            <div class="mission-card">
                                <div class="mission-card-number">4</div>
                                <h3>Pengembangan SDM</h3>
                                <p>
                                    Menciptakan lingkungan kerja yang mendukung pengembangan kompetensi karyawan, 
                                    memberikan pelatihan berkelanjutan, dan memastikan kesejahteraan seluruh tim.
                                </p>
                            </div>

                            <div class="mission-card">
                                <div class="mission-card-number">5</div>
                                <h3>Tanggung Jawab Sosial</h3>
                                <p>
                                    Berkontribusi aktif dalam pengembangan masyarakat sekitar melalui program 
                                    pemberdayaan, pelestarian lingkungan, dan kegiatan sosial lainnya.
                                </p>
                            </div>

                            <div class="mission-card">
                                <div class="mission-card-number">6</div>
                                <h3>Kemitraan Strategis</h3>
                                <p>
                                    Menjalin kemitraan yang kuat dengan supplier, distributor, dan semua stakeholder 
                                    berdasarkan prinsip saling percaya dan keuntungan bersama.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="values-section">
            <div class="values-container">
                <div class="values-header">
                    <h2>Nilai-Nilai Perusahaan</h2>
                    <p>Prinsip-prinsip dasar yang membimbing perilaku dan keputusan kami</p>
                </div>

                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Integritas</h3>
                        <p>
                            Bertindak jujur, transparan, dan konsisten dalam semua aspek bisnis. 
                            Menjaga kepercayaan yang diberikan oleh pelanggan dan mitra.
                        </p>
                    </div>

                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Kualitas</h3>
                        <p>
                            Tidak pernah berkompromi dengan kualitas. Setiap produk harus memenuhi 
                            standar tertinggi sebelum sampai ke tangan pelanggan.
                        </p>
                    </div>

                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Inovasi</h3>
                        <p>
                            Terus mencari cara baru dan lebih baik dalam melakukan sesuatu. 
                            Mendukung kreativitas dan pemikiran out-of-the-box.
                        </p>
                    </div>

                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Kolaborasi</h3>
                        <p>
                            Bekerja sama sebagai satu tim yang solid. Menghargai kontribusi 
                            setiap individu dan membangun sinergi untuk mencapai tujuan bersama.
                        </p>
                    </div>

                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Keberlanjutan</h3>
                        <p>
                            Mengutamakan praktik bisnis yang ramah lingkungan dan berkelanjutan. 
                            Bertanggung jawab terhadap dampak sosial dan ekologis.
                        </p>
                    </div>

                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Kepedulian</h3>
                        <p>
                            Peduli terhadap pelanggan, karyawan, dan masyarakat. 
                            Membangun hubungan yang bermakna di luar transaksi bisnis.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Commitment Section -->
        <section class="commitment-section">
            <div class="commitment-container">
                <div class="commitment-header">
                    <h2>Komitmen Kami</h2>
                    <p>Janji yang kami pegang teguh dalam menjalankan bisnis</p>
                </div>

                <div class="commitment-content">
                    <h3>Kami Berkomitmen Untuk:</h3>
                    <ul class="commitment-list">
                        <li>Selalu memberikan produk dengan kualitas terbaik yang memenuhi standar internasional</li>
                        <li>Mengutamakan keselamatan dan kesejahteraan karyawan dalam setiap aktivitas operasional</li>
                        <li>Menjaga kelestarian lingkungan melalui praktik produksi yang ramah lingkungan</li>
                        <li>Memberikan pelayanan terbaik dan responsif terhadap kebutuhan pelanggan</li>
                        <li>Berkontribusi aktif dalam pengembangan masyarakat sekitar perusahaan</li>
                        <li>Menjaga transparansi dan akuntabilitas dalam semua aspek bisnis</li>
                        <li>Terus berinovasi dan beradaptasi dengan perkembangan teknologi terkini</li>
                        <li>Membangun kemitraan yang saling menguntungkan dengan semua stakeholder</li>
                    </ul>
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
                    Menghadirkan kain premium dengan kualitas terbaik dengan komitmen pada visi dan misi 
                    yang jelas untuk masa depan yang lebih baik.
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
        //  Dropdown fungsi
        document.addEventListener('DOMContentLoaded', function() {
            // inisialisais dropdown
            const dropdownToggles = document.querySelectorAll('.nav-dropdown .dropdown-toggle');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const dropdown = this.closest('.nav-dropdown');
                    const isActive = dropdown.classList.contains('active');
                    
                    // tutup dropwodn
                    document.querySelectorAll('.nav-dropdown').forEach(d => {
                        d.classList.remove('active');
                    });
                    
                    
                    if (!isActive) {
                        dropdown.classList.add('active');
                    }
                });
            });
            
            // tutup dropdowns ketika klik diluar
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
                    
                    // tutup lain dripdown
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

        // Mobile Menu fungsi
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

        // Mobile dropdown fungsi
        document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = this.closest('.mobile-dropdown');
                const content = dropdown.querySelector('.mobile-dropdown-content');
                const isActive = content.classList.contains('active');
                
                // tutup semua  dropdowns lain
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