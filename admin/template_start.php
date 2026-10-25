<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel - PT INTIM' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.php">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="sidebar-logo">
                    <img src="../logo small black.png" alt="PT INTIM">
                </a>
            </div>
            
            <div class="admin-info">
                <div class="admin-avatar">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="admin-name"><?= htmlspecialchars($username) ?></div>
                <div class="admin-role">Administrator</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-title">Menu Utama</div>
                <a href="admin_dashboard.php" class="nav-item <?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="nav-title">Manajemen Konten</div>
                <a href="galeri_admin.php" class="nav-item <?= $current_page == 'galeri_admin.php' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i>
                    <span>Galeri</span>
                </a>
                <a href="admin_karir.php" class="nav-item <?= $current_page == 'admin_karir.php' ? 'active' : '' ?>">
                    <i class="fas fa-briefcase"></i>
                    <span>Lowongan Karir</span>
                </a>
                <a href="admin_lamar.php" class="nav-item <?= $current_page == 'admin_lamar.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Data Pelamar</span>
                </a>
                <a href="admin_kegiatan.php" class="nav-item <?= $current_page == 'admin_kegiatan.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Kegiatan</span>
                </a>
                <a href="admin_kontak.php" class="nav-item <?= $current_page == 'admin_kontak.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Pesan Kontak</span>
                </a>
                
                <div class="nav-title">Pengaturan</div>
                <a href="admin_users.php" class="nav-item <?= $current_page == 'admin_users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manajemen User</span>
                </a>
            </nav>
            
            <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </aside>
    </div>
</body>
