<?php
session_start();
include '../includes/config.php';

// 1. Proteksi Halaman - gunakan session tanpa query database
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user dari session langsung (tanpa query ke tabel users)
$namaLengkap = $_SESSION['username'] ?? '';
$emailUser = $_SESSION['email'] ?? '';

// 2. Logika Simpan Pesan
$message_status = "";
$status_type = "success";
$show_notification = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari input form secara manual
    $input_name    = mysqli_real_escape_string($conn, $_POST['name']);
    $input_email   = mysqli_real_escape_string($conn, $_POST['email']);
    $input_phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $input_subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $input_message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Validasi input
    if (empty($input_name) || empty($input_email) || empty($input_subject) || empty($input_message)) {
        $message_status = "Harap isi semua field yang wajib diisi!";
        $status_type = "danger";
        $show_notification = true;
    } else {
        // Simpan ke database menggunakan NOW() agar created_at terisi otomatis
        $sql = "INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                VALUES ('$input_name', '$input_email', '$input_phone', '$input_subject', '$input_message',  NOW())";

        if (mysqli_query($conn, $sql)) {
            $message_status = "Pesan Anda berhasil dikirim! Tim kami akan segera menghubungi Anda.";
            $show_notification = true;
            
            // Reset form fields
            $_POST = array();
        } else {
            $message_status = "Gagal mengirim data: " . mysqli_error($conn);
            $status_type = "danger";
            $show_notification = true;
        }
    }
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
    <title>Kontak Kami - PT INTIM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES CSS ===== */
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

        /* ===== PREMIUM NAVIGATION (SAMA DENGAN user_dashboard.php) ===== */
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

        /* ===== CONTENT STYLES (Khusus Kontak Page) ===== */
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        }
        
        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification i {
            font-size: 18px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
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

        /* Contact Hero Section */
        .contact-hero {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            color: var(--white);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .contact-hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }

        .contact-hero-content {
            max-width: 600px;
        }

        .contact-hero-badge {
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

        .contact-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--white);
        }

        .contact-hero-description {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
            margin-bottom: 32px;
        }

        /* Contact Content */
        .contact-content {
            padding: 80px 0;
            background: var(--white);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        /* Contact Info Sidebar */
        .contact-info-sidebar {
            background: var(--gray-50);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .contact-info-sidebar h3 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .contact-info-sidebar h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 40px;
        }

        .contact-detail-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .contact-detail-icon {
            width: 44px;
            height: 44px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 18px;
            flex-shrink: 0;
        }

        .contact-detail-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--primary);
        }

        .contact-detail-content p,
        .contact-detail-content a {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
            text-decoration: none;
        }

        .contact-detail-content a:hover {
            color: var(--accent);
        }

        /* Social Media */
        .social-section {
            margin-top: 40px;
        }

        .social-section h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .social-links-contact {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .social-link-contact {
            width: 40px;
            height: 40px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }

        .social-link-contact:hover {
            background: var(--accent);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--accent);
        }

        /* Contact Form */
        .contact-form-container {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .contact-form-container h3 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }

        .contact-form-container h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-label span {
            color: #e74c3c;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            transition: var(--transition);
            background: var(--white);
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237f8c8d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 14px;
            cursor: pointer;
        }

        .form-submit {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--white);
            border: none;
            padding: 16px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .form-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.3);
        }

        /* Map Section */
        .map-section {
            padding: 80px 0;
            background: var(--gray-50);
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .map-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
        }

        .map-section p {
            font-size: 16px;
            color: var(--text-light);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 50px;
            line-height: 1.8;
        }

        .map-wrapper {
            position: relative;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            height: 400px;
        }

        .map-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ===== FOOTER ===== */
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

        /* ===== MOBILE MENU OVERLAY ===== */
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

        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .nav-container,
            .contact-hero-container,
            .contact-container,
            .map-container,
            .footer-container {
                padding: 0 32px;
            }
            
            .contact-hero-title {
                font-size: 42px;
            }
        }

        @media (max-width: 992px) {
            /* Navbar Mobile */
            .nav-main {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
                order: 3;
            }
            
            .nav-actions {
                order: 2;
                gap: 15px;
            }
            
           
            
            .user-menu {
                order: 1;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 50px;
            }
            
            .contact-hero {
                padding: 60px 0;
            }
            
            .contact-hero-title {
                font-size: 38px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 32px;
            }
        }

        @media (max-width: 768px) {
            .nav-container,
            .contact-hero-container,
            .contact-container,
            .map-container,
            .footer-container {
                padding: 0 24px;
            }
            
            .nav-container {
                height: 70px;
            }
            
            .contact-hero-title {
                font-size: 32px;
            }
            
            .contact-hero-description {
                font-size: 16px;
            }
            
            .contact-info-sidebar,
            .contact-form-container {
                padding: 30px;
            }
            
            .contact-info-sidebar h3,
            .contact-form-container h3 {
                font-size: 24px;
            }
            
            .map-section h2 {
                font-size: 32px;
            }
            
            .map-wrapper {
                height: 300px;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        @media (max-width: 576px) {
            .contact-hero-title {
                font-size: 28px;
            }
            
            .contact-hero-badge {
                font-size: 12px;
                padding: 6px 16px;
            }
            
            .contact-info-sidebar h3,
            .contact-form-container h3 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

    <!-- Notification -->
    <?php if ($show_notification): ?>
    <div class="notification <?php echo $status_type === 'success' ? 'success' : 'error'; ?>" id="contactNotification">
        <i class="fas <?php echo $status_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message_status); ?></span>
    </div>
    <?php endif; ?>

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
            
            <a href="kontak.php" class="mobile-nav-link active">
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
                <a href="kontak.php" class="nav-link active">
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
                        <?= !empty($namaLengkap) ? strtoupper(substr($namaLengkap, 0, 1)) : 'U' ?>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-info-avatar">
                                <?= !empty($namaLengkap) ? strtoupper(substr($namaLengkap, 0, 1)) : 'U' ?>
                            </div>
                            <div class="user-details">
                                <h4><?= !empty($namaLengkap) ? htmlspecialchars($namaLengkap) : 'User' ?></h4>
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
                    <li class="breadcrumb-item active">
                        Kontak Kami
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contact Hero Section -->
        <section class="contact-hero">
            <div class="contact-hero-container">
                <div class="contact-hero-content">
                    <div class="contact-hero-badge">
                        <i class="fas fa-headset"></i>
                        <span>Hubungi Kami</span>
                    </div>
                    <h1 class="contact-hero-title">Kami Siap Membantu Anda</h1>
                    <p class="contact-hero-description">
                        Hubungi tim kami untuk informasi lebih lanjut mengenai produk, penawaran khusus, atau bantuan lainnya.
                        Kami berkomitmen untuk memberikan pelayanan terbaik kepada pelanggan.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Content Section -->
        <section class="contact-content">
            <div class="contact-container">
                <div class="contact-grid">
                    <!-- Contact Information Sidebar -->
                    <div class="contact-info-sidebar">
                        <h3>Informasi Kontak</h3>
                        
                        <div class="contact-details">
                            <div class="contact-detail-item">
                                <div class="contact-detail-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-detail-content">
                                    <h4>Alamat Kantor</h4>
                                    <p>
                                        Jl. Karangwuni-Dlimas, Dlimas, Ceper,<br>
                                        Klaten, Jawa Tengah 57465
                                    </p>
                                </div>
                            </div>
                            
                            <div class="contact-detail-item">
                                <div class="contact-detail-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-detail-content">
                                    <h4>Telepon & Fax</h4>
                                    <p>
                                        <a href="tel:+2725533488">(0272) 5533 488</a><br>
                                        <a href="tel:+6281226122611">0812 2612 2611</a>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="contact-detail-item">
                                <div class="contact-detail-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-detail-content">
                                    <h4>Email</h4>
                                    <p>
                                        <a href="mailto:headoffice@innagrouptm.com">headoffice@innagrouptm.com</a><br>
                                        <a href="mailto:sales@innagrouptm.com">sales@innagrouptm.com</a>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="contact-detail-item">
                                <div class="contact-detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-detail-content">
                                    <h4>Jam Operasional</h4>
                                    <p>
                                        Senin - Jumat: 08:00 - 17:00<br>
                                        Sabtu: 08:00 - 12:00<br>
                                        Minggu: Libur
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="social-section">
                            <h4>Ikuti Kami</h4>
                            <div class="social-links-contact">
                                <a href="#" class="social-link-contact">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-link-contact">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link-contact">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link-contact">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link-contact">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Form -->
                    <div class="contact-form-container">
                        <h3>Kirim Pesan</h3>
                        <p style="color: var(--text-light); margin-bottom: 25px; font-size: 14px;">
                            Silakan isi formulir di bawah ini dan tim kami akan segera menghubungi Anda.
                        </p>
                        
                        <form class="contact-form" method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="name">Nama Lengkap <span>*</span></label>
                                <input type="text" id="name" name="name" class="form-input" required 
                                       placeholder="Masukkan nama lengkap Anda" 
                                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($namaLengkap) ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="email">Email <span>*</span></label>
                                    <input type="email" id="email" name="email" class="form-input" required 
                                           placeholder="nama@email.com" 
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($emailUser) ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="phone">Telepon <span>*</span></label>
                                    <input type="tel" id="phone" name="phone" class="form-input" required 
                                           placeholder="08xxxxxxxxxx" 
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="subject">Subjek <span>*</span></label>
                                <input type="text" id="subject" name="subject" class="form-input" required 
                                       placeholder="Topik pesan" 
                                       value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="message">Pesan <span>*</span></label>
                                <textarea id="message" name="message" class="form-textarea" required 
                                          placeholder="Tuliskan pesan Anda..."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                            </div>
                            
                            <button type="submit" name="submit_contact" class="form-submit">
                                <i class="fas fa-paper-plane"></i>
                                Kirim Pesan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="map-section">
            <div class="map-container">
                <h2>Lokasi Kantor Kami</h2>
                <p>
                    Kunjungi kantor pusat PT Innagroup Textile Manufacture untuk konsultasi langsung dengan tim kami.
                </p>
                
                <div class="map-wrapper">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3954.123456789012!2d110.63000000000001!3d-7.789012345678901!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwNDclMjAuNCJTIDExMMKwMzclNDguMCJF!5e0!3m2!1sen!2sid!4v1234567890123!5m2!1sen!2sid" 
                        class="map-iframe" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
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

        // Notification auto hide
        setTimeout(function() {
            const notification = document.getElementById('contactNotification');
            if (notification) {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);

        // Form validation
        const contactForm = document.querySelector('.contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#e74c3c';
                        isValid = false;
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Harap isi semua field yang wajib diisi!');
                }
            });
        }

        // Clear field border on input
        document.querySelectorAll('.form-input, .form-textarea').forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html>