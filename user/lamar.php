<?php
session_start();
include '../includes/config.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Ambil data dari SESSION (jika ada) sebagai nilai default form
$namaLengkap = $_SESSION['nama'] ?? $_SESSION['username'] ?? ''; 
$userEmail = $_SESSION['email'] ?? '';

// Ambil ID lowongan dari parameter
$id_lowongan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Cek apakah lowongan valid
if ($id_lowongan > 0) {
    $lowonganQuery = mysqli_query($conn, "SELECT * FROM karir WHERE id = $id_lowongan AND is_active = 1 AND deadline >= CURDATE()");
    $lowongan = mysqli_fetch_assoc($lowonganQuery);
    
    if (!$lowongan) {
        header("Location: berita_karir.php?error=lowongan_tidak_ditemukan");
        exit;
    }
} else {
    header("Location: berita_karir.php?error=lowongan_tidak_valid");
    exit;
}

// Inisialisasi variabel
$errors = [];
$success = false;

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
    $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan'] ?? '');
    $pengalaman = mysqli_real_escape_string($conn, $_POST['pengalaman'] ?? '');
    $motivasi = mysqli_real_escape_string($conn, $_POST['motivasi'] ?? '');
    
    // Validasi
    if (empty($nama)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email harus valid";
    }
    
    if (empty($telepon)) {
        $errors[] = "Nomor telepon harus diisi";
    }
    
    if (empty($pendidikan)) {
        $errors[] = "Pendidikan terakhir harus diisi";
    }
    
    if (empty($pengalaman)) {
        $errors[] = "Pengalaman kerja harus diisi";
    }
    
    if (empty($motivasi)) {
        $errors[] = "Motivasi bergabung harus diisi";
    }
    
    // Validasi panjang teks
    if (strlen($pengalaman) < 50) {
        $errors[] = "Pengalaman kerja minimal 50 karakter";
    }
    
    if (strlen($motivasi) < 100) {
        $errors[] = "Motivasi bergabung minimal 100 karakter";
    }
    
    // Handle file upload
    $cv_filename = '';
    $foto_filename = '';
    
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
        $cv_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        $allowed_cv = ['pdf', 'doc', 'docx'];
        
        if (in_array(strtolower($cv_ext), $allowed_cv)) {
            if ($_FILES['cv']['size'] > 2 * 1024 * 1024) {
                $errors[] = "Ukuran CV maksimal 2MB";
            } else {
                $cv_filename = 'cv_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $_FILES['cv']['name']);
                $cv_path = '../uploads/lamaran/' . $cv_filename;
                
                if (!is_dir('../uploads/lamaran')) {
                    mkdir('../uploads/lamaran', 0777, true);
                }
                
                if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
                    $errors[] = "Gagal mengupload CV";
                }
            }
        } else {
            $errors[] = "Format CV harus PDF, DOC, atau DOCX";
        }
    } else {
        $errors[] = "CV harus diupload";
    }
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $allowed_foto = ['jpg', 'jpeg', 'png'];
        
        if (in_array(strtolower($foto_ext), $allowed_foto)) {
            if ($_FILES['foto']['size'] > 1 * 1024 * 1024) {
                $errors[] = "Ukuran foto maksimal 1MB";
            } else {
                $foto_filename = 'foto_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $_FILES['foto']['name']);
                $foto_path = '../uploads/lamaran/' . $foto_filename;
                
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    $errors[] = "Gagal mengupload foto";
                }
            }
        } else {
            $errors[] = "Format foto harus JPG, JPEG, atau PNG";
        }
    } else {
        $errors[] = "Foto harus diupload";
    }
    
    // CEK APAKAH ID LOWONGAN ADA DI TABEL KARIR
    $checkKarir = mysqli_query($conn, "SELECT id FROM karir WHERE id = $id_lowongan");
    if (mysqli_num_rows($checkKarir) == 0) {
        $errors[] = "Lowongan pekerjaan tidak valid";
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $query = "INSERT INTO lamaran (
                    karir_id, 
                    nama_pelamar, 
                    email, 
                    telepon, 
                    pendidikan, 
                    pengalaman, 
                    motivasi, 
                    cv_file, 
                    foto_file, 
                    status, 
                    created_at
                  ) VALUES (
                    '$id_lowongan',
                    '$nama',
                    '$email',
                    '$telepon',
                    '$pendidikan',
                    '$pengalaman',
                    '$motivasi',
                    '$cv_filename',
                    '$foto_filename',
                    'pending',
                    NOW()
                  )";
        
        if (mysqli_query($conn, $query)) {
            $success = true;
        } else {
            $errors[] = "Gagal menyimpan data lamaran: " . mysqli_error($conn);
        }
    }
}

