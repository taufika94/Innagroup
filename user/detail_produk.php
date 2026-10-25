<?php
session_start();
include '../includes/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil ID (kode_inna) dari URL
if (!isset($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM produk WHERE kode_inna = '$id'");

// Debug query
if (!$query) {
    die("Query error: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='user_dashboard.php';</script>";
    exit;
}

// Debug: Tampilkan data yang diambil
// var_dump($data); exit;

// --- LOGIKA PENGOLAHAN DATA ---
// Ambil gambar unik
$raw_images = explode(",", $data['gambar'] ?? '');
$images = array_unique(array_filter(array_map('trim', $raw_images))); 
$images = array_values($images); 

// Ambil dan proses data warna
$warna_data = [];
$color_image_map = []; // Map warna ke index gambar

if (!empty($data['warna_kain'])) {
    $warna_array = explode(",", $data['warna_kain']);
    foreach ($warna_array as $index => $w) {
        $parts = explode(":", $w);
        if (count($parts) >= 2) {
            $nama = trim($parts[0]);
            $hex = trim($parts[1]);
            $gambar_index = isset($parts[2]) ? (int)trim($parts[2]) : 0;
            
            // Validasi index gambar
            if ($gambar_index < 0 || $gambar_index >= count($images)) {
                $gambar_index = 0;
            }
            
            $warna_data[] = [
                'nama' => htmlspecialchars($nama),
                'hex' => htmlspecialchars($hex),
                'gambar_index' => $gambar_index
            ];
            
            // Buat mapping untuk JavaScript
            $color_image_map[] = $gambar_index;
        }
    }
}

// Filter warna unik (tidak ada duplikasi)
$unique_warna_data = [];
$seen_colors = [];
foreach ($warna_data as $warna) {
    $color_key = strtolower($warna['hex'] . '_' . $warna['nama']);
    if (!in_array($color_key, $seen_colors)) {
        $unique_warna_data[] = $warna;
        $seen_colors[] = $color_key;
    }
}

// Fungsi untuk mendapatkan path gambar lokal dengan debugging
function getLocalImage($imageName, $default = 'logo.png') {
    // Debug: Tampilkan nama gambar yang dicari
    // echo "Mencari gambar: " . $imageName . "<br>";
    
    // Jika kosong, return default
    if (empty($imageName) || $imageName === '') {
        return $default;
    }
    
    // Daftar ekstensi yang dicoba
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP'];
    
    // Jika sudah full path, langsung return
    if (file_exists($imageName)) {
        // echo "Found: " . $imageName . "<br>";
        return $imageName;
    }
    
    // Coba berbagai lokasi
    $possibleLocations = [
        '../uploads/' . $imageName,
        '../assets/images/' . $imageName,
        '../images/' . $imageName,
        'img/' . $imageName,
        'uploads/' . $imageName,
        'assets/images/' . $imageName,
        'images/' . $imageName,
        $imageName
    ];
    
    foreach ($possibleLocations as $location) {
        // Cek file dengan ekstensi asli
        if (file_exists($location)) {
            // echo "Found at: " . $location . "<br>";
            return $location;
        }
        
        // Coba dengan berbagai ekstensi
        foreach ($extensions as $ext) {
            $testPath = $location . '.' . $ext;
            if (file_exists($testPath)) {
                // echo "Found with extension: " . $testPath . "<br>";
                return $testPath;
            }
            
            // Coba tanpa perubahan ekstensi (jika sudah ada ekstensi)
            $testPath2 = $location;
            if (file_exists($testPath2)) {
                // echo "Found original: " . $testPath2 . "<br>";
                return $testPath2;
            }
        }
    }
    
    // Return default dengan path yang benar
    // echo "Using default: " . $default . "<br>";
    return $default;
}

// Proses semua gambar untuk menggunakan lokal
$local_images = [];
foreach ($images as $img) {
    $clean_img = trim($img);
    if (!empty($clean_img)) {
        $local_images[] = getLocalImage($clean_img, 'logo.png');
    }
}

// Jika tidak ada gambar, gunakan default
if (empty($local_images)) {
    $local_images[] = getLocalImage('logo.png', 'logo.png');
}

$first_img = !empty($local_images) ? $local_images[0] : getLocalImage('logo.png');

// Ambil data user untuk navbar
$username = $_SESSION['username'];
$userQuery = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
$namaLengkap = $userData['username'] ?? $username;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - <?= htmlspecialchars($data['kode_inna']); ?> | PT INTIM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
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

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            line-height: 1.6;
            overflow-x: hidden;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            min-height: 100vh;
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

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 120px auto 60px;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .breadcrumb a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb a:hover {
            color: var(--accent);
        }

        .breadcrumb-separator {
            color: var(--gray-300);
            font-size: 14px;
        }

        /* Product Detail Container */
        .product-detail-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            background: var(--white);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--gray-100);
            animation: fadeInUp 0.6s ease-out;
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

        /* Image Gallery Section */
        .image-gallery {
            padding: 40px;
            background: var(--gray-50);
            position: relative;
        }

        .main-image-container {
            position: relative;
            width: 100%;
            height: 500px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            background: var(--white);
            cursor: zoom-in;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .main-image-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: var(--transition-slow);
        }

        .main-image-container:hover .main-image {
            transform: scale(1.02);
        }

        .image-zoom-hint {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: var(--radius);
            font-size: 12px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transition: var(--transition);
        }

        .main-image-container:hover .image-zoom-hint {
            opacity: 1;
        }

        /* Thumbnails */
        .thumbnails-container {
            display: flex;
            gap: 16px;
            margin-top: 30px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--accent) var(--gray-100);
        }

        .thumbnails-container::-webkit-scrollbar {
            height: 6px;
        }

        .thumbnails-container::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 3px;
        }

        .thumbnails-container::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 3px;
        }

        .thumbnail {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            opacity: 0.7;
            transition: var(--transition);
            flex-shrink: 0;
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .thumbnail:hover {
            opacity: 1;
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .thumbnail.active {
            border-color: var(--accent);
            opacity: 1;
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            padding: 16px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray-100);
            font-size: 14px;
            color: var(--text-light);
        }

        /* Product Info Section */
        .product-info {
            padding: 50px 40px 40px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .product-header {
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 24px;
        }

        .product-category {
            font-size: 13px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-bottom: 12px;
            display: block;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
            line-height: 1.2;
        }

        .product-code {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--gray-100);
            border-radius: var(--radius);
            font-family: 'Courier New', monospace;
            font-size: 15px;
            color: var(--text-light);
            font-weight: 600;
        }

        .product-details {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .detail-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .detail-item:hover {
            background: var(--white);
            border-color: var(--gray-200);
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .detail-icon {
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

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .detail-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Color Selector */
        .color-selector {
            margin-top: 10px;
        }

        .color-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .color-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--white);
            padding: 12px 20px;
            border-radius: var(--radius-xl);
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .color-chip::before {
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

        .color-chip:hover::before,
        .color-chip.active::before {
            width: 300px;
            height: 300px;
        }

        .color-chip span {
            position: relative;
            z-index: 1;
            transition: color 0.3s;
            font-weight: 500;
            font-size: 14px;
        }

        .color-chip:hover span,
        .color-chip.active span {
            color: var(--white);
        }

        .color-chip:hover,
        .color-chip.active {
            border-color: var(--accent);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid var(--white);
            box-shadow: var(--shadow-sm);
            position: relative;
            z-index: 1;
            transition: transform 0.3s;
        }

        .color-chip:hover .color-dot,
        .color-chip.active .color-dot {
            transform: scale(1.2);
            box-shadow: var(--shadow);
        }

        .color-hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--gray-200);
        }

        .action-btn {
            flex: 1;
            padding: 18px 32px;
            border-radius: var(--radius-xl);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            border: none;
            font-family: 'Inter', sans-serif;
        }

        .action-btn.primary {
            background: var(--accent);
            color: var(--white);
            border: 2px solid var(--accent);
        }

        .action-btn.primary:hover {
            background: var(--accent-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .action-btn.secondary {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .action-btn.secondary:hover {
            background: var(--accent);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.2);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(44, 62, 80, 0.98);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            backdrop-filter: blur(20px);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-overlay.active {
            display: flex;
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

        .modal-image {
            width: 100%;
            height: 70vh;
            object-fit: contain;
            background: var(--gray-50);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--white);
            border: none;
            border-radius: 50%;
            font-size: 24px;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            z-index: 1;
        }

        .modal-close:hover {
            background: var(--accent);
            color: var(--white);
            transform: rotate(90deg);
        }

        .modal-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: var(--shadow);
        }

        .nav-btn {
            width: 44px;
            height: 44px;
            background: var(--accent);
            border: none;
            border-radius: 50%;
            color: var(--white);
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-btn:hover {
            background: var(--accent-light);
            transform: scale(1.1);
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
            z-index: 99999;
            transition: opacity 0.3s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid var(--gray-200);
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-container,
            .nav-container {
                padding: 0 32px;
            }
            
            .product-detail-container {
                gap: 40px;
            }
        }

        @media (max-width: 992px) {
            /* NAVBAR MOBILE */
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

            .product-detail-container {
                grid-template-columns: 1fr;
            }
            
            .image-gallery,
            .product-info {
                padding: 30px;
            }
            
            .main-image-container {
                height: 400px;
            }
            
            .product-title {
                font-size: 30px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 24px;
                margin: 100px auto 40px;
            }
            
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .image-gallery,
            .product-info {
                padding: 24px;
            }
            
            .main-image-container {
                height: 350px;
            }
            
            .thumbnail {
                width: 80px;
                height: 80px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .main-container,
            .nav-container {
                padding: 0 20px;
            }
            
            .product-title {
                font-size: 26px;
            }
            
            .main-image-container {
                height: 300px;
            }
            
            .thumbnail {
                width: 70px;
                height: 70px;
            }
            
            .detail-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .color-chip {
                padding: 10px 16px;
            }
            
            .logo img {
                height: 35px;
            }
        }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div class="loading" id="loading">
    <div class="spinner"></div>
</div>

<!-- Modal untuk zoom gambar -->
<div class="modal-overlay" id="zoomModal">
    <div class="modal-content">
        <button class="modal-close" id="modalClose">
            <i class="fas fa-times"></i>
        </button>
        <img src="" alt="Preview" class="modal-image" id="modalImage">
        <div class="modal-nav">
            <button class="nav-btn" id="prevBtn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span id="modalCounter">1 / <?= count($local_images) ?></span>
            <button class="nav-btn" id="nextBtn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay">
    <button class="mobile-menu-close" id="mobileMenuClose">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Search Box Mobile -->
    <div class="mobile-search-box">
        <form action="koleksi.php" method="GET">
            <div style="position: relative;">
                <input type="text" name="cari" placeholder="Cari kain..." class="mobile-search-input">
                <button type="submit" class="mobile-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
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
            <a href="#" class="mobile-nav-link mobile-dropdown-toggle">
                <span>
                    <i class="fas fa-calendar-alt"></i>
                    Acara
                </span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown-content" id="mobileAcaraDropdown">
                <a href="berita_karir.php" class="mobile-dropdown-link">
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
                    <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
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

<!-- Header -->
<header class="header" id="header">
    <div class="nav-container">
        <!-- Logo -->
        <a href="user_dashboard.php" class="logo">
            <img src="<?= getLocalImage('logo small black.png', 'logo.png') ?>" alt="PT INTIM">
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
            
            <!-- Acara Dropdown (Karir + Kegiatan) -->
            <div class="nav-dropdown" id="acaraDropdown">
                <div class="dropdown-toggle nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Acara</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    <div class="dropdown-links">
                        <a href="berita_karir.php" class="dropdown-item">
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

            <!-- Tentang Kami Dropdown (Profil Perusahaan + Visi & Misi) -->
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
            <!-- Search Box -->
            <div class="search-box">
                <form action="koleksi.php" method="GET">
                    <input type="text" name="cari" placeholder="Cari kain..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <!-- User Menu -->
            <div class="user-menu" id="userMenu">
                <div class="user-avatar" id="userAvatar">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-dropdown">
                    <div class="user-info">
                        <div class="user-info-avatar">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <h4><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h4>
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
<div class="main-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="user_dashboard.php">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        <span class="breadcrumb-separator">/</span>
        <a href="koleksi.php">
            <i class="fas fa-th-large"></i>
            <span>Koleksi</span>
        </a>
        <span class="breadcrumb-separator">/</span>
        <span style="color: var(--accent); font-weight: 600;">
            <?= htmlspecialchars($data['motif_kain']); ?>
        </span>
    </div>

    <!-- Product Detail -->
    <div class="product-detail-container">
        <!-- Image Gallery -->
        <div class="image-gallery">
            <div class="main-image-container" onclick="openModal()">
                <img src="<?= $first_img ?>" id="mainView" class="main-image" alt="<?= htmlspecialchars($data['motif_kain']) ?>">
                <div class="image-zoom-hint">
                    <i class="fas fa-search-plus"></i>
                    <span>Klik untuk memperbesar</span>
                </div>
            </div>

            <!-- Thumbnails -->
            <?php if(count($local_images) > 0): ?>
                <div class="thumbnails-container">
                    <?php foreach($local_images as $index => $img): ?>
                        <div class="thumbnail <?= ($index === 0) ? 'active' : '' ?>" 
                             onclick="changeImage(<?= $index ?>)"
                             data-index="<?= $index ?>">
                            <img src="<?= $img ?>" alt="Thumbnail <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="image-info">
                    <i class="fas fa-info-circle" style="color: var(--accent);"></i>
                    <span id="imageInfo"><?= count($local_images) ?> gambar tersedia</span>
                </div>
            <?php else: ?>
                <div class="image-info">
                    <i class="fas fa-exclamation-circle" style="color: var(--accent);"></i>
                    <span>Tidak ada gambar tersedia</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <div class="product-header">
                <span class="product-category"><?= htmlspecialchars($data['tipe_kain']); ?></span>
                <h1 class="product-title"><?= htmlspecialchars($data['motif_kain']); ?></h1>
                <div class="product-code">
                    <i class="fas fa-barcode"></i>
                    <span><?= htmlspecialchars($data['kode_inna']); ?></span>
                </div>
            </div>

            <div class="product-details">
                <!-- Motif Detail -->
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Desain Motif</span>
                        <span class="detail-value"><?= htmlspecialchars($data['motif_kain']); ?></span>
                    </div>
                </div>

                <!-- Tipe Kain -->
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Jenis Material</span>
                        <span class="detail-value"><?= htmlspecialchars($data['tipe_kain']); ?></span>
                    </div>
                </div>

                <!-- Screen -->
                <?php if(!empty($data['screen'])): ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Screen</span>
                        <span class="detail-value"><?= htmlspecialchars($data['screen']); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Varian Warna -->
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-fill-drip"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Varian Warna</span>
                        <div class="color-selector">
                            <div class="color-grid" id="colorSelector">
                                <?php if(!empty($unique_warna_data)): ?>
                                    <?php foreach($unique_warna_data as $index => $warna): ?>
                                        <div class="color-chip <?= ($index === 0) ? 'active' : '' ?>" 
                                             onclick="selectColor(<?= $index ?>, <?= $warna['gambar_index'] ?>)"
                                             data-index="<?= $index ?>"
                                             data-image-index="<?= $warna['gambar_index'] ?>"
                                             title="Klik untuk lihat gambar <?= $warna['nama'] ?>">
                                            <div class="color-dot" style="background-color: <?= $warna['hex'] ?>;"></div>
                                            <span><?= $warna['nama'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-size: 14px;">
                                        <i class="fas fa-info-circle"></i>
                                        Tidak ada varian warna.
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="color-hint">
                                <i class="fas fa-mouse-pointer"></i>
                                <span>Klik warna untuk melihat gambar terkait</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Deskripsi -->
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">Deskripsi</span>
                        <span class="detail-value"><?= htmlspecialchars($data['deskripsi']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="koleksi.php" class="action-btn secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Koleksi</span>
                </a>
                <button class="action-btn primary" onclick="openModal()">
                    <i class="fas fa-expand"></i>
                    <span>Lihat Detail Gambar</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* =========================================================
   DATA GLOBAL DARI SERVER (PHP → JAVASCRIPT)
   ========================================================= */

// Array berisi daftar path gambar (hasil dari PHP json_encode)
const images = <?= json_encode($local_images) ?>;

// Mapping warna ke gambar (misalnya warna merah → gambar tertentu)
const colorImageMap = <?= json_encode($color_image_map) ?>;

// Index gambar yang sedang aktif di tampilan utama
let currentImageIndex = 0;

// Index warna yang sedang dipilih
let activeColorIndex = 0;

// Index gambar yang sedang ditampilkan di modal (zoom)
let modalImageIndex = 0;


/* =========================================================
   LOADING SCREEN HANDLER
   ========================================================= */

// Menjalankan kode setelah seluruh DOM selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    // Delay 500ms untuk memastikan semua konten benar-benar siap
    setTimeout(function() {
        const loading = document.getElementById('loading');
        if (loading) {
            // Menambahkan class "hidden" untuk animasi fade out
            loading.classList.add('hidden');
            console.log('Loading screen hidden');
            
            // Menghapus elemen loading setelah animasi selesai
            setTimeout(function() {
                loading.style.display = 'none';
                console.log('Loading screen removed');
            }, 500);
        }
    }, 500);
});


/* =========================================================
   HEADER SCROLL EFFECT
   ========================================================= */

// Mengambil elemen header
const header = document.getElementById('header');

if (header) {
    // Event ketika user melakukan scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            // Tambahkan class jika scroll lebih dari 50px
            header.classList.add('scrolled');
        } else {
            // Hapus class jika kembali ke atas
            header.classList.remove('scrolled');
        }
    });
}


/* =========================================================
   DROPDOWN MENU (DESKTOP)
   ========================================================= */

document.addEventListener('DOMContentLoaded', function() {

    // Ambil semua tombol dropdown
    const dropdownToggles = document.querySelectorAll('.nav-dropdown .dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();     // Mencegah link default
            e.stopPropagation();    // Mencegah bubbling
            
            const dropdown = this.closest('.nav-dropdown');
            const isActive = dropdown.classList.contains('active');
            
            // Tutup semua dropdown lain
            document.querySelectorAll('.nav-dropdown').forEach(d => {
                d.classList.remove('active');
            });
            
            // Buka dropdown yang diklik
            if (!isActive) {
                dropdown.classList.add('active');
            }
        });
    });
    
    // Menutup dropdown jika klik di luar area dropdown
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Dropdown khusus menu user/avatar
    const userAvatar = document.getElementById('userAvatar');
    const userMenu = document.getElementById('userMenu');
    
    if (userAvatar && userMenu) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
            
            // Tutup dropdown lain
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
    }
});


