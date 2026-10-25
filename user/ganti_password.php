<?php
session_start();
include '../includes/config.php';
require_once '../includes/auth.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user
$username = $_SESSION['username'];
$userQuery = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userQuery);
$namaLengkap = $userData['username'] ?? $username;

// Variabel untuk pesan
$message = '';
$message_type = ''; // success atau error

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi!';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi password tidak cocok!';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password baru minimal 6 karakter!';
        $message_type = 'error';
    } else {
        // Verifikasi password saat ini
        if (password_verify($current_password, $userData['password'])) {
            // Hash password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password di database
            $update_query = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE id = " . $userData['id'];
            
            if (mysqli_query($conn, $update_query)) {
                // Set session message untuk ditampilkan di halaman dashboard
                $_SESSION['password_change_success'] = 'Password berhasil diubah!';
                
                // Redirect ke halaman dashboard setelah 2 detik
                header("refresh:2;url=user_dashboard.php");
                $message = 'Password berhasil diubah! Anda akan diarahkan kembali ke dashboard...';
                $message_type = 'success';
            } else {
                $message = 'Terjadi kesalahan saat mengubah password. Silakan coba lagi.';
                $message_type = 'error';
            }
        } else {
            $message = 'Password saat ini salah!';
            $message_type = 'error';
        }
    }
}