// Fungsi untuk mendapatkan path gambar lokal
function getLocalImage($imageName, $default = 'logo.png') {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    $possibleLocations = [
        '../uploads/' . $imageName,
        '../assets/images/' . $imageName,
        'images/' . $imageName,
        'img/' . $imageName,
        $imageName
    ];
    
    foreach ($possibleLocations as $location) {
        if (file_exists($location)) {
            return $location;
        }
        
        foreach ($extensions as $ext) {
            $testPath = $location . '.' . $ext;
            if (file_exists($testPath)) {
                return $testPath;
            }
        }
    }
    
    return $default;
}

// Ambil data untuk dropdown filter karir
$lokasiList = mysqli_query($conn, "SELECT DISTINCT location FROM karir WHERE location IS NOT NULL AND location != '' AND is_active = 1 ORDER BY location ASC");
$tipeList = mysqli_query($conn, "SELECT DISTINCT type FROM karir WHERE type IS NOT NULL AND type != '' AND is_active = 1 ORDER BY type ASC");

// Ambil data produk untuk dropdown motif
$listMotifForDropdown = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT INTIM - Lamar Pekerjaan</title>
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
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            min-height: 100vh;
        }

        /* Premium Navigation - SEPERTI user_dashboard.php */
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

        /* Enhanced Dropdown - SEPERTI user_dashboard.php */
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

        /* User Menu - SEPERTI user_dashboard.php */
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

        /* Search Box - SEPERTI user_dashboard.php */
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

        /* Mobile Menu Button - SEPERTI user_dashboard.php */
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

        /* Mobile Menu Overlay - SEPERTI user_dashboard.php */
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

        /* Responsive Design untuk Navbar - SEPERTI user_dashboard.php */
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
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 24px;
                height: 70px;
            }
            
            .logo img {
                height: 35px;
            }
        }

        @media (max-width: 576px) {
            .nav-container {
                padding: 0 20px;
            }
        }

        /* ==================== TIDAK MENGUBAH CODINGAN UTAMA DI BAWAH INI ==================== */
        /* Kode CSS untuk konten utama tetap sama seperti semula */
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 40px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-badge {
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
            margin-bottom: 20px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .page-subtitle {
            font-size: 18px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Job Info Card */
        .job-info-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .job-info-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-100);
        }

        .job-info-title h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }

        .job-info-department {
            font-size: 14px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
        }

        .job-info-meta {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-light);
        }

        .job-meta-item i {
            color: var(--accent);
            font-size: 16px;
        }

        .job-info-deadline {
            background: var(--accent);
            color: var(--white);
            padding: 8px 16px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 600;
        }

        .job-info-description {
            margin-bottom: 24px;
        }

        .job-info-description h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .job-info-description p {
            color: var(--text-light);
            line-height: 1.8;
        }

        /* Application Form */
        .application-form-wrapper {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }

        .form-subtitle {
            font-size: 16px;
            color: var(--text-light);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            color: #c0392b;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }

        .alert i {
            font-size: 18px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .form-label .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            transition: var(--transition);
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-control.textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-control.select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237f8c8d'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 48px;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
        }

        .file-upload-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            color: var(--text-light);
            background: var(--white);
            cursor: pointer;
        }

        .file-upload-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .file-upload-button {
            position: absolute;
            right: 4px;
            top: 4px;
            bottom: 4px;
            background: var(--gray-100);
            border: none;
            padding: 0 16px;
            border-radius: var(--radius-sm);
            color: var(--text-dark);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload-button:hover {
            background: var(--gray-200);
        }

        .file-info {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 6px;
        }

        .file-info i {
            color: var(--accent);
            margin-right: 6px;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-100);
        }

        .back-btn {
            padding: 14px 28px;
            background: transparent;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--gray-100);
            transform: translateX(-4px);
        }

        .submit-btn {
            padding: 14px 32px;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            color: var(--white);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }

        .submit-btn:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .submit-btn:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Success Message */
        .success-message {
            text-align: center;
            padding: 60px 40px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .success-icon {
            font-size: 64px;
            color: var(--success);
            margin-bottom: 24px;
            display: inline-block;
        }

        .success-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }

        .success-text {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
        }

        .success-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
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
            margin-top: 100px;
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design untuk konten utama */
        @media (max-width: 1200px) {
            .main-content,
            .footer-container {
                padding: 0 32px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 0 24px;
                margin: 100px auto 40px;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .application-form-wrapper {
                padding: 24px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 16px;
            }
            
            .back-btn,
            .submit-btn {
                width: 100%;
                justify-content: center;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .job-info-card {
                padding: 20px;
            }
            
            .success-message {
                padding: 40px 20px;
            }
            
            .success-title {
                font-size: 24px;
            }
            
            .success-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Character Counter */
        .char-counter {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Mobile Menu Overlay - SEPERTI user_dashboard.php -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Search Box Mobile -->
        <div class="mobile-search-box">
            <form action="berita_karir.php" method="GET">
                <div style="position: relative;">
                    <input type="text" name="cari" placeholder="Cari lowongan..." class="mobile-search-input">
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
                <!-- Search Box -->
                <div class="search-box">
                    <form action="berita_karir.php" method="GET">
                        <input type="text" name="cari" placeholder="Cari lowongan..." class="search-input">
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

    <!-- ==================== TIDAK MENGUBAH KONTEN UTAMA DI BAWAH INI ==================== -->
    <!-- Main Content (Kode utama tetap sama) -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-badge">
                <i class="fas fa-paper-plane"></i>
                <span>Lamar Pekerjaan</span>
            </div>
            <h1 class="page-title">Formulir Lamaran Kerja</h1>
            <p class="page-subtitle">
                Isi formulir di bawah ini dengan data yang benar dan lengkap untuk mengajukan lamaran kerja
            </p>
        </div>

        <!-- Job Info Card -->
        <div class="job-info-card">
            <div class="job-info-header">
                <div class="job-info-title">
                    <h2><?= htmlspecialchars($lowongan['position']) ?></h2>
                    <span class="job-info-department"><?= htmlspecialchars($lowongan['department']) ?></span>
                </div>
                <div class="job-info-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($lowongan['location'] ?? 'Tidak ditentukan') ?></span>
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?= htmlspecialchars($lowongan['type']) ?></span>
                    </div>
                    <div class="job-info-deadline">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Tutup: <?= date('d F Y', strtotime($lowongan['deadline'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="job-info-description">
                <h3>Deskripsi Pekerjaan</h3>
                <p><?= nl2br(htmlspecialchars($lowongan['description'])) ?></p>
            </div>
            
            <?php if (!empty($lowongan['requirements'])): ?>
            <div class="job-info-description">
                <h3>Persyaratan</h3>
                <p><?= nl2br(htmlspecialchars($lowongan['requirements'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <!-- Success Message -->
            <div class="success-message">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="success-title">Lamaran Berhasil Dikirim!</h2>
                <p class="success-text">
                    Terima kasih <strong><?= htmlspecialchars($_POST['nama'] ?? '') ?></strong> telah mengajukan lamaran untuk posisi 
                    <strong><?= htmlspecialchars($lowongan['position']) ?></strong>. Lamaran Anda telah kami terima dan sedang dalam proses review.
                    Kami akan menghubungi Anda melalui email atau telepon jika lamaran Anda lolos ke tahap selanjutnya.
                </p>
                <div class="success-actions">
                    <a href="berita_karir.php" class="btn btn-primary">
                        <i class="fas fa-briefcase"></i>
                        Lihat Lowongan Lain
                    </a>
                    <a href="user_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i>
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Application Form -->
            <div class="application-form-wrapper">
                <div class="form-header">
                    <h2 class="form-title">Data Pribadi</h2>
                    <p class="form-subtitle">Harap isi semua field dengan data yang valid</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Terjadi kesalahan:</strong>
                            <ul style="margin: 8px 0 0 20px; padding: 0;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="lamaranForm">
                    <div class="form-grid">
                        <!-- Nama Lengkap -->
                        <div class="form-group">
                            <label for="nama" class="form-label">
                                Nama Lengkap <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="nama" 
                                   name="nama" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($_POST['nama'] ?? $namaLengkap) ?>"
                                   required
                                   placeholder="Masukkan nama lengkap">
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">
                                Email <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? $userEmail) ?>"
                                   required
                                   placeholder="contoh@email.com">
                        </div>

                        <!-- Telepon -->
                        <div class="form-group">
                            <label for="telepon" class="form-label">
                                Nomor Telepon/HP <span class="required">*</span>
                            </label>
                            <input type="tel" 
                                   id="telepon" 
                                   name="telepon" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>"
                                   required
                                   placeholder="0812-3456-7890">
                        </div>

                        <!-- Pendidikan -->
                        <div class="form-group">
                            <label for="pendidikan" class="form-label">
                                Pendidikan Terakhir <span class="required">*</span>
                            </label>
                            <select id="pendidikan" name="pendidikan" class="form-control select" required>
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="SMA/SMK" <?= ($_POST['pendidikan'] ?? '') == 'SMA/SMK' ? 'selected' : '' ?>>SMA/SMK</option>
                                <option value="D1/D2/D3" <?= ($_POST['pendidikan'] ?? '') == 'D1/D2/D3' ? 'selected' : '' ?>>D1/D2/D3</option>
                                <option value="D4/S1" <?= ($_POST['pendidikan'] ?? '') == 'D4/S1' ? 'selected' : '' ?>>D4/S1</option>
                                <option value="S2" <?= ($_POST['pendidikan'] ?? '') == 'S2' ? 'selected' : '' ?>>S2</option>
                                <option value="S3" <?= ($_POST['pendidikan'] ?? '') == 'S3' ? 'selected' : '' ?>>S3</option>
                            </select>
                        </div>

                        <!-- Pengalaman Kerja -->
                        <div class="form-group full-width">
                            <label for="pengalaman" class="form-label">
                                Pengalaman Kerja <span class="required">*</span>
                            </label>
                            <textarea id="pengalaman" 
                                      name="pengalaman" 
                                      class="form-control textarea" 
                                      required
                                      placeholder="Jelaskan pengalaman kerja Anda (minimal 50 karakter)"><?= htmlspecialchars($_POST['pengalaman'] ?? '') ?></textarea>
                            <div id="pengalamanCounter" class="char-counter">0/50 karakter</div>
                            <small class="file-info">Jelaskan pengalaman kerja yang relevan dengan posisi ini</small>
                        </div>

                        <!-- Motivasi Bergabung -->
                        <div class="form-group full-width">
                            <label for="motivasi" class="form-label">
                                Motivasi Bergabung <span class="required">*</span>
                            </label>
                            <textarea id="motivasi" 
                                      name="motivasi" 
                                      class="form-control textarea" 
                                      required
                                      placeholder="Jelaskan alasan dan motivasi Anda bergabung dengan PT INTIM (minimal 100 karakter)"><?= htmlspecialchars($_POST['motivasi'] ?? '') ?></textarea>
                            <div id="motivasiCounter" class="char-counter">0/100 karakter</div>
                            <small class="file-info">Jelaskan mengapa Anda tertarik bergabung dengan perusahaan kami</small>
                        </div>

                        <!-- Upload CV -->
                        <div class="form-group">
                            <label for="cv" class="form-label">
                                Upload CV <span class="required">*</span>
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" 
                                       id="cv" 
                                       name="cv" 
                                       class="file-upload-input"
                                       accept=".pdf,.doc,.docx"
                                       required
                                       onchange="previewFileName('cv', 'cvPreview')">
                                <button type="button" class="file-upload-button" onclick="document.getElementById('cv').click()">
                                    <i class="fas fa-upload"></i> Pilih File
                                </button>
                            </div>
                            <div id="cvPreview" class="file-info">
                                <i class="fas fa-info-circle"></i> Format: PDF, DOC, DOCX (maks. 2MB)
                            </div>
                        </div>

                        <!-- Upload Foto -->
                        <div class="form-group">
                            <label for="foto" class="form-label">
                                Upload Foto <span class="required">*</span>
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" 
                                       id="foto" 
                                       name="foto" 
                                       class="file-upload-input"
                                       accept="image/*"
                                       required
                                       onchange="previewFileName('foto', 'fotoPreview')">
                                <button type="button" class="file-upload-button" onclick="document.getElementById('foto').click()">
                                    <i class="fas fa-upload"></i> Pilih File
                                </button>
                            </div>
                            <div id="fotoPreview" class="file-info">
                                <i class="fas fa-info-circle"></i> Format: JPG, PNG (maks. 1MB)
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="berita_karir.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Kembali
                        </a>
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Kirim Lamaran
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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
                <h4>Lowongan Terbaru</h4>
                <?php
                // Ambil 5 lowongan terbaru untuk footer
                $recentKarir = mysqli_query($conn, "SELECT * FROM karir WHERE is_active = 1 AND deadline >= CURDATE() ORDER BY created_at DESC LIMIT 5");
                if ($recentKarir && mysqli_num_rows($recentKarir) > 0):
                    while($recent = mysqli_fetch_assoc($recentKarir)):
                ?>
                <a href="lamar.php?id=<?= $recent['id'] ?>" class="recent-job">
                    <strong><?= htmlspecialchars($recent['position']) ?></strong><br>
                    <small><?= htmlspecialchars($recent['department']) ?> • <?= htmlspecialchars($recent['type']) ?></small>
                </a>
                <?php 
                    endwhile;
                else:
                    echo '<p>Tidak ada lowongan saat ini</p>';
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

        // Enhanced Dropdown Functionality - SEPERTI user_dashboard.php
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
            
            // Close user menu when clicking outside
            document.addEventListener('click', function() {
                if (userMenu) userMenu.classList.remove('active');
            });
        });

        // Mobile Menu Functionality - SEPERTI user_dashboard.php
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

        // ==================== TIDAK MENGUBAH FUNGSIONALITAS UTAMA DI BAWAH INI ====================
        // File name preview
        function previewFileName(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (input.files.length > 0) {
                const fileName = input.files[0].name;
                const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2); // MB
                preview.innerHTML = `<i class="fas fa-file"></i> ${fileName} (${fileSize} MB)`;
            }
        }

        // Character counters
        const pengalamanTextarea = document.getElementById('pengalaman');
        const motivasiTextarea = document.getElementById('motivasi');
        const pengalamanCounter = document.getElementById('pengalamanCounter');
        const motivasiCounter = document.getElementById('motivasiCounter');

        function updateCharacterCounter(textarea, counter, minLength) {
            const count = textarea.value.length;
            counter.textContent = `${count}/${minLength} karakter`;
            counter.style.color = count >= minLength ? 'var(--success)' : 'var(--danger)';
        }

        if (pengalamanTextarea && pengalamanCounter) {
            pengalamanTextarea.addEventListener('input', function() {
                updateCharacterCounter(this, pengalamanCounter, 50);
            });
            // Initial update
            updateCharacterCounter(pengalamanTextarea, pengalamanCounter, 50);
        }

        if (motivasiTextarea && motivasiCounter) {
            motivasiTextarea.addEventListener('input', function() {
                updateCharacterCounter(this, motivasiCounter, 100);
            });
            // Initial update
            updateCharacterCounter(motivasiTextarea, motivasiCounter, 100);
        }

        // Form validation
        const form = document.getElementById('lamaranForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Validation
                const pengalaman = document.getElementById('pengalaman');
                const motivasi = document.getElementById('motivasi');
                const cvFile = document.getElementById('cv').files[0];
                const fotoFile = document.getElementById('foto').files[0];
                
                let isValid = true;
                let errorMessage = '';
                
                // Check text lengths
                if (pengalaman.value.length < 50) {
                    isValid = false;
                    errorMessage = 'Pengalaman kerja minimal 50 karakter\n';
                }
                
                if (motivasi.value.length < 100) {
                    isValid = false;
                    errorMessage += 'Motivasi bergabung minimal 100 karakter\n';
                }
                
                // Check file sizes
                if (cvFile && cvFile.size > 2 * 1024 * 1024) {
                    isValid = false;
                    errorMessage += 'Ukuran CV maksimal 2MB\n';
                }
                
                if (fotoFile && fotoFile.size > 1 * 1024 * 1024) {
                    isValid = false;
                    errorMessage += 'Ukuran foto maksimal 1MB\n';
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                    return;
                }
                
                // Disable submit button and show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            });
        }

        // Auto-format phone number
        const phoneInput = document.getElementById('telepon');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = value;
                    } else if (value.length <= 6) {
                        value = value.substring(0, 3) + '-' + value.substring(3);
                    } else if (value.length <= 9) {
                        value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6);
                    } else {
                        value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 9) + '-' + value.substring(9, 12);
                    }
                }
                
                e.target.value = value;
            });
        }
    </script>
</body>
</html>