/* =========================================================
   MOBILE MENU (HAMBURGER)
   ========================================================= */

const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
const mobileMenuClose = document.getElementById('mobileMenuClose');

// Buka mobile menu
if (mobileMenuBtn && mobileMenuOverlay) {
    mobileMenuBtn.addEventListener('click', function() {
        this.classList.toggle('active');
        mobileMenuOverlay.classList.toggle('active');
        document.body.style.overflow = 'hidden'; // Disable scroll
    });
}

// Tutup mobile menu
if (mobileMenuClose && mobileMenuOverlay) {
    mobileMenuClose.addEventListener('click', function() {
        mobileMenuBtn.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    });
}


/* =========================================================
   MOBILE DROPDOWN MENU
   ========================================================= */

document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const dropdown = this.closest('.mobile-dropdown');
        const content = dropdown.querySelector('.mobile-dropdown-content');
        const isActive = content.classList.contains('active');
        
        // Tutup semua dropdown lain
        document.querySelectorAll('.mobile-dropdown-content').forEach(dc => {
            dc.classList.remove('active');
        });
        
        // Reset semua icon panah
        document.querySelectorAll('.mobile-dropdown-toggle .fa-chevron-down').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
        
        // Toggle dropdown yang dipilih
        if (!isActive) {
            content.classList.add('active');
            this.querySelector('.fa-chevron-down').style.transform = 'rotate(180deg)';
        } else {
            content.classList.remove('active');
        }
    });
});