// Function untuk mencari gambar lokal
function getLocalImage($imageName, $default = 'logo.png') {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    $possibleLocations = [
        '../uploads/' . $imageName,
        '../assets/images/' . $imageName,
        '../images/' . $imageName,
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - PT INTIM</title>
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
            --success: #27ae60;
            --success-light: #d5f4e6;
            --error: #e74c3c;
            --error-light: #fadbd8;
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

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Loading Screen */
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

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 9999;
            padding: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: var(--shadow);
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
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        /* User Menu */
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
            transition: var(--transition);
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

        /* Back Button */
        .back-button {
            position: absolute;
            left: 40px;
            top: 50%;
            transform: translateY(-50%);
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .back-button:hover {
            color: var(--accent);
            transform: translateY(-50%) translateX(-4px);
        }

        /* Main Content */
        .main-content {
            padding-top: 120px;
            padding-bottom: 60px;
            min-height: calc(100vh - 60px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        /* Password Change Card */
        .password-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
            border: 1px solid var(--gray-100);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            padding: 40px;
            text-align: center;
            color: var(--white);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
        }

        .card-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .card-body {
            padding: 40px;
        }

        /* Message Box */
        .message-box {
            margin-bottom: 24px;
            padding: 16px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease-out;
        }

        .message-box.success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .message-box.error {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .message-box.info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid rgba(25, 118, 210, 0.2);
        }

        .message-box i {
            font-size: 20px;
        }

        /* Countdown Timer */
        .countdown-timer {
            display: inline-block;
            margin-left: 8px;
            font-weight: bold;
            color: var(--accent);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-control.error {
            border-color: var(--error);
        }

        .form-control.success {
            border-color: var(--success);
        }

        .password-input-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--accent);
        }

        .form-help {
            margin-top: 8px;
            font-size: 13px;
            color: var(--text-light);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(52, 152, 219, 0.2);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            background: var(--error);
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-bar.weak {
            width: 33.33%;
            background: var(--error);
        }

        .strength-bar.medium {
            width: 66.66%;
            background: #f39c12;
        }

        .strength-bar.strong {
            width: 100%;
            background: var(--success);
        }

        .strength-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
            font-size: 12px;
            color: var(--text-light);
        }

        /* Footer */
        .footer {
            background: var(--primary);
            color: var(--white);
            padding: 40px 0;
            text-align: center;
        }

        .footer p {
            opacity: 0.8;
            font-size: 14px;
        }

        /* Redirect Overlay */
        .redirect-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .redirect-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .redirect-content {
            text-align: center;
            max-width: 400px;
            padding: 40px;
        }

        .redirect-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--white);
            font-size: 32px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container,
            .container {
                padding: 0 24px;
            }
            
            .header {
                padding: 0;
            }
            
            .nav-container {
                height: 70px;
            }
            
            .logo img {
                height: 35px;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 15px;
            }
            
            .main-content {
                padding-top: 100px;
                padding-bottom: 40px;
            }
            
            .password-card {
                max-width: 100%;
            }
            
            .card-header,
            .card-body {
                padding: 30px 24px;
            }
            
            .card-header h1 {
                font-size: 28px;
            }
            
            .back-button {
                left: 24px;
            }
            
            .redirect-content {
                padding: 30px 24px;
            }
        }

        @media (max-width: 480px) {
            .nav-container,
            .container {
                padding: 0 20px;
            }
            
            .logo span {
                display: none;
            }
            
            .card-header {
                padding: 30px 20px;
            }
            
            .card-body {
                padding: 24px 20px;
            }
            
            .btn {
                padding: 14px 24px;
                font-size: 14px;
            }
            
            .redirect-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Redirect Overlay -->
    <div class="redirect-overlay" id="redirectOverlay">
        <div class="redirect-content">
            <div class="redirect-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 style="color: var(--success); margin-bottom: 16px;">Password Berhasil Diubah!</h2>
            <p style="color: var(--text-light); margin-bottom: 24px;">
                Anda akan diarahkan kembali ke dashboard dalam <span id="countdown">3</span> detik...
            </p>
            <a href="user_dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i>
                Ke Dashboard Sekarang
            </a>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="user_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Dashboard
            </a>
            
            <a href="user_dashboard.php" class="logo">
                <img src="<?= getLocalImage('logo small black.png') ?>" alt="PT INTIM">
                <span>PT INTIM</span>
            </a>

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
                        <a href="user_dashboard.php" class="user-action-item">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="ganti_password.php" class="user-action-item">
                            <i class="fas fa-key"></i>
                            <span>Ganti Password</span>
                        </a>
                        <a href="../logout.php" class="user-action-item logout" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="password-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h1>Ganti Password</h1>
                    <p>Pastikan password baru Anda kuat dan mudah diingat</p>
                </div>
                
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="message-box <?= $message_type ?>">
                            <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                            <?php if ($message_type == 'success'): ?>
                                <span id="redirectCountdown" class="countdown-timer">3</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="passwordForm">
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-lock"></i>
                                Password Saat Ini
                            </label>
                            <div class="password-input-container">
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control" 
                                       placeholder="Masukkan password saat ini"
                                       required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-key"></i>
                                Password Baru
                            </label>
                            <div class="password-input-container">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       placeholder="Masukkan password baru (minimal 6 karakter)"
                                       required
                                       minlength="6">
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-labels">
                                <span>Lemah</span>
                                <span>Sedang</span>
                                <span>Kuat</span>
                            </div>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Password harus minimal 6 karakter
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-key"></i>
                                Konfirmasi Password Baru
                            </label>
                            <div class="password-input-container">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       placeholder="Masukkan kembali password baru"
                                       required
                                       minlength="6">
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-help" id="passwordMatchMessage"></div>
                        </div>

                        <div class="form-group" style="margin-top: 32px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i>
                                Ganti Password
                            </button>
                        </div>

                        <div class="form-group" style="margin-top: 16px;">
                            <a href="user_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> PT Innagroup Textile Manufacture. All rights reserved.</p>
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

        // User Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userAvatar = document.getElementById('userAvatar');
            const userMenu = document.getElementById('userMenu');
            
            if (userAvatar && userMenu) {
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                });
            }
            
            // Close user menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-menu')) {
                    userMenu.classList.remove('active');
                }
            });
            
            // Auto redirect jika berhasil ganti password
            const successMessage = document.querySelector('.message-box.success');
            if (successMessage) {
                // Tampilkan overlay redirect setelah 1 detik
                setTimeout(() => {
                    const redirectOverlay = document.getElementById('redirectOverlay');
                    if (redirectOverlay) {
                        redirectOverlay.classList.add('active');
                        document.body.style.overflow = 'hidden';
                        
                        // Countdown timer
                        let countdown = 3;
                        const countdownElement = document.getElementById('countdown');
                        const redirectCountdown = document.getElementById('redirectCountdown');
                        
                        const updateCountdown = () => {
                            if (countdownElement) countdownElement.textContent = countdown;
                            if (redirectCountdown) redirectCountdown.textContent = countdown;
                            
                            if (countdown > 0) {
                                countdown--;
                                setTimeout(updateCountdown, 1000);
                            } else {
                                // Redirect setelah countdown selesai
                                window.location.href = 'user_dashboard.php';
                            }
                        };
                        
                        updateCountdown();
                    }
                }, 1000);
                
                // Auto-hide pesan sukses
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (successMessage.parentNode) {
                            successMessage.parentNode.removeChild(successMessage);
                        }
                    }, 300);
                }, 1000);
            }
        });

        // Toggle Password Visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password Strength Indicator
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('strengthBar');
        
        if (newPasswordInput && strengthBar) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check password length
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                
                // Check for mixed case
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                
                // Check for numbers
                if (/\d/.test(password)) strength++;
                
                // Check for special characters
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength bar
                strengthBar.className = 'strength-bar';
                
                if (password.length === 0) {
                    strengthBar.style.width = '0%';
                } else if (strength <= 2) {
                    strengthBar.classList.add('weak');
                } else if (strength <= 4) {
                    strengthBar.classList.add('medium');
                } else {
                    strengthBar.classList.add('strong');
                }
            });
        }

        // Password Match Validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchMessage = document.getElementById('passwordMatchMessage');
        
        function checkPasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatchMessage.innerHTML = '';
                confirmPasswordInput.classList.remove('error', 'success');
            } else if (newPassword === confirmPassword) {
                passwordMatchMessage.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> Password cocok';
                confirmPasswordInput.classList.remove('error');
                confirmPasswordInput.classList.add('success');
            } else {
                passwordMatchMessage.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--error);"></i> Password tidak cocok';
                confirmPasswordInput.classList.remove('success');
                confirmPasswordInput.classList.add('error');
            }
        }
        
        if (newPasswordInput && confirmPasswordInput && passwordMatchMessage) {
            newPasswordInput.addEventListener('input', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }

        // Form Validation
        const form = document.getElementById('passwordForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                // Reset error states
                document.querySelectorAll('.form-control').forEach(input => {
                    input.classList.remove('error');
                });
                
                let hasError = false;
                
                // Validate current password
                if (!currentPassword) {
                    document.getElementById('current_password').classList.add('error');
                    hasError = true;
                }
                
                // Validate new password
                if (!newPassword || newPassword.length < 6) {
                    document.getElementById('new_password').classList.add('error');
                    hasError = true;
                }
                
                // Validate password match
                if (newPassword !== confirmPassword) {
                    document.getElementById('confirm_password').classList.add('error');
                    hasError = true;
                }
                
                // Prevent form submission if there are errors
                if (hasError) {
                    e.preventDefault();
                    
                    // Show error message
                    const messageBox = document.querySelector('.message-box');
                    if (!messageBox || !messageBox.classList.contains('error')) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'message-box error';
                        errorMsg.innerHTML = `
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Harap periksa kembali input Anda</span>
                        `;
                        
                        const cardBody = document.querySelector('.card-body');
                        const existingMsg = cardBody.querySelector('.message-box');
                        if (existingMsg) {
                            cardBody.removeChild(existingMsg);
                        }
                        cardBody.insertBefore(errorMsg, cardBody.firstChild);
                        
                        // Shake animation for error
                        errorMsg.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            errorMsg.style.animation = '';
                        }, 500);
                    }
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + S to submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.getElementById('passwordForm').dispatchEvent(new Event('submit'));
            }
            
            // Escape to go back
            if (e.key === 'Escape') {
                e.preventDefault();
                window.location.href = 'user_dashboard.php';
            }
        });

        // Manual redirect jika user klik "Ke Dashboard Sekarang"
        document.querySelectorAll('a[href="user_dashboard.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.closest('.redirect-overlay') || this.classList.contains('btn-secondary')) {
                    // Biarkan link bekerja normal
                    return true;
                }
            });
        });
    </script>
</body>
</html>