/* =========================================================
   AUTO CLOSE MOBILE MENU
   ========================================================= */

// Tutup menu setelah klik link
document.querySelectorAll('.mobile-nav-link, .mobile-dropdown-link').forEach(link => {
    link.addEventListener('click', function() {
        setTimeout(() => {
            if (mobileMenuBtn) mobileMenuBtn.classList.remove('active');
            if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }, 300);
    });
});

// Tutup menu jika klik di luar area
if (mobileMenuOverlay) {
    mobileMenuOverlay.addEventListener('click', function(e) {
        if (e.target === mobileMenuOverlay) {
            mobileMenuBtn.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// Tutup menu otomatis jika layar diperbesar
window.addEventListener('resize', function() {
    if (window.innerWidth > 992) {
        mobileMenuBtn.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});


/* =========================================================
   GALLERY IMAGE HANDLER
   ========================================================= */

// Mengganti gambar utama
function changeImage(index) {
    if (index < 0 || index >= images.length) return;
    
    currentImageIndex = index;
    
    const mainImg = document.getElementById('mainView');
    if (mainImg && images[index]) {
        mainImg.src = images[index];
    }
    
    // Update thumbnail aktif
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    const activeThumb = document.querySelector(`.thumbnail[data-index="${index}"]`);
    if (activeThumb) activeThumb.classList.add('active');
    
    updateImageInfo(index);
}

// Memilih warna
function selectColor(colorIndex, imageIndex) {
    activeColorIndex = colorIndex;
    
    document.querySelectorAll('.color-chip').forEach(c => c.classList.remove('active'));
    const activeChip = document.querySelector(`.color-chip[data-index="${colorIndex}"]`);
    if (activeChip) activeChip.classList.add('active');
    
    if (images[imageIndex]) {
        changeImage(imageIndex);
    } else {
        changeImage(0);
    }
}

// Update teks info gambar
function updateImageInfo(imageIndex) {
    const imageInfo = document.getElementById('imageInfo');
    if (imageInfo) {
        imageInfo.textContent = imageIndex === 0
            ? "Gambar utama (cover)"
            : `Gambar detail #${imageIndex + 1}`;
    }
}


/* =========================================================
   MODAL ZOOM IMAGE
   ========================================================= */

function openModal(imageIndex = currentImageIndex) {
    const modal = document.getElementById("zoomModal");
    const modalImg = document.getElementById("modalImage");
    const modalCounter = document.getElementById("modalCounter");
    
    if (!modal || !modalImg || !images[imageIndex]) return;
    
    modalImageIndex = imageIndex;
    modalImg.src = images[modalImageIndex];
    modalCounter.textContent = `${modalImageIndex + 1} / ${images.length}`;
    
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
}

function closeModal() {
    document.getElementById("zoomModal").classList.remove("active");
    document.body.style.overflow = "";
}

function nextImage() {
    modalImageIndex = (modalImageIndex + 1) % images.length;
    updateModal();
}

function prevImage() {
    modalImageIndex = (modalImageIndex - 1 + images.length) % images.length;
    updateModal();
}

function updateModal() {
    document.getElementById("modalImage").src = images[modalImageIndex];
    document.getElementById("modalCounter").textContent =
        `${modalImageIndex + 1} / ${images.length}`;
    
    changeImage(modalImageIndex);
}


/* =========================================================
   EVENT LISTENER MODAL & KEYBOARD
   ========================================================= */

document.getElementById('modalClose')?.addEventListener('click', closeModal);
document.getElementById('prevBtn')?.addEventListener('click', prevImage);
document.getElementById('nextBtn')?.addEventListener('click', nextImage);

// Navigasi keyboard
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById("zoomModal");
    if (modal.classList.contains("active")) {
        if (e.key === "Escape") closeModal();
        if (e.key === "ArrowLeft") prevImage();
        if (e.key === "ArrowRight") nextImage();
    }
});

// Tutup modal jika klik background
document.getElementById('zoomModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});


/* =========================================================
   INIT & DEBUG
   ========================================================= */

if (images.length > 0) updateImageInfo(0);

// Scroll ke atas saat halaman dimuat
window.scrollTo({ top: 0, behavior: 'smooth' });

// Debug log
console.log('JavaScript loaded successfully');
console.log('Images array:', images);
console.log('Local images count:', <?= count($local_images) ?>);
</script>


</body>
</html>